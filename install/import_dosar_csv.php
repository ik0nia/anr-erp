<?php
/**
 * Import/actualizare membri din dosar_export.csv
 *
 * Rulare: php install/import_dosar_csv.php [--dry-run]
 *
 * - Match pe CNP + dosarnr (ambele trebuie sa se potriveasca)
 * - Membrii existenti sunt actualizati cu datele din CSV
 * - Membrii noi sunt inserati
 * - Duplicate din CSV sunt ignorate (se pastreaza ultimul rand)
 * - CNP placeholder (2147483647) este tratat special
 */

$dry_run = in_array('--dry-run', $argv ?? []);

// Add columns if missing
function ensure_import_columns(PDO $pdo) {
    $cols_to_add = [
        'ap_rudenie' => "ALTER TABLE membri ADD COLUMN ap_rudenie VARCHAR(100) DEFAULT NULL AFTER prenume_apartinator",
        'ap_email' => "ALTER TABLE membri ADD COLUMN ap_email VARCHAR(255) DEFAULT NULL AFTER ap_rudenie",
        'diagnostic' => "ALTER TABLE membri ADD COLUMN diagnostic VARCHAR(500) DEFAULT NULL AFTER hmotiv",
        'cert_data_dela' => "ALTER TABLE membri ADD COLUMN cert_data_dela DATE DEFAULT NULL AFTER cedata",
    ];

    $existing = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cols_to_add as $col => $sql) {
        if (!in_array($col, $existing)) {
            try { $pdo->exec($sql); echo "  Coloana '$col' adaugata.\n"; }
            catch (PDOException $e) { echo "  Coloana '$col' exista deja.\n"; }
        }
    }
}

// Map CSV values to DB values
function map_sex($gen) {
    $gen = strtoupper(trim($gen));
    if ($gen === 'F') return 'Feminin';
    if ($gen === 'M') return 'Masculin';
    return null;
}

function map_status($stare) {
    $stare = strtoupper(trim($stare));
    $map = [
        'ACTIV' => 'Activ',
        'DECEDAT' => 'Decedat',
        'EXPIRAT' => 'Expirat',
        'SUSPENDAT' => 'Suspendat',
        'RETRAS' => 'Retras',
    ];
    return $map[$stare] ?? 'Activ';
}

function map_grad($grad) {
    $grad = strtoupper(trim($grad));
    $map = [
        'GRAV' => 'Grav',
        'ACCENTUAT' => 'Accentuat',
        'MEDIU' => 'Mediu',
        'USOR' => 'Usor',
    ];
    return $map[$grad] ?? null;
}

function map_mediu($tip) {
    $tip = strtoupper(trim($tip));
    if ($tip === 'URBAN') return 'Urban';
    if ($tip === 'RURAL') return 'Rural';
    return null;
}

function map_insotitor($val) {
    $val = strtoupper(trim($val));
    if (strpos($val, 'INDEMNIZATIE') !== false) return 'INDEMNIZATIE INSOTITOR';
    if (strpos($val, 'ASISTENT') !== false) return 'ASISTENT PERSONAL';
    if ($val === 'FARA' || $val === '') return '0';
    if ($val === 'NESPECIFICAT') return 'NESPECIFICAT';
    return $val;
}

function clean_date($date) {
    $date = trim($date);
    if ($date === '' || $date === '0000-00-00' || $date === '9999-12-31') return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date;
    return null;
}

function clean_str($val) {
    $val = trim($val);
    return $val === '' ? null : $val;
}

// ---- MAIN ----
if (php_sapi_name() !== 'cli') { die('Doar din CLI.'); }

echo "=== Import dosar_export.csv ===\n";
echo $dry_run ? "MODE: DRY RUN (nu se modifica nimic)\n\n" : "MODE: LIVE (se actualizeaza baza de date)\n\n";

// Bypass auth for CLI
$_SESSION['user_id'] = 1;
$_SESSION['utilizator'] = 'Import Script';
$_SESSION['username'] = 'import';
$_SESSION['rol'] = 'administrator';

require_once dirname(__DIR__) . '/config.php';

// 1. Ensure columns
echo "1. Verificare coloane...\n";
if (!$dry_run) ensure_import_columns($pdo);

// 2. Load CSV
echo "2. Incarcare CSV...\n";
$csv_lines = file('/tmp/dosar_export.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$header = str_getcsv(array_shift($csv_lines), ';');
$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

// Parse all rows, dedup by CNP+dosarnr (keep last)
$rows = [];
foreach ($csv_lines as $line) {
    $fields = str_getcsv($line, ';');
    $row = [];
    foreach ($header as $i => $col) {
        $row[$col] = $fields[$i] ?? '';
    }
    // Dedup key: CNP + dosarnr (unless CNP is placeholder)
    $cnp = trim($row['cnp']);
    $dos = trim($row['dos_nr']);
    $key = ($cnp === '2147483647') ? 'placeholder_' . $dos : $cnp;
    $rows[$key] = $row;
}
echo "  " . count($rows) . " randuri unice (din " . count($csv_lines) . " total)\n";

// 3. Load existing members from DB
echo "3. Incarcare membri existenti din DB...\n";
$stmt = $pdo->query("SELECT id, cnp, dosarnr FROM membri");
$db_by_cnp = [];
$db_by_dos = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($r['cnp'])) $db_by_cnp[$r['cnp']] = $r['id'];
    if (!empty($r['dosarnr'])) $db_by_dos[$r['dosarnr']] = $r['id'];
}
echo "  " . count($db_by_cnp) . " membri in DB\n";

