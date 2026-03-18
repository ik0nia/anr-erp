<?php
/**
 * Helper Registru Interacțiuni v2 - CRM ANR Bihor
 * Gestionează tabelele și datele pentru registrul de apeluri și vizite (v2).
 * Modul independent de registru_interactiuni (v1).
 */

/**
 * Creează tabelele registrului v2 dacă nu există.
 */
function ensure_registru_v2_tables($pdo) {
    // No-op: schema is managed by install/schema/migration.php
    return;
}

/**
 * Returnează subiectele active pentru dropdown (formulare interacțiuni v2).
 */
function get_subiecte_interactiuni_v2($pdo) {
    try {
        $stmt = $pdo->query('SELECT id, nume, ordine FROM registru_interactiuni_v2_subiecte WHERE activ = 1 ORDER BY ordine ASC, nume ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Returnează toate subiectele (active și inactive) pentru administrare în Setări.
 */
function get_subiecte_interactiuni_v2_toate($pdo) {
    try {
        $stmt = $pdo->query('SELECT id, nume, ordine, activ FROM registru_interactiuni_v2_subiecte ORDER BY ordine ASC, nume ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Obține statistici pentru interacțiuni v2 pe lunile anului.
 * Returnează array cu chei: 'luna' => ['apel' => count, 'vizita' => count]
 */
function registru_v2_statistici_lunare($pdo, $an = null) {
    if ($an === null) {
        $an = date('Y');
    }
    $statistici = [];
    try {
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(data_ora) as luna,
                tip,
                COUNT(*) as numar
            FROM registru_interactiuni_v2
            WHERE YEAR(data_ora) = ?
            GROUP BY MONTH(data_ora), tip
            ORDER BY luna ASC
        ");
        $stmt->execute([$an]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $luna = (int)$row['luna'];
            $tip = $row['tip'];
            if (!isset($statistici[$luna])) {
                $statistici[$luna] = ['apel' => 0, 'vizita' => 0, 'incasare' => 0];
            }
            $statistici[$luna][$tip] = (int)$row['numar'];
        }
    } catch (PDOException $e) {
        error_log('Eroare statistici lunare v2: ' . $e->getMessage());
    }
    return $statistici;
}

/**
 * Obține statistici pe subiecte pentru interacțiuni v2.
 * Returnează array cu chei: 'subiect_nume' => count
 */
function registru_v2_statistici_subiecte($pdo) {
    $statistici = [];
    try {
        // Subiecte din listă
        $stmt = $pdo->query("
            SELECT s.id, s.nume, COUNT(r.id) as numar
            FROM registru_interactiuni_v2_subiecte s
            LEFT JOIN registru_interactiuni_v2 r ON r.subiect_id = s.id
            GROUP BY s.id, s.nume
            HAVING numar > 0
            ORDER BY numar DESC, s.nume ASC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statistici[$row['nume']] = (int)$row['numar'];
        }
        
        // Subiecte "Alt subiect"
        $stmt = $pdo->query("
            SELECT COUNT(*) as numar
            FROM registru_interactiuni_v2
            WHERE subiect_alt IS NOT NULL AND TRIM(COALESCE(subiect_alt,'')) != ''
        ");
        $alt = (int)$stmt->fetchColumn();
        if ($alt > 0) {
            $statistici['Alt subiect'] = $alt;
        }
    } catch (PDOException $e) {
        error_log('Eroare statistici subiecte v2: ' . $e->getMessage());
    }
    return $statistici;
}

/**
 * Obține numărul de interacțiuni pentru ziua curentă.
 * Returnează array: ['apel' => count, 'vizita' => count]
 */
function registru_v2_interactiuni_azi($pdo) {
    $rezultat = ['apel' => 0, 'vizita' => 0, 'incasare' => 0];
    try {
        $azi = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT tip, COUNT(*) as numar
            FROM registru_interactiuni_v2
            WHERE DATE(data_ora) = ?
            GROUP BY tip
        ");
        $stmt->execute([$azi]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rezultat[$row['tip']] = (int)$row['numar'];
        }
    } catch (PDOException $e) {
        error_log('Eroare interacțiuni azi v2: ' . $e->getMessage());
    }
    return $rezultat;
}

/**
 * Obține interacțiunile recente pentru afișare.
 * @param int $limit Număr maxim de interacțiuni (default: 50)
 * @param int $zile Număr de zile în urmă (default: null = toate)
 */
function registru_v2_interactiuni_recente($pdo, $limit = 50, $zile = null) {
    try {
        // Folosim (int) pentru a preveni SQL injection și pentru compatibilitate cu MySQL
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;
        if ($limit > 1000) $limit = 1000; // Limitare maximă pentru siguranță
        
        $where_cond = '';
        $params = [];
        
        if ($zile !== null && $zile > 0) {
            $where_cond = 'WHERE r.data_ora >= DATE_SUB(NOW(), INTERVAL ? DAY)';
            $params[] = (int)$zile;
        }
        
        $stmt = $pdo->prepare("
            SELECT r.*, s.nume as subiect_nume
            FROM registru_interactiuni_v2 r
            LEFT JOIN registru_interactiuni_v2_subiecte s ON r.subiect_id = s.id
            {$where_cond}
            ORDER BY r.data_ora DESC
            LIMIT " . $limit
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Eroare interacțiuni recente v2: ' . $e->getMessage());
        return [];
    }
}

/**
 * Înregistrează o încasare în Registrul de Interacțiuni v2.
 * @param string $persoana Numele persoanei (membru/donator)
 * @param string $descriere Detalii încasare (tip, sumă, serie/nr chitanță)
 * @param string|null $utilizator Utilizatorul care a înregistrat
 */
function registru_v2_adauga_incasare($pdo, $persoana, $descriere, $utilizator = null) {
    if ($utilizator === null) {
        $utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Sistem';
    }
    try {
        $stmt = $pdo->prepare("
            INSERT INTO registru_interactiuni_v2 (tip, persoana, notite, utilizator, data_ora)
            VALUES ('incasare', ?, ?, ?, NOW())
        ");
        $stmt->execute([$persoana, $descriere, $utilizator]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Eroare adăugare încasare în registru v2: ' . $e->getMessage());
        return false;
    }
}
