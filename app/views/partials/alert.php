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
<div class="mb-4 p-4 border-l-4 rounded-r <?php echo $_alert_class; ?> js-dismissible-alert" role="<?php echo $_alert_role; ?>" aria-live="polite" data-auto-dismiss-seconds="15">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <?php echo htmlspecialchars($alert_message); ?>
        </div>
        <button type="button"
                class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded border border-current/20 hover:bg-black/10 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-current js-alert-close"
                aria-label="Închide mesajul">
            <span aria-hidden="true">X</span>
        </button>
    </div>
</div>
<?php
// Cleanup - nu lasa variabile in scope-ul global
unset($alert_type, $alert_message);
