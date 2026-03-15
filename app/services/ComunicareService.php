<?php
/**
 * ComunicareService — Business logic pentru modulul Comunicare > Printing.
 *
 * Filtrare membri, generare etichete PDF, generare scrisori PDF, logging batch.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/document_helper.php';
require_once APP_ROOT . '/includes/cotizatii_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

/**
 * Filtrare membri dupa diverse criterii.
 *
 * @param PDO   $pdo
 * @param array $filters  Chei posibile: cotizatie_neachitata, localitate, data_nastere_de_la,
 *                         data_nastere_pana_la, sex, mediu, hgrad, status
 * @return array Lista de membri (array asociativ)
 */
function comunicare_filtreaza_membri(PDO $pdo, array $filters): array {
    $where_parts = [];
    $params = [];

    // Status dosar
    $status = trim($filters['status'] ?? '');
    if ($status !== '' && in_array($status, ['Activ', 'Suspendat', 'Expirat', 'Decedat'])) {
        $where_parts[] = "status_dosar = ?";
        $params[] = $status;
    } else {
        $where_parts[] = "status_dosar = 'Activ'";
    }

    // Localitate
    $localitate = trim($filters['localitate'] ?? '');
    if ($localitate !== '') {
        $where_parts[] = "domloc LIKE ?";
        $params[] = '%' . $localitate . '%';
    }

    // Sex
    $sex = $filters['sex'] ?? '';
    if ($sex !== '' && in_array($sex, ['M', 'F'])) {
        $where_parts[] = "sex = ?";
        $params[] = $sex;
    }

    // Mediu (Urban/Rural)
    $mediu = $filters['mediu'] ?? '';
    if ($mediu !== '' && in_array($mediu, ['Urban', 'Rural'])) {
        $where_parts[] = "tipmediuur = ?";
        $params[] = $mediu;
    }

    // Grad handicap
    $hgrad = trim($filters['hgrad'] ?? '');
    if ($hgrad !== '') {
        $where_parts[] = "hgrad = ?";
        $params[] = $hgrad;
    }

    // Data nastere range
    $dn_de_la = $filters['data_nastere_de_la'] ?? '';
    if ($dn_de_la !== '') {
        $where_parts[] = "datanastere >= ?";
        $params[] = $dn_de_la;
    }
    $dn_pana_la = $filters['data_nastere_pana_la'] ?? '';
    if ($dn_pana_la !== '') {
        $where_parts[] = "datanastere <= ?";
        $params[] = $dn_pana_la;
    }

    // Cotizatie neachitata
    if (!empty($filters['cotizatie_neachitata'])) {
        $an_curent = (int)date('Y');
        $membri_scutiti = [];
        $membri_achitati = [];
        try {
            $membri_scutiti = cotizatii_membri_scutiti_ids($pdo);
        } catch (PDOException $e) {}
        try {
            cotizatii_ensure_tables($pdo);
            $membri_achitati = incasari_membri_cotizatie_achitata_an($pdo, $an_curent);
        } catch (PDOException $e) {}
        $excluded = array_unique(array_merge($membri_scutiti, $membri_achitati));
        if (!empty($excluded)) {
            $placeholders = implode(',', array_fill(0, count($excluded), '?'));
            $where_parts[] = "id NOT IN ($placeholders)";
            foreach ($excluded as $eid) {
                $params[] = (int)$eid;
            }
        }
    }

    $where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM membri $where ORDER BY nume ASC, prenume ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('comunicare_filtreaza_membri eroare: ' . $e->getMessage());
        return [];
    }
}

/**
 * Numara membrii care corespund filtrelor (pentru preview).
 */
