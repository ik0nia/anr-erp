<?php
/**
 * Router — Single entry point for clean URLs
 *
 * .htaccess sends requests here when no matching file exists on disk.
 * Maps clean URL paths to controllers (MVC) or legacy PHP files.
 */

// Parse request path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = '/' . trim($path, '/');

// --- Route definitions ---
$routes = [
    // MVC Controller routes
    '/'                        => ['type' => 'legacy', 'script' => 'index.php', 'file' => 'app/legacy/index.php'],
    '/dashboard'               => ['type' => 'legacy', 'script' => 'index.php', 'file' => 'app/legacy/index.php'],
    '/activitati'              => ['type' => 'controller', 'script' => 'activitati.php',              'file' => 'app/controllers/activitati/index.php'],
    '/activitati/istoric'      => ['type' => 'controller', 'script' => 'activitati-istoric.php',      'file' => 'app/controllers/activitati/istoric.php'],
    '/ajutoare-bpa'            => ['type' => 'controller', 'script' => 'ajutoare-bpa.php',            'file' => 'app/controllers/bpa/index.php'],
    '/contacte'                => ['type' => 'controller', 'script' => 'contacte.php',                'file' => 'app/controllers/contacte/index.php'],
    '/contacte/adauga'         => ['type' => 'controller', 'script' => 'contacte-adauga.php',         'file' => 'app/controllers/contacte/store.php'],
    '/contacte/edit'           => ['type' => 'controller', 'script' => 'contacte-edit.php',           'file' => 'app/controllers/contacte/update.php'],
    '/librarie-documente'      => ['type' => 'controller', 'script' => 'librarie-documente.php',      'file' => 'app/controllers/librarie-documente/index.php'],
    '/liste-prezenta/adauga'   => ['type' => 'controller', 'script' => 'lista-prezenta-create.php',   'file' => 'app/controllers/liste-prezenta/create.php'],
    '/liste-prezenta/edit'     => ['type' => 'controller', 'script' => 'lista-prezenta-edit.php',     'file' => 'app/controllers/liste-prezenta/edit.php'],
    '/notificari'              => ['type' => 'controller', 'script' => 'notificari.php',              'file' => 'app/controllers/notificari/index.php'],
    '/notificari/view'         => ['type' => 'controller', 'script' => 'notificare-view.php',         'file' => 'app/controllers/notificari/view.php'],
    '/rapoarte'                => ['type' => 'controller', 'script' => 'rapoarte.php',                'file' => 'app/controllers/rapoarte/index.php'],
    '/registratura'            => ['type' => 'controller', 'script' => 'registratura.php',            'file' => 'app/controllers/registratura/index.php'],
    '/registratura/adauga'     => ['type' => 'controller', 'script' => 'registratura-adauga.php',     'file' => 'app/controllers/registratura/store.php'],
    '/registratura/edit'       => ['type' => 'controller', 'script' => 'registratura-edit.php',       'file' => 'app/controllers/registratura/update.php'],
    '/registru-interactiuni'   => ['type' => 'controller', 'script' => 'registru-interactiuni-v2.php','file' => 'app/controllers/registru-interactiuni-v2/index.php'],
    '/todo'                    => ['type' => 'controller', 'script' => 'todo.php',                    'file' => 'app/controllers/todo/index.php'],
    '/todo/adauga'             => ['type' => 'controller', 'script' => 'todo-adauga.php',             'file' => 'app/controllers/todo/store.php'],
    '/todo/edit'               => ['type' => 'controller', 'script' => 'todo-edit.php',               'file' => 'app/controllers/todo/edit.php'],
    '/voluntariat'             => ['type' => 'controller', 'script' => 'voluntariat.php',             'file' => 'app/controllers/voluntariat/index.php'],

    // Legacy modules (not yet migrated to MVC)
    '/membri'                  => ['type' => 'legacy', 'script' => 'membri.php',                'file' => 'app/legacy/membri.php'],
    '/setari'                  => ['type' => 'legacy', 'script' => 'setari.php',                'file' => 'app/legacy/setari.php'],
    '/administrativ'           => ['type' => 'legacy', 'script' => 'administrativ.php',         'file' => 'app/legacy/administrativ.php'],
    '/formular-230'            => ['type' => 'legacy', 'script' => 'formular-230.php',          'file' => 'app/legacy/formular-230.php'],
    '/fundraising'             => ['type' => 'legacy', 'script' => 'fundraising.php',           'file' => 'app/legacy/fundraising.php'],
    '/membru-profil'           => ['type' => 'legacy', 'script' => 'membru-profil.php',         'file' => 'app/legacy/membru-profil.php'],
    '/aniversari'              => ['type' => 'legacy', 'script' => 'aniversari.php',            'file' => 'app/legacy/aniversari.php'],
    '/generare-documente'      => ['type' => 'legacy', 'script' => 'generare-documente.php',    'file' => 'app/legacy/generare-documente.php'],
    '/log-activitate'          => ['type' => 'legacy', 'script' => 'log-activitate.php',        'file' => 'app/legacy/log-activitate.php'],
    '/newsletter-view'         => ['type' => 'legacy', 'script' => 'newsletter-view.php',       'file' => 'app/legacy/newsletter-view.php'],
    '/registratura/sumar'      => ['type' => 'legacy', 'script' => 'registratura-sumar.php',    'file' => 'app/legacy/registratura-sumar.php'],
    '/actualizezcsv'           => ['type' => 'legacy', 'script' => 'actualizezcsv.php',         'file' => 'app/legacy/actualizezcsv.php'],
    '/import-membri-csv'       => ['type' => 'legacy', 'script' => 'import-membri-csv.php',     'file' => 'app/legacy/import-membri-csv.php'],
    '/contacte/import'         => ['type' => 'legacy', 'script' => 'contacte-import.php',       'file' => 'app/views/partials/contacte-import.php'],

    // Auth pages
    '/login'                   => ['type' => 'legacy', 'script' => 'login.php',                 'file' => 'app/auth/login.php'],
    '/logout'                  => ['type' => 'legacy', 'script' => 'logout.php',                'file' => 'app/auth/logout.php'],
    '/recuperare-parola'       => ['type' => 'legacy', 'script' => 'recuperare-parola.php',     'file' => 'app/auth/recuperare-parola.php'],
    '/reset-parola'            => ['type' => 'legacy', 'script' => 'reset-parola.php',          'file' => 'app/auth/reset-parola.php'],
    '/schimba-parola'          => ['type' => 'legacy', 'script' => 'schimba-parola.php',        'file' => 'app/auth/schimba-parola.php'],

    // API endpoints
    '/api/cauta-membri'            => ['type' => 'legacy', 'script' => 'api-cauta-membri.php',            'file' => 'api/cauta-membri.php'],
    '/api/cauta-voluntari'         => ['type' => 'legacy', 'script' => 'api-cauta-voluntari.php',         'file' => 'api/cauta-voluntari.php'],
    '/api/bpa-nr-registratura'     => ['type' => 'legacy', 'script' => 'api-bpa-nr-registratura.php',    'file' => 'api/bpa-nr-registratura.php'],
    '/api/registru-v2-stats'       => ['type' => 'legacy', 'script' => 'api-registru-v2-stats.php',      'file' => 'api/registru-v2-stats.php'],
    '/api/incasari-cauta-membri'   => ['type' => 'legacy', 'script' => 'incasari-cauta-membri.php',      'file' => 'api/incasari-cauta-membri.php'],
    '/api/incasari-salveaza'       => ['type' => 'legacy', 'script' => 'incasari-salveaza.php',           'file' => 'api/incasari-salveaza.php'],
    '/api/incasari-dashboard-salveaza' => ['type' => 'legacy', 'script' => 'incasari-dashboard-salveaza.php', 'file' => 'api/incasari-dashboard-salveaza.php'],
    '/api/genereaza-document'      => ['type' => 'legacy', 'script' => 'genereaza-document.php',          'file' => 'api/genereaza-document.php'],
    '/api/trimite-email-document'  => ['type' => 'legacy', 'script' => 'trimite-email-document.php',      'file' => 'api/trimite-email-document.php'],
    '/api/log-print-document'      => ['type' => 'legacy', 'script' => 'log-print-document.php',          'file' => 'api/log-print-document.php'],
];

// --- Route matching ---
$route = $routes[$path] ?? null;

if (!$route) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ro"><head><meta charset="utf-8"><title>404 - Pagina nu a fost găsită</title></head>';
    echo '<body style="font-family:sans-serif;text-align:center;padding:60px"><h1>404</h1><p>Pagina nu a fost găsită.</p>';
    echo '<a href="/dashboard">Mergi la Dashboard</a></body></html>';
    exit;
}

// Spoof SCRIPT_NAME so config.php auth checks and PLATFORM_BASE_URL work correctly
$_SERVER['SCRIPT_NAME'] = '/' . $route['script'];

$file = __DIR__ . '/' . $route['file'];

if ($route['type'] === 'controller') {
    // MVC route: load config then controller
    require_once __DIR__ . '/config.php';
    require_once $file;
} else {
    // Legacy route: include original file (which loads config.php itself)
    require $file;
}
