<?php
/**
 * Afișare listă prezență pentru print. Pentru document cu antet asociație: descărcați DOCX.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/liste_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/document_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: activitati.php'); exit; }

try {
    $stmt = $pdo->prepare('SELECT * FROM liste_prezenta WHERE id = ?');
    $stmt->execute([$id]);
    $lista = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lista) { header('Location: activitati.php'); exit; }

    // Log printare
    log_activitate($pdo, "liste_prezenta: Lista de prezenta printata - {$lista['tip_titlu']} (ID: {$id}) / Data: " . date(DATE_FORMAT, strtotime($lista['data_lista'])));
    $stmt = $pdo->prepare('SELECT lm.ordine, lm.nume_manual, m.nume, m.prenume, m.datanastere, m.ciseria, m.cinumar, m.domloc FROM liste_prezenta_membri lm LEFT JOIN membri m ON lm.membru_id = m.id WHERE lm.lista_id = ? ORDER BY lm.ordine');
    $stmt->execute([$id]);
    $participanti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: activitati.php');
    exit;
}
$coloane = json_decode($lista['coloane_selectate'] ?? '[]', true) ?: ['nr_crt','nume_prenume','semnatura'];
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">

    <title><?php echo htmlspecialchars($lista['tip_titlu']); ?></title>
    <style>
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .no-print { display: none !important; } }
        body { font-family: Arial, sans-serif; max-width: 210mm; margin: 0 auto; padding: 15mm; font-size: 11pt; }
        .centrat { text-align: center; margin: 8px 0; }
        .titlu { font-size: 16pt; font-weight: bold; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .semnaturi { margin-top: 30px; display: flex; justify-content: space-between; gap: 20px; }
        .semn { text-align: center; width: 30%; }
        .semn .nume { font-weight: bold; margin-bottom: 2px; }
        .semn .functie { font-size: 9pt; color: #555; margin-bottom: 8px; }
        .semn .linie { border-bottom: 1px solid #000; height: 40px; }
    </style>
</head>
<body>
    <?php if (get_antet_asociatie_docx_path($pdo)): ?>
    <p class="no-print centrat" style="margin-bottom: 12px; font-size: 10pt;">
        <a href="lista-prezenta-docx.php?id=<?php echo (int)$id; ?>" class="text-amber-600 dark:text-amber-400 underline">Descarcă document cu antet asociație (DOCX)</a>
    </p>
    <?php endif; ?>
    <div class="centrat titlu"><?php echo htmlspecialchars($lista['tip_titlu']); ?></div>
    <?php if (!empty($lista['detalii_activitate'])): ?>
    <div class="centrat"><?php echo nl2br(htmlspecialchars($lista['detalii_activitate'])); ?></div>
    <?php endif; ?>
    <div class="centrat"><strong>Data: <?php echo date(DATE_FORMAT, strtotime($lista['data_lista'])); ?></strong></div>
    <?php if (!empty($lista['detalii_suplimentare_sus'])): ?>
    <div class="centrat" style="margin: 10px 0;"><?php echo nl2br(htmlspecialchars($lista['detalii_suplimentare_sus'])); ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <?php foreach ($coloane as $col): ?>
                <th><?php echo htmlspecialchars(LISTE_COLOANE[$col] ?? $col); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($participanti as $i => $p): ?>
            <tr>
                <?php foreach ($coloane as $col): ?>
                <td>
                    <?php
                    if ($col === 'nr_crt') echo $i + 1;
                    elseif ($col === 'nume_prenume') {
                        // Folosește nume_manual dacă membru_id este NULL, altfel nume + prenume din membri
                        if (!empty($p['nume_manual'])) {
                            echo htmlspecialchars($p['nume_manual']);
                        } else {
                            echo htmlspecialchars(trim(($p['nume'] ?? '') . ' ' . ($p['prenume'] ?? '')));
                        }
                    }
                    elseif ($col === 'datanastere') echo $p['datanastere'] ? date(DATE_FORMAT, strtotime($p['datanastere'])) : '';
                    elseif ($col === 'varsta') echo calculeaza_varsta($p['datanastere']) ?? '';
                    elseif ($col === 'ci') echo htmlspecialchars(trim(($p['ciseria'] ?? '') . ' ' . ($p['cinumar'] ?? '')));
                    elseif ($col === 'domloc') echo htmlspecialchars($p['domloc'] ?? '');
                    elseif ($col === 'semnatura') echo '';
                    else echo '';
                    ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($lista['detalii_suplimentare_jos'])): ?>
    <div style="margin: 15px 0;"><?php echo nl2br(htmlspecialchars($lista['detalii_suplimentare_jos'])); ?></div>
    <?php endif; ?>

    <?php
    $semn = [
        [$lista['semnatura_stanga_nume'] ?? '', $lista['semnatura_stanga_functie'] ?? ''],
        [$lista['semnatura_centru_nume'] ?? '', $lista['semnatura_centru_functie'] ?? ''],
        [$lista['semnatura_dreapta_nume'] ?? '', $lista['semnatura_dreapta_functie'] ?? '']
    ];
    $semn_afisate = array_filter($semn, fn($s) => trim($s[0]) !== '' || trim($s[1]) !== '');
    if (!empty($semn_afisate)):
    ?>
    <div class="semnaturi">
        <?php foreach ($semn as $s):
            if (trim($s[0]) === '' && trim($s[1]) === '') continue;
        ?>
        <div class="semn">
            <div class="nume"><?php echo htmlspecialchars($s[0]); ?></div>
            <div class="functie"><?php echo htmlspecialchars($s[1]); ?></div>
            <div class="linie"></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <script>window.onload = function() { setTimeout(function(){ window.print(); }, 300); }</script>
</body>
</html>