function comunicare_count_membri(PDO $pdo, array $filters): int {
    $where_parts = [];
    $params = [];

    $status = trim($filters['status'] ?? '');
    if ($status !== '' && in_array($status, ['Activ', 'Suspendat', 'Expirat', 'Decedat'])) {
        $where_parts[] = "status_dosar = ?";
        $params[] = $status;
    } else {
        $where_parts[] = "status_dosar = 'Activ'";
    }

    $localitate = trim($filters['localitate'] ?? '');
    if ($localitate !== '') {
        $where_parts[] = "domloc LIKE ?";
        $params[] = '%' . $localitate . '%';
    }

    $sex = $filters['sex'] ?? '';
    if ($sex !== '' && in_array($sex, ['M', 'F'])) {
        $where_parts[] = "sex = ?";
        $params[] = $sex;
    }

    $mediu = $filters['mediu'] ?? '';
    if ($mediu !== '' && in_array($mediu, ['Urban', 'Rural'])) {
        $where_parts[] = "tipmediuur = ?";
        $params[] = $mediu;
    }

    $hgrad = trim($filters['hgrad'] ?? '');
    if ($hgrad !== '') {
        $where_parts[] = "hgrad = ?";
        $params[] = $hgrad;
    }

    $dn_de_la = $filters['data_nastere_de_la'] ?? '';
    if ($dn_de_la !== '') {
        $where_parts[] = "datanastere >= ?";
        $params[] = $dn_de_la;
    }
    $dn_pana_la = $filters['data_nastere_pana_la'] ?? '';
    if ($dn_pana_la !== '') {
        $where_parts[] = "datanastere <= ?";
        $params[] = $dn_pana_la;
    }

    if (!empty($filters['cotizatie_neachitata'])) {
        $an_curent = (int)date('Y');
        $excluded = [];
        try {
            $membri_scutiti = cotizatii_membri_scutiti_ids($pdo);
            cotizatii_ensure_tables($pdo);
            $membri_achitati = incasari_membri_cotizatie_achitata_an($pdo, $an_curent);
            $excluded = array_unique(array_merge($membri_scutiti, $membri_achitati));
        } catch (PDOException $e) {}
        if (!empty($excluded)) {
            $placeholders = implode(',', array_fill(0, count($excluded), '?'));
            $where_parts[] = "id NOT IN ($placeholders)";
            foreach ($excluded as $eid) {
                $params[] = (int)$eid;
            }
        }
    }

    $where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM membri $where");
        $stmt->execute($params);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        error_log('comunicare_count_membri eroare: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Genereaza PDF cu etichete pentru fiecare membru.
 * Fiecare pagina = o eticheta cu dimensiunea specificata.
 *
 * @param array $membri     Lista de membri
 * @param float $latime_mm  Latimea etichetei in mm
 * @param float $inaltime_mm Inaltimea etichetei in mm
 * @return array ['success'=>bool, 'path'=>string|null, 'filename'=>string|null, 'error'=>string|null]
 */
function comunicare_genereaza_etichete_pdf(array $membri, float $latime_mm, float $inaltime_mm): array {
    if (empty($membri)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Nu sunt membri pentru generare etichete.'];
    }

    $classFile = APP_ROOT . '/vendor/autoload.php';
    if (!file_exists($classFile)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Lipseste vendor/autoload.php (FPDF).'];
    }
    require_once $classFile;

    // Validare dimensiuni
    if ($latime_mm < 30) $latime_mm = 30;
    if ($latime_mm > 210) $latime_mm = 210;
    if ($inaltime_mm < 15) $inaltime_mm = 15;
    if ($inaltime_mm > 297) $inaltime_mm = 297;

    $output_dir = APP_ROOT . '/uploads/comunicare/';
    if (!is_dir($output_dir)) {
        @mkdir($output_dir, 0755, true);
    }

    $filename = 'etichete_' . date('Y-m-d_His') . '_' . uniqid() . '.pdf';
    $output_path = $output_dir . $filename;

    try {
        $pdf = new \FPDF();
        $pdf->SetAutoPageBreak(false);

        // Calcul font size proportional cu eticheta
        $font_size_nume = min(12, max(7, $inaltime_mm / 5));
        $font_size_adresa = min(10, max(6, $inaltime_mm / 6));
        $margin = 3; // mm

        foreach ($membri as $membru) {
            $pdf->AddPage('P', [$latime_mm, $inaltime_mm]);
            $pdf->SetMargins($margin, $margin, $margin);

            // Nume Prenume
            $pdf->SetFont('Arial', 'B', $font_size_nume);
            $nume_complet = trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? ''));
            $pdf->SetXY($margin, $margin);
            $pdf->Cell($latime_mm - 2 * $margin, $font_size_nume * 0.5, iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $nume_complet), 0, 1);

            // Adresa: str, nr, bl, sc, et, ap
            $pdf->SetFont('Arial', '', $font_size_adresa);
            $linie_h = $font_size_adresa * 0.45;
            $w = $latime_mm - 2 * $margin;

            $adresa_parts = [];
            if (!empty($membru['domstr'])) $adresa_parts[] = 'str. ' . $membru['domstr'];
            if (!empty($membru['domnr']))  $adresa_parts[] = 'nr. ' . $membru['domnr'];
            if (!empty($membru['dombl']))  $adresa_parts[] = 'bl. ' . $membru['dombl'];
            if (!empty($membru['domsc']))  $adresa_parts[] = 'sc. ' . $membru['domsc'];
            if (!empty($membru['domet']))  $adresa_parts[] = 'et. ' . $membru['domet'];
            if (!empty($membru['domap']))  $adresa_parts[] = 'ap. ' . $membru['domap'];
            $adresa_linia1 = implode(', ', $adresa_parts);

            if ($adresa_linia1 !== '') {
                $pdf->Cell($w, $linie_h, iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $adresa_linia1), 0, 1);
            }

            // Cod postal + Localitate
            $loc_parts = [];
            if (!empty($membru['codpost'])) $loc_parts[] = $membru['codpost'];
            if (!empty($membru['domloc']))  $loc_parts[] = $membru['domloc'];
            $linia2 = implode(' ', $loc_parts);
            if ($linia2 !== '') {
                $pdf->Cell($w, $linie_h, iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $linia2), 0, 1);
            }

            // Judet
            if (!empty($membru['judet_domiciliu'])) {
                $pdf->Cell($w, $linie_h, iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', 'jud. ' . $membru['judet_domiciliu']), 0, 1);
            }
        }

        $pdf->Output('F', $output_path);

        if (file_exists($output_path)) {
            return ['success' => true, 'path' => $output_path, 'filename' => $filename, 'error' => null];
        }
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Eroare la salvarea PDF-ului.'];
    } catch (Exception $e) {
        error_log('comunicare_genereaza_etichete_pdf eroare: ' . $e->getMessage());
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Eroare generare PDF: ' . $e->getMessage()];
    }
}

