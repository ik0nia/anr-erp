<?php
/**
 * Script pentru adăugare automată Foreign Keys în CRM ANR Bihor
 * Verifică și adaugă FK-urile lipsă pentru integritate referențială
 * 
 * RULARE: Accesează acest fișier în browser sau rulează via CLI: php fix_foreign_keys.php
 */

require_once __DIR__ . '/config.php';
require_once 'includes/auth_helper.php';

// Doar administratorii pot rula acest script
if (empty($_SESSION['user_id']) || !is_admin()) {
    die('Acces restricționat. Doar administratorii pot rula acest script.');
}

$rezultate = [];
$eroare = '';
$succes = false;

// Lista FK-uri de adăugat
$foreign_keys = [
    [
        'table' => 'liste_prezenta_membri',
        'constraint' => 'fk_lista',
        'column' => 'lista_id',
        'references' => 'liste_prezenta(id)',
        'on_delete' => 'CASCADE'
    ],
    [
        'table' => 'liste_prezenta_membri',
        'constraint' => 'fk_membru',
        'column' => 'membru_id',
        'references' => 'membri(id)',
        'on_delete' => 'CASCADE'
    ],
    [
        'table' => 'activitati',
        'constraint' => 'fk_lista_prezenta',
        'column' => 'lista_prezenta_id',
        'references' => 'liste_prezenta(id)',
        'on_delete' => 'SET NULL'
    ],
    [
        'table' => 'registru_interactiuni',
        'constraint' => 'fk_subiect',
        'column' => 'subiect_id',
        'references' => 'registru_interactiuni_subiecte(id)',
        'on_delete' => 'SET NULL'
    ],
    [
        'table' => 'registru_interactiuni',
        'constraint' => 'fk_task_interactiune',
        'column' => 'task_id',
        'references' => 'taskuri(id)',
        'on_delete' => 'SET NULL'
    ],
    [
        'table' => 'registratura',
        'constraint' => 'fk_task_registratura',
        'column' => 'task_id',
        'references' => 'taskuri(id)',
        'on_delete' => 'SET NULL'
    ],
    [
        'table' => 'password_reset_tokens',
        'constraint' => 'fk_utilizator_reset',
        'column' => 'utilizator_id',
        'references' => 'utilizatori(id)',
        'on_delete' => 'CASCADE'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executa_fix'])) {
    csrf_require_valid();
    
    try {
        $pdo->beginTransaction();
        
        foreach ($foreign_keys as $fk) {
            $table = $fk['table'];
            $constraint = $fk['constraint'];
            $column = $fk['column'];
            $references = $fk['references'];
            $on_delete = $fk['on_delete'];
            
            // Verifică dacă tabelul există
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                $rezultate[] = [
                    'status' => 'skip',
                    'mesaj' => "Tabelul '{$table}' nu există - omis"
                ];
                continue;
            }
            
            // Verifică dacă coloana există
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $rezultate[] = [
                    'status' => 'skip',
                    'mesaj' => "Coloana '{$table}.{$column}' nu există - omis"
                ];
                continue;
            }
            
            // Verifică dacă FK-ul există deja
            $stmt = $pdo->prepare("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ");
            $stmt->execute([$table, $constraint]);
            if ($stmt->fetch()) {
                $rezultate[] = [
                    'status' => 'skip',
                    'mesaj' => "FK '{$constraint}' există deja în '{$table}'"
                ];
                continue;
            }
            
            // Verifică dacă există date orfane
            list($ref_table, $ref_column) = explode('(', str_replace(')', '', $references));
            $ref_column = trim($ref_column);
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM `{$table}` t 
                LEFT JOIN `{$ref_table}` r ON t.`{$column}` = r.`{$ref_column}` 
                WHERE t.`{$column}` IS NOT NULL AND r.`{$ref_column}` IS NULL
            ");
            $stmt->execute();
            $orphans = $stmt->fetch()['cnt'];
            
            if ($orphans > 0) {
                $rezultate[] = [
                    'status' => 'warning',
                    'mesaj' => "FK '{$constraint}': {$orphans} înregistrări orfane găsite. Se va încerca adăugarea FK-ului."
                ];
            }
            
            // Adaugă FK
            $sql = "ALTER TABLE `{$table}` 
                    ADD CONSTRAINT `{$constraint}` 
                    FOREIGN KEY (`{$column}`) 
                    REFERENCES `{$ref_table}`(`{$ref_column}`) 
                    ON DELETE {$on_delete}";
            
            try {
                $pdo->exec($sql);
                $rezultate[] = [
                    'status' => 'success',
                    'mesaj' => "FK '{$constraint}' adăugat cu succes în '{$table}'"
                ];
            } catch (PDOException $e) {
                // Dacă eșuează din cauza datelor orfane, încercăm să le curățăm
                if (strpos($e->getMessage(), 'Cannot add foreign key constraint') !== false && $orphans > 0) {
                    $rezultate[] = [
                        'status' => 'error',
                        'mesaj' => "FK '{$constraint}': Eșec din cauza a {$orphans} înregistrări orfane. Curățați datele orfane manual."
                    ];
                } else {
                    $rezultate[] = [
                        'status' => 'error',
                        'mesaj' => "FK '{$constraint}': Eroare - " . $e->getMessage()
                    ];
                }
            }
        }
        
        $pdo->commit();
        $succes = true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $eroare = 'Eroare generală: ' . $e->getMessage();
        error_log('Eroare fix_foreign_keys: ' . $e->getMessage());
    }
}

