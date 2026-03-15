<?php
/**
 * Controller: Voluntariat — Nomenclator, Activități, Registru
 *
 * GET: Afișează modulul cu 3 taburi
 * POST: Procesează acțiunile (mesaj, template, voluntar, activitate, participant)
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/VoluntariatService.php';

require_login();
voluntariat_ensure_tables($pdo);

// Log accesare din dashboard
if (isset($_GET['from']) && $_GET['from'] === 'dashboard') {
    log_activitate($pdo, 'Voluntariat: Accesare formular adăugare activitate din dashboard');
}

$eroare = '';
$succes = '';
$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['nomenclator', 'activitati', 'registru'], true) ? $_GET['tab'] : 'nomenclator';

// --- POST: Acțiuni ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();

    if (isset($_POST['salveaza_mesaj_voluntari'])) {
        voluntariat_salveaza_mesaj($pdo, $_POST['mesaj_voluntari'] ?? '', $tab);
    }

    if (isset($_POST['sterge_mesaj_voluntari'])) {
        voluntariat_sterge_mesaj($pdo, $tab);
    }

    if (isset($_POST['setare_template_contract'])) {
        voluntariat_seteaza_template($pdo, (int)($_POST['template_contract_id'] ?? 0));
    }

    if (isset($_POST['adauga_voluntar'])) {
        $rez = voluntariat_store_voluntar($pdo, $_POST);
        if ($rez['success']) {
            header('Location: voluntariat.php?tab=nomenclator&succes=voluntar');
            exit;
        }
        $eroare = $rez['error'] ?? 'Eroare la salvare.';
    }

    if (isset($_POST['actualizeaza_voluntar'])) {
        $rez = voluntariat_update_voluntar($pdo, (int)($_POST['voluntar_id'] ?? 0), $_POST);
        if ($rez['success']) {
            header('Location: voluntariat.php?tab=nomenclator&succes=actualizat');
            exit;
        }
        $eroare = $rez['error'] ?? 'Eroare la actualizare.';
    }

    if (isset($_POST['adauga_activitate'])) {
        $rez = voluntariat_store_activitate(
            $pdo,
            $_POST['nume_activitate'] ?? '',
            $_POST['data_activitate'] ?? '',
            trim($_POST['ora_inceput'] ?? '') ?: null,
            trim($_POST['ora_sfarsit'] ?? '') ?: null
        );
        if ($rez['success']) {
            header('Location: voluntariat.php?tab=activitati&succes=activitate');
            exit;
        }
        $eroare = $rez['error'] ?? 'Eroare la salvare.';
    }

    if (isset($_POST['adauga_participant'])) {
        $ore = trim($_POST['ore_prestate'] ?? '');
        $rez = voluntariat_store_participant(
            $pdo,
            (int)($_POST['activitate_id'] ?? 0),
            (int)($_POST['voluntar_id'] ?? 0),
            $ore !== '' ? (float)$ore : null
        );
        if ($rez['success']) {
            header('Location: voluntariat.php?tab=activitati&succes=participant');
            exit;
        }
        $eroare = $rez['error'] ?? 'Selectați activitatea și voluntarul.';
    }
}

// --- GET: Mesaj succes ---
if (isset($_GET['succes'])) {
    $mesaje = voluntariat_mesaje_succes();
    $succes = $mesaje[$_GET['succes']] ?? '';
}

// --- Date pentru view ---
$data = voluntariat_load_data($pdo);
$mesaj_zilei = $data['mesaj_zilei'];
$lista_voluntari = $data['lista_voluntari'];
$lista_activitati = $data['lista_activitati'];
$registru_ore = $data['registru_ore'];
$templates_doc = $data['templates_doc'];
$template_contract_id = $data['template_contract_id'];

// Voluntar pentru editare (modal)
$editVol = null;
if ($tab === 'nomenclator' && !empty($_GET['editeaza'])) {
    $editId = (int)$_GET['editeaza'];
    $editVol = $editId > 0 ? voluntariat_get_voluntar($pdo, $editId) : null;
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/voluntariat/index.php';
