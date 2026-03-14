<?php
/**
 * Helper import membri din CSV – modul nou, izolat.
 */

require_once __DIR__ . '/cnp_validator.php';
require_once __DIR__ . '/log_helper.php';

/**
 * Parsează un fișier CSV și întoarce headere + rânduri (index numeric).
 *
 * @param string $file_path
 * @return array{headers: string[], rows: array<int, array<int, string>>}
 */
function membri_import_parse_csv(string $file_path): array {
    $headers = [];
    $rows = [];

    if (!is_readable($file_path)) {
        return ['headers' => [], 'rows' => []];
    }

    if (($handle = fopen($file_path, 'r')) === false) {
        return ['headers' => [], 'rows' => []];
    }

    // Auto-detectare delimitator (virgulă vs. punct și virgulă)
    $firstLine = fgets($handle);
    if ($firstLine === false) {
        fclose($handle);
        return ['headers' => [], 'rows' => []];
    }

    $delimiter = ',';
    $countComma = substr_count($firstLine, ',');
    $countSemicolon = substr_count($firstLine, ';');
    if ($countSemicolon > $countComma) {
        $delimiter = ';';
    }

    // Procesează header-ul cu str_getcsv pe prima linie citită
    $headers = str_getcsv($firstLine, $delimiter);
    if (!empty($headers)) {
        // Elimină eventualul BOM din primul header
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
    }
    if ($headers === false || empty($headers)) {
        fclose($handle);
        return ['headers' => [], 'rows' => []];
    }

    // Citește rândurile
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (count($data) === count($headers)) {
            $rows[] = $data;
        }
    }

    fclose($handle);
    return ['headers' => $headers, 'rows' => $rows];
}

/**
 * Returnează lista de câmpuri disponibile pentru mapare pe baza tabelului membri.
 *
 * @param PDO $pdo
 * @return array<string,string> db_field => eticheta pentru UI
 */
function membri_import_available_fields(PDO $pdo): array {
    // Etichete prietenoase pentru câmpurile principale
    $fields = [
        'dosarnr' => 'Nr. Dosar',
        'dosardata' => 'Data Dosar',
        'status_dosar' => 'Status Dosar',
        'nume' => 'Nume',
        'prenume' => 'Prenume',
        'cnp' => 'CNP',
        'telefonnev' => 'Telefon membru',
        'telefonapartinator' => 'Telefon aparținător',
        'email' => 'Email',
        'datanastere' => 'Data Nașterii',
        'locnastere' => 'Loc. Nașterii',
        'judnastere' => 'Jud. Nașterii',
        'tara_nastere' => 'Țara Nașterii',
        'ciseria' => 'Seria C.I.',
        'cinumar' => 'Număr C.I.',
        'cielib' => 'C.I. eliberat de',
        'cidataelib' => 'C.I. data elib.',
        'cidataexp' => 'C.I. data expirării',
        'gdpr' => 'Acord GDPR',
        'codpost' => 'Cod Poștal',
        'tipmediuur' => 'Tip Mediu',
        'domloc' => 'Localitatea',
        'judet_domiciliu' => 'Județ Domiciliu',
        'domstr' => 'Strada',
        'domnr' => 'Nr.',
        'dombl' => 'Bl.',
        'domsc' => 'Sc.',
        'domet' => 'Et.',
        'domap' => 'Ap.',
        'sex' => 'Sex',
        'hgrad' => 'Grad Handicap (ENUM)',
        'grad_handicap' => 'Grad Handicap (text)',
        'hmotiv' => 'Motiv Handicap',
        'hdur' => 'Valabilitate Certificat',
        'cenr' => 'Nr. Certificat Handicap',
        'cedata' => 'Data Certificatului',
        'ceexp' => 'Data Expirării',
        'primaria' => 'Primăria de Domiciliu',
        'notamembru' => 'Notă',
        'insotitor' => 'Însoțitor (0/1)',
        'nume_apartinator' => 'Nume aparținător',
        'prenume_apartinator' => 'Prenume aparținător',
        'surse_venit' => 'Surse venit',
        'diagnostic' => 'Diagnostic',
    ];

    // Completează cu toate coloanele reale din DB (fără dubluri)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cols as $col) {
            if (!isset($fields[$col])) {
                $fields[$col] = 'Coloană: ' . $col;
            }
        }
    } catch (PDOException $e) {
        // dacă e eroare, rămân doar cele statice
    }

    return $fields;
}

