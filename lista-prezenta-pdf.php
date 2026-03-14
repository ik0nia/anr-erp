<?php
/**
 * Descarcă listă prezență - deschide versiunea tipăribilă (Putem salva ca PDF din browser: Ctrl+P -> Salvează ca PDF)
 */
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: activitati.php'); exit; }
header('Location: lista-prezenta-print.php?id=' . $id);
exit;
