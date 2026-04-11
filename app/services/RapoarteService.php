<?php
/**
 * RapoarteService — Business logic pentru modulul Rapoarte.
 *
 * Calculeaza indicatori membri, interactiuni, newsletter, statistici.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/newsletter_helper.php';
require_once APP_ROOT . '/includes/registru_interactiuni_v2_helper.php';
require_once APP_ROOT . '/includes/liste_helper.php';
require_once APP_ROOT . '/includes/membri_legitimatii_helper.php';

/**
 * Calculeaza indicatorii pentru membri (total, grad, sex, status).
 */
function rapoarte_indicatori_membri(PDO $pdo): array {
    try {
        $stmt_total = $pdo->query("SELECT COUNT(*) as total FROM membri");
        $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_activi = (int)($result_total['total'] ?? 0);

        $stmt_grav = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Grav' AND (insotitor IS NULL OR insotitor NOT IN ('INDEMNIZATIE INSOTITOR', 'ASISTENT PERSONAL')) AND status_dosar = 'Activ'");
        $result_grav = $stmt_grav->fetch(PDO::FETCH_ASSOC);
        $grad_grav = (int)($result_grav['total'] ?? 0);

        $stmt_grav_asistent = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE (hgrad = 'Grav cu insotitor' OR (hgrad = 'Grav' AND insotitor IN ('INDEMNIZATIE INSOTITOR', 'ASISTENT PERSONAL'))) AND status_dosar = 'Activ'");
        $result_grav_asistent = $stmt_grav_asistent->fetch(PDO::FETCH_ASSOC);
        $grad_grav_cu_asistent = (int)($result_grav_asistent['total'] ?? 0);

        $stmt_accentuat = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Accentuat' AND status_dosar = 'Activ'");
        $result_accentuat = $stmt_accentuat->fetch(PDO::FETCH_ASSOC);
        $grad_accentuat = (int)($result_accentuat['total'] ?? 0);

        $stmt_mediu = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Mediu' AND status_dosar = 'Activ'");
        $result_mediu = $stmt_mediu->fetch(PDO::FETCH_ASSOC);
        $grad_mediu = (int)($result_mediu['total'] ?? 0);

        $stmt_femei = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Feminin' AND status_dosar = 'Activ'");
        $result_femei = $stmt_femei->fetch(PDO::FETCH_ASSOC);
        $femei = (int)($result_femei['total'] ?? 0);

        $stmt_barbati = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Masculin' AND status_dosar = 'Activ'");
        $result_barbati = $stmt_barbati->fetch(PDO::FETCH_ASSOC);
        $barbati = (int)($result_barbati['total'] ?? 0);

        $stmt_cazuri_sociale = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE caz_social = 1");
        $result_cazuri_sociale = $stmt_cazuri_sociale->fetch(PDO::FETCH_ASSOC);
        $cazuri_sociale = (int)($result_cazuri_sociale['total'] ?? 0);

        $stmt_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ'");
        $result_activi = $stmt_activi->fetch(PDO::FETCH_ASSOC);
        $membri_activi = (int)($result_activi['total'] ?? 0);

        $stmt_suspendati = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar IN ('Suspendat', 'Expirat')");
        $result_suspendati = $stmt_suspendati->fetch(PDO::FETCH_ASSOC);
        $membri_suspendati = (int)($result_suspendati['total'] ?? 0);

        $stmt_arhiva = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Decedat'");
        $result_arhiva = $stmt_arhiva->fetch(PDO::FETCH_ASSOC);
        $membri_arhiva = (int)($result_arhiva['total'] ?? 0);

        return [
            'total_activi' => $total_activi,
            'grad_grav' => $grad_grav,
            'grad_grav_cu_asistent' => $grad_grav_cu_asistent,
            'grad_accentuat' => $grad_accentuat,
            'grad_mediu' => $grad_mediu,
            'femei' => $femei,
            'barbati' => $barbati,
            'cazuri_sociale' => $cazuri_sociale,
            'membri_activi' => $membri_activi,
            'membri_suspendati' => $membri_suspendati,
            'membri_arhiva' => $membri_arhiva,
        ];
    } catch (PDOException $e) {
        return [
            'total_activi' => 0,
            'grad_grav' => 0,
            'grad_grav_cu_asistent' => 0,
            'grad_accentuat' => 0,
            'grad_mediu' => 0,
            'femei' => 0,
            'barbati' => 0,
            'cazuri_sociale' => 0,
            'membri_activi' => 0,
            'membri_suspendati' => 0,
            'membri_arhiva' => 0,
        ];
    }
}

