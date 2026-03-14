<?php
/**
 * Adăugare contact
 */
require_once __DIR__ . '/config.php';
require_once 'includes/contacte_helper.php';
require_once 'includes/log_helper.php';

ensure_contacte_table($pdo);
$eroare = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_contact'])) {
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
            $data_nasterii_val = !empty($data_nasterii) ? $data_nasterii : null;
            if ($data_nasterii_val) {
                $d = date_create_from_format('Y-m-d', $data_nasterii_val) ?: date_create_from_format('d.m.Y', $data_nasterii_val);
                $data_nasterii_val = $d ? $d->format('Y-m-d') : null;
            }
            $stmt = $pdo->prepare('INSERT INTO contacte (nume, prenume, companie, tip_contact, telefon, telefon_personal, email, email_personal, website, data_nasterii, notite, referinta_contact) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$nume, $prenume ?: null, $companie ?: null, $tip_contact, $telefon ?: null, $telefon_personal ?: null, $email ?: null, $email_personal ?: null, $website ?: null, $data_nasterii_val, $notite ?: null, $referinta_contact ?: null]);
            log_activitate($pdo, 'contacte: Adăugat contact ' . trim($nume . ' ' . $prenume));
            header('Location: contacte.php?succes=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la salvare.';
        }
    }
}

$tipuri = get_contacte_tipuri();
include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Adaugă contact</h1>
        <a href="contacte.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">← Înapoi</a>
    </header>
    <div class="p-6 overflow-y-auto flex-1 max-w-2xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="contacte-adauga.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_contact" value="1">
                <?php include 'contacte-form-fields.php'; ?>
                <div class="mt-6 flex gap-3">
                    <a href="contacte.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg" aria-label="Salvează contactul nou">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
