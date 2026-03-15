<?php
/**
 * Cerere recuperare parolă - CRM ANR Bihor
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/auth_helper.php';

$mesaj = '';
$succes = false;
$redirect = trim($_GET['redirect'] ?? '/login');

auth_ensure_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesaj = 'Introduceți o adresă de email validă.';
    } else {
        $rez = auth_creaza_token_recuperare($pdo, $email);
        // Afișăm același mesaj indiferent dacă emailul există (securitate)
        $succes = true;
        $mesaj = 'Dacă există un cont asociat acestei adrese de email, ați primit un link pentru resetarea parolei. Verificați și spam-ul.';
        if ($rez) {
            auth_trimite_email_recuperare($rez['email'], $rez['token']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperare parolă - <?php echo htmlspecialchars(PLATFORM_NAME); ?></title>
    <link href="/css/tailwind.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .focus-visible:focus { outline: 2px solid #f59e0b; outline-offset: 2px; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md" role="main" aria-labelledby="recuperare-title">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-200 dark:border-gray-700 p-8">
            <h1 id="recuperare-title" class="text-xl font-bold text-slate-900 dark:text-white mb-2">Recuperare parolă</h1>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Introduceți adresa de email asociată contului. Veți primi un link pentru resetarea parolei (valid 1 oră).</p>

            <?php if ($mesaj !== ''): ?>
            <div class="mb-4 p-3 rounded text-sm <?php echo $succes ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 border-l-4 border-emerald-600' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 border-l-4 border-red-600'; ?>" role="alert">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
            <?php endif; ?>

            <?php if (!$succes): ?>
            <form method="post" action="/recuperare-parola" class="space-y-4" aria-label="Formular recuperare parolă">
                <?php echo csrf_field(); ?>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" required autocomplete="email" autofocus
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Trimite link recuperare">
                        Trimite link
                    </button>
                    <a href="/login?redirect=<?php echo urlencode($redirect); ?>" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                        Înapoi la login
                    </a>
                </div>
            </form>
            <?php else: ?>
            <a href="/login?redirect=<?php echo urlencode($redirect); ?>" class="inline-flex items-center justify-center w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                Înapoi la login
            </a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