/**
 * Incarca datele pentru raportul de interactiuni.
 */
function rapoarte_interactiuni(PDO $pdo): array {
    $raport_interactiuni = ['total_apeluri' => 0, 'total_vizite' => 0, 'total_general' => 0, 'categorii' => []];
    try {
        ensure_registru_v2_tables($pdo);
        $stmt = $pdo->query("SELECT tip, COUNT(*) as n FROM registru_interactiuni_v2 GROUP BY tip");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_apeluri = 0;
        $total_vizite = 0;
        foreach ($results as $r) {
            if ($r['tip'] === 'apel') $total_apeluri = (int)$r['n'];
            if ($r['tip'] === 'vizita') $total_vizite = (int)$r['n'];
        }
        // Statistici pe categorii (subiecte)
        $categorii = [];
        $stmt_cat = $pdo->query("
            SELECT COALESCE(s.nume, r.subiect_alt, 'Necategorizat') as categorie, COUNT(*) as numar
            FROM registru_interactiuni_v2 r
            LEFT JOIN registru_interactiuni_v2_subiecte s ON r.subiect_id = s.id
            GROUP BY categorie
            ORDER BY numar DESC
        ");
        while ($row_cat = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
            $categorii[$row_cat['categorie']] = (int)$row_cat['numar'];
        }

        $raport_interactiuni = [
            'total_apeluri' => $total_apeluri,
            'total_vizite' => $total_vizite,
            'total_general' => $total_apeluri + $total_vizite,
            'categorii' => $categorii
        ];
    } catch (PDOException $e) {
        error_log('Eroare raport interactiuni: ' . $e->getMessage());
    }
    return $raport_interactiuni;
}

/**
 * Incarca lista de newslettere pentru rapoarte.
 */
function rapoarte_newsletter(PDO $pdo): array {
    return newsletter_lista_rapoarte($pdo);
}

/**
 * Calculeaza statisticile detaliate pentru membri activi.
 * Returneaza ['statistici_membri' => ..., 'statistici_localitati' => ...].
 */
function rapoarte_statistici(PDO $pdo): array {
    try {
        // Membri activi - total
        $stmt_total_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ'");
        $total_activi_stat = (int)$stmt_total_activi->fetch(PDO::FETCH_ASSOC)['total'];

        // Membri activi - Femei/Barbati
        $stmt_femei_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND sex = 'Feminin'");
        $femei_activi = (int)$stmt_femei_activi->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt_barbati_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND sex = 'Masculin'");
        $barbati_activi = (int)$stmt_barbati_activi->fetch(PDO::FETCH_ASSOC)['total'];

        // Membri activi - Urban/Rural
        $stmt_urban_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND tipmediuur = 'Urban'");
        $urban_activi = (int)$stmt_urban_activi->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt_rural_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND tipmediuur = 'Rural'");
        $rural_activi = (int)$stmt_rural_activi->fetch(PDO::FETCH_ASSOC)['total'];

        // Grupe de varsta pentru membri activi
        $grupe_varsta = [];
        $grupe_varsta_labels = [
            '0-18' => [0, 18],
            '18-35' => [18, 35],
            '35-45' => [35, 45],
            '45-55' => [45, 55],
            '55-65' => [55, 65],
            '65-75' => [65, 75],
            '75-85' => [75, 85],
            '85+' => [85, 200]
        ];

        foreach ($grupe_varsta_labels as $label => $range) {
            $min_age = $range[0];
            $max_age = $range[1] === 200 ? 200 : $range[1];

            if ($max_age === 200) {
                // 85+ ani
                $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN sex = 'Feminin' THEN 1 ELSE 0 END) as femei,
                    SUM(CASE WHEN sex = 'Masculin' THEN 1 ELSE 0 END) as barbati,
                    SUM(CASE WHEN tipmediuur = 'Urban' THEN 1 ELSE 0 END) as urban,
                    SUM(CASE WHEN tipmediuur = 'Rural' THEN 1 ELSE 0 END) as rural
                    FROM membri
                    WHERE status_dosar = 'Activ'
                    AND datanastere IS NOT NULL
                    AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$min_age]);
            } else {
                // Grupe normale
                $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN sex = 'Feminin' THEN 1 ELSE 0 END) as femei,
                    SUM(CASE WHEN sex = 'Masculin' THEN 1 ELSE 0 END) as barbati,
                    SUM(CASE WHEN tipmediuur = 'Urban' THEN 1 ELSE 0 END) as urban,
                    SUM(CASE WHEN tipmediuur = 'Rural' THEN 1 ELSE 0 END) as rural
                    FROM membri
                    WHERE status_dosar = 'Activ'
                    AND datanastere IS NOT NULL
                    AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= ?
                    AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$min_age, $max_age]);
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $grupe_varsta[$label] = [
                'total' => (int)$result['total'],
                'femei' => (int)$result['femei'],
                'barbati' => (int)$result['barbati'],
                'urban' => (int)$result['urban'],
                'rural' => (int)$result['rural']
            ];
        }

        $statistici_membri = [
            'total' => $total_activi_stat,
            'femei' => $femei_activi,
            'barbati' => $barbati_activi,
            'urban' => $urban_activi,
            'rural' => $rural_activi,
            'grupe_varsta' => $grupe_varsta
        ];

        // Statistici pe localitati
        $stmt_localitati = $pdo->query("
            SELECT
                domloc as localitate,
                judet_domiciliu as judet,
                primaria,
                COUNT(*) as total,
                SUM(CASE WHEN sex = 'Feminin' THEN 1 ELSE 0 END) as femei,
                SUM(CASE WHEN sex = 'Masculin' THEN 1 ELSE 0 END) as barbati,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 0 AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < 18 THEN 1 ELSE 0 END) as varsta_0_18,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 18 AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < 35 THEN 1 ELSE 0 END) as varsta_18_35,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 35 AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < 65 THEN 1 ELSE 0 END) as varsta_35_65,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 65 THEN 1 ELSE 0 END) as varsta_65_plus
            FROM membri
            WHERE status_dosar = 'Activ'
            AND domloc IS NOT NULL AND domloc != ''
            GROUP BY domloc, judet_domiciliu, primaria
            ORDER BY domloc ASC, judet_domiciliu ASC
        ");

        $statistici_localitati = $stmt_localitati->fetchAll(PDO::FETCH_ASSOC);

        return [
            'statistici_membri' => $statistici_membri,
            'statistici_localitati' => $statistici_localitati,
        ];
    } catch (PDOException $e) {
        error_log('Eroare calculare statistici: ' . $e->getMessage());
        return [
            'statistici_membri' => [
                'total' => 0,
                'femei' => 0,
                'barbati' => 0,
                'urban' => 0,
                'rural' => 0,
                'grupe_varsta' => []
            ],
            'statistici_localitati' => [],
        ];
    }
}

