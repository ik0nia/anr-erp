<?php
$step6Data = $_SESSION['install_data'][6] ?? [];
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 6: Instalare pachete Composer</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Se instalează dependențele PHP necesare platformei (PHPWord, PHPMailer, mPDF, etc.).</p>

<?php if (!empty($step6Data['composer_installed'])): ?>
<div class="success-message">
    ✓ Pachetele Composer au fost instalate cu succes!
</div>
<?php else: ?>
<div class="info-box">
    <h3>Ce se va instala</h3>
    <ul style="margin-left: 20px; color: #1e3a8a;">
        <li>phpoffice/phpword - Generare documente Word</li>
        <li>phpmailer/phpmailer - Trimitere emailuri</li>
        <li>mpdf/mpdf - Generare PDF-uri</li>
        <li>setasign/fpdf - Manipulare PDF-uri</li>
    </ul>
    <p style="margin-top: 12px; color: #6b7280;">
        <strong>Notă:</strong> Dacă Composer nu este disponibil pe server, pachetele pot fi instalate manual mai târziu sau poți uploada folderul vendor/ de pe calculatorul local.
    </p>
</div>
<?php endif; ?>
