<?php
/**
 * Helper pentru import Excel membri
 * Notă: Pentru funcționalitate completă, instalați PhpSpreadsheet:
 * composer require phpoffice/phpspreadsheet
 * 
 * Această implementare folosește o metodă simplă pentru CSV și Excel basic
 */

/**
 * Citește un fișier Excel/CSV și returnează datele
 * 
 * @param string $file_path Calea către fișier
 * @return array ['headers' => [], 'rows' => []]
 */
function citeste_fisier_excel($file_path) {
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($extension === 'csv') {
        return citeste_csv($file_path);
    } elseif ($extension === 'xlsx' || $extension === 'xls') {
        // Pentru Excel, folosim PhpSpreadsheet dacă este disponibilă
        // (instalată prin Composer: phpoffice/phpspreadsheet).
        // Fallback: dacă clasa nu există sau apare o eroare, NU mai afișăm
        // conținut binar „PK...” ca la CSV, ci întoarcem date goale astfel
        // încât UI-ul să poată afișa un mesaj clar.
        try {
            if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                // Încearcă să încarce autoload-ul dacă nu e deja
                $autoload = __DIR__ . '/../vendor/autoload.php';
                if (file_exists($autoload)) {
                    require_once $autoload;
                }
            }
            if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
                $sheet = $spreadsheet->getActiveSheet();
                // toArray: [rows][columns] cu index numeric
                $rows = $sheet->toArray(null, false, false, false);
                if (empty($rows)) {
                    return ['headers' => [], 'rows' => []];
                }
                $headers = array_map(function ($val) {
                    // Curăță eventualul BOM și spațiile
                    $val = is_string($val) ? preg_replace('/^\xEF\xBB\xBF/', '', $val) : $val;
                    return trim((string) $val);
                }, array_shift($rows));

                // Normalizează rândurile ulterioare la aceeași lungime ca header-ele
                $normalizedRows = [];
                $headerCount = count($headers);
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    // Taie sau completează cu NULL la numărul de coloane
                    $row = array_values($row);
                    if (count($row) > $headerCount) {
                        $row = array_slice($row, 0, $headerCount);
                    } elseif (count($row) < $headerCount) {
                        $row = array_pad($row, $headerCount, null);
                    }
                    $normalizedRows[] = $row;
                }

                return ['headers' => $headers, 'rows' => $normalizedRows];
            }
        } catch (\Throwable $e) {
            // Lăsăm să cadă pe fallback-ul „gol” de mai jos
        }
        // Dacă nu avem PhpSpreadsheet, întoarcem structuri goale astfel încât UI-ul
        // să poată semnala clar că nu s-au putut citi header-ele.
        return ['headers' => [], 'rows' => []];
    }
    
    return ['headers' => [], 'rows' => []];
}

/**
 * Citește un fișier CSV
 */
function citeste_csv($file_path) {
    $headers = [];
    $rows = [];
    
    if (($handle = fopen($file_path, 'r')) !== false) {
        // Auto-detectare delimitator (virgulă vs. punct și virgulă)
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        $delimiter = ',';
        $countComma = substr_count($firstLine, ',');
        $countSemicolon = substr_count($firstLine, ';');
        if ($countSemicolon > $countComma) {
            $delimiter = ';';
        }

        // Procesează header-ul cu str_getcsv pe prima linie citită
        $headers = str_getcsv($firstLine, $delimiter);
        if (!empty($headers)) {
            // Elimină eventualul BOM din primul header
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        }
        
        if ($headers === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }
        
        // Citește rândurile (păstrăm indexele numerice pentru compatibilitate
        // cu importa_membri / importa_contacte care folosesc indexul coloanei)
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = $data;
            }
        }
        
        fclose($handle);
    }
    
    return ['headers' => $headers, 'rows' => $rows];
}

/**
 * Mapează coloanele Excel cu câmpurile bazei de date
 */
