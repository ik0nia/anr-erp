<?php
/**
 * Helper pentru modulul Registratura - alocare nr. înregistrare, setări
 */

/**
 * Returnează următorul număr de înregistrare intern
 * Logica: Dacă există documente cu numere >= start, continuă de la max + 1
 *         Dacă nu există documente sau toate au numere < start, începe de la start
 * @param PDO $pdo
 * @return int
 */
function registratura_urmatorul_nr(PDO $pdo) {
    ensure_registratura_table($pdo);
    // Citește numărul de pornire din setări (actualizat din Setări)
    $start = (int) get_setare_registratura($pdo, 'registratura_nr_pornire', 1);
    if ($start < 1) {
        $start = 1; // Validare minim
    }
    
    // Găsește numărul maxim existent în registratură
    try {
        $stmt = $pdo->query("SELECT COALESCE(MAX(nr_intern), 0) as mx FROM registratura");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $max = (int)($row['mx'] ?? 0);
    } catch (PDOException $e) {
        // Dacă tabelul nu există sau eșuează query-ul, folosește start
        return $start;
    }
    
    // Dacă există documente cu numere >= start, continuă numerotarea
    // Altfel, începe de la numărul de start setat în Setări
    return $max >= $start ? $max + 1 : $start;
}

/**
 * Citește o setare din tabelul setari
 */
function get_setare_registratura(PDO $pdo, string $cheie, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = ?");
        $stmt->execute([$cheie]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && $r['valoare'] !== null && $r['valoare'] !== '') {
            return $r['valoare'];
        }
        return $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Asigură existența tabelului registratura cu toate coloanele necesare
 */
function ensure_registratura_table(PDO $pdo) {
    // No-op: schema is managed by install/schema/migration.php
    return;
}

/**
 * Înregistrează un document în registratură (pentru apeluri din alte module)
 * @param PDO $pdo
 * @param array $data Chei: tip_act, detalii, continut_document, provine_din, destinatar_document, membru_id, document_path, task_deschis
 * @return array ['success'=>bool, 'id'=>int, 'nr_inregistrare'=>string] sau ['success'=>false, 'error'=>string]
 */
function registratura_inregistreaza_document(PDO $pdo, array $data) {
    try {
        ensure_registratura_table($pdo);
        $utilizator = $_SESSION['utilizator'] ?? 'Sistem';
        // Task se creează strict doar când checkbox-ul este bifat explicit.
        $task_deschis = 0;
        if (array_key_exists('task_deschis', $data)) {
            $raw_task = is_array($data['task_deschis']) ? '' : trim((string)$data['task_deschis']);
            $task_deschis = ($raw_task === '1' || strcasecmp($raw_task, 'on') === 0 || strcasecmp($raw_task, 'true') === 0) ? 1 : 0;
        }
        $task_id = null;

        // Retry în caz de conflict pe nr_intern (UNIQUE constraint)
        $max_incercari = 3;
        $id = null;
        $nr_intern = null;
        $nr_inregistrare = null;

        for ($incercare = 0; $incercare < $max_incercari; $incercare++) {
            if (isset($data['nr_intern']) && is_numeric($data['nr_intern']) && (int)$data['nr_intern'] > 0) {
                $nr_intern = (int)$data['nr_intern'];
            } else {
                $nr_intern = registratura_urmatorul_nr($pdo);
            }
            $nr_inregistrare = (string) $nr_intern;

            try {
                $stmt = $pdo->prepare('INSERT INTO registratura (nr_intern, nr_inregistrare, utilizator, tip_act, detalii, nr_document, data_document, provine_din, continut_document, destinatar_document, task_deschis, membru_id, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $nr_intern,
                    $nr_inregistrare,
                    $utilizator,
                    $data['tip_act'] ?? 'Document',
                    $data['detalii'] ?? null,
                    $data['nr_document'] ?? null,
                    $data['data_document'] ?? null,
                    $data['provine_din'] ?? null,
                    $data['continut_document'] ?? $data['detalii'] ?? null,
                    $data['destinatar_document'] ?? null,
                    $task_deschis,
                    $data['membru_id'] ?? null,
                    $data['document_path'] ?? null
                ]);
                $id = (int) $pdo->lastInsertId();
                break; // Insert reușit
            } catch (PDOException $e) {
                // Duplicate key pe nr_intern — reîncearcă cu următorul număr
                if ($incercare < $max_incercari - 1 && strpos($e->getMessage(), 'Duplicate') !== false) {
                    continue;
                }
                throw $e;
            }
        }

        if ($task_deschis) {
            $continut = $data['continut_document'] ?? $data['detalii'] ?? '';
            $nume_task = 'Registratura nr. ' . $nr_inregistrare . ': ' . mb_substr($continut, 0, 80);
            $detalii_task = ($data['provine_din'] ? "Provine din: {$data['provine_din']}\n" : '') . ($data['destinatar_document'] ? "Destinatar: {$data['destinatar_document']}\n" : '') . $continut;
            $stmt_t = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, NOW(), ?, ?)');
            $stmt_t->execute([$nume_task, $detalii_task ?: null, 'normal']);
            $task_id = $pdo->lastInsertId();
            $pdo->prepare('UPDATE registratura SET task_id = ? WHERE id = ?')->execute([$task_id, $id]);
        }

        return ['success' => true, 'id' => $id, 'nr_inregistrare' => $nr_inregistrare];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