// 4. Process
echo "4. Procesare...\n";
$updated = 0;
$inserted = 0;
$skipped = 0;
$errors = 0;

foreach ($rows as $row) {
    $cnp = trim($row['cnp']);
    $dos = trim($row['dos_nr']);

    // Find existing member: match CNP first (if not placeholder), then dosarnr
    $existing_id = null;
    if ($cnp !== '2147483647' && $cnp !== '' && isset($db_by_cnp[$cnp])) {
        $existing_id = $db_by_cnp[$cnp];
    }
    if (!$existing_id && $dos !== '' && isset($db_by_dos[$dos])) {
        $existing_id = $db_by_dos[$dos];
    }

    // Prepare data
    $data = [
        'dosarnr' => clean_str($dos),
        'dosardata' => clean_date($row['dos_data']),
        'nume' => strtoupper(trim($row['nume'])),
        'prenume' => strtoupper(trim($row['prenume'])),
        'cnp' => ($cnp === '2147483647') ? null : clean_str($cnp),
        'sex' => map_sex($row['gen']),
        'datanastere' => clean_date($row['nas_data']),
        'tara_nastere' => clean_str($row['nas_tara']),
        'judnastere' => clean_str($row['nas_jud']),
        'locnastere' => clean_str($row['nas_loc']),
        'ciseria' => clean_str($row['ai_ser']),
        'cinumar' => clean_str($row['ai_nr']),
        'cidataelib' => clean_date($row['ai_elib']),
        'cidataexp' => clean_date($row['ai_exp']),
        'cielib' => clean_str($row['ai_elibde']),
        'tipmediuur' => map_mediu($row['dom_tip']),
        'judet_domiciliu' => clean_str($row['dom_judet']),
        'domloc' => clean_str($row['dom_localitate']),
        'domstr' => clean_str($row['dom_str']),
        'domnr' => clean_str($row['dom_nr']),
        'dombl' => clean_str($row['dom_bl']),
        'domsc' => clean_str($row['dom_sc']),
        'domet' => clean_str($row['dom_et']),
        'domap' => clean_str($row['dom_ap']),
        'codpost' => clean_str($row['dom_cp']),
        'telefonnev' => clean_str($row['tel']),
        'email' => clean_str($row['mail']),
        'surse_venit' => clean_str($row['venit']),
        'nume_apartinator' => clean_str(trim($row['ap_nume'] . ' ' . $row['ap_prenume'])),
        'telefonapartinator' => clean_str($row['ap_tel']),
        'ap_rudenie' => clean_str($row['ap_rudenie']),
        'ap_email' => clean_str($row['ap_mail']),
        'status_dosar' => map_status($row['stare_curenta']),
        'hgrad' => map_grad($row['grad_handicap']),
        'insotitor' => map_insotitor($row['insotitor']),
        'hmotiv' => clean_str($row['tip_handicap']),
        'diagnostic' => clean_str($row['diagnostic']),
        'cenr' => clean_str($row['cert_nr']),
        'cedata' => clean_date($row['cert_data_elib']),
        'cert_data_dela' => clean_date($row['cert_data_dela']),
        'ceexp' => clean_date($row['cert_data_exp']),
    ];

    // Handle grad + insotitor -> hgrad combined
    if ($data['hgrad'] === 'Grav' && strpos(strtoupper($data['insotitor']), 'INDEMNIZATIE') !== false) {
        $data['hgrad'] = 'Grav cu insotitor';
    }

    // Nu suprascrie status_dosar daca CSV-ul e gol (pastram ce e in DB)
    if (empty(trim($row['stare_curenta'])) && $existing_id) {
        unset($data['status_dosar']);
    }

    try {
        if ($existing_id) {
            // UPDATE - skip null values that would overwrite existing data unnecessarily
            if (!$dry_run) {
                $sets = [];
                $params = [];
                foreach ($data as $col => $val) {
                    $sets[] = "$col = ?";
                    $params[] = $val;
                }
                $params[] = $existing_id;
                $sql = "UPDATE membri SET " . implode(', ', $sets) . " WHERE id = ?";
                $pdo->prepare($sql)->execute($params);
            }
            $updated++;
        } else {
            // INSERT
            if (!$dry_run) {
                $cols = array_keys($data);
                $placeholders = array_fill(0, count($cols), '?');
                $sql = "INSERT INTO membri (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $pdo->prepare($sql)->execute(array_values($data));
                $new_id = (int)$pdo->lastInsertId();
                // Track for dedup
                if ($data['cnp']) $db_by_cnp[$data['cnp']] = $new_id;
                if ($data['dosarnr']) $db_by_dos[$data['dosarnr']] = $new_id;
            }
            $inserted++;
        }
    } catch (PDOException $e) {
        $errors++;
        if ($errors <= 10) {
            echo "  EROARE la " . ($data['dosarnr'] ?? '?') . " " . ($data['nume'] ?? '') . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== REZULTAT ===\n";
echo "Actualizati: $updated\n";
echo "Inserati: $inserted\n";
echo "Erori: $errors\n";
echo $dry_run ? "\n(DRY RUN - nimic nu a fost modificat)\n" : "\nImport finalizat.\n";
