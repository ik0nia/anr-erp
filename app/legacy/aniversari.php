<?php
/**
 * Aniversări – afișează aniversările zilei (membri și contacte)
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/membri_alerts.php';

ensure_contacte_table($pdo);

// Mesajul de azi: persistat în sesiune pentru sesiunea curentă
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mesaj_azi'])) {
    csrf_require_valid();
    $_SESSION['aniversari_mesaj_azi'] = trim((string)$_POST['mesaj_azi']);
    header('Location: /aniversari');
    exit;
}
$mesaj_azi = isset($_SESSION['aniversari_mesaj_azi']) ? (string)$_SESSION['aniversari_mesaj_azi'] : '';

function calculeaza_varsta_aniversari($data_nastere) {
    if (empty($data_nastere)) return '-';
    $birth = new DateTime($data_nastere);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

// Membri cu ziua de nașteri azi (zi + lună) – excludem status dosar Decedat
$aniversari_membri = [];
try {
    $stmt = $pdo->query("
        SELECT id, nume, prenume, datanastere, domloc, telefonnev, telefonapartinator, email,
               gdpr, cidataelib, ceexp
        FROM membri
        WHERE datanastere IS NOT NULL
          AND MONTH(datanastere) = MONTH(CURDATE())
          AND DAY(datanastere) = DAY(CURDATE())
          AND (status_dosar IS NULL OR status_dosar != 'Decedat')
        ORDER BY nume, prenume
    ");
    $aniversari_membri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Aniversari membri: ' . $e->getMessage());
}

// Contacte cu ziua de nașteri azi (excludem Beneficiar – aceștia sunt în lista de membri)
$aniversari_contacte = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, nume, prenume, data_nasterii, companie, telefon, telefon_personal, email, email_personal, tip_contact
        FROM contacte
        WHERE data_nasterii IS NOT NULL
          AND MONTH(data_nasterii) = MONTH(CURDATE())
          AND DAY(data_nasterii) = DAY(CURDATE())
          AND (tip_contact IS NULL OR tip_contact != 'Beneficiar')
        ORDER BY nume, prenume
    ");
    $stmt->execute();
    $aniversari_contacte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Aniversari contacte: ' . $e->getMessage());
}

// Calendar lunar: număr aniversari per zi (membri fără Decedat + contacte fără Beneficiar) pentru luna curentă
$aniversari_per_zi = [];
try {
    $stmt = $pdo->query("
        SELECT DAY(datanastere) as zi, COUNT(*) as n FROM membri
        WHERE datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE())
          AND (status_dosar IS NULL OR status_dosar != 'Decedat')
        GROUP BY zi
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $aniversari_per_zi[(int)$row['zi']] = (int)($aniversari_per_zi[(int)$row['zi']] ?? 0) + (int)$row['n'];
    }
} catch (PDOException $e) {}
try {
    $stmt = $pdo->prepare("
        SELECT DAY(data_nasterii) as zi, COUNT(*) as n FROM contacte
        WHERE data_nasterii IS NOT NULL AND MONTH(data_nasterii) = MONTH(CURDATE())
          AND (tip_contact IS NULL OR tip_contact != 'Beneficiar')
        GROUP BY zi
    ");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $aniversari_per_zi[(int)$row['zi']] = (int)($aniversari_per_zi[(int)$row['zi']] ?? 0) + (int)$row['n'];
    }
} catch (PDOException $e) {}

$luna_curenta = (int)date('n');
$anul_curent = (int)date('Y');
$zi_azi = (int)date('j');
$zile_in_luna = (int)date('t');
$prima_zi_luna = (int)date('w', mktime(0, 0, 0, $luna_curenta, 1, $anul_curent)); // 0=Duminică
$luni_ro = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
$zile_sapt = ['Dum', 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sâm'];

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-4"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Aniversări</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Calendar lunar stânga sus -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" role="region" aria-label="Calendar lunar aniversări">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3"><?php echo $luni_ro[$luna_curenta - 1] . ' ' . $anul_curent; ?></h2>
                    <table class="w-full text-sm border-collapse" role="grid" aria-label="Zilele lunii cu număr aniversări">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-gray-600">
                                <?php foreach ($zile_sapt as $z): ?>
                                <th scope="col" class="py-1 px-0.5 text-center text-xs font-medium text-slate-600 dark:text-gray-400"><?php echo $z; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $celule_goale = $prima_zi_luna;
                            $nr_randuri = (int)ceil(($celule_goale + $zile_in_luna) / 7);
                            for ($r = 0; $r < $nr_randuri; $r++):
                            ?><tr class="border-b border-slate-100 dark:border-gray-700"><?php
                                for ($col = 0; $col < 7; $col++):
                                    $idx = $r * 7 + $col;
                                    if ($idx < $celule_goale) {
                                        echo '<td class="p-0.5 w-[14%] align-top">&nbsp;</td>';
                                        continue;
                                    }
                                    $zi = $idx - $celule_goale + 1;
                                    if ($zi > $zile_in_luna) {
                                        echo '<td class="p-0.5 w-[14%] align-top">&nbsp;</td>';
                                        continue;
                                    }
                                    $nr = $aniversari_per_zi[$zi] ?? 0;
                                    $e_azi = ($zi === $zi_azi);
                            ?>
                                <td class="p-0.5 w-[14%] align-top">
                                    <div class="min-h-[2.5rem] rounded text-center <?php echo $e_azi ? 'bg-amber-500 dark:bg-amber-600 text-white font-bold ring-2 ring-amber-600 dark:ring-amber-500' : 'bg-slate-50 dark:bg-gray-700/50 text-slate-800 dark:text-gray-200'; ?>">
                                        <span class="block text-xs"><?php echo $zi; ?></span>
                                        <?php if ($nr > 0): ?>
                                        <span class="block text-xs font-semibold" aria-label="<?php echo $nr; ?> aniversări"><?php echo $nr; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endfor; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Mesajul de azi dreapta -->
            <div class="lg:col-span-1">
                <form method="post" action="/aniversari" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                    <?php echo csrf_field(); ?>
                    <label for="mesaj-azi" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Mesajul de azi</label>
                    <textarea id="mesaj-azi" name="mesaj_azi" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" placeholder="Text predefinit pentru WhatsApp și email..."><?php echo htmlspecialchars($mesaj_azi); ?></textarea>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">Folosit la „Mesaj WhatsApp” și la „Trimite email”. Subiectul emailului: „Echipa Asociației Nevăzătorilor Bihor vă urează La Mulți Ani!”.</p>
                    <button type="submit" class="mt-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează mesajul de azi">Salvează mesaj</button>
                </form>
            </div>
        </div>

        <section class="mb-8" aria-labelledby="titlu-membri">
            <h2 id="titlu-membri" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Aniversări membri – <?php echo date('d.m.Y'); ?></h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Aniversări membri">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume și prenume</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data nașterii</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Vârstă</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitatea</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr telefon</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Avertizări</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php
                            $subiect_email_aniversari = 'Echipa Asociatiei Nevazatorilor Bihor va ureaza La Multi Ani!';
                            if (empty($aniversari_membri)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu există aniversări ale membrilor în această zi.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($aniversari_membri as $m):
                                $alerts = genereaza_alerts_membru($m, $pdo);
                                $tel_primar = trim($m['telefonnev'] ?? '');
                                $tel_personal = trim($m['telefonapartinator'] ?? '');
                                $email_m = trim($m['email'] ?? '');
                                $wa_primar = $tel_primar ? contacte_whatsapp_url_cu_mesaj($tel_primar, $mesaj_azi) : null;
                                $wa_personal = $tel_personal ? contacte_whatsapp_url_cu_mesaj($tel_personal, $mesaj_azi) : null;
                                $afiseaza_wa_personal = $wa_personal && $tel_primar !== $tel_personal;
                                $mailto = $email_m ? ('mailto:' . htmlspecialchars($email_m) . '?subject=' . rawurlencode($subiect_email_aniversari) . '&body=' . rawurlencode($mesaj_azi)) : '';
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <a href="membru-profil.php?id=<?php echo (int)$m['id']; ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline"><?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $m['datanastere'] ? date('d.m.Y', strtotime($m['datanastere'])) : '-'; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo calculeaza_varsta_aniversari($m['datanastere']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($m['domloc'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($tel_primar ?: ($tel_personal ?: '-')); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if (!empty($alerts)): ?>
                                    <ul class="list-disc list-inside space-y-0.5 text-amber-700 dark:text-amber-300">
                                        <?php foreach ($alerts as $a): ?>
                                        <li><?php echo htmlspecialchars($a['mesaj']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    <span class="text-slate-500 dark:text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        <?php if ($mailto): ?>
                                        <a href="<?php echo $mailto; ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-800/70 text-blue-800 dark:text-blue-200 text-sm font-medium" aria-label="Trimite email către <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>">
                                            <i data-lucide="mail" class="w-4 h-4" aria-hidden="true"></i>
                                            Email
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($wa_primar): ?>
                                        <a href="<?php echo htmlspecialchars($wa_primar); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp către <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>">
                                            <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                            Mesaj WhatsApp
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($afiseaza_wa_personal): ?>
                                        <a href="<?php echo htmlspecialchars($wa_personal); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp personal către <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>">
                                            <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                            Mesaj WhatsApp Personal
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$mailto && !$wa_primar && !$afiseaza_wa_personal): ?>
                                        <span class="text-slate-400 dark:text-gray-500 text-sm">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="mb-6" aria-labelledby="titlu-contacte">
            <h2 id="titlu-contacte" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Aniversări contacte – <?php echo date('d.m.Y'); ?></h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Aniversări contacte">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume și prenume</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data nașterii</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Vârstă</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitatea</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr telefon</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tip contact</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($aniversari_contacte)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu există aniversări ale contactelor în această zi.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($aniversari_contacte as $c):
                                $tel_primar_c = trim($c['telefon'] ?? '');
                                $tel_personal_c = trim($c['telefon_personal'] ?? '');
                                $email_c = trim($c['email'] ?? $c['email_personal'] ?? '');
                                $wa_primar_c = $tel_primar_c ? contacte_whatsapp_url_cu_mesaj($tel_primar_c, $mesaj_azi) : null;
                                $wa_personal_c = $tel_personal_c ? contacte_whatsapp_url_cu_mesaj($tel_personal_c, $mesaj_azi) : null;
                                $afiseaza_wa_personal_c = $wa_personal_c && $tel_primar_c !== $tel_personal_c;
                                $mailto_c = $email_c ? ('mailto:' . htmlspecialchars($email_c) . '?subject=' . rawurlencode($subiect_email_aniversari) . '&body=' . rawurlencode($mesaj_azi)) : '';
                                $localitate = $c['companie'] ?? '-';
                                $tip_contact_label = isset($c['tip_contact']) && $c['tip_contact'] !== '' ? (CONTACTE_TIPURI[$c['tip_contact']] ?? $c['tip_contact']) : '—';
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <a href="contacte-edit.php?id=<?php echo (int)$c['id']; ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline"><?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $c['data_nasterii'] ? date('d.m.Y', strtotime($c['data_nasterii'])) : '-'; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo calculeaza_varsta_aniversari($c['data_nasterii']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($localitate); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($tel_primar_c ?: ($tel_personal_c ?: '-')); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($tip_contact_label); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        <?php if ($mailto_c): ?>
                                        <a href="<?php echo $mailto_c; ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-800/70 text-blue-800 dark:text-blue-200 text-sm font-medium" aria-label="Trimite email către <?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?>">
                                            <i data-lucide="mail" class="w-4 h-4" aria-hidden="true"></i>
                                            Email
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($wa_primar_c): ?>
                                        <a href="<?php echo htmlspecialchars($wa_primar_c); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp către <?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?>">
                                            <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                            Mesaj WhatsApp
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($afiseaza_wa_personal_c): ?>
                                        <a href="<?php echo htmlspecialchars($wa_personal_c); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp personal către <?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?>">
                                            <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                            Mesaj WhatsApp Personal
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$mailto_c && !$wa_primar_c && !$afiseaza_wa_personal_c): ?>
                                        <span class="text-slate-400 dark:text-gray-500 text-sm">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
