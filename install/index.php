<?php
/**
 * Installer CRM ANR Bihor
 * Instalare pas cu pas pentru platforma CRM
 */

// Previne accesarea dacă platforma e deja instalată
$installedFile = __DIR__ . '/../.installed';
if (file_exists($installedFile)) {
    die('Platforma este deja instalată. Ștergeți fișierul .installed pentru a reinstala.');
}

// Verifică dacă există config.php și dacă platforma e funcțională
if (file_exists(__DIR__ . '/../config.php')) {
    try {
        require_once __DIR__ . '/../config.php';
        // Verifică dacă există deja utilizatori
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM utilizatori LIMIT 1");
                $count = $stmt->fetchColumn();
                if ($count > 0) {
                    // Creează fișier .installed pentru a preveni reinstalarea
                    file_put_contents($installedFile, date('Y-m-d H:i:s'));
                    die('Platforma este deja instalată și are utilizatori. Ștergeți fișierul .installed pentru a reinstala.');
                }
            } catch (PDOException $e) {
                // Baza de date nu există sau nu e configurată, continuă instalarea
            }
        }
    } catch (Exception $e) {
        // Config nu există sau e invalid, continuă instalarea
        // Poate fi o instalare nouă sau reinstalare
    }
}

session_start();

// Stocare progres instalare în sesiune
if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
    $_SESSION['install_data'] = [];
}

$step = (int)($_GET['step'] ?? $_SESSION['install_step']);
$max_step = 8;

// Procesare pași
require_once __DIR__ . '/installer.php';
$installer = new CRMInstaller();
$result = $installer->processStep($step);

if ($result['redirect']) {
    header('Location: ' . $result['redirect']);
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
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            font-size: 12px;
            color: #6b7280;
        }
        .step-item.active {
            color: #f5576c;
            font-weight: 600;
        }
        .step-item.completed {
            color: #10b981;
        }
        .step-item::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: -1;
        }
        .step-item:last-child::after {
            display: none;
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
            
            <div class="step-content">
                <?php include __DIR__ . '/steps/step-' . $step . '.php'; ?>
            </div>
            
            <div class="button-group">
                <?php if ($step > 1): ?>
                <a href="?step=<?php echo $step - 1; ?>" class="btn btn-secondary">Înapoi</a>
                <?php endif; ?>
                
                <?php if ($step < $max_step): ?>
                <form method="POST" action="?step=<?php echo $step; ?>" style="display: inline;">
                    <button type="submit" class="btn btn-primary" id="next-btn">
                        Continuă
                    </button>
                </form>
                <?php endif; ?>
            </div>
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
