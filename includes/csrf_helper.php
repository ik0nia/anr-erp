<?php
/**
 * Helper CSRF Protection - CRM ANR Bihor
 * Protecție împotriva atacurilor Cross-Site Request Forgery
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generează un token CSRF și îl salvează în sesiune
 * @return string Token CSRF
 */
function csrf_generate_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Returnează token-ul CSRF curent sau generează unul nou
 * @return string Token CSRF
 */
function csrf_token() {
    return csrf_generate_token();
}

/**
 * Validează token-ul CSRF
 * @param string $token Token-ul de validat
 * @return bool True dacă token-ul este valid
 */
function csrf_validate_token($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verifică token-ul CSRF din POST și aruncă excepție dacă este invalid
 * @throws Exception Dacă token-ul este invalid
 */
function csrf_require_valid() {
    // Dacă CSRF este dezactivat (pentru platforme interne), nu verifică token-ul
    if (defined('CSRF_ENABLED') && CSRF_ENABLED === false) {
        return;
    }
    $token = $_POST['_csrf_token'] ?? '';
    if (!csrf_validate_token($token)) {
        http_response_code(403);
        die('Eroare de securitate: Token CSRF invalid sau lipsă. Vă rugăm să reîncărcați pagina și să încercați din nou.');
    }
}

/**
 * Returnează HTML pentru câmp hidden CSRF token
 * @return string HTML input hidden
 */
function csrf_field() {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
