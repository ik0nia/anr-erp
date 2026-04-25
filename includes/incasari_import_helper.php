<?php
/**
 * Helper import încasări istorice din CSV.
 */

require_once __DIR__ . '/date_helper.php';
require_once __DIR__ . '/incasari_helper.php';
require_once __DIR__ . '/log_helper.php';

/**
 * Parsează un fișier CSV și întoarce headere + rânduri.
 *
 * @param string $file_path
 * @return array{headers: string[], rows: array<int, array<int, string>>}
 */
function incasari_import_parse_csv(string $file_path): array {
    $headers = [];
    $rows = [];

    if (!is_readable($file_path)) {
        return ['headers' => [], 'rows' => []];
    }

    $handle = fopen($file_path, 'r');
    if ($handle === false) {
        return ['headers' => [], 'rows' => []];
    }

    $first_line = fgets($handle);
    if ($first_line === false) {
        fclose($handle);
        return ['headers' => [], 'rows' => []];
    }

    $delimiter = ',';
    if (substr_count($first_line, ';') > substr_count($first_line, ',')) {
        $delimiter = ';';
    }

    $headers = str_getcsv($first_line, $delimiter);
    if (!empty($headers)) {
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
    }
    if (empty($headers)) {
        fclose($handle);
        return ['headers' => [], 'rows' => []];
    }

    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (count($data) !== count($headers)) {
            continue;
        }
        $rows[] = $data;
    }

    fclose($handle);
    return ['headers' => $headers, 'rows' => $rows];
}

/**
 * Câmpuri disponibile pentru mapare CSV în UI.
 *
 * @return array<string, string>
 */
function incasari_import_available_fields(): array {
    return [
        'dosarnr' => 'Nr. dosar (obligatoriu)',
        'data_incasare' => 'Data încasării',
        'tip_incasare' => 'Tip încasare (cotizație / donație)',
        'an_cotizatie' => 'An cotizație (pentru cotizații)',
        'suma' => 'Valoare încasată',
        'observatii' => 'Observații',
        'nr_chitanta_veche' => 'Nr. chitanță veche (opțional)',
    ];
}

/**
 * Normalizează tipul încasării din CSV.
 *
 * @param string      $value
 * @param string|null $fallback
 * @return string|null
 */
