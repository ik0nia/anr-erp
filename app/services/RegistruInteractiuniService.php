<?php
/**
 * RegistruInteractiuniService — Business logic pentru modulul Registru Interacțiuni v2.
 *
 * Încarcă statistici lunare, statistici pe subiecte, interacțiuni recente.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/registru_interactiuni_v2_helper.php';

/**
 * Încarcă toate datele necesare paginii Registru Interacțiuni v2.
 *
 * @param PDO $pdo
 * @param int $an_selectat
 * @return array
 */
function registru_interactiuni_load_data(PDO $pdo, int $an_selectat): array {
    ensure_registru_v2_tables($pdo);

    $statistici_lunare = registru_v2_statistici_lunare($pdo, $an_selectat);
    $statistici_subiecte = registru_v2_statistici_subiecte($pdo);
    $interactiuni_recente_30_zile = registru_v2_interactiuni_recente($pdo, 100, 30);
    $interactiuni_azi = registru_v2_interactiuni_azi($pdo);
    $subiecte_interactiuni = get_subiecte_interactiuni_v2($pdo);

    // Debug: verifică dacă există interacțiuni în baza de date
    if (empty($interactiuni_recente_30_zile)) {
        try {
            $stmt_test = $pdo->query('SELECT COUNT(*) as total FROM registru_interactiuni_v2');
            $total_test = $stmt_test->fetch();
            if ($total_test && $total_test['total'] > 0) {
                // Există interacțiuni dar nu se încarcă - posibilă problemă cu query-ul
                error_log('Registru v2: Există ' . $total_test['total'] . ' interacțiuni în baza de date dar nu se încarcă în listă');
            }
        } catch (PDOException $e) {
            error_log('Registru v2 debug: ' . $e->getMessage());
        }
    }

    return [
        'statistici_lunare' => $statistici_lunare,
        'statistici_subiecte' => $statistici_subiecte,
        'interactiuni_recente_30_zile' => $interactiuni_recente_30_zile,
        'interactiuni_azi' => $interactiuni_azi,
        'subiecte_interactiuni' => $subiecte_interactiuni,
    ];
}
