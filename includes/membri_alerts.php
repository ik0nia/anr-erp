<?php
/**
 * Helper pentru mesaje de avertizare membri
 */

/**
 * Verifică dacă actul de identitate expiră în următoarele 60 zile
 * Folosește cidataexp dacă este disponibil, altfel calculează din cidataelib + 10 ani
 */
function verifica_expirare_ci($cidataexp = null, $cidataelib = null) {
    // Preferă cidataexp dacă este disponibil
    if (!empty($cidataexp)) {
        $data_expirare = new DateTime($cidataexp);
    } elseif (!empty($cidataelib)) {
        $data_elib = new DateTime($cidataelib);
        $data_expirare = clone $data_elib;
        $data_expirare->modify('+10 years'); // Actele de identitate expiră după 10 ani
    } else {
        return false;
    }
    
    $acum = new DateTime();
    $in_60_zile = clone $acum;
    $in_60_zile->modify('+60 days');
    
    return $data_expirare <= $in_60_zile && $data_expirare > $acum;
}

/**
 * Verifică dacă certificatul de handicap expiră în următoarele 60 zile
 */
function verifica_expirare_certificat($ceexp) {
    if (empty($ceexp)) {
        return false;
    }
    
    $data_expirare = new DateTime($ceexp);
    $acum = new DateTime();
    $in_60_zile = clone $acum;
    $in_60_zile->modify('+60 days');
    
    return $data_expirare <= $in_60_zile && $data_expirare > $acum;
}

/**
 * Generează mesajele de avertizare pentru un membru
 * @param array $membru Date membru
 * @param PDO $pdo Conexiune la baza de date (opțional, pentru verificare alerts dismissed)
 */
