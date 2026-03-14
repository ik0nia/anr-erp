<?php
/**
 * Procesare formular membri - CRM ANR Bihor
 * Include validare și salvare pentru toate câmpurile
 */
require_once 'includes/cnp_validator.php';
require_once 'includes/file_helper.php';
require_once 'includes/log_helper.php';

function proceseaza_formular_membru($pdo, $post_data, $files) {
    $eroare = '';
    $membru_id = null;
    
    // Validare câmpuri obligatorii
    $nume = trim($post_data['nume'] ?? '');
    $prenume = trim($post_data['prenume'] ?? '');
    $cnp = preg_replace('/\D/', '', $post_data['cnp'] ?? '');
    $is_update = !empty($post_data['membru_id']);

    if (empty($nume) || empty($prenume)) {
        return ['success' => false, 'error' => 'Numele și prenumele sunt obligatorii.', 'membru_id' => null];
    }

    // La actualizare: dacă CNP-ul din formular e gol, păstrăm CNP-ul existent din baza de date
    if ($is_update) {
        try {
            $stmt_cnp = $pdo->prepare('SELECT cnp FROM membri WHERE id = ?');
            $stmt_cnp->execute([(int)$post_data['membru_id']]);
            $cnp_existent = $stmt_cnp->fetchColumn();
            if (empty($cnp) && $cnp_existent !== null && $cnp_existent !== '') {
                $cnp = preg_replace('/\D/', '', $cnp_existent);
                error_log('DEBUG membri_processing: CNP gol în formular, folosim CNP existent pentru actualizare');
            }
        } catch (PDOException $e) {
            error_log('DEBUG membri_processing: Eroare la citire CNP existent - ' . $e->getMessage());
        }
    }

    // CNP obligatoriu doar la adăugare membru nou; la actualizare poate rămâne gol
    if (!$is_update && empty($cnp)) {
        return ['success' => false, 'error' => 'CNP-ul este obligatoriu.', 'membru_id' => null];
    }

    // Validare CNP - la actualizare cu CNP gol nu validăm; la actualizare cu CNP schimbat sau la adăugare validăm
    if ($is_update && empty($cnp)) {
        // Actualizare fără CNP (păstrăm sau lăsăm gol) – fără validare
    } elseif ($is_update) {
        try {
            $stmt_cnp = $pdo->prepare('SELECT cnp FROM membri WHERE id = ?');
            $stmt_cnp->execute([(int)$post_data['membru_id']]);
            $cnp_vechi = $stmt_cnp->fetchColumn();
            if ($cnp_vechi === $cnp) {
                error_log('DEBUG membri_processing: CNP nu s-a schimbat, skip validare');
            } else {
                $validare_cnp = valideaza_cnp($cnp);
                if (!$validare_cnp['valid']) {
                    error_log('DEBUG membri_processing: CNP schimbat dar invalid - ' . $validare_cnp['error']);
                    return ['success' => false, 'error' => $validare_cnp['error'], 'membru_id' => null];
                }
            }
        } catch (PDOException $e) {
            error_log('DEBUG membri_processing: Eroare la verificare CNP vechi - ' . $e->getMessage());
            $validare_cnp = valideaza_cnp($cnp);
            if (!$validare_cnp['valid']) {
                return ['success' => false, 'error' => $validare_cnp['error'], 'membru_id' => null];
            }
        }
    } else {
        $validare_cnp = valideaza_cnp($cnp);
        if (!$validare_cnp['valid']) {
            error_log('DEBUG membri_processing: CNP invalid la adăugare - ' . $validare_cnp['error']);
            return ['success' => false, 'error' => $validare_cnp['error'], 'membru_id' => null];
        }
    }
    
    // Extrage informații din CNP dacă nu sunt completate
    $info_cnp = extrage_info_cnp($cnp);
    
    // Pregătește datele pentru inserare
    $dosarnr = trim($post_data['dosarnr'] ?? '') ?: null;
    $dosardata = !empty($post_data['dosardata']) ? date('Y-m-d', strtotime($post_data['dosardata'])) : null;
    $status_dosar = in_array($post_data['status_dosar'] ?? '', ['Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat']) ? $post_data['status_dosar'] : 'Activ';
    $telefonnev = trim($post_data['telefonnev'] ?? '') ?: null;
    $telefonapartinator = trim($post_data['telefonapartinator'] ?? '') ?: null;
    $nume_apartinator = trim($post_data['nume_apartinator'] ?? '') ?: null;
    $prenume_apartinator = trim($post_data['prenume_apartinator'] ?? '') ?: null;
    $email = trim($post_data['email'] ?? '') ?: null;
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Adresa de email nu este validă.', 'membru_id' => null];
    }
    
    $datanastere = !empty($post_data['datanastere']) ? date('Y-m-d', strtotime($post_data['datanastere'])) : ($info_cnp ? $info_cnp['data_nastere'] : null);
    $locnastere = trim($post_data['locnastere'] ?? '') ?: null;
    $judnastere = trim($post_data['judnastere'] ?? '') ?: null;
    $ciseria = trim($post_data['ciseria'] ?? '') ?: null;
    // Număr C.I.: doar cifre, maxim 7 caractere; valori mai scurte se păstrează ca atare
    $cinumar_raw = preg_replace('/\D/', '', (string)($post_data['cinumar'] ?? ''));
    $cinumar_raw = $cinumar_raw !== '' ? substr($cinumar_raw, 0, 7) : '';
    $cinumar = $cinumar_raw !== '' ? $cinumar_raw : null;
    $cielib = trim($post_data['cielib'] ?? '') ?: null;
    $cidataelib = !empty($post_data['cidataelib']) ? date('Y-m-d', strtotime($post_data['cidataelib'])) : null;
    $cidataexp = !empty($post_data['cidataexp']) ? date('Y-m-d', strtotime($post_data['cidataexp'])) : null;
    $gdpr = isset($post_data['gdpr']) ? 1 : 0;
    $codpost = trim($post_data['codpost'] ?? '') ?: null;
    $tipmediuur = in_array($post_data['tipmediuur'] ?? '', ['Urban', 'Rural']) ? $post_data['tipmediuur'] : null;
    $domloc = trim($post_data['domloc'] ?? '') ?: null;
    $judet_domiciliu = trim($post_data['judet_domiciliu'] ?? '') ?: null;
    $domstr = trim($post_data['domstr'] ?? '') ?: null;
    $domnr = trim($post_data['domnr'] ?? '') ?: null;
    $dombl = trim($post_data['dombl'] ?? '') ?: null;
    $domsc = trim($post_data['domsc'] ?? '') ?: null;
    $domet = trim($post_data['domet'] ?? '') ?: null;
    $domap = trim($post_data['domap'] ?? '') ?: null;
    $sex = in_array($post_data['sex'] ?? '', ['Masculin', 'Feminin']) ? $post_data['sex'] : ($info_cnp ? $info_cnp['sex'] : null);
    $hgrad = in_array($post_data['hgrad'] ?? '', ['Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap']) ? $post_data['hgrad'] : null;
    $hmotiv = trim($post_data['hmotiv'] ?? '') ?: null;
    $diagnostic = trim($post_data['diagnostic'] ?? '') ?: null;
    $hdur = in_array($post_data['hdur'] ?? '', ['Permanent', 'Revizuibil']) ? $post_data['hdur'] : null;
    $cenr = trim($post_data['cenr'] ?? '') ?: null;
    $cedata = !empty($post_data['cedata']) ? date('Y-m-d', strtotime($post_data['cedata'])) : null;
    $ceexp = !empty($post_data['ceexp']) ? date('Y-m-d', strtotime($post_data['ceexp'])) : null;
    $primaria = trim($post_data['primaria'] ?? '') ?: null;
    $notamembru = trim($post_data['notamembru'] ?? '') ?: null;
    
    try {
        // Verifică dacă CNP-ul există deja (pentru adăugare nouă)
        if (empty($post_data['membru_id'])) {
            $stmt = $pdo->prepare('SELECT id FROM membri WHERE cnp = ?');
            $stmt->execute([$cnp]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Un membru cu acest CNP există deja în baza de date.', 'membru_id' => null];
            }
        }
        
        // Construiește query-ul de inserare/actualizare
        if (!empty($post_data['membru_id'])) {
            // Actualizare - validare ID
            $membru_id = (int)$post_data['membru_id'];
            if ($membru_id <= 0) {
                error_log('DEBUG membri_processing: ID membru invalid - ' . $post_data['membru_id']);
                return ['success' => false, 'error' => 'ID membru invalid.', 'membru_id' => null];
            }
            
            // Încarcă datele vechi pentru logging
            $stmt_old = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
            $stmt_old->execute([$membru_id]);
            $membru_vechi = $stmt_old->fetch(PDO::FETCH_ASSOC);
            
            if (!$membru_vechi) {
                error_log('DEBUG membri_processing: Membru nu există în DB - ID=' . $membru_id);
                return ['success' => false, 'error' => 'Membru nu există în baza de date.', 'membru_id' => null];
            }
            
            error_log('DEBUG membri_processing: Actualizare membru ID=' . $membru_id . ', GDPR=' . $gdpr . ', CNP=' . substr($cnp, 0, 3) . '...');
            
            $sql = 'UPDATE membri SET 
                dosarnr = ?, dosardata = ?, status_dosar = ?, nume = ?, prenume = ?, telefonnev = ?, telefonapartinator = ?,
                nume_apartinator = ?, prenume_apartinator = ?, email = ?, datanastere = ?, locnastere = ?, judnastere = ?, ciseria = ?, cinumar = ?,
                cielib = ?, cidataelib = ?, cidataexp = ?, gdpr = ?, codpost = ?, tipmediuur = ?, domloc = ?, judet_domiciliu = ?, domstr = ?,
                domnr = ?, dombl = ?, domsc = ?, domet = ?, domap = ?, sex = ?, hgrad = ?, hmotiv = ?, diagnostic = ?,
                hdur = ?, cnp = ?, cenr = ?, cedata = ?, ceexp = ?, primaria = ?, notamembru = ?
                WHERE id = ?';
            
            $params = [
                $dosarnr, $dosardata, $status_dosar, $nume, $prenume, $telefonnev, $telefonapartinator,
                $nume_apartinator, $prenume_apartinator, $email, $datanastere, $locnastere, $judnastere, $ciseria, $cinumar,
                $cielib, $cidataelib, $cidataexp, $gdpr, $codpost, $tipmediuur, $domloc, $judet_domiciliu, $domstr,
                $domnr, $dombl, $domsc, $domet, $domap, $sex, $hgrad, $hmotiv, $diagnostic,
                $hdur, $cnp, $cenr, $cedata, $ceexp, $primaria, $notamembru, $membru_id
            ];
            
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows_affected = $stmt->rowCount();
                error_log('DEBUG membri_processing: UPDATE executat, rânduri afectate=' . $rows_affected);
            } catch (PDOException $e) {
                error_log('DEBUG membri_processing: Eroare SQL - ' . $e->getMessage());
                throw $e;
            }
            
            // Construiește mesajul de log cu modificările
            $modificari = [];
            $nume_complet = trim($nume . ' ' . $prenume);
            if ($membru_vechi) {
                if (($membru_vechi['telefonnev'] ?? '') !== ($telefonnev ?? '')) {
                    $modificari[] = log_format_modificare('Numar de telefon', $membru_vechi['telefonnev'] ?? '', $telefonnev ?? '');
                }
                if (($membru_vechi['telefonapartinator'] ?? '') !== ($telefonapartinator ?? '')) {
                    $modificari[] = log_format_modificare('Telefon apartinator', $membru_vechi['telefonapartinator'] ?? '', $telefonapartinator ?? '');
                }
                if (($membru_vechi['email'] ?? '') !== ($email ?? '')) {
                    $modificari[] = log_format_modificare('Email', $membru_vechi['email'] ?? '', $email ?? '');
                }
                if (($membru_vechi['status_dosar'] ?? '') !== ($status_dosar ?? '')) {
                    $modificari[] = log_format_modificare('Status dosar', $membru_vechi['status_dosar'] ?? '', $status_dosar ?? '');
                }
                if (($membru_vechi['domloc'] ?? '') !== ($domloc ?? '')) {
                    $modificari[] = log_format_modificare('Locatie', $membru_vechi['domloc'] ?? '', $domloc ?? '');
                }
            }
            
            // Procesează fișierele
            if (isset($files['doc_ci']) && $files['doc_ci']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ci'], 'ci', $membru_id);
                if ($result['success'] && $result['filename']) {
                    // Șterge vechiul fișier dacă există
                    $stmt_old = $pdo->prepare('SELECT doc_ci FROM membri WHERE id = ?');
                    $stmt_old->execute([$membru_id]);
                    $old_file = $stmt_old->fetchColumn();
                    if ($old_file) {
                        sterge_fisier($old_file, 'ci');
                        log_activitate($pdo, "membri: Fisier CI sters: {$old_file} > {$result['filename']} / {$nume_complet}", null, $membru_id);
                    }
                    // Actualizează numele fișierului
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ci = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                    log_activitate($pdo, "membri: Fisier CI incarcat: {$result['filename']} / {$nume_complet}", null, $membru_id);
                } elseif (!$result['success']) {
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => $membru_id];
                }
            }
            
            if (isset($files['doc_ch']) && $files['doc_ch']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ch'], 'ch', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_old = $pdo->prepare('SELECT doc_ch FROM membri WHERE id = ?');
                    $stmt_old->execute([$membru_id]);
                    $old_file = $stmt_old->fetchColumn();
                    if ($old_file) {
                        sterge_fisier($old_file, 'ch');
                        log_activitate($pdo, "membri: Fisier CH sters: {$old_file} > {$result['filename']} / {$nume_complet}", null, $membru_id);
                    }
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ch = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                    log_activitate($pdo, "membri: Fisier CH incarcat: {$result['filename']} / {$nume_complet}", null, $membru_id);
                } elseif (!$result['success']) {
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => $membru_id];
                }
            }
            
            // Log modificări
            if (!empty($modificari)) {
                log_activitate($pdo, "membri: " . implode("; ", $modificari) . " / {$nume_complet}", null, $membru_id);
            } else {
                log_activitate($pdo, "membri: Actualizat membru ({$nume_complet})", null, $membru_id);
            }
        } else {
            // Inserare nouă
            $sql = 'INSERT INTO membri (
                dosarnr, dosardata, status_dosar, nume, prenume, telefonnev, telefonapartinator,
                nume_apartinator, prenume_apartinator, email, datanastere, locnastere, judnastere, ciseria, cinumar,
                cielib, cidataelib, cidataexp, gdpr, codpost, tipmediuur, domloc, judet_domiciliu, domstr,
                domnr, dombl, domsc, domet, domap, sex, hgrad, hmotiv, diagnostic,
                hdur, cnp, cenr, cedata, ceexp, primaria, notamembru
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            
            $params = [
                $dosarnr, $dosardata, $status_dosar, $nume, $prenume, $telefonnev, $telefonapartinator,
                $nume_apartinator, $prenume_apartinator, $email, $datanastere, $locnastere, $judnastere, $ciseria, $cinumar,
                $cielib, $cidataelib, $cidataexp, $gdpr, $codpost, $tipmediuur, $domloc, $judet_domiciliu, $domstr,
                $domnr, $dombl, $domsc, $domet, $domap, $sex, $hgrad, $hmotiv, $diagnostic,
                $hdur, $cnp, $cenr, $cedata, $ceexp, $primaria, $notamembru
            ];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $membru_id = $pdo->lastInsertId();
            
            // Procesează fișierele pentru membru nou
            if (isset($files['doc_ci']) && $files['doc_ci']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ci'], 'ci', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ci = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                } elseif (!$result['success']) {
                    // Șterge membru dacă fișierul nu s-a salvat
                    $pdo->prepare('DELETE FROM membri WHERE id = ?')->execute([$membru_id]);
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => null];
                }
            }
            
            if (isset($files['doc_ch']) && $files['doc_ch']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ch'], 'ch', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ch = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                } elseif (!$result['success']) {
                    $pdo->prepare('DELETE FROM membri WHERE id = ?')->execute([$membru_id]);
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => null];
                }
            }
            
            log_activitate($pdo, log_format_creare('membri', trim($nume . ' ' . $prenume)), null, $membru_id);
        }
        
        error_log('DEBUG membri_processing: Funcție finalizată cu succes, membru_id=' . $membru_id);
        return ['success' => true, 'error' => null, 'membru_id' => $membru_id];
        
    } catch (PDOException $e) {
        // Log detalii tehnice, dar afișează mesaj generic utilizatorului
        error_log('Eroare membri_processing: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        return ['success' => false, 'error' => 'A apărut o eroare la salvare: ' . $e->getMessage() . '. Vă rugăm să încercați din nou sau să contactați administratorul.', 'membru_id' => $membru_id];
    } catch (Exception $e) {
        error_log('Eroare membri_processing (Exception): ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        return ['success' => false, 'error' => 'A apărut o eroare neașteptată: ' . $e->getMessage(), 'membru_id' => $membru_id];
    }
}