/**
 * Calculeaza raportul anual pentru listele de prezenta "Socializare".
 */
function rapoarte_socializare(PDO $pdo, int $an_selectat): array {
    $an_curent = (int)date('Y');
    if ($an_selectat < 2000 || $an_selectat > ($an_curent + 1)) {
        $an_selectat = $an_curent;
    }

    $empty = [
        'ani_disponibili' => [$an_curent],
        'an_selectat' => $an_selectat,
        'nr_activitati' => 0,
        'total_participanti' => 0,
        'femei' => 0,
        'barbati' => 0,
        'fara_sex' => 0,
        'grupe_varsta' => [
            '0-17 ani' => 0,
            '18-35 ani' => 0,
            '36-50 ani' => 0,
            '51-65 ani' => 0,
            '66+ ani' => 0,
        ],
        'liste' => [],
    ];

    try {
        $ani_disponibili = [];
        $stmt_ani = $pdo->prepare("
            SELECT DISTINCT YEAR(data_lista) AS an
            FROM liste_prezenta
            WHERE detalii_activitate = ?
            ORDER BY an DESC
        ");
        $stmt_ani->execute([LISTA_SOCIALIZARE_ACTIVITATE]);
        foreach ($stmt_ani->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $an = (int)($row['an'] ?? 0);
            if ($an > 0) $ani_disponibili[] = $an;
        }
        if (!in_array($an_curent, $ani_disponibili, true)) {
            $ani_disponibili[] = $an_curent;
        }
        rsort($ani_disponibili);
        if (!in_array($an_selectat, $ani_disponibili, true)) {
            $an_selectat = $ani_disponibili[0] ?? $an_curent;
        }

        $stmt_activitati = $pdo->prepare("
            SELECT l.id, l.data_lista, l.detalii_activitate, COUNT(lm.id) AS participanti
            FROM liste_prezenta l
            LEFT JOIN liste_prezenta_membri lm ON lm.lista_id = l.id
            WHERE l.detalii_activitate = ?
              AND YEAR(l.data_lista) = ?
            GROUP BY l.id, l.data_lista, l.detalii_activitate
            ORDER BY l.data_lista ASC, l.id ASC
        ");
        $stmt_activitati->execute([LISTA_SOCIALIZARE_ACTIVITATE, $an_selectat]);
        $liste = [];
        $total_participanti = 0;
        foreach ($stmt_activitati->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $n = (int)($row['participanti'] ?? 0);
            $total_participanti += $n;
            $liste[] = [
                'id' => (int)$row['id'],
                'data_lista' => (string)$row['data_lista'],
                'detalii_activitate' => (string)($row['detalii_activitate'] ?? ''),
                'nr_participanti' => $n,
            ];
        }

        $stmt_sex = $pdo->prepare("
            SELECT
                SUM(CASE WHEN m.sex = 'Feminin' THEN 1 ELSE 0 END) AS femei,
                SUM(CASE WHEN m.sex = 'Masculin' THEN 1 ELSE 0 END) AS barbati,
                SUM(CASE WHEN m.sex IS NULL OR m.sex NOT IN ('Feminin', 'Masculin') THEN 1 ELSE 0 END) AS fara_sex
            FROM liste_prezenta l
            JOIN liste_prezenta_membri lm ON lm.lista_id = l.id
            JOIN membri m ON m.id = lm.membru_id
            WHERE l.detalii_activitate = ?
              AND YEAR(l.data_lista) = ?
        ");
        $stmt_sex->execute([LISTA_SOCIALIZARE_ACTIVITATE, $an_selectat]);
        $sex = $stmt_sex->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt_varsta = $pdo->prepare("
            SELECT
                SUM(CASE WHEN m.datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.datanastere, l.data_lista) BETWEEN 0 AND 17 THEN 1 ELSE 0 END) AS g_0_17,
                SUM(CASE WHEN m.datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.datanastere, l.data_lista) BETWEEN 18 AND 35 THEN 1 ELSE 0 END) AS g_18_35,
                SUM(CASE WHEN m.datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.datanastere, l.data_lista) BETWEEN 36 AND 50 THEN 1 ELSE 0 END) AS g_36_50,
                SUM(CASE WHEN m.datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.datanastere, l.data_lista) BETWEEN 51 AND 65 THEN 1 ELSE 0 END) AS g_51_65,
                SUM(CASE WHEN m.datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.datanastere, l.data_lista) >= 66 THEN 1 ELSE 0 END) AS g_66_plus
            FROM liste_prezenta l
            JOIN liste_prezenta_membri lm ON lm.lista_id = l.id
            JOIN membri m ON m.id = lm.membru_id
            WHERE l.detalii_activitate = ?
              AND YEAR(l.data_lista) = ?
        ");
        $stmt_varsta->execute([LISTA_SOCIALIZARE_ACTIVITATE, $an_selectat]);
        $varste = $stmt_varsta->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'ani_disponibili' => !empty($ani_disponibili) ? $ani_disponibili : [$an_curent],
            'an_selectat' => $an_selectat,
            'nr_activitati' => count($liste),
            'total_participanti' => $total_participanti,
            'femei' => (int)($sex['femei'] ?? 0),
            'barbati' => (int)($sex['barbati'] ?? 0),
            'fara_sex' => (int)($sex['fara_sex'] ?? 0),
            'grupe_varsta' => [
                '0-17 ani' => (int)($varste['g_0_17'] ?? 0),
                '18-35 ani' => (int)($varste['g_18_35'] ?? 0),
                '36-50 ani' => (int)($varste['g_36_50'] ?? 0),
                '51-65 ani' => (int)($varste['g_51_65'] ?? 0),
                '66+ ani' => (int)($varste['g_66_plus'] ?? 0),
            ],
            'liste' => $liste,
        ];
    } catch (PDOException $e) {
        return $empty;
    }
}

