<?php
/**
 * Controller: Aniversari — Afiseaza aniversarile zilei (membri si contacte)
 *
 * GET: Lista aniversari + calendar lunar
 * POST mesaj_azi: Salveaza mesajul de azi in sesiune
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/membri_alerts.php';
require_once APP_ROOT . '/app/services/AniversariService.php';

ensure_contacte_table($pdo);

// --- POST: Salvare mesaj de azi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mesaj_azi'])) {
    csrf_require_valid();
    $_SESSION['aniversari_mesaj_azi'] = trim((string)$_POST['mesaj_azi']);
    header('Location: /aniversari');
    exit;
}

$mesaj_azi = isset($_SESSION['aniversari_mesaj_azi']) ? (string)$_SESSION['aniversari_mesaj_azi'] : '';

// --- Date pentru view ---
$aniversari_membri = aniversari_membri_azi($pdo);
$aniversari_contacte = aniversari_contacte_azi($pdo);
$aniversari_per_zi = aniversari_per_zi_luna($pdo);
$cal = aniversari_calendar_data();

$luna_curenta = $cal['luna_curenta'];
$anul_curent = $cal['anul_curent'];
$zi_azi = $cal['zi_azi'];
$zile_in_luna = $cal['zile_in_luna'];
$prima_zi_luna = $cal['prima_zi_luna'];
$luni_ro = $cal['luni_ro'];
$zile_sapt = $cal['zile_sapt'];

$subiect_email_aniversari = 'Echipa Asociatiei Nevazatorilor Bihor va ureaza La Multi Ani!';

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/aniversari/index.php';
