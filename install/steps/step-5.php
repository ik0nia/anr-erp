<?php
$platformData = $_SESSION['install_data'][5] ?? [];
// Detectare automată URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$path = str_replace('/install', '', $path);
$autoUrl = $protocol . '://' . $host . $path;
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 5: Configurare platformă</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Configurează informațiile de bază ale platformei.</p>

<div class="form-group">
    <label for="platform_url">URL platformă <span class="required">*</span></label>
    <input type="url" id="platform_url" name="platform_url" value="<?php echo htmlspecialchars($platformData['platform_url'] ?? $autoUrl); ?>" required>
    <div class="help-text">URL-ul complet al platformei (ex: https://domeniul-tau.ro/crm-anr-bihor)</div>
</div>

<div class="form-group">
    <label for="platform_name">Nume platformă</label>
    <input type="text" id="platform_name" name="platform_name" value="<?php echo htmlspecialchars($platformData['platform_name'] ?? 'CRM ANR Bihor'); ?>">
    <div class="help-text">Numele care va apărea în interfața platformei.</div>
</div>

<div class="info-box">
    <h3>URL platformă</h3>
    <ul style="margin-left: 20px; color: #1e3a8a;">
        <li>Include protocolul (http:// sau https://)</li>
        <li>Include domeniul complet</li>
        <li>Include path-ul către platformă dacă nu e în root</li>
        <li>Acest URL va fi folosit pentru link-uri în emailuri și notificări</li>
    </ul>
</div>