/**
 * Genereaza scrisori PDF din template pentru fiecare membru.
 * Foloseste document_helper.php: genereaza_document_docx + converteste_docx_la_pdf
 * Rezultatul final: un singur PDF cu toate scrisorile concatenate (sau ZIP daca sunt multe).
 *
 * @param PDO   $pdo
 * @param array $membri       Lista de membri
 * @param int   $template_id  ID din documente_template
 * @return array ['success'=>bool, 'path'=>string|null, 'filename'=>string|null, 'error'=>string|null, 'count'=>int]
 */
function comunicare_genereaza_scrisori_pdf(PDO $pdo, array $membri, int $template_id): array {
    if (empty($membri)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Nu sunt membri pentru generare scrisori.', 'count' => 0];
    }

    // Incarca template
    try {
        $stmt = $pdo->prepare('SELECT * FROM documente_template WHERE id = ? AND activ = 1');
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Eroare la incarcarea template-ului.', 'count' => 0];
    }

    if (!$template) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Template-ul selectat nu exista sau nu este activ.', 'count' => 0];
    }

    $template_path = UPLOAD_TEMPLATE_DIR . $template['nume_fisier'];
    if (!file_exists($template_path)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Fisierul template nu exista pe disc.', 'count' => 0];
    }

    $output_dir = APP_ROOT . '/uploads/comunicare/';
    if (!is_dir($output_dir)) {
        @mkdir($output_dir, 0755, true);
    }

    $pdf_files = [];
    $errors = [];
    $count = 0;

    foreach ($membri as $membru) {
        $result = genereaza_document_docx($template_path, $membru, null, [
            'nume_template' => $template['nume_afisare'] ?? 'scrisoare',
            'include_data_generare' => true,
        ]);

        if (!$result['success']) {
            $errors[] = ($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? '') . ': ' . $result['error'];
            continue;
        }

        // Converteste DOCX la PDF
        $pdf_result = converteste_docx_la_pdf($result['path'], $pdo);
        if ($pdf_result['success'] && !empty($pdf_result['path'])) {
            $pdf_files[] = $pdf_result['path'];
            $count++;
        } else {
            $errors[] = ($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? '') . ': Conversie PDF esuata';
        }

        // Curata fisierul DOCX intermediar
        if (!empty($result['path']) && file_exists($result['path'])) {
            @unlink($result['path']);
        }
    }

    if (empty($pdf_files)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Nu s-a putut genera nicio scrisoare. ' . implode('; ', $errors), 'count' => 0];
    }

    // Merge PDFs using FPDI or return single file
    if (count($pdf_files) === 1) {
        // Un singur PDF - il copiem in directorul comunicare
        $filename = 'scrisori_' . date('Y-m-d_His') . '_' . uniqid() . '.pdf';
        $final_path = $output_dir . $filename;
        copy($pdf_files[0], $final_path);
        return ['success' => true, 'path' => $final_path, 'filename' => $filename, 'error' => null, 'count' => $count];
    }

    // Mai multe PDF-uri: merge cu FPDI daca exista, altfel ZIP
    $classFile = APP_ROOT . '/vendor/autoload.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }

    // Incercam merge cu FPDI
    if (class_exists('\setasign\Fpdi\Fpdi')) {
        try {
            $merger = new \setasign\Fpdi\Fpdi();
            foreach ($pdf_files as $pdf_file) {
                $page_count = $merger->setSourceFile($pdf_file);
                for ($i = 1; $i <= $page_count; $i++) {
                    $tpl = $merger->importPage($i);
                    $size = $merger->getTemplateSize($tpl);
                    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $merger->useTemplate($tpl);
                }
            }
            $filename = 'scrisori_' . date('Y-m-d_His') . '_' . uniqid() . '.pdf';
            $final_path = $output_dir . $filename;
            $merger->Output('F', $final_path);

            // Curata PDF-urile individuale
            foreach ($pdf_files as $f) {
                if (file_exists($f)) @unlink($f);
            }

            return ['success' => true, 'path' => $final_path, 'filename' => $filename, 'error' => null, 'count' => $count];
        } catch (Exception $e) {
            // Fallback la ZIP
        }
    }

    // Fallback: ZIP cu toate PDF-urile
    $zip_filename = 'scrisori_' . date('Y-m-d_His') . '_' . uniqid() . '.zip';
    $zip_path = $output_dir . $zip_filename;
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) === true) {
        $idx = 1;
        foreach ($pdf_files as $pdf_file) {
            $zip->addFile($pdf_file, 'scrisoare_' . $idx . '.pdf');
            $idx++;
        }
        $zip->close();

        // Curata PDF-urile individuale
        foreach ($pdf_files as $f) {
            if (file_exists($f)) @unlink($f);
        }

        return ['success' => true, 'path' => $zip_path, 'filename' => $zip_filename, 'error' => null, 'count' => $count];
    }

    return ['success' => true, 'path' => $pdf_files[0], 'filename' => basename($pdf_files[0]), 'error' => 'Nu s-au putut combina PDF-urile. Se returneaza primul fisier.', 'count' => $count];
}

