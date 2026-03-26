<?php
/**
 * Tipărire tabel din modulul Administrativ (tab-uri cu date tabelare).
 * Include antetul configurabil "Antet documente".
 */
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_ROOT . '/app/services/AdministrativService.php';
require_once APP_ROOT . '/includes/document_helper.php';
require_once APP_ROOT . '/includes/administrativ_helper.php';

$tab = isset($_GET['tab']) ? trim((string)$_GET['tab']) : 'achizitii';
$validTabs = ['achizitii', 'calendar', 'juridic', 'parteneriate', 'proceduri'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'achizitii';
}

$data = administrativ_service_load_page_data($pdo);
if ($tab === 'proceduri') {
    $q = trim((string)($_GET['cautare_proceduri'] ?? ''));
    if ($q !== '') {
        $data['lista_proceduri'] = administrativ_proceduri_lista($pdo, $q);
    }
}

function administrativ_print_h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$title = 'Administrativ - print';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo administrativ_print_h($title); ?></title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
        }
        <?php echo documente_antet_print_css(); ?>
        .no-print { margin-bottom: 10px; text-align: right; }
        .no-print button {
            padding: 8px 14px;
            border: 0;
            border-radius: 6px;
            background: #b45309;
            color: #fff;
            cursor: pointer;
        }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 8px; }
        .sub { margin: 0 0 12px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; text-align: left; }
        th { background: #f1f1f1; text-transform: uppercase; font-size: 10px; }
        .empty { color: #666; font-style: italic; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();">Tipărește</button>
    </div>

    <?php echo documente_antet_render($pdo); ?>

    <?php if ($tab === 'achizitii'): ?>
        <?php
        $title = 'Administrativ - Lista achiziții';
        $rows = (array)($data['lista_achizitii'] ?? []);
        $statusuri = administrativ_statusuri_achizitie();
        ?>
        <p class="title"><?php echo administrativ_print_h($title); ?></p>
        <p class="sub">Data tipăririi: <?php echo administrativ_print_h(date('d.m.Y H:i')); ?></p>
        <table aria-label="Lista achiziții">
            <thead>
                <tr>
                    <th>Produs</th>
                    <th>Locație</th>
                    <th>Urgență</th>
                    <th>Status</th>
                    <th>Furnizor</th>
                    <th>Data adăugării</th>
                    <th>Utilizator</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td class="empty" colspan="7">Nu există înregistrări.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo administrativ_print_h((string)($r['denumire'] ?? '')); ?></td>
                        <td><?php echo administrativ_print_h((string)($r['locatie'] ?? '-')); ?></td>
                        <td><?php echo administrativ_print_h((string)($r['urgenta'] ?? '-')); ?></td>
                        <td>
                            <?php
                            $status = administrativ_normalize_status_achizitie((string)($r['status_achizitie'] ?? ''));
                            echo administrativ_print_h((string)($statusuri[$status] ?? $status));
                            ?>
                        </td>
                        <td><?php echo administrativ_print_h((string)($r['furnizor'] ?? '-')); ?></td>
                        <td><?php echo !empty($r['data_adaugare']) ? administrativ_print_h(date('d.m.Y', strtotime((string)$r['data_adaugare']))) : '-'; ?></td>
                        <td><?php echo administrativ_print_h((string)($r['added_by'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php elseif ($tab === 'calendar'): ?>
        <?php
        $title = 'Administrativ - Calendar administrativ';
        $rows = (array)($data['lista_termene'] ?? []);
        $tipuri = administrativ_tipuri_document_calendar();
        ?>
        <p class="title"><?php echo administrativ_print_h($title); ?></p>
        <p class="sub">Data tipăririi: <?php echo administrativ_print_h(date('d.m.Y H:i')); ?></p>
        <table aria-label="Calendar administrativ">
            <thead>
                <tr>
                    <th>Denumire</th>
                    <th>Tip document</th>
                    <th>Data început</th>
                    <th>Data expirării</th>
                    <th>Observații</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td class="empty" colspan="5">Nu există înregistrări.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo administrativ_print_h((string)($r['nume'] ?? '')); ?></td>
                        <td>
                            <?php
                            $tip = (string)($r['tip_document'] ?? '');
                            echo administrativ_print_h((string)($tipuri[$tip] ?? $tip));
                            ?>
                        </td>
                        <td><?php echo !empty($r['data_inceput']) ? administrativ_print_h(date('d.m.Y', strtotime((string)$r['data_inceput']))) : '-'; ?></td>
                        <td><?php echo !empty($r['data_expirarii']) ? administrativ_print_h(date('d.m.Y', strtotime((string)$r['data_expirarii']))) : '-'; ?></td>
                        <td><?php echo administrativ_print_h((string)($r['observatii'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php elseif ($tab === 'juridic'): ?>
        <?php
        $title = 'Administrativ - Juridic ANR';
        $rows = (array)($data['lista_juridic'] ?? []);
        ?>
        <p class="title"><?php echo administrativ_print_h($title); ?></p>
        <p class="sub">Data tipăririi: <?php echo administrativ_print_h(date('d.m.Y H:i')); ?></p>
        <table aria-label="Juridic ANR">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Categorie</th>
                    <th>Titlu</th>
                    <th>Descriere</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td class="empty" colspan="5">Nu există înregistrări.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo !empty($r['data_document']) ? administrativ_print_h(date('d.m.Y', strtotime((string)$r['data_document']))) : '-'; ?></td>
                        <td><?php echo administrativ_print_h((string)($r['categorie'] ?? '-')); ?></td>
                        <td><?php echo administrativ_print_h((string)($r['titlu'] ?? '-')); ?></td>
                        <td><?php echo administrativ_print_h((string)($r['descriere'] ?? '-')); ?></td>
                        <td><?php echo administrativ_print_h((string)($r['status_document'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php elseif ($tab === 'parteneriate'): ?>
        <?php
        $title = 'Administrativ - Parteneriate';
        $rows = (array)($data['lista_parteneriate'] ?? []);
        ?>
        <p class="title"><?php echo administrativ_print_h($title); ?></p>
        <p class="sub">Data tipăririi: <?php echo administrativ_print_h(date('d.m.Y H:i')); ?></p>
        <table aria-label="Parteneriate">
            <thead>
                <tr>
                    <th>Partener</th>
                    <th>Obiect</th>
                    <th>Data început</th>
                    <th>Data sfârșit</th>
                    <th>Observații</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td class="empty" colspan="5">Nu există înregistrări.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo administrativ_print_h((string)($r['nume_partener'] ?? '-')); ?></td>
                        <td><?php echo administrativ_print_h((string)($r['obiect_parteneriat'] ?? '-')); ?></td>
                        <td><?php echo !empty($r['data_inceput']) ? administrativ_print_h(date('d.m.Y', strtotime((string)$r['data_inceput']))) : '-'; ?></td>
                        <td><?php echo !empty($r['data_sfarsit']) ? administrativ_print_h(date('d.m.Y', strtotime((string)$r['data_sfarsit']))) : '-'; ?></td>
                        <td><?php echo administrativ_print_h((string)($r['observatii'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php
        $title = 'Administrativ - Proceduri interne';
        $rows = (array)($data['lista_proceduri'] ?? []);
        ?>
        <p class="title"><?php echo administrativ_print_h($title); ?></p>
        <p class="sub">Data tipăririi: <?php echo administrativ_print_h(date('d.m.Y H:i')); ?></p>
        <table aria-label="Proceduri interne">
            <thead>
                <tr>
                    <th>Titlu</th>
                    <th>Conținut</th>
                    <th>Data actualizării</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td class="empty" colspan="3">Nu există înregistrări.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo administrativ_print_h((string)($r['titlu'] ?? '-')); ?></td>
                        <td><?php echo administrativ_print_h(trim(strip_tags((string)($r['continut'] ?? '')))); ?></td>
                        <td><?php echo !empty($r['updated_at']) ? administrativ_print_h(date('d.m.Y H:i', strtotime((string)$r['updated_at']))) : '-'; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
