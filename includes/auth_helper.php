<?php
/**
 * Helper autentificare - CRM ANR Bihor
 * Verificare sesiune, roluri, hash parolă, token recuperare
 */

if (!defined('PLATFORM_NAME')) {
    die('Configurarea trebuie încărcată înainte de auth_helper.');
}

/** Pagini care nu necesită autentificare */
function auth_pagini_publice() {
    return ['login.php', 'logout.php', 'recuperare-parola.php', 'reset-parola.php', 'install-auth.php', 'setare-admin.php'];
}

/**
 * Redirecționează la login dacă utilizatorul nu este autentificat.
 * Apelați după config.php pe toate paginile protejate.
 */
function require_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (in_array($script, auth_pagini_publice(), true)) {
        return;
    }
    if (empty($_SESSION['user_id']) || empty($_SESSION['utilizator'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard');
        header('Location: /login?redirect=' . $redirect);
        exit;
    }
    return true;
}

/**
 * Returnează true dacă utilizatorul curent este administrator.
 */
function is_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrator';
}

/**
 * Cere rol de administrator; redirecționează la index dacă nu are.
 */
function require_admin() {
    if (!is_admin()) {
        header('Location: /dashboard');
        exit;
    }
}

/**
 * Asigură că tabelele utilizatori există.
 */
function auth_ensure_tables(PDO $pdo) {
    // No-op: schema is managed by install/schema/migration.php
    return;
}

/**
 * Autentificare utilizator. Returnează array cu user sau eroare.
 */
function auth_login(PDO $pdo, $username, $password) {
    auth_ensure_tables($pdo);
    $username = trim($username);
    $password = (string) $password;
    if ($username === '' || $password === '') {
        return ['ok' => false, 'mesaj' => 'Introduceți utilizatorul și parola.'];
    }
    $stmt = $pdo->prepare('SELECT id, nume_complet, email, username, parola_hash, rol, activ FROM utilizatori WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        return ['ok' => false, 'mesaj' => 'Utilizator sau parolă incorectă.'];
    }
    if (!$u['activ']) {
        return ['ok' => false, 'mesaj' => 'Cont dezactivat. Contactați administratorul.'];
    }
    if (!password_verify($password, $u['parola_hash'])) {
        return ['ok' => false, 'mesaj' => 'Utilizator sau parolă incorectă.'];
    }
    return [
        'ok' => true,
        'id' => (int) $u['id'],
        'nume_complet' => $u['nume_complet'],
        'username' => $u['username'],
        'rol' => $u['rol'],
    ];
}

/**
 * Creează token pentru recuperare parolă. Returnează token sau null.
 */
function auth_creaza_token_recuperare(PDO $pdo, $email) {
    auth_ensure_tables($pdo);
    $email = trim($email);
    if ($email === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id FROM utilizatori WHERE email = ? AND activ = 1 LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        return null; // Nu dezvăluim dacă emailul există
    }
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $pdo->prepare('INSERT INTO password_reset_tokens (utilizator_id, token, expira_la) VALUES (?, ?, ?)')->execute([$u['id'], $token, $expira]);
    return ['token' => $token, 'email' => $email, 'user_id' => (int) $u['id']];
}

/**
 * Validează token și returnează user_id sau null.
 */
