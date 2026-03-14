<?php
/**
 * Instalare modul autentificare - creează tabele și primul utilizator administrator.
 * Rulați o singură dată în browser, apoi ștergeți sau restricționați accesul la acest fișier.
 */
require_once __DIR__ . '/config.php';
require_once 'includes/auth_helper.php';

define('SKIP_AUTH_CHECK', true);
auth_ensure_tables($pdo);

$creat = false;
$eroare = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creaza_admin'])) {
    $user = trim($_POST['username'] ?? 'admin');
    $parola = $_POST['parola'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $nume = trim($_POST['nume_complet'] ?? 'Administrator');
    if ($user === '' || strlen($parola) < 6) {
        $eroare = 'Nume utilizator și parolă (min. 6 caractere) sunt obligatorii.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM utilizatori WHERE username = ? LIMIT 1');
            $stmt->execute([$user]);
            if ($stmt->fetch()) {
                $eroare = 'Există deja un utilizator cu acest nume.';
            } else {
                $hash = password_hash($parola, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO utilizatori (nume_complet, email, functie, username, parola_hash, rol, activ) VALUES (?, ?, ?, ?, ?, ?, 1)')
                    ->execute([$nume ?: 'Administrator', $email ?: 'admin@anrbihor.ro', 'Administrator', $user, $hash, 'administrator']);
                $creat = true;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare: ' . $e->getMessage();
        }
    }
}

// Verifică dacă există deja cel puțin un admin
$exista_admin = false;
try {
    $r = $pdo->query("SELECT 1 FROM utilizatori WHERE rol = 'administrator' LIMIT 1")->fetch();
    $exista_admin = (bool) $r;
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalare autentificare - <?php echo htmlspecialchars(PLATFORM_NAME); ?></title>
    <link href="css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h1 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Instalare autentificare</h1>
        <?php if ($creat): ?>
        <p class="text-emerald-700 dark:text-emerald-300 mb-4">Administrator creat. <a href="login.php" class="underline font-medium">Mergi la autentificare</a>.</p>
        <p class="text-sm text-slate-600 dark:text-gray-400">Recomandare: ștergeți sau restricționați accesul la <code>install-auth.php</code>.</p>
        <?php elseif ($exista_admin): ?>
        <p class="text-slate-700 dark:text-gray-300 mb-4">Există deja cel puțin un administrator. Folosiți <a href="login.php" class="text-amber-600 underline">login</a> sau adăugați utilizatori din Setări → Management utilizatori.</p>
        <a href="login.php" class="inline-block px-4 py-2 bg-amber-600 text-white rounded-lg">Autentificare</a>
        <?php else: ?>
        <?php if ($eroare): ?>
        <p class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded text-sm"><?php echo htmlspecialchars($eroare); ?></p>
        <?php endif; ?>
        <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Creați primul utilizator administrator (parola min. 6 caractere).</p>
        <form method="post" action="install-auth.php" class="space-y-3">
            <input type="hidden" name="creaza_admin" value="1">
            <div>
                <label for="nume_complet" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume complet</label>
                <input type="text" id="nume_complet" name="nume_complet" value="<?php echo htmlspecialchars($_POST['nume_complet'] ?? 'Administrator'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@anrbihor.ro'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume utilizator *</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="parola" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parolă * (min. 6 caractere)</label>
                <input type="password" id="parola" name="parola" required minlength="6" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </div>
            <button type="submit" class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Creează administrator</button>
        </form>
        <?php endif; ?>
    </main>
</body>
</html>
