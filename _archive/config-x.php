<?php
/**
 * Exemplu pentru mediu LOCAL (XAMPP).
 * Copiază acest fișier ca config.local.php și ajustează valorile.
 * config.local.php nu se încarcă pe server – pe server se folosește doar config.php cu baza de date de pe host.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'crm-anr-bihorxampp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Versiune platformă (primul update major = 2.0)
if (!defined('PLATFORM_VERSION')) {
    define('PLATFORM_VERSION', '2.0');
}
require_once __DIR__ . '/includes/platform_helper.php';
