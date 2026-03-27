<?php
/**
 * Controller: Încasări — Setări serii chitanțe
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/csrf_helper.php';

$eroare = '';
$succes = '';

incasari_ensure_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_serii_incasari'])) {
    csrf_require_valid();

    $nr_start_donatii = max(1, (int)($_POST['nr_start_donatii'] ?? 1));
    $nr_start_incasari = max(1, (int)($_POST['nr_start_incasari'] ?? 1));

    $nr_curent_donatii = max($nr_start_donatii, (int)($_POST['nr_curent_donatii'] ?? $nr_start_donatii));
    $nr_curent_incasari = max($nr_start_incasari, (int)($_POST['nr_curent_incasari'] ?? $nr_start_incasari));

    $nr_final_donatii = max($nr_start_donatii, (int)($_POST['nr_final_donatii'] ?? $nr_curent_donatii));
    $nr_final_incasari = max($nr_start_incasari, (int)($_POST['nr_final_incasari'] ?? $nr_curent_incasari));

    incasari_salveaza_serie(
        $pdo,
        'donatii',
        trim((string)($_POST['serie_donatii'] ?? 'D')),
        $nr_start_donatii,
        $nr_curent_donatii,
        $nr_final_donatii
    );
    incasari_salveaza_serie(
        $pdo,
        'incasari',
        trim((string)($_POST['serie_incasari'] ?? 'INC')),
        $nr_start_incasari,
        $nr_curent_incasari,
        $nr_final_incasari
    );

    log_activitate($pdo, 'Încasări: serii și intervale chitanțe actualizate din pagina Încasări > Setări.');
    header('Location: /incasari/setari?succes_incasari=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_design_chitante'])) {
    csrf_require_valid();

    $email_notificari_stergere = trim((string)($_POST['email_notificari_stergere_chitanta'] ?? ''));
    if ($email_notificari_stergere !== '' && !filter_var($email_notificari_stergere, FILTER_VALIDATE_EMAIL)) {
        $eroare = 'Adresa email pentru notificări ștergere chitanță nu este validă.';
    } else {
        if (isset($_FILES['info_suplimentare_chitanta_imagine']) && (int)($_FILES['info_suplimentare_chitanta_imagine']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload_result = incasari_upload_info_suplimentara_image($pdo, $_FILES['info_suplimentare_chitanta_imagine']);
            if (empty($upload_result['success'])) {
                $eroare = $upload_result['error'] ?? 'Eroare la încărcarea imaginii pentru informații suplimentare.';
            }
        }
    }

    if ($eroare === '') {
        incasari_set_setare($pdo, 'logo_chitanta', trim((string)($_POST['logo_chitanta'] ?? '')));
        incasari_set_setare($pdo, 'date_asociatie', trim((string)($_POST['date_asociatie'] ?? '')));
        incasari_set_setare(
            $pdo,
            'dimensiune_chitanta',
            in_array((string)($_POST['dimensiune_chitanta'] ?? 'a5'), ['a5', 'a4'], true) ? (string)$_POST['dimensiune_chitanta'] : 'a5'
        );
        incasari_set_setare($pdo, 'template_chitanta', trim((string)($_POST['template_chitanta'] ?? 'standard')));
        incasari_set_setare($pdo, 'email_notificari_stergere_chitanta', $email_notificari_stergere);

        log_activitate($pdo, 'Încasări: design chitanțe actualizat din pagina Încasări > Setări.');
        header('Location: /incasari/setari?succes_incasari=1');
        exit;
    }
}

if (isset($_GET['succes_incasari'])) {
    $succes = 'Setările modulului Încasări au fost salvate.';
}

$incasari_serie_donatii = array_merge(
    incasari_get_serie($pdo, 'donatii') ?: [],
    ['nr_final' => incasari_get_serie_nr_final($pdo, 'donatii') ?: 0]
);
$incasari_serie_incasari = array_merge(
    incasari_get_serie($pdo, 'incasari') ?: [],
    ['nr_final' => incasari_get_serie_nr_final($pdo, 'incasari') ?: 0]
);
$incasari_setari_design = [
    'logo_chitanta' => incasari_get_setare($pdo, 'logo_chitanta') ?: (defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''),
    'date_asociatie' => incasari_get_setare($pdo, 'date_asociatie') ?: '',
    'dimensiune_chitanta' => incasari_get_setare($pdo, 'dimensiune_chitanta') ?: 'a5',
    'template_chitanta' => incasari_get_setare($pdo, 'template_chitanta') ?: 'standard',
    'email_notificari_stergere_chitanta' => incasari_get_setare($pdo, 'email_notificari_stergere_chitanta') ?: '',
    'info_suplimentare_chitanta_image_path' => incasari_get_setare($pdo, 'informatii_suplimentare_chitanta_image_path') ?: '',
    'info_suplimentare_chitanta_image_url' => '',
];
$incasari_setari_design['info_suplimentare_chitanta_image_url'] = $incasari_setari_design['info_suplimentare_chitanta_image_path'] !== ''
    ? '/' . ltrim((string)$incasari_setari_design['info_suplimentare_chitanta_image_path'], '/')
    : '';

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/incasari/setari.php';