/**
 * Normalizează o valoare de dată (diverse formate uzuale) la Y-m-d.
 *
 * @param string $value
 * @return string|null
 */
function membri_import_parse_date(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    $formats = ['d.m.Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'];
    foreach ($formats as $fmt) {
        $dt = date_create_from_format($fmt, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }
    return null;
}

/**
 * Convertesc valori text în boolean (0/1) pentru câmpuri de tip flag.
 */
function membri_import_parse_bool(string $value): int {
    $v = mb_strtolower(trim($value));
    if ($v === '' || $v === '0' || $v === 'nu' || $v === 'no' || $v === 'false') {
        return 0;
    }
    return 1;
}

/**
 * Importă membrii folosind maparea aleasă în UI.
 *
 * @param PDO   $pdo
 * @param array $rows   Lista de rânduri (index numeric)
 * @param array $mapare [excel_index => db_field]
 * @param bool  $skipDuplicates Dacă true, sare peste CNP existente
 * @return array{importati:int, skipati:int, eroare:string[]}
 */
function membri_import_execute(PDO $pdo, array $rows, array $mapare, bool $skipDuplicates = true): array {
    $importati = 0;
    $skipati = 0;
    $eroare = [];

    foreach ($rows as $row_index => $row) {
        $data = [];

        // Construiește array-ul $data[db_field] din mapare
        foreach ($mapare as $excel_index => $db_field) {
            if ($db_field === 'ignora') continue;
            if (!array_key_exists($excel_index, $row)) continue;
            $value = trim((string)$row[$excel_index]);

            if (in_array($db_field, ['dosardata', 'datanastere', 'cidataelib', 'cidataexp', 'cedata', 'ceexp'], true)) {
                $data[$db_field] = membri_import_parse_date($value);
            } elseif (in_array($db_field, ['gdpr', 'insotitor'], true)) {
                $data[$db_field] = membri_import_parse_bool($value);
            } else {
                $data[$db_field] = $value !== '' ? $value : null;
            }
        }

        // Validări minime (nume / prenume necesare; CNP nu mai blochează importul dacă este invalid)
        $nume = trim((string)($data['nume'] ?? ''));
        $prenume = trim((string)($data['prenume'] ?? ''));
        $cnp_raw = preg_replace('/\D/', '', (string)($data['cnp'] ?? ''));

        if ($nume === '' || $prenume === '') {
            $eroare[] = 'Rând ' . ($row_index + 2) . ': lipsă nume sau prenume';
            continue;
        }

        // Dacă avem CNP în fișier, îl normalizăm la cifre, dar nu mai blocăm importul la CNP invalid.
        // Alertarea pentru CNP greșit se va face în platformă (buton Actualizare CNP/CI).
        if ($cnp_raw !== '') {
            // Verificăm doar duplicatele, opțional, fără a respinge valorile invalide la nivel de structură.
            if ($skipDuplicates) {
                $stmt = $pdo->prepare('SELECT id FROM membri WHERE cnp = ?');
                $stmt->execute([$cnp_raw]);
                if ($stmt->fetch()) {
                    $skipati++;
                    continue;
                }
            }
            $data['cnp'] = $cnp_raw;
        }

        // Setăm câteva valori implicite
        if (empty($data['status_dosar'])) {
            $data['status_dosar'] = 'Activ';
        }
        $data['nume'] = $nume;
        $data['prenume'] = $prenume;

        // Construiește query-ul din câmpurile efectiv definite
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = 'INSERT INTO membri (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $importati++;
            log_activitate($pdo, 'membri: Importat membru din CSV (' . $nume . ' ' . $prenume . ')');
        } catch (PDOException $e) {
            $eroare[] = 'Rând ' . ($row_index + 2) . ': ' . $e->getMessage();
        }
    }

    return [
        'importati' => $importati,
        'skipati'   => $skipati,
        'eroare'    => $eroare,
    ];
}

