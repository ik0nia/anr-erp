<?php
/**
 * Controller: Log Activitate — Lista cu paginare si filtrare dupa data
 *
 * GET: Afiseaza log-ul de activitate cu paginare si filtru pe interval de date
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/db_helper.php';

// --- GET: Parametri ---
$per_page = (int)($_GET['per_page'] ?? 50);
if (!in_array($per_page, [25, 50, 100])) $per_page = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$data_de_la = trim($_GET['data_de_la'] ?? '');
$data_pana_la = trim($_GET['data_pana_la'] ?? '');

$logs = [];
$total = 0;
$total_pages = 1;
$eroare_bd = '';

try {
    $where = [];
    $params = [];

    if ($data_de_la !== '') {
        $where[] = 'data_ora >= ?';
        $params[] = $data_de_la . ' 00:00:00';
    }
    if ($data_pana_la !== '') {
        $where[] = 'data_ora <= ?';
        $params[] = $data_pana_la . ' 23:59:59';
    }

    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $count_sql = "SELECT COUNT(*) FROM log_activitate {$where_sql}";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total / $per_page));
    if ($page > $total_pages) $page = $total_pages;

    $offset = ($page - 1) * $per_page;
    $sql = "SELECT id, data_ora, utilizator, actiune FROM log_activitate {$where_sql} ORDER BY data_ora DESC LIMIT {$per_page} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eroare_bd = 'Tabelul log_activitate nu există. Rulați schema_log_activitate.sql în baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '.';
}

// Helper URL paginare
function build_log_url($params = []) {
    $p = array_merge($_GET, $params);
    $p['page'] = $p['page'] ?? 1;
    return '/log-activitate?' . http_build_query($p);
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/log-activitate/index.php';
