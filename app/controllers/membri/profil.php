<?php
/**
 * Controller: Membri — Profil membru (vizualizare/editare)
 *
 * GET: Afiseaza profilul membrului
 * POST actualizeaza_membru: Salveaza modificarile
 * POST marcheaza_alert_informat: Toggle alert informat
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/MembriService.php';
require_once APP_ROOT . '/includes/liste_helper.php';

$eroare = '';
$succes = '';
$membru_id = (int)($_GET['id'] ?? 0);

if ($membru_id <= 0) {
    header('Location: /membri');
    exit;
}

// --- POST: Upload atasament ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_atasament'])) {
    csrf_require_valid();
    $tip_atasament = $_POST['tip_atasament'] ?? 'certificat_handicap';
    $nota_atasament = trim($_POST['nota_atasament'] ?? '');
    $uploaded_by = $_SESSION['utilizator'] ?? 'Sistem';

    if (isset($_FILES['fisier_atasament']) && $_FILES['fisier_atasament']['error'] === UPLOAD_ERR_OK) {
        $result = membri_atasament_adauga($pdo, $membru_id, $tip_atasament, $_FILES['fisier_atasament'], $nota_atasament, $uploaded_by);
        if ($result['success']) {
            header('Location: /membru-profil?id=' . $membru_id . '&succes=1');
            exit;
        } else {
            $eroare = $result['error'];
        }
    } else {
        $eroare = 'Nu a fost selectat niciun fisier pentru incarcare.';
    }
}

// --- POST: Stergere atasament ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_atasament'])) {
    csrf_require_valid();
    $atasament_id = (int)($_POST['atasament_id'] ?? 0);
    if ($atasament_id > 0) {
        $deleted = membri_atasament_sterge($pdo, $atasament_id);
        if ($deleted) {
            header('Location: /membru-profil?id=' . $membru_id . '&succes=1');
            exit;
        } else {
            $eroare = 'Eroare la stergerea atasamentului.';
        }
    }
}

// --- POST: Marcare/debifare alert informat ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcheaza_alert_informat'])) {
    csrf_require_valid();
    $alert_tip = trim($_POST['alert_tip'] ?? '');
    $membru_id_alert = (int)($_POST['membru_id'] ?? 0);
    $debifa = empty($_POST['marcat_informat']);

    if ($membru_id_alert > 0 && in_array($alert_tip, ['ci', 'ch', 'cotizatie'])) {
        $result = membri_toggle_alert_informat($pdo, $membru_id_alert, $alert_tip, $debifa);
        if ($result['success']) {
            header('Location: /membru-profil?id=' . $membru_id_alert);
            exit;
        } else {
            $eroare = $result['error'];
        }
    }
}

// --- POST: Actualizare membru ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_membru'])) {
    csrf_require_valid();
    $_POST['membru_id'] = $membru_id;

    try {
        $result = membri_update($pdo, $membru_id, $_POST, $_FILES);

        if ($result['success']) {
            header('Location: /membru-profil?id=' . $membru_id . '&succes=1');
            exit;
        } else {
            $eroare = $result['error'] ?? 'Eroare necunoscuta la salvare';
        }
    } catch (Exception $e) {
        $eroare = 'Eroare neasteptata: ' . $e->getMessage();
    }
}

// Daca am fost pe POST (salvare) dar nu avem nici eroare nici redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_membru']) && $eroare === '' && !isset($_GET['succes'])) {
    $eroare = 'Salvarea nu a reusit. Verificati datele (nume, prenume, CNP, email) sau contactati administratorul.';
}

// --- Incarcare date membru ---
$membru = membri_get($pdo, $membru_id);
if (!$membru) {
    header('Location: /membri');
    exit;
}

// Cotizatie info
$cot_info = membri_cotizatie_info($pdo, $membru_id, $membru);
$scutire_cotizatie = $cot_info['scutire_cotizatie'];
$cotizatie_achitata_an_curent = $cot_info['cotizatie_achitata_an_curent'];
$valoare_cotizatie_an = $cot_info['valoare_cotizatie_an'];

// Afisare mesaj succes
if (isset($_GET['succes']) && $_GET['succes'] == '1') {
    $succes = 'Datele membrului au fost actualizate cu succes.';
}

$varsta = calculeaza_varsta($membru['datanastere'] ?? null);

// Istoric modificari
$istoric_modificari = membri_istoric($pdo, $membru_id, $membru);

// Alerte membru
$alerts = membri_alerts($pdo, $membru_id);

// Incasari
$lista_incasari = incasari_lista_membru($pdo, $membru_id);
$tipuri_afisare = incasari_tipuri_afisare();
$moduri_plata_afisare = incasari_moduri_plata_afisare();

// Jurnal activitate
$jurnal = membri_jurnal_activitate($pdo, $membru_id);

// Atasamente
$atasamente_ch = membri_atasamente_lista($pdo, $membru_id, 'certificat_handicap');
$atasamente_ci = membri_atasamente_lista($pdo, $membru_id, 'act_identitate');
$atasamente_alt = membri_atasamente_lista($pdo, $membru_id, 'alt_document');

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/membri/profil.php';
