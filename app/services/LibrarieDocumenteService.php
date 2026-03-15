<?php
/**
 * LibrarieDocumenteService — Business logic pentru modulul Librărie documente.
 *
 * Wrap-uri peste helper-ul librarie_documente_helper.php + logica de serviciu.
 * Nu accesează $_GET, $_POST, $_SESSION direct.
 * Nu generează HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/librarie_documente_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Mesaje de succes mapate pe cod.
 */
function librarie_documente_succes_mesaje(): array {
    return [
        '1' => 'Document încărcat în librărie.',
        '2' => 'Document șters.',
        '3' => 'Document actualizat.',
    ];
}

/**
 * Returnează mesajul de succes pentru un cod dat, sau fallback.
 */
function librarie_documente_succes_mesaj(string $cod): string {
    $mesaje = librarie_documente_succes_mesaje();
    return $mesaje[$cod] ?? 'Operațiune reușită.';
}

/**
 * Încarcă datele necesare pentru view (lista documente + base_url).
 *
 * @return array ['lista' => array, 'base_url' => string]
 */
function librarie_documente_load_data(PDO $pdo): array {
    $lista = librarie_documente_lista($pdo);
    $base_url = defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '';
    return [
        'lista'    => $lista,
        'base_url' => $base_url,
    ];
}

/**
 * Reordonează documentele.
 *
 * @return array ['ok' => bool]
 */
function librarie_documente_service_reordoneaza(PDO $pdo, array $ids): array {
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function ($id) { return $id > 0; });
    if (empty($ids)) {
        return ['ok' => false];
    }
    librarie_documente_reordoneaza($pdo, array_values($ids));
    log_activitate($pdo, 'Librărie documente: Reordonare documente');
    return ['ok' => true];
}

/**
 * Încarcă un document nou în librărie.
 *
 * @return array ['success' => bool, 'error' => string|null]
 */
function librarie_documente_service_incarca(PDO $pdo, string $institutie, string $nume_document, array $file): array {
    if (empty($institutie) || empty($nume_document)) {
        return ['success' => false, 'error' => 'Instituția și numele documentului sunt obligatorii.'];
    }
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Selectați un fișier valid (Word, Excel sau PDF, max. ' . LIBRARIE_DOC_MAX_MB . ' MB).'];
    }
    $id = librarie_documente_adauga($pdo, $institutie, $nume_document, $file);
    if ($id > 0) {
        log_activitate($pdo, "Librărie documente: Document adăugat – {$nume_document} / {$institutie}");
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Eroare la încărcare. Verificați formatul (Word, Excel, PDF) și mărimea (max. ' . LIBRARIE_DOC_MAX_MB . ' MB).'];
}

/**
 * Șterge un document din librărie.
 *
 * @return array ['success' => bool, 'error' => string|null]
 */
function librarie_documente_service_sterge(PDO $pdo, int $id): array {
    if ($id <= 0) {
        return ['success' => false, 'error' => 'ID document invalid.'];
    }
    $doc = librarie_documente_get($pdo, $id);
    if (!$doc) {
        return ['success' => false, 'error' => 'Documentul nu a fost găsit.'];
    }
    $result = librarie_documente_sterge($pdo, $id);
    if ($result) {
        log_activitate($pdo, "Librărie documente: Document șters – {$doc['nume_document']} / {$doc['institutie']}");
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Eroare la ștergerea documentului.'];
}

/**
 * Actualizează un document (instituție + nume).
 *
 * @return array ['success' => bool, 'error' => string|null]
 */
function librarie_documente_service_actualizeaza(PDO $pdo, int $id, string $institutie, string $nume_document): array {
    $doc = $id > 0 ? librarie_documente_get($pdo, $id) : null;
    if (!$doc || $institutie === '' || $nume_document === '') {
        return ['success' => false, 'error' => 'Date invalide pentru actualizare.'];
    }
    $vechi_i = $doc['institutie'];
    $vechi_n = $doc['nume_document'];
    librarie_documente_actualizeaza($pdo, $id, $institutie, $nume_document);
    log_activitate($pdo, log_format_modificare('Librărie documente – Instituție', $vechi_i, $institutie, $nume_document));
    log_activitate($pdo, log_format_modificare('Librărie documente – Nume document', $vechi_n, $nume_document, $institutie));
    return ['success' => true, 'error' => null];
}
