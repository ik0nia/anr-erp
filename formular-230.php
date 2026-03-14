<?php
/**
 * Modul Formular 230 – evidență persoane care redirecționează 3.5% din impozit.
 */

ob_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/contacte_helper.php';

// Asigură tabelele necesare (în caz că nu a rulat încă scriptul de update)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS formular230_persoane (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        initiala_tatalui VARCHAR(5) DEFAULT NULL,
        prenume VARCHAR(100) NOT NULL,
        cnp VARCHAR(13) NOT NULL,
        strada VARCHAR(255) DEFAULT NULL,
        nr VARCHAR(10) DEFAULT NULL,
        bl VARCHAR(10) DEFAULT NULL,
        sc VARCHAR(10) DEFAULT NULL,
        et VARCHAR(10) DEFAULT NULL,
        ap VARCHAR(10) DEFAULT NULL,
        localitatea VARCHAR(100) DEFAULT NULL,
        judet VARCHAR(100) DEFAULT NULL,
        cod_postal VARCHAR(10) DEFAULT NULL,
        telefon VARCHAR(50) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        ani_formular TEXT DEFAULT NULL,
        status ENUM('Activ','Inactiv') NOT NULL DEFAULT 'Activ',
        canal_tiparit TINYINT(1) NOT NULL DEFAULT 0,
        canal_online TINYINT(1) NOT NULL DEFAULT 0,
        canal_campanie TINYINT(1) NOT NULL DEFAULT 0,
        canal_altele TINYINT(1) NOT NULL DEFAULT 0,
        bifat_an_recent TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cnp (cnp),
        INDEX idx_nume_prenume (nume, prenume),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS formular230_ani (
        id INT AUTO_INCREMENT PRIMARY KEY,
        an SMALLINT UNSIGNED NOT NULL UNIQUE,
        ordine SMALLINT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS formular230_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        utilizator VARCHAR(100) DEFAULT NULL,
        actiune TEXT NOT NULL,
        persoana_id INT DEFAULT NULL,
        INDEX idx_data_ora (data_ora),
        INDEX idx_persoana (persoana_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    die('Eroare inițializare Formular 230: ' . htmlspecialchars($e->getMessage()));
}

function f230_log(PDO $pdo, string $msg, ?int $persoana_id = null): void {
    $util = $_SESSION['utilizator']['username'] ?? ($_SESSION['utilizator'] ?? 'system');
    $stmt = $pdo->prepare("INSERT INTO formular230_log (utilizator, actiune, persoana_id) VALUES (?, ?, ?)");
    $stmt->execute([$util, $msg, $persoana_id]);
}

function f230_get_ani(PDO $pdo): array {
    $stmt = $pdo->query("SELECT an FROM formular230_ani ORDER BY an DESC");
    return $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'an') : [];
}

function f230_calc_varsta_din_cnp(string $cnp): ?int {
    $cnp = preg_replace('/\D/', '', $cnp);
    if (strlen($cnp) !== 13) return null;
    $s = (int)$cnp[0];
    $aa = (int)substr($cnp, 1, 2);
    $ll = (int)substr($cnp, 3, 2);
    $zz = (int)substr($cnp, 5, 2);
    $secol = 1900;
    if ($s === 3 || $s === 4) $secol = 1800;
    elseif ($s === 5 || $s === 6) $secol = 2000;
    $an = $secol + $aa;
    if (!checkdate($ll, $zz, $an)) return null;
    $birth = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $an, $ll, $zz));
    if (!$birth) return null;
    $today = new DateTime();
    return $today->diff($birth)->y;
}

