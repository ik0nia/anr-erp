<?php
/**
 * Partial: Alert message (succes/eroare/warning)
 *
 * Variabile asteptate:
 *   $alert_type    — 'success' | 'error' | 'warning' | 'info'
 *   $alert_message — textul mesajului (string)
 *
 * Utilizare:
 *   $alert_type = 'success'; $alert_message = 'Salvat!';
 *   include APP_ROOT . '/app/views/partials/alert.php';
 */
if (empty($alert_message)) return;

$_alert_styles = [
    'success' => 'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-600 text-emerald-900 dark:text-emerald-200',
    'error'   => 'bg-red-100 dark:bg-red-900/30 border-red-600 text-red-800 dark:text-red-200',
    'warning' => 'bg-amber-100 dark:bg-amber-900/30 border-amber-600 text-amber-900 dark:text-amber-200',
    'info'    => 'bg-blue-100 dark:bg-blue-900/30 border-blue-600 text-blue-900 dark:text-blue-200',
];
$_alert_roles = [
    'success' => 'status',
    'error'   => 'alert',
    'warning' => 'alert',
    'info'    => 'status',
];
$_alert_class = $_alert_styles[$alert_type] ?? $_alert_styles['error'];
$_alert_role = $_alert_roles[$alert_type] ?? 'status';
?>
<div class="mb-4 p-4 border-l-4 rounded-r <?php echo $_alert_class; ?>" role="<?php echo $_alert_role; ?>" aria-live="polite">
    <?php echo htmlspecialchars($alert_message); ?>
</div>
<?php
// Cleanup - nu lasa variabile in scope-ul global
unset($alert_type, $alert_message);
