<?php
/**
 * Script pentru crearea membrilor de test
 * Rulați acest script o singură dată pentru a crea membri de test în baza de date
 */
require_once __DIR__ . '/config.php';
require_once 'includes/cnp_validator.php';
require_once 'includes/log_helper.php';

echo "<!DOCTYPE html>
<html lang='ro'>
<head><meta charset="utf-8">
    
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Creare Membri Test - CRM ANR Bihor</title>
    <link href='css/tailwind.css' rel='stylesheet'>
</head>
<body class='bg-gray-50 p-8'>
<div class='max-w-4xl mx-auto'>
    <h1 class='text-2xl font-bold mb-6'>Creare Membri de Test</h1>";

$eroare = '';
$succes = [];

try {
    // Verifică dacă tabelul membri există, dacă nu îl creează
    $stmt = $pdo->query("SHOW TABLES LIKE 'membri'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='mb-4 p-4 bg-blue-100 border-l-4 border-blue-600 text-blue-900 rounded-r'>
                <p><strong>Informație:</strong> Tabelul 'membri' nu există. Se creează acum...</p>
              </div>";
        
        // Creează tabelul membri
        $pdo->exec("CREATE TABLE IF NOT EXISTS membri (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dosarnr VARCHAR(50) DEFAULT NULL,
            dosardata DATE DEFAULT NULL,
            nume VARCHAR(100) NOT NULL,
            prenume VARCHAR(100) NOT NULL,
            telefonnev VARCHAR(20) DEFAULT NULL,
            telefonapartinator VARCHAR(20) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            datanastere DATE DEFAULT NULL,
            locnastere VARCHAR(100) DEFAULT NULL,
            judnastere VARCHAR(50) DEFAULT NULL,
            ciseria VARCHAR(2) DEFAULT NULL,
            cinumar VARCHAR(7) DEFAULT NULL,
            cielib VARCHAR(100) DEFAULT NULL,
            cidataelib DATE DEFAULT NULL,
            gdpr TINYINT(1) DEFAULT 0,
            codpost VARCHAR(10) DEFAULT NULL,
            tipmediuur ENUM('Urban', 'Rural') DEFAULT NULL,
            domloc VARCHAR(100) DEFAULT NULL,
            domstr VARCHAR(100) DEFAULT NULL,
            domnr VARCHAR(20) DEFAULT NULL,
            dombl VARCHAR(20) DEFAULT NULL,
            domsc VARCHAR(10) DEFAULT NULL,
            domet VARCHAR(10) DEFAULT NULL,
            domap VARCHAR(10) DEFAULT NULL,
            sex ENUM('Masculin', 'Feminin') DEFAULT NULL,
            hgrad ENUM('Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap') DEFAULT NULL,
            hmotiv TEXT DEFAULT NULL,
            hdur ENUM('Permanent', 'Revizuibil') DEFAULT NULL,
            cnp VARCHAR(13) NOT NULL,
            cenr VARCHAR(50) DEFAULT NULL,
            cedata DATE DEFAULT NULL,
            ceexp DATE DEFAULT NULL,
            primaria VARCHAR(255) DEFAULT NULL,
            doc_ci VARCHAR(255) DEFAULT NULL,
            doc_ch VARCHAR(255) DEFAULT NULL,
            notamembru TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_dosarnr (dosarnr),
            INDEX idx_cnp (cnp),
            INDEX idx_nume_prenume (nume, prenume),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<div class='mb-4 p-4 bg-emerald-100 border-l-4 border-emerald-600 text-emerald-900 rounded-r'>
                <p><strong>Succes:</strong> Tabelul 'membri' a fost creat cu succes!</p>
              </div>";
    }
    
    // Pentru testare: elimină temporar constraint-ul UNIQUE pe CNP dacă există
    try {
        $pdo->exec("ALTER TABLE membri DROP INDEX idx_cnp");
    } catch (PDOException $e) {
        // Index-ul poate să nu existe sau să fie numit diferit
    }
    
    try {
        // Încearcă să elimine constraint-ul UNIQUE direct
        $pdo->exec("ALTER TABLE membri DROP INDEX cnp");
    } catch (PDOException $e) {
        // Constraint-ul poate să nu existe sau să fie numit diferit
    }
    
    // Readaugă index-ul fără UNIQUE pentru testare
    try {
        $pdo->exec("ALTER TABLE membri ADD INDEX idx_cnp (cnp)");
    } catch (PDOException $e) {
        // Index-ul poate exista deja
    }
    // Membru 1: Ion Popescu
    $membru1 = [
        'dosarnr' => 'DOS-2024-001',
        'dosardata' => '2024-01-15',
        'nume' => 'Popescu',
        'prenume' => 'Ion',
        'telefonnev' => '0721234567',
        'telefonapartinator' => '0721234568',
        'email' => 'ion.popescu@example.com',
        'datanastere' => '1988-03-22', // Actualizat pentru a corespunde CNP-ului
        'locnastere' => 'Oradea',
        'judnastere' => 'Bihor',
        'ciseria' => 'AB',
        'cinumar' => '123456',
        'cielib' => 'Primăria Oradea',
        'cidataelib' => '2010-06-15',
        'gdpr' => 1,
        'codpost' => '410001',
        'tipmediuur' => 'Urban',
        'domloc' => 'Oradea',
        'domstr' => 'Calea Republicii',
        'domnr' => '15',
        'dombl' => null,
        'domsc' => 'A',
        'domet' => '2',
        'domap' => '10',
        'sex' => 'Masculin',
        'hgrad' => 'Mediu',
        'hmotiv' => 'Deficiență de vedere',
        'hdur' => 'Permanent',
        'cnp' => '1880322055052', // CNP valid de probă
        'cenr' => 'CH-2024-001',
        'cedata' => '2024-01-10',
        'ceexp' => '2029-01-10',
        'primaria' => 'Primăria Oradea',
        'notamembru' => 'Membru activ, participă la toate activitățile.'
    ];
    
    // Membru 2: Maria Ionescu (fără GDPR și cu documente care expiră pentru testare alertă)
    // Calculăm datele pentru testare: CI expiră în 2 luni, certificat în 3 luni
    $acum = new DateTime();
    $in_2_luni = clone $acum;
    $in_2_luni->modify('+2 months');
    $data_elib_ci = clone $in_2_luni;
    $data_elib_ci->modify('-10 years'); // CI eliberat acum 10 ani, expiră în 2 luni
    
    $in_3_luni = clone $acum;
    $in_3_luni->modify('+3 months');
    
    $membru2 = [
        'dosarnr' => 'DOS-2024-002',
        'dosardata' => '2024-02-20',
        'nume' => 'Ionescu',
        'prenume' => 'Maria',
        'telefonnev' => '0729876543',
        'telefonapartinator' => '0729876544',
        'email' => 'maria.ionescu@example.com',
        'datanastere' => '1988-03-22', // Aceeași dată ca membru 1 (pentru testare)
        'locnastere' => 'Beiuș',
        'judnastere' => 'Bihor',
        'ciseria' => 'BH',
        'cinumar' => '654321',
        'cielib' => 'Primăria Beiuș',
        'cidataelib' => $data_elib_ci->format('Y-m-d'), // Expiră în 2 luni
        'gdpr' => 0, // Fără GDPR pentru testare alertă roșie
        'codpost' => '415100',
        'tipmediuur' => 'Rural',
        'domloc' => 'Beiuș',
        'domstr' => 'Strada Principală',
        'domnr' => '42',
        'dombl' => null,
        'domsc' => null,
        'domet' => null,
        'domap' => null,
        'sex' => 'Feminin',
        'hgrad' => 'Usor',
        'hmotiv' => 'Deficiență de vedere parțială',
        'hdur' => 'Revizuibil',
        'cnp' => '1880322055052', // Același CNP valid ca membru 1 (doar pentru testare)
        'cenr' => 'CH-2024-002',
        'cedata' => '2024-02-15',
        'ceexp' => $in_3_luni->format('Y-m-d'), // Expiră în 3 luni pentru testare alertă galbenă
        'primaria' => 'Primăria Beiuș',
        'notamembru' => 'Membru nou, necesită suport suplimentar. Documente care expiră în curând pentru testare.'
    ];
    
    // Validare CNP pentru membru 1
    $validare1 = valideaza_cnp($membru1['cnp']);
    if (!$validare1['valid']) {
        throw new Exception("CNP invalid pentru membru 1: " . $validare1['error']);
    }
    
    // Validare CNP pentru membru 2
    $validare2 = valideaza_cnp($membru2['cnp']);
    if (!$validare2['valid']) {
        throw new Exception("CNP invalid pentru membru 2: " . $validare2['error']);
    }
    
    // Verifică dacă membrii există deja (folosim nume+prenume pentru că CNP-ul este același)
    $stmt = $pdo->prepare('SELECT id FROM membri WHERE nume = ? AND prenume = ?');
    $stmt->execute([$membru1['nume'], $membru1['prenume']]);
    if ($stmt->fetch()) {
        echo "<div class='mb-4 p-4 bg-amber-100 border-l-4 border-amber-600 text-amber-900 rounded-r'>
                <p><strong>Membru 1 (Ion Popescu)</strong> există deja în baza de date.</p>
              </div>";
    } else {
        // Inserează membru 1
        $sql = 'INSERT INTO membri (
            dosarnr, dosardata, nume, prenume, telefonnev, telefonapartinator,
            email, datanastere, locnastere, judnastere, ciseria, cinumar,
            cielib, cidataelib, gdpr, codpost, tipmediuur, domloc, domstr,
            domnr, dombl, domsc, domet, domap, sex, hgrad, hmotiv,
            hdur, cnp, cenr, cedata, ceexp, primaria, notamembru
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $params1 = [
            $membru1['dosarnr'], $membru1['dosardata'], $membru1['nume'], $membru1['prenume'],
            $membru1['telefonnev'], $membru1['telefonapartinator'], $membru1['email'],
            $membru1['datanastere'], $membru1['locnastere'], $membru1['judnastere'],
            $membru1['ciseria'], $membru1['cinumar'], $membru1['cielib'], $membru1['cidataelib'],
            $membru1['gdpr'], $membru1['codpost'], $membru1['tipmediuur'], $membru1['domloc'],
            $membru1['domstr'], $membru1['domnr'], $membru1['dombl'], $membru1['domsc'],
            $membru1['domet'], $membru1['domap'], $membru1['sex'], $membru1['hgrad'],
            $membru1['hmotiv'], $membru1['hdur'], $membru1['cnp'], $membru1['cenr'],
            $membru1['cedata'], $membru1['ceexp'], $membru1['primaria'], $membru1['notamembru']
        ];
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params1);
            $membru1_id = $pdo->lastInsertId();
            
            log_activitate($pdo, "membri: Creat membru de test (Ion Popescu)", null, $membru1_id);
            $succes[] = "Membru 1 (Ion Popescu) creat cu succes! ID: $membru1_id";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<div class='mb-4 p-4 bg-amber-100 border-l-4 border-amber-600 text-amber-900 rounded-r'>
                        <p><strong>Membru 1 (Ion Popescu)</strong> - CNP duplicat (pentru testare se folosește același CNP). Verificare după nume+prenume.</p>
                      </div>";
            } else {
                throw $e;
            }
        }
    }
    
    // Verifică dacă membru 2 există deja (folosim nume+prenume pentru că CNP-ul este același)
    $stmt = $pdo->prepare('SELECT id FROM membri WHERE nume = ? AND prenume = ?');
    $stmt->execute([$membru2['nume'], $membru2['prenume']]);
    if ($stmt->fetch()) {
        echo "<div class='mb-4 p-4 bg-amber-100 border-l-4 border-amber-600 text-amber-900 rounded-r'>
                <p><strong>Membru 2 (Maria Ionescu)</strong> există deja în baza de date.</p>
              </div>";
    } else {
        // Inserează membru 2
        $params2 = [
            $membru2['dosarnr'], $membru2['dosardata'], $membru2['nume'], $membru2['prenume'],
            $membru2['telefonnev'], $membru2['telefonapartinator'], $membru2['email'],
            $membru2['datanastere'], $membru2['locnastere'], $membru2['judnastere'],
            $membru2['ciseria'], $membru2['cinumar'], $membru2['cielib'], $membru2['cidataelib'],
            $membru2['gdpr'], $membru2['codpost'], $membru2['tipmediuur'], $membru2['domloc'],
            $membru2['domstr'], $membru2['domnr'], $membru2['dombl'], $membru2['domsc'],
            $membru2['domet'], $membru2['domap'], $membru2['sex'], $membru2['hgrad'],
            $membru2['hmotiv'], $membru2['hdur'], $membru2['cnp'], $membru2['cenr'],
            $membru2['cedata'], $membru2['ceexp'], $membru2['primaria'], $membru2['notamembru']
        ];
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params2);
            $membru2_id = $pdo->lastInsertId();
            
            log_activitate($pdo, "membri: Creat membru de test (Maria Ionescu)", null, $membru2_id);
            $succes[] = "Membru 2 (Maria Ionescu) creat cu succes! ID: $membru2_id";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<div class='mb-4 p-4 bg-amber-100 border-l-4 border-amber-600 text-amber-900 rounded-r'>
                        <p><strong>Membru 2 (Maria Ionescu)</strong> - CNP duplicat (pentru testare se folosește același CNP). Verificare după nume+prenume.</p>
                      </div>";
            } else {
                throw $e;
            }
        }
    }
    
    // Afișează rezultate
    if (!empty($succes)) {
        foreach ($succes as $msg) {
            echo "<div class='mb-4 p-4 bg-emerald-100 border-l-4 border-emerald-600 text-emerald-900 rounded-r'>
                    <p>$msg</p>
                  </div>";
        }
    }
    
    // Afișează detalii membri
    echo "<div class='mt-6 p-6 bg-white rounded-lg shadow'>
            <h2 class='text-xl font-semibold mb-4'>Detalii Membri de Test</h2>
            
            <div class='mb-6'>
                <h3 class='font-semibold text-lg mb-2'>Membru 1: Ion Popescu</h3>
                <ul class='list-disc list-inside space-y-1 text-sm'>
                    <li><strong>CNP:</strong> {$membru1['cnp']}</li>
                    <li><strong>Email:</strong> {$membru1['email']}</li>
                    <li><strong>Telefon:</strong> {$membru1['telefonnev']}</li>
                    <li><strong>GDPR:</strong> " . ($membru1['gdpr'] ? 'Da' : 'Nu') . "</li>
                    <li><strong>CI expiră:</strong> " . date('d.m.Y', strtotime($membru1['cidataelib'] . ' +10 years')) . "</li>
                    <li><strong>Certificat expiră:</strong> " . date('d.m.Y', strtotime($membru1['ceexp'])) . "</li>
                </ul>
            </div>
            
            <div class='mb-6'>
                <h3 class='font-semibold text-lg mb-2'>Membru 2: Maria Ionescu</h3>
                <ul class='list-disc list-inside space-y-1 text-sm'>
                    <li><strong>CNP:</strong> {$membru2['cnp']}</li>
                    <li><strong>Email:</strong> {$membru2['email']}</li>
                    <li><strong>Telefon:</strong> {$membru2['telefonnev']}</li>
                    <li><strong>GDPR:</strong> " . ($membru2['gdpr'] ? 'Da' : 'Nu') . " <span class='text-red-600 font-semibold'>(Va apărea alertă roșie)</span></li>
                    <li><strong>CI expiră:</strong> " . date('d.m.Y', strtotime($membru2['cidataelib'] . ' +10 years')) . " <span class='text-amber-600 font-semibold'>(Va apărea alertă galbenă)</span></li>
                    <li><strong>Certificat expiră:</strong> " . date('d.m.Y', strtotime($membru2['ceexp'])) . " <span class='text-amber-600 font-semibold'>(Va apărea alertă galbenă)</span></li>
                </ul>
            </div>
            
            <div class='mt-6'>
                <a href='membri.php' class='inline-block px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg'>
                    Vezi Membri în Sistem
                </a>
            </div>
          </div>";
    
} catch (PDOException $e) {
    $eroare = 'Eroare la inserare în baza de date: ' . $e->getMessage();
} catch (Exception $e) {
    $eroare = $e->getMessage();
}

if (!empty($eroare)) {
    echo "<div class='mb-4 p-4 bg-red-100 border-l-4 border-red-600 text-red-900 rounded-r'>
            <p><strong>Eroare:</strong> $eroare</p>
          </div>";
}

echo "</div>
</body>
</html>";
?>
