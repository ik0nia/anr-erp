<?php
/**
 * Editare contact
 */
require_once __DIR__ . '/config.php';
require_once 'includes/contacte_helper.php';
require_once 'includes/log_helper.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: contacte.php');
    exit;
}

ensure_contacte_table($pdo);
$eroare = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM contacte WHERE id = ?');
    $stmt->execute([$id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contact = null;
}

if (!$contact) {
    header('Location: contacte.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_contact'])) {
    csrf_require_valid();
    $nume = trim($_POST['nume'] ?? '');
    $prenume = trim($_POST['prenume'] ?? '');
    $companie = trim($_POST['companie'] ?? '');
    $tip_contact = $_POST['tip_contact'] ?? 'alte contacte';
    $telefon = trim($_POST['telefon'] ?? '');
    $telefon_personal = trim($_POST['telefon_personal'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $email_personal = trim($_POST['email_personal'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $data_nasterii = trim($_POST['data_nasterii'] ?? '');
    $notite = trim($_POST['notite'] ?? '');
    $referinta_contact = trim($_POST['referinta_contact'] ?? '');

    $tipuri = array_keys(get_contacte_tipuri());
    if (!in_array($tip_contact, $tipuri)) $tip_contact = 'alte contacte';

    if (empty($nume)) {
        $eroare = 'Numele este obligatoriu.';
    } else {
        try {
            // Încarcă datele vechi pentru logging
            $stmt_old = $pdo->prepare('SELECT * FROM contacte WHERE id = ?');
            $stmt_old->execute([$id]);
            $contact_vechi = $stmt_old->fetch(PDO::FETCH_ASSOC);
            
            $data_nasterii_val = !empty($data_nasterii) ? $data_nasterii : null;
            if ($data_nasterii_val) {
                $data_nasterii_val = parse_date_to_ymd($data_nasterii_val, ['Y-m-d', 'd.m.Y']);
            }
            $stmt = $pdo->prepare('UPDATE contacte SET nume=?, prenume=?, companie=?, tip_contact=?, telefon=?, telefon_personal=?, email=?, email_personal=?, website=?, data_nasterii=?, notite=?, referinta_contact=? WHERE id=?');
            $stmt->execute([$nume, $prenume ?: null, $companie ?: null, $tip_contact, $telefon ?: null, $telefon_personal ?: null, $email ?: null, $email_personal ?: null, $website ?: null, $data_nasterii_val, $notite ?: null, $referinta_contact ?: null, $id]);
            
            // Construiește mesajul de log cu modificările
            $nume_complet = trim($nume . ' ' . $prenume);
            $modificari = [];
            if ($contact_vechi) {
                if (($contact_vechi['telefon'] ?? '') !== ($telefon ?? '')) {
                    $modificari[] = log_format_modificare('Numar de telefon', $contact_vechi['telefon'] ?? '', $telefon ?? '');
                }
                if (($contact_vechi['telefon_personal'] ?? '') !== ($telefon_personal ?? '')) {
                    $modificari[] = log_format_modificare('Telefon personal', $contact_vechi['telefon_personal'] ?? '', $telefon_personal ?? '');
                }
                if (($contact_vechi['email'] ?? '') !== ($email ?? '')) {
                    $modificari[] = log_format_modificare('Email', $contact_vechi['email'] ?? '', $email ?? '');
                }
                if (($contact_vechi['email_personal'] ?? '') !== ($email_personal ?? '')) {
                    $modificari[] = log_format_modificare('Email personal', $contact_vechi['email_personal'] ?? '', $email_personal ?? '');
                }
            }
            
            if (!empty($modificari)) {
                log_activitate($pdo, "contacte: " . implode("; ", $modificari) . " / {$nume_complet}");
            } else {
                log_activitate($pdo, "contacte: Modificat contact {$nume_complet}");
            }
            header('Location: contacte.php?succes=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la actualizare.';
        }
    }
    $contact = array_merge($contact, $_POST);
}

$tipuri = get_contacte_tipuri();
include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Editează contact</h1>
        <a href="contacte.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">← Înapoi</a>
    </header>
    <div class="p-6 overflow-y-auto flex-1 max-w-2xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="contacte-edit.php?id=<?php echo (int)$id; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizeaza_contact" value="1">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                <?php include 'contacte-form-fields.php'; ?>
                <div class="mt-6 flex gap-3">
                    <a href="contacte.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg" aria-label="Salvează modificările contactului">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
