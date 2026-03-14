<?php
/**
 * Fișier de test pentru a verifica dacă installer-ul este accesibil
 */
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Installer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { background: #eff6ff; padding: 15px; border-radius: 6px; margin: 15px 0; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Test Acces Installer</h1>
        
        <div class="info">
            <h3>Informații server:</h3>
            <ul>
                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                <li><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></li>
                <li><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></li>
                <li><strong>Script Path:</strong> <?php echo __FILE__; ?></li>
                <li><strong>Current URL:</strong> <?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></li>
            </ul>
        </div>
        
        <h3>Verificări:</h3>
        <ul>
            <li>
                <?php if (file_exists(__DIR__ . '/index.php')): ?>
                    <span class="success">✓</span> Fișier <code>install/index.php</code> există
                <?php else: ?>
                    <span class="error">✗</span> Fișier <code>install/index.php</code> NU există
                <?php endif; ?>
            </li>
            <li>
                <?php if (file_exists(__DIR__ . '/installer.php')): ?>
                    <span class="success">✓</span> Fișier <code>install/installer.php</code> există
                <?php else: ?>
                    <span class="error">✗</span> Fișier <code>install/installer.php</code> NU există
                <?php endif; ?>
            </li>
            <li>
                <?php if (is_dir(__DIR__ . '/steps')): ?>
                    <span class="success">✓</span> Folder <code>install/steps/</code> există
                <?php else: ?>
                    <span class="error">✗</span> Folder <code>install/steps/</code> NU există
                <?php endif; ?>
            </li>
        </ul>
        
        <div class="info">
            <h3>Pași următori:</h3>
            <ol>
                <li>Dacă toate verificările sunt OK, accesează: <a href="index.php"><code>install/index.php</code></a></li>
                <li>Dacă primești eroare sau redirecționare, verifică:
                    <ul>
                        <li>Dacă există un fișier <code>index.php</code> în root care redirecționează</li>
                        <li>Dacă hosting-ul are redirecționări configurate în cPanel</li>
                        <li>Dacă URL-ul este corect (fără typo-uri)</li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <p style="margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px;">
                → Accesează Installer
            </a>
        </p>
    </div>
</body>
</html>
