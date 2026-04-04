<?php
/**
 * Endpoint securizat pentru livrarea template-ului PDF către mapper.
 */
require_once __DIR__ . '/../config.php';
require_once APP_ROOT . '/app/services/FundraisingService.php';

require_login();
fundraising_f230_ensure_schema($pdo);

$template_abs = fundraising_f230_template_abs($pdo);
if ($template_abs === '' || !is_file($template_abs)) {
    http_response_code(404);
    echo 'Template indisponibil.';
    exit;
}
$template_sha = fundraising_f230_template_sha256($template_abs);
$requested_sha = strtolower(trim((string)($_GET['t'] ?? '')));
if ($requested_sha !== '' && $requested_sha !== strtolower($template_sha)) {
    http_response_code(409);
    echo 'Template schimbat. Reîncarcă mapper-ul.';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="fundraising-template-230.pdf"');
header('Cache-Control: private, max-age=30');
header('X-Template-Sha256: ' . $template_sha);
header('Content-Length: ' . (string)filesize($template_abs));
readfile($template_abs);
exit;