function genereaza_alerts_membru($membru, $pdo = null) {
    $alerts = [];
    $membru_id = $membru['id'] ?? null;
    
    // Verifică GDPR
    if (empty($membru['gdpr']) || $membru['gdpr'] == 0) {
        $alerts[] = [
            'tip' => 'error',
            'mesaj' => 'Lipsește acordul GDPR',
            'alert_key' => 'gdpr'
        ];
    }
    
    // Verifică expirare CI (folosește cidataexp dacă disponibil, altfel calculează din cidataelib)
    $cidataexp = $membru['cidataexp'] ?? null;
    $cidataelib = $membru['cidataelib'] ?? null;
    if (verifica_expirare_ci($cidataexp, $cidataelib)) {
        // Preferă cidataexp dacă este disponibil
        if (!empty($cidataexp)) {
            $data_expirare = new DateTime($cidataexp);
        } else {
            $data_expirare = new DateTime($cidataelib);
            $data_expirare->modify('+10 years');
        }
        $data_expirare_str = $data_expirare->format('Y-m-d');
        $acum = new DateTime();
        
        // Verifică dacă alerta a fost marcată ca informată sau dacă membru a fost notificat
        $dismissed = false;
        if ($pdo && $membru_id) {
            try {
                // Verifică dacă membru a fost notificat (expira_ci_notificat = 1)
                if (isset($membru['expira_ci_notificat']) && $membru['expira_ci_notificat'] == 1) {
                    $dismissed = true;
                } else {
                    // Verifică în tabelul de alerts dismissed
                    $stmt = $pdo->prepare('SELECT data_informat FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
                    $stmt->execute([$membru_id, 'ci']);
                    $dismissed_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($dismissed_data && $data_expirare > $acum) {
                        $dismissed = true;
                    }
                }
            } catch (PDOException $e) {
                // Ignoră eroarea
            }
        }
        
        if (!$dismissed) {
            $alerts[] = [
                'tip' => 'warning',
                'mesaj' => 'Expira C.I. pe ' . $data_expirare->format('d.m.Y'),
                'alert_key' => 'ci',
                'data_expirare' => $data_expirare_str
            ];
        }
    }
    
    // Verifică expirare certificat handicap
    if (verifica_expirare_certificat($membru['ceexp'] ?? null)) {
        $data_expirare = new DateTime($membru['ceexp']);
        $data_expirare_str = $data_expirare->format('Y-m-d');
        $acum = new DateTime();
        
        // Verifică dacă alerta a fost marcată ca informată sau dacă membru a fost notificat
        $dismissed = false;
        if ($pdo && $membru_id) {
            try {
                // Verifică dacă membru a fost notificat (expira_ch_notificat = 1)
                if (isset($membru['expira_ch_notificat']) && $membru['expira_ch_notificat'] == 1) {
                    $dismissed = true;
                } else {
                    // Verifică în tabelul de alerts dismissed
                    $stmt = $pdo->prepare('SELECT data_informat FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
                    $stmt->execute([$membru_id, 'ch']);
                    $dismissed_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($dismissed_data && $data_expirare > $acum) {
                        $dismissed = true;
                    }
                }
            } catch (PDOException $e) {
                // Ignoră eroarea
            }
        }
        
        if (!$dismissed) {
            $alerts[] = [
                'tip' => 'warning',
                'mesaj' => 'Expira C.H. pe ' . $data_expirare->format('d.m.Y'),
                'alert_key' => 'ch',
                'data_expirare' => $data_expirare_str
            ];
        }
    }
    
    return $alerts;
}

/**
 * Generează mesajele de avertizare pentru profil membru: toate alertele (inclusiv cele marcate ca informate)
 * sunt returnate, cu flag 'dismissed' => true/false. În listă (membri.php) se folosește genereaza_alerts_membru
 * care ascunde Expira C.I. / Expira C.H. când sunt bifate, până după data expirării.
 */
function genereaza_alerts_membru_pentru_profil($membru, $pdo = null) {
    $alerts = [];
    $membru_id = $membru['id'] ?? null;
    
    // GDPR - fără dismissed
    if (empty($membru['gdpr']) || $membru['gdpr'] == 0) {
        $alerts[] = [
            'tip' => 'error',
            'mesaj' => 'Lipsește acordul GDPR',
            'alert_key' => 'gdpr',
            'dismissed' => false
        ];
    }
    
    // Expirare CI - întotdeauna în listă când e în fereastra de expirare, cu flag dismissed
    $cidataexp = $membru['cidataexp'] ?? null;
    $cidataelib = $membru['cidataelib'] ?? null;
    if (verifica_expirare_ci($cidataexp, $cidataelib)) {
        // Preferă cidataexp dacă este disponibil
        if (!empty($cidataexp)) {
            $data_expirare = new DateTime($cidataexp);
        } else {
            $data_expirare = new DateTime($cidataelib);
            $data_expirare->modify('+10 years');
        }
        $data_expirare_str = $data_expirare->format('Y-m-d');
        $dismissed = false;
        if ($pdo && $membru_id) {
            try {
                // Verifică dacă membru a fost notificat
                if (isset($membru['expira_ci_notificat']) && $membru['expira_ci_notificat'] == 1) {
                    $dismissed = true;
                } else {
                    $stmt = $pdo->prepare('SELECT 1 FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
                    $stmt->execute([$membru_id, 'ci']);
                    $dismissed = (bool) $stmt->fetch();
                }
            } catch (PDOException $e) {}
        }
        $alerts[] = [
            'tip' => 'warning',
            'mesaj' => 'Expira C.I. pe ' . $data_expirare->format('d.m.Y'),
            'alert_key' => 'ci',
            'data_expirare' => $data_expirare_str,
            'dismissed' => $dismissed
        ];
    }
    
    // Expirare C.H.
    if (verifica_expirare_certificat($membru['ceexp'] ?? null)) {
        $data_expirare = new DateTime($membru['ceexp']);
        $data_expirare_str = $data_expirare->format('Y-m-d');
        $dismissed = false;
        if ($pdo && $membru_id) {
            try {
                // Verifică dacă membru a fost notificat
                if (isset($membru['expira_ch_notificat']) && $membru['expira_ch_notificat'] == 1) {
                    $dismissed = true;
                } else {
                    $stmt = $pdo->prepare('SELECT 1 FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
                    $stmt->execute([$membru_id, 'ch']);
                    $dismissed = (bool) $stmt->fetch();
                }
            } catch (PDOException $e) {}
        }
        $alerts[] = [
            'tip' => 'warning',
            'mesaj' => 'Expira C.H. pe ' . $data_expirare->format('d.m.Y'),
            'alert_key' => 'ch',
            'data_expirare' => $data_expirare_str,
            'dismissed' => $dismissed
        ];
    }
    
    // Cotizație neachitată: verifică din tabelul incasari + scutiri
    if ($pdo && $membru_id) {
        try {
            require_once __DIR__ . '/cotizatii_helper.php';
            require_once __DIR__ . '/incasari_helper.php';
            $an_curent = (int)date('Y');
            $scutit = cotizatii_membru_este_scutit($pdo, $membru_id);
            if (empty($scutit) && !incasari_cotizatie_achitata_an($pdo, $membru_id, $an_curent)) {
                $alerts[] = [
                    'tip' => 'warning',
                    'mesaj' => 'Cotizatie ' . $an_curent . ' neachitata',
                    'alert_key' => 'cotizatie',
                    'dismissed' => false
                ];
            }
        } catch (PDOException $e) {}
    }
    
    return $alerts;
}

/**
 * Afișează badge-uri de avertizare pentru tabel
 * @param array $membru Date membru
 * @param int|null $membru_id ID membru
 * @param PDO|null $pdo Conexiune la baza de date (opțional, pentru verificare alerts dismissed)
 */
function render_alerts_badge($membru, $membru_id = null, $pdo = null) {
    $alerts = genereaza_alerts_membru($membru, $pdo);
    
    if (empty($alerts)) {
        return '';
    }
    
    $membru_id = $membru_id ?? ($membru['id'] ?? null);
    $link_start = $membru_id ? '<a href="/membru-profil?id=' . $membru_id . '" class="inline-block">' : '';
    $link_end = $membru_id ? '</a>' : '';
    
    $html = '<div class="flex flex-row flex-nowrap gap-1 items-center">';
    foreach ($alerts as $alert) {
        if ($alert['tip'] === 'error') {
            // Roșu cu text alb bold
            $html .= $link_start . '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-red-600 text-white hover:bg-red-700 transition" title="' . htmlspecialchars($alert['mesaj']) . '">';
            $html .= '<i data-lucide="alert-circle" class="w-3 h-3 mr-1 shrink-0" aria-hidden="true"></i>';
            $html .= 'GDPR</span>' . $link_end;
        } elseif ($alert['tip'] === 'warning') {
            // Verifică dacă este CI sau certificat pentru a distinge între galben și portocaliu
            $is_ci = isset($alert['alert_key']) && $alert['alert_key'] === 'ci';
            if ($is_ci) {
                // Portocaliu + text negru bold pentru CI
                $html .= $link_start . '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-orange-500 text-black hover:bg-orange-600 transition" title="' . htmlspecialchars($alert['mesaj']) . '">';
                $html .= '<i data-lucide="alert-triangle" class="w-3 h-3 mr-1 shrink-0" aria-hidden="true"></i>';
                $html .= 'Expira C.I.</span>' . $link_end;
            } else {
                // Galben + text negru bold pentru certificat
                $html .= $link_start . '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-yellow-400 text-black hover:bg-yellow-500 transition" title="' . htmlspecialchars($alert['mesaj']) . '">';
                $html .= '<i data-lucide="alert-triangle" class="w-3 h-3 mr-1 shrink-0" aria-hidden="true"></i>';
                $html .= 'Expira C.H.</span>' . $link_end;
            }
        }
    }
    $html .= '</div>';
    
    return $html;
}
