<?php
/**
 * Controller: Newsletter — Lista campanii + creare/trimitere
 *
 * GET: Afiseaza lista newsletterelor + formular campanie noua
 * POST salveaza_newsletter: Salveaza draft sau trimite acum
 * POST programeaza_newsletter: Programeaza trimiterea
 * POST sterge_newsletter: Sterge un draft
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/newsletter_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

// Ensure tables and opt-in column
newsletter_ensure_tables($pdo);
newsletter_ensure_opt_in_column($pdo);

$eroare = '';
$succes = '';

// --- POST: Salveaza newsletter (draft sau trimite acum) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_newsletter'])) {
    csrf_require_valid();

    $actiune = $_POST['actiune'] ?? 'draft'; // 'draft' sau 'trimite'
    $id_edit = (int)($_POST['newsletter_id'] ?? 0);
    $subiect = trim($_POST['subiect'] ?? '');
    // Sanitizeaza HTML: strip tags + elimina atribute periculoase (onclick, onerror, javascript:)
    $continut_raw = strip_tags($_POST['continut'] ?? '', '<p><br><b><i><strong><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><table><tr><td><th><thead><tbody><div><span><hr><blockquote>');
    $continut = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $continut_raw);
    $continut = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $continut);
    $lista_destinatari = trim($_POST['lista_destinatari'] ?? '');
    $data_programata = trim($_POST['data_programata'] ?? '');

    if ($subiect === '' || $continut === '') {
        $eroare = 'Subiectul si continutul sunt obligatorii.';
    } elseif ($lista_destinatari === '') {
        $eroare = 'Selectati lista de destinatari.';
    } else {
        // Determine the categoria_contacte field for DB compatibility
        // For members lists, store the lista key; for contacte, store the original category
        $categoria_db = $lista_destinatari;

        $date = [
            'subiect' => $subiect,
            'continut' => $continut,
            'nume_expeditor' => trim($_POST['nume_expeditor'] ?? ''),
            'categoria_contacte' => $categoria_db,
            'atasament_nume' => null,
            'atasament_path' => null,
        ];

        // Handle attachment upload
        $fisier = $_FILES['atasament'] ?? null;
        if (!empty($fisier['tmp_name']) && is_uploaded_file($fisier['tmp_name'])) {
            $max_bytes = NEWSLETTER_ATASAMENT_MAX_MB * 1024 * 1024;
            if ($fisier['size'] > $max_bytes) {
                $eroare = 'Atasamentul depaseste ' . NEWSLETTER_ATASAMENT_MAX_MB . ' MB.';
            } else {
                $upload_dir = APP_ROOT . '/uploads/newsletter/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext = strtolower(pathinfo($fisier['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'csv', 'txt', 'zip'];
                if (!in_array($ext, $allowed_ext)) {
                    $eroare = 'Tip de fisier nepermis. Extensii acceptate: ' . implode(', ', $allowed_ext);
                } else {
                    $date['atasament_nume'] = basename($fisier['name']);
                    $date['atasament_path'] = $upload_dir . 'nl_' . time() . '_' . uniqid() . '.' . $ext;
                    if (!move_uploaded_file($fisier['tmp_name'], $date['atasament_path'])) {
                        $eroare = 'Eroare la incarcarea atasamentului.';
                    }
                }
            }
        }

        if ($eroare === '') {
            if ($actiune === 'trimite') {
                // Send immediately
                $destinatari = newsletter_get_destinatari_by_lista($pdo, $lista_destinatari);
                if (empty($destinatari)) {
                    $eroare = 'Nu exista destinatari cu email valid in lista selectata.';
                } else {
                    $from_email = newsletter_get_sender_email($pdo);
                    if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
                        $eroare = 'Configurati emailul expeditorului in Setari.';
                    } else {
                        // Save to DB first
                        if ($id_edit > 0) {
                            newsletter_actualizeaza_draft($pdo, $id_edit, $date);
                            $nl_id = $id_edit;
                        } else {
                            $nl_id = newsletter_salveaza_draft($pdo, $date);
                        }
                        // Send
                        $rez = newsletter_trimite_emails($pdo, $from_email, $date['nume_expeditor'], $subiect, $continut, $destinatari, $date['atasament_path'], $date['atasament_nume']);
                        newsletter_marca_trimis($pdo, $nl_id, $rez['trimise']);
                        $utilizator = $_SESSION['utilizator'] ?? $_SESSION['username'] ?? 'sistem';
                        log_activitate($pdo, "Newsletter trimis: \"{$subiect}\" catre {$rez['trimise']} destinatari", $utilizator);
                        $succes = "Newsletter trimis cu succes catre {$rez['trimise']} destinatari.";
                        if (!empty($rez['eroare'])) {
                            $succes .= ' Erori: ' . implode(', ', $rez['eroare']);
                        }
                    }
                }
            } elseif ($actiune === 'programeaza' && $data_programata !== '') {
                // Schedule - validate date is in the future
                $ts = strtotime($data_programata);
                if (!$ts || $ts < time()) {
                    $eroare = 'Data programata trebuie sa fie in viitor.';
                    $data_mysql = null;
                } else {
                    $data_mysql = date('Y-m-d H:i:s', $ts);
                }
                if (!$eroare) {
                    $utilizator = $_SESSION['utilizator'] ?? $_SESSION['username'] ?? 'sistem';
                    if ($id_edit > 0) {
                        newsletter_actualizeaza_draft($pdo, $id_edit, $date);
                        newsletter_programeaza_trimitere($pdo, $id_edit, $data_mysql);
                        $nl_id = $id_edit;
                    } else {
                        $nl_id = newsletter_salveaza_draft($pdo, $date);
                        newsletter_programeaza_trimitere($pdo, $nl_id, $data_mysql);
                    }
                    log_activitate($pdo, "Newsletter programat: \"{$subiect}\" la {$data_programata}", $utilizator);
                    $succes = "Newsletter programat pentru {$data_programata}.";
                }
            } else {
                // Save as draft
                if ($id_edit > 0) {
                    newsletter_actualizeaza_draft($pdo, $id_edit, $date);
                    $nl_id = $id_edit;
                } else {
                    $nl_id = newsletter_salveaza_draft($pdo, $date);
                }
                $utilizator = $_SESSION['utilizator'] ?? $_SESSION['username'] ?? 'sistem';
                log_activitate($pdo, "Newsletter draft salvat: \"{$subiect}\"", $utilizator);
                $succes = 'Draft salvat cu succes.';
            }
        }
    }

    if ($succes !== '') {
        header('Location: /newsletter?succes=' . urlencode($succes));
        exit;
    }
}

// --- POST: Sterge newsletter draft ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_newsletter'])) {
    csrf_require_valid();
    $id = (int)($_POST['newsletter_id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM newsletter WHERE id = ? AND status = 'draft'");
            $stmt->execute([$id]);
            $utilizator = $_SESSION['utilizator'] ?? $_SESSION['username'] ?? 'sistem';
            log_activitate($pdo, "Newsletter draft sters: #{$id}", $utilizator);
        } catch (PDOException $e) {}
    }
    header('Location: /newsletter');
    exit;
}

// --- GET: Succes message from redirect ---
if (isset($_GET['succes'])) {
    $succes = 'Operatiunea a fost efectuata cu succes.';
}

// --- GET: Load data for editing a specific newsletter ---
$edit_nl = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    if ($edit_id > 0) {
        $edit_nl = newsletter_get($pdo, $edit_id);
        if ($edit_nl && $edit_nl['status'] !== 'draft') {
            $edit_nl = null; // Only drafts can be edited
        }
    }
}

// --- Load data for view ---
$newslettere = newsletter_lista_toate($pdo);
$liste_predefinite = newsletter_get_liste_predefinite($pdo);

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/newsletter/index.php';
