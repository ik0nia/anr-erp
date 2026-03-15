<?php
/**
 * Modul nou: Import membri CSV (cu mapare coloane) + link către export CSV existent.
 */

ob_start();
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/membri_import_helper.php';

$eroare = '';
$succes = '';
$import_result = null;
$excel_data = null;
$mapare_coloane = null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = 'upload'; // upload | map | done

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['membri_import_upload'])) {
        csrf_require_valid();
        if (!isset($_FILES['fisier_csv']) || $_FILES['fisier_csv']['error'] !== UPLOAD_ERR_OK) {
            $eroare = 'Nu s-a selectat niciun fișier CSV sau a apărut o eroare la încărcare.';
        } else {
            $file = $_FILES['fisier_csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $eroare = 'Modulul nou acceptă doar fișiere CSV.';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $eroare = 'Fișierul depășește 10 MB.';
            } else {
                $upload_dir = APP_ROOT . '/uploads/import/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'membri_import_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', uniqid()) . '.csv';
                $file_path = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['membri_import_csv_path'] = $file_path;
                    $excel_data = membri_import_parse_csv($file_path);
                    if (empty($excel_data['headers'])) {
                        $eroare = 'Nu s-au putut citi coloanele din fișierul CSV.';
                        unlink($file_path);
                        unset($_SESSION['membri_import_csv_path']);
                    } else {
                        $step = 'map';
                    }
                } else {
                    $eroare = 'Eroare la salvarea fișierului pe server.';
                }
            }
        }
    } elseif (isset($_POST['membri_import_execute'])) {
        csrf_require_valid();
        $path = $_SESSION['membri_import_csv_path'] ?? null;
        if (!$path || !file_exists($path)) {
            $eroare = 'Sesiunea de import a expirat. Încarcă din nou fișierul.';
        } else {
            $excel_data = membri_import_parse_csv($path);
            if (empty($excel_data['headers'])) {
                $eroare = 'Nu s-au putut citi din nou datele din fișierul CSV.';
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
                if (empty($mapare_coloane)) {
                    $eroare = 'Nu a fost mapată nicio coloană către baza de date.';
                    $step = 'map';
                } else {
                    $actiune = $_POST['actiune_import'] ?? 'adauga';

                    if ($actiune === 'actualizeaza_dosarnr') {
                        // Pentru actualizare avem nevoie obligatoriu de maparea Nr. Dosar
                        $index_dosarnr = array_search('dosarnr', $mapare_coloane, true);
                        if ($index_dosarnr === false) {
                            $eroare = 'Pentru actualizare după Nr. Dosar trebuie să mapezi o coloană la câmpul „Nr. Dosar”.';
                            $step = 'map';
                        } else {
                            $result = membri_actualizare_execute($pdo, $excel_data['rows'], $mapare_coloane);
                            $actualizati = $result['actualizati'] ?? 0;
                            $negasiti = $result['negasiti'] ?? 0;

                            if ($actualizati > 0) {
                                $succes = "Actualizare reușită: {$actualizati} membri actualizați după Nr. Dosar.";
                                if ($negasiti > 0) {
                                    $succes .= " {$negasiti} rânduri cu Nr. Dosar care nu există în baza de date.";
                                }
                            } elseif ($negasiti > 0) {
                                $eroare = "Nu s-a actualizat niciun membru. {$negasiti} rânduri au avut Nr. Dosar care nu există în baza de date.";
                            }

                            if (!empty($result['eroare'])) {
                                $eroare_extra = 'Erori la actualizare: ' . implode('; ', array_slice($result['eroare'], 0, 10));
                                $extra = count($result['eroare']) - 10;
                                if ($extra > 0) {
                                    $eroare_extra .= " ... și încă {$extra} erori.";
                                }
                                $eroare = empty($eroare) ? $eroare_extra : ($eroare . ' | ' . $eroare_extra);
                            }

                            // Curățăm fișierul CSV
                            unlink($path);
                            unset($_SESSION['membri_import_csv_path']);
                            $step = 'done';
                        }
                    } else {
                        $skip_duplicates = !empty($_POST['skip_duplicates']);
                        $import_result = membri_import_execute($pdo, $excel_data['rows'], $mapare_coloane, $skip_duplicates);
                        $importati = $import_result['importati'] ?? 0;
                        $skipati = $import_result['skipati'] ?? 0;

                        if ($importati > 0) {
                            $succes = "Import reușit: {$importati} membri importați.";
                            if ($skipati > 0) {
                                $succes .= " {$skipati} membri au fost săriți (CNP existent).";
                            }
                        }
                        if (!empty($import_result['eroare'])) {
                            $eroare = 'Erori la import: ' . implode('; ', array_slice($import_result['eroare'], 0, 10));
                            $extra = count($import_result['eroare']) - 10;
                            if ($extra > 0) {
                                $eroare .= " ... și încă {$extra} erori.";
                            }
                        }

                        // Curățăm fișierul CSV
                        unlink($path);
                        unset($_SESSION['membri_import_csv_path']);
                        $step = 'done';
                    }
                }
            }
        }
    }
}

