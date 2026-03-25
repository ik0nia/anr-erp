<?php
/**
 * Helper Încasări - cotizații, donații, taxe participare, alte încasări. Chitanțe, serii, integrare FGO.ro.
 * CRM ANR Bihor
 */

if (!defined('INCASARI_TIP_COTIZATIE')) {
    define('INCASARI_TIP_COTIZATIE', 'cotizatie');
    define('INCASARI_TIP_DONATIE', 'donatie');
    define('INCASARI_TIP_TAXA_PARTICIPARE', 'taxa_participare');
    define('INCASARI_TIP_ALTE', 'alte');
}
if (!defined('INCASARI_MOD_NUMERAR')) {
    define('INCASARI_MOD_NUMERAR', 'numerar');
    define('INCASARI_MOD_CARD_POS', 'card_pos');
    define('INCASARI_MOD_CARD_ONLINE', 'card_online');
    define('INCASARI_MOD_TRANSFER', 'transfer_bancar');
    define('INCASARI_MOD_CHITANTA_VECHE', 'chitanta_veche');
    define('INCASARI_MOD_MANDAT_POSTAL', 'mandat_postal');
}

function incasari_ensure_tables($pdo) {
    // No-op: schema is managed by install/schema/migration.php
    return;
}

/** Verifică dacă membru a achitat cotizația pentru anul dat (inclusiv scutit). */
function incasari_cotizatie_achitata_an($pdo, $membru_id, $anul) {
    require_once __DIR__ . '/cotizatii_helper.php';
    if (cotizatii_membru_este_scutit($pdo, $membru_id)) return true;
    $anul = (int) $anul;
    $stmt = $pdo->prepare("SELECT 1 FROM incasari WHERE membru_id = ? AND tip = 'cotizatie' AND anul = ? LIMIT 1");
    $stmt->execute([(int)$membru_id, $anul]);
    return $stmt->fetch() ? true : false;
}

