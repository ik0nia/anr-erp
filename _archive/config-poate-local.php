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

// Nume/platformă și setări generale (fallback-urile pot fi suprascrise din BD)
if (!defined('PLATFORM_NAME')) {
    define('PLATFORM_NAME', 'ERP ANR BIHOR');
}
if (!defined('PLATFORM_LOGO_URL')) {
    define('PLATFORM_LOGO_URL', 'https://anrbihor.ro/wp-content/uploads/2023/08/logo-anr-site-test1.png');
}
if (!defined('PLATFORM_BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (strpos($base, '/crm-anr-bihor') !== false) {
        $base = preg_replace('#/crm-anr-bihor.*$#', '/crm-anr-bihor', $base);
    }
    define('PLATFORM_BASE_URL', $protocol . '://' . $host . $base);
}

// Formate dată/oră
if (!defined('DATE_FORMAT')) {
    define('DATE_FORMAT', 'd.m.Y');
}
if (!defined('TIME_FORMAT')) {
    define('TIME_FORMAT', 'H:i');
}
if (!defined('DATETIME_FORMAT')) {
    define('DATETIME_FORMAT', 'd.m.Y H:i');
}

// CSRF & alte setări globale
if (!defined('CRON_NEWSLETTER_KEY')) {
    define('CRON_NEWSLETTER_KEY', '');
}
if (!defined('CSRF_ENABLED')) {
    define('CSRF_ENABLED', true);
}

// Conexiune PDO globală ($pdo) – folosită în toate modulele (notificări, auth, etc.)
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Eroare conexiune bază de date: ' . $e->getMessage());
}

// Sesiune (cu suport „rămâne logat” pentru login)
if (session_status() === PHP_SESSION_NONE) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'login.php' && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['remember_me'])) {
        session_set_cookie_params([
            'lifetime' => 30 * 24 * 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

// Helper‑e comune (autentificare, CSRF, helper platformă)
require_once __DIR__ . '/includes/auth_helper.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/platform_helper.php';

// Toate scripturile care includ config.php (mai puțin paginile publice) cer autentificare
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!in_array($script, auth_pagini_publice(), true)) {
    require_login();
}