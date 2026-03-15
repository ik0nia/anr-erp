<?php
/**
 * Controller: Setari — All settings tabs + POST actions
 *
 * Tabs: general, dashboard, email, cotizatii, incasari
 * Handles all POST actions, loads data, includes layout + view.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/SetariService.php';

$eroare = '';
$succes = '';
$lista_utilizatori = [];

// Ensure setari table exists once at the top
setari_ensure_table($pdo);

// ---------------------------------------------------------------------------
// Tab detection (needed early for some POST redirects)
// ---------------------------------------------------------------------------
$tab_setari = 'general';
$valid_tabs = ['general', 'dashboard', 'email', 'cotizatii', 'incasari'];
if (isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs)) {
    $tab_setari = $_GET['tab'];
}

// ---------------------------------------------------------------------------
// POST: Add user
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_utilizator']) && !empty($_SESSION['user_id']) && is_admin()) {
    csrf_require_valid();
    $result = setari_user_create($pdo, $_POST);
    if ($result['success']) {
        header('Location: /setari?succes_util=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Update logo
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_logo'])) {
    csrf_require_valid();
    $result = setari_update_logo($pdo, trim($_POST['logo_url'] ?? ''));
    if ($result['success']) {
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) . '&' : '?';
        header('Location: /setari' . $tab_redirect . 'succes_logo=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Upload antet asociatie
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['incarca_antet_asociatie'])) {
    csrf_require_valid();
    $result = setari_upload_antet($pdo, $_FILES['antet_docx'] ?? []);
    if ($result['success']) {
        header('Location: /setari?succes_antet=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Import Excel
// ---------------------------------------------------------------------------
$import_result = null;
$excel_data = null;
$mapare_coloane = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel'])) {
    csrf_require_valid();
    $res = setari_import_excel($pdo, $_FILES['fisier_excel'] ?? [], $_POST);
    if (!$res['success']) {
        $eroare = $res['error'];
    } else {
        if (!empty($res['succes_msg'])) $succes = $res['succes_msg'];
        if (!empty($res['error'])) $eroare = $res['error'];
        $excel_data = $res['excel_data'] ?? null;
        $mapare_coloane = $res['mapare_coloane'] ?? null;
        $import_result = $res['import_result'] ?? null;
    }
}

// ---------------------------------------------------------------------------
// POST: Cotizatii actions
// ---------------------------------------------------------------------------
if ($tab_setari === 'cotizatii' || isset($_POST['salveaza_cotizatie_anuala']) || isset($_POST['sterge_cotizatie_anuala']) || isset($_POST['adauga_scutire_cotizatie']) || isset($_POST['actualizeaza_scutire_cotizatie']) || isset($_POST['sterge_scutire_cotizatie'])) {
    cotizatii_ensure_tables($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_cotizatie_anuala'])) {
    csrf_require_valid();
    $tab_setari = 'cotizatii';
    $id = (int)($_POST['id_cotizatie_anuala'] ?? 0);
    $anul = (int)($_POST['anul'] ?? date('Y'));
    $grad = trim($_POST['grad_handicap'] ?? '');
    $asistent = trim($_POST['asistent_personal'] ?? '');
    $valoare = (float)str_replace(',', '.', $_POST['valoare_cotizatie'] ?? 0);
    $result = setari_cotizatie_anuala_save($pdo, $id, $anul, $grad, $asistent, $valoare);
    if ($result['success']) {
        header('Location: /setari?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_cotizatie_anuala'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_cotizatie_anuala'] ?? 0);
    $result = setari_cotizatie_anuala_delete($pdo, $id);
    if ($result['success']) {
        header('Location: /setari?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_scutire_cotizatie'])) {
    csrf_require_valid();
    $membru_id = (int)($_POST['membru_id_scutire'] ?? 0);
    $data_pana = trim($_POST['data_scutire_pana_la'] ?? '') ?: null;
    $permanenta = !empty($_POST['scutire_permanenta']);
    $motiv = trim($_POST['motiv_scutire'] ?? '');
    $result = setari_scutire_add($pdo, $membru_id, $data_pana, $permanenta, $motiv);
    if ($result['success']) {
        header('Location: /setari?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_scutire_cotizatie'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_scutire'] ?? 0);
    $data_pana = trim($_POST['data_scutire_pana_la'] ?? '') ?: null;
    $permanenta = !empty($_POST['scutire_permanenta']);
    $motiv = trim($_POST['motiv_scutire'] ?? '');
    $result = setari_scutire_update($pdo, $id, $data_pana, $permanenta, $motiv);
    if ($result['success']) {
        header('Location: /setari?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_scutire_cotizatie'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_scutire'] ?? 0);
    $result = setari_scutire_delete($pdo, $id);
    if ($result['success']) {
        header('Location: /setari?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}

// ---------------------------------------------------------------------------
// POST: Incasari actions
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_serii_incasari'])) {
    csrf_require_valid();
    $tab_setari = 'incasari';
    $result = setari_incasari_serii_save($pdo, $_POST);
    if ($result['success']) {
        header('Location: /setari?tab=incasari&succes_incasari=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_design_chitante'])) {
    csrf_require_valid();
    $tab_setari = 'incasari';
    $result = setari_incasari_design_save($pdo, $_POST);
    if ($result['success']) {
        header('Location: /setari?tab=incasari&succes_incasari=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_fgo_api'])) {
    csrf_require_valid();
    $tab_setari = 'incasari';
    $result = setari_incasari_fgo_save($pdo, $_POST);
    if ($result['success']) {
        header('Location: /setari?tab=incasari&succes_incasari=1');
        exit;
    }
}

// ---------------------------------------------------------------------------
// POST: Dashboard — subiecte registru v2
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_subiect_interactiune_v2'])) {
    csrf_require_valid();
    $result = setari_subiect_v2_add($pdo, trim($_POST['subiect_nou_v2'] ?? ''));
    if ($result['success']) {
        header('Location: /setari?tab=dashboard&succes_subiect_v2=1');
        exit;
    }
    $eroare = $result['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subiect_activ_v2'])) {
    csrf_require_valid();
    $id = (int)($_POST['subiect_id_v2'] ?? 0);
    $result = setari_subiect_v2_toggle($pdo, $id);
    if ($result['success']) {
        header('Location: /setari?tab=dashboard&succes_subiect_v2=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Email settings
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_setari_email'])) {
    csrf_require_valid();
    $result = setari_email_save($pdo, $_POST);
    if ($result['success']) {
        header('Location: /setari?tab=email&succes_email=1');
        exit;
    }
    $eroare = $result['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trimite_email_test'])) {
    csrf_require_valid();
    $result = setari_email_test($pdo, trim($_POST['email_test_destinatar'] ?? ''), $_SESSION['user_id'] ?? null);
    if ($result['success']) {
        $dest = urlencode($result['destinatar'] ?? '');
        header('Location: /setari?tab=email&succes_email_test=1&dest=' . $dest);
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Registratura settings
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_registratura'])) {
    csrf_require_valid();
    $nr = (int)($_POST['registratura_nr_pornire'] ?? 1);
    $result = setari_registratura_save($pdo, $nr);
    if ($result['success']) {
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) . '&' : '?';
        header('Location: /setari' . $tab_redirect . 'succes_registratura=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Platform name
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_nume_platforma'])) {
    csrf_require_valid();
    if (!is_admin()) {
        $eroare = 'Doar administratorii pot modifica numele platformei.';
    } else {
        $result = setari_update_platform_name($pdo, trim($_POST['nume_platforma'] ?? ''));
        if ($result['success']) {
            header('Location: /setari?succes_nume=1');
            exit;
        }
        $eroare = $result['error'];
    }
}

// ---------------------------------------------------------------------------
// POST: Newsletter settings
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_newsletter'])) {
    csrf_require_valid();
    $result = setari_newsletter_save($pdo, trim($_POST['newsletter_email'] ?? ''));
    if ($result['success']) {
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) . '&' : '?';
        header('Location: /setari' . $tab_redirect . 'succes_newsletter=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// POST: Documente settings
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_documente'])) {
    csrf_require_valid();
    $result = setari_documente_save($pdo, trim($_POST['email_asociatie'] ?? ''), trim($_POST['cale_libreoffice'] ?? ''));
    if ($result['success']) {
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) . '&' : '?';
        header('Location: /setari' . $tab_redirect . 'succes_documente=1');
        exit;
    }
    $eroare = $result['error'];
}

// ---------------------------------------------------------------------------
// Load user list (admin only)
// ---------------------------------------------------------------------------
if (!empty($_SESSION['user_id']) && is_admin()) {
    $lista_utilizatori = setari_users_list($pdo);
}

// ---------------------------------------------------------------------------
// Include layout
// ---------------------------------------------------------------------------
include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';

// ---------------------------------------------------------------------------
// Success messages from query params
// ---------------------------------------------------------------------------
if (isset($_GET['succes_util'])) $succes = 'Utilizatorul a fost creat. Un email de confirmare a fost trimis.';
if (isset($_GET['succes_nume'])) $succes = 'Numele platformei a fost actualizat.';
if (isset($_GET['succes_subiect_v2'])) $succes = 'Subiectul a fost salvat.';
if (isset($_GET['succes_cotizatii'])) $succes = 'Modificările cotizațiilor au fost salvate.';
if (isset($_GET['succes_incasari'])) $succes = 'Setările modulului Încasări au fost salvate.';
if (isset($_GET['succes_antet'])) $succes = 'Antetul asociației a fost încărcat.';
if (isset($_GET['succes_logo'])) $succes = 'Logo-ul a fost actualizat cu succes.';
if (isset($_GET['succes_registratura'])) $succes = 'Setările Registraturii au fost salvate.';
if (isset($_GET['succes_newsletter'])) $succes = 'Setările Newsletter au fost salvate.';
if (isset($_GET['succes_documente'])) $succes = 'Setările pentru generare documente au fost salvate.';
if (isset($_GET['succes_email'])) $succes = 'Setările email au fost salvate.';
if (isset($_GET['succes_email_test'])) $succes = 'Emailul de test a fost trimis cu succes la ' . htmlspecialchars(urldecode($_GET['dest'] ?? 'adresa configurată')) . '.';

// ---------------------------------------------------------------------------
// Load tab-specific data for the view
// ---------------------------------------------------------------------------
$general = setari_load_general($pdo);
$logo_url_actual = $general['logo_url_actual'];
$nume_platforma_actual = $general['nume_platforma_actual'];
$email_asociatie = $general['email_asociatie'];
$cale_libreoffice = $general['cale_libreoffice'];
$registratura_nr_pornire = $general['registratura_nr_pornire'];
$newsletter_email = $general['newsletter_email'];
$antet_asociatie_docx = $general['antet_asociatie_docx'];

$subiecte_dashboard_v2 = [];
if ($tab_setari === 'dashboard') {
    ensure_registru_v2_tables($pdo);
    $subiecte_dashboard_v2 = get_subiecte_interactiuni_v2_toate($pdo);
}

$settings_email = [];
if ($tab_setari === 'email') {
    $settings_email = setari_email_config($pdo);
}

$lista_cotizatii_anuale = [];
$lista_scutiri_cotizatii = [];
$edit_cotizatie_anuala = null;
$edit_scutire_cotizatie = null;
$graduri_handicap = cotizatii_graduri_handicap($pdo);
$asistent_personal_opts = cotizatii_asistent_personal_lista($pdo);

if ($tab_setari === 'cotizatii') {
    $lista_cotizatii_anuale = cotizatii_lista_anuale($pdo);
    $lista_scutiri_cotizatii = cotizatii_lista_scutiri($pdo);
    if (isset($_GET['edit_cotizatie']) && (int)$_GET['edit_cotizatie'] > 0) {
        $edit_cotizatie_anuala = cotizatii_get_anuala($pdo, (int)$_GET['edit_cotizatie']);
    }
    if (isset($_GET['edit_scutire']) && (int)$_GET['edit_scutire'] > 0) {
        $edit_scutire_cotizatie = cotizatii_get_scutire($pdo, (int)$_GET['edit_scutire']);
    }
}

$incasari_serie_donatii = null;
$incasari_serie_incasari = null;
$incasari_setari_design = [];
$lista_donatii_incasate = [];
if ($tab_setari === 'incasari') {
    $incasari_data = setari_incasari_load($pdo);
    $incasari_serie_donatii = $incasari_data['serie_donatii'];
    $incasari_serie_incasari = $incasari_data['serie_incasari'];
    $lista_donatii_incasate = $incasari_data['donatii'];
    $incasari_setari_design = $incasari_data['design'];
}

// ---------------------------------------------------------------------------
// Render the view
// ---------------------------------------------------------------------------
include APP_ROOT . '/app/views/setari/index.php';