function auth_valideaza_token_reset(PDO $pdo, $token) {
    $token = trim($token);
    if ($token === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT utilizator_id FROM password_reset_tokens WHERE token = ? AND folosit = 0 AND expira_la > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? (int) $r['utilizator_id'] : null;
}

/**
 * Marchează tokenul ca folosit și actualizează parola.
 */
function auth_reset_parola_cu_token(PDO $pdo, $token, $parola_noua) {
    $user_id = auth_valideaza_token_reset($pdo, $token);
    if (!$user_id) {
        return ['ok' => false, 'mesaj' => 'Link invalid sau expirat.'];
    }
    $parola_noua = (string) $parola_noua;
    if (strlen($parola_noua) < 8) {
        return ['ok' => false, 'mesaj' => 'Parola trebuie să aibă minim 8 caractere.'];
    }
    $hash = password_hash($parola_noua, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE utilizatori SET parola_hash = ? WHERE id = ?')->execute([$hash, $user_id]);
        $pdo->prepare('UPDATE password_reset_tokens SET folosit = 1 WHERE token = ?')->execute([$token]);
        $pdo->commit();
        return ['ok' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['ok' => false, 'mesaj' => 'Eroare la actualizare parolă.'];
    }
}

/**
 * Schimbă parola utilizatorului autentificat (parola actuală + parolă nouă).
 * Returnează ['ok' => true] sau ['ok' => false, 'mesaj' => '...'].
 */
function auth_schimba_parola(PDO $pdo, $user_id, $parola_actuala, $parola_noua) {
    $user_id = (int) $user_id;
    if ($user_id < 1) {
        return ['ok' => false, 'mesaj' => 'Sesiune invalidă.'];
    }
    $stmt = $pdo->prepare('SELECT parola_hash FROM utilizatori WHERE id = ? AND activ = 1 LIMIT 1');
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u || !password_verify((string) $parola_actuala, $u['parola_hash'])) {
        return ['ok' => false, 'mesaj' => 'Parola actuală este incorectă.'];
    }
    $parola_noua = (string) $parola_noua;
    if (strlen($parola_noua) < 8) {
        return ['ok' => false, 'mesaj' => 'Parola nouă trebuie să aibă minim 8 caractere.'];
    }
    $hash = password_hash($parola_noua, PASSWORD_DEFAULT);
    try {
        $pdo->prepare('UPDATE utilizatori SET parola_hash = ? WHERE id = ?')->execute([$hash, $user_id]);
        return ['ok' => true];
    } catch (PDOException $e) {
        return ['ok' => false, 'mesaj' => 'Eroare la actualizare parolă.'];
    }
}

/**
 * Trimite email recuperare parolă. Returnează true/false.
 */
function auth_trimite_email_recuperare($email, $token) {
    $url = (defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '') . '/reset-parola?token=' . urlencode($token);
    $subiect = '[' . PLATFORM_NAME . '] Recuperare parolă';
    $mesaj = "Bună ziua,\n\nAți solicitat resetarea parolei pentru " . PLATFORM_NAME . ".\n\n";
    $mesaj .= "Accesați linkul de mai jos pentru a seta o parolă nouă (valid 1 oră):\n" . $url . "\n\n";
    $mesaj .= "Dacă nu ați solicitat acest email, ignorați-l.\n\n— " . PLATFORM_NAME;
    $headers = "From: " . PLATFORM_NAME . " <noreply@anrbihor.ro>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($email, $subiect, $mesaj, $headers);
}

/**
 * Trimite email confirmare cont nou (fără parolă, cu link login).
 */
function auth_trimite_email_confirmare_utilizator($nume_complet, $email, $username, $functie, $rol) {
    $url_login = (defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '') . '/login';
    $subiect = '[' . PLATFORM_NAME . '] Cont creat';
    $mesaj = "Bună ziua, " . $nume_complet . ",\n\n";
    $mesaj .= "Vi s-a creat un cont în platforma " . PLATFORM_NAME . ".\n\n";
    $mesaj .= "Date cont:\n";
    $mesaj .= "- Nume complet: " . $nume_complet . "\n";
    $mesaj .= "- Email: " . $email . "\n";
    $mesaj .= "- Nume utilizator: " . $username . "\n";
    if ($functie !== '') {
        $mesaj .= "- Funcție: " . $functie . "\n";
    }
    $mesaj .= "- Rol: " . $rol . "\n\n";
    $mesaj .= "Parola nu este afișată în email. Dacă ați primit una temporar de la administrator, folosiți-o la prima autentificare. ";
    $mesaj .= "Puteți solicita recuperare parolă din pagina de login.\n\n";
    $mesaj .= "Link autentificare: " . $url_login . "\n\n— " . PLATFORM_NAME;
    $headers = "From: " . PLATFORM_NAME . " <noreply@anrbihor.ro>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($email, $subiect, $mesaj, $headers);
}
