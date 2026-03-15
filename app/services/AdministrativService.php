<?php
/**
 * AdministrativService — Business logic pentru modulul Administrativ.
 *
 * Achizitii, Echipa, Calendar administrativ, Consiliul Director,
 * Adunarea Generala, Juridic ANR, Parteneriate, Proceduri interne.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/administrativ_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Tab-urile valide ale modulului.
 */
function administrativ_valid_tabs(): array {
    return ['achizitii','echipa','calendar','cd','ag','juridic','parteneriate','proceduri'];
}

/**
 * Harta mesajelor de succes dupa redirect.
 */
function administrativ_success_messages(): array {
    return [
        'achizitie'       => 'Achizitia a fost adaugata.',
        'cumparat'        => 'Achizitia a fost marcata ca cumparata.',
        'sterge'          => 'Achizitia a fost stearsa.',
        'angajat'         => 'Angajatul a fost salvat.',
        'sterge_angajat'  => 'Angajatul a fost sters.',
        'cd'              => 'Membrul Consiliului Director a fost salvat.',
        'sterge_cd'       => 'Membrul C.D. a fost sters.',
        'ag'              => 'Membrul Adunarii Generale a fost salvat.',
        'sterge_ag'       => 'Membrul A.G. a fost sters.',
        'termen'          => 'Termenul din calendar a fost salvat.',
        'sterge_termen'   => 'Termenul din calendar a fost sters.',
        'sedinta'         => 'Sedinta a fost programata.',
        'juridic'         => 'Inregistrarea Juridic ANR a fost salvata.',
        'parteneriat'     => 'Parteneriatul a fost salvat.',
        'sterge_parteneriat' => 'Parteneriatul a fost sters.',
        'procedura'       => 'Procedura interna a fost salvata.',
        'sterge_procedura'=> 'Procedura a fost stearsa.',
    ];
}

/**
 * Incarca toate datele necesare pentru afisarea paginii.
 */
function administrativ_service_load_page_data(PDO $pdo): array {
    administrativ_ensure_tables($pdo);

    $lista_achizitii   = administrativ_achizitii_lista($pdo, false);
    $lista_istoric     = administrativ_achizitii_istoric($pdo, 50);
    $lista_angajati    = administrativ_angajati_lista($pdo);
    $lista_cd          = administrativ_cd_lista($pdo);
    $lista_ag          = administrativ_ag_lista($pdo);

    $data_start        = date('Y-m-d', strtotime('-1 year'));
    $data_end          = date('Y-m-d', strtotime('+2 years'));
    $lista_termene     = administrativ_calendar_lista($pdo, $data_start, $data_end);
    $lista_sedinte_cd  = administrativ_cd_sedinte_lista($pdo, $data_start, $data_end);
    $lista_sedinte_ag  = administrativ_ag_sedinte_lista($pdo, $data_start, $data_end);

    $lista_juridic      = administrativ_juridic_lista($pdo);
    $lista_parteneriate = administrativ_parteneriate_lista($pdo);

    $cautare_proceduri = isset($_GET['cautare_proceduri']) ? trim($_GET['cautare_proceduri']) : '';
    $lista_proceduri   = administrativ_proceduri_lista($pdo, $cautare_proceduri !== '' ? $cautare_proceduri : null);

    $edit_procedura_id = isset($_GET['edit_procedura']) ? (int)$_GET['edit_procedura'] : 0;
    $edit_procedura    = $edit_procedura_id > 0 ? administrativ_procedura_get($pdo, $edit_procedura_id) : null;

    $edit_angajat_id   = isset($_GET['edit_angajat']) ? (int)$_GET['edit_angajat'] : 0;
    $edit_angajat      = $edit_angajat_id > 0 ? administrativ_angajat_get($pdo, $edit_angajat_id) : null;

    $edit_cd_id        = isset($_GET['edit_cd']) ? (int)$_GET['edit_cd'] : 0;
    $edit_cd           = $edit_cd_id > 0 ? administrativ_cd_get($pdo, $edit_cd_id) : null;

    $edit_ag_id        = isset($_GET['edit_ag']) ? (int)$_GET['edit_ag'] : 0;
    $edit_ag           = $edit_ag_id > 0 ? administrativ_ag_get($pdo, $edit_ag_id) : null;

    $tipuri_doc        = administrativ_tipuri_document_calendar();
    $categorii_juridic = administrativ_categorii_juridic();

    return compact(
        'lista_achizitii', 'lista_istoric', 'lista_angajati',
        'lista_cd', 'lista_ag', 'lista_termene',
        'lista_sedinte_cd', 'lista_sedinte_ag',
        'lista_juridic', 'lista_parteneriate',
        'cautare_proceduri', 'lista_proceduri',
        'edit_procedura', 'edit_angajat', 'edit_cd', 'edit_ag',
        'tipuri_doc', 'categorii_juridic'
    );
}

