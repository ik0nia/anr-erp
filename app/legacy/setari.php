<?php
/**
 * Pagină Setări - CRM ANR Bihor
 * Gestionează setările platformei
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/auth_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/excel_import.php';
require_once APP_ROOT . '/includes/file_helper.php';
require_once APP_ROOT . '/includes/registru_interactiuni_v2_helper.php';
require_once APP_ROOT . '/includes/mailer_functions.php';
require_once APP_ROOT . '/includes/cotizatii_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

$eroare = '';
$succes = '';
$lista_utilizatori = [];

// Procesare adăugare utilizator (doar administrator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_utilizator']) && !empty($_SESSION['user_id']) && is_admin()) {
    csrf_require_valid();
    auth_ensure_tables($pdo);
    $nume_complet = trim($_POST['nume_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $functie = trim($_POST['functie'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $parola = (string)($_POST['parola'] ?? '');
    $rol = in_array($_POST['rol'] ?? '', ['administrator', 'operator']) ? $_POST['rol'] : 'operator';
    if ($nume_complet === '' || $email === '' || $username === '' || $parola === '') {
        $eroare = 'Numele complet, emailul, numele de utilizator și parola sunt obligatorii.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $eroare = 'Adresa de email nu este validă.';
    } elseif (strlen($parola) < 6) {
        $eroare = 'Parola trebuie să aibă minim 6 caractere.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM utilizatori WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $eroare = 'Există deja un utilizator cu acest nume de utilizator.';
            } else {
                $hash = password_hash($parola, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO utilizatori (nume_complet, email, functie, username, parola_hash, rol, activ) VALUES (?, ?, ?, ?, ?, ?, 1)')
                    ->execute([$nume_complet, $email, $functie ?: null, $username, $hash, $rol]);
                auth_trimite_email_confirmare_utilizator($nume_complet, $email, $username, $functie, $rol);
                $succes = 'Utilizatorul a fost creat. Un email de confirmare a fost trimis pe adresa indicată.';
                header('Location: setari.php?succes_util=1');
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la salvare utilizator: ' . $e->getMessage();
        }
    }
}

// Încărcare listă utilizatori (pentru secțiunea Management - doar admin)
if (!empty($_SESSION['user_id']) && is_admin()) {
    auth_ensure_tables($pdo);
    try {
        $stmt = $pdo->query('SELECT id, nume_complet, email, functie, username, rol, activ, created_at FROM utilizatori ORDER BY nume_complet');
        $lista_utilizatori = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';

if (isset($_GET['succes_util'])) {
    $succes = 'Utilizatorul a fost creat. Un email de confirmare a fost trimis.';
}
if (isset($_GET['succes_nume'])) {
    $succes = 'Numele platformei a fost actualizat.';
}
if (isset($_GET['succes_subiect_v2'])) {
    $succes = 'Subiectul a fost salvat.';
}
if (isset($_GET['succes_cotizatii'])) {
    $succes = 'Modificările cotizațiilor au fost salvate.';
}
if (isset($_GET['succes_incasari'])) {
    $succes = 'Setările modulului Încasări au fost salvate.';
}
if (isset($_GET['succes_antet'])) {
    $succes = 'Antetul asociației a fost încărcat.';
}
if (isset($_GET['succes_logo'])) {
    $succes = 'Logo-ul a fost actualizat cu succes.';
}
if (isset($_GET['succes_registratura'])) {
    $succes = 'Setările Registraturii au fost salvate.';
}
if (isset($_GET['succes_newsletter'])) {
    $succes = 'Setările Newsletter au fost salvate.';
}
if (isset($_GET['succes_documente'])) {
    $succes = 'Setările pentru generare documente au fost salvate.';
}
$import_result = null;
$excel_data = null;
$mapare_coloane = null;

// Variabile vechi pentru importul Excel – păstrate pentru compatibilitate, dar
// modul recomandat de import membri este acum pagina separată import-membri-csv.php.

// Procesare formular actualizare logo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_logo'])) {
    csrf_require_valid();
    $logo_url = trim($_POST['logo_url'] ?? '');
    
    if (empty($logo_url)) {
        $eroare = 'URL-ul logo-ului este obligatoriu.';
    } elseif (!filter_var($logo_url, FILTER_VALIDATE_URL)) {
        $eroare = 'URL-ul introdus nu este valid.';
    } else {
        try {
            // Verifică dacă există tabelul de setări
            $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cheie VARCHAR(100) NOT NULL UNIQUE,
                valoare TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Încarcă valoarea veche pentru logging
            $stmt_old = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
            $stmt_old->execute(['logo_url']);
            $logo_vechi = $stmt_old->fetchColumn();
            
            // Salvează sau actualizează logo-ul
            $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE valoare = ?, updated_at = CURRENT_TIMESTAMP');
            $stmt->execute(['logo_url', $logo_url, $logo_url]);
            
            log_activitate($pdo, log_format_modificare('Logo URL', $logo_vechi ?: '(gol)', $logo_url, 'setari'));
            $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : '';
            header('Location: setari.php' . $tab_redirect . '&succes_logo=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la salvare: ' . $e->getMessage();
        }
    }
}

// Procesare încărcare antet asociație (DOCX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['incarca_antet_asociatie'])) {
    csrf_require_valid();
    $antet_eroare = '';
    if (!isset($_FILES['antet_docx']) || $_FILES['antet_docx']['error'] !== UPLOAD_ERR_OK) {
        $antet_eroare = $_FILES['antet_docx']['error'] ?? 0;
        $eroare = $antet_eroare === UPLOAD_ERR_NO_FILE ? 'Selectați un fișier DOCX.' : 'Eroare la încărcarea fișierului.';
    } else {
        $file = $_FILES['antet_docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            $eroare = 'Doar fișiere DOCX sunt acceptate.';
        }
        if (empty($eroare) && $file['size'] > 10 * 1024 * 1024) {
            $eroare = 'Fișierul depășește 10 MB.';
        }
        if (empty($eroare)) {
            $upload_dir = APP_ROOT . '/uploads/antet/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $filename = 'antet_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', substr(uniqid(), -8)) . '.docx';
            $full_path = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS setari (id INT AUTO_INCREMENT PRIMARY KEY, cheie VARCHAR(100) NOT NULL UNIQUE, valoare TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    $rel_path = 'uploads/antet/' . $filename;
                    $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = ?, updated_at = CURRENT_TIMESTAMP');
                    $stmt->execute(['antet_asociatie_docx', $rel_path, $rel_path]);
                    log_activitate($pdo, 'Setări: antet asociație DOCX încărcat – ' . $filename);
                    header('Location: setari.php?succes_antet=1');
                    exit;
                } catch (PDOException $e) {
                    $eroare = 'Eroare la salvare setare: ' . $e->getMessage();
                    @unlink($full_path);
                }
            } else {
                $eroare = 'Eroare la salvarea fișierului pe server.';
            }
        }
    }
}

// Procesare import Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel'])) {
    csrf_require_valid();
    if (isset($_FILES['fisier_excel']) && $_FILES['fisier_excel']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fisier_excel'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            $eroare = 'Tipul fișierului nu este suportat. Folosiți CSV sau Excel (.xlsx, .xls).';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $eroare = 'Fișierul depășește 10 MB.';
        } else {
            $upload_dir = APP_ROOT . '/uploads/import/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'import_' . time() . '_' . uniqid() . '.' . $extension;
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Citește fișierul
                $excel_data = citeste_fisier_excel($file_path);
                
                if (empty($excel_data['headers'])) {
                    $eroare = 'Nu s-au putut citi header-urile din fișier.';
                    unlink($file_path);
                } else {
                    // Generează maparea
                    $mapare_coloane = mapeaza_coloane($excel_data['headers']);
                    
                    // Dacă s-a făcut maparea manuală
                    if (isset($_POST['mapare_coloane']) && is_array($_POST['mapare_coloane'])) {
                        $mapare_coloane = [];
                        foreach ($_POST['mapare_coloane'] as $index => $db_field) {
                            if (!empty($db_field) && $db_field !== 'ignora') {
                                $mapare_coloane[$index] = $db_field;
                            }
                        }
                    }
                    
                    // Dacă s-a apăsat butonul de import efectiv
                    if (isset($_POST['executa_import']) && !empty($mapare_coloane)) {
                        $skip_duplicates = isset($_POST['skip_duplicates']) ? 1 : 0;
                        $import_result = importa_membri($pdo, $excel_data['rows'], $mapare_coloane, $skip_duplicates);
                        
                        if ($import_result['importati'] > 0) {
                            $succes = "Import reușit: {$import_result['importati']} membri importați";
                            if ($import_result['skipati'] > 0) {
                                $succes .= ", {$import_result['skipati']} membri săriți (duplicate)";
                            }
                        }
                        
                        if (!empty($import_result['eroare'])) {
                            $eroare = "Erori la import: " . implode("; ", array_slice($import_result['eroare'], 0, 10));
                            if (count($import_result['eroare']) > 10) {
                                $eroare .= " ... și " . (count($import_result['eroare']) - 10) . " altele";
                            }
                        }
                        
                        unlink($file_path);
                        $excel_data = null;
                        $mapare_coloane = null;
                    }
                }
            } else {
                $eroare = 'Eroare la încărcarea fișierului.';
            }
        }
    } else {
        $eroare = 'Nu s-a selectat niciun fișier sau a apărut o eroare la încărcare.';
    }
}

// Tab Setări: general (implicit), dashboard, email, cotizatii sau incasari
$tab_setari = 'general';
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'dashboard') $tab_setari = 'dashboard';
    elseif ($_GET['tab'] === 'email') $tab_setari = 'email';
    elseif ($_GET['tab'] === 'cotizatii') $tab_setari = 'cotizatii';
    elseif ($_GET['tab'] === 'incasari') $tab_setari = 'incasari';
}

// Procesare Cotizații (tab Cotizații)
if ($tab_setari === 'cotizatii' || isset($_POST['salveaza_cotizatie_anuala']) || isset($_POST['sterge_cotizatie_anuala']) || isset($_POST['adauga_scutire_cotizatie']) || isset($_POST['actualizeaza_scutire_cotizatie']) || isset($_POST['sterge_scutire_cotizatie'])) {
    cotizatii_ensure_tables($pdo);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_cotizatie_anuala'])) {
    csrf_require_valid();
    $tab_setari = 'cotizatii'; // rămâne pe tab Cotizații după submit
    $id = (int)($_POST['id_cotizatie_anuala'] ?? 0);
    $anul = (int)($_POST['anul'] ?? date('Y'));
    $grad = trim($_POST['grad_handicap'] ?? '');
    $asistent = trim($_POST['asistent_personal'] ?? '');
    $valoare = (float)str_replace(',', '.', $_POST['valoare_cotizatie'] ?? 0);
    if ($anul >= 1900 && $anul <= 2100 && $grad !== '' && $valoare >= 0) {
        cotizatii_salveaza_anuala($pdo, $id, $anul, $grad, $asistent, $valoare);
        log_activitate($pdo, 'Setări: cotizație anuală salvată – ' . $anul . ' / ' . $grad);
        header('Location: setari.php?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_cotizatie_anuala'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_cotizatie_anuala'] ?? 0);
    if ($id > 0 && cotizatii_sterge_anuala($pdo, $id)) {
        log_activitate($pdo, 'Setări: cotizație anuală ștearsă ID ' . $id);
        header('Location: setari.php?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_scutire_cotizatie'])) {
    csrf_require_valid();
    $membru_id = (int)($_POST['membru_id_scutire'] ?? 0);
    $data_pana = trim($_POST['data_scutire_pana_la'] ?? '') ?: null;
    $permanenta = !empty($_POST['scutire_permanenta']);
    $motiv = trim($_POST['motiv_scutire'] ?? '');
    if ($membru_id > 0) {
        cotizatii_adauga_scutire($pdo, $membru_id, $data_pana, $permanenta, $motiv);
        log_activitate($pdo, 'Setări: scutire cotizație adăugată pentru membru ID ' . $membru_id);
        header('Location: setari.php?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_scutire_cotizatie'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_scutire'] ?? 0);
    $data_pana = trim($_POST['data_scutire_pana_la'] ?? '') ?: null;
    $permanenta = !empty($_POST['scutire_permanenta']);
    $motiv = trim($_POST['motiv_scutire'] ?? '');
    if ($id > 0) {
        cotizatii_actualizeaza_scutire($pdo, $id, $data_pana, $permanenta, $motiv);
        log_activitate($pdo, 'Setări: scutire cotizație actualizată ID ' . $id);
        header('Location: setari.php?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_scutire_cotizatie'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_scutire'] ?? 0);
    if ($id > 0 && cotizatii_sterge_scutire($pdo, $id)) {
        log_activitate($pdo, 'Setări: scutire cotizație ștearsă ID ' . $id);
        header('Location: setari.php?tab=cotizatii&succes_cotizatii=1');
        exit;
    }
}

// Procesare tab Încasări: serii chitanțe, design chitanțe, FGO.ro API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_serii_incasari'])) {
    csrf_require_valid();
    $tab_setari = 'incasari';
    incasari_ensure_tables($pdo);
    incasari_salveaza_serie($pdo, 'donatii', trim($_POST['serie_donatii'] ?? 'D'), (int)($_POST['nr_start_donatii'] ?? 1), (int)($_POST['nr_curent_donatii'] ?? 1));
    incasari_salveaza_serie($pdo, 'incasari', trim($_POST['serie_incasari'] ?? 'INC'), (int)($_POST['nr_start_incasari'] ?? 1), (int)($_POST['nr_curent_incasari'] ?? 1));
    log_activitate($pdo, 'Setări: serii chitanțe Încasări actualizate.');
    header('Location: setari.php?tab=incasari&succes_incasari=1');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_design_chitante'])) {
    csrf_require_valid();
    $tab_setari = 'incasari';
    incasari_set_setare($pdo, 'logo_chitanta', trim($_POST['logo_chitanta'] ?? ''));
    incasari_set_setare($pdo, 'date_asociatie', trim($_POST['date_asociatie'] ?? ''));
    log_activitate($pdo, 'Setări: design chitanțe Încasări actualizat.');
    header('Location: setari.php?tab=incasari&succes_incasari=1');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_fgo_api'])) {
    csrf_require_valid();
    $tab_setari = 'incasari';
    incasari_set_setare($pdo, 'fgo_api_key', trim($_POST['fgo_api_key'] ?? ''));
    incasari_set_setare($pdo, 'fgo_merchant_name', trim($_POST['fgo_merchant_name'] ?? ''));
    incasari_set_setare($pdo, 'fgo_merchant_tax_id', trim($_POST['fgo_merchant_tax_id'] ?? ''));
    incasari_set_setare($pdo, 'fgo_api_url', trim($_POST['fgo_api_url'] ?? ''));
    incasari_set_setare($pdo, 'fgo_mediu', in_array($_POST['fgo_mediu'] ?? '', ['test', 'productie']) ? $_POST['fgo_mediu'] : 'test');
    log_activitate($pdo, 'Setări: parametri FGO.ro API actualizați.');
    header('Location: setari.php?tab=incasari&succes_incasari=1');
    exit;
}

// Procesare subiecte registru interacțiuni v2 (tab Dashboard)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_subiect_interactiune_v2'])) {
    csrf_require_valid();
    ensure_registru_v2_tables($pdo);
    $nume = trim($_POST['subiect_nou_v2'] ?? '');
    if (!empty($nume)) {
        try {
            $r = $pdo->query('SELECT COALESCE(MAX(ordine), 0) + 1 as next_ord FROM registru_interactiuni_v2_subiecte')->fetch();
            $ord = (int)($r['next_ord'] ?? 0);
            $stmt = $pdo->prepare('INSERT INTO registru_interactiuni_v2_subiecte (nume, ordine, activ) VALUES (?, ?, 1)');
            $stmt->execute([$nume, $ord]);
            log_activitate($pdo, "registru_interactiuni_v2: Subiect adaugat: {$nume} / Modul: Setari");
            header('Location: setari.php?tab=dashboard&succes_subiect_v2=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la adăugare subiect v2: ' . $e->getMessage();
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subiect_activ_v2'])) {
    csrf_require_valid();
    ensure_registru_v2_tables($pdo);
    $id = (int)($_POST['subiect_id_v2'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT nume, activ FROM registru_interactiuni_v2_subiecte WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $nou_activ = $row['activ'] ? 0 : 1;
                $pdo->prepare('UPDATE registru_interactiuni_v2_subiecte SET activ = ? WHERE id = ?')->execute([$nou_activ, $id]);
                $act = $nou_activ ? 'activat' : 'dezactivat';
                log_activitate($pdo, "registru_interactiuni_v2: Subiect {$row['nume']} {$act} / Modul: Setari");
                $succes = $nou_activ ? 'Subiectul v2 a fost activat.' : 'Subiectul v2 a fost dezactivat.';
            }
            header('Location: setari.php?tab=dashboard&succes_subiect_v2=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la actualizare v2: ' . $e->getMessage();
        }
    }
}

// Procesare setări Email (tab Email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_setari_email'])) {
    csrf_require_valid();
    mailer_ensure_table($pdo);
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = (int)($_POST['smtp_port'] ?? 587);
    if ($smtp_port < 1 || $smtp_port > 65535) $smtp_port = 587;
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = (string)($_POST['smtp_pass'] ?? '');
    $smtp_encryption = in_array($_POST['smtp_encryption'] ?? '', ['tls', 'ssl', '']) ? trim($_POST['smtp_encryption']) : 'tls';
    $from_name = trim($_POST['from_name'] ?? '');
    $from_email = trim($_POST['from_email'] ?? '');
    $email_signature = trim($_POST['email_signature'] ?? '');
    try {
        $stmt = $pdo->prepare("UPDATE settings_email SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, smtp_encryption=?, from_name=?, from_email=?, email_signature=? WHERE id=1");
        $pass_val = $smtp_pass !== '' ? $smtp_pass : (mailer_get_settings($pdo)['smtp_pass'] ?? '');
        $stmt->execute([$smtp_host ?: null, $smtp_port, $smtp_user ?: null, $pass_val, $smtp_encryption ?: null, $from_name ?: null, $from_email ?: null, $email_signature ?: null]);
        log_activitate($pdo, 'Setări Email (EMAILCRM) actualizate.');
        $succes = 'Setările email au fost salvate.';
        header('Location: setari.php?tab=email&succes_email=1');
        exit;
    } catch (PDOException $e) {
        $eroare = 'Eroare la salvare setări email: ' . $e->getMessage();
    }
}

// Trimite email de test (tab Email) – destinatar: câmp optional sau email utilizator logat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trimite_email_test'])) {
    csrf_require_valid();
    $email_destinatar = trim($_POST['email_test_destinatar'] ?? '');
    if ($email_destinatar === '' && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT email FROM utilizatori WHERE id = ? AND activ = 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $email_destinatar = trim((string)$stmt->fetchColumn());
    }
    if ($email_destinatar === '' || !filter_var($email_destinatar, FILTER_VALIDATE_EMAIL)) {
        $eroare = 'Introduceți o adresă de email validă sau asigurați-vă că utilizatorul logat are email setat.';
    } else {
        $ok = sendAutomatedEmail($pdo, $email_destinatar, 'Test Email CRM – Setări email', 'Acesta este un email de test trimis din modulul Setări → Email. Setările SMTP/expeditor sunt funcționale.');
        if ($ok) {
            $succes = 'Email de test trimis cu succes către ' . htmlspecialchars($email_destinatar) . '.';
            header('Location: setari.php?tab=email&succes_email=1');
            exit;
        } else {
            $eroare = 'Trimiterea emailului de test a eșuat. Verificați setările SMTP și adresa expeditor.';
        }
    }
}

// Procesare setări registratura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_registratura'])) {
    csrf_require_valid();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS setari (id INT AUTO_INCREMENT PRIMARY KEY, cheie VARCHAR(100) NOT NULL UNIQUE, valoare TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $nr_pornire = (int)($_POST['registratura_nr_pornire'] ?? 1);
        if ($nr_pornire < 1) $nr_pornire = 1;
        $stmt_old = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
        $stmt_old->execute(['registratura_nr_pornire']);
        $nr_vechi = $stmt_old->fetchColumn();
        
        $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
        $stmt->execute(['registratura_nr_pornire', (string)$nr_pornire]);
        log_activitate($pdo, log_format_modificare('Registratura nr pornire', $nr_vechi ?: '(gol)', (string)$nr_pornire, 'setari'));
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : '';
        header('Location: setari.php' . $tab_redirect . '&succes_registratura=1');
        exit;
    } catch (PDOException $e) {
        $eroare = 'Eroare la salvare: ' . $e->getMessage();
    }
}

// Procesare modificare nume platformă
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_nume_platforma'])) {
    csrf_require_valid();
    if (!is_admin()) {
        $eroare = 'Doar administratorii pot modifica numele platformei.';
    } else {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS setari (id INT AUTO_INCREMENT PRIMARY KEY, cheie VARCHAR(100) NOT NULL UNIQUE, valoare TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $nume_platforma = trim($_POST['nume_platforma'] ?? '');
            if (empty($nume_platforma)) {
                $eroare = 'Numele platformei este obligatoriu.';
            } else {
                $stmt_old = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
                $stmt_old->execute(['platform_name']);
                $nume_vechi = $stmt_old->fetchColumn() ?: PLATFORM_NAME;
                
                $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
                $stmt->execute(['platform_name', $nume_platforma]);
                
                log_activitate($pdo, log_format_modificare('Nume platforma', $nume_vechi, $nume_platforma, 'setari'));
                header('Location: setari.php?succes_nume=1');
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la salvare: ' . $e->getMessage();
        }
    }
}

// Procesare setări Newsletter (email expeditor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_newsletter'])) {
    csrf_require_valid();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS setari (id INT AUTO_INCREMENT PRIMARY KEY, cheie VARCHAR(100) NOT NULL UNIQUE, valoare TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $email_newsletter = trim($_POST['newsletter_email'] ?? '');
        $stmt_old = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
        $stmt_old->execute(['newsletter_email']);
        $email_vechi = $stmt_old->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
        $stmt->execute(['newsletter_email', $email_newsletter]);
        if ($email_vechi !== $email_newsletter) {
            log_activitate($pdo, log_format_modificare('Email newsletter (expeditor)', $email_vechi ?: '(gol)', $email_newsletter ?: '(gol)', 'setari'));
        }
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : '';
        header('Location: setari.php' . $tab_redirect . '&succes_newsletter=1');
        exit;
    } catch (PDOException $e) {
        $eroare = 'Eroare la salvare: ' . $e->getMessage();
    }
}

// Procesare setări generare documente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_documente'])) {
    csrf_require_valid();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS setari (id INT AUTO_INCREMENT PRIMARY KEY, cheie VARCHAR(100) NOT NULL UNIQUE, valoare TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $email_asoc = trim($_POST['email_asociatie'] ?? '');
        $libreoffice = trim($_POST['cale_libreoffice'] ?? '');
        $stmt_old = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
        $stmt_old->execute(['email_asociatie']);
        $email_vechi = $stmt_old->fetchColumn();
        $stmt_old->execute(['cale_libreoffice']);
        $libreoffice_vechi = $stmt_old->fetchColumn();
        
        $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
        $stmt->execute(['email_asociatie', $email_asoc]);
        $stmt->execute(['cale_libreoffice', $libreoffice]);
        
        if ($email_vechi !== $email_asoc) {
            log_activitate($pdo, log_format_modificare('Email asociatie', $email_vechi ?: '(gol)', $email_asoc ?: '(gol)', 'setari'));
        }
        if ($libreoffice_vechi !== $libreoffice) {
            log_activitate($pdo, log_format_modificare('Cale LibreOffice', $libreoffice_vechi ?: '(gol)', $libreoffice ?: '(gol)', 'setari'));
        }
        $tab_redirect = isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : '';
        header('Location: setari.php' . $tab_redirect . '&succes_documente=1');
        exit;
    } catch (PDOException $e) {
        $eroare = 'Eroare la salvare: ' . $e->getMessage();
    }
}

// Încărcare logo URL actual din baza de date sau din config
$logo_url_actual = PLATFORM_LOGO_URL;
$nume_platforma_actual = PLATFORM_NAME;
$email_asociatie = '';
$cale_libreoffice = '';
$registratura_nr_pornire = 1;
$newsletter_email = '';
$antet_asociatie_docx = '';
try {
    $stmt = $pdo->query("SELECT valoare FROM setari WHERE cheie = 'logo_url'");
    $result = $stmt->fetch();
    if ($result && !empty($result['valoare'])) {
        $logo_url_actual = $result['valoare'];
    }
    $stmt = $pdo->query("SELECT valoare FROM setari WHERE cheie = 'platform_name'");
    $result = $stmt->fetch();
    if ($result && !empty($result['valoare'])) {
        $nume_platforma_actual = $result['valoare'];
    }
    $stmt = $pdo->query("SELECT cheie, valoare FROM setari WHERE cheie IN ('email_asociatie', 'cale_libreoffice', 'registratura_nr_pornire', 'newsletter_email', 'antet_asociatie_docx')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['cheie'] === 'email_asociatie') $email_asociatie = (string)($row['valoare'] ?? '');
        if ($row['cheie'] === 'cale_libreoffice') $cale_libreoffice = (string)($row['valoare'] ?? '');
        if ($row['cheie'] === 'registratura_nr_pornire') $registratura_nr_pornire = (int)($row['valoare'] ?? 1) ?: 1;
        if ($row['cheie'] === 'newsletter_email') $newsletter_email = (string)($row['valoare'] ?? '');
        if ($row['cheie'] === 'antet_asociatie_docx') $antet_asociatie_docx = (string)($row['valoare'] ?? '');
    }
} catch (PDOException $e) {}
$subiecte_dashboard_v2 = [];
if ($tab_setari === 'dashboard') {
    ensure_registru_v2_tables($pdo);
    $subiecte_dashboard_v2 = get_subiecte_interactiuni_v2_toate($pdo);
}
$settings_email = [];
if ($tab_setari === 'email') {
    $settings_email = mailer_get_settings($pdo);
}
$lista_cotizatii_anuale = [];
$lista_scutiri_cotizatii = [];
$edit_cotizatie_anuala = null;
$edit_scutire_cotizatie = null;
$graduri_handicap = cotizatii_graduri_handicap($pdo);
$asistent_personal_opts = cotizatii_asistent_personal_lista($pdo);
if ($tab_setari === 'cotizatii') {
    $lista_cotizatii_anuale = cotizatii_lista_anuale($pdo);
    $lista_scutiri_cotizatii = cotizatii_lista_scutiri($pdo);
    if (isset($_GET['edit_cotizatie']) && (int)$_GET['edit_cotizatie'] > 0) {
        $edit_cotizatie_anuala = cotizatii_get_anuala($pdo, (int)$_GET['edit_cotizatie']);
    }
    if (isset($_GET['edit_scutire']) && (int)$_GET['edit_scutire'] > 0) {
        $edit_scutire_cotizatie = cotizatii_get_scutire($pdo, (int)$_GET['edit_scutire']);
    }
}
$incasari_serie_donatii = null;
$incasari_serie_incasari = null;
$incasari_setari_design = [];
$lista_donatii_incasate = [];
if ($tab_setari === 'incasari') {
    incasari_ensure_tables($pdo);
    $incasari_serie_donatii = incasari_get_serie($pdo, 'donatii');
    $incasari_serie_incasari = incasari_get_serie($pdo, 'incasari');
    $lista_donatii_incasate = incasari_lista_donatii($pdo, 500);
    $incasari_setari_design = [
        'logo_chitanta' => incasari_get_setare($pdo, 'logo_chitanta') ?: (defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''),
        'date_asociatie' => incasari_get_setare($pdo, 'date_asociatie') ?: '',
        'fgo_api_key' => incasari_get_setare($pdo, 'fgo_api_key') ?: '',
        'fgo_merchant_name' => incasari_get_setare($pdo, 'fgo_merchant_name') ?: '',
        'fgo_merchant_tax_id' => incasari_get_setare($pdo, 'fgo_merchant_tax_id') ?: '',
        'fgo_api_url' => incasari_get_setare($pdo, 'fgo_api_url') ?: 'https://api.fgo.ro',
        'fgo_mediu' => incasari_get_setare($pdo, 'fgo_mediu') ?: 'test',
    ];
}
if (isset($_GET['succes_email'])) {
    $succes = 'Setările email au fost salvate sau emailul de test a fost trimis.';
}
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex justify-between items-center flex-wrap gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Setări</h1>
            <p class="text-sm text-slate-500 dark:text-gray-400 mt-0.5" aria-label="Versiune CRM">Versiune CRM: <?php echo htmlspecialchars(function_exists('get_platform_version') ? get_platform_version() : (defined('PLATFORM_VERSION') ? PLATFORM_VERSION : '1')); ?></p>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <nav class="mb-6 flex gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Tab-uri setări">
            <a href="setari.php" role="tab" aria-selected="<?php echo $tab_setari === 'general' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'general' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                General
            </a>
            <a href="setari.php?tab=dashboard" role="tab" aria-selected="<?php echo $tab_setari === 'dashboard' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'dashboard' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Dashboard
            </a>
            <a href="setari.php?tab=email" role="tab" aria-selected="<?php echo $tab_setari === 'email' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'email' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Email
            </a>
            <a href="setari.php?tab=cotizatii" role="tab" aria-selected="<?php echo $tab_setari === 'cotizatii' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'cotizatii' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Cotizații
            </a>
            <a href="setari.php?tab=incasari" role="tab" aria-selected="<?php echo $tab_setari === 'incasari' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'incasari' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Încasări
            </a>
        </nav>

        <?php if ($tab_setari === 'incasari'): ?>
        <!-- Tab Încasări: administrare modul (serii chitanțe, design, FGO.ro API) -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="incasari-serii-heading">
            <h2 id="incasari-serii-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Serii chitanțe</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Definiți seria și intervalul de numerotare pentru chitanțe donații și pentru chitanțe încasări (donații, taxe participare, alte încasări).</p>
            <form method="post" action="/setari?tab=incasari">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_serii_incasari" value="1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg">
                        <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3">Chitanțe donații</h3>
                        <div class="space-y-2">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Serie</label>
                            <input type="text" name="serie_donatii" value="<?php echo htmlspecialchars($incasari_serie_donatii['serie'] ?? 'D'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" maxlength="20">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Nr. start / Nr. curent</label>
                            <div class="flex gap-2">
                                <input type="number" name="nr_start_donatii" value="<?php echo (int)($incasari_serie_donatii['nr_start'] ?? 1); ?>" min="1" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="number" name="nr_curent_donatii" value="<?php echo (int)($incasari_serie_donatii['nr_curent'] ?? 1); ?>" min="0" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg">
                        <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3">Chitanțe încasări (donații, taxe participare, alte)</h3>
                        <div class="space-y-2">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Serie</label>
                            <input type="text" name="serie_incasari" value="<?php echo htmlspecialchars($incasari_serie_incasari['serie'] ?? 'INC'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" maxlength="20">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Nr. start / Nr. curent</label>
                            <div class="flex gap-2">
                                <input type="number" name="nr_start_incasari" value="<?php echo (int)($incasari_serie_incasari['nr_start'] ?? 1); ?>" min="1" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="number" name="nr_curent_incasari" value="<?php echo (int)($incasari_serie_incasari['nr_curent'] ?? 1); ?>" min="0" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează serii</button>
            </form>
        </section>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="incasari-design-heading">
            <h2 id="incasari-design-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Design chitanțe</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Logo-ul se afișează în dreapta sus pe chitanță (format A5). Datele asociației în stânga sus.</p>
            <form method="post" action="/setari?tab=incasari">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_design_chitante" value="1">
                <div class="space-y-3 mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">URL logo (chitanță)</label>
                    <input type="url" name="logo_chitanta" value="<?php echo htmlspecialchars($incasari_setari_design['logo_chitanta'] ?? ''); ?>" placeholder="https://..." class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Date asociație (stânga sus pe chitanță)</label>
                    <textarea name="date_asociatie" rows="6" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Denumire, CUI, sediu, cont bancar..."><?php echo htmlspecialchars($incasari_setari_design['date_asociatie'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează design</button>
            </form>
        </section>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="incasari-fgo-heading">
            <h2 id="incasari-fgo-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Integrare FGO.ro (API)</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Conectare la platforma FGO.ro pentru transmiterea documentelor de încasare. Consultați <a href="https://www.fgo.ro" target="_blank" rel="noopener noreferrer" class="text-amber-600 dark:text-amber-400 hover:underline">FGO.ro</a> și documentația API (PDF) pentru parametrii exacti.</p>
            <form method="post" action="/setari?tab=incasari">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_fgo_api" value="1">
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Cheie API</label>
                        <input type="text" name="fgo_api_key" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_api_key'] ?? ''); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Cheie API FGO">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Nume comerciant</label>
                        <input type="text" name="fgo_merchant_name" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_merchant_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">CUI / ID fiscal comerciant</label>
                        <input type="text" name="fgo_merchant_tax_id" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_merchant_tax_id'] ?? ''); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">URL API</label>
                        <input type="url" name="fgo_api_url" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_api_url'] ?? 'https://api.fgo.ro'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Mediu</label>
                        <select name="fgo_mediu" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                            <option value="test" <?php echo ($incasari_setari_design['fgo_mediu'] ?? '') === 'test' ? 'selected' : ''; ?>>Test</option>
                            <option value="productie" <?php echo ($incasari_setari_design['fgo_mediu'] ?? '') === 'productie' ? 'selected' : ''; ?>>Producție</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează parametri FGO</button>
            </form>
        </section>
        <!-- Donațiile încasate -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="incasari-donatii-heading">
            <h2 id="incasari-donatii-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Donațiile încasate</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Lista donațiilor încasate (de la membri și donatori externi).</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-labelledby="incasari-donatii-heading">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Donator / Membru</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Sumă (RON)</th>
                            <th scope="col" class="px-4 py-2 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Seria / Nr.</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($lista_donatii_incasate)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu există donații încasate.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_donatii_incasate as $don): 
                            $nume_don = trim(($don['nume'] ?? '') . ' ' . ($don['prenume'] ?? ''));
                            $data_fmt = !empty($don['data_incasare']) ? date('d.m.Y', strtotime($don['data_incasare'])) : '—';
                            $seria = $don['seria_chitanta'] ?? '—';
                            $nr = $don['nr_chitanta'] ?? '—';
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($data_fmt); ?></td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($nume_don ?: '—'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 text-right"><?php echo number_format((float)$don['suma'], 2, ',', '.'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 text-center"><?php echo htmlspecialchars($seria); ?> / <?php echo htmlspecialchars($nr); ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="util/incasari-chitanta-print.php?id=<?php echo (int)$don['id']; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Tipărește chitanța <?php echo htmlspecialchars($seria . ' ' . $nr); ?>">Tipărește</a>
                                <a href="util/incasari-chitanta-pdf.php?id=<?php echo (int)$don['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-slate-500" aria-label="Descarcă PDF chitanța <?php echo htmlspecialchars($seria . ' ' . $nr); ?>">PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php elseif ($tab_setari === 'cotizatii'): ?>
        <!-- Tab Cotizații: valori anuale și membri scutiți -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="cotizatii-anuale-heading">
            <h2 id="cotizatii-anuale-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Valori cotizații anuale</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Setați valoarea cotizației pe an, grad de handicap și asistent personal. Fiecare combinație an + grad + asistent poate exista o singură dată.</p>
            <form method="post" action="<?php echo htmlspecialchars('setari.php?tab=cotizatii'); ?>" class="mb-4 flex flex-wrap gap-4 items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_cotizatie_anuala" value="1">
                <input type="hidden" name="id_cotizatie_anuala" value="<?php echo $edit_cotizatie_anuala ? (int)$edit_cotizatie_anuala['id'] : 0; ?>">
                <div>
                    <label for="cot-anul" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Anul</label>
                    <input type="number" id="cot-anul" name="anul" min="1900" max="2100" value="<?php echo $edit_cotizatie_anuala ? (int)$edit_cotizatie_anuala['anul'] : date('Y'); ?>" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Anul">
                </div>
                <div>
                    <label for="cot-grad" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Grad handicap</label>
                    <select id="cot-grad" name="grad_handicap" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Grad handicap">
                        <?php foreach ($graduri_handicap as $val => $lbl): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($edit_cotizatie_anuala && ($edit_cotizatie_anuala['grad_handicap'] ?? '') === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="cot-asistent" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Asistent personal</label>
                    <select id="cot-asistent" name="asistent_personal" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Asistent personal">
                        <?php foreach ($asistent_personal_opts as $val => $lbl): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($edit_cotizatie_anuala && ($edit_cotizatie_anuala['asistent_personal'] ?? '') === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="cot-valoare" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Valoare cotizație (lei)</label>
                    <input type="text" id="cot-valoare" name="valoare_cotizatie" value="<?php echo $edit_cotizatie_anuala ? htmlspecialchars($edit_cotizatie_anuala['valoare_cotizatie']) : ''; ?>" placeholder="0.00" class="w-32 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Valoare cotizație">
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg"><?php echo $edit_cotizatie_anuala ? 'Actualizează' : 'Adaugă'; ?></button>
                <?php if ($edit_cotizatie_anuala): ?>
                <a href="setari.php?tab=cotizatii" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300">Anulare</a>
                <?php endif; ?>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700 text-sm [&_th]:min-h-[1.5em] [&_th]:leading-[1.5] [&_td]:min-h-[1.5em] [&_td]:leading-[1.5] [&_th]:align-middle [&_td]:align-middle [&_th]:py-0 [&_td]:py-0" role="table" aria-label="Lista cotizații anuale">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Anul</th>
                            <th class="px-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Grad handicap</th>
                            <th class="px-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Asistent personal</th>
                            <th class="px-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Valoare (lei)</th>
                            <th class="px-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($lista_cotizatii_anuale as $c): ?>
                        <tr>
                            <td class="px-3 text-slate-900 dark:text-white"><?php echo (int)$c['anul']; ?></td>
                            <td class="px-3 text-slate-900 dark:text-white"><?php echo htmlspecialchars($graduri_handicap[$c['grad_handicap']] ?? $c['grad_handicap']); ?></td>
                            <td class="px-3 text-slate-900 dark:text-white"><?php echo htmlspecialchars($asistent_personal_opts[$c['asistent_personal'] ?? ''] ?? ($c['asistent_personal'] ?? '')); ?></td>
                            <td class="px-3 text-right text-slate-900 dark:text-white"><?php echo number_format((float)$c['valoare_cotizatie'], 2, ',', '.'); ?></td>
                            <td class="px-3 text-right">
                                <a href="setari.php?tab=cotizatii&edit_cotizatie=<?php echo (int)$c['id']; ?>" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">Modificare</a>
                                <form method="post" action="/setari?tab=cotizatii" class="inline ml-2" onsubmit="return confirm('Ștergeți această cotizație?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="sterge_cotizatie_anuala" value="1">
                                    <input type="hidden" name="id_cotizatie_anuala" value="<?php echo (int)$c['id']; ?>">
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Șterge</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista_cotizatii_anuale)): ?>
                        <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500 dark:text-gray-400">Nicio cotizație. Folosiți formularul de mai sus.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="cotizatii-scutiri-heading">
            <h2 id="cotizatii-scutiri-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Membri scutiți de la plata cotizației</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Adăugați membri care sunt scutiți (permanent sau până la o dată) și motivul scutirii.</p>
            <div class="mb-4 p-4 bg-slate-50 dark:bg-gray-700/50 rounded-lg border border-slate-200 dark:border-gray-600">
                <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3"><?php echo $edit_scutire_cotizatie ? 'Modifică scutire' : 'Adaugă membru scutit'; ?></h3>
                <form method="post" action="/setari?tab=cotizatii" id="form-scutire-cotizatie">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit_scutire_cotizatie): ?>
                    <input type="hidden" name="actualizeaza_scutire_cotizatie" value="1">
                    <input type="hidden" name="id_scutire" value="<?php echo (int)$edit_scutire_cotizatie['id']; ?>">
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">Membru: <strong><?php echo htmlspecialchars(trim(($edit_scutire_cotizatie['nume'] ?? '') . ' ' . ($edit_scutire_cotizatie['prenume'] ?? ''))); ?></strong></p>
                    <?php else: ?>
                    <input type="hidden" name="adauga_scutire_cotizatie" value="1">
                    <div class="mb-3">
                        <label for="cautare-membru-scutire" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Caută membru</label>
                        <div class="flex gap-2">
                            <input type="text" id="cautare-membru-scutire" placeholder="Nume sau prenume (min. 2 caractere)..." class="flex-1 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Caută membru" autocomplete="off">
                            <button type="button" id="btn-selecteaza-membru-scutire" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium">Selectează membru</button>
                        </div>
                        <div id="rezultate-cautare-scutire" class="mt-1 border border-slate-200 dark:border-gray-600 rounded-lg max-h-48 overflow-y-auto hidden bg-white dark:bg-gray-700" role="region" aria-live="polite"></div>
                        <input type="hidden" name="membru_id_scutire" id="membru_id_scutire" value="">
                        <p id="membru-selectat-afis" class="text-sm text-slate-600 dark:text-gray-400 mt-1"></p>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300">
                            <input type="checkbox" name="scutire_permanenta" value="1" id="scutire-permanenta" <?php echo ($edit_scutire_cotizatie && !empty($edit_scutire_cotizatie['scutire_permanenta'])) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600">
                            Scutire permanentă
                        </label>
                    </div>
                    <div class="mb-3" id="wrap-data-pana-la">
                        <label for="data-scutire-pana-la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Perioada de scutire: până la data</label>
                        <input type="date" id="data-scutire-pana-la" name="data_scutire_pana_la" value="<?php echo $edit_scutire_cotizatie && !empty($edit_scutire_cotizatie['data_scutire_pana_la']) ? htmlspecialchars($edit_scutire_cotizatie['data_scutire_pana_la']) : ''; ?>" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Data până la care este valabilă scutirea">
                    </div>
                    <div class="mb-3">
                        <label for="motiv-scutire" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Motivul scutirii</label>
                        <textarea id="motiv-scutire" name="motiv_scutire" rows="2" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Ex: handicap grav, situație socială"><?php echo $edit_scutire_cotizatie ? htmlspecialchars($edit_scutire_cotizatie['motiv'] ?? '') : ''; ?></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg"><?php echo $edit_scutire_cotizatie ? 'Actualizează scutire' : 'Adaugă scutire'; ?></button>
                    <?php if ($edit_scutire_cotizatie): ?>
                    <a href="setari.php?tab=cotizatii" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 ml-2">Anulare</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista scutiri cotizație">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Membru</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Scutire până la / Permanentă</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Motiv</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($lista_scutiri_cotizatii as $s): ?>
                        <tr id="scutire-<?php echo (int)$s['id']; ?>">
                            <td class="px-4 py-3 text-slate-900 dark:text-white"><?php echo htmlspecialchars(trim(($s['nume'] ?? '') . ' ' . ($s['prenume'] ?? ''))); ?></td>
                            <td class="px-4 py-3 text-slate-900 dark:text-white"><?php echo !empty($s['scutire_permanenta']) ? 'Permanentă' : ($s['data_scutire_pana_la'] ? date('d.m.Y', strtotime($s['data_scutire_pana_la'])) : '—'); ?></td>
                            <td class="px-4 py-3 text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars(mb_substr($s['motiv'] ?? '', 0, 80)); ?><?php echo mb_strlen($s['motiv'] ?? '') > 80 ? '…' : ''; ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="setari.php?tab=cotizatii&edit_scutire=<?php echo (int)$s['id']; ?>#form-scutire-cotizatie" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">Editează</a>
                                <form method="post" action="/setari?tab=cotizatii" class="inline ml-2" onsubmit="return confirm('Ștergeți această scutire?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="sterge_scutire_cotizatie" value="1">
                                    <input type="hidden" name="id_scutire" value="<?php echo (int)$s['id']; ?>">
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Șterge</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista_scutiri_cotizatii)): ?>
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500 dark:text-gray-400">Nicio scutire. Adăugați un membru scutit cu formularul de mai sus.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <script>
        (function(){
            var permanenta = document.getElementById('scutire-permanenta');
            var wrapData = document.getElementById('wrap-data-pana-la');
            if (permanenta && wrapData) {
                function toggle() { wrapData.style.display = permanenta.checked ? 'none' : 'block'; }
                permanenta.addEventListener('change', toggle);
                toggle();
            }
            var cautare = document.getElementById('cautare-membru-scutire');
            var rezultate = document.getElementById('rezultate-cautare-scutire');
            var membruId = document.getElementById('membru_id_scutire');
            var membruAfis = document.getElementById('membru-selectat-afis');
            var btnSelect = document.getElementById('btn-selecteaza-membru-scutire');
            if (cautare && rezultate && membruId && membruAfis) {
                var selectat = { id: 0, nume: '', prenume: '' };
                function afiseazaRezultate(membri) {
                    rezultate.classList.remove('hidden');
                    rezultate.innerHTML = membri.length ? membri.map(function(m){
                        return '<button type="button" class="block w-full text-left px-3 py-2 hover:bg-amber-100 dark:hover:bg-gray-600 border-b border-slate-200 dark:border-gray-600 last:border-0" data-id="'+m.id+'" data-nume="'+(m.nume||'')+'" data-prenume="'+(m.prenume||'')+'">'+(m.nume||'')+' '+(m.prenume||'')+'</button>';
                    }).join('') : '<p class="px-3 py-2 text-slate-500 dark:text-gray-400">Niciun rezultat.</p>';
                    rezultate.querySelectorAll('button').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            selectat = { id: parseInt(btn.dataset.id), nume: btn.dataset.nume||'', prenume: btn.dataset.prenume||'' };
                            membruId.value = selectat.id;
                            membruAfis.textContent = 'Selectat: ' + selectat.nume + ' ' + selectat.prenume;
                            rezultate.classList.add('hidden');
                        });
                    });
                }
                var timer;
                cautare.addEventListener('input', function(){
                    clearTimeout(timer);
                    var q = cautare.value.trim();
                    if (q.length < 2) { rezultate.classList.add('hidden'); return; }
                    timer = setTimeout(function(){
                        fetch('/api/cauta-membri?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(d){ afiseazaRezultate(d.membri||[]); });
                    }, 200);
                });
                function executaCautareScutire() { var q = cautare.value.trim(); if (q.length < 2) return; fetch('/api/cauta-membri?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(d){ afiseazaRezultate(d.membri||[]); }); }
                if (btnSelect) btnSelect.addEventListener('click', executaCautareScutire);
                cautare.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); executaCautareScutire(); } });
            }
        })();
        </script>

        <?php elseif ($tab_setari === 'email'): ?>
        <!-- Tab Email: setări SMTP și expeditor -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border-2 border-slate-200 dark:border-gray-600 p-6 max-w-2xl" aria-labelledby="setari-email-heading">
            <h2 id="setari-email-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i data-lucide="mail" class="mr-2 w-5 h-5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                Setări Email (EMAILCRM)
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Configurare trimitere emailuri automate din platformă. Folosit de notificări și alte module.</p>

            <form method="post" action="/setari?tab=email" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_setari_email" value="1">

                <div class="rounded-lg border-2 border-slate-200 dark:border-gray-600 p-4 bg-slate-50 dark:bg-gray-700/30">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">Server SMTP</h3>
                    <div class="space-y-3">
                        <label for="smtp_host" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Host SMTP</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings_email['smtp_host'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: smtp.gmail.com" aria-label="Adresa server SMTP">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="smtp_port" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Port</label>
                                <input type="number" id="smtp_port" name="smtp_port" value="<?php echo (int)($settings_email['smtp_port'] ?? 587); ?>"
                                       min="1" max="65535" class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                       aria-label="Port SMTP (ex: 587)">
                            </div>
                            <div>
                                <label for="smtp_encryption" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Criptare</label>
                                <select id="smtp_encryption" name="smtp_encryption" class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                        aria-label="Criptare SMTP (TLS sau SSL)">
                                    <option value="tls" <?php echo ($settings_email['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($settings_email['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo ($settings_email['smtp_encryption'] ?? '') === '' ? 'selected' : ''; ?>>Niciuna</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-2 border-slate-200 dark:border-gray-600 p-4 bg-slate-50 dark:bg-gray-700/30">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">Autentificare</h3>
                    <div class="space-y-3">
                        <label for="smtp_user" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Utilizator SMTP</label>
                        <input type="text" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($settings_email['smtp_user'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               autocomplete="username" aria-label="Utilizator pentru autentificare SMTP">
                        <label for="smtp_pass" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Parolă SMTP</label>
                        <input type="password" id="smtp_pass" name="smtp_pass" value=""
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               autocomplete="current-password" placeholder="Lăsați gol pentru a păstra parola existentă" aria-label="Parolă SMTP (lăsați gol pentru a nu schimba)">
                    </div>
                </div>

                <div class="rounded-lg border-2 border-slate-200 dark:border-gray-600 p-4 bg-slate-50 dark:bg-gray-700/30">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">Personalizare Expeditor</h3>
                    <div class="space-y-3">
                        <label for="from_name" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Nume expeditor</label>
                        <input type="text" id="from_name" name="from_name" value="<?php echo htmlspecialchars($settings_email['from_name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: ERP ANR BIHOR" aria-label="Numele afișat ca expeditor">
                        <label for="from_email" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Email expeditor</label>
                        <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($settings_email['from_email'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: noreply@anrbihor.ro" aria-label="Adresa de email a expeditorului">
                        <label for="quill-signature-container" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Semnătură globală (editor vizual)</label>
                        <input type="hidden" name="email_signature" id="email_signature_hidden" value="">
                        <div id="quill-signature-container" class="quill-editor-wrap border-2 border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 min-h-[120px]" role="textbox" aria-label="Semnătura adăugată la sfârșitul fiecărui email automat. Editor rich text: Bold, Italic, Link, Image.">
                            <?php echo $settings_email['email_signature'] ?? ''; ?>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mt-1"><strong>Logo în emailuri:</strong> Pentru ca logoul să apară în emailuri, folosiți butonul Imagine și introduceți fie URL-ul public al logoului (ex: din <code class="bg-slate-200 dark:bg-gray-600 px-1 rounded">assets/img/logo.png</code> pe server), fie un link Base64 (generat cu un tool online). Varianta Base64 încorporă imaginea în HTML și funcționează și când clientul de email blochează imaginile externe.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Salvează setările email">Salvează setări</button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-gray-600">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-2">Verificare setări</h3>
                <form method="post" action="/setari?tab=email" class="flex flex-wrap items-end gap-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="trimite_email_test" value="1">
                    <div class="flex-1 min-w-[200px]">
                        <label for="email_test_destinatar" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Trimite test la adresa</label>
                        <input type="email" id="email_test_destinatar" name="email_test_destinatar" value=""
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: email@domeniu.ro (opțional)" aria-label="Adresă email la care să se trimită emailul de test; lăsați gol pentru emailul utilizatorului logat">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-600 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Trimite un email de test">Trimite Email de Test</button>
                </form>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-2">Dacă nu completați adresa, se folosește emailul utilizatorului logat.</p>
            </div>
        </section>

        <!-- Quill.js pentru Semnătură (doar pe tab Email) -->
        <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
        <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
        <script>
        (function() {
            var container = document.getElementById('quill-signature-container');
            if (!container) return;
            var existingHtml = container.innerHTML.trim();
            container.innerHTML = '';
            var quill = new Quill(container, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic'],
                        ['link', 'image']
                    ]
                }
            });
            if (existingHtml) quill.root.innerHTML = existingHtml;
            container.setAttribute('aria-label', 'Semnătură email: editor rich text cu Bold, Italic, Link, Imagine.');
            var form = container.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    var hidden = document.getElementById('email_signature_hidden');
                    if (hidden) hidden.value = quill.root.innerHTML;
                });
            }
        })();
        </script>

        <?php elseif ($tab_setari === 'dashboard'): ?>
        <!-- Tab Dashboard: administrare subiecte Registru Interacțiuni -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-2xl" aria-labelledby="dashboard-interactiuni-heading">
            <h2 id="dashboard-interactiuni-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i data-lucide="phone-call" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Registru Interacțiuni – Subiecte dropdown
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Creați și activați sau dezactivați subiectele afișate în meniul dropdown din formularul de interacțiuni (Dashboard și pagina Registru Interacțiuni). Subiectele dezactivate nu apar în dropdown.</p>
            <form method="post" action="/setari?tab=dashboard" class="mb-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="adauga_subiect_interactiune_v2" value="1">
                <div class="flex gap-2">
                    <input type="text" name="subiect_nou_v2" id="subiect_nou_v2"
                           placeholder="Ex: Cerere documente"
                           class="flex-1 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-label="Nume subiect nou">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Adaugă subiect">Adaugă</button>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista subiecte registru interacțiuni">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Subiect</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiune</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($subiecte_dashboard_v2)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-slate-500 dark:text-gray-400">Niciun subiect. Adăugați un subiect cu formularul de mai sus.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($subiecte_dashboard_v2 as $s): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($s['nume']); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $s['activ'] ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200' : 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-gray-300'; ?>">
                                    <?php echo $s['activ'] ? 'Activ' : 'Dezactivat'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" action="/setari?tab=dashboard" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="toggle_subiect_activ_v2" value="1">
                                    <input type="hidden" name="subiect_id_v2" value="<?php echo (int)$s['id']; ?>">
                                    <button type="submit" class="text-sm font-medium <?php echo $s['activ'] ? 'text-amber-600 dark:text-amber-400 hover:underline' : 'text-emerald-600 dark:text-emerald-400 hover:underline'; ?>"
                                            aria-label="<?php echo $s['activ'] ? 'Dezactivează' : 'Activează'; ?> <?php echo htmlspecialchars($s['nume']); ?>">
                                        <?php echo $s['activ'] ? 'Dezactivează' : 'Activează'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php else: ?>
        <!-- Tab General: setări platformă (3 coloane) -->
        <!-- Buton Management Generare Documente -->
        <div class="mb-6">
            <a href="generare-documente.php"
               class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
               aria-label="Deschide Management Generare Documente">
                <i data-lucide="file-text" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Management Generare Documente
            </a>
        </div>

        <!-- Secțiune Antet asociație (DOCX) -->
        <section class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="antet-asociatie-heading">
            <h2 id="antet-asociatie-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-2 flex items-center">
                <i data-lucide="file-type" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Antet asociație
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Încărcați un document DOCX care conține antetul asociației. Acest antet se va folosi la toate documentele generate în platformă, cu excepția modulului <strong>Generare documente</strong> precompletate cu datele membrului și a modulului <strong>Încasări</strong>.</p>
            <form method="post" action="/setari" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="incarca_antet_asociatie" value="1">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="antet_docx" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Fișier DOCX <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="file" id="antet_docx" name="antet_docx" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-amber-50 file:text-amber-800 dark:file:bg-amber-900/30 dark:file:text-amber-200"
                               aria-required="true" aria-describedby="antet-docx-desc">
                        <p id="antet-docx-desc" class="text-xs text-slate-500 dark:text-gray-400 mt-1">Doar format DOCX, maxim 10 MB.</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Încarcă antet asociație">Încarcă antet</button>
                </div>
                <?php if (!empty($antet_asociatie_docx) && file_exists(APP_ROOT . '/' . $antet_asociatie_docx)): ?>
                <div class="pt-2 border-t border-slate-200 dark:border-gray-600">
                    <p class="text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Antet curent</p>
                    <a href="<?php echo htmlspecialchars($antet_asociatie_docx); ?>" download class="inline-flex items-center gap-2 text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Descarcă antetul curent">
                        <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(basename($antet_asociatie_docx)); ?>
                    </a>
                </div>
                <?php elseif (!empty($antet_asociatie_docx)): ?>
                <p class="text-sm text-amber-700 dark:text-amber-300">Antet setat, dar fișierul lipsește pe server. Încărcați din nou un DOCX.</p>
                <?php endif; ?>
            </form>
        </section>

        <!-- Secțiune Management utilizatori (doar administrator) -->
        <?php if (!empty($_SESSION['user_id']) && is_admin()): ?>
        <section class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="utilizatori-heading">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 id="utilizatori-heading" class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                    <i data-lucide="users" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Management utilizatori
                </h2>
                <button type="button" id="btn-adauga-utilizator"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                        aria-label="Adaugă utilizator" aria-haspopup="dialog" aria-expanded="false" aria-controls="modal-adauga-utilizator">
                    <i data-lucide="user-plus" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                    Adaugă utilizator
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista utilizatori">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume complet</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Email</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Funcție</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume utilizator</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Rol</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($lista_utilizatori)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-600 dark:text-gray-400">Niciun utilizator. Adăugați un utilizator cu butonul de mai sus.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_utilizatori as $u): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($u['nume_complet']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($u['functie'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $u['rol'] === 'administrator' ? 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200'; ?>"><?php echo htmlspecialchars($u['rol']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm <?php echo $u['activ'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-gray-400'; ?>"><?php echo $u['activ'] ? 'Activ' : 'Dezactivat'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- Grid cu 3 coloane pentru setări -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Secțiune 1: Setare Logo Platformă -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="logo-heading">
                <h2 id="logo-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="image" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Logo Platformă
                </h2>
                
                <form method="post" action="/setari" class="space-y-4">
                    <input type="hidden" name="actualizeaza_logo" value="1">
                    
                    <div>
                        <label for="logo_url" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">
                            URL Logo <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                        </label>
                        <input type="url"
                               id="logo_url"
                               name="logo_url"
                               value="<?php echo htmlspecialchars($logo_url_actual); ?>"
                               required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="https://exemplu.com/logo.png"
                               aria-required="true"
                               aria-describedby="logo-desc">
                        <p id="logo-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Introduceți URL-ul complet al logo-ului platformei
                        </p>
                    </div>

                    <div class="flex items-center gap-3 mb-4">
                        <?php if (!empty($logo_url_actual)): ?>
                        <div class="flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($logo_url_actual); ?>" 
                                 alt="Logo actual" 
                                 class="h-16 w-auto object-contain border border-slate-200 dark:border-gray-600 rounded p-2 bg-white dark:bg-gray-700"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                            aria-label="Salvează setările logo-ului">
                        <i data-lucide="save" class="inline-block mr-2 w-4 h-4" aria-hidden="true"></i>
                        Salvează Logo
                    </button>
                </form>
            </section>

            <!-- Secțiune: Nume Platformă (doar administrator) -->
            <?php if (is_admin()): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="nume-platforma-heading">
                <h2 id="nume-platforma-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="type" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Nume Platformă
                </h2>
                
                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_nume_platforma" value="1">
                    
                    <div>
                        <label for="nume_platforma" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">
                            Nume Platformă <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="nume_platforma"
                               name="nume_platforma"
                               value="<?php echo htmlspecialchars($nume_platforma_actual); ?>"
                               required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="CRM ANR Bihor"
                               aria-required="true"
                               aria-describedby="nume-platforma-desc">
                        <p id="nume-platforma-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Numele afișat în header și în titlurile paginilor
                        </p>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                            aria-label="Salvează numele platformei">
                        <i data-lucide="save" class="inline-block mr-2 w-4 h-4" aria-hidden="true"></i>
                        Salvează Nume
                    </button>
                </form>
            </section>
            <?php endif; ?>

            <!-- Secțiune: Generare Documente -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="documente-heading">
                <h2 id="documente-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="file-text" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Generare Documente
                </h2>
                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_documente" value="1">
                    <div>
                        <label for="email_asociatie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Email asociație (Cc la trimitere documente)</label>
                        <input type="email" id="email_asociatie" name="email_asociatie"
                               value="<?php echo htmlspecialchars($email_asociatie); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="contact@anrbihor.ro">
                    </div>
                    <div>
                        <label for="cale_libreoffice" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Cale LibreOffice (pentru conversie PDF)</label>
                        <input type="text" id="cale_libreoffice" name="cale_libreoffice"
                               value="<?php echo htmlspecialchars($cale_libreoffice); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="C:\Program Files\LibreOffice\program\soffice.exe">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Lăsați gol dacă nu doriți conversie PDF. Se va oferi doar descărcare DOCX.</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează setările logo-ului">Salvează</button>
                </form>
            </section>

            <!-- Secțiune: Newsletter -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="newsletter-heading">
                <h2 id="newsletter-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="mail" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Newsletter
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Emailul de pe care se trimit newsletterele către contacte. Numele expeditorului se setează în formularul de trimitere.</p>
                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_newsletter" value="1">
                    <div>
                        <label for="newsletter_email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Email expeditor newsletter</label>
                        <input type="email" id="newsletter_email" name="newsletter_email"
                               value="<?php echo htmlspecialchars($newsletter_email); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="newsletter@anrbihor.ro">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Dacă este gol, se folosește emailul asociației (Generare Documente).</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează setările newsletter">Salvează</button>
                </form>
            </section>

            <!-- Secțiune: Registratura -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="registratura-heading">
                <h2 id="registratura-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="book-open" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Registratura
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Numărul de pornire pentru numerotarea automată a înregistrărilor în registratură.</p>
                <?php
                // Calculează următorul număr care va fi alocat
                require_once APP_ROOT . '/includes/registratura_helper.php';
                $urmatorul_nr = registratura_urmatorul_nr($pdo);
                ?>
                <form method="post" action="/setari" class="space-y-4">
                    <input type="hidden" name="actualizeaza_registratura" value="1">
                    <div>
                        <label for="registratura_nr_pornire" class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Număr pornire înregistrări</label>
                        <input type="number" id="registratura_nr_pornire" name="registratura_nr_pornire" min="1" step="1"
                               value="<?php echo (int)$registratura_nr_pornire; ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               aria-describedby="registratura-nr-desc">
                        <p id="registratura-nr-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Primul număr alocat la o înregistrare nouă (incremental de aici).
                            <?php if ($urmatorul_nr != $registratura_nr_pornire): ?>
                            <br><strong class="text-amber-600 dark:text-amber-400">Următorul număr care va fi alocat: <?php echo $urmatorul_nr; ?></strong>
                            <?php else: ?>
                            <br><span class="text-slate-500 dark:text-gray-400">Următorul număr care va fi alocat: <strong><?php echo $urmatorul_nr; ?></strong></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează setările registraturii">Salvează</button>
                </form>
            </section>

            <!-- Secțiune: Import Membri (redirijare către modulul nou CSV) -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="import-heading">
                <h2 id="import-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="upload" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Import membri
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                    Pentru importul avansat al membrilor din fișiere CSV (cu mapare de coloane),
                    folosește modulul dedicat de import. Aici poți și exporta lista actuală de membri.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="util/export_membri.php"
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        <i data-lucide="download" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                        Export membri în CSV
                    </a>
                    <a href="import-membri-csv.php"
                       class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        <i data-lucide="upload" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                        Deschide modul Import membri CSV
                    </a>
                </div>
            </section>
            
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Adaugă utilizator -->
<dialog id="modal-adauga-utilizator" role="dialog" aria-modal="true" aria-labelledby="modal-utilizator-title" aria-describedby="modal-utilizator-desc"
        class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="modal-utilizator-title" class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adaugă utilizator</h2>
        <p id="modal-utilizator-desc" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completați datele. După salvare, utilizatorul primește un email de confirmare (fără parolă) și link către platformă.</p>
        <form method="post" action="/setari" id="form-adauga-utilizator">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_utilizator" value="1">
            <div class="space-y-4">
                <div>
                    <label for="util-nume_complet" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Numele complet <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="util-nume_complet" name="nume_complet" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="util-email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="email" id="util-email" name="email" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="util-functie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Funcția din cadrul organizației</label>
                    <input type="text" id="util-functie" name="functie"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           placeholder="Ex: Secretar, Contabil">
                </div>
                <div>
                    <label for="util-username" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume utilizator <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="util-username" name="username" required autocomplete="username"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="util-parola" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parolă <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="util-parola" name="parola" required minlength="6" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true" aria-describedby="util-parola-desc">
                    <p id="util-parola-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Minim 6 caractere. Nu se afișează în emailul de confirmare.</p>
                </div>
                <div>
                    <label for="util-rol" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Rol <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="util-rol" name="rol" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                        <option value="operator">Operator</option>
                        <option value="administrator">Administrator</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-inchide-modal-utilizator" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulare (închide fereastra)">Anulare</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează și trimite email confirmare">Salvează și trimite email</button>
            </div>
        </form>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    var modalUtil = document.getElementById('modal-adauga-utilizator');
    var btnDeschide = document.getElementById('btn-adauga-utilizator');
    var btnInchide = document.getElementById('btn-inchide-modal-utilizator');
    if (btnDeschide && modalUtil) {
        btnDeschide.addEventListener('click', function() {
            modalUtil.showModal();
            btnDeschide.setAttribute('aria-expanded', 'true');
            document.getElementById('util-nume_complet').focus();
        });
    }
    if (btnInchide && modalUtil) {
        btnInchide.addEventListener('click', function() {
            modalUtil.close();
            if (btnDeschide) btnDeschide.setAttribute('aria-expanded', 'false');
        });
    }
    if (modalUtil) {
        modalUtil.addEventListener('close', function() {
            if (btnDeschide) btnDeschide.setAttribute('aria-expanded', 'false');
        });
        modalUtil.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') this.close();
        });
    }
});
</script>
</body>
</html>
