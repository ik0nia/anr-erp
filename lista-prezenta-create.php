<?php
/**
 * Creare listă de prezență / tabel nominal
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';
require_once 'includes/liste_helper.php';

$eroare = '';
$succes = '';
$din_activitate = isset($_GET['din_activitate']);
$activitate_nume = trim($_GET['nume'] ?? '');
$activitate_data = trim($_GET['data'] ?? date('Y-m-d'));
$activitate_ora = trim($_GET['ora'] ?? '09:00');
$activitate_locatie = trim($_GET['locatie'] ?? '');
$activitate_responsabili = trim($_GET['responsabili'] ?? ($_SESSION['utilizator'] ?? ''));

$activitati_select = [];
try {
    $stmt = $pdo->query("SELECT id, data_ora, nume FROM activitati ORDER BY data_ora DESC LIMIT 100");
    $activitati_select = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_lista'])) {
    csrf_require_valid();
    $tip_titlu = $_POST['tip_titlu'] ?? 'Lista prezenta';
    $detalii_activitate = trim($_POST['detalii_activitate'] ?? '');
    $data_lista = trim($_POST['data_lista'] ?? '');
    $detalii_sus = trim($_POST['detalii_suplimentare_sus'] ?? '');
    $detalii_jos = trim($_POST['detalii_suplimentare_jos'] ?? '');
    $coloane = $_POST['coloane'] ?? [];
    $membri_ids_raw = json_decode($_POST['membri_ids'] ?? '[]', true) ?: [];
    // Validare ID-uri membri: doar numere pozitive
    $membri_ids = [];
    foreach ($membri_ids_raw as $mid) {
        $mid_int = (int)$mid;
        if ($mid_int > 0) {
            $membri_ids[] = $mid_int;
        }
    }
    $activitate_id = !empty($_POST['activitate_id']) ? (int)$_POST['activitate_id'] : null;
    if ($activitate_id !== null && $activitate_id <= 0) {
        $activitate_id = null;
    }
    $creaza_activitate = !empty($_POST['creaza_activitate']);
    if ($creaza_activitate && empty($_POST['activitate_nume'])) {
        $_POST['activitate_nume'] = !empty(trim($_POST['detalii_activitate'] ?? '')) ? trim($_POST['detalii_activitate']) : ($_POST['tip_titlu'] ?? 'Lista prezenta');
        $_POST['activitate_data'] = $_POST['data_lista'] ?? '';
        $_POST['activitate_ora'] = $_POST['ora_lista'] ?? '09:00';
    }
    $semn_st_n = trim($_POST['semn_stanga_nume'] ?? '');
    $semn_st_f = trim($_POST['semn_stanga_functie'] ?? '');
    $semn_c_n = trim($_POST['semn_centru_nume'] ?? '');
    $semn_c_f = trim($_POST['semn_centru_functie'] ?? '');
    $semn_d_n = trim($_POST['semn_dreapta_nume'] ?? '');
    $semn_d_f = trim($_POST['semn_dreapta_functie'] ?? '');

    if (empty($data_lista)) {
        $eroare = 'Data listei este obligatorie.';
    } else {
        try {
            $user = $_SESSION['utilizator'] ?? 'Sistem';
                // Creare automată activitate dacă există data și ora
                if (!empty($data_lista)) {
                    $ora_lista = trim($_POST['ora_lista'] ?? '09:00');
                    if (strlen($ora_lista) === 5) {
                        $ora_lista .= ':00';
                    }
                    $ora_finalizare = trim($_POST['ora_finalizare'] ?? '');
                    if (!empty($ora_finalizare) && strlen($ora_finalizare) === 5) {
                        $ora_finalizare .= ':00';
                    }
                    $data_ora_activitate = $data_lista . ' ' . $ora_lista;
                    $act_nume = ($detalii_activitate ? 'Activitate: ' . mb_substr($detalii_activitate, 0, 100) : $tip_titlu);
                    $act_info = 'Activitate Generata automat din Lista de Participare';
                    
                    // Verifică dacă există coloana ora_finalizare
                    try {
                        $cols_check = $pdo->query("SHOW COLUMNS FROM activitati LIKE 'ora_finalizare'")->fetch();
                        if (!$cols_check) {
                            $pdo->exec("ALTER TABLE activitati ADD COLUMN ora_finalizare TIME DEFAULT NULL AFTER data_ora");
                        }
                    } catch (PDOException $e) {}
                    
                    // Creează activitate doar dacă utilizatorul a bifat „Creează activitate la salvare”
                    if ($creaza_activitate && !$activitate_id) {
                        $act_nume = trim($_POST['activitate_nume'] ?? '') ?: ($detalii_activitate ?: $tip_titlu);
                        $act_data = trim($_POST['activitate_data'] ?? $data_lista);
                        $act_ora = trim($_POST['activitate_ora'] ?? '09:00');
                        if (strlen($act_ora) === 5) $act_ora .= ':00';
                        $data_ora_activitate = $act_data . ' ' . $act_ora;
                        if (!empty($ora_finalizare)) {
                            $stmt_act = $pdo->prepare('INSERT INTO activitati (data_ora, ora_finalizare, nume, responsabili, info_suplimentare) VALUES (?,?,?,?,?)');
                            $stmt_act->execute([$data_ora_activitate, $ora_finalizare, $act_nume, $user, $act_info]);
                        } else {
                            $stmt_act = $pdo->prepare('INSERT INTO activitati (data_ora, nume, responsabili, info_suplimentare) VALUES (?,?,?,?)');
                            $stmt_act->execute([$data_ora_activitate, $act_nume, $user, $act_info]);
                        }
                        $activitate_id = $pdo->lastInsertId();
                        log_activitate($pdo, "activitati: Activitate creata automat din lista participare - {$act_nume}");
                    }
                }
            $coloane_json = json_encode(array_values($coloane));
            $stmt = $pdo->prepare('INSERT INTO liste_prezenta (tip_titlu, detalii_activitate, data_lista, detalii_suplimentare_sus, coloane_selectate, detalii_suplimentare_jos, semnatura_stanga_nume, semnatura_stanga_functie, semnatura_centru_nume, semnatura_centru_functie, semnatura_dreapta_nume, semnatura_dreapta_functie, activitate_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$tip_titlu, $detalii_activitate, $data_lista, $detalii_sus, $coloane_json, $detalii_jos, $semn_st_n, $semn_st_f, $semn_c_n, $semn_c_f, $semn_d_n, $semn_d_f, $activitate_id, $user]);
            $lista_id = $pdo->lastInsertId();

            // Salvare membri cu ID
            $stmt_m = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, ordine, nume_manual) VALUES (?, ?, ?, NULL)');
            $ordine_curenta = 1;
            foreach (array_values($membri_ids) as $mid) {
                if ($mid) {
                    $stmt_m->execute([$lista_id, $mid, $ordine_curenta]);
                    $ordine_curenta++;
                }
            }
            
            // Salvare participanți manuali (fără membru_id)
            // Verifică dacă există coloana nume_manual
            try {
                $cols_check = $pdo->query("SHOW COLUMNS FROM liste_prezenta_membri LIKE 'nume_manual'")->fetch();
                if (!$cols_check) {
                    $pdo->exec("ALTER TABLE liste_prezenta_membri ADD COLUMN nume_manual VARCHAR(255) DEFAULT NULL AFTER membru_id");
                }
            } catch (PDOException $e) {
                // Coloana poate exista deja sau eroare la ALTER
            }
            
            $stmt_m_manual = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, ordine, nume_manual) VALUES (?, NULL, ?, ?)');
            foreach ($participanti_manuali as $pm) {
                $stmt_m_manual->execute([$lista_id, $ordine_curenta, $pm['nume']]);
                $ordine_curenta++;
            }

            if ($activitate_id) {
                $pdo->prepare('UPDATE activitati SET lista_prezenta_id = ?, responsabili = COALESCE(responsabili, ?) WHERE id = ?')->execute([$lista_id, $user, $activitate_id]);
            }

            log_activitate($pdo, 'Listă prezență creată: ' . $tip_titlu . ' - ' . $detalii_activitate);

            $act = $_POST['actiune_dupa'] ?? '';
            if ($act === 'print') {
                header('Location: lista-prezenta-print.php?id=' . $lista_id);
                exit;
            }
            if ($act === 'pdf') {
                header('Location: lista-prezenta-pdf.php?id=' . $lista_id);
                exit;
            }
            header('Location: activitati.php?succes_lista=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la salvare: ' . $e->getMessage();
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Creare listă prezență / tabel nominal</h1>
        <a href="activitati.php" class="text-amber-600 dark:text-amber-400 hover:underline">Înapoi la activități</a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>

        <form method="post" id="form-lista" class="space-y-6 max-w-4xl">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="salveaza_lista" value="1">
            <input type="hidden" name="membri_ids" id="membri_ids_json" value="[]">

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Detalii document</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Titlu</label>
                        <select name="tip_titlu" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" required aria-label="Selectează tipul titlului listei" aria-required="true">
                            <option value="Lista prezenta">Listă prezență</option>
                            <option value="Tabel nominal">Tabel nominal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" name="data_lista" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" aria-required="true">
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Ora început <span class="text-slate-500 dark:text-gray-400 text-xs">(pentru creare automată activitate)</span></label>
                            <input type="time" name="ora_lista" value="09:00" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" aria-label="Ora de început pentru creare automată activitate">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Ora finalizare <span class="text-slate-500 dark:text-gray-400 text-xs">(opțional)</span></label>
                            <input type="time" name="ora_finalizare" value="" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" aria-label="Ora de finalizare pentru activitate (opțional)">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Activitate:</label>
                        <input type="text" name="detalii_activitate" placeholder="Ex: Ședință comitet de conducere" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>
                    <?php if ($din_activitate && $activitate_nume): ?>
                    <div class="md:col-span-2 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <input type="hidden" name="creaza_activitate" value="1">
                        <input type="hidden" name="activitate_nume" value="<?php echo htmlspecialchars($activitate_nume); ?>">
                        <input type="hidden" name="activitate_data" value="<?php echo htmlspecialchars($activitate_data); ?>">
                        <input type="hidden" name="activitate_ora" value="<?php echo htmlspecialchars($activitate_ora); ?>">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Se va crea activitatea: <strong><?php echo htmlspecialchars($activitate_nume); ?></strong> (<?php echo date(DATE_FORMAT, strtotime($activitate_data)); ?> <?php echo $activitate_ora; ?>) și se va asocia acestei liste.</p>
                        <input type="text" name="activitate_locatie" placeholder="Locație (opțional)" value="<?php echo htmlspecialchars($activitate_locatie); ?>" class="mt-2 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        <input type="text" name="activitate_responsabili" placeholder="Responsabili (opțional)" value="<?php echo htmlspecialchars($activitate_responsabili); ?>" class="mt-2 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <?php endif; ?>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Asociere cu activitate existentă (opțional)</label>
                        <select name="activitate_id" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" aria-label="Selectează activitatea asociată (opțional)">
                            <option value="">— Fără asociere / Creare nouă —</option>
                            <?php foreach ($activitati_select as $act): ?>
                            <option value="<?php echo $act['id']; ?>"><?php echo htmlspecialchars($act['nume'] . ' - ' . date(DATE_FORMAT, strtotime($act['data_ora']))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Detalii suplimentare (sus)</label>
                        <textarea name="detalii_suplimentare_sus" rows="2" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600"></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Participanți</h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-slate-900 dark:text-white">Căutare membri</label>
                    <div class="flex gap-2">
                        <input type="text" id="cauta-membru" placeholder="Nume, prenume, CNP..." class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        <button type="button" id="btn-cauta" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Caută</button>
                    </div>
                    <div id="rezultate-cautare" class="mt-2 border border-slate-200 dark:border-gray-600 rounded-lg p-2 max-h-48 overflow-y-auto hidden bg-white dark:bg-gray-700/50 text-slate-900 dark:text-white"></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Coloane de afișat în tabel:</p>
                <div class="flex flex-wrap gap-4 mb-4">
                    <?php foreach (LISTE_COLOANE as $k => $l): ?>
                    <label class="flex items-center gap-2 text-slate-900 dark:text-white">
                        <input type="checkbox" name="coloane[]" value="<?php echo $k; ?>" <?php echo in_array($k, ['nr_crt','nume_prenume','semnatura']) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($l); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="mb-4">
                    <button type="button" id="btn-adauga-manual" class="px-4 py-2 bg-slate-600 dark:bg-gray-600 hover:bg-slate-700 dark:hover:bg-gray-700 text-white rounded-lg text-sm font-medium">
                        <i data-lucide="user-plus" class="w-4 h-4 inline-block mr-2" aria-hidden="true"></i>
                        Adaugă participant manual
                    </button>
                </div>
                <div id="lista-participanti" class="border border-slate-200 dark:border-gray-600 rounded-lg p-4 min-h-[100px] bg-white dark:bg-gray-800">
                    <p class="text-slate-500 dark:text-gray-400 text-sm">Adăugați participanți folosind căutarea de mai sus sau butonul "Adaugă participant manual".</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <label class="block text-sm font-medium mb-2 text-slate-900 dark:text-white">Detalii suplimentare (jos, după tabel)</label>
                <textarea name="detalii_suplimentare_jos" rows="3" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600"></textarea>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Semnături (3 coloane)</h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Numele și funcția apar deasupra liniei de semnătură. Linia se afișează doar dacă sunt completate.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="border border-slate-200 dark:border-gray-600 rounded-lg p-4">
                        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Stânga - Nume</label>
                        <input type="text" name="semn_stanga_nume" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <label class="block text-sm font-medium mt-2 mb-1 text-slate-700 dark:text-gray-300">Funcție</label>
                        <input type="text" name="semn_stanga_functie" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <div class="mt-3 border-b border-slate-300 dark:border-gray-500 h-10" aria-hidden="true" title="Linie semnătură"></div>
                    </div>
                    <div class="border border-slate-200 dark:border-gray-600 rounded-lg p-4">
                        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Centru - Nume</label>
                        <input type="text" name="semn_centru_nume" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <label class="block text-sm font-medium mt-2 mb-1 text-slate-700 dark:text-gray-300">Funcție</label>
                        <input type="text" name="semn_centru_functie" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <div class="mt-3 border-b border-slate-300 dark:border-gray-500 h-10" aria-hidden="true" title="Linie semnătură"></div>
                    </div>
                    <div class="border border-slate-200 dark:border-gray-600 rounded-lg p-4">
                        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Dreapta - Nume</label>
                        <input type="text" name="semn_dreapta_nume" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <label class="block text-sm font-medium mt-2 mb-1 text-slate-700 dark:text-gray-300">Funcție</label>
                        <input type="text" name="semn_dreapta_functie" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <div class="mt-3 border-b border-slate-300 dark:border-gray-500 h-10" aria-hidden="true" title="Linie semnătură"></div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2 text-slate-900 dark:text-white cursor-pointer">
                    <input type="checkbox" name="creaza_activitate" value="1" class="rounded border-slate-300 dark:border-gray-500 text-amber-600 focus:ring-amber-500"
                           <?php echo (isset($_POST['creaza_activitate']) || ($din_activitate && $activitate_nume)) ? 'checked' : ''; ?>
                           aria-describedby="creaza-activitate-desc">
                    <span>La salvare, creează activitate în Activități Programate (la data și ora listei)</span>
                </label>
                <p id="creaza-activitate-desc" class="text-sm text-slate-500 dark:text-gray-400 mt-1 ml-6">Bifați pentru a crea automat o activitate în Calendar (Activități Programate) asociată acestei liste.</p>
            </div>
            <div class="flex flex-wrap gap-4">
                <button type="submit" name="actiune_dupa" value="" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează lista de prezență">Salvează</button>
                <button type="submit" name="actiune_dupa" value="print" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium" aria-label="Salvează lista de prezență și printează">Salvează și printează</button>
                <button type="submit" name="actiune_dupa" value="pdf" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium" aria-label="Salvează lista de prezență și descarcă PDF">Salvează și descarcă PDF</button>
                <a href="activitati.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-white hover:bg-slate-100 dark:hover:bg-gray-700" aria-label="Renunță la creare listă">Renunță</a>
            </div>
        </form>
    </div>
</main>

<script>
const membriSelectati = [];
const LISTE_COLOANE = <?php echo json_encode(LISTE_COLOANE); ?>;

function renderLista() {
    const c = document.getElementById('lista-participanti');
    const j = document.getElementById('membri_ids_json');
    const jManual = document.getElementById('participanti_manuali_json');
    j.value = JSON.stringify(membriSelectati.filter(m => m.id).map(m => m.id));
    jManual.value = JSON.stringify(membriSelectati.filter(m => !m.id && m.numeManual).map(m => ({ nume: m.numeManual, ordine: m.ordine })));
    
    if (membriSelectati.length === 0) {
        c.innerHTML = '<p class="text-slate-500 dark:text-gray-400 text-sm">Adăugați participanți folosind căutarea de mai sus sau butonul "Adaugă participant manual".</p>';
        return;
    }
    
    c.innerHTML = '<table class="min-w-full text-sm border border-slate-200 dark:border-gray-600"><thead class="bg-slate-100 dark:bg-gray-600"><tr><th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Nr.</th><th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Nume</th><th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Acțiune</th></tr></thead><tbody class="bg-white dark:bg-gray-800">' +
        membriSelectati.map((m, i) => {
            const numeAfisat = m.id ? (m.nume + ' ' + m.prenume) : (m.numeManual || '');
            const idUnic = m.id || ('manual_' + m.ordine);
            return '<tr class="border-b border-slate-200 dark:border-gray-600"><td class="py-2 px-3 text-slate-900 dark:text-white">' + (i+1) + '</td><td class="py-2 px-3 text-slate-900 dark:text-white">' + 
                (m.id ? numeAfisat : '<input type="text" value="' + (m.numeManual || '') + '" onchange="actualizeazaNumeManual(' + m.ordine + ', this.value)" placeholder="Nume participant" class="w-full px-2 py-1 border border-slate-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white text-slate-900">') + 
                '</td><td class="py-2 px-3"><button type="button" onclick="stergeParticipant(' + (m.id || 0) + ', ' + (m.ordine || 0) + ')" class="text-red-600 dark:text-red-400 hover:underline text-xs">Șterge</button></td></tr>';
        }).join('') +
        '</tbody></table>';
}

function stergeParticipant(id) {
    const i = membriSelectati.findIndex(m => m.id == id);
    if (i >= 0) { membriSelectati.splice(i, 1); renderLista(); }
}

function adaugaParticipant(m) {
    if (membriSelectati.some(x => x.id == m.id)) return;
    membriSelectati.push(m);
    renderLista();
}

function executaCautareLista() {
    const q = document.getElementById('cauta-membru').value.trim();
    const div = document.getElementById('rezultate-cautare');
    if (q.length < 2) { div.classList.add('hidden'); return; }
    div.classList.remove('hidden');
    div.innerHTML = '<p class="text-slate-500 dark:text-gray-400 py-2">Se caută…</p>';
    fetch('api-cauta-membri.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(d => {
            const membri = d && d.membri ? d.membri : [];
            div.innerHTML = membri.length ? membri.map((m) =>
                '<div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-gray-600"><span class="text-slate-900 dark:text-white">' + (m.nume || '') + ' ' + (m.prenume || '') + '</span><button type="button" data-membru-id="' + m.id + '" data-membru-nume="' + (m.nume || '') + '" data-membru-prenume="' + (m.prenume || '') + '" class="btn-adauga-membru px-2 py-1 bg-amber-600 text-white rounded text-xs">Adaugă în listă</button></div>'
            ).join('') : '<p class="text-slate-500 dark:text-gray-400 py-2">Niciun rezultat.</p>';
            div.querySelectorAll('.btn-adauga-membru').forEach(btn => {
                btn.onclick = function() {
                    adaugaParticipant({ id: parseInt(this.dataset.membruId), nume: this.dataset.membruNume || '', prenume: this.dataset.membruPrenume || '' });
                };
            });
        })
        .catch(function() {
            div.innerHTML = '<p class="text-red-600 dark:text-red-400 py-2">Eroare la căutare. Încercați din nou.</p>';
        });
}
document.getElementById('btn-cauta').onclick = executaCautareLista;
document.getElementById('cauta-membru').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); executaCautareLista(); }
});
document.getElementById('btn-adauga-manual').onclick = function() {
    adaugaParticipant();
};

document.getElementById('form-lista').onsubmit = function() {
    document.getElementById('membri_ids_json').value = JSON.stringify(membriSelectati.filter(m => m.id).map(m => m.id));
    document.getElementById('participanti_manuali_json').value = JSON.stringify(membriSelectati.filter(m => !m.id && m.numeManual).map(m => ({ nume: m.numeManual, ordine: m.ordine })));
    return true;
};
</script>
<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
