<?php
/**
 * Controller: Notificari — Lista + Adaugare + Marcheaza necitit
 *
 * GET: Afiseaza lista notificari cu formular adaugare
 * POST adauga_notificare: Adauga notificare noua si redirecteaza
 * POST marcheaza_necitit: Marcheaza notificarea ca necitita si redirecteaza
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/NotificariService.php';

$eroare = '';
$succes = '';
notificari_ensure_tables($pdo);

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: /login');
    exit;
}

$ordine = isset($_GET['ordine']) && $_GET['ordine'] === 'z-a' ? 'z-a' : 'a-z';
$istoric = isset($_GET['istoric']) && $_GET['istoric'] === '1';
$utilizatori_activi = notificari_lista_utilizatori_activi($pdo);

// --- POST: Marcheaza necitit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcheaza_necitit'])) {
    csrf_require_valid();
    $notif_id = (int)($_POST['notif_id'] ?? 0);
    if ($notif_id > 0) {
        notificari_marcheaza_necitita($pdo, $notif_id, $user_id);
        log_activitate($pdo, "Notificare marcată ca necitită: ID {$notif_id}");
        header('Location: /notificari?necitit=1');
        exit;
    }
}

// --- POST: Adauga notificare ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_notificare'])) {
    csrf_require_valid();
    $titlu = trim($_POST['titlu'] ?? '');
    $importanta = in_array($_POST['importanta'] ?? '', array_keys(NOTIFICARI_IMPORTANTE)) ? $_POST['importanta'] : 'Normal';
    $continut = trim($_POST['continut'] ?? '');
    $link_extern = trim($_POST['link_extern'] ?? '');
    $trimite_email = isset($_POST['trimite_email']) ? 1 : 0;
    $target_selector = trim((string)($_POST['notificare_pentru'] ?? ''));
    if ($target_selector === '') {
        // Backward compatibility for older form payload.
        $legacy_scope = (($_POST['target_scope'] ?? 'all') === 'user') ? 'user' : 'all';
        $legacy_user_id = (int)($_POST['target_user_id'] ?? 0);
        $target_selector = $legacy_scope === 'user' ? (string)$legacy_user_id : 'all';
    }
    $target_scope = 'all';
    $target_user_id = 0;
    if ($target_selector !== 'all') {
        if (!ctype_digit($target_selector) || (int)$target_selector <= 0) {
            $eroare = 'Selectați un utilizator valid pentru notificarea țintită.';
        } else {
            $target_scope = 'user';
            $target_user_id = (int)$target_selector;
        }
    }
    if (empty($titlu)) {
        $eroare = 'Titlul notificării este obligatoriu.';
    } elseif (empty($eroare)) {
        $fisier = isset($_FILES['atasament']) && $_FILES['atasament']['error'] === UPLOAD_ERR_OK ? $_FILES['atasament'] : null;
        if ($fisier && $fisier['size'] > NOTIFICARI_ATAŞAMENT_MAX_MB * 1024 * 1024) {
            $eroare = 'Atașamentul depășește ' . NOTIFICARI_ATAŞAMENT_MAX_MB . ' MB.';
        } else {
            $id = notificari_adauga($pdo, [
                'titlu' => $titlu,
                'importanta' => $importanta,
                'continut' => $continut,
                'link_extern' => $link_extern,
                'trimite_email' => $trimite_email,
                'target_scope' => $target_scope,
                'target_user_id' => $target_user_id,
            ], $fisier, $user_id);
            if ($id > 0) {
                log_activitate($pdo, "Notificare adăugată: {$titlu}");
                $succes = 'Notificarea a fost salvată.' . ($trimite_email ? ' Emailurile au fost trimise utilizatorilor.' : '');
                header('Location: /notificari?succes=1');
                exit;
            } else {
                $eroare = 'Eroare la salvare sau la încărcarea atașamentului.';
            }
        }
    }
}

// --- GET: Date pentru view ---
$lista = notificari_lista_pentru_utilizator($pdo, $user_id, $ordine, $istoric);

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/notificari/index.php';
