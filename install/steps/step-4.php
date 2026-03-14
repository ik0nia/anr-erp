<?php
$adminData = $_SESSION['install_data'][4] ?? [];
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 4: Creare utilizator administrator</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Creează contul de administrator care va avea acces complet la platformă.</p>

<div class="form-group">
    <label for="admin_username">Nume utilizator <span class="required">*</span></label>
    <input type="text" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($adminData['admin_username'] ?? 'Administrator'); ?>" required autocomplete="username">
    <div class="help-text">Numele de utilizator pentru autentificare.</div>
</div>

<div class="form-group">
    <label for="admin_password">Parolă <span class="required">*</span></label>
    <input type="password" id="admin_password" name="admin_password" required minlength="6" autocomplete="new-password">
    <div class="help-text">Minim 6 caractere. Folosește o parolă sigură.</div>
</div>

<div class="form-group">
    <label for="admin_email">Email <span class="required">*</span></label>
    <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($adminData['admin_email'] ?? 'merca.bhanvr@gmail.com'); ?>" required autocomplete="email">
    <div class="help-text">Adresa de email pentru utilizatorul administrator.</div>
</div>

<div class="form-group">
    <label for="admin_nume">Nume complet</label>
    <input type="text" id="admin_nume" name="admin_nume" value="<?php echo htmlspecialchars($adminData['admin_nume'] ?? 'Administrator'); ?>" autocomplete="name">
    <div class="help-text">Numele complet al administratorului.</div>
</div>

<div class="info-box">
    <h3>Securitate</h3>
    <ul style="margin-left: 20px; color: #1e3a8a;">
        <li>Folosește o parolă puternică și unică</li>
        <li>Nu partaja datele de autentificare</li>
        <li>Poți crea utilizatori suplimentari după instalare din Setări</li>
    </ul>
</div>