// ---- POST action handlers ----

/**
 * Adauga achizitie.
 * @return array ['success'=>bool, 'redirect'=>string]
 */
function administrativ_service_adauga_achizitie(PDO $pdo, array $post, ?int $user_id): array {
    $denumire = trim($post['denumire'] ?? '');
    $locatie  = in_array($post['locatie'] ?? '', ['Sediu', 'Centru', 'Alta']) ? $post['locatie'] : null;
    $urgenta  = in_array($post['urgenta'] ?? '', ['normal', 'urgent', 'optional']) ? $post['urgenta'] : 'normal';
    $furnizor = trim($post['furnizor'] ?? '');
    if ($denumire === '') {
        return ['success' => false, 'redirect' => '/administrativ?tab=achizitii'];
    }
    administrativ_achizitie_adauga($pdo, $denumire, $locatie, $urgenta, $furnizor ?: null);
    log_activitate($pdo, 'Administrativ: achizitie adaugata ' . $denumire . ($locatie ? ' (Loc: ' . $locatie . ')' : '') . ' Urgenta: ' . $urgenta);
    if ($urgenta === 'urgent') {
        require_once APP_ROOT . '/includes/notificari_helper.php';
        notificari_adauga($pdo, [
            'titlu'      => 'Achizitie urgenta: ' . $denumire,
            'importanta' => 'Important',
            'continut'   => 'A fost adaugata o achizitie marcata ca urgenta in modulul Administrativ. Denumire: ' . $denumire . ($furnizor ? '. Furnizor: ' . $furnizor : ''),
            'trimite_email' => 0,
        ], null, $user_id);
    }
    return ['success' => true, 'redirect' => '/administrativ?tab=achizitii&succes=achizitie'];
}

function administrativ_service_marcheaza_cumparat(PDO $pdo, int $id): array {
    if ($id > 0 && administrativ_achizitie_marcheaza_cumparat($pdo, $id)) {
        log_activitate($pdo, 'Administrativ: achizitie marcata cumparata ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=achizitii&succes=cumparat'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=achizitii'];
}

function administrativ_service_sterge_achizitie(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_achizitie_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: achizitie stearsa ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=achizitii&succes=sterge'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=achizitii'];
}

function administrativ_service_salveaza_angajat(PDO $pdo, array $post): array {
    $id  = (int)($post['id_angajat'] ?? 0);
    $rid = administrativ_angajat_salveaza($pdo, $id, $post);
    if ($rid) {
        log_activitate($pdo, 'Administrativ: angajat salvat ID ' . $rid);
        return ['success' => true, 'redirect' => '/administrativ?tab=echipa&succes=angajat'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=echipa'];
}

function administrativ_service_sterge_angajat(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_angajat_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: angajat sters ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=echipa&succes=sterge_angajat'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=echipa'];
}

function administrativ_service_salveaza_cd(PDO $pdo, array $post): array {
    $id = (int)($post['id_cd'] ?? 0);
    administrativ_cd_salveaza($pdo, $id, $post['membru_id'] ?? null, $post['nume_manual'] ?? '', $post['prenume_manual'] ?? '', $post['functie'] ?? '', (int)($post['ordine'] ?? 0), $post['email'] ?? null, $post['telefon'] ?? null);
    log_activitate($pdo, 'Administrativ: membru Consiliul Director salvat');
    return ['success' => true, 'redirect' => '/administrativ?tab=echipa&succes=cd'];
}

function administrativ_service_sterge_cd(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_cd_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: membru C.D. sters ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=echipa&succes=sterge_cd'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=echipa'];
}

function administrativ_service_salveaza_ag(PDO $pdo, array $post): array {
    $id = (int)($post['id_ag'] ?? 0);
    administrativ_ag_salveaza($pdo, $id, $post['membru_id'] ?? null, $post['nume_manual'] ?? '', $post['prenume_manual'] ?? '', (int)($post['ordine'] ?? 0), $post['functie'] ?? null, $post['email'] ?? null, $post['telefon'] ?? null);
    log_activitate($pdo, 'Administrativ: membru Adunare Generala salvat');
    return ['success' => true, 'redirect' => '/administrativ?tab=echipa&succes=ag'];
}

function administrativ_service_sterge_ag(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_ag_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: membru A.G. sters ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=echipa&succes=sterge_ag'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=echipa'];
}

