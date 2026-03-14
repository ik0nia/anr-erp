<?php
/**
 * Modul Administrativ - CRM ANR Bihor
 * Necesar achiziții, Echipa, Calendar administrativ, Consiliul Director, Adunarea Generală, Juridic ANR, Parteneriate, Proceduri
 */
require_once __DIR__ . '/config.php';
require_once 'includes/administrativ_helper.php';
require_once 'includes/log_helper.php';
require_once 'includes/auth_helper.php';

require_login();
administrativ_ensure_tables($pdo);

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['achizitii','echipa','calendar','cd','ag','juridic','parteneriate','proceduri']) ? $_GET['tab'] : 'achizitii';
$eroare = '';
$succes = '';
$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
$user_id = $_SESSION['user_id'] ?? null;

// ---- POST: Necesar achiziții ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();
    if (isset($_POST['adauga_achizitie'])) {
        $denumire = trim($_POST['denumire'] ?? '');
        $locatie = in_array($_POST['locatie'] ?? '', ['Sediu', 'Centru', 'Alta']) ? $_POST['locatie'] : null;
        $urgenta = in_array($_POST['urgenta'] ?? '', ['normal', 'urgent', 'optional']) ? $_POST['urgenta'] : 'normal';
        $furnizor = trim($_POST['furnizor'] ?? '');
        if ($denumire !== '') {
            $aid = administrativ_achizitie_adauga($pdo, $denumire, $locatie, $urgenta, $furnizor ?: null);
            log_activitate($pdo, 'Administrativ: achiziție adăugată ' . $denumire . ($locatie ? ' (Loc: ' . $locatie . ')' : '') . ' Urgență: ' . $urgenta);
            if ($urgenta === 'urgent') {
                require_once __DIR__ . '/includes/notificari_helper.php';
                notificari_adauga($pdo, ['titlu' => 'Achiziție urgentă: ' . $denumire, 'importanta' => 'Important', 'continut' => 'A fost adăugată o achiziție marcată ca urgentă în modulul Administrativ. Denumire: ' . $denumire . ($furnizor ? '. Furnizor: ' . $furnizor : ''), 'trimite_email' => 0], null, $user_id);
            }
            header('Location: administrativ.php?tab=achizitii&succes=achizitie');
            exit;
        }
    }
    if (isset($_POST['marcheaza_cumparat'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && administrativ_achizitie_marcheaza_cumparat($pdo, $id)) {
            log_activitate($pdo, 'Administrativ: achiziție marcată cumpărată ID ' . $id);
            header('Location: administrativ.php?tab=achizitii&succes=cumparat');
            exit;
        }
    }
    if (isset($_POST['sterge_achizitie'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            administrativ_achizitie_sterge($pdo, $id);
            log_activitate($pdo, 'Administrativ: achiziție ștearsă ID ' . $id);
            header('Location: administrativ.php?tab=achizitii&succes=sterge');
            exit;
        }
    }
    // Angajat salvează/șterge
    if (isset($_POST['salveaza_angajat'])) {
        $id = (int)($_POST['id_angajat'] ?? 0);
        $rid = administrativ_angajat_salveaza($pdo, $id, $_POST);
        if ($rid) {
            log_activitate($pdo, 'Administrativ: angajat salvat ID ' . $rid);
            header('Location: administrativ.php?tab=echipa&succes=angajat');
            exit;
        }
    }
    if (isset($_POST['sterge_angajat'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            administrativ_angajat_sterge($pdo, $id);
            log_activitate($pdo, 'Administrativ: angajat șters ID ' . $id);
            header('Location: administrativ.php?tab=echipa&succes=sterge_angajat');
            exit;
        }
    }
    // CD / AG nomenclator
    if (isset($_POST['salveaza_cd'])) {
        $id = (int)($_POST['id_cd'] ?? 0);
        administrativ_cd_salveaza($pdo, $id, $_POST['membru_id'] ?? null, $_POST['nume_manual'] ?? '', $_POST['prenume_manual'] ?? '', $_POST['functie'] ?? '', (int)($_POST['ordine'] ?? 0), $_POST['email'] ?? null, $_POST['telefon'] ?? null);
        log_activitate($pdo, 'Administrativ: membru Consiliul Director salvat');
        header('Location: administrativ.php?tab=echipa&succes=cd');
        exit;
    }
    if (isset($_POST['sterge_cd'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { administrativ_cd_sterge($pdo, $id); log_activitate($pdo, 'Administrativ: membru C.D. șters ID ' . $id); header('Location: administrativ.php?tab=echipa&succes=sterge_cd'); exit; }
    }
    if (isset($_POST['salveaza_ag'])) {
        $id = (int)($_POST['id_ag'] ?? 0);
        administrativ_ag_salveaza($pdo, $id, $_POST['membru_id'] ?? null, $_POST['nume_manual'] ?? '', $_POST['prenume_manual'] ?? '', (int)($_POST['ordine'] ?? 0), $_POST['functie'] ?? null, $_POST['email'] ?? null, $_POST['telefon'] ?? null);
        log_activitate($pdo, 'Administrativ: membru Adunare Generală salvat');
        header('Location: administrativ.php?tab=echipa&succes=ag');
        exit;
    }
    if (isset($_POST['sterge_ag'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { administrativ_ag_sterge($pdo, $id); log_activitate($pdo, 'Administrativ: membru A.G. șters ID ' . $id); header('Location: administrativ.php?tab=echipa&succes=sterge_ag'); exit; }
    }
    // Calendar termen
    if (isset($_POST['salveaza_termen'])) {
        $id = (int)($_POST['id_termen'] ?? 0);
        administrativ_calendar_salveaza($pdo, $id, $_POST['nume'] ?? '', $_POST['data_inceput'] ?? '', $_POST['data_expirarii'] ?? '', $_POST['tip_document'] ?? 'alt_document', $_POST['observatii'] ?? null, $_POST['angajat_id'] ? (int)$_POST['angajat_id'] : null);
        log_activitate($pdo, 'Administrativ: termen calendar salvat');
        header('Location: administrativ.php?tab=calendar&succes=termen');
        exit;
    }
    if (isset($_POST['sterge_termen'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { administrativ_calendar_sterge($pdo, $id); log_activitate($pdo, 'Administrativ: termen calendar șters ID ' . $id); header('Location: administrativ.php?tab=calendar&succes=sterge_termen'); exit; }
    }
    // Sedință CD
    if (isset($_POST['adauga_sedinta_cd'])) {
        $data = trim($_POST['data_sedinta'] ?? ''); $ora = trim($_POST['ora'] ?? '09:00'); $loc = trim($_POST['loc'] ?? '');
        if ($data) {
            administrativ_cd_sedinta_adauga($pdo, $data, $ora, $loc, !empty($_POST['creaza_activitate']));
            log_activitate($pdo, 'Administrativ: sedință C.D. programată ' . $data);
            header('Location: administrativ.php?tab=cd&succes=sedinta');
            exit;
        }
    }
    // Sedință AG
    if (isset($_POST['adauga_sedinta_ag'])) {
        $data = trim($_POST['data_sedinta'] ?? ''); $ora = trim($_POST['ora'] ?? '09:00'); $loc = trim($_POST['loc'] ?? '');
        if ($data) {
            administrativ_ag_sedinta_adauga($pdo, $data, $ora, $loc, !empty($_POST['creaza_activitate']));
            log_activitate($pdo, 'Administrativ: sedință A.G. programată ' . $data);
            header('Location: administrativ.php?tab=ag&succes=sedinta');
            exit;
        }
    }
    // Juridic ANR
    if (isset($_POST['adauga_juridic'])) {
        $creaza = !empty($_POST['creaza_task_todo']);
        $notif = !empty($_POST['trimite_notificare_platforma']);
        $creaza_procedura = !empty($_POST['creaza_procedura_interna']);
        administrativ_juridic_adauga($pdo, $_POST, $creaza, $notif, $user_id, $creaza_procedura);
        log_activitate($pdo, 'Administrativ: înregistrare Juridic ANR adăugată');
        header('Location: administrativ.php?tab=juridic&succes=juridic');
        exit;
    }
    // Parteneriat
    if (isset($_POST['salveaza_parteneriat'])) {
        $id = (int)($_POST['id_parteneriat'] ?? 0);
        administrativ_parteneriat_salveaza($pdo, $id, $_POST['nume_partener'] ?? '', $_POST['obiect_parteneriat'] ?? '', $_POST['data_inceput'] ?? null, $_POST['data_sfarsit'] ?? null, $_POST['observatii'] ?? null);
        log_activitate($pdo, 'Administrativ: parteneriat salvat');
        header('Location: administrativ.php?tab=parteneriate&succes=parteneriat');
        exit;
    }
    if (isset($_POST['sterge_parteneriat'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { administrativ_parteneriat_sterge($pdo, $id); log_activitate($pdo, 'Administrativ: parteneriat șters ID ' . $id); header('Location: administrativ.php?tab=parteneriate&succes=sterge'); exit; }
    }
    // Procedură
    if (isset($_POST['salveaza_procedura'])) {
        $id = (int)($_POST['id_procedura'] ?? 0);
        $titlu = $_POST['titlu'] ?? '';
        $continut = $_POST['continut'] ?? '';
        administrativ_procedura_salveaza($pdo, $id, $titlu, $continut);
        if (!empty($_POST['trimite_notificare_procedura'])) {
            require_once __DIR__ . '/includes/notificari_helper.php';
            notificari_adauga($pdo, ['titlu' => 'Procedură internă: ' . $titlu, 'importanta' => 'Informativ', 'continut' => 'A fost adăugată/actualizată o procedură internă. ' . (mb_strlen($continut) > 200 ? mb_substr(strip_tags($continut), 0, 200) . '…' : strip_tags($continut)), 'trimite_email' => 0], null, $user_id);
        }
        log_activitate($pdo, 'Administrativ: procedură internă salvată');
        header('Location: administrativ.php?tab=proceduri&succes=procedura');
        exit;
    }
    if (isset($_POST['sterge_procedura'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { administrativ_procedura_sterge($pdo, $id); log_activitate($pdo, 'Administrativ: procedură ștearsă ID ' . $id); header('Location: administrativ.php?tab=proceduri&succes=sterge'); exit; }
    }
}

$succes_get = $_GET['succes'] ?? '';
if ($succes_get) $succes = 'Salvare reușită.';

$lista_achizitii = administrativ_achizitii_lista($pdo, false);
$lista_istoric = administrativ_achizitii_istoric($pdo, 50);
$lista_angajati = administrativ_angajati_lista($pdo);
$lista_cd = administrativ_cd_lista($pdo);
$lista_ag = administrativ_ag_lista($pdo);
$data_start = date('Y-m-d', strtotime('-1 year'));
$data_end = date('Y-m-d', strtotime('+2 years'));
$lista_termene = administrativ_calendar_lista($pdo, $data_start, $data_end);
$lista_sedinte_cd = administrativ_cd_sedinte_lista($pdo, $data_start, $data_end);
$lista_sedinte_ag = administrativ_ag_sedinte_lista($pdo, $data_start, $data_end);
$lista_juridic = administrativ_juridic_lista($pdo);
$lista_parteneriate = administrativ_parteneriate_lista($pdo);
$cautare_proceduri = isset($_GET['cautare_proceduri']) ? trim($_GET['cautare_proceduri']) : '';
$lista_proceduri = administrativ_proceduri_lista($pdo, $cautare_proceduri !== '' ? $cautare_proceduri : null);
$edit_procedura_id = isset($_GET['edit_procedura']) ? (int)$_GET['edit_procedura'] : 0;
$edit_procedura = $edit_procedura_id > 0 ? administrativ_procedura_get($pdo, $edit_procedura_id) : null;
$edit_angajat_id = isset($_GET['edit_angajat']) ? (int)$_GET['edit_angajat'] : 0;
$edit_angajat = $edit_angajat_id > 0 ? administrativ_angajat_get($pdo, $edit_angajat_id) : null;
$edit_cd_id = isset($_GET['edit_cd']) ? (int)$_GET['edit_cd'] : 0;
$edit_cd = $edit_cd_id > 0 ? administrativ_cd_get($pdo, $edit_cd_id) : null;
$edit_ag_id = isset($_GET['edit_ag']) ? (int)$_GET['edit_ag'] : 0;
$edit_ag = $edit_ag_id > 0 ? administrativ_ag_get($pdo, $edit_ag_id) : null;
$tipuri_doc = administrativ_tipuri_document_calendar();
$categorii_juridic = administrativ_categorii_juridic();

require_once 'header.php';
include 'sidebar.php';
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">Modul Administrativ</h1>
        <nav class="flex gap-2 flex-wrap" role="tablist" aria-label="Tab-uri modul Administrativ">
            <?php
            $tabs = [
                'achizitii' => 'Lista achiziții',
                'echipa' => 'Echipa',
                'calendar' => 'Calendar administrativ',
                'cd' => 'Consiliul Director',
                'ag' => 'Adunarea Generală',
                'juridic' => 'Juridic ANR',
                'parteneriate' => 'Parteneriate',
                'proceduri' => 'Proceduri interne',
            ];
            foreach ($tabs as $k => $label):
                $active = $tab === $k;
            ?>
            <a href="administrativ.php?tab=<?php echo $k; ?>" role="tab" aria-selected="<?php echo $active ? 'true' : 'false'; ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $active ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"><?php echo htmlspecialchars($label); ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if ($eroare): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <!-- Tab Necesar achiziții -->
        <?php if ($tab === 'achizitii'): ?>
        <div class="max-w-3xl">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4" aria-labelledby="titlu-achizitii">
                <h2 id="titlu-achizitii" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Lista achiziții</h2>
                <form method="post" action="administrativ.php?tab=achizitii" class="space-y-3 mb-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_achizitie" value="1">
                    <div class="flex flex-wrap gap-2 items-end">
                        <label for="denumire-achizitie" class="sr-only">Produs necesar</label>
                        <input type="text" id="denumire-achizitie" name="denumire" required placeholder="Denumire produs..." class="min-w-[200px] flex-1 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500 placeholder-slate-500 dark:placeholder-gray-400">
                        <label for="achizitie-locatie" class="text-sm text-slate-700 dark:text-gray-300">Locație</label>
                        <select id="achizitie-locatie" name="locatie" class="rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            <option value="">—</option>
                            <option value="Sediu">Sediu</option>
                            <option value="Centru">Centru</option>
                            <option value="Alta">Alta</option>
                        </select>
                        <label for="achizitie-urgenta" class="text-sm text-slate-700 dark:text-gray-300">Urgență</label>
                        <select id="achizitie-urgenta" name="urgenta" class="rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            <option value="normal">Normal</option>
                            <option value="urgent">Urgent</option>
                            <option value="optional">Optional</option>
                        </select>
                        <label for="achizitie-furnizor" class="text-sm text-slate-700 dark:text-gray-300">Furnizor</label>
                        <input type="text" id="achizitie-furnizor" name="furnizor" placeholder="Furnizor" class="w-40 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">Adaugă</button>
                    </div>
                </form>
                <ul class="space-y-1 text-slate-900 dark:text-gray-100 border-t border-slate-200 dark:border-gray-600 pt-3">
                    <?php foreach ($lista_achizitii as $a): ?>
                    <li class="flex items-center gap-3 py-1.5 border-b border-slate-100 dark:border-gray-600 last:border-0 flex-wrap">
                        <form method="post" action="administrativ.php?tab=achizitii" class="flex items-center gap-2 flex-1 min-w-0">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="marcheaza_cumparat" value="1">
                            <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                            <input type="checkbox" <?php echo $a['cumparat'] ? 'checked disabled' : ''; ?> onchange="this.form.submit()" class="rounded border-slate-300 dark:border-gray-500 text-amber-600 focus:ring-amber-500" aria-label="Marchează cumpărat: <?php echo htmlspecialchars($a['denumire']); ?>">
                            <span class="<?php echo $a['cumparat'] ? 'line-through text-slate-500 dark:text-gray-400' : ''; ?>"><?php echo htmlspecialchars($a['denumire']); ?></span>
                            <?php if (!empty($a['locatie'])): ?><span class="text-xs text-slate-500 dark:text-gray-400">(<?php echo htmlspecialchars($a['locatie']); ?>)</span><?php endif; ?>
                            <?php if (!empty($a['urgenta']) && $a['urgenta'] !== 'normal'): ?><span class="text-xs <?php echo $a['urgenta'] === 'urgent' ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-500 dark:text-gray-400'; ?>"><?php echo $a['urgenta'] === 'urgent' ? 'Urgent' : 'Optional'; ?></span><?php endif; ?>
                            <?php if (!empty($a['furnizor'])): ?><span class="text-xs text-slate-500 dark:text-gray-400">Furnizor: <?php echo htmlspecialchars($a['furnizor']); ?></span><?php endif; ?>
                            <?php if ($a['cumparat'] && !empty($a['data_cumparare'])): ?>
                            <span class="text-xs text-slate-500 dark:text-gray-400">(<?php echo date(DATE_FORMAT, strtotime($a['data_cumparare'])); ?>)</span>
                            <?php endif; ?>
                        </form>
                        <form method="post" action="administrativ.php?tab=achizitii" class="inline" onsubmit="return confirm('Ștergeți acest item?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="sterge_achizitie" value="1">
                            <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                            <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Șterge</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($lista_achizitii)): ?>
                    <li class="text-slate-500 dark:text-gray-400 py-2">Niciun produs în listă. Adăugați cu formularul de mai sus.</li>
                    <?php endif; ?>
                </ul>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="titlu-istoric-achizitii">
                <h2 id="titlu-istoric-achizitii" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Istoric cumpărări</h2>
                <ul class="space-y-1 text-sm text-slate-700 dark:text-gray-300">
                    <?php foreach (array_slice($lista_istoric, 0, 30) as $i): ?>
                    <li><?php echo htmlspecialchars($i['denumire']); ?> – <?php echo date(DATE_FORMAT, strtotime($i['data_cumparare'])); ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($lista_istoric)): ?>
                    <li class="text-slate-500 dark:text-gray-400">Niciun istoric.</li>
                    <?php endif; ?>
                </ul>
            </section>
        </div>
        <?php endif; ?>

        <!-- Tab Echipa -->
        <?php if ($tab === 'echipa'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="titlu-angajati">
                <h2 id="titlu-angajati" class="text-lg font-semibold text-slate-900 dark:text-white mb-3"><?php echo $edit_angajat ? 'Modifică angajat' : 'Angajați'; ?></h2>
                <form method="post" action="administrativ.php?tab=echipa" class="space-y-2 mb-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="salveaza_angajat" value="1">
                    <input type="hidden" name="id_angajat" value="<?php echo $edit_angajat ? (int)$edit_angajat['id'] : 0; ?>">
                    <input type="text" name="nume" placeholder="Nume" value="<?php echo $edit_angajat ? htmlspecialchars($edit_angajat['nume'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="prenume" placeholder="Prenume" value="<?php echo $edit_angajat ? htmlspecialchars($edit_angajat['prenume'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="functie" placeholder="Funcție" value="<?php echo $edit_angajat ? htmlspecialchars($edit_angajat['functie'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="email" name="email" placeholder="Email" value="<?php echo $edit_angajat ? htmlspecialchars($edit_angajat['email'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="telefon" placeholder="Telefon" value="<?php echo $edit_angajat ? htmlspecialchars($edit_angajat['telefon'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="date" name="data_angajare" value="<?php echo $edit_angajat && !empty($edit_angajat['data_angajare']) ? $edit_angajat['data_angajare'] : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="block text-slate-700 dark:text-gray-300 mb-0.5">Medicina muncii</span>
                            <div class="flex gap-1"><input type="date" name="data_inceput_medicina_muncii" value="<?php echo $edit_angajat && !empty($edit_angajat['data_inceput_medicina_muncii']) ? $edit_angajat['data_inceput_medicina_muncii'] : ''; ?>" class="flex-1 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1"><input type="date" name="data_expirarii_medicina_muncii" value="<?php echo $edit_angajat && !empty($edit_angajat['data_expirarii_medicina_muncii']) ? $edit_angajat['data_expirarii_medicina_muncii'] : ''; ?>" class="flex-1 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1"></div></div>
                        <div><span class="block text-slate-700 dark:text-gray-300 mb-0.5">Instruire PSI/SSM</span>
                            <div class="flex gap-1"><input type="date" name="data_inceput_psi_ssm" value="<?php echo $edit_angajat && !empty($edit_angajat['data_inceput_psi_ssm']) ? $edit_angajat['data_inceput_psi_ssm'] : ''; ?>" class="flex-1 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1"><input type="date" name="data_expirarii_psi_ssm" value="<?php echo $edit_angajat && !empty($edit_angajat['data_expirarii_psi_ssm']) ? $edit_angajat['data_expirarii_psi_ssm'] : ''; ?>" class="flex-1 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1"></div></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300"><input type="checkbox" name="notificare_medicina_muncii" value="1" <?php echo (!$edit_angajat || !empty($edit_angajat['notificare_medicina_muncii'])) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600"> Notificare Medicina muncii</label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300"><input type="checkbox" name="notificare_instruire_psi_ssm" value="1" <?php echo (!$edit_angajat || !empty($edit_angajat['notificare_instruire_psi_ssm'])) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600"> Notificare Instructaj PSI/SSM</label>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg"><?php echo $edit_angajat ? 'Salvează modificările' : 'Adaugă angajat'; ?></button>
                        <?php if ($edit_angajat): ?>
                        <a href="administrativ.php?tab=echipa" class="px-3 py-1.5 bg-slate-200 dark:bg-gray-600 hover:bg-slate-300 dark:hover:bg-gray-500 text-slate-800 dark:text-white text-sm rounded-lg">Renunță</a>
                        <?php endif; ?>
                    </div>
                </form>
                <ul class="divide-y divide-slate-200 dark:divide-gray-600 text-slate-900 dark:text-gray-100">
                    <?php foreach ($lista_angajati as $ang): ?>
                    <li class="py-2 flex justify-between items-start gap-2">
                        <span><?php echo htmlspecialchars(trim($ang['nume'] . ' ' . $ang['prenume'])); ?><?php if ($ang['functie']): ?> – <?php echo htmlspecialchars($ang['functie']); endif; ?></span>
                        <span class="flex gap-1 shrink-0">
                            <a href="administrativ.php?tab=echipa&amp;edit_angajat=<?php echo (int)$ang['id']; ?>" class="text-amber-600 dark:text-amber-400 text-xs hover:underline" aria-label="Editează <?php echo htmlspecialchars(trim($ang['nume'] . ' ' . $ang['prenume'])); ?>">Editare</a>
                            <form method="post" class="inline" onsubmit="return confirm('Ștergeți acest angajat?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="sterge_angajat" value="1">
                                <input type="hidden" name="id" value="<?php echo (int)$ang['id']; ?>">
                                <button type="submit" class="text-red-600 dark:text-red-400 text-xs hover:underline">Șterge</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="titlu-cd">
                <h2 id="titlu-cd" class="text-lg font-semibold text-slate-900 dark:text-white mb-3"><?php echo $edit_cd ? 'Modifică membru C.D.' : 'Consiliul Director'; ?></h2>
                <form method="post" action="administrativ.php?tab=echipa" class="space-y-2 mb-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="salveaza_cd" value="1">
                    <input type="hidden" name="id_cd" value="<?php echo $edit_cd ? (int)$edit_cd['id'] : 0; ?>">
                    <input type="hidden" name="membru_id" value="<?php echo $edit_cd ? (int)($edit_cd['membru_id'] ?? 0) : 0; ?>">
                    <input type="text" name="nume_manual" placeholder="Nume" value="<?php echo $edit_cd ? htmlspecialchars($edit_cd['nume_manual'] ?? $edit_cd['membru_nume'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="prenume_manual" placeholder="Prenume" value="<?php echo $edit_cd ? htmlspecialchars($edit_cd['prenume_manual'] ?? $edit_cd['membru_prenume'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="functie" placeholder="Funcție (ex. Președinte)" value="<?php echo $edit_cd ? htmlspecialchars($edit_cd['functie'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="email" name="email" placeholder="Email" value="<?php echo $edit_cd ? htmlspecialchars($edit_cd['email'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="telefon" placeholder="Telefon" value="<?php echo $edit_cd ? htmlspecialchars($edit_cd['telefon'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg"><?php echo $edit_cd ? 'Salvează modificările' : 'Adaugă membru C.D.'; ?></button>
                        <?php if ($edit_cd): ?>
                        <a href="administrativ.php?tab=echipa" class="px-3 py-1.5 bg-slate-200 dark:bg-gray-600 hover:bg-slate-300 dark:hover:bg-gray-500 text-slate-800 dark:text-white text-sm rounded-lg">Renunță</a>
                        <?php endif; ?>
                    </div>
                </form>
                <ul class="divide-y divide-slate-200 dark:divide-gray-600 text-slate-900 dark:text-gray-100">
                    <?php foreach ($lista_cd as $m): ?>
                    <li class="py-2 flex justify-between gap-2">
                        <span><?php echo htmlspecialchars(trim(($m['membru_nume'] ?? $m['nume_manual']) . ' ' . ($m['membru_prenume'] ?? $m['prenume_manual']))); ?><?php if (!empty($m['functie'])): ?> – <?php echo htmlspecialchars($m['functie']); endif; ?></span>
                        <span class="flex gap-1 shrink-0">
                            <a href="administrativ.php?tab=echipa&amp;edit_cd=<?php echo (int)$m['id']; ?>" class="text-amber-600 dark:text-amber-400 text-xs hover:underline" aria-label="Editează membru C.D.">Editare</a>
                            <form method="post" class="inline" onsubmit="return confirm('Ștergeți?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="sterge_cd" value="1">
                                <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" class="text-red-600 dark:text-red-400 text-xs hover:underline">Șterge</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="titlu-ag">
                <h2 id="titlu-ag" class="text-lg font-semibold text-slate-900 dark:text-white mb-3"><?php echo $edit_ag ? 'Modifică membru A.G.' : 'Adunarea Generală'; ?></h2>
                <form method="post" action="administrativ.php?tab=echipa" class="space-y-2 mb-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="salveaza_ag" value="1">
                    <input type="hidden" name="id_ag" value="<?php echo $edit_ag ? (int)$edit_ag['id'] : 0; ?>">
                    <input type="hidden" name="membru_id" value="<?php echo $edit_ag ? (int)($edit_ag['membru_id'] ?? 0) : 0; ?>">
                    <input type="text" name="nume_manual" placeholder="Nume" value="<?php echo $edit_ag ? htmlspecialchars($edit_ag['nume_manual'] ?? $edit_ag['membru_nume'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="prenume_manual" placeholder="Prenume" value="<?php echo $edit_ag ? htmlspecialchars($edit_ag['prenume_manual'] ?? $edit_ag['membru_prenume'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="functie" placeholder="Funcție" value="<?php echo $edit_ag ? htmlspecialchars($edit_ag['functie'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="email" name="email" placeholder="Email" value="<?php echo $edit_ag ? htmlspecialchars($edit_ag['email'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <input type="text" name="telefon" placeholder="Telefon" value="<?php echo $edit_ag ? htmlspecialchars($edit_ag['telefon'] ?? '') : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5 text-sm">
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg"><?php echo $edit_ag ? 'Salvează modificările' : 'Adaugă membru A.G.'; ?></button>
                        <?php if ($edit_ag): ?>
                        <a href="administrativ.php?tab=echipa" class="px-3 py-1.5 bg-slate-200 dark:bg-gray-600 hover:bg-slate-300 dark:hover:bg-gray-500 text-slate-800 dark:text-white text-sm rounded-lg">Renunță</a>
                        <?php endif; ?>
                    </div>
                </form>
                <ul class="divide-y divide-slate-200 dark:divide-gray-600 text-slate-900 dark:text-gray-100">
                    <?php foreach ($lista_ag as $m): ?>
                    <li class="py-2 flex justify-between gap-2">
                        <span><?php echo htmlspecialchars(trim(($m['membru_nume'] ?? $m['nume_manual']) . ' ' . ($m['membru_prenume'] ?? $m['prenume_manual']))); ?><?php if (!empty($m['functie'])): ?> – <?php echo htmlspecialchars($m['functie']); endif; ?></span>
                        <span class="flex gap-1 shrink-0">
                            <a href="administrativ.php?tab=echipa&amp;edit_ag=<?php echo (int)$m['id']; ?>" class="text-amber-600 dark:text-amber-400 text-xs hover:underline" aria-label="Editează membru A.G.">Editare</a>
                            <form method="post" class="inline" onsubmit="return confirm('Ștergeți?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="sterge_ag" value="1">
                                <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" class="text-red-600 dark:text-red-400 text-xs hover:underline">Șterge</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
        <?php endif; ?>

        <!-- Tab Calendar administrativ -->
        <?php if ($tab === 'calendar'): ?>
        <div class="max-w-4xl">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Adaugă termen valabilitate</h2>
                <form method="post" action="administrativ.php?tab=calendar" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="salveaza_termen" value="1">
                    <input type="hidden" name="id_termen" value="0">
                    <div>
                        <label for="termen-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume document</label>
                        <input type="text" id="termen-nume" name="nume" required class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                    </div>
                    <div>
                        <label for="termen-tip" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip document</label>
                        <select id="termen-tip" name="tip_document" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            <?php foreach ($tipuri_doc as $val => $lbl): ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($lbl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="termen-inceput" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data început</label>
                        <input type="date" id="termen-inceput" name="data_inceput" required class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                    </div>
                    <div>
                        <label for="termen-expirare" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data expirare</label>
                        <input type="date" id="termen-expirare" name="data_expirarii" required class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label for="termen-observatii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Observații</label>
                        <input type="text" id="termen-observatii" name="observatii" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                    </div>
                    <div>
                        <label for="termen-angajat" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Angajat (opțional)</label>
                        <select id="termen-angajat" name="angajat_id" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            <option value="">—</option>
                            <?php foreach ($lista_angajati as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars(trim($a['nume'] . ' ' . $a['prenume'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează termen</button>
                    </div>
                </form>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Termene valabilitate</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-slate-300 dark:border-gray-600">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Nume</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Tip</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Expirare</th>
                                <th class="px-3 py-2 text-center text-slate-800 dark:text-gray-200">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                            <?php foreach ($lista_termene as $t): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50">
                                <td class="px-3 py-2"><?php echo htmlspecialchars($t['nume']); ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($tipuri_doc[$t['tip_document']] ?? $t['tip_document']); ?></td>
                                <td class="px-3 py-2"><?php echo date(DATE_FORMAT, strtotime($t['data_expirarii'])); ?></td>
                                <td class="px-3 py-2 text-center">
                                    <form method="post" class="inline" onsubmit="return confirm('Ștergeți termenul?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="sterge_termen" value="1">
                                        <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                        <button type="submit" class="text-red-600 dark:text-red-400 text-sm">Șterge</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lista_termene)): ?>
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500 dark:text-gray-400">Niciun termen înregistrat.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'cd'): ?>
        <!-- Tab Consiliul Director -->
        <div class="max-w-4xl">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Programează sedință Consiliul Director</h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-3">Sedința va apărea în Calendarul administrativ și în Calendarul de activități.</p>
                <form method="post" action="administrativ.php?tab=cd" class="flex flex-wrap gap-3 items-end">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_sedinta_cd" value="1">
                    <label class="block"><span class="text-sm text-slate-700 dark:text-gray-300">Data</span>
                        <input type="date" name="data_sedinta" required class="rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5"></label>
                    <label class="block"><span class="text-sm text-slate-700 dark:text-gray-300">Ora</span>
                        <input type="time" name="ora" value="09:00" class="rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5"></label>
                    <label class="block"><span class="text-sm text-slate-700 dark:text-gray-300">Loc</span>
                        <input type="text" name="loc" placeholder="Loc sedință" class="rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5"></label>
                    <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300"><input type="checkbox" name="creaza_activitate" value="1" checked> Creează activitate în calendar</label>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">Programează sedință</button>
                </form>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Sedințe programate</h2>
                <ul class="divide-y divide-slate-200 dark:divide-gray-600 text-slate-900 dark:text-gray-100">
                    <?php foreach ($lista_sedinte_cd as $s): ?>
                    <li class="py-2"><?php echo date(DATE_FORMAT, strtotime($s['data_sedinta'])); ?> – <?php echo date('H:i', strtotime($s['ora'])); ?><?php if ($s['loc']): ?> – <?php echo htmlspecialchars($s['loc']); endif; ?> <span class="text-slate-500 dark:text-gray-400">(<?php echo $s['stare']; ?>)</span></li>
                    <?php endforeach; ?>
                    <?php if (empty($lista_sedinte_cd)): ?>
                    <li class="text-slate-500 dark:text-gray-400 py-2">Nicio sedință programată.</li>
                    <?php endif; ?>
                </ul>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Generare documente C.D.</h2>
                <p class="text-sm text-slate-600 dark:text-gray-400">Convocator, Proces-verbal sedință, Listă prezență, Decizii – pot fi generate din <a href="lista-prezenta-create.php" class="text-amber-600 dark:text-amber-400 hover:underline">Liste prezență</a> și <a href="generare-documente.php" class="text-amber-600 dark:text-amber-400 hover:underline">Generare documente</a>.</p>
            </section>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'ag'): ?>
        <!-- Tab Adunarea Generală -->
        <div class="max-w-4xl">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Programează sedință Adunare Generală</h2>
                <form method="post" action="administrativ.php?tab=ag" class="flex flex-wrap gap-3 items-end">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_sedinta_ag" value="1">
                    <label class="block"><span class="text-sm text-slate-700 dark:text-gray-300">Data</span>
                        <input type="date" name="data_sedinta" required class="rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5"></label>
                    <label class="block"><span class="text-sm text-slate-700 dark:text-gray-300">Ora</span>
                        <input type="time" name="ora" value="09:00" class="rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5"></label>
                    <label class="block"><span class="text-sm text-slate-700 dark:text-gray-300">Loc</span>
                        <input type="text" name="loc" placeholder="Loc sedință" class="rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1.5"></label>
                    <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300"><input type="checkbox" name="creaza_activitate" value="1" checked> Creează activitate în calendar</label>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">Programează sedință</button>
                </form>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Sedințe A.G. programate</h2>
                <ul class="divide-y divide-slate-200 dark:divide-gray-600 text-slate-900 dark:text-gray-100">
                    <?php foreach ($lista_sedinte_ag as $s): ?>
                    <li class="py-2"><?php echo date(DATE_FORMAT, strtotime($s['data_sedinta'])); ?> – <?php echo date('H:i', strtotime($s['ora'])); ?><?php if ($s['loc']): ?> – <?php echo htmlspecialchars($s['loc']); endif; ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($lista_sedinte_ag)): ?>
                    <li class="text-slate-500 dark:text-gray-400 py-2">Nicio sedință programată.</li>
                    <?php endif; ?>
                </ul>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Generare documente A.G.</h2>
                <p class="text-sm text-slate-600 dark:text-gray-400">Convocator, Proces-verbal, Listă prezență, Hotărâri – <a href="lista-prezenta-create.php" class="text-amber-600 dark:text-amber-400 hover:underline">Liste prezență</a>, <a href="generare-documente.php" class="text-amber-600 dark:text-amber-400 hover:underline">Generare documente</a>.</p>
            </section>
        </div>
        <?php endif; ?>

        <!-- Tab Juridic ANR -->
        <?php if ($tab === 'juridic'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Adaugă informație Juridic ANR</h2>
                <form method="post" action="administrativ.php?tab=juridic" class="space-y-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_juridic" value="1">
                    <div>
                        <label for="juridic-subiect" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Subiect *</label>
                        <input type="text" id="juridic-subiect" name="subiect" required class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                    </div>
                    <div>
                        <label for="juridic-categorie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Categorie</label>
                        <select id="juridic-categorie" name="categorie" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            <?php foreach ($categorii_juridic as $val => $lbl): ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($lbl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="juridic-data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data document</label>
                            <input type="date" id="juridic-data" name="data_document" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        </div>
                        <div>
                            <label for="juridic-nr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr document</label>
                            <input type="text" id="juridic-nr" name="nr_document" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label for="juridic-continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Conținut</label>
                        <textarea id="juridic-continut" name="continut" rows="6" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2"></textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300"><input type="checkbox" name="creaza_task_todo" value="1" class="rounded border-slate-300 dark:border-gray-500 text-amber-600"> Creează task în Taskuri</label>
                    </div>
                    <div class="w-full">
                        <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300"><input type="checkbox" name="trimite_notificare_platforma" value="1" class="rounded border-slate-300 dark:border-gray-500 text-amber-600"> Trimite notificare în platformă pentru toți utilizatorii</label>
                    </div>
                    <div class="w-full">
                        <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300"><input type="checkbox" name="creaza_procedura_interna" value="1" class="rounded border-slate-300 dark:border-gray-500 text-amber-600"> Creează o procedură internă nouă (în tabul Proceduri interne)</label>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează</button>
                </form>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Istoric înregistrări</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-slate-300 dark:border-gray-600">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Subiect</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Categorie</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Data</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Nr. doc.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                            <?php foreach ($lista_juridic as $j): ?>
                            <tr>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($j['subiect']); ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($categorii_juridic[$j['categorie']] ?? $j['categorie']); ?></td>
                                <td class="px-3 py-2"><?php echo $j['data_document'] ? date(DATE_FORMAT, strtotime($j['data_document'])) : '—'; ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($j['nr_document'] ?? '—'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lista_juridic)): ?>
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500 dark:text-gray-400">Nicio înregistrare.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <?php endif; ?>

        <!-- Tab Parteneriate: listă stânga, formular dreapta -->
        <?php if ($tab === 'parteneriate'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Lista parteneriate</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-slate-300 dark:border-gray-600">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Partener</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Obiect</th>
                                <th class="px-3 py-2 text-left text-slate-800 dark:text-gray-200">Valabilitate</th>
                                <th class="px-3 py-2 text-center text-slate-800 dark:text-gray-200">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-600 bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                            <?php foreach ($lista_parteneriate as $p): ?>
                            <tr>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($p['nume_partener']); ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars(mb_substr($p['obiect_parteneriat'] ?? '', 0, 80)); ?><?php echo mb_strlen($p['obiect_parteneriat'] ?? '') > 80 ? '…' : ''; ?></td>
                                <td class="px-3 py-2"><?php echo $p['data_inceput'] ? date(DATE_FORMAT, strtotime($p['data_inceput'])) : '—'; ?> – <?php echo $p['data_sfarsit'] ? date(DATE_FORMAT, strtotime($p['data_sfarsit'])) : '—'; ?></td>
                                <td class="px-3 py-2 text-center">
                                    <form method="post" class="inline" onsubmit="return confirm('Ștergeți parteneriatul?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="sterge_parteneriat" value="1">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button type="submit" class="text-red-600 dark:text-red-400 text-sm">Șterge</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lista_parteneriate)): ?>
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500 dark:text-gray-400">Niciun parteneriat.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Adaugă parteneriat</h2>
                <form method="post" action="administrativ.php?tab=parteneriate" class="space-y-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="salveaza_parteneriat" value="1">
                    <input type="hidden" name="id_parteneriat" value="0">
                    <div>
                        <label for="part-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume partener *</label>
                        <input type="text" id="part-nume" name="nume_partener" required class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                    </div>
                    <div>
                        <label for="part-obiect" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Obiectul parteneriatului</label>
                        <textarea id="part-obiect" name="obiect_parteneriat" rows="2" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="part-inceput" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data început</label>
                            <input type="date" id="part-inceput" name="data_inceput" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        </div>
                        <div>
                            <label for="part-sfarsit" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data sfârșit (valabilitate)</label>
                            <input type="date" id="part-sfarsit" name="data_sfarsit" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează</button>
                </form>
            </section>
        </div>
        <?php endif; ?>

        <!-- Tab Proceduri interne -->
        <?php if ($tab === 'proceduri'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Căutare proceduri</h2>
                    <form method="get" action="administrativ.php" class="flex gap-2">
                        <input type="hidden" name="tab" value="proceduri">
                        <label for="cautare-proceduri" class="sr-only">Caută</label>
                        <input type="search" id="cautare-proceduri" name="cautare_proceduri" value="<?php echo htmlspecialchars($cautare_proceduri); ?>" placeholder="Caută în titlu sau conținut..." class="flex-1 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        <button type="submit" class="px-4 py-2 bg-slate-600 dark:bg-gray-600 hover:bg-slate-700 dark:hover:bg-gray-500 text-white rounded-lg">Caută</button>
                    </form>
                </section>
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3"><?php echo $edit_procedura ? 'Modifică procedură' : 'Adaugă procedură internă'; ?></h2>
                    <form method="post" action="administrativ.php?tab=proceduri<?php echo $cautare_proceduri !== '' ? '&cautare_proceduri=' . urlencode($cautare_proceduri) : ''; ?>" class="space-y-3">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="salveaza_procedura" value="1">
                        <input type="hidden" name="id_procedura" value="<?php echo $edit_procedura ? (int)$edit_procedura['id'] : 0; ?>">
                        <div>
                            <label for="proc-titlu" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Titlu *</label>
                            <input type="text" id="proc-titlu" name="titlu" required value="<?php echo $edit_procedura ? htmlspecialchars($edit_procedura['titlu']) : ''; ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                        </div>
                        <div>
                            <label for="proc-continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Conținut</label>
                            <textarea id="proc-continut" name="continut" rows="8" class="w-full rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2"><?php echo $edit_procedura ? htmlspecialchars($edit_procedura['continut'] ?? '') : ''; ?></textarea>
                        </div>
                        <?php if (!$edit_procedura): ?>
                        <div>
                            <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300"><input type="checkbox" name="trimite_notificare_procedura" value="1" class="rounded border-slate-300 dark:border-gray-500 text-amber-600"> Creează notificare în platformă pentru toți utilizatorii</label>
                        </div>
                        <?php endif; ?>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg"><?php echo $edit_procedura ? 'Actualizează' : 'Salvează procedură'; ?></button>
                            <?php if ($edit_procedura): ?>
                            <a href="administrativ.php?tab=proceduri<?php echo $cautare_proceduri !== '' ? '&cautare_proceduri=' . urlencode($cautare_proceduri) : ''; ?>" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>
            </div>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Proceduri interne</h2>
                <ul class="divide-y divide-slate-200 dark:divide-gray-600 text-slate-900 dark:text-gray-100">
                    <?php foreach ($lista_proceduri as $proc): ?>
                    <li class="py-3 flex justify-between items-start gap-4">
                        <div>
                            <strong><?php echo htmlspecialchars($proc['titlu']); ?></strong>
                            <?php if ($proc['continut']): ?>
                            <p class="text-sm text-slate-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars(mb_substr(strip_tags($proc['continut']), 0, 150)); ?>…</p>
                            <?php endif; ?>
                        </div>
                        <span class="flex gap-2 shrink-0">
                            <a href="administrativ.php?tab=proceduri&edit_procedura=<?php echo (int)$proc['id']; ?><?php echo $cautare_proceduri !== '' ? '&cautare_proceduri=' . urlencode($cautare_proceduri) : ''; ?>" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">Modificare</a>
                            <form method="post" class="inline" onsubmit="return confirm('Ștergeți procedura?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="sterge_procedura" value="1">
                                <input type="hidden" name="id" value="<?php echo (int)$proc['id']; ?>">
                                <button type="submit" class="text-red-600 dark:text-red-400 text-sm">Șterge</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($lista_proceduri)): ?>
                    <li class="text-slate-500 dark:text-gray-400 py-2">Nicio procedură. Folosiți formularul din stânga pentru adăugare.</li>
                    <?php endif; ?>
                </ul>
            </section>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require_once 'footer.php'; ?>