function f230_sync_contact(PDO $pdo, array $p): void {
    // Creează sau actualizează un contact în categoria „Formular 230”
    $nume = trim($p['nume'] ?? '');
    $prenume = trim($p['prenume'] ?? '');
    $cnp = trim($p['cnp'] ?? '');
    $telefon = trim($p['telefon'] ?? '');
    $email = trim($p['email'] ?? '');
    $localitatea = trim($p['localitatea'] ?? '');
    $judet = trim($p['judet'] ?? '');

    if ($nume === '' || $prenume === '') return;

    ensure_contacte_table($pdo);
    $data_n = contacte_data_nasterii_din_cnp($cnp);

    // Căutăm contact existent cu același CNP + tip_contact Formular 230
    $stmt = $pdo->prepare("SELECT id FROM contacte WHERE cnp = ? AND tip_contact = 'Formular 230' LIMIT 1");
    $stmt->execute([$cnp]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE contacte
            SET nume = ?, prenume = ?, telefon = ?, email = ?, data_nasterii = ?, notite = ?, tip_contact = 'Formular 230'
            WHERE id = ?");
        $notite = trim($localitatea . ', ' . $judet);
        $stmt->execute([$nume, $prenume, $telefon ?: null, $email ?: null, $data_n, $notite ?: null, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii, notite)
            VALUES (?, ?, ?, 'Formular 230', ?, ?, ?, ?)");
        $notite = trim($localitatea . ', ' . $judet);
        $stmt->execute([$nume, $prenume, $cnp, $telefon ?: null, $email ?: null, $data_n, $notite ?: null]);
    }
}

$eroare = '';
$succes = '';

// Filtre/paginare simple
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;
$hide_bifat = !empty($_GET['hide_bifat']) ? 1 : 0;

// Cel mai recent an din formular230_ani
$ani = f230_get_ani($pdo);
$an_curent_form = $ani[0] ?? null;

// Procesare formulare
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adauga_persoana_230']) || isset($_POST['editeaza_persoana_230'])) {
        csrf_require_valid();
        $id = isset($_POST['persoana_id']) ? (int)$_POST['persoana_id'] : 0;
        $nume = trim($_POST['nume'] ?? '');
        $initiala_tatalui = trim($_POST['initiala_tatalui'] ?? '');
        $prenume = trim($_POST['prenume'] ?? '');
        $cnp = preg_replace('/\D/', '', $_POST['cnp'] ?? '');

        if ($nume === '' || $prenume === '' || $cnp === '') {
            $eroare = 'Numele, prenumele și CNP-ul sunt obligatorii.';
        } elseif (strlen($cnp) !== 13) {
            $eroare = 'CNP-ul trebuie să aibă 13 cifre.';
        } else {
            $status = in_array($_POST['status'] ?? '', ['Activ', 'Inactiv'], true) ? $_POST['status'] : 'Activ';
            $ani_selectati = isset($_POST['ani_formular']) && is_array($_POST['ani_formular']) ? array_map('intval', $_POST['ani_formular']) : [];
            sort($ani_selectati);
            $ani_str = $ani_selectati ? implode(',', $ani_selectati) : null;
            $bifat_an_recent = !empty($_POST['bifat_an_recent']) ? 1 : 0;

            $fields = [
                'nume' => $nume,
                'initiala_tatalui' => $initiala_tatalui ?: null,
                'prenume' => $prenume,
                'cnp' => $cnp,
                'strada' => trim($_POST['strada'] ?? '') ?: null,
                'nr' => trim($_POST['nr'] ?? '') ?: null,
                'bl' => trim($_POST['bl'] ?? '') ?: null,
                'sc' => trim($_POST['sc'] ?? '') ?: null,
                'et' => trim($_POST['et'] ?? '') ?: null,
                'ap' => trim($_POST['ap'] ?? '') ?: null,
                'localitatea' => trim($_POST['localitatea'] ?? '') ?: null,
                'judet' => trim($_POST['judet'] ?? '') ?: null,
                'cod_postal' => trim($_POST['cod_postal'] ?? '') ?: null,
                'telefon' => trim($_POST['telefon'] ?? '') ?: null,
                'email' => trim($_POST['email'] ?? '') ?: null,
                'ani_formular' => $ani_str,
                'status' => $status,
                'canal_tiparit' => !empty($_POST['canal_tiparit']) ? 1 : 0,
                'canal_online' => !empty($_POST['canal_online']) ? 1 : 0,
                'canal_campanie' => !empty($_POST['canal_campanie']) ? 1 : 0,
                'canal_altele' => !empty($_POST['canal_altele']) ? 1 : 0,
                'bifat_an_recent' => $bifat_an_recent,
            ];

            if ($id > 0) {
                $setSql = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
                $sql = "UPDATE formular230_persoane SET $setSql WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $fields['id'] = $id;
                $stmt->execute($fields);
                f230_log($pdo, "Actualizare persoană Formular 230: {$nume} {$prenume}", $id);
                $succes = 'Persoana a fost actualizată.';
            } else {
                $cols = implode(', ', array_keys($fields));
                $place = ':' . implode(', :', array_keys($fields));
                $sql = "INSERT INTO formular230_persoane ($cols) VALUES ($place)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($fields);
                $id = (int)$pdo->lastInsertId();
                f230_log($pdo, "Adăugare persoană Formular 230: {$nume} {$prenume}", $id);
                $succes = 'Persoana a fost adăugată.';
            }

            // Sincronizare cu modul Contacte
            $fields['id'] = $id;
            f230_sync_contact($pdo, array_merge($fields, ['cnp' => $cnp]));

            // Refresh pentru a evita double submit
            header('Location: formular-230.php');
            exit;
        }
    } elseif (isset($_POST['arhiveaza_persoana_230'])) {
        csrf_require_valid();
        $id = (int)($_POST['persoana_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE formular230_persoane SET status = 'Inactiv' WHERE id = ?");
            $stmt->execute([$id]);
            f230_log($pdo, "Persoană Formular 230 arhivată", $id);
            $succes = 'Persoana a fost arhivată.';
        }
    } elseif (isset($_POST['toggle_hide_bifat'])) {
        // Doar schimbăm parametru în URL
        $hideFlag = !empty($_POST['hide_bifat']) ? 1 : 0;
        header('Location: formular-230.php?hide_bifat=' . $hideFlag);
        exit;
    }
}

