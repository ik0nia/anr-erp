<?php
/**
 * BpaService — Business logic pentru modulul Ajutoare BPA.
 *
 * Gestiune stoc produse BPA (kg), documente, tabele de distributie, rapoarte.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/bpa_helper.php';
require_once APP_ROOT . '/includes/liste_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Harta mesajelor de succes dupa redirect.
 */
function bpa_success_messages(): array {
    return [
        'document'          => 'Documentul a fost adaugat.',
        'tabel'             => 'Tabelul de distributie a fost salvat. Cantitatea a fost scazuta din stoc.',
        'editeaza_gestiune' => 'Documentul a fost actualizat.',
        'sterge_gestiune'   => 'Documentul a fost sters din gestiune.',
    ];
}

/**
 * Sterge un document din gestiune.
 *
 * @return bool
 */
function bpa_service_sterge_gestiune(PDO $pdo, int $id): bool {
    if ($id > 0 && bpa_delete_gestiune($pdo, $id)) {
        log_activitate($pdo, "BPA: document gestiune sters ID {$id}");
        return true;
    }
    return false;
}

/**
 * Actualizeaza un document de gestiune.
 *
 * @return bool
 */
function bpa_service_editeaza_gestiune(PDO $pdo, int $id, string $nr, string $data_doc, string $tip, float $cantitate, ?string $loc, ?int $nr_benef): bool {
    if ($id > 0 && $nr && $data_doc && $tip && $cantitate > 0 && bpa_update_gestiune($pdo, $id, $nr, $data_doc, $tip, $cantitate, $loc, $nr_benef)) {
        log_activitate($pdo, "BPA: document gestiune actualizat ID {$id}");
        return true;
    }
    return false;
}

/**
 * Adauga un document (aviz sau tabel pe hartie).
 *
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function bpa_service_adauga_document(PDO $pdo, string $tip, string $nr, string $data_doc, float $cantitate, ?string $loc, ?int $nr_benef, string $utilizator): array {
    if (!$tip || !$nr || !$data_doc || $cantitate <= 0) {
        return ['success' => false, 'error' => 'Completati tip document, numar, data si cantitate (pozitiva).'];
    }
    bpa_adauga_document($pdo, $nr, $data_doc, $tip, $cantitate, $loc, $nr_benef, $utilizator);
    if ($tip === 'aviz') {
        require_once APP_ROOT . '/includes/registratura_helper.php';
        registratura_inregistreaza_document($pdo, [
            'tip_act' => 'Inregistrare document',
            'continut_document' => 'Aviz BPA',
            'provine_din' => 'Banca pentru alimente Oradea',
            'nr_document' => $nr,
            'data_document' => $data_doc,
            'task_deschis' => 0,
        ]);
    }
    log_activitate($pdo, "BPA: document adaugat - {$tip} {$nr}");
    return ['success' => true, 'error' => null];
}

/**
 * Salveaza un tabel de distributie (creare sau actualizare).
 *
 * @return array ['success'=>bool, 'id'=>int|null, 'error'=>string|null]
 */
function bpa_service_salveaza_tabel(PDO $pdo, int $tabel_id, string $nr_tabel, string $data_tabel, bool $predare_sediul, bool $predare_centru, bool $livrare_domiciliu, array $randuri_raw, string $utilizator): array {
    $randuri = [];
    if (!empty($randuri_raw) && is_array($randuri_raw)) {
        foreach ($randuri_raw as $r) {
            $greutate = (float)($r['greutate_pachet'] ?? 0);
            if (empty($r['membru_id']) && empty($r['nume_manual']) && empty($r['prenume_manual']) && $greutate <= 0) continue;
            $randuri[] = [
                'membru_id'       => isset($r['membru_id']) && (int)$r['membru_id'] > 0 ? (int)$r['membru_id'] : null,
                'nume_manual'     => trim($r['nume_manual'] ?? ''),
                'prenume_manual'  => trim($r['prenume_manual'] ?? ''),
                'localitate'      => trim($r['localitate'] ?? ''),
                'seria_nr_ci'     => trim($r['seria_nr_ci'] ?? ''),
                'data_nastere'    => !empty($r['data_nastere']) ? $r['data_nastere'] : null,
                'greutate_pachet' => $greutate,
                'semnatura_note'  => trim($r['semnatura_note'] ?? ''),
            ];
        }
    }
    if (!$nr_tabel || !$data_tabel) {
        return ['success' => false, 'id' => null, 'error' => 'Completati numarul si data tabelului.'];
    }
    $id = bpa_salveaza_tabel($pdo, $tabel_id, $nr_tabel, $data_tabel, $predare_sediul, $predare_centru, $livrare_domiciliu, $randuri, $utilizator);
    log_activitate($pdo, "BPA: tabel distributie salvat - {$nr_tabel}");

    // Log distributie in istoricul fiecarui membru doar la creare (nu re-save)
    if ($tabel_id == 0) {
        foreach ($randuri as $rand) {
            if (!empty($rand['membru_id'])) {
                $membru_id = (int)$rand['membru_id'];
                log_activitate($pdo, "BPA: Distribuit pachet - Tabel distributie #{$nr_tabel}", null, $membru_id);
            }
        }
    }

    return ['success' => true, 'id' => (int)$id, 'error' => null];
}

/**
 * Incarca toate datele necesare pentru afisarea paginii.
 *
 * @return array
 */
function bpa_service_load_page_data(PDO $pdo, string $perioada, int $edit_id): array {
    bpa_ensure_tables($pdo);

    $tabel_edit = null;
    if ($edit_id > 0) {
        $tabel_edit = bpa_get_tabel($pdo, $edit_id);
    }

    $stoc                 = bpa_stoc_curent($pdo);
    $total_preluat        = bpa_total_preluat($pdo);
    $total_distribuit     = bpa_total_distribuit($pdo);
    $nr_beneficiari_unici = bpa_nr_beneficiari_unici($pdo);
    $nr_pachete           = bpa_nr_pachete_distribuite($pdo);
    $lista_gestiune       = bpa_lista_gestiune($pdo);
    $lista_tabele         = bpa_lista_tabele($pdo);

    // Perioada pentru rapoarte
    $data_inceput = null;
    $data_sfarsit = null;
    if ($perioada === 'luna') {
        $data_inceput = date('Y-m-01');
        $data_sfarsit = date('Y-m-t');
    } elseif ($perioada === 'an') {
        $data_inceput = date('Y-01-01');
        $data_sfarsit = date('Y-12-31');
    } elseif ($perioada === 'toata') {
        $data_inceput = '2020-01-01';
        $data_sfarsit = date('Y-m-d');
    }
    $indicatori_rap  = bpa_indicatori_perioada($pdo, $data_inceput, $data_sfarsit);
    $localitati_rap  = bpa_localitati_beneficiari($pdo, $data_inceput, $data_sfarsit);
    $varste_rap      = bpa_varste_beneficiari($pdo, $data_inceput, $data_sfarsit);
    $sex_rap         = bpa_sex_beneficiari($pdo, $data_inceput, $data_sfarsit);
    $evolutie_rap    = [];
    if ($data_inceput && $data_sfarsit) {
        $evolutie_rap = bpa_evolutie_lunara($pdo, $data_inceput, $data_sfarsit);
    }

    return compact(
        'tabel_edit', 'stoc', 'total_preluat', 'total_distribuit',
        'nr_beneficiari_unici', 'nr_pachete', 'lista_gestiune', 'lista_tabele',
        'indicatori_rap', 'localitati_rap', 'varste_rap', 'sex_rap', 'evolutie_rap'
    );
}
