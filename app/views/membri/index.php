<?php
/**
 * View: Membri — Lista
 *
 * Variabile disponibile (setate de controller):
 *   $membri, $total_membri, $total_pages, $page, $per_page, $sort_col, $sort_dir,
 *   $status_filter, $cautare, $avertizari_filter, $aniversari_azi_filter,
 *   $actualizare_cnp_ci_filter, $eroare, $eroare_bd, $succes,
 *   $membri_activi_count, $membri_suspendati_expirati_count,
 *   $membri_cu_avertizari, $membri_actualizare_cnp_ci, $membri_aniversari_azi_count,
 *   $membri_scutiti_cotizatie_ids, $membri_cotizatie_achitata_an_curent,
 *   $valori_cotizatie_an_curent
 */

// Helper: sort link
$sort_link_params = [
    'per_page' => $per_page,
    'status' => $status_filter,
];
if (!empty($cautare)) $sort_link_params['cautare'] = $cautare;
if ($avertizari_filter) $sort_link_params['avertizari'] = '1';
if ($actualizare_cnp_ci_filter) $sort_link_params['actualizare_cnp_ci'] = '1';
if ($aniversari_azi_filter) $sort_link_params['aniversari_azi'] = '1';

$deschide_formular = !empty($eroare) && $_SERVER['REQUEST_METHOD'] === 'POST';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Management Membri</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 dark:border-amber-500 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <p class="font-semibold mb-2"><?php echo htmlspecialchars($eroare_bd); ?></p>
            <p class="text-sm mt-2">
                <strong>Pasi pentru rezolvare:</strong><br>
                1. Deschideti panoul MySQL (cPanel -> phpMyAdmin sau MySQL de pe server)<br>
                2. Selectati baza de date <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded"><?php echo htmlspecialchars(defined('DB_NAME') ? DB_NAME : ''); ?></code><br>
                3. Rulati scriptul <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded">schema.sql</code>; daca tabelul exista deja, rulati <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded">schema_update.sql</code> sau <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded">schema_update_simplu.sql</code>
            </p>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <!-- Stanga: camp cautare + butoane -->
            <form method="get" action="/membri" class="flex items-center gap-2 shrink-0">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_col); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars(strtolower($sort_dir)); ?>">
                <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php if ($avertizari_filter): ?><input type="hidden" name="avertizari" value="1"><?php endif; ?>
                <?php if ($actualizare_cnp_ci_filter): ?><input type="hidden" name="actualizare_cnp_ci" value="1"><?php endif; ?>
                <?php if ($aniversari_azi_filter): ?><input type="hidden" name="aniversari_azi" value="1"><?php endif; ?>
                <div class="relative">
                    <input type="search"
                           name="cautare"
                           value="<?php echo htmlspecialchars($cautare); ?>"
                           placeholder="Cauta membri..."
                           class="w-64 pl-10 pr-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-label="Cauta membri">
                    <button type="submit"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400"
                            aria-label="Cauta">
                        <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
                <button type="submit"
                        name="reset"
                        value="1"
                        onclick="this.form.querySelector('input[name=cautare]').value='';"
                        class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 shrink-0"
                        aria-label="Reseteaza cautarea">
                    <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                </button>
            </form>

            <!-- Centru: butoane tipuri membri -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <a href="/membri?status=activi&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $status_filter === 'activi' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                    Membri Activi (<?php echo $membri_activi_count; ?>)
                </a>
                <a href="/membri?status=suspendati&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $status_filter === 'suspendati' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                    Membri Suspendati/Expirati (<?php echo $membri_suspendati_expirati_count; ?>)
                </a>
                <a href="/membri?status=arhiva&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $status_filter === 'arhiva' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                    Arhiva Membri
                </a>
                <a href="/membri?status=activi&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?>&avertizari=<?php echo $avertizari_filter ? '0' : '1'; ?><?php echo $actualizare_cnp_ci_filter ? '&actualizare_cnp_ci=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2 <?php echo $avertizari_filter ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   aria-label="<?php echo $avertizari_filter ? 'Dezactiveaza filtrarea dupa actualizare date' : 'Afiseaza doar membrii cu date de actualizat'; ?>">
                    <i data-lucide="alert-triangle" class="w-4 h-4" aria-hidden="true"></i>
                    Actualizare date (<?php echo $membri_cu_avertizari; ?>)
                </a>
                <?php if ($membri_actualizare_cnp_ci > 0): ?>
                <a href="/membri?status=<?php echo urlencode($status_filter); ?>&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?>&actualizare_cnp_ci=<?php echo $actualizare_cnp_ci_filter ? '0' : '1'; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2 <?php echo $actualizare_cnp_ci_filter ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   aria-label="<?php echo $actualizare_cnp_ci_filter ? 'Dezactiveaza filtrarea dupa actualizare CNP/CI' : 'Afiseaza doar membrii care necesita actualizare CNP/CI'; ?>">
                    <i data-lucide="file-edit" class="w-4 h-4" aria-hidden="true"></i>
                    Actualizare CNP/CI (<?php echo $membri_actualizare_cnp_ci; ?>)
                </a>
                <?php endif; ?>
                <a href="/membri?status=<?php echo urlencode($status_filter); ?>&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $actualizare_cnp_ci_filter ? '&actualizare_cnp_ci=1' : ''; ?>&aniversari_azi=<?php echo $aniversari_azi_filter ? '0' : '1'; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2 <?php echo $aniversari_azi_filter ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   aria-label="<?php echo $aniversari_azi_filter ? 'Dezactiveaza filtrarea aniversari azi' : 'Afiseaza doar membrii care isi serbeaza ziua de nastere azi'; ?>">
                    <i data-lucide="cake" class="w-4 h-4" aria-hidden="true"></i>
                    Aniversari azi (<?php echo $membri_aniversari_azi_count; ?>)
                </a>
            </div>

            <!-- Dreapta: buton adauga membru -->
            <button type="button"
                    onclick="document.getElementById('formular-membru').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition shrink-0"
                    aria-label="Deschide formular pentru adaugare membru nou"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    id="btn-adauga-membru">
                <i data-lucide="user-plus" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Adauga Membru Nou
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table id="tabel-membri" class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista membrilor asociatiei">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="dosarnr">
                                <div class="flex items-center justify-between">
                                    <span><?php echo membri_sort_link('dosarnr', 'Nr. Dosar', $sort_col, strtolower($sort_dir), $sort_link_params); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="nume">
                                <div class="flex items-center justify-between">
                                    <span><?php echo membri_sort_link('nume', 'Nume si Prenume', $sort_col, strtolower($sort_dir), $sort_link_params); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="datanastere">
                                <div class="flex items-center justify-between">
                                    <span><?php echo membri_sort_link('datanastere', 'Data Nasterii', $sort_col, strtolower($sort_dir), $sort_link_params); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="varsta">
                                <div class="flex items-center justify-between">
                                    <span>Varsta</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="ci">
                                <div class="flex items-center justify-between">
                                    <span><?php echo membri_sort_link('ciseria', 'Seria si Nr. C.I.', $sort_col, strtolower($sort_dir), $sort_link_params); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="hgrad">
                                <div class="flex items-center justify-between">
                                    <span><?php echo membri_sort_link('hgrad', 'Grad Handicap', $sort_col, strtolower($sort_dir), $sort_link_params); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="telefon">
                                <div class="flex items-center justify-between">
                                    <span><?php echo membri_sort_link('telefonnev', 'Telefon', $sort_col, strtolower($sort_dir), $sort_link_params); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="avertizari">
                                <div class="flex items-center justify-between">
                                    <span>Actualizare date</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="actiuni">
                                <div class="flex items-center justify-between">
                                    <span>Actiuni</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($membri)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-600 dark:text-gray-400">
                                <?php
                                if (!empty($cautare)) {
                                    echo 'Nu s-au gasit membri care sa corespunda cautarii.';
                                } elseif ($status_filter === 'activi') {
                                    echo 'Nu exista membri activi.';
                                } elseif ($status_filter === 'suspendati') {
                                    echo 'Nu exista membri suspendati sau expirati.';
                                } elseif ($status_filter === 'arhiva') {
                                    echo 'Nu exista membri in arhiva (decedati).';
                                } else {
                                    echo 'Nu exista membri inregistrati.';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($membri as $m):
                            $m_id = (int)($m['id'] ?? 0);
                            $dosarnr = htmlspecialchars($m['dosarnr'] ?? '-');
                            $status_afisat = $m['status_dosar'] ?? 'Activ';
                            $status_colors_dosar = [
                                'Activ' => 'text-emerald-600 dark:text-emerald-400',
                                'Expirat' => 'text-orange-500 dark:text-orange-400',
                                'Suspendat' => 'text-yellow-600 dark:text-yellow-400',
                                'Retras' => 'text-slate-500 dark:text-gray-400',
                                'Decedat' => 'text-red-600 dark:text-red-400'
                            ];
                            $dosar_color = $status_colors_dosar[$status_afisat] ?? 'text-slate-600 dark:text-gray-400';
                            $nume_complet = trim($m['nume'] . ' ' . $m['prenume']);
                            $profil_url = '/membru-profil?id=' . $m_id;
                        ?>
                        <tr class="hover:bg-slate-100 dark:hover:bg-gray-700 membri-table-row cursor-pointer transition-colors"
                            onclick="window.location.href='<?php echo $profil_url; ?>'"
                            role="link"
                            aria-label="Deschide profilul <?php echo htmlspecialchars($nume_complet); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-base">
                                <span class="font-bold <?php echo $dosar_color; ?>"><?php echo $dosarnr; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base font-medium text-slate-900 dark:text-white">
                                <?php echo htmlspecialchars($nume_complet); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base text-slate-700 dark:text-gray-300">
                                <?php echo $m['datanastere'] ? date(DATE_FORMAT, strtotime($m['datanastere'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base text-slate-700 dark:text-gray-300">
                                <?php
                                $varsta = membri_calculeaza_varsta($m['datanastere']);
                                echo $varsta !== '-' ? ((int)$varsta . ' ani') : '-';
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base text-slate-700 dark:text-gray-300">
                                <?php
                                $ci = '';
                                if (!empty($m['ciseria'])) $ci .= $m['ciseria'];
                                if (!empty($m['cinumar'])) $ci .= ($ci ? ' ' : '') . $m['cinumar'];
                                echo htmlspecialchars($ci ?: '-');
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($m['hgrad'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($m['telefonnev'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo render_alerts_badge($m, $m['id'], $pdo); ?>
                            </td>
                            <td class="px-6 py-4 text-base font-medium" onclick="event.stopPropagation()">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button"
                                            data-action="generare-document"
                                            data-membru-id="<?php echo $m['id']; ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-400 dark:border-blue-500 bg-blue-100 dark:bg-blue-800/70 text-blue-900 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-700 font-medium"
                                            aria-label="Genereaza document pentru <?php echo htmlspecialchars($m['nume'] . ' ' . $m['prenume']); ?>">
                                        <i data-lucide="file-text" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Genereaza Document</span>
                                    </button>
                                    <?php
                                    $cot_achitata_incasari = in_array($m['id'], $membri_cotizatie_achitata_an_curent) || in_array($m['id'], $membri_scutiti_cotizatie_ids);
                                    $val_cot = $valori_cotizatie_an_curent[$m['hgrad'] ?? 'Fara handicap'] ?? 0;
                                    if (in_array($m['id'], $membri_scutiti_cotizatie_ids)): ?>
                                    <a href="/setari?tab=cotizatii"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-400 dark:border-gray-500 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 font-medium"
                                       aria-label="Scutit de cotizatie - vezi detalii">
                                        <i data-lucide="shield-check" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Scutit de cotizatie</span>
                                    </a>
                                    <?php else: ?>
                                    <button type="button"
                                            class="btn-deschide-incasari inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-purple-400 dark:border-purple-500 bg-purple-100 dark:bg-purple-800/70 text-purple-900 dark:text-purple-100 hover:bg-purple-200 dark:hover:bg-purple-700 font-medium"
                                            data-membru-id="<?php echo (int)$m['id']; ?>"
                                            data-valoare-cot="<?php echo number_format($val_cot, 2, '.', ''); ?>"
                                            data-cot-achitata="<?php echo $cot_achitata_incasari ? '1' : '0'; ?>"
                                            aria-label="Incaseaza">
                                        <i data-lucide="dollar-sign" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Incaseaza</span>
                                    </button>
                                    <?php endif; ?>
                                    <?php
                                    $mesaj_subiect = $_SESSION['membri_mesaj_subiect'] ?? '';
                                    $mesaj_continut = $_SESSION['membri_mesaj_continut'] ?? '';
                                    if (!empty($m['email'])):
                                        $mailto = 'mailto:' . htmlspecialchars($m['email']);
                                        if ($mesaj_subiect !== '' || $mesaj_continut !== '') {
                                            $mailto .= '?';
                                            if ($mesaj_subiect !== '') $mailto .= 'subject=' . rawurlencode($mesaj_subiect);
                                            if ($mesaj_continut !== '') $mailto .= ($mesaj_subiect !== '' ? '&' : '') . 'body=' . rawurlencode(str_replace(["\r\n", "\n"], "\n", $mesaj_continut));
                                        }
                                    ?>
                                    <a href="<?php echo $mailto; ?>"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-400 dark:border-emerald-500 bg-emerald-100 dark:bg-emerald-800/70 text-emerald-900 dark:text-emerald-100 hover:bg-emerald-200 dark:hover:bg-emerald-700 font-medium"
                                       aria-label="Trimite email">
                                        <i data-lucide="mail" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Email</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($m['telefonnev'])):
                                        $wa_url = 'https://wa.me/' . preg_replace('/\D/', '', $m['telefonnev']);
                                        if ($mesaj_continut !== '') $wa_url .= '?text=' . rawurlencode($mesaj_continut);
                                    ?>
                                    <a href="<?php echo htmlspecialchars($wa_url); ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-green-400 dark:border-green-500 bg-green-100 dark:bg-green-800/70 text-green-900 dark:text-green-100 hover:bg-green-200 dark:hover:bg-green-700 font-medium"
                                       aria-label="Mesaj WhatsApp">
                                        <i data-lucide="message-circle" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>WhatsApp</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginare si selector numar rezultate -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <label for="per_page_select" class="text-sm text-slate-700 dark:text-gray-300">Rezultate pe pagina:</label>
                    <select id="per_page_select"
                            onchange="window.location.href = '<?php
                                $url_params = array_merge($_GET, ['per_page' => '', 'page' => '1']);
                                if (!isset($url_params['status'])) $url_params['status'] = $status_filter;
                                echo '/membri?' . http_build_query($url_params) . 'per_page=';
                            ?>' + this.value"
                            class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500"
                            aria-label="Selecteaza numarul de rezultate afisate pe pagina">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <?php if ($total_pages > 1): ?>
                    <span class="text-sm text-slate-700 dark:text-gray-300">
                        Pagina <?php echo $page; ?> din <?php echo $total_pages; ?>
                        (<?php echo $total_membri; ?> membri)
                    </span>

                    <div class="flex gap-1">
                        <?php
                        $query_params = array_merge($_GET, []);
                        unset($query_params['page']);
                        if (!isset($query_params['status'])) {
                            $query_params['status'] = $status_filter;
                        }
                        $base_url = '/membri?' . http_build_query($query_params) . '&page=';

                        if ($page > 1):
                        ?>
                        <a href="<?php echo $base_url . ($page - 1); ?>"
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">
                            <i data-lucide="chevron-left" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1):
                        ?>
                        <a href="<?php echo $base_url . '1'; ?>"
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">1</a>
                        <?php if ($start_page > 2): ?>
                        <span class="px-3 py-1.5 text-slate-500 dark:text-gray-400">...</span>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="<?php echo $base_url . $i; ?>"
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg <?php echo $i == $page ? 'bg-amber-600 text-white border-amber-600' : 'hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                        <span class="px-3 py-1.5 text-slate-500 dark:text-gray-400">...</span>
                        <?php endif; ?>
                        <a href="<?php echo $base_url . $total_pages; ?>"
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">
                            <?php echo $total_pages; ?>
                        </a>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . ($page + 1); ?>"
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">
                            <i data-lucide="chevron-right" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-sm text-slate-700 dark:text-gray-300">
                        <?php echo $total_membri; ?> membri
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sectiune dreapta jos: precompletare subiect si mesaj pentru WhatsApp/Email -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6" aria-label="Mesaj pentru trimitere catre membri">
            <div class="hidden lg:block" aria-hidden="true"></div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-5 h-5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                    Mesaj pentru WhatsApp / Email
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-3">Subiectul si mesajul se precompleteaza la linkurile Email si WhatsApp din tabel. Se reseteaza la schimbarea afisarii (butoanele de filtrare).</p>
                <form method="post" action="/membri" id="form-mesaj-precompletat">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="save_mesaj_precompletat" value="1">
                    <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <input type="hidden" name="redirect_sort" value="<?php echo htmlspecialchars($sort_col); ?>">
                    <input type="hidden" name="redirect_dir" value="<?php echo htmlspecialchars(strtolower($sort_dir)); ?>">
                    <input type="hidden" name="redirect_per_page" value="<?php echo (int)$per_page; ?>">
                    <input type="hidden" name="redirect_page" value="<?php echo (int)$page; ?>">
                    <input type="hidden" name="redirect_cautare" value="<?php echo htmlspecialchars($cautare); ?>">
                    <input type="hidden" name="redirect_avertizari" value="<?php echo $avertizari_filter ? '1' : ''; ?>">
                    <input type="hidden" name="redirect_actualizare_cnp_ci" value="<?php echo $actualizare_cnp_ci_filter ? '1' : ''; ?>">
                    <input type="hidden" name="redirect_aniversari_azi" value="<?php echo $aniversari_azi_filter ? '1' : ''; ?>">
                    <div class="space-y-3">
                        <div>
                            <label for="mesaj_subiect" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Subiect (pentru email)</label>
                            <input type="text" id="mesaj_subiect" name="mesaj_subiect" value="<?php echo htmlspecialchars($_SESSION['membri_mesaj_subiect'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   placeholder="Subiect email">
                        </div>
                        <div>
                            <label for="mesaj_continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Mesaj (WhatsApp si continut email)</label>
                            <textarea id="mesaj_continut" name="mesaj_continut" rows="4"
                                      class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                      placeholder="Text mesaj"><?php echo htmlspecialchars($_SESSION['membri_mesaj_continut'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salveaza mesajul pentru precompletare">Salveaza mesaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- Dialog formular adaugare membru -->
<dialog id="formular-membru"
        role="dialog"
        aria-modal="true"
        aria-labelledby="titlu-formular"
        aria-describedby="desc-formular"
        class="p-0 rounded-lg shadow-xl max-w-4xl w-[calc(100%-1rem)] sm:w-full mx-2 sm:mx-auto max-h-[90vh] overflow-y-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 id="titlu-formular" class="text-lg font-bold text-slate-900 dark:text-white">Adauga Membru Nou</h2>
            <button type="button"
                    onclick="document.getElementById('formular-membru').close()"
                    class="text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
                    aria-label="Inchide">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
        <p id="desc-formular" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completati campurile de mai jos. Campurile marcate cu * sunt obligatorii.</p>

        <?php require_once APP_ROOT . '/app/views/partials/membri_form.php'; ?>
        <?php render_formular_membru(null, $eroare); ?>
    </div>
</dialog>

<?php require_once APP_ROOT . '/includes/documente_modal.php'; ?>
<?php require_once APP_ROOT . '/includes/incasari_modal.php'; ?>

<style>
.resizable-th {
    position: relative;
    user-select: none;
}
.resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    width: 4px;
    height: 100%;
    cursor: col-resize;
    z-index: 10;
}
.resize-handle:hover {
    background-color: #f59e0b;
}
.resizable-th:last-child .resize-handle {
    display: none;
}
.membri-table-row {
    min-height: calc(2.5rem + 20px);
}
.membri-table-row td {
    padding-top: calc(0.5rem + 5px);
    padding-bottom: calc(0.5rem + 5px);
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    <?php if (!empty($deschide_formular)): ?>
    document.getElementById('formular-membru').showModal();
    document.getElementById('btn-adauga-membru').setAttribute('aria-expanded', 'true');
    <?php endif; ?>

    var btnAdauga = document.getElementById('btn-adauga-membru');
    var dialog = document.getElementById('formular-membru');

    if (dialog) {
        dialog.addEventListener('close', function() {
            if (btnAdauga) btnAdauga.setAttribute('aria-expanded', 'false');
        });

        if (btnAdauga) {
            btnAdauga.addEventListener('click', function() {
                btnAdauga.setAttribute('aria-expanded', 'true');
            });
        }
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Drag and drop pentru redimensionarea coloanelor
    const table = document.getElementById('tabel-membri');
    if (table) {
        const headers = table.querySelectorAll('.resizable-th');
        let currentResize = null;

        headers.forEach((header, index) => {
            const handle = header.querySelector('.resize-handle');
            if (!handle) return;

            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                currentResize = {
                    header: header,
                    startX: e.clientX,
                    startWidth: header.offsetWidth,
                    index: index
                };
                document.addEventListener('mousemove', resizeColumn);
                document.addEventListener('mouseup', stopResize);
                handle.style.backgroundColor = '#f59e0b';
            });
        });

        function resizeColumn(e) {
            if (!currentResize) return;
            const diff = e.clientX - currentResize.startX;
            const newWidth = Math.max(50, currentResize.startWidth + diff);
            currentResize.header.style.width = newWidth + 'px';
            currentResize.header.style.minWidth = newWidth + 'px';
            const colName = currentResize.header.getAttribute('data-col');
            localStorage.setItem('col_width_' + colName, newWidth);
        }

        function stopResize() {
            if (currentResize) {
                const handle = currentResize.header.querySelector('.resize-handle');
                if (handle) handle.style.backgroundColor = '';
            }
            currentResize = null;
            document.removeEventListener('mousemove', resizeColumn);
            document.removeEventListener('mouseup', stopResize);
        }

        headers.forEach(header => {
            const colName = header.getAttribute('data-col');
            const savedWidth = localStorage.getItem('col_width_' + colName);
            if (savedWidth) {
                header.style.width = savedWidth + 'px';
                header.style.minWidth = savedWidth + 'px';
            }
        });
    }
});
</script>
</body>
</html>