function incasari_import_normalize_tip(string $value, ?string $fallback = null): ?string {
    $raw = trim($value);
    if ($raw === '' && $fallback !== null && $fallback !== '') {
        return $fallback;
    }

    $normalized = mb_strtolower($raw);
    $normalized = str_replace(['ă', 'â', 'î', 'ș', 'ş', 'ț', 'ţ'], ['a', 'a', 'i', 's', 's', 't', 't'], $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    $normalized = trim((string)$normalized);

    if ($normalized === '') {
        return $fallback ?: null;
    }
    if (strpos($normalized, 'cot') !== false) {
        return INCASARI_TIP_COTIZATIE;
    }
    if (strpos($normalized, 'don') !== false) {
        return INCASARI_TIP_DONATIE;
    }
    if (strpos($normalized, 'tax') !== false) {
        return INCASARI_TIP_TAXA_PARTICIPARE;
    }
    if (strpos($normalized, 'alt') !== false) {
        return INCASARI_TIP_ALTE;
    }
    if (in_array($normalized, [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE], true)) {
        return $normalized;
    }
    return $fallback ?: null;
}

/**
 * Parsează data în format Y-m-d.
 *
 * @param string $value
 * @return string|null
 */
function incasari_import_parse_date(string $value): ?string {
    return parse_date_to_ymd($value, ['Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y']);
}

/**
 * Parsează suma din formate locale.
 *
 * @param string $value
 * @return float
 */
function incasari_import_parse_suma(string $value): float {
    $clean = trim($value);
    $clean = str_replace(["\xc2\xa0", ' '], '', $clean);
    if ($clean === '') {
        return 0.0;
    }

    if (preg_match('/,\d{1,2}$/', $clean)) {
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
    } elseif (preg_match('/\.\d{1,2}$/', $clean)) {
        $clean = str_replace(',', '', $clean);
    } else {
        $clean = str_replace(',', '.', $clean);
    }

    return (float)$clean;
}

/**
 * Execută importul de încasări CSV.
 *
 * @param PDO   $pdo
 * @param array $rows
 * @param array $mapare
 * @param array $options
 * @return array{importate:int, negasite:int, skip_achitate:int, erori:string[]}
 */
function incasari_import_execute(PDO $pdo, array $rows, array $mapare, array $options = []): array {
    $importate = 0;
    $negasite = 0;
    $skip_achitate = 0;
    $erori = [];

    $utilizator = trim((string)($options['utilizator'] ?? 'Utilizator'));
    $tip_implicit = incasari_import_normalize_tip((string)($options['tip_implicit'] ?? ''), null);
    $an_cotizatie_implicit = (int)($options['an_cotizatie_implicit'] ?? date('Y'));
    if ($an_cotizatie_implicit < 1900 || $an_cotizatie_implicit > 2100) {
        $an_cotizatie_implicit = (int)date('Y');
    }
    $skip_cotizatii_achitate = !empty($options['skip_cotizatii_achitate']);

    $stmt_membru = $pdo->prepare('SELECT id, nume, prenume FROM membri WHERE TRIM(COALESCE(dosarnr, "")) = ? LIMIT 1');
    $stmt_insert = $pdo->prepare("
        INSERT INTO incasari (
            membru_id, contact_id, tip, anul, suma, mod_plata, data_incasare,
            seria_chitanta, nr_chitanta, reprezentand, observatii, created_by
        )
        VALUES (?, NULL, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?)
    ");

    foreach ($rows as $index => $row) {
        $row_data = [];
        foreach ($mapare as $csv_index => $db_field) {
            if ($db_field === 'ignora') {
                continue;
            }
            if (!array_key_exists((int)$csv_index, $row)) {
                continue;
            }
            $row_data[$db_field] = trim((string)$row[(int)$csv_index]);
        }

        $dosarnr = trim((string)($row_data['dosarnr'] ?? ''));
        if ($dosarnr === '') {
            $erori[] = 'Rând ' . ($index + 2) . ': lipsă Nr. dosar.';
            continue;
        }

        try {
            $stmt_membru->execute([$dosarnr]);
            $membru = $stmt_membru->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $erori[] = 'Rând ' . ($index + 2) . ': eroare căutare dosar.';
            continue;
        }

        if (!$membru) {
            $negasite++;
            continue;
        }

        $tip = incasari_import_normalize_tip((string)($row_data['tip_incasare'] ?? ''), $tip_implicit);
        if ($tip === null || !in_array($tip, [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE], true)) {
            $erori[] = 'Rând ' . ($index + 2) . ': tip încasare invalid (acceptat: cotizație / donație).';
            continue;
        }

        $data_incasare = incasari_import_parse_date((string)($row_data['data_incasare'] ?? ''));
        if ($data_incasare === null) {
            $erori[] = 'Rând ' . ($index + 2) . ': data încasării este invalidă.';
            continue;
        }

        $suma = incasari_import_parse_suma((string)($row_data['suma'] ?? ''));
        if ($suma <= 0) {
            $erori[] = 'Rând ' . ($index + 2) . ': valoarea încasată trebuie să fie > 0.';
            continue;
        }

        $anul = null;
        $reprezentand = null;
        if ($tip === INCASARI_TIP_COTIZATIE) {
            $an_row = trim((string)($row_data['an_cotizatie'] ?? ''));
            $anul = ctype_digit($an_row) ? (int)$an_row : $an_cotizatie_implicit;
            if (!in_array($anul, [2025, 2026], true)) {
                $erori[] = 'Rând ' . ($index + 2) . ': an cotizație invalid (acceptat: 2025 sau 2026).';
                continue;
            }

            if ($skip_cotizatii_achitate && incasari_cotizatie_achitata_an($pdo, (int)$membru['id'], $anul)) {
                $skip_achitate++;
                continue;
            }
            $reprezentand = 'Cotizatie membru ' . $anul . ' (import CSV)';
        } elseif ($tip === INCASARI_TIP_DONATIE) {
            $reprezentand = 'Donatie (import CSV)';
        } elseif ($tip === INCASARI_TIP_TAXA_PARTICIPARE) {
            $reprezentand = 'Taxa participare (import CSV)';
        } else {
            $reprezentand = 'Alta incasare (import CSV)';
        }

        $obs_parts = ['Import CSV încasări istorice'];
        $nr_chitanta_veche = trim((string)($row_data['nr_chitanta_veche'] ?? ''));
        if ($nr_chitanta_veche !== '') {
            $obs_parts[] = 'Chitanță veche: ' . $nr_chitanta_veche;
        }
        $obs_raw = trim((string)($row_data['observatii'] ?? ''));
        if ($obs_raw !== '') {
            $obs_parts[] = $obs_raw;
        }
        $observatii = implode(' | ', $obs_parts);

        try {
            $stmt_insert->execute([
                (int)$membru['id'],
                $tip,
                $anul,
                $suma,
                INCASARI_MOD_CHITANTA_VECHE,
                $data_incasare,
                $reprezentand,
                $observatii,
                $utilizator,
            ]);
            $importate++;
            log_activitate(
                $pdo,
                'Încasări: import CSV dosar ' . $dosarnr . ' (' . trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? '')) . ') – ' .
                ($tip === INCASARI_TIP_COTIZATIE ? 'cotizație ' . (string)$anul : 'donație') . ', ' . number_format($suma, 2, '.', '') . ' RON',
                $utilizator,
                (int)$membru['id']
            );
        } catch (PDOException $e) {
            $erori[] = 'Rând ' . ($index + 2) . ': ' . $e->getMessage();
        }
    }

    return [
        'importate' => $importate,
        'negasite' => $negasite,
        'skip_achitate' => $skip_achitate,
        'erori' => $erori,
    ];
}
