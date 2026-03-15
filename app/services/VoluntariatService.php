<?php
/**
 * VoluntariatService — Business logic pentru modulul Voluntariat.
 *
 * Toate operatiile CRUD + validare + logging.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/voluntariat_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Mesaje succes mapate pe coduri.
 */
function voluntariat_mesaje_succes(): array {
    return [
        'mesaj'       => 'Mesajul pentru voluntari a fost salvat.',
        'mesaj_sters' => 'Mesajul pentru voluntari a fost șters.',
        'template'    => 'Template-ul pentru contract a fost setat.',
        'voluntar'    => 'Voluntarul a fost adăugat. Contractul a fost generat și înregistrat.',
        'actualizat'  => 'Voluntarul a fost actualizat.',
        'activitate'  => 'Activitatea a fost adăugată.',
        'participant' => 'Voluntarul a fost adăugat în activitate.',
    ];
}

/**
 * Salvează mesajul pentru voluntari.
 */
function voluntariat_salveaza_mesaj(PDO $pdo, string $mesaj, string $tab): void {
    voluntariat_set_mesaj_zilei($pdo, $mesaj);
    log_activitate($pdo, 'Voluntariat: Mesaj pentru voluntari salvat.');
    header('Location: /voluntariat?tab=' . urlencode($tab) . '&succes=mesaj');
    exit;
}

/**
 * Șterge mesajul pentru voluntari.
 */
function voluntariat_sterge_mesaj(PDO $pdo, string $tab): void {
    voluntariat_sterge_mesaj_zilei($pdo);
    log_activitate($pdo, 'Voluntariat: Mesaj pentru voluntari șters.');
    header('Location: /voluntariat?tab=' . urlencode($tab) . '&succes=mesaj_sters');
    exit;
}

/**
 * Setează template-ul de contract.
 */
function voluntariat_seteaza_template(PDO $pdo, int $tid): void {
    $stmt = $pdo->prepare("INSERT INTO setari (cheie, valoare) VALUES ('voluntariat_template_contract_id', ?) ON DUPLICATE KEY UPDATE valoare = ?, updated_at = CURRENT_TIMESTAMP");
    $val = $tid ? (string)$tid : '';
    $stmt->execute([$val, $val]);
    header('Location: /voluntariat?tab=nomenclator&succes=template');
    exit;
}

/**
 * Procesează adăugare voluntar.
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function voluntariat_store_voluntar(PDO $pdo, array $data): array {
    return voluntariat_adauga_voluntar($pdo, $data);
}

/**
 * Procesează actualizare voluntar.
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function voluntariat_update_voluntar(PDO $pdo, int $id, array $data): array {
    if ($id <= 0) {
        return ['success' => false, 'error' => 'ID invalid.'];
    }
    $ok = voluntariat_actualizeaza_voluntar($pdo, $id, $data);
    if ($ok) {
        log_activitate($pdo, 'Voluntariat: Voluntar actualizat ID ' . $id);
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Eroare la actualizare.'];
}

/**
 * Procesează adăugare activitate.
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function voluntariat_store_activitate(PDO $pdo, string $nume, string $data_activitate, ?string $ora_inceput, ?string $ora_sfarsit): array {
    $nume = trim($nume);
    $data_activitate = trim($data_activitate);
    if ($nume === '' || $data_activitate === '') {
        return ['success' => false, 'error' => 'Numele și data activității sunt obligatorii.'];
    }
    $aid = voluntariat_adauga_activitate($pdo, $nume, $data_activitate, $ora_inceput, $ora_sfarsit);
    if ($aid) {
        log_activitate($pdo, 'Voluntariat: Activitate adăugată - ' . $nume . ' (' . $data_activitate . ')');
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Eroare la salvare activitate.'];
}

/**
 * Procesează adăugare participant la activitate.
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function voluntariat_store_participant(PDO $pdo, int $aid, int $vid, ?float $ore): array {
    if ($aid <= 0 || $vid <= 0) {
        return ['success' => false, 'error' => 'Selectați activitatea și voluntarul.'];
    }
    $ok = voluntariat_adauga_participant($pdo, $aid, $vid, $ore);
    if ($ok) {
        log_activitate($pdo, 'Voluntariat: Voluntar adăugat în activitate ID ' . $aid);
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Selectați activitatea și voluntarul.'];
}

/**
 * Încarcă toate datele necesare pentru view.
 */
function voluntariat_load_data(PDO $pdo): array {
    $mesaj_zilei = voluntariat_get_mesaj_zilei($pdo);
    $lista_voluntari = voluntariat_lista_voluntari($pdo);
    $lista_activitati = voluntariat_lista_activitati($pdo);
    $registru_ore = voluntariat_registru_ore($pdo);

    $templates_doc = [];
    try {
        $stmt = $pdo->query("SELECT id, nume_afisare FROM documente_template WHERE activ = 1 ORDER BY nume_afisare");
        $templates_doc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    $template_contract_id = voluntariat_get_template_contract_id($pdo);

    return compact('mesaj_zilei', 'lista_voluntari', 'lista_activitati', 'registru_ore', 'templates_doc', 'template_contract_id');
}
