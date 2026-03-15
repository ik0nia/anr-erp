<?php
/**
 * Afișare tabel distributie BPA pentru print
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/bpa_helper.php';
require_once __DIR__ . '/../includes/liste_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /ajutoare-bpa'); exit; }
$tabel = bpa_get_tabel($pdo, $id);
if (!$tabel) { header('Location: /ajutoare-bpa'); exit; }

$locuri = [];
if ($tabel['predare_sediul']) $locuri[] = 'Predare la sediu';
if ($tabel['predare_centru']) $locuri[] = 'Predare la centru';
if ($tabel['livrare_domiciliu']) $locuri[] = 'Livrare la domiciliu';
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">

    <title>Tabel Distributie <?php echo htmlspecialchars($tabel['nr_tabel']); ?></title>
    <style>
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        body { font-family: Arial, sans-serif; max-width: 210mm; margin: 0 auto; padding: 15mm; font-size: 11pt; }
        .centrat { text-align: center; margin: 8px 0; }
        .titlu { font-size: 16pt; font-weight: bold; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .semnaturi { margin-top: 30px; display: flex; justify-content: space-between; gap: 20px; }
        .semn { text-align: center; width: 45%; }
        .semn .nume { font-weight: bold; margin-bottom: 2px; }
        .semn .functie { font-size: 9pt; color: #555; margin-bottom: 8px; }
        .semn .linie { border-bottom: 1px solid #000; height: 40px; }
    </style>
</head>
<body>
    <div class="centrat titlu">Tabel Distributie</div>
    <div class="centrat"><strong>Data: <?php echo date(DATE_FORMAT, strtotime($tabel['data_tabel'])); ?></strong> &nbsp; Nr. <?php echo htmlspecialchars($tabel['nr_tabel']); ?></div>
    <?php if (!empty($locuri)): ?>
    <div class="centrat" style="margin: 8px 0;"><?php echo implode(' &bull; ', $locuri); ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Nr. crt.</th>
                <th>Numele și prenumele membru</th>
                <th>Localitatea de domiciliu</th>
                <th>Seria și nr. C.I.</th>
                <th>Vârstă</th>
                <th>Greutate pachet (Kg)</th>
                <th>Semnătură</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tabel['randuri'] as $i => $r): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php
                    if (!empty($r['membru_id'])) {
                        echo htmlspecialchars(trim(($r['nume'] ?? '') . ' ' . ($r['prenume'] ?? '')));
                    } else {
                        echo htmlspecialchars(trim(($r['nume_manual'] ?? '') . ' ' . ($r['prenume_manual'] ?? '')));
                    }
                ?></td>
                <td><?php echo htmlspecialchars($r['localitate'] ?? $r['domloc'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(($r['ciseria'] ?? '') . ' ' . ($r['cinumar'] ?? '') ?: ($r['seria_nr_ci'] ?? '')); ?></td>
                <td><?php
                    $dn = $r['datanastere'] ?? $r['data_nastere'] ?? null;
                    echo $dn ? (string)calculeaza_varsta($dn) : '';
                ?></td>
                <td><?php echo number_format($r['greutate_pachet'] ?? 0, 2, ',', '.'); ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="centrat" style="margin-top: 10px;"><strong>Total cantitate distribuită: <?php echo number_format($tabel['cantitate_totala'], 2, ',', '.'); ?> kg</strong></p>

    <div class="semnaturi">
        <div class="semn">
            <div class="nume">Mihai Merca</div>
            <div class="functie">Președinte</div>
            <div class="linie"></div>
        </div>
        <div class="semn">
            <div class="nume">Cristina Cociuba</div>
            <div class="functie">Responsabil distributie</div>
            <div class="linie"></div>
        </div>
    </div>

    <script>window.onload = function() { setTimeout(function(){ window.print(); }, 300); }</script>
</body>
</html>
