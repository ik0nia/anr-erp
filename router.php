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
    '/'                        => ['type' => 'controller', 'script' => 'index.php', 'file' => 'app/controllers/dashboard/index.php'],
    '/dashboard'               => ['type' => 'controller', 'script' => 'index.php', 'file' => 'app/controllers/dashboard/index.php'],
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
    '/tickete'                 => ['type' => 'controller', 'script' => 'tickete.php',                  'file' => 'app/controllers/tickete/index.php'],
    '/tickete/edit'            => ['type' => 'controller', 'script' => 'tickete-edit.php',             'file' => 'app/controllers/tickete/edit.php'],
    '/todo'                    => ['type' => 'controller', 'script' => 'todo.php',                    'file' => 'app/controllers/todo/index.php'],
    '/todo/adauga'             => ['type' => 'controller', 'script' => 'todo-adauga.php',             'file' => 'app/controllers/todo/store.php'],
    '/todo/edit'               => ['type' => 'controller', 'script' => 'todo-edit.php',               'file' => 'app/controllers/todo/edit.php'],
    '/voluntariat'             => ['type' => 'controller', 'script' => 'voluntariat.php',             'file' => 'app/controllers/voluntariat/index.php'],
    '/comunicare'              => ['type' => 'controller', 'script' => 'comunicare.php',              'file' => 'app/controllers/comunicare/index.php'],

    // Legacy modules (not yet migrated to MVC)
    '/membri'                  => ['type' => 'controller', 'script' => 'membri.php',            'file' => 'app/controllers/membri/index.php'],
    '/membri/adauga'           => ['type' => 'controller', 'script' => 'membri-adauga.php',    'file' => 'app/controllers/membri/store.php'],
    '/setari'                  => ['type' => 'controller', 'script' => 'setari.php',             'file' => 'app/controllers/setari/index.php'],
    '/administrativ'           => ['type' => 'controller', 'script' => 'administrativ.php',      'file' => 'app/controllers/administrativ/index.php'],
    '/formular-230'            => ['type' => 'controller', 'script' => 'formular-230.php',       'file' => 'app/controllers/formular-230/index.php'],
    '/fundraising'             => ['type' => 'controller', 'script' => 'fundraising.php',        'file' => 'app/controllers/fundraising/index.php'],
    '/incasari'                => ['type' => 'controller', 'script' => 'incasari.php',           'file' => 'app/controllers/incasari/index.php'],
    '/membru-profil'           => ['type' => 'controller', 'script' => 'membru-profil.php',     'file' => 'app/controllers/membri/profil.php'],
    '/aniversari'              => ['type' => 'controller', 'script' => 'aniversari.php',         'file' => 'app/controllers/aniversari/index.php'],
    '/generare-documente'      => ['type' => 'controller', 'script' => 'generare-documente.php', 'file' => 'app/controllers/generare-documente/index.php'],
    '/log-activitate'          => ['type' => 'controller', 'script' => 'log-activitate.php',     'file' => 'app/controllers/log-activitate/index.php'],
    '/newsletter'              => ['type' => 'controller', 'script' => 'newsletter.php',          'file' => 'app/controllers/newsletter/index.php'],
    '/newsletter-view'         => ['type' => 'controller', 'script' => 'newsletter-view.php',    'file' => 'app/controllers/newsletter/view.php'],
    '/registratura/sumar'      => ['type' => 'controller', 'script' => 'registratura-sumar.php', 'file' => 'app/controllers/registratura/sumar.php'],
    '/actualizeaza-csv'        => ['type' => 'controller', 'script' => 'actualizezcsv.php',      'file' => 'app/controllers/import/actualizeaza-csv.php'],
    '/actualizezcsv'           => ['type' => 'controller', 'script' => 'actualizezcsv.php',      'file' => 'app/controllers/import/actualizeaza-csv.php'],
    '/import-membri-csv'       => ['type' => 'controller', 'script' => 'import-membri-csv.php',  'file' => 'app/controllers/import/membri-csv.php'],
    '/contacte/import'         => ['type' => 'controller', 'script' => 'contacte-import.php',    'file' => 'app/controllers/contacte/import.php'],

    // Auth pages
    '/login'                   => ['type' => 'legacy', 'script' => 'login.php',                 'file' => 'app/auth/login.php'],
    '/logout'                  => ['type' => 'legacy', 'script' => 'logout.php',                'file' => 'app/auth/logout.php'],
    '/recuperare-parola'       => ['type' => 'legacy', 'script' => 'recuperare-parola.php',     'file' => 'app/auth/recuperare-parola.php'],
    '/reset-parola'            => ['type' => 'legacy', 'script' => 'reset-parola.php',          'file' => 'app/auth/reset-parola.php'],
    '/schimba-parola'          => ['type' => 'legacy', 'script' => 'schimba-parola.php',        'file' => 'app/auth/schimba-parola.php'],

    // API endpoints
    '/api/cauta-membri'            => ['type' => 'legacy', 'script' => 'api-cauta-membri.php',            'file' => 'api/cauta-membri.php'],
    '/api/cauta-voluntari'         => ['type' => 'legacy', 'script' => 'api-cauta-voluntari.php',         'file' => 'api/cauta-voluntari.php'],
    '/api/cauta-contacte'          => ['type' => 'legacy', 'script' => 'api-cauta-contacte.php',          'file' => 'api/cauta-contacte.php'],
    '/api/bpa-nr-registratura'     => ['type' => 'legacy', 'script' => 'api-bpa-nr-registratura.php',    'file' => 'api/bpa-nr-registratura.php'],
    '/api/registru-v2-stats'       => ['type' => 'legacy', 'script' => 'api-registru-v2-stats.php',      'file' => 'api/registru-v2-stats.php'],
    '/api/incasari-cauta-membri'   => ['type' => 'legacy', 'script' => 'incasari-cauta-membri.php',      'file' => 'api/incasari-cauta-membri.php'],
    '/api/incasari-salveaza'       => ['type' => 'legacy', 'script' => 'incasari-salveaza.php',           'file' => 'api/incasari-salveaza.php'],
    '/api/incasari-dashboard-salveaza' => ['type' => 'legacy', 'script' => 'incasari-dashboard-salveaza.php', 'file' => 'api/incasari-dashboard-salveaza.php'],
    '/api/incasari-update'             => ['type' => 'legacy', 'script' => 'incasari-update.php',            'file' => 'api/incasari-update.php'],
    '/api/incasari-sterge'             => ['type' => 'legacy', 'script' => 'incasari-sterge.php',            'file' => 'api/incasari-sterge.php'],
    '/api/genereaza-document'      => ['type' => 'legacy', 'script' => 'genereaza-document.php',          'file' => 'api/genereaza-document.php'],
    '/api/trimite-email-document'  => ['type' => 'legacy', 'script' => 'trimite-email-document.php',      'file' => 'api/trimite-email-document.php'],
    '/api/log-print-document'      => ['type' => 'legacy', 'script' => 'log-print-document.php',          'file' => 'api/log-print-document.php'],
    '/api/log-actiune-membru'      => ['type' => 'legacy', 'script' => 'log-actiune-membru.php',         'file' => 'api/log-actiune-membru.php'],
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