function administrativ_service_salveaza_termen(PDO $pdo, array $post): array {
    $id = (int)($post['id_termen'] ?? 0);
    administrativ_calendar_salveaza($pdo, $id, $post['nume'] ?? '', $post['data_inceput'] ?? '', $post['data_expirarii'] ?? '', $post['tip_document'] ?? 'alt_document', $post['observatii'] ?? null, !empty($post['angajat_id']) ? (int)$post['angajat_id'] : null);
    log_activitate($pdo, 'Administrativ: termen calendar salvat');
    return ['success' => true, 'redirect' => '/administrativ?tab=calendar&succes=termen'];
}

function administrativ_service_sterge_termen(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_calendar_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: termen calendar sters ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=calendar&succes=sterge_termen'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=calendar'];
}

function administrativ_service_adauga_sedinta_cd(PDO $pdo, array $post): array {
    $data = trim($post['data_sedinta'] ?? '');
    $ora  = trim($post['ora'] ?? '09:00');
    $loc  = trim($post['loc'] ?? '');
    if ($data) {
        administrativ_cd_sedinta_adauga($pdo, $data, $ora, $loc, !empty($post['creaza_activitate']));
        log_activitate($pdo, 'Administrativ: sedinta C.D. programata ' . $data);
        return ['success' => true, 'redirect' => '/administrativ?tab=cd&succes=sedinta'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=cd'];
}

function administrativ_service_adauga_sedinta_ag(PDO $pdo, array $post): array {
    $data = trim($post['data_sedinta'] ?? '');
    $ora  = trim($post['ora'] ?? '09:00');
    $loc  = trim($post['loc'] ?? '');
    if ($data) {
        administrativ_ag_sedinta_adauga($pdo, $data, $ora, $loc, !empty($post['creaza_activitate']));
        log_activitate($pdo, 'Administrativ: sedinta A.G. programata ' . $data);
        return ['success' => true, 'redirect' => '/administrativ?tab=ag&succes=sedinta'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=ag'];
}

function administrativ_service_adauga_juridic(PDO $pdo, array $post, ?int $user_id): array {
    $creaza           = !empty($post['creaza_task_todo']);
    $notif            = !empty($post['trimite_notificare_platforma']);
    $creaza_procedura = !empty($post['creaza_procedura_interna']);
    administrativ_juridic_adauga($pdo, $post, $creaza, $notif, $user_id, $creaza_procedura);
    log_activitate($pdo, 'Administrativ: inregistrare Juridic ANR adaugata');
    return ['success' => true, 'redirect' => '/administrativ?tab=juridic&succes=juridic'];
}

function administrativ_service_salveaza_parteneriat(PDO $pdo, array $post): array {
    $id = (int)($post['id_parteneriat'] ?? 0);
    administrativ_parteneriat_salveaza($pdo, $id, $post['nume_partener'] ?? '', $post['obiect_parteneriat'] ?? '', $post['data_inceput'] ?? null, $post['data_sfarsit'] ?? null, $post['observatii'] ?? null);
    log_activitate($pdo, 'Administrativ: parteneriat salvat');
    return ['success' => true, 'redirect' => '/administrativ?tab=parteneriate&succes=parteneriat'];
}

function administrativ_service_sterge_parteneriat(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_parteneriat_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: parteneriat sters ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=parteneriate&succes=sterge_parteneriat'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=parteneriate'];
}

function administrativ_service_salveaza_procedura(PDO $pdo, array $post, ?int $user_id): array {
    $id      = (int)($post['id_procedura'] ?? 0);
    $titlu   = $post['titlu'] ?? '';
    $continut = $post['continut'] ?? '';
    administrativ_procedura_salveaza($pdo, $id, $titlu, $continut);
    if (!empty($post['trimite_notificare_procedura'])) {
        require_once APP_ROOT . '/includes/notificari_helper.php';
        notificari_adauga($pdo, [
            'titlu'      => 'Procedura interna: ' . $titlu,
            'importanta' => 'Informativ',
            'continut'   => 'A fost adaugata/actualizata o procedura interna. ' . (mb_strlen($continut) > 200 ? mb_substr(strip_tags($continut), 0, 200) . '...' : strip_tags($continut)),
            'trimite_email' => 0,
        ], null, $user_id);
    }
    log_activitate($pdo, 'Administrativ: procedura interna salvata');
    return ['success' => true, 'redirect' => '/administrativ?tab=proceduri&succes=procedura'];
}

function administrativ_service_sterge_procedura(PDO $pdo, int $id): array {
    if ($id > 0) {
        administrativ_procedura_sterge($pdo, $id);
        log_activitate($pdo, 'Administrativ: procedura stearsa ID ' . $id);
        return ['success' => true, 'redirect' => '/administrativ?tab=proceduri&succes=sterge_procedura'];
    }
    return ['success' => false, 'redirect' => '/administrativ?tab=proceduri'];
}
