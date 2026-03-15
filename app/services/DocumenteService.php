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
        return 'Selectati un fisier .docx valid.';
    }

    $ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
    if ($ext !== 'docx') {
        return 'Doar fisiere Word (.docx) sunt acceptate.';
    }
    if ($file_info['size'] > 10 * 1024 * 1024) {
        return 'Fisierul depaseste 10 MB.';
    }

    $filename = 'tpl_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nume_afisare) . '.docx';
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
