<?php
/**
 * Installer alternativ CRM ANR Bihor
 * Accesibil direct fără a fi interceptat de redirecționări
 * Accesează: https://domeniul-tau.ro/crm-anr-bihor/setup.php
 */

// Previne redirecționări
if (ob_get_level()) {
    ob_end_clean();
}

// Previne accesarea dacă platforma e deja instalată
$installedFile = __DIR__ . '/.installed';
if (file_exists($installedFile)) {
    die('Platforma este deja instalată. Ștergeți fișierul .installed pentru a reinstala.');
}

// Verifică dacă există config.php și dacă platforma e funcțională
// IMPORTANT: Nu încărcăm config.php dacă are setări default (pentru XAMPP local)
// pentru că va genera eroare de conexiune și va opri scriptul cu die()
$skipConfigCheck = false;
if (file_exists(__DIR__ . '/config.php')) {
    // Citește conținutul config.php pentru a verifica dacă e configurat pentru hosting
    $configContent = @file_get_contents(__DIR__ . '/config.php');
    
    if ($configContent !== false) {
        // Verifică dacă are setări default XAMPP (root, fără parolă, localhost)
        $isDefaultConfig = (
            strpos($configContent, "DB_USER', 'root'") !== false ||
            strpos($configContent, "DB_PASS', ''") !== false ||
            strpos($configContent, "crm-anr-bihorxampp") !== false ||
            preg_match("/define\s*\(\s*['\"]DB_PASS['\"]\s*,\s*['\"]['\"]/", $configContent)
        );
        
        // Dacă e config default, NU-l încărca deloc - continuă direct cu instalarea
        if ($isDefaultConfig) {
            // Config.php are setări default XAMPP, nu-l încărca
            // Continuă direct cu instalarea fără să încerce să se conecteze
            $skipConfigCheck = true;
        }
    }
}

// Dacă config.php nu e default, verifică dacă platforma e deja instalată
if (!$skipConfigCheck && file_exists(__DIR__ . '/config.php')) {
    // Config.php pare să fie configurat pentru hosting
    // Verifică dacă există fișier .installed (mai sigur decât să încărci config.php)
    // Dacă .installed există, platforma e deja instalată
    if (file_exists($installedFile)) {
        die('Platforma este deja instalată. Ștergeți fișierul .installed pentru a reinstala.');
    }
}

// Include logica de instalare
session_start();

// Stocare progres instalare în sesiune
if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
    $_SESSION['install_data'] = [];
}

// Include clasa installer
require_once __DIR__ . '/install/installer.php';

$step = (int)($_GET['step'] ?? $_SESSION['install_step']);
$max_step = 8;

// Procesare pași
$installer = new CRMInstaller();
$result = $installer->processStep($step);

if ($result['redirect']) {
    // Redirect: result conține "?step=N", adăugăm numele scriptului
    $currentScript = basename($_SERVER['PHP_SELF'] ?? 'setup.php');
    $redirectUrl = $currentScript . $result['redirect'];
    // Asigură-te că nu duplicăm scriptul (dacă result conține deja setup.php)
    if (strpos($result['redirect'], 'setup.php') === 0) {
        $redirectUrl = $result['redirect'];
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$current_step_data = $_SESSION['install_data'][$step] ?? [];
$errors = $result['errors'] ?? [];
$success = $result['success'] ?? false;
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalare CRM ANR Bihor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .installer-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .installer-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .installer-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .progress-bar {
            background: #e5e7eb;
            height: 6px;
            position: relative;
        }
        .progress-fill {
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
            height: 100%;
            transition: width 0.3s ease;
            width: <?php echo ($step / $max_step * 100); ?>%;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            font-size: 11px;
            color: #6b7280;
            min-width: 60px;
            margin: 5px 0;
        }
        .step-item.active {
            color: #f5576c;
            font-weight: 600;
        }
        .step-item.completed {
            color: #10b981;
        }
        .installer-content {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .form-group label .required {
            color: #ef4444;
        }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group input[type="url"],
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #f5576c;
        }
        .form-group .help-text {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
        }
        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        .info-box h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #1e40af;
        }
        .info-box ul {
            margin-left: 20px;
            color: #1e3a8a;
        }
        .info-box li {
            margin-bottom: 4px;
        }
        .check-list {
            list-style: none;
            margin: 0;
        }
        .check-list li {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
        }
        .check-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 18px;
        }
        .check-list li.error::before {
            content: '✗';
            color: #ef4444;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .step-content {
            min-height: 300px;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>Instalare CRM ANR Bihor</h1>
            <p>Asistent de instalare pas cu pas</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        
        <div class="step-indicator">
            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                <div>1. Cerințe</div>
            </div>
            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                <div>2. Baza de date</div>
            </div>
            <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                <div>3. Tabele</div>
            </div>
            <div class="step-item <?php echo $step >= 4 ? ($step > 4 ? 'completed' : 'active') : ''; ?>">
                <div>4. Admin</div>
            </div>
            <div class="step-item <?php echo $step >= 5 ? ($step > 5 ? 'completed' : 'active') : ''; ?>">
                <div>5. Config</div>
            </div>
            <div class="step-item <?php echo $step >= 6 ? ($step > 6 ? 'completed' : 'active') : ''; ?>">
                <div>6. Pachete</div>
            </div>
            <div class="step-item <?php echo $step >= 7 ? ($step > 7 ? 'completed' : 'active') : ''; ?>">
                <div>7. CSS</div>
            </div>
            <div class="step-item <?php echo $step >= 8 ? 'active' : ''; ?>">
                <div>8. Finalizare</div>
            </div>
        </div>
        
        <div class="installer-content">
            <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong>Eroare:</strong>
                <ul style="margin-top: 8px; margin-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php 
            $stepFile = __DIR__ . '/install/steps/step-' . $step . '.php';
            $stepHasForm = in_array($step, [2, 4, 5], true); // Pașii care au câmpuri de completat
            $formAction = htmlspecialchars($_SERVER['PHP_SELF'] ?? 'setup.php') . '?step=' . $step;
            ?>
            <?php if ($step < $max_step): ?>
            <form method="POST" action="<?php echo $formAction; ?>" id="install-step-form">
            <?php endif; ?>
            
            <div class="step-content">
                <?php 
                if (file_exists($stepFile)) {
                    include $stepFile;
                } else {
                    echo '<p>Eroare: Fișierul pasului ' . $step . ' nu a fost găsit.</p>';
                }
                ?>
            </div>
            
            <div class="button-group">
                <?php if ($step > 1): ?>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? 'setup.php'); ?>?step=<?php echo $step - 1; ?>" class="btn btn-secondary">Înapoi</a>
                <?php endif; ?>
                
                <?php if ($step < $max_step): ?>
                    <button type="submit" class="btn btn-primary" id="next-btn">
                        Continuă
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if ($step < $max_step): ?>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Previne dubla trimitere
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('next-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = 'Se procesează...<span class="loading"></span>';
            }
        });
    </script>
</body>
</html>
