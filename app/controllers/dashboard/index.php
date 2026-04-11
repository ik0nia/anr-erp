<?php
/**
 * Controller: Dashboard — Pagina principala
 *
 * GET: Afiseaza dashboard-ul cu taskuri, interactiuni, cautare membri, librarie
 * POST finalizeaza_task: Finalizeaza un task si redirecteaza
 * POST adauga_interactiune_v2: Adauga interactiune si redirecteaza
 */
ob_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/DashboardService.php';

$eroare = '';
$succes = '';
$eroare_bd = '';

// --- POST: Finalizare task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizeaza_task'])) {
    csrf_require_valid();
    $id = (int) ($_POST['task_id'] ?? 0);
    if ($id > 0) {
        try {
            $result = dashboard_finalize_task($pdo, $id);
            if ($result['success']) {
                $redirect_url = '/dashboard?succes=1';
                if (function_exists('ob_get_level')) {
                    while (ob_get_level()) { ob_end_clean(); }
                }
                if (!headers_sent()) {
                    header('Location: ' . $redirect_url);
                    exit;
                }
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><p>Redirectionare...</p><script>location.replace(' . json_encode($redirect_url) . ');</script></body></html>';
                exit;
            } else {
                $eroare = $result['error'] ?? 'Eroare la finalizare.';
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la finalizare.';
        }
    }
}

// --- POST: Adauga interactiune v2 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_interactiune_v2'])) {
    csrf_require_valid();
    $user = $_SESSION['utilizator'] ?? 'Utilizator';
    $user_id = $_SESSION['user_id'] ?? null;

    try {
        $result = dashboard_add_interactiune_v2($pdo, $_POST, $user, $user_id);
        if ($result['success']) {
            $redirect_url = '/dashboard?succes_interact_v2=1';
            while (ob_get_level()) { ob_end_clean(); }
            if (!headers_sent()) {
                header('Location: ' . $redirect_url);
                exit;
            }
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><p>Redirectionare...</p><script>location.replace(' . json_encode($redirect_url) . ');</script></body></html>';
            exit;
        } else {
            $eroare = $result['error'] ?? 'Eroare la salvare.';
        }
    } catch (PDOException $e) {
        $eroare = 'Eroare la salvare: ' . $e->getMessage();
    }
}

// --- POST: Adauga task rapid (din modal) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_task_rapid'])) {
    csrf_require_valid();
    $task_nume = trim($_POST['task_nume'] ?? '');
    $task_data = trim($_POST['task_data'] ?? date('Y-m-d H:i:s'));
    $task_detalii = trim($_POST['task_detalii'] ?? '');
    $task_urgenta = in_array($_POST['task_urgenta'] ?? '', ['normal', 'important']) ? $_POST['task_urgenta'] : 'normal';

    if ($task_nume !== '') {
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$task_nume, $task_data, $task_detalii ?: null, $task_urgenta, $user_id]);
            log_activitate($pdo, 'Task creat: ' . $task_nume);
            while (ob_get_level()) { ob_end_clean(); }
            header('Location: /dashboard?succes_task=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la crearea taskului: ' . $e->getMessage();
        }
    } else {
        $eroare = 'Numele taskului este obligatoriu.';
    }
}

// --- GET: Incarca date ---
$taskuri_active = [];
$taskuri_istoric_count = 0;
$membri_cu_avertizari = 0;
$ci_de_actualizat = 0;
$ch_de_actualizat = 0;
$aniversari_azi_count = 0;
$tickete_deschise_count = 0;

if (isset($_GET['succes'])) {
    $succes = $_GET['succes'] == '4' ? 'Taskul a fost actualizat cu succes.' : 'Taskul a fost marcat ca finalizat.';
}
if (isset($_GET['succes_task'])) {
    $succes = 'Taskul a fost adaugat cu succes.';
}
if (isset($_GET['succes_interact_v2'])) {
    $succes = 'Interactiunea a fost inregistrata cu succes.';
}

try {
    $user_id = $_SESSION['user_id'] ?? null;
    $task_data = dashboard_load_tasks($pdo, $user_id);
    $taskuri_active = $task_data['taskuri_active'];
    $taskuri_istoric_count = $task_data['taskuri_istoric_count'];

    $stats = dashboard_load_stats($pdo);
    $membri_cu_avertizari = $stats['membri_cu_avertizari'];
    $ci_de_actualizat = $stats['ci_de_actualizat'] ?? 0;
    $ch_de_actualizat = $stats['ch_de_actualizat'] ?? 0;
    $aniversari_azi_count = dashboard_count_aniversari_azi($pdo);
    try {
        require_once APP_ROOT . '/includes/tickete_helper.php';
        tickete_ensure_tables($pdo);
        $tickete_deschise_count = tickete_count_deschise($pdo);
    } catch (Throwable $e2) {}
} catch (PDOException $e) {
    $eroare_bd = 'Tabelul taskuri nu exista. Rulati schema.sql sau schema_taskuri.sql in baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '.';
    $taskuri_istoric_count = 0;
    $membri_cu_avertizari = 0;
}

ensure_registru_v2_tables($pdo);
librarie_documente_ensure_tables($pdo);
$librarie_cautare = trim($_GET['cautare_librarie'] ?? '');
$librarie_lista = librarie_documente_lista($pdo, $librarie_cautare);
if ($librarie_cautare === '' && count($librarie_lista) > 7) {
    $librarie_lista = array_slice($librarie_lista, 0, 7);
}
$subiecte_interactiuni_v2 = get_subiecte_interactiuni_v2($pdo);
$interactiuni_v2_azi = dashboard_load_interactiuni_azi($pdo);

// Cautare membru
$cautare_membru = trim($_GET['cautare_membru'] ?? '');
$rezultate_membri = [];
$eroare_cautare_membri = '';
if (!empty($cautare_membru)) {
    try {
        $rezultate_membri = dashboard_search_membri($pdo, $cautare_membru);
    } catch (PDOException $e) {
        $eroare_cautare_membri = 'Eroare la cautare.';
    }
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/dashboard/index.php';
