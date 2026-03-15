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

/**
 * Calculeaza indicatorii pentru membri (total, grad, sex, status).
 */
function rapoarte_indicatori_membri(PDO $pdo): array {
    try {
        $stmt_total = $pdo->query("SELECT COUNT(*) as total FROM membri");
        $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_activi = (int)($result_total['total'] ?? 0);

        $stmt_grav = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Grav'");
        $result_grav = $stmt_grav->fetch(PDO::FETCH_ASSOC);
        $grad_grav = (int)($result_grav['total'] ?? 0);

        $stmt_accentuat = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Accentuat'");
        $result_accentuat = $stmt_accentuat->fetch(PDO::FETCH_ASSOC);
        $grad_accentuat = (int)($result_accentuat['total'] ?? 0);

        $stmt_mediu = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Mediu'");
        $result_mediu = $stmt_mediu->fetch(PDO::FETCH_ASSOC);
        $grad_mediu = (int)($result_mediu['total'] ?? 0);

        $stmt_femei = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Feminin'");
        $result_femei = $stmt_femei->fetch(PDO::FETCH_ASSOC);
        $femei = (int)($result_femei['total'] ?? 0);

        $stmt_barbati = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Masculin'");
        $result_barbati = $stmt_barbati->fetch(PDO::FETCH_ASSOC);
        $barbati = (int)($result_barbati['total'] ?? 0);

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
            'grad_accentuat' => $grad_accentuat,
            'grad_mediu' => $grad_mediu,
            'femei' => $femei,
            'barbati' => $barbati,
            'membri_activi' => $membri_activi,
            'membri_suspendati' => $membri_suspendati,
            'membri_arhiva' => $membri_arhiva,
        ];
    } catch (PDOException $e) {
        return [
            'total_activi' => 0,
            'grad_grav' => 0,
            'grad_accentuat' => 0,
            'grad_mediu' => 0,
            'femei' => 0,
            'barbati' => 0,
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
        $raport_interactiuni = [
            'total_apeluri' => $total_apeluri,
            'total_vizite' => $total_vizite,
            'total_general' => $total_apeluri + $total_vizite,
            'categorii' => []
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
