<?php
/**
 * Tipărire Borderou legitimații membru.
 */
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/document_helper.php';
require_once APP_ROOT . '/includes/membri_legitimatii_helper.php';
require_once APP_ROOT . '/app/services/RapoarteService.php';

membri_legitimatii_ensure_table($pdo);

$an_curent = (int)date('Y');
$data_de_la = trim((string)($_GET['data_de_la'] ?? ($an_curent . '-01-01')));
$data_pana_la = trim((string)($_GET['data_pana_la'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_de_la)) {
    $data_de_la = $an_curent . '-01-01';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_pana_la)) {
    $data_pana_la = date('Y-m-d');
}
if ($data_de_la > $data_pana_la) {
    [$data_de_la, $data_pana_la] = [$data_pana_la, $data_de_la];
}

$raport = rapoarte_borderou_legitimatii($pdo, $data_de_la, $data_pana_la);
$rows = $raport['operatiuni'] ?? [];
$stats = $raport['statistici'] ?? ['total' => 0, 'nou' => 0, 'plina' => 0, 'pierduta' => 0];
$tipuri_actiuni = membri_legitimatii_tipuri_actiune();
$antet_html = documente_antet_render($pdo);
$antet_css = documente_antet_print_css();

log_activitate(
    $pdo,
    'Rapoarte: print Borderou legitimatii membru interval ' .
    date('d.m.Y', strtotime($data_de_la)) . ' - ' . date('d.m.Y', strtotime($data_pana_la))
);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Borderou legitimatii de membru</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
        }
        .no-print { text-align: right; margin-bottom: 10px; }
        .no-print button {
            padding: 8px 14px;
            border: 0;
            border-radius: 6px;
            background: #0b5d95;
            color: #fff;
            cursor: pointer;
        }
        <?php echo $antet_css; ?>
        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px;
        }
        .sub {
            margin: 0 0 12px;
            color: #333;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .stat {
            border: 1px solid #333;
            padding: 8px;
        }
        .stat .k { font-size: 10px; color: #444; }
        .stat .v { font-size: 16px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th, td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top;
        }
        th {
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 10px;
            text-align: left;
        }
        .num { text-align: right; white-space: nowrap; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();">Tipărește</button>
    </div>

    <?php echo $antet_html; ?>

    <p class="title"><strong>Borderou legitimatii de membru</strong></p>
    <p class="sub">Interval: <?php echo htmlspecialchars(date('d.m.Y', strtotime($data_de_la))); ?> - <?php echo htmlspecialchars(date('d.m.Y', strtotime($data_pana_la))); ?></p>

    <div class="stats">
        <div class="stat"><div class="k">Total legitimații</div><div class="v"><?php echo (int)$stats['total']; ?></div></div>
        <div class="stat"><div class="k">Legitimații membru nou</div><div class="v"><?php echo (int)($stats['nou'] ?? 0); ?></div></div>
        <div class="stat"><div class="k">Înlocuire legitimație plină</div><div class="v"><?php echo (int)($stats['plina'] ?? 0); ?></div></div>
        <div class="stat"><div class="k">Înlocuire legitimație pierdută</div><div class="v"><?php echo (int)($stats['pierduta'] ?? 0); ?></div></div>
    </div>

    <table aria-label="Borderou legitimatii membru">
        <thead>
            <tr>
                <th>Nr. crt.</th>
                <th>Data acțiunii</th>
                <th>Membru</th>
                <th>Număr dosar</th>
                <th>Tip acțiune</th>
                <th>Utilizator</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="6">Nu există înregistrări în intervalul selectat.</td></tr>
            <?php else: ?>
            <?php foreach ($rows as $idx => $row): ?>
            <tr>
                <td class="num"><?php echo (int)($idx + 1); ?></td>
                <td><?php echo htmlspecialchars((string)($row['data_actiune_display'] ?? date('d.m.Y', strtotime((string)$row['data_actiune'])))); ?></td>
                <td><?php echo htmlspecialchars((string)($row['membru_nume'] ?? '-')); ?></td>
                <td><?php echo htmlspecialchars((string)(($row['dosarnr'] ?? '') !== '' ? $row['dosarnr'] : '-')); ?></td>
                <td>
                    <?php
                    $tip = (string)($row['actiune'] ?? $row['tip_actiune'] ?? '');
                    echo htmlspecialchars((string)($tipuri_actiuni[$tip] ?? $tip));
                    ?>
                </td>
                <td><?php echo htmlspecialchars((string)(($row['utilizator'] ?? '') !== '' ? $row['utilizator'] : 'Sistem')); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