// Încărcare listă persoane pentru afișare
$where = "WHERE status = 'Activ'";
$params = [];
if ($hide_bifat && $an_curent_form !== null) {
    $where .= " AND bifat_an_recent = 0";
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM formular230_persoane $where");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// MySQL/MariaDB nu acceptă parametri numiți în LIMIT/OFFSET, folosim valori întregi direct (sunt deja validate)
$limit = (int)$per_page;
$offset_int = (int)$offset;
$stmtList = $pdo->prepare("
    SELECT * FROM formular230_persoane
    $where
    ORDER BY nume, prenume
    LIMIT $limit OFFSET $offset_int
");
$stmtList->execute($params);
$persoane = $stmtList->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Formular 230</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Evidență persoane care redirecționează 3.5% din impozit către asociație.
            </p>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
                <?php echo htmlspecialchars($eroare); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
            <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status">
                <?php echo htmlspecialchars($succes); ?>
            </div>
        <?php endif; ?>

        <div class="mb-6 flex flex-wrap justify-between items-center gap-3">
            <form method="post" action="formular-230.php" class="flex items-center gap-2">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="toggle_hide_bifat" value="1">
                <label class="flex items-center text-sm text-slate-700 dark:text-gray-200">
                    <input type="checkbox" name="hide_bifat" value="1" <?php echo $hide_bifat ? 'checked' : ''; ?>
                           class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2">
                        Ascunde persoanele bifate pentru anul curent<?php echo $an_curent_form ? ' (' . (int)$an_curent_form . ')' : ''; ?>
                    </span>
                </label>
                <button type="submit"
                        class="px-3 py-1.5 bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-200 rounded-lg text-xs font-medium hover:bg-slate-200 dark:hover:bg-gray-600">
                    Aplică
                </button>
            </form>

            <button type="button"
                    onclick="document.getElementById('dialog-f230').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <i data-lucide="user-plus" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Adaugă persoană
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Nume și Prenume</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Telefon</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Email</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Vârstă</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Localitatea</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-700 dark:text-gray-200">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($persoane)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-sm text-slate-500 dark:text-gray-400">
                                    Nu există înregistrări în acest moment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($persoane as $p): ?>
                                <?php $varsta = f230_calc_varsta_din_cnp($p['cnp'] ?? ''); ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars(trim(($p['nume'] ?? '') . ' ' . ($p['prenume'] ?? ''))); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($p['telefon'] ?? ''); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($p['email'] ?? ''); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo $varsta !== null ? $varsta . ' ani' : '—'; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php
                                        $loc = trim(($p['localitatea'] ?? '') . ($p['judet'] ? ', ' . $p['judet'] : ''));
                                        echo htmlspecialchars($loc ?: '—');
                                        ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right space-x-2">
                                        <!-- Editează: setăm un dataset pe buton și populăm dialogul prin JS simplu -->
                                        <button type="button"
                                                class="inline-flex items-center px-2 py-1 text-xs bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-100 rounded hover:bg-slate-200 dark:hover:bg-gray-600"
                                                onclick="window.location.href='formular-230.php?edit=<?php echo (int)$p['id']; ?>'">
                                            Editează
                                        </button>
                                        <?php if (!empty($p['telefon'])): ?>
                                            <a href="<?php echo htmlspecialchars(contacte_whatsapp_url($p['telefon'])); ?>"
                                               target="_blank"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                WhatsApp
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($p['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Email
                                            </a>
                                        <?php endif; ?>
                                        <form method="post" action="formular-230.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="persoana_id" value="<?php echo (int)$p['id']; ?>">
                                            <button type="submit" name="arhiveaza_persoana_230" value="1"
                                                    class="inline-flex items-center px-2 py-1 text-xs bg-slate-200 dark:bg-gray-700 text-slate-700 dark:text-gray-200 rounded hover:bg-slate-300 dark:hover:bg-gray-600">
                                                Arhivează
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="px-4 py-3 border-t border-slate-200 dark:border-gray-700 flex justify-between items-center text-sm text-slate-600 dark:text-gray-300">
                    <span>Pagina <?php echo $page; ?> din <?php echo $total_pages; ?></span>
                    <div class="space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="formular-230.php?page=<?php echo $page - 1; ?><?php echo $hide_bifat ? '&hide_bifat=1' : ''; ?>"
                               class="px-3 py-1 border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-700">Înapoi</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="formular-230.php?page=<?php echo $page + 1; ?><?php echo $hide_bifat ? '&hide_bifat=1' : ''; ?>"
                               class="px-3 py-1 border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-700">Înainte</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<dialog id="dialog-f230" class="p-0 rounded-lg shadow-xl max-w-3xl w-[calc(100%-2rem)] mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adaugă persoană – Formular 230</h2>
        <form method="post" action="formular-230.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_persoana_230" value="1">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume</label>
                    <input type="text" name="nume" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Inițiala tatălui</label>
                    <input type="text" name="initiala_tatalui" maxlength="5" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume</label>
                    <input type="text" name="prenume" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">CNP</label>
                    <input type="text" name="cnp" maxlength="13" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon</label>
                    <input type="text" name="telefon" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <fieldset class="border border-slate-200 dark:border-gray-700 rounded-lg p-3">
                <legend class="px-1 text-sm font-medium text-slate-800 dark:text-gray-200">Adresă</legend>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Strada</label>
                        <input type="text" name="strada" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr.</label>
                        <input type="text" name="nr" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Bl.</label>
                        <input type="text" name="bl" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Sc.</label>
                        <input type="text" name="sc" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Et.</label>
                        <input type="text" name="et" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ap.</label>
                        <input type="text" name="ap" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Localitatea</label>
                        <input type="text" name="localitatea" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Județ</label>
                        <input type="text" name="judet" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cod poștal</label>
                        <input type="text" name="cod_postal" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </fieldset>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <option value="Activ">Activ</option>
                        <option value="Inactiv">Inactiv</option>
                    </select>
                </div>
                <div>
                    <span class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Canal formular</span>
                    <div class="space-y-1 text-sm text-slate-700 dark:text-gray-200">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_tiparit" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Tipărit
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_online" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Formular online
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_campanie" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Campanie
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_altele" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Altele
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ani în care a trimis formulare</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($ani as $an): ?>
                        <label class="inline-flex items-center gap-1 text-sm text-slate-700 dark:text-gray-200">
                            <input type="checkbox" name="ani_formular[]" value="<?php echo (int)$an; ?>"
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            <?php echo (int)$an; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($an_curent_form): ?>
                <div>
                    <label class="flex items-center gap-2 text-sm text-slate-800 dark:text-gray-200">
                        <input type="checkbox" name="bifat_an_recent" value="1"
                               class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                        Formular depus pentru anul curent (<?php echo (int)$an_curent_form; ?>)
                    </label>
                </div>
            <?php endif; ?>

            <div class="mt-4 flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('dialog-f230').close()"
                        class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                    Anulează
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    Salvează
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>lucide.createIcons();</script>
</body>
</html>

