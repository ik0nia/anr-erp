<?php
/**
 * Descarcă atașament notificare (doar pentru utilizatori autentificați cu acces la notificare)
 */
require_once __DIR__ . '/config.php';
require_once 'includes/notificari_helper.php';

$id = (int)($_GET['id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($id <= 0 || $user_id <= 0) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acces interzis.');
}

$notif = notificari_get_by_id($pdo, $id, $user_id);
if (!$notif || empty($notif['atasament_path']) || empty($notif['atasament_nume'])) {
    header('HTTP/1.1 404 Not Found');
    exit('Atașament inexistent.');
}

$path = realpath($notif['atasament_path']);
$base_dir = realpath(__DIR__ . '/uploads/notificari/');
if (!$path || !$base_dir || strpos($path, $base_dir) !== 0 || !is_file($path)) {
    header('HTTP/1.1 404 Not Found');
    exit('Fișierul nu a fost găsit.');
}

$mime = mime_content_type($path);
if (!$mime) {
    $mime = 'application/octet-stream';
}
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($notif['atasament_nume']) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, must-revalidate');
readfile($path);
exit;
