<?php
/**
 * Management Generare Documente - CRM ANR
 * Upload templateuri, editare nume, activare/dezactivare, listă taguri
 */
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/document_helper.php';

$eroare = '';
$succes = '';

// Creare tabele și director dacă nu există
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS documente_template (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume_afisare VARCHAR(255) NOT NULL,
        nume_fisier VARCHAR(255) NOT NULL,
        activ TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_activ (activ)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (!is_dir(UPLOAD_TEMPLATE_DIR)) {
        mkdir(UPLOAD_TEMPLATE_DIR, 0755, true);
    }
} catch (PDOException $e) {
    $eroare = 'Eroare la inițializare: ' . $e->getMessage();
}

// Upload template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    csrf_require_valid();
    $nume_afisare = trim($_POST['nume_afisare'] ?? '');
    if (empty($nume_afisare)) {
        $eroare = 'Numele afișat este obligatoriu.';
    } elseif (!isset($_FILES['fisier_template']) || $_FILES['fisier_template']['error'] !== UPLOAD_ERR_OK) {
        $eroare = 'Selectați un fișier .docx valid.';
    } else {
        $f = $_FILES['fisier_template'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            $eroare = 'Doar fișiere Word (.docx) sunt acceptate.';
        } elseif ($f['size'] > 10 * 1024 * 1024) {
            $eroare = 'Fișierul depășește 10 MB.';
        } else {
            $filename = 'tpl_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nume_afisare) . '.docx';
            $filepath = UPLOAD_TEMPLATE_DIR . $filename;
            if (move_uploaded_file($f['tmp_name'], $filepath)) {
                try {
                    $stmt = $pdo->prepare('INSERT INTO documente_template (nume_afisare, nume_fisier, activ) VALUES (?, ?, 1)');
                    $stmt->execute([$nume_afisare, $filename]);
                    log_activitate($pdo, 'Template document adăugat: ' . $nume_afisare);
                    header('Location: /generare-documente?succes=1');
                    exit;
                } catch (PDOException $e) {
                    unlink($filepath);
                    $eroare = 'Eroare la salvare: ' . $e->getMessage();
                }
            } else {
                $eroare = 'Eroare la încărcarea fișierului.';
            }
        }
    }
}

// Ștergere template (din listă și fișier de pe server)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_template'])) {
    csrf_require_valid();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT nume_afisare, nume_fisier FROM documente_template WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $pdo->prepare('DELETE FROM documente_template WHERE id = ?')->execute([$id]);
                $fisier = UPLOAD_TEMPLATE_DIR . $row['nume_fisier'];
                if (file_exists($fisier)) {
                    @unlink($fisier);
                }
                log_activitate($pdo, 'Template document șters: ' . $row['nume_afisare'] . ' (ID ' . $id . ')');
                header('Location: /generare-documente?succes=3');
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la ștergere.';
        }
    }
}

// Actualizare nume / activ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_template'])) {
    csrf_require_valid();
    $id = (int)($_POST['id'] ?? 0);
    $nume_afisare = trim($_POST['nume_afisare'] ?? '');
    $activ = isset($_POST['activ']) ? 1 : 0;
    if ($id > 0 && !empty($nume_afisare)) {
        try {
            // Încarcă datele vechi pentru logging
            $stmt_old = $pdo->prepare('SELECT nume_afisare, activ FROM documente_template WHERE id = ?');
            $stmt_old->execute([$id]);
            $template_vechi = $stmt_old->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare('UPDATE documente_template SET nume_afisare = ?, activ = ? WHERE id = ?');
            $stmt->execute([$nume_afisare, $activ, $id]);
            
            // Log modificări
            $modificari = [];
            if ($template_vechi) {
                if (($template_vechi['nume_afisare'] ?? '') !== $nume_afisare) {
                    $modificari[] = log_format_modificare('Nume template', $template_vechi['nume_afisare'] ?? '', $nume_afisare);
                }
                if (($template_vechi['activ'] ?? 0) != $activ) {
                    $modificari[] = log_format_modificare('Status activ', ($template_vechi['activ'] ?? 0) ? 'Activ' : 'Inactiv', $activ ? 'Activ' : 'Inactiv');
                }
            }
            
            if (!empty($modificari)) {
                log_activitate($pdo, "documente_template: " . implode("; ", $modificari) . " / Template ID: {$id}");
            } else {
                log_activitate($pdo, "documente_template: Template actualizat ID {$id}");
            }
            header('Location: /generare-documente?succes=2');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la actualizare.';
        }
    }
}

$templates = [];
try {
    $stmt = $pdo->query('SELECT * FROM documente_template ORDER BY nume_afisare ASC');
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eroare = 'Eroare la încărcarea templateurilor.';
}

