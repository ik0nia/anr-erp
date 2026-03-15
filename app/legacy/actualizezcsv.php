<?php
/**
 * Actualizare membri din CSV
 * Încarcă un fișier CSV, mapează coloanele la câmpurile bazei de date,
 * actualizează membrii pentru care Nr. Dosar (dosarnr) coincide.
 */
ob_start();
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/membri_import_helper.php';

$eroare = '';
$succes = '';
$rezultat = null;
$excel_data = null;
$mapare_coloane = null;

$step = 'upload';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();

    if (isset($_POST['actualizare_upload'])) {
        if (!isset($_FILES['fisier_csv']) || $_FILES['fisier_csv']['error'] !== UPLOAD_ERR_OK) {
            $eroare = 'Nu s-a selectat niciun fișier CSV sau a apărut o eroare la încărcare.';
        } else {
            $file = $_FILES['fisier_csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $eroare = 'Se acceptă doar fișiere CSV (separate cu virgulă).';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $eroare = 'Fișierul depășește 10 MB.';
            } else {
                $upload_dir = APP_ROOT . '/uploads/import/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'actualizare_membri_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', uniqid()) . '.csv';
                $file_path = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['actualizare_csv_path'] = $file_path;
                    $excel_data = membri_import_parse_csv($file_path);
                    if (empty($excel_data['headers'])) {
                        $eroare = 'Nu s-au putut citi coloanele din fișierul CSV.';
                        @unlink($file_path);
                        unset($_SESSION['actualizare_csv_path']);
                    } else {
                        $step = 'map';
                    }
                } else {
                    $eroare = 'Eroare la salvarea fișierului pe server.';
                }
            }
        }
    } elseif (isset($_POST['actualizare_execute'])) {
        $path = $_SESSION['actualizare_csv_path'] ?? null;
        if (!$path || !file_exists($path)) {
            $eroare = 'Sesiunea a expirat. Încarcă din nou fișierul.';
        } else {
            $excel_data = membri_import_parse_csv($path);
            if (empty($excel_data['headers'])) {
                $eroare = 'Nu s-au putut citi datele din fișierul CSV.';
            } else {
                $mapare_coloane = [];
                if (isset($_POST['mapare_coloane']) && is_array($_POST['mapare_coloane'])) {
                    foreach ($_POST['mapare_coloane'] as $index => $db_field) {
                        $index = (int)$index;
                        $db_field = trim((string)$db_field);
                        if ($db_field !== '' && $db_field !== 'ignora') {
                            $mapare_coloane[$index] = $db_field;
                        }
                    }
                }

                if (!isset($mapare_coloane) || !in_array('dosarnr', $mapare_coloane, true)) {
                    $eroare = 'Coloana Nr. Dosar (dosarnr) trebuie mapată obligatoriu – este câmpul de potrivire.';
                    $step = 'map';
                } else {
                    $rezultat = membri_actualizare_execute($pdo, $excel_data['rows'], $mapare_coloane);
                    $actualizati = $rezultat['actualizati'] ?? 0;
                    $negasiti = $rezultat['negasiti'] ?? 0;

                    if ($actualizati > 0) {
                        $succes = "Actualizare reușită: {$actualizati} membri actualizați.";
                        if ($negasiti > 0) {
                            $succes .= " {$negasiti} rânduri nesincronizate (Nr. Dosar negăsit în baza de date).";
                        }
                    } elseif ($negasiti > 0 && empty($rezultat['eroare'])) {
                        $eroare = "Niciun membru actualizat. Toate cele {$negasiti} rânduri au Nr. Dosar negăsit în baza de date.";
                    }

                    if (!empty($rezultat['eroare'])) {
                        $eroare = ($eroare ? $eroare . ' ' : '') . 'Erori: ' . implode('; ', array_slice($rezultat['eroare'], 0, 10));
                        if (count($rezultat['eroare']) > 10) {
                            $eroare .= ' ... și încă ' . (count($rezultat['eroare']) - 10) . ' erori.';
                        }
                    }

                    @unlink($path);
                    unset($_SESSION['actualizare_csv_path']);
                    $step = 'done';
                }
            }
        }
    }
}

if ($step === 'map' && $excel_data === null && !empty($_SESSION['actualizare_csv_path']) && file_exists($_SESSION['actualizare_csv_path'])) {
    $excel_data = membri_import_parse_csv($_SESSION['actualizare_csv_path']);
}

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';

$campuri_membri = membri_import_available_fields($pdo);
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Actualizare membri din CSV</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Încarcă un CSV, mapează coloanele și actualizează membrii existenți după Nr. Dosar.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="membri.php"
               class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                ← Înapoi la Membri
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-5xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($step === 'upload'): ?>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="upload-heading">
            <h2 id="upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">1. Încarcă fișierul CSV</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Fișierul trebuie să conțină o coloană cu Nr. Dosar (DosarNr), care va fi folosită pentru potrivirea cu membrii existenți. După mapare, se actualizează toate datele mapate pentru membrii găsiți.
            </p>
            <form method="post" action="/actualizezcsv" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizare_upload" value="1">
                <div>
                    <label for="fisier_csv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Fișier CSV <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="file" id="fisier_csv" name="fisier_csv" accept=".csv" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">CSV separat cu virgulă, maxim 10 MB.</p>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="membri.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulează</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">Continuă la mapare</button>
                </div>
            </form>
        </section>

        <?php elseif ($step === 'map' && $excel_data): ?>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="map-heading">
            <h2 id="map-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">2. Mapare coloane CSV → câmpuri Membri</h2>
            <p class="text-sm text-slate-700 dark:text-gray-300 mb-2">
                <strong><?php echo count($excel_data['rows']); ?></strong> rânduri găsite. <strong>Mapați obligatoriu coloana Nr. Dosar (dosarnr)</strong> – ea determină ce membru se actualizează.
            </p>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Pentru fiecare coloană din CSV alegeți câmpul corespunzător. Coloanele nel mapate sunt ignorate.
            </p>

            <form method="post" action="/actualizezcsv" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizare_execute" value="1">
                <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded p-3 space-y-2">
                    <?php foreach ($excel_data['headers'] as $index => $header): ?>
                    <div class="flex items-center gap-3">
                        <label class="flex-1 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($header); ?></label>
                        <select name="mapare_coloane[<?php echo (int)$index; ?>]" class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white">
                            <option value="ignora">-- Ignoră --</option>
                            <?php foreach ($campuri_membri as $db_field => $label): ?>
                            <option value="<?php echo htmlspecialchars($db_field); ?>" <?php echo ($db_field === 'dosarnr' && (stripos($header, 'dosar') !== false || stripos($header, 'nr') !== false)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?><?php echo $db_field === 'dosarnr' ? ' (obligatoriu)' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" onclick="return confirm('Executați actualizarea? Membrii cu Nr. Dosar găsit vor fi actualizați conform CSV.');">Actualizează membri</button>
                </div>
            </form>
        </section>

        <?php else: ?>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Actualizare finalizată</h2>
            <p class="text-sm text-slate-700 dark:text-gray-300 mb-4">Poți reveni la membri sau porni o nouă actualizare.</p>
            <div class="flex flex-wrap gap-3">
                <a href="membri.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">← Înapoi la Membri</a>
                <a href="actualizezcsv.php" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Pornește o nouă actualizare</a>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
