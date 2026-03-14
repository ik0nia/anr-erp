<?php
$step7Data = $_SESSION['install_data'][7] ?? [];
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 7: Generare CSS (Tailwind)</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Se generează fișierul CSS compilat din Tailwind CSS pentru interfața platformei.</p>

<?php if (!empty($step7Data['css_built'])): ?>
<div class="success-message">
    ✓ CSS-ul a fost generat cu succes!
</div>
<?php else: ?>
<div class="info-box">
    <h3>Ce se va întâmpla</h3>
    <ul style="margin-left: 20px; color: #1e3a8a;">
        <li>Se instalează dependențele npm (Tailwind CSS)</li>
        <li>Se compilează CSS-ul din fișierul input.css</li>
        <li>Se generează fișierul tailwind.css final</li>
    </ul>
    <p style="margin-top: 12px; color: #6b7280;">
        <strong>Notă:</strong> Dacă npm nu este disponibil pe server, CSS-ul poate fi generat manual mai târziu sau poți uploada fișierul css/tailwind.css de pe calculatorul local.
    </p>
</div>
<?php endif; ?>