/**
 * Logheaza o actiune batch in istoricul fiecarui membru.
 *
 * @param PDO    $pdo
 * @param array  $membri      Lista de membri
 * @param string $tip         Tipul actiunii (ex: 'etichete', 'scrisoare')
 * @param string $utilizator  Numele utilizatorului
 */
function comunicare_log_batch(PDO $pdo, array $membri, string $tip, string $utilizator): void {
    $actiune_text = ($tip === 'etichete')
        ? 'Comunicare: Eticheta generata (batch printing)'
        : 'Comunicare: Scrisoare generata (batch printing)';

    foreach ($membri as $membru) {
        $membru_id = $membru['id'] ?? null;
        if ($membru_id) {
            $detaliu = $actiune_text . ' / ' . trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? ''));
            log_activitate($pdo, $detaliu, $utilizator, (int)$membru_id);
        }
    }
}

/**
 * Incarca datele necesare pentru filtrele din view.
 *
 * @return array ['localitati'=>[], 'graduri'=>[], 'templates'=>[]]
 */
function comunicare_load_filter_data(PDO $pdo): array {
    $localitati = [];
    $graduri = [];
    $templates = [];

    try {
        $stmt = $pdo->query("SELECT DISTINCT domloc FROM membri WHERE domloc IS NOT NULL AND domloc != '' ORDER BY domloc ASC");
        $localitati = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}

    try {
        $stmt = $pdo->query("SELECT DISTINCT hgrad FROM membri WHERE hgrad IS NOT NULL AND hgrad != '' ORDER BY hgrad ASC");
        $graduri = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}

    try {
        $stmt = $pdo->query("SELECT id, nume_afisare FROM documente_template WHERE activ = 1 ORDER BY nume_afisare ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    return [
        'localitati' => $localitati,
        'graduri' => $graduri,
        'templates' => $templates,
    ];
}