function mapeaza_coloane($excel_headers) {
    // Mapare standard între numele coloanelor Excel și câmpurile din baza de date
    $mapare_standard = [
        'Nr. Dosar' => 'dosarnr',
        'Data Dosar' => 'dosardata',
        'Status Dosar' => 'status_dosar',
        'Stare Dosar' => 'status_dosar',
        'Nume' => 'nume',
        'Prenume' => 'prenume',
        'Telefon' => 'telefonnev',
        'Telefon apartinator' => 'telefonapartinator',
        'Email' => 'email',
        'Data Nasterii' => 'datanastere',
        'Loc. Nasterii' => 'locnastere',
        'Jud. Nasterii' => 'judnastere',
        'Seria C.I.' => 'ciseria',
        'Numar C.I.' => 'cinumar',
        'C.I. eliberat de' => 'cielib',
        'C.I. data elib.' => 'cidataelib',
        'C.I. data expirării' => 'cidataexp',
        'Acord GDPR' => 'gdpr',
        'Cod Postal' => 'codpost',
        'Tip mediu' => 'tipmediuur',
        'Localitatea' => 'domloc',
        'Județ Domiciliu' => 'judet_domiciliu',
        'Strada' => 'domstr',
        'Nr.' => 'domnr',
        'Bl.' => 'dombl',
        'Sc.' => 'domsc',
        'Et.' => 'domet',
        'Ap.' => 'domap',
        'Sex' => 'sex',
        'Grad handicap' => 'hgrad',
        'Motiv handicap' => 'hmotiv',
        'Valabilitate certificat' => 'hdur',
        'CNP' => 'cnp',
        'Nr. Certificat Handicap' => 'cenr',
        'Data certificatului' => 'cedata',
        'Data expirarii' => 'ceexp',
        'Primaria de domiciliu' => 'primaria',
        'Nota' => 'notamembru',
        // Câmpuri noi adăugate în tabelul membri
        'Însoțitor' => 'insotitor',
        'Insotitor' => 'insotitor',
        'Nume apartinator' => 'nume_apartinator',
        'Prenume apartinator' => 'prenume_apartinator',
        'Grad Handicap (text)' => 'grad_handicap',
        'Surse venit' => 'surse_venit',
        'Sursa venit' => 'surse_venit',
        'Țara nașterii' => 'tara_nastere',
        'Tara Nasterii' => 'tara_nastere',
        'Diagnostic' => 'diagnostic',
    ];
    
    $mapare = [];
    foreach ($excel_headers as $index => $header) {
        $header_clean = trim($header);
        // Mapare doar pe potrivire exactă (case-insensitive) pentru a evita
        // asocieri greșite de tip „Nr. Crt.” → „Nr.”.
        foreach ($mapare_standard as $excel_name => $db_field) {
            if (mb_strtolower($header_clean) === mb_strtolower($excel_name)) {
                $mapare[$index] = $db_field;
                break;
            }
        }
    }
    
    return $mapare;
}

/**
 * Importă membri din datele mapate
 */
function importa_membri($pdo, $rows, $mapare, $skip_duplicates = true) {
    require_once __DIR__ . '/cnp_validator.php';
    require_once __DIR__ . '/log_helper.php';
    
    $importati = 0;
    $eroare = [];
    $skipati = 0;
    
    foreach ($rows as $row_index => $row) {
        $data = [];
        
        // Mapează datele
        foreach ($mapare as $excel_index => $db_field) {
            if (isset($row[$excel_index])) {
                $value = trim($row[$excel_index]);
                
                // Procesare specială pentru anumite câmpuri
                if ($db_field === 'gdpr') {
                    $data[$db_field] = (strtolower($value) === 'da' || $value === '1' || strtolower($value) === 'yes') ? 1 : 0;
                } elseif (in_array($db_field, ['dosardata', 'datanastere', 'cidataelib', 'cidataexp', 'cedata', 'ceexp'])) {
                    // Procesare date
                    if (!empty($value)) {
                        $date = date_create_from_format('d.m.Y', $value);
                        if (!$date) $date = date_create_from_format('Y-m-d', $value);
                        if (!$date) $date = date_create_from_format('d/m/Y', $value);
                        $data[$db_field] = $date ? $date->format('Y-m-d') : null;
                    } else {
                        $data[$db_field] = null;
                    }
                } elseif ($db_field === 'cinumar') {
                    // Număr C.I.: doar cifre, max 7 caractere
                    $num = preg_replace('/\D/', '', $value);
                    $num = $num !== '' ? substr($num, 0, 7) : '';
                    $data[$db_field] = $num !== '' ? $num : null;
                } else {
                    $data[$db_field] = $value ?: null;
                }
            }
        }
        
        // Validare câmpuri obligatorii
        if (empty($data['nume']) || empty($data['prenume']) || empty($data['cnp'])) {
            $eroare[] = "Rând " . ($row_index + 2) . ": Lipsește nume, prenume sau CNP";
            continue;
        }
        
        // Validare CNP
        $cnp = preg_replace('/\D/', '', $data['cnp']);
        if (strlen($cnp) !== 13) {
            $eroare[] = "Rând " . ($row_index + 2) . ": CNP invalid ({$data['cnp']})";
            continue;
        }
        
        $validare_cnp = valideaza_cnp($cnp);
        if (!$validare_cnp['valid']) {
            $eroare[] = "Rând " . ($row_index + 2) . ": " . $validare_cnp['error'];
            continue;
        }
        
        // Verifică dacă există deja
        if ($skip_duplicates) {
            $stmt = $pdo->prepare('SELECT id FROM membri WHERE cnp = ?');
            $stmt->execute([$cnp]);
            if ($stmt->fetch()) {
                $skipati++;
                continue;
            }
        }
        
        // Setare valori implicite pentru câmpurile noi dacă nu sunt în date
        if (!isset($data['status_dosar'])) {
            $data['status_dosar'] = 'Activ';
        }
        
        // Inserează membru
        try {
            // Construiește query-ul cu toate câmpurile necesare
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($data), '?');
            
            $sql = 'INSERT INTO membri (' . implode(', ', $fields) . ') VALUES (' . 
                   implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $importati++;
            
            log_activitate($pdo, "membri: Importat membru din Excel ({$data['nume']} {$data['prenume']})");
        } catch (PDOException $e) {
            $eroare[] = "Rând " . ($row_index + 2) . ": " . $e->getMessage();
        }
    }
    
    return [
        'importati' => $importati,
        'skipati' => $skipati,
        'eroare' => $eroare
    ];
}
