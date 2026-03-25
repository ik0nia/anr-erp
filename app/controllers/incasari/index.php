<?php
/**
 * Controller: Încasări — Lista tuturor încasărilor cu paginare și filtrare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';

// --- GET: Parametri ---
$per_page = (int)($_GET['per_page'] ?? 50);
if (!in_array($per_page, [25, 50, 100])) $per_page = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$tip_filtru = trim($_GET['tip'] ?? '');
$serie_filtru = trim($_GET['serie'] ?? '');
$data_de_la = trim($_GET['data_de_la'] ?? '');
$data_pana_la = trim($_GET['data_pana_la'] ?? '');
$cautare = trim($_GET['q'] ?? '');
$azi = date('Y-m-d');
$prima_zi_luna = date('Y-m-01');
if ($data_de_la === '') {
    $data_de_la = $prima_zi_luna;
}
if ($data_pana_la === '') {
    $data_pana_la = $azi;
}

$incasari = [];
$total = 0;
$total_pages = 1;
$total_suma_afisata = 0.0;
$total_chitante_afisate = 0;
$afiseaza_resetare_filtre = isset($_GET['tip']) || isset($_GET['serie']) || isset($_GET['data_de_la']) || isset($_GET['data_pana_la']) || isset($_GET['q']) || isset($_GET['per_page']) || isset($_GET['page']);
$serie_options = [];

try {
    incasari_ensure_tables($pdo);
    ensure_contacte_table($pdo);
    $serie_donatii = trim((string)((incasari_get_serie($pdo, 'donatii')['serie'] ?? '')));
    $serie_incasari = trim((string)((incasari_get_serie($pdo, 'incasari')['serie'] ?? '')));
    foreach ([$serie_donatii, $serie_incasari] as $serie_cfg) {
        if ($serie_cfg !== '' && !in_array($serie_cfg, $serie_options, true)) {
            $serie_options[] = $serie_cfg;
        }
    }
    if ($serie_filtru !== '' && !in_array($serie_filtru, $serie_options, true)) {
        $serie_filtru = '';
    }

    $where = [];
    $params = [];

    if ($tip_filtru !== '' && in_array($tip_filtru, [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE])) {
        $where[] = 'i.tip = ?';
        $params[] = $tip_filtru;
    }
    if ($serie_filtru !== '') {
        $where[] = 'i.seria_chitanta = ?';
        $params[] = $serie_filtru;
    }
    if ($data_de_la !== '') {
        $where[] = 'i.data_incasare >= ?';
        $params[] = $data_de_la;
    }
    if ($data_pana_la !== '') {
        $where[] = 'i.data_incasare <= ?';
        $params[] = $data_pana_la;
    }
    if ($cautare !== '') {
        $where[] = '(COALESCE(m.nume, c.nume, \'\') LIKE ? OR COALESCE(m.prenume, c.prenume, \'\') LIKE ? OR i.seria_chitanta LIKE ?)';
        $params[] = "%{$cautare}%";
        $params[] = "%{$cautare}%";
        $params[] = "%{$cautare}%";
    }

    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $count_sql = "SELECT COUNT(*) FROM incasari i LEFT JOIN membri m ON m.id = i.membru_id LEFT JOIN contacte c ON c.id = i.contact_id {$where_sql}";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total / $per_page));
    if ($page > $total_pages) $page = $total_pages;

    $offset = ($page - 1) * $per_page;
    $sql = "
        SELECT i.*,
               COALESCE(m.nume, c.nume) AS nume,
               COALESCE(m.prenume, c.prenume) AS prenume
        FROM incasari i
        LEFT JOIN membri m ON m.id = i.membru_id
        LEFT JOIN contacte c ON c.id = i.contact_id
        {$where_sql}
        ORDER BY i.data_incasare DESC, i.id DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $incasari = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($incasari as $incasare) {
        $total_suma_afisata += (float)($incasare['suma'] ?? 0);
        if (!empty($incasare['seria_chitanta'])) {
            $total_chitante_afisate++;
        }
    }
} catch (PDOException $e) {
    error_log('Eroare încasări: ' . $e->getMessage());
}

$tipuri_afisare = incasari_tipuri_afisare();
$moduri_plata_afisare = incasari_moduri_plata_afisare();

// Helper URL paginare
function build_incasari_url($params = []) {
    $p = array_merge($_GET, $params);
    $p['page'] = $p['page'] ?? 1;
    return '/incasari?' . http_build_query($p);
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/incasari/index.php';
