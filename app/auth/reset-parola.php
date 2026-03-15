<?php
/**
 * Resetare parolă cu token - CRM ANR Bihor
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/auth_helper.php';

auth_ensure_tables($pdo);

$mesaj = '';
$succes = false;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$token_invalid = empty($token);

if ($token !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $token_invalid = auth_valideaza_token_reset($pdo, $token) === null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
    csrf_require_valid();
    $parola = $_POST['parola'] ?? '';
    $parola2 = $_POST['parola2'] ?? '';
    if (strlen($parola) < 6) {
        $mesaj = 'Parola trebuie să aibă minim 6 caractere.';
    } elseif ($parola !== $parola2) {
        $mesaj = 'Parolele introduse nu coincid.';
    } else {
        $rez = auth_reset_parola_cu_token($pdo, $token, $parola);
        if ($rez['ok']) {
            $succes = true;
            $mesaj = 'Parola a fost actualizată. Vă puteți autentifica cu noua parolă.';
        } else {
            $mesaj = $rez['mesaj'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetare parolă - <?php echo htmlspecialchars(PLATFORM_NAME); ?></title>
    <link href="/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .focus-visible:focus { outline: 2px solid #f59e0b; outline-offset: 2px; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md" role="main" aria-labelledby="reset-title">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-200 dark:border-gray-700 p-8">
            <h1 id="reset-title" class="text-xl font-bold text-slate-900 dark:text-white mb-2">Setare parolă nouă</h1>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Introduceți parola nouă (minim 6 caractere).</p>

            <?php if ($token_invalid && !$succes): ?>
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r text-sm" role="alert">
                Link invalid sau expirat. Solicitați din nou recuperarea parolei.
            </div>
            <a href="/recuperare-parola" class="inline-flex items-center justify-center w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                Recuperare parolă
            </a>
            <?php elseif ($succes): ?>
            <div class="mb-4 p-3 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-800 dark:text-emerald-200 rounded-r text-sm" role="status">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
            <a href="/login" class="inline-flex items-center justify-center w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                Mergi la autentificare
            </a>
            <?php else: ?>
            <?php if ($mesaj !== ''): ?>
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r text-sm" role="alert">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
            <?php endif; ?>
            <form method="post" action="/reset-parola" class="space-y-4" aria-label="Formular parolă nouă">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div>
                    <label for="parola" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parolă nouă <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="parola" name="parola" required minlength="6" autocomplete="new-password" autofocus
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true" aria-describedby="parola-desc">
                    <p id="parola-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Minim 6 caractere.</p>
                </div>
                <div>
                    <label for="parola2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Confirmare parolă <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="parola2" name="parola2" required minlength="6" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează parola nouă">
                        Salvează parola
                    </button>
                    <a href="/login" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                        Anulare
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