/**
 * Actualizează membrii din baza de date pe baza unui CSV.
 * Câmpul de potrivire este dosarnr (Nr. Dosar) – dacă coincide, se actualizează toate datele mapate.
 *
 * @param PDO   $pdo
 * @param array $rows   Lista de rânduri (index numeric)
 * @param array $mapare [csv_index => db_field]
 * @return array{actualizati:int, negasiti:int, eroare:string[]}
 */
function membri_actualizare_execute(PDO $pdo, array $rows, array $mapare): array {
    $actualizati = 0;
    $negasiti = 0;
    $eroare = [];

    $date_fields = ['dosardata', 'datanastere', 'cidataelib', 'cidataexp', 'cedata', 'ceexp'];
    $bool_fields = ['gdpr', 'insotitor'];

    foreach ($rows as $row_index => $row) {
        $data = [];

        foreach ($mapare as $excel_index => $db_field) {
            if ($db_field === 'ignora') continue;
            if (!array_key_exists($excel_index, $row)) continue;
            $value = trim((string)$row[$excel_index]);

            if (in_array($db_field, $date_fields, true)) {
                $data[$db_field] = membri_import_parse_date($value);
            } elseif (in_array($db_field, $bool_fields, true)) {
                $data[$db_field] = membri_import_parse_bool($value);
            } else {
                $data[$db_field] = $value !== '' ? $value : null;
            }
        }

        $dosarnr = trim((string)($data['dosarnr'] ?? ''));
        if ($dosarnr === '') {
            $eroare[] = 'Rând ' . ($row_index + 2) . ': lipsă Nr. Dosar – nu se poate actualiza';
            continue;
        }

        // Căutăm membru după dosarnr (potrivire exactă după trim)
        $stmt = $pdo->prepare('SELECT id, nume, prenume FROM membri WHERE TRIM(COALESCE(dosarnr, "")) = ?');
        $stmt->execute([$dosarnr]);
        $membru = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$membru) {
            $negasiti++;
            continue;
        }

        // Nu actualizăm id, created_at; excludem dosarnr din SET dacă e singurul – dar îl putem actualiza
        $exclude = ['id', 'created_at'];
        $update_data = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $exclude, true)) continue;
            $update_data[$k] = $v;
        }

        if (empty($update_data)) {
            continue;
        }

        // CNP: dacă e în mapare, normalizăm la cifre, dar NU mai blocăm actualizarea când este invalid.
        // Eventualele probleme de CNP vor apărea în platformă la butonul „Actualizare CNP/CI”.
        if (isset($update_data['cnp']) && $update_data['cnp'] !== null) {
            $cnp_raw = preg_replace('/\D/', '', (string)$update_data['cnp']);
            if ($cnp_raw !== '') {
                $update_data['cnp'] = $cnp_raw;
            }
        }

        try {
            $cols_allowed = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $eroare[] = 'Rând ' . ($row_index + 2) . ': Eroare la citirea schemei.';
            continue;
        }
        $set_parts = [];
        $params = [];
        foreach ($update_data as $k => $v) {
            if (!in_array($k, $cols_allowed, true)) continue;
            $set_parts[] = '`' . str_replace('`', '``', $k) . '` = ?';
            $params[] = $v;
        }
        if (empty($set_parts)) {
            continue;
        }
        $params[] = $membru['id'];

        $sql = 'UPDATE membri SET ' . implode(', ', $set_parts) . ' WHERE id = ?';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $actualizati++;
            log_activitate($pdo, 'membri: Actualizat din CSV (dosar ' . $dosarnr . ' – ' . ($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? '') . ')');
        } catch (PDOException $e) {
            $eroare[] = 'Rând ' . ($row_index + 2) . ': ' . $e->getMessage();
        }
    }

    return [
        'actualizati' => $actualizati,
        'negasiti'    => $negasiti,
        'eroare'      => $eroare,
    ];
}