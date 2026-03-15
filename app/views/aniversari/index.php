<?php
/**
 * View: Aniversari — Lista aniversari + calendar lunar
 *
 * Variabile disponibile (setate de controller):
 *   $aniversari_membri, $aniversari_contacte, $aniversari_per_zi,
 *   $luna_curenta, $anul_curent, $zi_azi, $zile_in_luna, $prima_zi_luna,
 *   $luni_ro, $zile_sapt, $mesaj_azi, $subiect_email_aniversari
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-4"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Aniversari</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <!-- Date picker -->
        <div class="mb-4 flex items-center gap-3">
            <label for="data-aniversari" class="text-sm font-medium text-slate-700 dark:text-gray-300">Selecteaza data:</label>
            <input type="date" id="data-aniversari" value="<?php echo htmlspecialchars($data_selectata); ?>"
                   class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500"
                   onchange="window.location.href='/aniversari?data=' + this.value">
            <?php if (!$este_azi): ?>
            <a href="/aniversari" class="px-3 py-2 text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400 underline">Inapoi la azi</a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Calendar lunar stanga sus -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" role="region" aria-label="Calendar lunar aniversari">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3"><?php echo $luni_ro[$luna_curenta - 1] . ' ' . $anul_curent; ?></h2>
                    <table class="w-full text-sm border-collapse" role="grid" aria-label="Zilele lunii cu numar aniversari">
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
                                        <span class="block text-xs font-semibold" aria-label="<?php echo $nr; ?> aniversari"><?php echo $nr; ?></span>
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
                    <textarea id="mesaj-azi" name="mesaj_azi" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" placeholder="Text predefinit pentru WhatsApp si email..."><?php echo htmlspecialchars($mesaj_azi); ?></textarea>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">Folosit la „Mesaj WhatsApp" si la „Trimite email". Subiectul emailului: „Echipa Asociatiei Nevazatorilor Bihor va ureaza La Multi Ani!".</p>
                    <button type="submit" class="mt-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salveaza mesajul de azi">Salveaza mesaj</button>
                </form>
            </div>
        </div>

        <section class="mb-8" aria-labelledby="titlu-membri">
            <h2 id="titlu-membri" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Aniversari membri – <?php echo date('d.m.Y', strtotime($data_selectata)); ?></h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Aniversari membri">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume si prenume</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data nasterii</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Varsta</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitatea</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Numar telefon</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Avertizari</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($aniversari_membri)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu exista aniversari ale membrilor in aceasta zi.</td>
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
                                    <a href="/membru-profil?id=<?php echo (int)$m['id']; ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline"><?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $m['datanastere'] ? date('d.m.Y', strtotime($m['datanastere'])) : '-'; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo aniversari_calculeaza_varsta($m['datanastere']); ?></td>
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
                                        <a href="<?php echo $mailto; ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-800/70 text-blue-800 dark:text-blue-200 text-sm font-medium" aria-label="Trimite email catre <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>">
                                            <i data-lucide="mail" class="w-4 h-4" aria-hidden="true"></i>
                                            Email
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($wa_primar): ?>
                                        <a href="<?php echo htmlspecialchars($wa_primar); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp catre <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>">
                                            <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                            Mesaj WhatsApp
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($afiseaza_wa_personal): ?>
                                        <a href="<?php echo htmlspecialchars($wa_personal); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp personal catre <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>">
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
            <h2 id="titlu-contacte" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Aniversari contacte – <?php echo date('d.m.Y', strtotime($data_selectata)); ?></h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Aniversari contacte">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume si prenume</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data nasterii</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Varsta</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitatea</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Numar telefon</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tip contact</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($aniversari_contacte)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu exista aniversari ale contactelor in aceasta zi.</td>
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
                                    <a href="/contacte/edit?id=<?php echo (int)$c['id']; ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline"><?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $c['data_nasterii'] ? date('d.m.Y', strtotime($c['data_nasterii'])) : '-'; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo aniversari_calculeaza_varsta($c['data_nasterii']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($localitate); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($tel_primar_c ?: ($tel_personal_c ?: '-')); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($tip_contact_label); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        <?php if ($mailto_c): ?>
                                        <a href="<?php echo $mailto_c; ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-800/70 text-blue-800 dark:text-blue-200 text-sm font-medium" aria-label="Trimite email catre <?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?>">
                                            <i data-lucide="mail" class="w-4 h-4" aria-hidden="true"></i>
                                            Email
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($wa_primar_c): ?>
                                        <a href="<?php echo htmlspecialchars($wa_primar_c); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp catre <?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?>">
                                            <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                            Mesaj WhatsApp
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($afiseaza_wa_personal_c): ?>
                                        <a href="<?php echo htmlspecialchars($wa_personal_c); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm font-medium" aria-label="Mesaj WhatsApp personal catre <?php echo htmlspecialchars(trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''))); ?>">
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
