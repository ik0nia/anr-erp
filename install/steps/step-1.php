<?php
$checks = $_SESSION['install_data'][1]['checks'] ?? [];
$phpVersion = $_SESSION['install_data'][1]['php_version'] ?? PHP_VERSION;
?>
<h2 style="margin-bottom: 20px; color: #1f2937;">Pasul 1: Verificare cerințe</h2>
<p style="margin-bottom: 24px; color: #6b7280;">Installer-ul verifică dacă serverul îndeplinește toate cerințele necesare pentru platformă.</p>

<div class="info-box">
    <h3>Cerințe sistem</h3>
    <ul class="check-list">
        <li class="<?php echo ($checks['php'] ?? false) ? '' : 'error'; ?>">
            PHP 7.4+ (versiunea curentă: <?php echo htmlspecialchars($phpVersion); ?>)
        </li>
        <li class="<?php echo ($checks['ext_pdo'] ?? false) ? '' : 'error'; ?>">
            Extensia PDO
        </li>
        <li class="<?php echo ($checks['ext_pdo_mysql'] ?? false) ? '' : 'error'; ?>">
            Extensia PDO MySQL
        </li>
        <li class="<?php echo ($checks['ext_mbstring'] ?? false) ? '' : 'error'; ?>">
            Extensia mbstring
        </li>
        <li class="<?php echo ($checks['ext_gd'] ?? false) ? '' : 'error'; ?>">
            Extensia GD
        </li>
        <li class="<?php echo ($checks['ext_zip'] ?? false) ? '' : 'error'; ?>">
            Extensia ZIP
        </li>
        <li class="<?php echo ($checks['ext_curl'] ?? false) ? '' : 'error'; ?>">
            Extensia cURL
        </li>
        <li class="<?php echo ($checks['uploads'] ?? false) ? '' : 'error'; ?>">
            Permisiuni folder uploads/
        </li>
    </ul>
</div>

<?php if (!empty($checks) && !in_array(false, $checks)): ?>
<div class="success-message">
    ✓ Toate cerințele sunt îndeplinite! Poți continua la pasul următor.
</div>
<?php endif; ?>
