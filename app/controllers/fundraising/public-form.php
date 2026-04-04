<?php
/**
 * Controller public: Formular 230 (Fundraising)
 *
 * Pagină accesibilă fără autentificare.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/FundraisingService.php';

fundraising_f230_ensure_schema($pdo);
$setari_modul = fundraising_f230_get_settings($pdo);
$public_url = (string)$setari_modul['public_url'];
$template_activ = !empty($setari_modul['template_exists']);

$eroare = '';
$succes = '';
$warning = '';

$valori = [
    'nume' => '',
    'initiala_tatalui' => '',
    'prenume' => '',
    'cnp' => '',
    'localitate' => '',
    'judet' => '',
    'cod_postal' => '',
    'strada' => '',
    'numar' => '',
    'bloc' => '',
    'scara' => '',
    'etaj' => '',
    'apartament' => '',
    'telefon' => '',
    'email' => '',
    'gdpr_acord' => 0,
    'signature_data' => '',
];

if (!empty($_SESSION['fundraising_public_flash']) && is_array($_SESSION['fundraising_public_flash'])) {
    $flash = $_SESSION['fundraising_public_flash'];
    unset($_SESSION['fundraising_public_flash']);
    $succes = (string)($flash['succes'] ?? '');
    $warning = (string)($flash['warning'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trimite_formular_230_public'])) {
    if (!$template_activ) {
        $eroare = 'Formularul nu este configurat încă. Revino mai târziu.';
    } else {
        csrf_require_valid();
        $valori = array_merge($valori, fundraising_f230_extract_data($_POST));
        $result = fundraising_f230_process_submission($pdo, $_POST, [
            'sursa' => 'online',
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
        if (!empty($result['success'])) {
            $_SESSION['fundraising_public_flash'] = [
                'succes' => 'Formularul a fost trimis cu succes. Vă mulțumim!',
                'warning' => (string)($result['warning'] ?? ''),
            ];
            header('Location: /fundraising/formular-230-public?trimis=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Formularul nu a putut fi trimis.');
    }
}

include APP_ROOT . '/app/views/fundraising/public-form.php';
