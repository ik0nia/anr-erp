<?php
/**
 * API endpoint pentru statistici Registru Interacțiuni v2
 * Returnează JSON cu numărul de interacțiuni pentru ziua curentă
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/registru_interactiuni_v2_helper.php';

header('Content-Type: application/json');

ensure_registru_v2_tables($pdo);
$interactiuni_azi = registru_v2_interactiuni_azi($pdo);

echo json_encode([
    'apel' => $interactiuni_azi['apel'],
    'vizita' => $interactiuni_azi['vizita']
]);
