<?php
/**
 * Controller: Registratura — Lista inregistrari cu paginare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/RegistraturaService.php';

$eroare_bd = '';
$per_page = (int)($_GET['per_page'] ?? 25);
if (!in_array($per_page, [10, 25, 50])) $per_page = 25;
$page = max(1, (int)($_GET['page'] ?? 1));

try {
    $data = registratura_list($pdo, $page, $per_page);
    $inregistrari = $data['inregistrari'];
    $total = $data['total'];
    $total_pages = $data['total_pages'];
} catch (Throwable $e) {
    $inregistrari = [];
    $total = 0;
    $total_pages = 1;
    $eroare_bd = 'Eroare la încărcarea înregistrărilor.';
}

$succes_msg = isset($_GET['succes']) ? 'Înregistrarea a fost actualizată cu succes.' : null;

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/registratura/index.php';
