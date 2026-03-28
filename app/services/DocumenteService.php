<?php
/**
 * Service: Documente — Gestionare templateuri documente
 *
 * Wrapper peste document_helper.php + operatii CRUD pe documente_template
 */
require_once APP_ROOT . '/includes/document_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Asigura existenta tabelei si directorului pentru templateuri
 */
function documente_ensure_table(PDO $pdo): ?string {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS documente_template (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume_afisare VARCHAR(255) NOT NULL,
            nume_fisier VARCHAR(255) NOT NULL,
            activ TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_activ (activ)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        // Mapare manuală coordonate taguri pentru template-uri PDF (fallback hibrid).
        $pdo->exec("CREATE TABLE IF NOT EXISTS documente_template_pdf_mapari (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_id INT NOT NULL UNIQUE,
            mapari_text TEXT NULL,
            updated_by VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_doc_tpl_pdf_map_template (template_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        documente_ensure_generated_table($pdo);
        if (!is_dir(UPLOAD_TEMPLATE_DIR)) {
            mkdir(UPLOAD_TEMPLATE_DIR, 0755, true);
        }
        return null;
    } catch (PDOException $e) {
        return 'Eroare la initializare: ' . $e->getMessage();
    }
}

/**
 * Listeaza toate templateurile ordonate dupa nume
 */
function documente_list_templates(PDO $pdo): array {
    $stmt = $pdo->query('SELECT * FROM documente_template ORDER BY nume_afisare ASC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Upload template nou: valideaza, muta fisierul, insereaza in DB
 * @return string|null — mesaj eroare sau null daca succes
 */
function documente_upload_template(PDO $pdo, string $nume_afisare, array $file_info): ?string {
    if (empty($nume_afisare)) {
        return 'Numele afisat este obligatoriu.';
    }
    if (!isset($file_info) || $file_info['error'] !== UPLOAD_ERR_OK) {
        return 'Selectati un fisier .docx sau .pdf valid.';
    }

    $ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['docx', 'pdf'], true)) {
        return 'Doar fisiere Word (.docx) sau PDF (.pdf) sunt acceptate.';
    }
    if ($file_info['size'] > 10 * 1024 * 1024) {
        return 'Fisierul depaseste 10 MB.';
    }
    if (!empty($file_info['tmp_name'])) {
        $integrity = documente_validate_template_integrity($file_info['tmp_name'], $ext);
        if (is_array($integrity)) {
            if (empty($integrity['ok'])) {
                return trim((string)($integrity['error'] ?? 'Template invalid sau corupt.'));
            }
        } elseif (!$integrity) {
            return $ext === 'pdf'
                ? 'Template PDF invalid sau corupt. Verificati fisierul si reincarcati.'
                : 'Template DOCX invalid sau corupt. Verificati fisierul si reincarcati.';
        }
    }

    $filename = 'tpl_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nume_afisare) . '.' . $ext;
    $filepath = UPLOAD_TEMPLATE_DIR . $filename;

    if (!move_uploaded_file($file_info['tmp_name'], $filepath)) {
        return 'Eroare la incarcarea fisierului.';
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO documente_template (nume_afisare, nume_fisier, activ) VALUES (?, ?, 1)');
        $stmt->execute([$nume_afisare, $filename]);
        log_activitate($pdo, 'Template document adaugat: ' . $nume_afisare);
        return null;
    } catch (PDOException $e) {
        unlink($filepath);
        return 'Eroare la salvare: ' . $e->getMessage();
    }
}

/**
 * Sterge un template (din DB si de pe disk)
 * @return string|null — mesaj eroare sau null daca succes
 */
function documente_delete_template(PDO $pdo, int $id): ?string {
    if ($id <= 0) return 'ID invalid.';

    try {
        $stmt = $pdo->prepare('SELECT nume_afisare, nume_fisier FROM documente_template WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $pdo->prepare('DELETE FROM documente_template WHERE id = ?')->execute([$id]);
        $fisier = UPLOAD_TEMPLATE_DIR . $row['nume_fisier'];
        if (file_exists($fisier)) {
            @unlink($fisier);
        }
        log_activitate($pdo, 'Template document sters: ' . $row['nume_afisare'] . ' (ID ' . $id . ')');
        return null;
    } catch (PDOException $e) {
        return 'Eroare la stergere.';
    }
}

/**
 * Actualizeaza nume si status activ al unui template
 * @return string|null — mesaj eroare sau null daca succes
 */
function documente_update_template(PDO $pdo, int $id, string $nume_afisare, int $activ): ?string {
    if ($id <= 0 || empty($nume_afisare)) return 'Date invalide.';

    try {
        $stmt_old = $pdo->prepare('SELECT nume_afisare, activ FROM documente_template WHERE id = ?');
        $stmt_old->execute([$id]);
        $template_vechi = $stmt_old->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('UPDATE documente_template SET nume_afisare = ?, activ = ? WHERE id = ?');
        $stmt->execute([$nume_afisare, $activ, $id]);

        $modificari = [];
        if ($template_vechi) {
            if (($template_vechi['nume_afisare'] ?? '') !== $nume_afisare) {
                $modificari[] = log_format_modificare('Nume template', $template_vechi['nume_afisare'] ?? '', $nume_afisare);
            }
            if (($template_vechi['activ'] ?? 0) != $activ) {
                $modificari[] = log_format_modificare('Status activ', ($template_vechi['activ'] ?? 0) ? 'Activ' : 'Inactiv', $activ ? 'Activ' : 'Inactiv');
            }
        }

        if (!empty($modificari)) {
            log_activitate($pdo, "documente_template: " . implode("; ", $modificari) . " / Template ID: {$id}");
        } else {
            log_activitate($pdo, "documente_template: Template actualizat ID {$id}");
        }
        return null;
    } catch (PDOException $e) {
        return 'Eroare la actualizare.';
    }
}

/**
 * Validează maparea manuală pentru template PDF.
 */
function documente_validate_pdf_mapari_text(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '') return null;
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $linia = 0;
    foreach ($lines as $line) {
        $linia++;
        $line = trim((string)$line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 4) {
            return "Linia {$linia}: format invalid. Folositi [tag]|pagina|x_mm|y_mm|font_pt";
        }
        if (!preg_match('/^\[[a-zA-Z0-9_]+\]$/', (string)$parts[0])) {
            return "Linia {$linia}: tag invalid. Folositi forma [nume_tag].";
        }
        if (!is_numeric($parts[1]) || (int)$parts[1] < 1) {
            return "Linia {$linia}: pagina trebuie sa fie numar intreg >= 1.";
        }
        if (!is_numeric($parts[2]) || !is_numeric($parts[3])) {
            return "Linia {$linia}: coordonatele x_mm si y_mm trebuie sa fie numerice.";
        }
        if (isset($parts[4]) && $parts[4] !== '' && !is_numeric($parts[4])) {
            return "Linia {$linia}: font_pt trebuie sa fie numeric.";
        }
    }
    return null;
}

/**
 * Salvează maparea manuală de coordonate pentru un template PDF.
 */
function documente_save_pdf_mapari(PDO $pdo, int $template_id, string $mapari_text, string $updated_by = 'Sistem'): ?string {
    if ($template_id <= 0) return 'Template invalid pentru mapare.';
    $mapari_text = trim($mapari_text);
    $validErr = documente_validate_pdf_mapari_text($mapari_text);
    if ($validErr !== null) return $validErr;
    try {
        $stmtTpl = $pdo->prepare('SELECT nume_fisier, nume_afisare FROM documente_template WHERE id = ? LIMIT 1');
        $stmtTpl->execute([$template_id]);
        $tpl = $stmtTpl->fetch(PDO::FETCH_ASSOC);
        if (!$tpl) return 'Template inexistent.';
        $ext = strtolower((string)pathinfo((string)$tpl['nume_fisier'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') return 'Maparea manuala se aplica doar pentru template-urile PDF.';

        $stmt = $pdo->prepare("INSERT INTO documente_template_pdf_mapari (template_id, mapari_text, updated_by)
                               VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE mapari_text = VALUES(mapari_text), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$template_id, $mapari_text, $updated_by]);
        log_activitate($pdo, 'documente_template: mapari PDF actualizate / Template ID: ' . $template_id . ' / ' . ($tpl['nume_afisare'] ?? 'Template'));
        return null;
    } catch (PDOException $e) {
        return 'Eroare la salvarea maparilor PDF.';
    }
}

/**
 * Încarcă maparea manuală pentru template PDF (din tabel dedicat).
 */
function documente_get_pdf_mapari(PDO $pdo, int $template_id): string {
    if ($template_id <= 0) return '';
    try {
        $stmt = $pdo->prepare('SELECT mapari_text FROM documente_template_pdf_mapari WHERE template_id = ? LIMIT 1');
        $stmt->execute([$template_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return trim((string)($row['mapari_text'] ?? ''));
    } catch (PDOException $e) {
        return '';
    }
}
