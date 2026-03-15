<?php
/**
 * Bootstrap MVC Light — ERP ANR Bihor
 *
 * Include acest fisier din orice controller sau service.
 * Ofera: $pdo, sesiune, CSRF, auth, constante + APP_ROOT.
 *
 * NOTA: Daca esti inclus dintr-un adaptor root (contacte.php, todo.php etc.)
 * care deja a facut require config.php, acest fisier nu va re-include config.php
 * datorita require_once.
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config.php';