/**
 * Raport Borderou legitimații în interval calendaristic selectat.
 */
function rapoarte_borderou_legitimatii(PDO $pdo, string $data_de_la, string $data_pana_la): array {
    membri_legitimatii_ensure_table($pdo);

    $azi = date('Y-m-d');
    $prima_zi_an = date('Y-01-01');

    $data_de_la = trim($data_de_la) !== '' ? trim($data_de_la) : $prima_zi_an;
    $data_pana_la = trim($data_pana_la) !== '' ? trim($data_pana_la) : $azi;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_de_la)) {
        $data_de_la = $prima_zi_an;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_pana_la)) {
        $data_pana_la = $azi;
    }
    if ($data_de_la > $data_pana_la) {
        $tmp = $data_de_la;
        $data_de_la = $data_pana_la;
        $data_pana_la = $tmp;
    }

    $raw = membri_legitimatii_borderou($pdo, $data_de_la, $data_pana_la);
    $tipuri = membri_legitimatii_tipuri_actiune();
    $operatiuni = [];
    foreach ($raw as $idx => $row) {
        $tip = membri_legitimatii_tip_normalizat((string)($row['tip_actiune'] ?? ''));
        $operatiuni[] = [
            'nr_crt' => $idx + 1,
            'data_actiune' => (string)($row['data_actiune'] ?? ''),
            'data_actiune_display' => !empty($row['data_actiune']) ? date(DATE_FORMAT, strtotime((string)$row['data_actiune'])) : '-',
            'membru_nume' => trim((string)($row['nume'] ?? '') . ' ' . (string)($row['prenume'] ?? '')),
            'dosarnr' => (string)($row['dosarnr'] ?? ''),
            'actiune' => $tip,
            'actiune_label' => $tipuri[$tip] ?? $tip,
            'utilizator' => (string)($row['utilizator'] ?? 'Sistem'),
        ];
    }

    $statRaw = membri_legitimatii_statistici($pdo, $data_de_la, $data_pana_la);
    $stat = [
        'total' => (int)($statRaw['total'] ?? 0),
        'nou' => (int)($statRaw['legitimatie_membru_nou'] ?? 0),
        'plina' => (int)($statRaw['inlocuire_legitimatie_plina'] ?? 0),
        'pierduta' => (int)($statRaw['inlocuire_legitimatie_pierduta'] ?? 0),
    ];

    return [
        'data_de_la' => $data_de_la,
        'data_pana_la' => $data_pana_la,
        'operatiuni' => $operatiuni,
        'statistici' => $stat,
    ];
}
