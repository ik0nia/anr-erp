<?php
/**
 * View: Print membri - pagina curata pentru printare
 * Variabile: $all_members (array)
 */
$all_columns = [
    'dosarnr' => 'Nr. Dosar',
    'nume' => 'Nume',
    'prenume' => 'Prenume',
    'cnp' => 'CNP',
    'datanastere' => 'Data Nastere',
    'telefonnev' => 'Telefon',
    'email' => 'Email',
    'domloc' => 'Localitate',
    'judet_domiciliu' => 'Judet',
    'domstr' => 'Strada',
    'domnr' => 'Nr.',
    'codpost' => 'Cod Postal',
    'status_dosar' => 'Status',
    'hgrad' => 'Grad Handicap',
    'insotitor' => 'Asistent Personal',
    'sex' => 'Sex',
    'tipmediuur' => 'Mediu',
];

// Coloane selectate din GET sau default
$selected = $_GET['cols'] ?? 'dosarnr,nume,prenume,telefonnev,domloc,status_dosar,hgrad';
$selected_cols = array_filter(explode(',', $selected));
// Validare - doar coloane permise
$selected_cols = array_intersect($selected_cols, array_keys($all_columns));
if (empty($selected_cols)) {
    $selected_cols = ['dosarnr', 'nume', 'prenume', 'telefonnev', 'domloc', 'status_dosar'];
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<title>Lista Membri - Print</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #fff; }

    .no-print { padding: 15px 20px; background: #f8f8f8; border-bottom: 2px solid #ddd; }
    .no-print h2 { font-size: 16px; margin-bottom: 10px; }
    .cols-grid { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-bottom: 12px; }
    .cols-grid label { font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 4px; }
    .cols-grid input[type="checkbox"] { accent-color: #d97706; }
    .btn { display: inline-block; padding: 8px 20px; font-size: 13px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px; }
    .btn-print { background: #d97706; color: #fff; }
    .btn-print:hover { background: #b45309; }
    .btn-close { background: #e5e7eb; color: #374151; }
    .btn-close:hover { background: #d1d5db; }

    .print-header { text-align: center; padding: 10px 0 5px; }
    .print-header h1 { font-size: 16px; font-weight: bold; }
    .print-header p { font-size: 11px; color: #666; }

    table { width: 100%; border-collapse: collapse; margin-top: 5px; }
    th { background: #f3f4f6; font-weight: 700; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; }
    th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
    tr:nth-child(even) { background: #fafafa; }
    .status-activ { color: #059669; font-weight: 600; }
    .status-suspendat, .status-expirat { color: #d97706; font-weight: 600; }
    .status-decedat { color: #dc2626; font-weight: 600; }
    .total-row { font-size: 11px; margin-top: 8px; text-align: right; color: #666; }

    @media print {
        .no-print { display: none !important; }
        body { font-size: 10px; }
        th, td { padding: 3px 5px; }
        @page { margin: 10mm; size: landscape; }
    }
</style>
</head>
<body>

<div class="no-print">
    <h2>Configurare Print - Selecteaza coloanele</h2>
    <form id="cols-form" class="cols-grid">
        <?php foreach ($all_columns as $key => $label): ?>
        <label>
            <input type="checkbox" name="col" value="<?php echo $key; ?>" <?php echo in_array($key, $selected_cols) ? 'checked' : ''; ?>>
            <?php echo htmlspecialchars($label); ?>
        </label>
        <?php endforeach; ?>
    </form>
    <button class="btn btn-print" onclick="applyAndPrint()">Printeaza</button>
    <button class="btn btn-print" onclick="applyColumns()" style="background:#374151">Actualizeaza coloane</button>
    <button class="btn btn-close" onclick="window.close()">Inchide</button>
</div>

<div class="print-header">
    <h1><?php echo htmlspecialchars(defined('PLATFORM_NAME') ? PLATFORM_NAME : 'ERP'); ?> - Lista Membri</h1>
    <p>Data: <?php echo date('d.m.Y H:i'); ?> | Total: <?php echo count($all_members); ?> membri</p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <?php foreach ($selected_cols as $col): ?>
            <th><?php echo htmlspecialchars($all_columns[$col] ?? $col); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($all_members)): ?>
        <tr><td colspan="<?php echo count($selected_cols) + 1; ?>" style="text-align:center;padding:20px;color:#999;">Nu exista membri.</td></tr>
        <?php else: ?>
        <?php foreach ($all_members as $i => $m):
            $status = $m['status_dosar'] ?? '';
            $status_class = '';
            if ($status === 'Activ') $status_class = 'status-activ';
            elseif ($status === 'Suspendat' || $status === 'Expirat') $status_class = 'status-suspendat';
            elseif ($status === 'Decedat') $status_class = 'status-decedat';
        ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <?php foreach ($selected_cols as $col): ?>
            <td class="<?php echo $col === 'status_dosar' ? $status_class : ''; ?>">
                <?php
                $val = $m[$col] ?? '';
                if ($col === 'datanastere' && $val) {
                    echo date('d.m.Y', strtotime($val));
                } else {
                    echo htmlspecialchars($val);
                }
                ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="total-row">Total: <?php echo count($all_members); ?> membri</div>

<script>
function getSelectedCols() {
    var checks = document.querySelectorAll('#cols-form input[type=checkbox]:checked');
    var cols = [];
    checks.forEach(function(c) { cols.push(c.value); });
    return cols.join(',');
}
function applyColumns() {
    var cols = getSelectedCols();
    if (!cols) { alert('Selecteaza cel putin o coloana.'); return; }
    var url = new URL(window.location.href);
    url.searchParams.set('cols', cols);
    window.location.href = url.toString();
}
function applyAndPrint() {
    var cols = getSelectedCols();
    if (!cols) { alert('Selecteaza cel putin o coloana.'); return; }
    var currentCols = '<?php echo implode(',', $selected_cols); ?>';
    if (cols !== currentCols) {
        var url = new URL(window.location.href);
        url.searchParams.set('cols', cols);
        url.searchParams.set('autoprint', '1');
        window.location.href = url.toString();
    } else {
        window.print();
    }
}
<?php if (isset($_GET['autoprint'])): ?>
window.onload = function() { setTimeout(function() { window.print(); }, 300); };
<?php endif; ?>
</script>
</body>
</html>
