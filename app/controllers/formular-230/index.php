<?php
/**
 * Controller: Formular 230 — Lista + CRUD persoane
 *
 * GET: Afiseaza lista persoane cu filtrare si paginare
 * POST: Adauga/editeaza/arhiveaza persoana
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/Formular230Service.php';

// Asigura tabelele necesare
try {
    f230_ensure_tables($pdo);
} catch (PDOException $e) {
    die('Eroare initializare Formular 230: ' . htmlspecialchars($e->getMessage()));
}

$eroare = '';
$succes = '';

// Filtre/paginare
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$hide_bifat = !empty($_GET['hide_bifat']) ? 1 : 0;

// Cel mai recent an din formular230_ani
$ani = f230_get_ani($pdo);
$an_curent_form = $ani[0] ?? null;

// Utilizator curent
$utilizator = $_SESSION['utilizator']['username'] ?? ($_SESSION['utilizator'] ?? 'system');

// Procesare formulare POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adauga_persoana_230']) || isset($_POST['editeaza_persoana_230'])) {
        csrf_require_valid();
        $id = isset($_POST['persoana_id']) ? (int)$_POST['persoana_id'] : 0;
        $nume = trim($_POST['nume'] ?? '');
        $prenume = trim($_POST['prenume'] ?? '');
        $cnp = preg_replace('/\D/', '', $_POST['cnp'] ?? '');

        $validare = f230_validate($nume, $prenume, $cnp);
        if ($validare !== null) {
            $eroare = $validare;
        } else {
            $fields = f230_build_fields($_POST);

            if ($id > 0) {
                f230_update($pdo, $id, $fields, $utilizator);
                $succes = 'Persoana a fost actualizata.';
            } else {
                $id = f230_create($pdo, $fields, $utilizator);
                $succes = 'Persoana a fost adaugata.';
            }

            // Sincronizare cu modul Contacte
            f230_sync_contact($pdo, array_merge($fields, ['cnp' => $cnp]));

            header('Location: /formular-230');
            exit;
        }
    } elseif (isset($_POST['arhiveaza_persoana_230'])) {
        csrf_require_valid();
        $id = (int)($_POST['persoana_id'] ?? 0);
        if ($id > 0) {
            f230_archive($pdo, $id, $utilizator);
            $succes = 'Persoana a fost arhivata.';
        }
    } elseif (isset($_POST['toggle_hide_bifat'])) {
        $hideFlag = !empty($_POST['hide_bifat']) ? 1 : 0;
        header('Location: /formular-230?hide_bifat=' . $hideFlag);
        exit;
    }
}

// Incarcare date pentru view
$data = f230_list($pdo, $page, $per_page, (bool)$hide_bifat, $an_curent_form);
$persoane = $data['persoane'];
$page = $data['page'];
$total_pages = $data['total_pages'];

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
include APP_ROOT . '/app/views/formular-230/index.php';
