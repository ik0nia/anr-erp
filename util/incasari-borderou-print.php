<?php
/**
 * Tipărire borderou încasări (tabelul afișat în modulul Încasări).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/contacte_helper.php';

incasari_ensure_tables($pdo);
ensure_contacte_table($pdo);

$per_page = (int)($_GET['per_page'] ?? 50);
if (!in_array($per_page, [25, 50, 100], true)) {
    $per_page = 50;
}
$page = max(1, (int)($_GET['page'] ?? 1));
$tip_filtru = trim((string)($_GET['tip'] ?? ''));
$data_de_la = trim((string)($_GET['data_de_la'] ?? ''));
$data_pana_la = trim((string)($_GET['data_pana_la'] ?? ''));
$cautare = trim((string)($_GET['q'] ?? ''));

if ($data_de_la === '') {
    $data_de_la = date('Y-m-01');
}
if ($data_pana_la === '') {
    $data_pana_la = date('Y-m-d');
}

$where = [];
$params = [];
if ($tip_filtru !== '' && in_array($tip_filtru, [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE], true)) {
    $where[] = 'i.tip = ?';
    $params[] = $tip_filtru;
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

$count_sql = "SELECT COUNT(*) FROM incasari i LEFT JOIN membri m ON m.id = i.membru_id LEFT JOIN contacte c ON c.id = i.contact_id {$where_sql}";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total = (int)$stmt_count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}

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

$tipuri_afisare = incasari_tipuri_afisare();
$moduri_plata_afisare = incasari_moduri_plata_afisare();

$total_suma_afisata = 0.0;
$total_chitante_afisate = 0;
$serii_gasite = [];

foreach ($incasari as $inc) {
    $total_suma_afisata += (float)($inc['suma'] ?? 0);
    if (!empty($inc['seria_chitanta'])) {
        $total_chitante_afisate++;
        $serii_gasite[(string)$inc['seria_chitanta']] = true;
    }
}

if ($tip_filtru === INCASARI_TIP_DONATIE) {
    $serie_selectata = (string)((incasari_get_serie($pdo, 'donatii')['serie'] ?? '') ?: 'D');
} elseif ($tip_filtru !== '') {
    $serie_selectata = (string)((incasari_get_serie($pdo, 'incasari')['serie'] ?? '') ?: 'INC');
} elseif (!empty($serii_gasite)) {
    $serie_selectata = implode(', ', array_keys($serii_gasite));
} else {
    $serie_selectata = 'Toate';
}

$titlu_borderou = 'Borderou Chitante, Seria ' . $serie_selectata . ', Perioada ' . date('d.m.Y', strtotime($data_de_la)) . ' - ' . date('d.m.Y', strtotime($data_pana_la));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titlu_borderou); ?></title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
        }
        .no-print {
            margin-bottom: 10px;
            text-align: right;
        }
        .no-print button {
            padding: 8px 14px;
            border: 0;
            border-radius: 6px;
            background: #b45309;
            color: #fff;
            cursor: pointer;
        }
        .title {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        .sub {
            margin: 0 0 12px 0;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            display: table-header-group;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top;
        }
        th {
            background: #f1f1f1;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        td.num, th.num {
            text-align: right;
            white-space: nowrap;
        }
        tfoot td {
            font-weight: 700;
            background: #f7f7f7;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();">Tipărește</button>
    </div>

    <p class="title"><?php echo htmlspecialchars($titlu_borderou); ?></p>
    <p class="sub">Afișare: pagina <?php echo (int)$page; ?> din <?php echo (int)$total_pages; ?>, <?php echo (int)$total; ?> înregistrări filtrate</p>

    <table aria-label="Borderou încasări">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tip</th>
                <th>Persoană</th>
                <th class="num">Sumă</th>
                <th>Mod plată</th>
                <th>Chitanță</th>
                <th>Reprezentând</th>
                <th>Înregistrat de</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($incasari)): ?>
                <tr>
                    <td colspan="8">Nu există încasări pentru filtrele selectate.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($incasari as $inc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d.m.Y', strtotime((string)$inc['data_incasare']))); ?></td>
                        <td><?php echo htmlspecialchars($tipuri_afisare[$inc['tip']] ?? (string)$inc['tip']); ?></td>
                        <td><?php echo htmlspecialchars(trim((string)($inc['nume'] ?? '') . ' ' . (string)($inc['prenume'] ?? '')) ?: '-'); ?></td>
                        <td class="num"><?php echo htmlspecialchars(number_format((float)$inc['suma'], 2, ',', '.')); ?> RON</td>
                        <td><?php echo htmlspecialchars($moduri_plata_afisare[$inc['mod_plata']] ?? (string)$inc['mod_plata']); ?></td>
                        <td><?php echo !empty($inc['seria_chitanta']) ? htmlspecialchars((string)$inc['seria_chitanta'] . ' nr. ' . (int)$inc['nr_chitanta']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars((string)($inc['reprezentand'] ?? '-')); ?></td>
                        <td><?php echo htmlspecialchars((string)($inc['created_by'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total chitanțe (tabel afișat)</td>
                <td class="num"><?php echo htmlspecialchars(number_format($total_suma_afisata, 2, ',', '.')); ?> RON</td>
                <td colspan="4"><?php echo (int)$total_chitante_afisate; ?> chitanțe numerotate</td>
            </tr>
        </tfoot>
    </table>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
