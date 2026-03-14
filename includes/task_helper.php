<?php
/**
 * Task Helper — centralizarea creării taskurilor.
 * Funcție pură: $pdo + $data in → task ID out.
 * Excepțiile PDO sunt lăsate să se propage.
 * NU apelează log_activitate — apelantul decide ce loghează.
 */

/**
 * Creează un task nou.
 *
 * @param PDO   $pdo
 * @param array $data Keys:
 *   - 'nume'           (string, required)
 *   - 'data_ora'       (string, required, format YYYY-MM-DD HH:MM:SS)
 *   - 'detalii'        (string|null, optional)
 *   - 'nivel_urgenta'  (string, optional, default 'normal')
 *   - 'utilizator_id'  (int|null, optional — folosit doar dacă coloana există)
 * @return int Task ID pe succes, 0 pe eșec
 */
function task_create(PDO $pdo, array $data): int {
    $nume = trim($data['nume'] ?? '');
    if ($nume === '') return 0;

    $data_ora = trim($data['data_ora'] ?? '');
    if ($data_ora === '') return 0;

    $detalii = isset($data['detalii']) && trim((string)$data['detalii']) !== '' ? trim((string)$data['detalii']) : null;
    $nivel = in_array($data['nivel_urgenta'] ?? '', ['normal', 'important', 'reprogramat'])
        ? $data['nivel_urgenta']
        : 'normal';
    $uid = isset($data['utilizator_id']) ? (int)$data['utilizator_id'] : null;

    // Verifică dacă există coloana utilizator_id
    $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
    $has_uid = in_array('utilizator_id', $cols);

    if ($has_uid) {
        $stmt = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nume, $data_ora, $detalii, $nivel, $uid]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nume, $data_ora, $detalii, $nivel]);
    }

    return (int)$pdo->lastInsertId();
}
