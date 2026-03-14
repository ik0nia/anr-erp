<?php
/**
 * Configurare CRM ANR - Asociația Nevăzătorilor Bihor
 *
 * PE HOSTING: Valorile de mai jos (root, fără parolă) sunt pentru XAMPP local.
 * După mutarea pe server, înlocuiți DB_HOST, DB_NAME, DB_USER, DB_PASS cu datele
 * din pasul 2 al installer-ului. Nu suprascrieți acest fișier pe server cu cel de pe PC
 * după ce a rulat instalarea (pasul 8 generează config cu datele corecte).
 */

// Versiune platformă: 1 = prima versiune; modificările ulterioare vor face parte din Update 1
if (!defined('PLATFORM_VERSION')) {
    define('PLATFORM_VERSION', '1');
}

// Definește numele platformei default (va fi suprascris din setări după ce conexiunea este disponibilă)
if (!defined('PLATFORM_NAME')) {
    define('PLATFORM_NAME', 'ERP ANR BIHOR');
}
if (!defined('PLATFORM_LOGO_URL')) {
    define('PLATFORM_LOGO_URL', 'https://anrbihor.ro/wp-content/uploads/2023/08/logo-anr-site-test1.png');
}
// URL bază platformă (pentru linkuri în emailuri - recuperare parolă, confirmare utilizator)
if (!defined('PLATFORM_BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (strpos($base, '/crm-anr-bihor') !== false) {
        $base = preg_replace('#/crm-anr-bihor.*$#', '/crm-anr-bihor', $base);
    }
    define('PLATFORM_BASE_URL', $protocol . '://' . $host . $base);
}

// Formate dată și oră - DD.MM.YYYY și 24 de ore
define('DATE_FORMAT', 'd.m.Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd.m.Y H:i');

// Cheie opțională pentru apelare cron newsletter din browser (lăsați gol = doar CLI)
if (!defined('CRON_NEWSLETTER_KEY')) {
    define('CRON_NEWSLETTER_KEY', '');
}

// Activare CSRF pentru protecție împotriva atacurilor Cross-Site Request Forgery
// Setează la false pentru a dezactiva protecția CSRF (doar pentru platforme interne foarte restricționate)
if (!defined('CSRF_ENABLED')) {
    define('CSRF_ENABLED', true);
}

// Configurare bază de date
// Pe hosting: baza r74526anrb_internapp_crm. Pe mediu local (XAMPP): creează config.local.php cu DB_NAME, DB_USER, DB_PASS pentru XAMPP.
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
if (!defined('DB_HOST')) { define('DB_HOST', 'localhost'); }
if (!defined('DB_NAME')) { define('DB_NAME', 'r74526anrb_internapp_crm'); }
if (!defined('DB_USER')) { define('DB_USER', 'r74526anrb_internapp_usr'); }
if (!defined('DB_PASS')) { define('DB_PASS', ''); } // Pe hosting: editează cu parola MySQL din cPanel

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
    // Actualizează PLATFORM_NAME din setări după ce conexiunea este disponibilă
    try {
        $stmt = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
        $stmt->execute(['platform_name']);
        $platform_name = $stmt->fetchColumn();
        if ($platform_name) {
            // Redefinește constanta dacă există valoare în setări
            if (defined('PLATFORM_NAME')) {
                // Nu putem redefini o constantă, dar vom folosi o funcție helper
                if (!function_exists('get_platform_name')) {
                    function get_platform_name() {
                        global $pdo;
                        try {
                            $stmt = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
                            $stmt->execute(['platform_name']);
                            $name = $stmt->fetchColumn();
                            return $name ?: PLATFORM_NAME;
                        } catch (Exception $e) {
                            return PLATFORM_NAME;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {}
} catch (PDOException $e) {
    die('Eroare conexiune bază de date: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    // „Rămâne logat”: cookie de sesiune 30 zile când utilizatorul bifează la login
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'login.php' && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['remember_me'])) {
        session_set_cookie_params([
            'lifetime' => 30 * 24 * 3600, // 30 zile
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

// Protecție autentificare: toate scripturile care folosesc config sunt protejate, cu excepția paginilor publice
require_once __DIR__ . '/includes/auth_helper.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/platform_helper.php';
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!in_array($script, auth_pagini_publice(), true)) {
    require_login();
}
