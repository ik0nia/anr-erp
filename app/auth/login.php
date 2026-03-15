<?php
/**
 * Pagină autentificare - CRM ANR Bihor
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/auth_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

$eroare = '';
$redirect = trim($_POST['redirect'] ?? $_GET['redirect'] ?? '/dashboard');
if ($redirect === '' || strpos($redirect, '//') !== false) {
    $redirect = '/dashboard';
}

// Dacă e deja autentificat, redirecționează
if (!empty($_SESSION['user_id']) && !empty($_SESSION['utilizator'])) {
    header('Location: ' . ($redirect !== '' ? $redirect : '/dashboard'));
    exit;
}

auth_ensure_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($user === '' || $pass === '') {
        $eroare = 'Introduceți numele de utilizator și parola.';
    } else {
        $rez = auth_login($pdo, $user, $pass);
        if ($rez['ok']) {
            $_SESSION['user_id'] = $rez['id'];
            $_SESSION['utilizator'] = $rez['nume_complet'];
            $_SESSION['username'] = $rez['username'];
            $_SESSION['rol'] = $rez['rol'];
            // Log autentificare
            log_activitate($pdo, "autentificare: Utilizator autentificat - {$rez['username']} ({$rez['nume_complet']})", $rez['username']);
            header('Location: ' . $redirect);
            exit;
        }
        $eroare = $rez['mesaj'];
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - <?php echo htmlspecialchars(PLATFORM_NAME); ?></title>
    <link href="/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .focus-visible:focus { outline: 2px solid #f59e0b; outline-offset: 2px; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md" role="main" aria-labelledby="login-title">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-200 dark:border-gray-700 p-8">
            <div class="text-center mb-6">
                <?php if (defined('PLATFORM_LOGO_URL') && PLATFORM_LOGO_URL !== ''): ?>
                <img src="<?php echo htmlspecialchars(PLATFORM_LOGO_URL); ?>"
                     alt="Logo <?php echo htmlspecialchars(PLATFORM_NAME); ?>"
                     class="h-16 w-auto mx-auto object-contain mb-4">
                <?php endif; ?>
                <h1 id="login-title" class="text-xl font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars(PLATFORM_NAME); ?></h1>
                <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">Autentificare</p>
            </div>

            <?php if ($eroare !== ''): ?>
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r text-sm" role="alert">
                <?php echo htmlspecialchars($eroare); ?>
            </div>
            <?php endif; ?>

            <form method="post" action="/login" class="space-y-4" aria-label="Formular autentificare">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume utilizator <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true" aria-describedby="username-desc">
                    <span id="username-desc" class="sr-only">Introduceți numele de utilizator</span>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parolă <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1"
                           class="w-4 h-4 text-amber-600 border-slate-300 dark:border-gray-600 rounded focus:ring-amber-500 dark:bg-gray-700"
                           <?php echo !empty($_POST['remember_me']) ? 'checked' : ''; ?>
                           aria-describedby="remember_me-desc">
                    <label for="remember_me" class="ml-2 text-sm text-slate-700 dark:text-gray-300" id="remember_me-desc">Rămâne logat (30 zile)</label>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Autentificare">
                        Autentificare
                    </button>
                    <a href="/recuperare-parola?redirect=<?php echo urlencode($redirect); ?>"
                       class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500 text-center"
                       aria-label="Recuperare parolă">
                        Recuperare parolă
                    </a>
                </div>
            </form>
        </div>
    </main>
    <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
