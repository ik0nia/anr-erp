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
    <h2>Configurare Print</h2>

    <!-- Filtre -->
    <div style="margin-bottom:15px; padding:12px; background:#fff; border:1px solid #ddd; border-radius:8px;">
        <h3 style="font-size:14px; margin-bottom:8px; color:#374151;">Filtre membri</h3>
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:end;">
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; font-weight:600;">Status</label>
                <select id="f_status" style="padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:12px;">
                    <option value="toti" <?php echo ($_GET['status'] ?? '') === 'toti' || empty($_GET['status']) ? 'selected' : ''; ?>>Toti</option>
                    <option value="activi" <?php echo ($_GET['status'] ?? '') === 'activi' ? 'selected' : ''; ?>>Activi</option>
                    <option value="suspendati" <?php echo ($_GET['status'] ?? '') === 'suspendati' ? 'selected' : ''; ?>>Suspendati/Expirati</option>
                    <option value="arhiva" <?php echo ($_GET['status'] ?? '') === 'arhiva' ? 'selected' : ''; ?>>Arhiva (Decedat)</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; font-weight:600;">Sex</label>
                <select id="f_sex" style="padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:12px;">
                    <option value="">Toate</option>
                    <option value="Masculin" <?php echo ($_GET['sex'] ?? '') === 'Masculin' ? 'selected' : ''; ?>>Masculin</option>
                    <option value="Feminin" <?php echo ($_GET['sex'] ?? '') === 'Feminin' ? 'selected' : ''; ?>>Feminin</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; font-weight:600;">Grad handicap</label>
                <select id="f_hgrad" style="padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:12px;">
                    <option value="">Toate</option>
                    <?php foreach (['Grav cu insotitor','Grav','Accentuat','Mediu','Usor','Alt handicap','Asociat','Fara handicap'] as $g): ?>
                    <option value="<?php echo $g; ?>" <?php echo ($_GET['hgrad'] ?? '') === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; font-weight:600;">Localitate</label>
                <input type="text" id="f_localitate" value="<?php echo htmlspecialchars($_GET['localitate'] ?? ''); ?>" placeholder="Cauta..." style="padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:12px; width:140px;">
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; font-weight:600;">Mediu</label>
                <select id="f_mediu" style="padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:12px;">
                    <option value="">Toate</option>
                    <option value="Urban" <?php echo ($_GET['mediu'] ?? '') === 'Urban' ? 'selected' : ''; ?>>Urban</option>
                    <option value="Rural" <?php echo ($_GET['mediu'] ?? '') === 'Rural' ? 'selected' : ''; ?>>Rural</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; font-weight:600;">Cautare</label>
                <input type="text" id="f_cautare" value="<?php echo htmlspecialchars($_GET['cautare'] ?? ''); ?>" placeholder="Nume, CNP..." style="padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:12px; width:160px;">
            </div>
            <div>
                <button class="btn" style="background:#374151; color:#fff; padding:6px 14px;" onclick="applyFilters()">Aplica filtre</button>
            </div>
        </div>
    </div>

    <!-- Coloane -->
    <div style="margin-bottom:12px; padding:12px; background:#fff; border:1px solid #ddd; border-radius:8px;">
        <h3 style="font-size:14px; margin-bottom:8px; color:#374151;">Coloane vizibile <span style="font-weight:400; font-size:11px; color:#888;">(trage pentru reordonare sau foloseste sagetile)</span></h3>
        <div id="cols-form" style="display:flex; flex-direction:column; gap:3px; max-width:500px;">
            <?php
            // Ordinea: mai intai coloanele selectate (in ordinea din URL), apoi restul
            $ordered_keys = array_merge($selected_cols, array_diff(array_keys($all_columns), $selected_cols));
            foreach ($ordered_keys as $key):
                $checked = in_array($key, $selected_cols);
            ?>
            <div class="col-item" draggable="true" data-key="<?php echo $key; ?>" style="display:flex; align-items:center; gap:6px; padding:4px 8px; background:<?php echo $checked ? '#fef3c7' : '#f9fafb'; ?>; border:1px solid <?php echo $checked ? '#d97706' : '#e5e7eb'; ?>; border-radius:5px; cursor:grab; font-size:13px;">
                <span style="cursor:grab; color:#999;">&#9776;</span>
                <input type="checkbox" value="<?php echo $key; ?>" <?php echo $checked ? 'checked' : ''; ?> style="accent-color:#d97706;">
                <span style="flex:1;"><?php echo htmlspecialchars($all_columns[$key]); ?></span>
                <button type="button" onclick="moveItem(this.parentElement, -1)" style="border:none; background:none; cursor:pointer; font-size:14px; color:#666;" title="Muta sus">&#9650;</button>
                <button type="button" onclick="moveItem(this.parentElement, 1)" style="border:none; background:none; cursor:pointer; font-size:14px; color:#666;" title="Muta jos">&#9660;</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="btn btn-print" onclick="applyAndPrint()">Printeaza</button>
    <button class="btn btn-print" onclick="applyAll()" style="background:#374151">Actualizeaza</button>
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
// Coloane selectate (in ordinea din lista)
function getSelectedCols() {
    var items = document.querySelectorAll('#cols-form .col-item');
    var cols = [];
    items.forEach(function(item) {
        var cb = item.querySelector('input[type=checkbox]');
        if (cb && cb.checked) cols.push(cb.value);
    });
    return cols.join(',');
}