/** Valoare cotizație pentru an, grad și asistent personal (din cotizatii_anuale). $asistent_personal default „Fara asistent personal”. */
function incasari_valoare_cotizatie_anuala($pdo, $anul, $grad_handicap, $asistent_personal = 'Fara asistent personal') {
    require_once __DIR__ . '/cotizatii_helper.php';
    cotizatii_ensure_tables($pdo);
    $asistent_personal = trim((string)$asistent_personal);
    $stmt = $pdo->prepare("SELECT valoare_cotizatie FROM cotizatii_anuale WHERE anul = ? AND grad_handicap = ? AND asistent_personal = ? LIMIT 1");
    $stmt->execute([(int)$anul, $grad_handicap, $asistent_personal]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (float)$row['valoare_cotizatie'];
    if ($asistent_personal !== '') {
        $stmt = $pdo->prepare("SELECT valoare_cotizatie FROM cotizatii_anuale WHERE anul = ? AND grad_handicap = ? AND (asistent_personal = '' OR asistent_personal IS NULL) LIMIT 1");
        $stmt->execute([(int)$anul, $grad_handicap]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $row ? (float)$row['valoare_cotizatie'] : 0;
}

/** Returnează seria și următorul nr pentru tip_serie (donatii / incasari). */
function incasari_urmatorul_nr_serie($pdo, $tip_serie) {
    incasari_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT serie, nr_curent, nr_start FROM incasari_serii WHERE tip_serie = ? FOR UPDATE");
    $stmt->execute([$tip_serie]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['serie' => '', 'nr' => 1, 'error' => null];
    $nr_start = max(1, (int)($row['nr_start'] ?? 1));
    $nr = (int)($row['nr_curent'] ?? $nr_start);
    if ($nr < $nr_start) {
        $nr = $nr_start;
    }
    $nr_final = incasari_get_serie_nr_final($pdo, $tip_serie);
    if ($nr_final > 0 && $nr > $nr_final) {
        return [
            'serie' => (string)$row['serie'],
            'nr' => null,
            'error' => 'Intervalul de numerotare pentru seria ' . (string)$row['serie'] . ' a fost epuizat.',
        ];
    }
    $pdo->prepare("UPDATE incasari_serii SET nr_curent = nr_curent + 1 WHERE tip_serie = ?")->execute([$tip_serie]);
    return ['serie' => $row['serie'], 'nr' => $nr, 'error' => null];
}

/** Tip serie chitanță în funcție de tip încasare: cotizație -> incasari, restul -> donatii */
function incasari_tip_serie_pentru_tip($tip) {
    return $tip === INCASARI_TIP_COTIZATIE ? 'incasari' : 'donatii';
}

/**
 * Înregistrează o încasare. Pentru numerar generează seria și nr chitanță.
 * membru_id poate fi null când contact_id este setat (donație de la donator extern).
 * Returnează id încasare sau false.
 */
function incasari_adauga($pdo, $membru_id, $tip, $anul, $suma, $mod_plata, $data_incasare, $created_by, $observatii = null, $contact_id = null, $reprezentand = null) {
    incasari_ensure_tables($pdo);
    $membru_id = $membru_id !== null && $membru_id !== '' ? (int)$membru_id : null;
    $contact_id = $contact_id !== null && $contact_id !== '' ? (int)$contact_id : null;
    $suma = (float)str_replace(',', '.', $suma);
    $anul = $anul !== null && $anul !== '' ? (int)$anul : null;
    $reprezentand = trim((string)$reprezentand) ?: null;
    $seria = null;
    $nr_chitanta = null;
    // Generate receipt series/number for cash and old receipt payment methods
    $metode_cu_chitanta = [INCASARI_MOD_NUMERAR, INCASARI_MOD_CHITANTA_VECHE];
    if (in_array($mod_plata, $metode_cu_chitanta)) {
        $tip_serie = incasari_tip_serie_pentru_tip($tip);
        $next = incasari_urmatorul_nr_serie($pdo, $tip_serie);
        if (empty($next['nr'])) {
            return false;
        }
        $seria = $next['serie'];
        $nr_chitanta = $next['nr'];
    }
    $stmt = $pdo->prepare("INSERT INTO incasari (membru_id, contact_id, tip, anul, suma, mod_plata, data_incasare, seria_chitanta, nr_chitanta, reprezentand, observatii, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$membru_id, $contact_id, $tip, $anul, $suma, $mod_plata, $data_incasare, $seria, $nr_chitanta, $reprezentand, $observatii ?: null, $created_by ?: null]);
    return (int)$pdo->lastInsertId();
}

function incasari_get($pdo, $id) {
    incasari_ensure_tables($pdo);
    require_once __DIR__ . '/contacte_helper.php';
    ensure_contacte_table($pdo);
    $stmt = $pdo->prepare("
        SELECT i.*,
               COALESCE(m.nume, c.nume) AS nume,
               COALESCE(m.prenume, c.prenume) AS prenume,
               COALESCE(m.cnp, c.cnp) AS cnp,
               m.domloc, m.judet_domiciliu
        FROM incasari i
        LEFT JOIN membri m ON m.id = i.membru_id
        LEFT JOIN contacte c ON c.id = i.contact_id
        WHERE i.id = ?
    ");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function incasari_lista_membru($pdo, $membru_id, $limit = 100) {
    $limit = (int)$limit;
    $stmt = $pdo->prepare("SELECT * FROM incasari WHERE membru_id = ? ORDER BY data_incasare DESC, id DESC LIMIT " . $limit);
    $stmt->execute([(int)$membru_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Listă ID-uri membri care au achitat cotizația pentru anul dat (pentru afișare „Cotizație achitată”). */
function incasari_membri_cotizatie_achitata_an($pdo, $anul) {
    $stmt = $pdo->prepare("SELECT DISTINCT membru_id FROM incasari WHERE tip = 'cotizatie' AND anul = ?");
    $stmt->execute([(int)$anul]);
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'membru_id');
}

/** Membri care au achitat cotizația pentru anul dat. */
function incasari_lista_cotizatii_an($pdo, $anul) {
    $stmt = $pdo->prepare("SELECT i.*, m.nume, m.prenume FROM incasari i LEFT JOIN membri m ON m.id = i.membru_id WHERE i.tip = 'cotizatie' AND i.anul = ? ORDER BY i.data_incasare DESC");
    $stmt->execute([(int)$anul]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function incasari_lista_tip($pdo, $tip, $limit = 200) {
    incasari_ensure_tables($pdo);
    require_once __DIR__ . '/contacte_helper.php';
    ensure_contacte_table($pdo);
    $stmt = $pdo->prepare("
        SELECT i.*, COALESCE(m.nume, c.nume) AS nume, COALESCE(m.prenume, c.prenume) AS prenume
        FROM incasari i
        LEFT JOIN membri m ON m.id = i.membru_id
        LEFT JOIN contacte c ON c.id = i.contact_id
        WHERE i.tip = ?
        ORDER BY i.data_incasare DESC, i.id DESC
        LIMIT ?
    ");
    $stmt->execute([$tip, (int)$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Listă toate donațiile încasate (membri + donatori externi) pentru Setări / Donațiile încasate. */
function incasari_lista_donatii($pdo, $limit = 500) {
    return incasari_lista_tip($pdo, INCASARI_TIP_DONATIE, $limit);
}

/**
 * Listă donatori – persoane (membri sau contacte) care au făcut cel puțin o donație.
 * Returnează pentru fiecare: nume, prenume, email, telefon, total donat, nr. donații, ultima donație.
 */
function incasari_lista_donatori($pdo, $limit = 500) {
    incasari_ensure_tables($pdo);
    require_once __DIR__ . '/contacte_helper.php';
    ensure_contacte_table($pdo);
    $stmt = $pdo->prepare("
        SELECT
            i.membru_id,
            i.contact_id,
            MAX(COALESCE(m.nume, c.nume)) AS nume,
            MAX(COALESCE(m.prenume, c.prenume)) AS prenume,
            MAX(COALESCE(m.email, c.email)) AS email,
            MAX(COALESCE(m.telefonnev, c.telefon)) AS telefon,
            SUM(i.suma) AS total_donat,
            COUNT(*) AS nr_donatii,
            MAX(i.data_incasare) AS ultima_donatie
        FROM incasari i
        LEFT JOIN membri m ON m.id = i.membru_id
        LEFT JOIN contacte c ON c.id = i.contact_id
        WHERE i.tip = ?
        GROUP BY i.membru_id, i.contact_id
        ORDER BY total_donat DESC, ultima_donatie DESC
        LIMIT ?
    ");
    $stmt->execute([INCASARI_TIP_DONATIE, (int)$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function incasari_get_serie($pdo, $tip_serie) {
    $stmt = $pdo->prepare("SELECT * FROM incasari_serii WHERE tip_serie = ?");
    $stmt->execute([$tip_serie]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function incasari_setare_cheie_serie_nr_final($tip_serie) {
    return 'incasari_serie_nr_final_' . preg_replace('/[^a-z0-9_]/i', '', (string)$tip_serie);
}

function incasari_get_serie_nr_final($pdo, $tip_serie) {
    $raw = incasari_get_setare($pdo, incasari_setare_cheie_serie_nr_final($tip_serie));
    if ($raw === null || $raw === '') return 0;
    return max(0, (int)$raw);
}

function incasari_set_serie_nr_final($pdo, $tip_serie, $nr_final) {
    $nr_final = max(0, (int)$nr_final);
    return incasari_set_setare($pdo, incasari_setare_cheie_serie_nr_final($tip_serie), (string)$nr_final);
}

function incasari_salveaza_serie($pdo, $tip_serie, $serie, $nr_start, $nr_curent, $nr_final = null) {
    incasari_ensure_tables($pdo);
    $nr_start = max(1, (int)$nr_start);
    $nr_curent = max($nr_start, (int)$nr_curent);
    if ($nr_final !== null) {
        $nr_final = max($nr_start, (int)$nr_final);
        if ($nr_curent > ($nr_final + 1)) {
            $nr_curent = $nr_final + 1;
        }
        incasari_set_serie_nr_final($pdo, $tip_serie, $nr_final);
    }
    $stmt = $pdo->prepare("INSERT INTO incasari_serii (tip_serie, serie, nr_start, nr_curent) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE serie = VALUES(serie), nr_start = VALUES(nr_start), nr_curent = VALUES(nr_curent)");
    $stmt->execute([$tip_serie, $serie, $nr_start, $nr_curent]);
    return true;
}

function incasari_get_setare($pdo, $cheie) {
    incasari_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT valoare FROM incasari_setari WHERE cheie = ?");
    $stmt->execute([$cheie]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['valoare'] : null;
}

function incasari_set_setare($pdo, $cheie, $valoare) {
    incasari_ensure_tables($pdo);
    $stmt = $pdo->prepare("INSERT INTO incasari_setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)");
    $stmt->execute([$cheie, $valoare]);
    return true;
}

/** Etichete tip încasare. */
function incasari_tipuri_afisare() {
    return [
        INCASARI_TIP_COTIZATIE => 'Cotizație',
        INCASARI_TIP_DONATIE => 'Donație',
        INCASARI_TIP_TAXA_PARTICIPARE => 'Taxă participare',
        INCASARI_TIP_ALTE => 'Alte venituri',
    ];
}

function incasari_moduri_plata_afisare() {
    return [
        INCASARI_MOD_NUMERAR => 'Chitanta ERP',
        INCASARI_MOD_CHITANTA_VECHE => 'Chitanta veche',
        INCASARI_MOD_CARD_POS => 'POS',
        INCASARI_MOD_TRANSFER => 'Transfer bancar',
        INCASARI_MOD_CARD_ONLINE => 'Plata online',
        INCASARI_MOD_MANDAT_POSTAL => 'Mandat postal',
    ];
}

/** Scrie suma în litere (RON) - variantă simplă în română. */
function incasari_suma_in_litere($suma) {
    $suma = round((float)$suma, 2);
    $int = (int)floor($suma);
    $zec = (int)round(($suma - $int) * 100);
    $sint = $int === 1 ? 'un' : (string)$int;
    if ($zec === 0) return $sint . ' lei';
    return $sint . ' lei și ' . $zec . '/100';
}

/**
 * Trimite datele chitanței către FGO.ro prin API.
 * Returnează true la succes, false la eroare. Payload-ul respectă un format generic; adaptați după documentația FGO.
 */
function incasari_trimite_fgo($pdo, $inc) {
    $api_key = incasari_get_setare($pdo, 'fgo_api_key');
    $api_url = incasari_get_setare($pdo, 'fgo_api_url');
    $merchant = incasari_get_setare($pdo, 'fgo_merchant_name');
    $tax_id = incasari_get_setare($pdo, 'fgo_merchant_tax_id');
    if (empty($api_url) || empty($api_key)) {
        return false;
    }
    $url = rtrim($api_url, '/') . '/chitanta'; // endpoint generic; verificați documentația FGO
    $payload = [
        'api_key' => $api_key,
        'merchant_name' => $merchant,
        'merchant_tax_id' => $tax_id,
        'seria' => $inc['seria_chitanta'] ?? '',
        'nr' => (int)($inc['nr_chitanta'] ?? 0),
        'suma' => (float)($inc['suma'] ?? 0),
        'data' => $inc['data_incasare'] ?? '',
        'tip' => $inc['tip'] ?? '',
        'nume_plătitor' => trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? '')),
        'cnp' => $inc['cnp'] ?? '',
    ];
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($payload),
            'timeout' => 10,
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false;
}