// Dacă suntem în pasul de mapare și nu avem încărcat $excel_data (GET după POST),
// reîncărcăm din fișierul de pe server.
if ($step === 'map' && $excel_data === null && !empty($_SESSION['membri_import_csv_path']) && file_exists($_SESSION['membri_import_csv_path'])) {
    $excel_data = membri_import_parse_csv($_SESSION['membri_import_csv_path']);
}

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';

$campuri_membri = membri_import_available_fields($pdo);
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Import membri CSV</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Importă membri dintr-un fișier CSV, cu mapare flexibilă a coloanelor către câmpurile bazei de date.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="util/export_membri.php"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Exportă membri existenți în CSV">
                <i data-lucide="download" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Export membri în CSV
            </a>
            <a href="setari.php"
               class="inline-flex items-center px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                ← Înapoi la Setări
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-5xl">
        <?php if (!empty($eroare)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
                <p><?php echo htmlspecialchars($eroare); ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
            <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
                <p><?php echo htmlspecialchars($succes); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($step === 'upload'): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="upload-heading">
                <h2 id="upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    1. Încarcă fișierul CSV
                </h2>
                <form method="post" action="/import-membri-csv" enctype="multipart/form-data" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="membri_import_upload" value="1">

                    <div>
                        <label for="fisier_csv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">
                            Fișier CSV <span class="text-red-600" aria-hidden="true">*</span>
                        </label>
                        <input type="file"
                               id="fisier_csv"
                               name="fisier_csv"
                               accept=".csv"
                               required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Se acceptă doar fișiere CSV (delimitate prin virgulă sau punct și virgulă), maxim 10 MB.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="setari.php"
                           class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Anulează
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Continuă la mapare
                        </button>
                    </div>
                </form>
            </section>
        <?php elseif ($step === 'map' && $excel_data): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="map-heading">
                <h2 id="map-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    2. Mapare coloane CSV → câmpuri Membri
                </h2>
                <p class="text-sm text-slate-700 dark:text-gray-300 mb-2">
                    <strong><?php echo count($excel_data['rows']); ?></strong> rânduri găsite în fișier.
                </p>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">
                    Alege pentru fiecare coloană din CSV câmpul corespunzător din baza de date. Coloanele pe care nu vrei să le imporți lasă-le la <strong>-- Ignoră --</strong>.
                </p>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                    Poți <strong>adăuga membri noi</strong> sau <strong>actualiza membri existenți după Nr. Dosar</strong>.
                </p>

                <form method="post" action="/import-membri-csv" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="membri_import_execute" value="1">

                    <fieldset class="border border-slate-200 dark:border-gray-600 rounded p-3">
                        <legend class="px-2 text-sm font-medium text-slate-800 dark:text-gray-200">Tip acțiune import</legend>
                        <div class="mt-2 space-y-1 text-sm text-slate-700 dark:text-gray-300">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="actiune_import" value="adauga" checked
                                       class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                <span>Adaugă membri noi (opțional sare CNP-urile existente)</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="actiune_import" value="actualizeaza_dosarnr"
                                       class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                <span>Actualizează membri existenți după <strong>Nr. Dosar</strong> (caută după câmpul „Nr. Dosar”)</span>
                            </label>
                        </div>
                    </fieldset>

                    <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded p-3 space-y-2" aria-label="Mapare coloane CSV către câmpuri membri">
                        <?php foreach ($excel_data['headers'] as $index => $header): ?>
                            <div class="flex items-center gap-3">
                                <label class="flex-1 text-sm text-slate-700 dark:text-gray-300">
                                    <?php echo htmlspecialchars($header); ?>
                                </label>
                                <select name="mapare_coloane[<?php echo (int)$index; ?>]"
                                        class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm text-slate-900 dark:text-white dark:bg-gray-700">
                                    <option value="ignora">-- Ignoră --</option>
                                    <?php foreach ($campuri_membri as $db_field => $label): ?>
                                        <option value="<?php echo htmlspecialchars($db_field); ?>">
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="skip_duplicates" value="1" checked
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-slate-800 dark:text-gray-200">
                                Sari membri duplicați (CNP existent în baza de date)
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Importă
                        </button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="done-heading">
                <h2 id="done-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    Import finalizat
                </h2>
                <p class="text-sm text-slate-700 dark:text-gray-300 mb-4">
                    Poți reveni la setări sau poți porni un nou import CSV.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="setari.php"
                       class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        ← Înapoi la Setări
                    </a>
                    <a href="import-membri-csv.php"
                       class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Pornește un nou import
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>

