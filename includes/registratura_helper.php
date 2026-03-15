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
 * Asigură că tabelul setari există înainte de citire
 */
function get_setare_registratura(PDO $pdo, string $cheie, $default = null) {
    try {
        // Asigură existența tabelului setari
        $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cheie VARCHAR(100) NOT NULL UNIQUE,
            valoare TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = ?");
        $stmt->execute([$cheie]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && $r['valoare'] !== null && $r['valoare'] !== '') {
            return $r['valoare'];
        }
        return $default;
    } catch (PDOException $e) {
        // În caz de eroare, returnează valoarea default
        return $default;
    }
}

/**
 * Asigură existența tabelului registratura cu toate coloanele necesare
 */
function ensure_registratura_table(PDO $pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cheie VARCHAR(100) NOT NULL UNIQUE,
        valoare TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS registratura (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nr_intern INT NOT NULL DEFAULT 0,
        nr_inregistrare VARCHAR(50) NOT NULL,
        data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        utilizator VARCHAR(100) NOT NULL,
        tip_act VARCHAR(100) DEFAULT NULL,
        detalii TEXT DEFAULT NULL,
        nr_document VARCHAR(100) DEFAULT NULL,
        data_document DATE DEFAULT NULL,
        provine_din VARCHAR(500) DEFAULT NULL,
        continut_document TEXT DEFAULT NULL,
        destinatar_document VARCHAR(500) DEFAULT NULL,
        task_deschis TINYINT(1) NOT NULL DEFAULT 0,
        task_id INT DEFAULT NULL,
        membru_id INT DEFAULT NULL,
        document_path VARCHAR(500) DEFAULT NULL,
        INDEX idx_data_ora (data_ora),
        INDEX idx_utilizator (utilizator),
        INDEX idx_nr_inregistrare (nr_inregistrare),
        INDEX idx_nr_intern (nr_intern)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Adaugă coloane noi dacă nu există
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM registratura")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return;
    }
    $to_add = [
        'nr_intern' => "ALTER TABLE registratura ADD COLUMN nr_intern INT NOT NULL DEFAULT 0 AFTER id",
        'nr_document' => "ALTER TABLE registratura ADD COLUMN nr_document VARCHAR(100) DEFAULT NULL AFTER detalii",
        'data_document' => "ALTER TABLE registratura ADD COLUMN data_document DATE DEFAULT NULL AFTER nr_document",
        'provine_din' => "ALTER TABLE registratura ADD COLUMN provine_din VARCHAR(500) DEFAULT NULL AFTER data_document",
        'continut_document' => "ALTER TABLE registratura ADD COLUMN continut_document TEXT DEFAULT NULL AFTER provine_din",
        'destinatar_document' => "ALTER TABLE registratura ADD COLUMN destinatar_document VARCHAR(500) DEFAULT NULL AFTER continut_document",
        'task_deschis' => "ALTER TABLE registratura ADD COLUMN task_deschis TINYINT(1) NOT NULL DEFAULT 0 AFTER destinatar_document",
        'task_id' => "ALTER TABLE registratura ADD COLUMN task_id INT DEFAULT NULL AFTER task_deschis",
    ];
    foreach ($to_add as $col => $sql) {
        if (!in_array($col, $cols)) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // Coloana poate exista deja sau ALTER eșuează – continuăm
            }
        }
    }
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
        if (isset($data['nr_intern']) && is_numeric($data['nr_intern']) && (int)$data['nr_intern'] > 0) {
            $nr_intern = (int)$data['nr_intern'];
            $nr_inregistrare = (string) $nr_intern;
        } else {
            $nr_intern = registratura_urmatorul_nr($pdo);
            $nr_inregistrare = (string) $nr_intern;
        }
        $utilizator = $_SESSION['utilizator'] ?? 'Sistem';
        $task_deschis = !empty($data['task_deschis']) ? 1 : 0;
        $task_id = null;

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
