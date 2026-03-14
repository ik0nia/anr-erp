<?php
$adminData = $_SESSION['install_data'][4] ?? [];
$platformData = $_SESSION['install_data'][5] ?? [];
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 8: Finalizare instalare</h2>

<div class="success-message" style="background: #d1fae5; border-color: #10b981; padding: 24px; margin-bottom: 24px;">
    <h3 style="color: #065f46; margin-bottom: 12px; font-size: 20px;">✓ Instalare completă!</h3>
    <p style="color: #047857; margin-bottom: 16px;">Platforma CRM ANR Bihor a fost instalată cu succes.</p>
    
    <div style="background: white; padding: 16px; border-radius: 8px; margin-top: 16px;">
        <h4 style="color: #1f2937; margin-bottom: 12px;">Informații importante:</h4>
        <ul style="margin-left: 20px; color: #374151;">
            <li><strong>URL platformă:</strong> <?php echo htmlspecialchars($platformData['platform_url'] ?? 'N/A'); ?></li>
            <li><strong>Utilizator admin:</strong> <?php echo htmlspecialchars($adminData['admin_username'] ?? 'N/A'); ?></li>
            <li><strong>Email admin:</strong> <?php echo htmlspecialchars($adminData['admin_email'] ?? 'N/A'); ?></li>
        </ul>
    </div>
</div>

<div class="info-box" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
    <h3 style="color: #b91c1c;">⚠ Nu suprascrie config.php</h3>
    <p style="color: #991b1b; margin-top: 8px;">
        <strong>config.php</strong> a fost generat cu datele bazei de date introduse la pasul 2. Dacă reîncarci proiectul de pe calculator (FTP/git), <strong>nu suprascrie config.php</strong> de pe server cu cel de pe PC (acesta conține root/fără parolă pentru XAMPP). Dacă l-ai suprascris, editează pe server liniile DB_HOST, DB_NAME, DB_USER, DB_PASS din config.php cu valorile de la pasul 2.
    </p>
</div>

<div class="info-box" style="background: #fef3c7; border-color: #f59e0b;">
    <h3 style="color: #92400e;">⚠ Securitate</h3>
    <p style="color: #78350f; margin-top: 8px;">
        <strong>IMPORTANT:</strong> După instalare, șterge următoarele fișiere pentru securitate:
    </p>
    <ul style="margin-left: 20px; color: #78350f; margin-top: 8px;">
        <li>Fișierul <code>setup.php</code> din root</li>
        <li>Folderul <code>install/</code> (sau redenumește-l)</li>
    </ul>
    <p style="color: #78350f; margin-top: 12px;">
        Poți face acest lucru prin FTP sau SSH:
    </p>
    <ul style="margin-left: 20px; color: #78350f; margin-top: 8px;">
        <li>Șterge setup.php: <code>rm setup.php</code></li>
        <li>Șterge folderul install: <code>rm -rf install/</code></li>
        <li>Sau redenumește: <code>mv install install_backup</code></li>
    </ul>
</div>

    <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 2px solid #e5e7eb;">
    <a href="<?php echo htmlspecialchars($platformData['platform_url'] ?? 'index.php'); ?>" class="btn btn-primary" style="font-size: 18px; padding: 16px 32px;">
        Accesează platforma →
    </a>
    <p style="margin-top: 20px; color: #6b7280; font-size: 14px;">
        <strong>Notă:</strong> După instalare, șterge fișierul <code>setup.php</code> pentru securitate.
    </p>
</div>