// Verificare FK-uri existente
$fk_existente = [];
try {
    foreach ($foreign_keys as $fk) {
        $table = $fk['table'];
        $constraint = $fk['constraint'];
        
        $stmt = $pdo->prepare("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ");
        $stmt->execute([$table, $constraint]);
        if ($stmt->fetch()) {
            $fk_existente[] = $constraint;
        }
    }
} catch (PDOException $e) {
    // Ignoră erorile la verificare
}

include 'header.php';
include 'sidebar.php';
?>

<main class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Adăugare Foreign Keys</h1>
        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">Verificare și adăugare automată Foreign Keys pentru integritate referențială</p>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if ($succes): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-800 dark:text-emerald-200 rounded-r" role="status">
            <p class="font-semibold mb-2">Procesare finalizată!</p>
            <p class="text-sm">Verificați rezultatele mai jos.</p>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Status Foreign Keys</h2>
            
            <div class="mb-4">
                <p class="text-sm text-slate-600 dark:text-gray-400">
                    <strong><?php echo count($fk_existente); ?></strong> din <strong><?php echo count($foreign_keys); ?></strong> Foreign Keys există deja.
                </p>
            </div>

            <?php if (!empty($rezultate)): ?>
            <div class="space-y-2 mb-4">
                <?php foreach ($rezultate as $r): ?>
                <div class="p-3 rounded-lg <?php 
                    echo $r['status'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200' : 
                        ($r['status'] === 'error' ? 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200' : 
                        ($r['status'] === 'warning' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200' : 
                        'bg-slate-50 dark:bg-gray-700 text-slate-600 dark:text-gray-300')); 
                ?>">
                    <span class="font-medium"><?php echo htmlspecialchars($r['mesaj']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="post" action="fix_foreign_keys.php" class="mt-6">
                <?php echo csrf_field(); ?>
                <button type="submit" name="executa_fix" value="1" 
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                        onclick="return confirm('Adăugați Foreign Keys-urile lipsă? Această acțiune poate dura câteva secunde.');">
                    <i data-lucide="key" class="w-4 h-4 inline mr-2" aria-hidden="true"></i>
                    Adaugă Foreign Keys Lipsă
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Lista Foreign Keys de Adăugat</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tabel</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Constraint</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Coloană</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Referință</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($foreign_keys as $fk): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($fk['table']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($fk['constraint']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($fk['column']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($fk['references']); ?></td>
                            <td class="px-4 py-3 text-sm">
                                <?php if (in_array($fk['constraint'], $fk_existente)): ?>
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 rounded">
                                    <i data-lucide="check-circle" class="w-3 h-3 mr-1" aria-hidden="true"></i>
                                    Există
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 rounded">
                                    <i data-lucide="alert-circle" class="w-3 h-3 mr-1" aria-hidden="true"></i>
                                    Lipsă
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php 
// Include footer dacă există, altfel doar închide tag-urile
if (file_exists(__DIR__ . '/footer.php')) {
    include 'footer.php';
} else {
    echo '</body></html>';
}
?>
