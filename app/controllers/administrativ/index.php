<?php
/**
 * Controller: Modul Administrativ
 * Achizitii, Echipa, Calendar, Consiliul Director, Adunarea Generala,
 * Juridic ANR, Parteneriate, Proceduri interne.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/AdministrativService.php';

$eroare = '';
$succes = '';
$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
$user_id = $_SESSION['user_id'] ?? null;

// --- Tab selection ---
$valid_tabs = administrativ_valid_tabs();
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'achizitii';

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();
    $result = null;

    // Achizitii
    if (isset($_POST['adauga_achizitie'])) {
        $result = administrativ_service_adauga_achizitie($pdo, $_POST, $user_id, $utilizator);
    } elseif (isset($_POST['marcheaza_cumparat'])) {
        $result = administrativ_service_marcheaza_cumparat($pdo, (int)($_POST['id'] ?? 0));
    } elseif (isset($_POST['actualizeaza_status_achizitie'])) {
        $result = administrativ_service_status_achizitie($pdo, (int)($_POST['id'] ?? 0), (string)($_POST['status_achizitie'] ?? ''));
    } elseif (isset($_POST['sterge_achizitie'])) {
        $result = administrativ_service_sterge_achizitie($pdo, (int)($_POST['id'] ?? 0));
    }
    // Echipa: angajati
    elseif (isset($_POST['salveaza_angajat'])) {
        $result = administrativ_service_salveaza_angajat($pdo, $_POST);
    } elseif (isset($_POST['sterge_angajat'])) {
        $result = administrativ_service_sterge_angajat($pdo, (int)($_POST['id'] ?? 0));
    }
    // CD / AG nomenclator
    elseif (isset($_POST['salveaza_cd'])) {
        $result = administrativ_service_salveaza_cd($pdo, $_POST);
    } elseif (isset($_POST['sterge_cd'])) {
        $result = administrativ_service_sterge_cd($pdo, (int)($_POST['id'] ?? 0));
    } elseif (isset($_POST['salveaza_ag'])) {
        $result = administrativ_service_salveaza_ag($pdo, $_POST);
    } elseif (isset($_POST['sterge_ag'])) {
        $result = administrativ_service_sterge_ag($pdo, (int)($_POST['id'] ?? 0));
    }
    // Calendar termene
    elseif (isset($_POST['salveaza_termen'])) {
        $result = administrativ_service_salveaza_termen($pdo, $_POST);
    } elseif (isset($_POST['sterge_termen'])) {
        $result = administrativ_service_sterge_termen($pdo, (int)($_POST['id'] ?? 0));
    }
    // Sedinte CD / AG
    elseif (isset($_POST['adauga_sedinta_cd'])) {
        $result = administrativ_service_adauga_sedinta_cd($pdo, $_POST);
    } elseif (isset($_POST['adauga_sedinta_ag'])) {
        $result = administrativ_service_adauga_sedinta_ag($pdo, $_POST);
    }
    // Juridic ANR
    elseif (isset($_POST['adauga_juridic'])) {
        $result = administrativ_service_adauga_juridic($pdo, $_POST, $user_id);
    }
    // Parteneriate
    elseif (isset($_POST['salveaza_parteneriat'])) {
        $result = administrativ_service_salveaza_parteneriat($pdo, $_POST);
    } elseif (isset($_POST['sterge_parteneriat'])) {
        $result = administrativ_service_sterge_parteneriat($pdo, (int)($_POST['id'] ?? 0));
    }
    // Proceduri
    elseif (isset($_POST['salveaza_procedura'])) {
        $result = administrativ_service_salveaza_procedura($pdo, $_POST, $user_id);
    } elseif (isset($_POST['sterge_procedura'])) {
        $result = administrativ_service_sterge_procedura($pdo, (int)($_POST['id'] ?? 0));
    }

    if ($result && !empty($result['redirect'])) {
        header('Location: ' . $result['redirect']);
        exit;
    }
}

// --- Prepare view data ---
$pageData = administrativ_service_load_page_data($pdo);
extract($pageData);

// Success messages from redirect
if (isset($_GET['succes'])) {
    $messages = administrativ_success_messages();
    $succes = $messages[$_GET['succes']] ?? 'Salvare reusita.';
}

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/administrativ/index.php';
