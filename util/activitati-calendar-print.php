<?php
/**
 * Print "Calendar activități" (activități viitoare).
 * Include antetul configurabil din Setări > Antet documente pe fiecare pagină.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/ActivitatiService.php';
require_once __DIR__ . '/../includes/document_helper.php';

$ziua_curenta = date('Y-m-d');
$rezultat = activitati_list_viitoare_overview($pdo, $ziua_curenta);
$activitati = $rezultat['activitati'] ?? [];

function activitati_calendar_print_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar activitati</title>
    <style>
        @page {
            size: A4;
            margin: 40mm 12mm 12mm 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
        }
        <?php echo documente_antet_print_css(); ?>
        .print-fixed-header {
            display: none;
        }
        .content-wrap {
            margin-top: 0;
        }
        .no-print {
            margin-bottom: 10px;
            text-align: right;
        }
        .no-print button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 0;
            border-radius: 6px;
            background: #b45309;
            color: #fff;
            cursor: pointer;
            font-weight: 600;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px;
            text-align: center;
        }
        .sub {
            margin: 0 0 12px;
            color: #333;
            text-align: center;
        }
        .list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .item {
            border: 1px solid #333;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .item-title {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 4px;
        }
        .item-meta {
            margin: 0;
            font-size: 11px;
            color: #1f2937;
        }
        .empty {
            border: 1px dashed #666;
            border-radius: 6px;
            padding: 12px;
            color: #666;
            font-style: italic;
        }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-fixed-header {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #fff;
                padding: 6mm 12mm 4mm 12mm;
                z-index: 999;
            }
            .content-wrap {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();" aria-label="Tipărește calendar activități">
            <span aria-hidden="true">🖨️</span>
            Tipărește
        </button>
    </div>

    <div class="print-fixed-header" aria-hidden="true">
        <?php echo documente_antet_render($pdo); ?>
    </div>

    <div class="content-wrap">
        <?php echo documente_antet_render($pdo); ?>
        <p class="title">Calendar activitati</p>
        <p class="sub">Data tipăririi: <?php echo activitati_calendar_print_h(date('d.m.Y H:i')); ?></p>

        <?php if (!empty($activitati)): ?>
            <ul class="list" aria-label="Calendar activități programate pe viitor">
                <?php foreach ($activitati as $a): ?>
                    <?php
                    $dt = new DateTime((string)$a['data_ora']);
                    $ora = $dt->format(TIME_FORMAT);
                    if (!empty($a['ora_finalizare'])) {
                        $ora_fin = is_object($a['ora_finalizare']) ? $a['ora_finalizare'] : new DateTime((string)$a['ora_finalizare']);
                        if ($ora_fin instanceof DateTime) {
                            $ora .= '-' . $ora_fin->format(TIME_FORMAT);
                        }
                    }
                    ?>
                    <li class="item">
                        <p class="item-title"><?php echo activitati_calendar_print_h((string)($a['nume'] ?? 'Activitate')); ?></p>
                        <p class="item-meta">
                            <?php echo activitati_calendar_print_h(data_cu_ziua_ro($dt)); ?>, <?php echo activitati_calendar_print_h($ora); ?>
                            <?php if (!empty($a['locatie'])): ?>
                                — <?php echo activitati_calendar_print_h((string)$a['locatie']); ?>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty">Nu există activități programate în viitor.</div>
        <?php endif; ?>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
