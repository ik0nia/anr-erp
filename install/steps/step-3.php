<?php
$step3Data = $_SESSION['install_data'][3] ?? [];
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 3: Creare tabele baza de date</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Installer-ul va crea automat toate tabelele necesare platformei din fișierele schema SQL.</p>

<?php if (!empty($step3Data)): ?>
<div class="success-message">
    ✓ Au fost procesate <?php echo $step3Data['tables_created'] ?? 0; ?> fișiere schema și create toate tabelele necesare.
</div>
<?php else: ?>
<div class="info-box">
    <h3>Ce se va întâmpla</h3>
    <ul style="margin-left: 20px; color: #1e3a8a;">
        <li>Se vor citi toate fișierele schema_*.sql</li>
        <li>Se vor crea automat toate tabelele necesare</li>
        <li>Se vor adăuga indexuri și constrângeri</li>
        <li>Procesul poate dura câteva secunde</li>
    </ul>
</div>
<?php endif; ?>
