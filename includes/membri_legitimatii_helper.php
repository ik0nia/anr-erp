<?php
/**
 * Helper pentru operatiuni legitimatii membri.
 */

require_once __DIR__ . '/log_helper.php';

function membri_legitimatii_ensure_tables(PDO $pdo): void {
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS membri_legitimatii (
        id INT AUTO_INCREMENT PRIMARY KEY,
        membru_id INT NOT NULL,
        data_actiune DATE NOT NULL,
        tip_actiune VARCHAR(64) NOT NULL,
        created_by VARCHAR(191) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_membru_data (membru_id, data_actiune),
        INDEX idx_data_actiune (data_actiune),
        INDEX idx_tip_actiune (tip_actiune)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Backward-compat alias (singular form used in a few call sites).
function membri_legitimatii_ensure_table(PDO $pdo): void {
    membri_legitimatii_ensure_tables($pdo);
}

function legitimatie_membru_actiuni(): array {
    return [
        'legitimatie_membru_nou' => 'Legitimatie membru nou',
        'inlocuire_legitimatie_plina' => 'Inlocuire legitimatie plina',
        'inlocuire_legitimatie_pierduta' => 'Inlocuire legitimatie pierduta',
    ];
}

function membri_legitimatii_tipuri_actiune(): array {
    return legitimatie_membru_actiuni();
}

function membri_legitimatii_tip_normalizat(string $tip): string {
    $tipuri = legitimatie_membru_actiuni();
    return isset($tipuri[$tip]) ? $tip : 'legitimatie_membru_nou';
}

function membri_legitimatii_data_valida(string $data): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return false;
    }
    $ts = strtotime($data);
    return $ts !== false;
}

function membri_legitimatie_adauga(PDO $pdo, int $membru_id, string $data_actiune, string $tip_actiune, string $utilizator = 'Sistem'): array {
    membri_legitimatii_ensure_tables($pdo);
    if ($membru_id <= 0) {
        return ['success' => false, 'error' => 'Membru invalid.', 'id' => null];
    }
    if (!membri_legitimatii_data_valida($data_actiune)) {
        return ['success' => false, 'error' => 'Data actiunii este invalida.', 'id' => null];
    }

    $tip_actiune = membri_legitimatii_tip_normalizat($tip_actiune);
    $tipuri = legitimatie_membru_actiuni();

    try {
        $stmt = $pdo->prepare("INSERT INTO membri_legitimatii (membru_id, data_actiune, tip_actiune, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$membru_id, $data_actiune, $tip_actiune, trim($utilizator) ?: 'Sistem']);
        $id = (int)$pdo->lastInsertId();

        log_activitate(
            $pdo,
            'membri: Legitimație membru salvată - ' . ($tipuri[$tip_actiune] ?? $tip_actiune) . ' / Data: ' . $data_actiune,
            $utilizator,
            $membru_id
        );

        return ['success' => true, 'error' => null, 'id' => $id];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvarea legitimației: ' . $e->getMessage(), 'id' => null];
    }
}

function membri_legitimatii_lista_membru(PDO $pdo, int $membru_id, int $limit = 100): array {
    membri_legitimatii_ensure_tables($pdo);
    if ($membru_id <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, membru_id, data_actiune, tip_actiune, created_by, created_at
            FROM membri_legitimatii
            WHERE membru_id = ?
            ORDER BY data_actiune DESC, id DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute([$membru_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function membri_legitimatii_raport_interval(PDO $pdo, string $data_de_la, string $data_pana_la): array {
    membri_legitimatii_ensure_tables($pdo);
    if (!membri_legitimatii_data_valida($data_de_la) || !membri_legitimatii_data_valida($data_pana_la)) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.data_actiune,
                l.tip_actiune,
                l.created_by,
                m.id AS membru_id,
                m.nume,
                m.prenume,
                m.dosarnr
            FROM membri_legitimatii l
            INNER JOIN membri m ON m.id = l.membru_id
            WHERE l.data_actiune >= ?
              AND l.data_actiune <= ?
            ORDER BY l.data_actiune ASC, l.id ASC
        ");
        $stmt->execute([$data_de_la, $data_pana_la]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function membri_legitimatii_statistici_interval(PDO $pdo, string $data_de_la, string $data_pana_la): array {
    membri_legitimatii_ensure_tables($pdo);
    $tipuri = legitimatie_membru_actiuni();
    $stats = [
        'total' => 0,
        'legitimatie_membru_nou' => 0,
        'inlocuire_legitimatie_plina' => 0,
        'inlocuire_legitimatie_pierduta' => 0,
        'tipuri' => $tipuri,
    ];

    if (!membri_legitimatii_data_valida($data_de_la) || !membri_legitimatii_data_valida($data_pana_la)) {
        return $stats;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT tip_actiune, COUNT(*) AS total
            FROM membri_legitimatii
            WHERE data_actiune >= ?
              AND data_actiune <= ?
            GROUP BY tip_actiune
        ");
        $stmt->execute([$data_de_la, $data_pana_la]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $tip = membri_legitimatii_tip_normalizat((string)($row['tip_actiune'] ?? ''));
            $n = (int)($row['total'] ?? 0);
            $stats[$tip] = $n;
            $stats['total'] += $n;
        }
    } catch (PDOException $e) {}

    return $stats;
}

/**
 * Alias backward-compatible pentru raport borderou legitimatii.
 */
function membri_legitimatii_borderou(PDO $pdo, string $data_de_la, string $data_pana_la): array {
    $rows = membri_legitimatii_raport_interval($pdo, $data_de_la, $data_pana_la);
    foreach ($rows as &$row) {
        if (!isset($row['utilizator']) || $row['utilizator'] === '') {
            $row['utilizator'] = (string)($row['created_by'] ?? 'Sistem');
        }
    }
    unset($row);

    return $rows;
}

/**
 * Alias backward-compatible pentru statistici interval.
 */
function membri_legitimatii_statistici(PDO $pdo, string $data_de_la, string $data_pana_la): array {
    return membri_legitimatii_statistici_interval($pdo, $data_de_la, $data_pana_la);
}