// Construieste URL cu filtre + coloane
function buildUrl(extra) {
    var url = new URL(window.location.href);
    // Filtre
    var filters = {
        status: document.getElementById('f_status').value,
        sex: document.getElementById('f_sex').value,
        hgrad: document.getElementById('f_hgrad').value,
        localitate: document.getElementById('f_localitate').value,
        mediu: document.getElementById('f_mediu').value,
        cautare: document.getElementById('f_cautare').value,
    };
    // Seteaza sau sterge parametrii
    for (var k in filters) {
        if (filters[k]) url.searchParams.set(k, filters[k]);
        else url.searchParams.delete(k);
    }
    // Coloane
    var cols = getSelectedCols();
    if (cols) url.searchParams.set('cols', cols);
    // Pastreaza print=1
    url.searchParams.set('print', '1');
    // Extra params
    if (extra) for (var ek in extra) url.searchParams.set(ek, extra[ek]);
    return url.toString();
}

function applyFilters() {
    window.location.href = buildUrl();
}

function applyAll() {
    var cols = getSelectedCols();
    if (!cols) { alert('Selecteaza cel putin o coloana.'); return; }
    window.location.href = buildUrl();
}

function applyAndPrint() {
    var cols = getSelectedCols();
    if (!cols) { alert('Selecteaza cel putin o coloana.'); return; }
    window.location.href = buildUrl({ autoprint: '1' });
}

// Muta coloana sus/jos
function moveItem(el, dir) {
    var parent = el.parentElement;
    if (dir === -1 && el.previousElementSibling) {
        parent.insertBefore(el, el.previousElementSibling);
    } else if (dir === 1 && el.nextElementSibling) {
        parent.insertBefore(el.nextElementSibling, el);
    }
    updateItemStyles();
}

function updateItemStyles() {
    document.querySelectorAll('#cols-form .col-item').forEach(function(item) {
        var cb = item.querySelector('input[type=checkbox]');
        item.style.background = cb.checked ? '#fef3c7' : '#f9fafb';
        item.style.borderColor = cb.checked ? '#d97706' : '#e5e7eb';
    });
}

// Checkbox change updates style
document.querySelectorAll('#cols-form input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', updateItemStyles);
});

// Drag and drop
var dragItem = null;
document.querySelectorAll('#cols-form .col-item').forEach(function(item) {
    item.addEventListener('dragstart', function(e) { dragItem = this; this.style.opacity = '0.4'; });
    item.addEventListener('dragend', function() { this.style.opacity = '1'; dragItem = null; });
    item.addEventListener('dragover', function(e) { e.preventDefault(); });
    item.addEventListener('drop', function(e) {
        e.preventDefault();
        if (dragItem && dragItem !== this) {
            var parent = this.parentElement;
            var items = Array.from(parent.children);
            var fromIdx = items.indexOf(dragItem);
            var toIdx = items.indexOf(this);
            if (fromIdx < toIdx) parent.insertBefore(dragItem, this.nextSibling);
            else parent.insertBefore(dragItem, this);
        }
    });
});

<?php if (isset($_GET['autoprint'])): ?>
window.onload = function() { setTimeout(function() { window.print(); }, 300); };
<?php endif; ?>
</script>
</body>
</html>
