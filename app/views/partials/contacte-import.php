<?php
/**
 * Import contacte din Excel/CSV cu mapare câmpuri
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(dirname(__DIR__))); require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/excel_import.php';

ensure_contacte_table($pdo);
$eroare = '';
$succes = '';
$excel_data = null;
$mapare_coloane = null;
$import_result = null;

$campuri_contacte = [
    'nume' => 'Nume',
    'prenume' => 'Prenume',
    'companie' => 'Companie',
    'tip_contact' => 'Tip contact',
    'telefon' => 'Telefon mobil',
    'telefon_personal' => 'Telefon personal',
    'email' => 'Email',
    'email_personal' => 'Email personal',
    'website' => 'Website',
    'data_nasterii' => 'Data nașterii',
    'notite' => 'Notițe',
    'referinta_contact' => 'Referință / Contact comun',
];
$tipuri = get_contacte_tipuri();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_contacte'])) {
    csrf_require_valid();
    $upload_dir = APP_ROOT . '/uploads/import/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

    if (isset($_POST['executa_import']) && isset($_SESSION['contacte_import_path']) && file_exists($_SESSION['contacte_import_path'])) {
        $file_path = $_SESSION['contacte_import_path'];
        $excel_data = citeste_fisier_excel($file_path);
        $mapare_coloane = [];
        if (isset($_POST['mapare_coloane']) && is_array($_POST['mapare_coloane'])) {
            foreach ($_POST['mapare_coloane'] as $index => $db_field) {
                if (!empty($db_field) && $db_field !== 'ignora') {
                    $mapare_coloane[$index] = $db_field;
                }
            }
        }
        if (!empty($mapare_coloane) && !empty($excel_data['headers'])) {
            $import_result = importa_contacte($pdo, $excel_data['headers'], $excel_data['rows'], $mapare_coloane);
            if ($import_result['importati'] > 0) {
                $succes = "Import reușit: {$import_result['importati']} contacte importate.";
            }
            if (!empty($import_result['eroare'])) {
                $eroare = "Erori: " . implode("; ", array_slice($import_result['eroare'], 0, 10));
                if (count($import_result['eroare']) > 10) {
                    $eroare .= " ... și " . (count($import_result['eroare']) - 10) . " altele";
                }
            }
        }
        unlink($file_path);
        unset($_SESSION['contacte_import_path']);
        $excel_data = null;
        $mapare_coloane = null;
    } elseif (isset($_FILES['fisier_excel']) && $_FILES['fisier_excel']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fisier_excel'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            $eroare = 'Tipul fișierului nu este suportat. Folosiți CSV sau Excel (.xlsx, .xls).';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $eroare = 'Fișierul depășește 10 MB.';
        } else {
            $filename = 'import_contacte_' . time() . '_' . uniqid() . '.' . $extension;
            $file_path = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $_SESSION['contacte_import_path'] = $file_path;
                $excel_data = citeste_fisier_excel($file_path);
                if (empty($excel_data['headers'])) {
                    $eroare = 'Nu s-au putut citi header-urile din fișier.';
                    unlink($file_path);
                    unset($_SESSION['contacte_import_path']);
                    $excel_data = null;
                } else {
                    $mapare_coloane = [];
                    $mapare_std = contacte_mapare_coloane();
                    foreach ($excel_data['headers'] as $idx => $h) {
                        $h_clean = trim($h);
                        foreach ($mapare_std as $excel_name => $db_field) {
                            if (stripos($h_clean, $excel_name) !== false || stripos($excel_name, $h_clean) !== false) {
                                $mapare_coloane[$idx] = $db_field;
                                break;
                            }
                        }
                    }
                }
            } else {
                $eroare = 'Eroare la încărcarea fișierului.';
            }
        }
    } elseif (isset($_POST['executa_import'])) {
        $eroare = 'Sesiunea de import a expirat. Încărcați din nou fișierul.';
        unset($_SESSION['contacte_import_path']);
    } else {
        $eroare = 'Nu s-a selectat niciun fișier sau a apărut o eroare la încărcare.';
    }
}

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
?>

<main class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Import contacte din Excel/CSV</h1>
        <a href="contacte.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">← Înapoi</a>
    </header>
    <div class="p-6 overflow-y-auto flex-1 max-w-4xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <?php if ($excel_data === null): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="/contacte/import" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="import_contacte" value="1">
                <div>
                    <label for="fisier_excel" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Fișier Excel/CSV</label>
                    <input type="file" id="fisier_excel" name="fisier_excel" accept=".csv,.xlsx,.xls" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Maxim 10 MB. Formate: CSV, Excel (.xlsx, .xls)</p>
                </div>
                <button type="submit" class="mt-4 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Încarcă și mapează</button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="/contacte/import" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="import_contacte" value="1">
                <input type="hidden" name="executa_import" value="1">
                <p class="mb-4"><strong><?php echo count($excel_data['rows']); ?></strong> rânduri găsite. Mapează coloanele:</p>
                <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded p-3 space-y-2 mb-4">
                    <?php foreach ($excel_data['headers'] as $index => $header): ?>
                    <div class="flex items-center gap-3">
                        <label class="flex-1 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($header); ?></label>
                        <select name="mapare_coloane[<?php echo $index; ?>]" class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white">
                            <option value="ignora">-- Ignoră --</option>
                            <?php foreach ($campuri_contacte as $db_field => $label): ?>
                            <option value="<?php echo htmlspecialchars($db_field); ?>" <?php echo (isset($mapare_coloane[$index]) && $mapare_coloane[$index] === $db_field) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="location.reload()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg">Anulează</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Importă contacte</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
