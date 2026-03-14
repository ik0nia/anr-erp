<?php
$dbData = $_SESSION['install_data'][2] ?? [];
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 2: Configurare baza de date</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Introdu datele de conexiune la baza de date MySQL. Asigură-te că ai creat deja baza de date în cPanel/phpMyAdmin.</p>

<div class="form-group">
    <label for="db_host">Host baza de date <span class="required">*</span></label>
    <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($dbData['db_host'] ?? 'localhost'); ?>" required>
    <div class="help-text">De obicei "localhost" pe majoritatea hosting-urilor.</div>
</div>

<div class="form-group">
    <label for="db_name">Nume baza de date <span class="required">*</span></label>
    <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($dbData['db_name'] ?? ''); ?>" required>
    <div class="help-text">Numele bazei de date create în cPanel/phpMyAdmin.</div>
</div>

<div class="form-group">
    <label for="db_user">Utilizator baza de date <span class="required">*</span></label>
    <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($dbData['db_user'] ?? ''); ?>" required>
    <div class="help-text">Utilizatorul MySQL cu permisiuni pe baza de date.</div>
</div>

<div class="form-group">
    <label for="db_pass">Parolă baza de date</label>
    <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($dbData['db_pass'] ?? ''); ?>">
    <div class="help-text">Parola pentru utilizatorul MySQL (poate fi goală pentru localhost).</div>
</div>

<div class="info-box">
    <h3>Informații importante</h3>
    <ul style="margin-left: 20px; color: #1e3a8a;">
        <li><strong>Installer-ul va încerca să creeze automat baza de date</strong></li>
        <li>Dacă utilizatorul MySQL are permisiuni CREATE DATABASE, baza va fi creată automat</li>
        <li>Dacă nu ai permisiuni CREATE DATABASE, <strong>creează manual baza de date în cPanel/phpMyAdmin</strong> înainte de a continua</li>
        <li>Utilizatorul trebuie să aibă permisiuni pe baza de date: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX</li>
    </ul>
    <p style="margin-top: 12px; padding: 12px; background: #fef3c7; border-radius: 6px; color: #92400e;">
        <strong>💡 Sfat:</strong> Pe majoritatea hosting-urilor, trebuie să creezi manual baza de date în cPanel. După creare, continuă instalarea cu același nume de bază de date.
    </p>
</div>
