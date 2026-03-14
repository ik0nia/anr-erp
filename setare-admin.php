<?php
/**
 * Setare utilizator admin (one-time).
 * Utilizator: admin | Parolă: !5199002@Bihor | Nume: Mihai Merca | Funcție: Presedinte | Email: merca.bhanvr@gmail.com
 * După rulare, ștergeți acest fișier sau eliminați 'setare-admin.php' din auth_pagini_publice() în includes/auth_helper.php.
 */
require_once __DIR__ . '/config.php';
require_once 'includes/auth_helper.php';

auth_ensure_tables($pdo);

$username = 'admin';
$parola = '!5199002@Bihor';
$nume_complet = 'Mihai Merca';
$functie = 'Presedinte';
$email = 'merca.bhanvr@gmail.com';
$rol = 'administrator';

$hash = password_hash($parola, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('SELECT id FROM utilizatori WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $exista = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exista) {
        $pdo->prepare('UPDATE utilizatori SET nume_complet = ?, email = ?, functie = ?, parola_hash = ?, rol = ?, activ = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$nume_complet, $email, $functie, $hash, $rol, $exista['id']]);
        $mesaj = 'Utilizatorul admin a fost actualizat.';
    } else {
        $pdo->prepare('INSERT INTO utilizatori (nume_complet, email, functie, username, parola_hash, rol, activ) VALUES (?, ?, ?, ?, ?, ?, 1)')
            ->execute([$nume_complet, $email, $functie, $username, $hash, $rol]);
        $mesaj = 'Utilizatorul admin a fost creat.';
    }
} catch (PDOException $e) {
    $mesaj = 'Eroare: ' . $e->getMessage();
}
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setare admin</title>
    <link href="css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-200 dark:border-gray-700 p-8 max-w-md w-full text-center">
        <h1 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Setare admin</h1>
        <p class="text-slate-700 dark:text-gray-300 mb-6"><?php echo htmlspecialchars($mesaj); ?></p>
        <a href="login.php" class="inline-block px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Mergi la autentificare</a>
        <p class="text-xs text-slate-500 dark:text-gray-400 mt-6">Recomandare: ștergeți <code>setare-admin.php</code> sau eliminați-l din lista paginilor publice după utilizare.</p>
    </div>
</body>
</html>