$taguri = get_taguri_disponibile();
include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div class="flex items-center gap-2">
            <a href="setari.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">
                <i data-lucide="arrow-left" class="w-5 h-5 inline" aria-hidden="true"></i> Înapoi
            </a>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Management Generare Documente</h1>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1 space-y-6">
        <?php if (isset($_GET['succes'])): ?>
        <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="status">
            <?php
            if ($_GET['succes'] == '2') echo 'Template actualizat cu succes.';
            elseif ($_GET['succes'] == '3') echo 'Template șters cu succes.';
            else echo 'Template încărcat cu succes.';
            ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($eroare)): ?>
        <div class="p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <!-- Upload template -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="upload-heading">
            <h2 id="upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                <i data-lucide="upload" class="inline w-5 h-5 mr-2" aria-hidden="true"></i>
                Încărcare template
            </h2>
            <form method="post" enctype="multipart/form-data" class="flex flex-wrap gap-4 items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="upload_template" value="1">
                <div>
                    <label for="nume_afisare" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume afișat</label>
                    <input type="text" id="nume_afisare" name="nume_afisare" required
                           placeholder="Ex: Cerere pentru loc de parcare"
                           class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg w-64 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div>
                    <label for="fisier_template" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Fișier .docx</label>
                    <input type="file" id="fisier_template" name="fisier_template" accept=".docx" required
                           class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Încarcă template-ul selectat">
                    Încarcă
                </button>
            </form>
        </section>

        <!-- Tabel templateuri -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden" aria-labelledby="tabel-heading">
            <h2 id="tabel-heading" class="text-lg font-semibold text-slate-900 dark:text-white p-4">Templateuri încărcate</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Fișier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Activ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu există templateuri.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($templates as $t): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['nume_afisare']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($t['nume_fisier']); ?></td>
                            <td class="px-4 py-3">
                                <form method="post" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="actualizeaza_template" value="1">
                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="nume_afisare" value="<?php echo htmlspecialchars($t['nume_afisare']); ?>">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="activ" value="1" <?php echo $t['activ'] ? 'checked' : ''; ?>
                                               onchange="this.form.submit()"
                                               aria-label="Template activ">
                                    </label>
                                </form>
                            </td>
                            <td class="px-4 py-3 flex flex-wrap gap-2 items-center">
                                <button type="button" onclick="document.getElementById('edit-<?php echo $t['id']; ?>').showModal()"
                                        class="px-3 py-1.5 text-sm bg-amber-100 dark:bg-amber-800/70 text-amber-900 dark:text-amber-100 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-700"
                                        aria-label="Editează template: <?php echo htmlspecialchars($t['nume_afisare']); ?>">
                                    <i data-lucide="edit" class="w-4 h-4 inline" aria-hidden="true"></i> Editează
                                </button>
                                <form method="post" class="inline" onsubmit="return confirm('Ștergeți acest template? Fișierul va fi șters de pe server.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="sterge_template" value="1">
                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 text-sm bg-red-100 dark:bg-red-800/70 text-red-900 dark:text-red-100 rounded-lg hover:bg-red-200 dark:hover:bg-red-700"
                                            aria-label="Șterge template: <?php echo htmlspecialchars($t['nume_afisare']); ?>">
                                        <i data-lucide="trash-2" class="w-4 h-4 inline" aria-hidden="true"></i> Șterge documentul
                                    </button>
                                </form>
                                <dialog id="edit-<?php echo $t['id']; ?>" class="rounded-lg shadow-xl p-0 max-w-md w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto">
                                    <form method="post" class="p-6">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="actualizeaza_template" value="1">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <h3 class="text-lg font-semibold mb-4">Editează template</h3>
                                        <label class="block text-sm font-medium mb-2">Nume afișat</label>
                                        <input type="text" name="nume_afisare" value="<?php echo htmlspecialchars($t['nume_afisare']); ?>" required
                                               class="w-full px-3 py-2 border rounded-lg mb-4 dark:bg-gray-700 dark:text-white">
                                        <label class="flex items-center gap-2 mb-4">
                                            <input type="checkbox" name="activ" value="1" <?php echo $t['activ'] ? 'checked' : ''; ?>>
                                            <span>Activ (apare în listă)</span>
                                        </label>
                                        <div class="flex gap-2">
                                            <button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 border rounded-lg" aria-label="Anulează editare template">Anulare</button>
                                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg" aria-label="Salvează modificările template">Salvează</button>
                                        </div>
                                    </form>
                                </dialog>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Taguri disponibile -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="taguri-heading">
            <h2 id="taguri-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                <i data-lucide="tag" class="inline w-5 h-5 mr-2" aria-hidden="true"></i>
                Taguri disponibile
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">
                Templateurile se stochează în folderul <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">uploads/documente_template</code>. Folosiți în documentele Word tagurile sub forma <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[nume_tag]</code>. Ex: <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[nume]</code>, <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[prenume]</code>, <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[datagenerare]</code>.
            </p>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Tagurile fără date în profilul membrului și <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[datagenerare]</code> (dacă nu este bifată opțiunea la generare) vor apărea ca spațiu în documentul generat; nu se afișează textul <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[tag]</code>.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto">
                <?php foreach ($taguri as $tag): ?>
                <div class="flex items-center gap-2 text-sm py-1">
                    <code class="bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded font-mono text-xs">[<?php echo htmlspecialchars($tag['tag']); ?>]</code>
                    <span class="text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($tag['desc']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
