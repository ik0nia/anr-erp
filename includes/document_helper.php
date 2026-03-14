<?php
/**
 * Helper pentru generarea documentelor din șabloane Word
 * Taguri în template: [nume], [prenume], etc. (format [tag])
 */

define('UPLOAD_TEMPLATE_DIR', __DIR__ . '/../uploads/documente_template/');
define('UPLOAD_GENERATE_DIR', __DIR__ . '/../uploads/documente_generate/');

/**
 * Returnează calea absolută la fișierul DOCX antet asociație din setări, sau null dacă nu există.
 * Folosit la: liste prezență, documente BPA, documente administrative (nu la generare documente cu date membrului, nici la Încasări).
 *
 * @param PDO|null $pdo
 * @return string|null Cale absolută la antet.docx sau null
 */
function get_antet_asociatie_docx_path($pdo = null) {
    if ($pdo === null && isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    }
    if (!$pdo) {
        return null;
    }
    try {
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'antet_asociatie_docx'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $rel = $row ? trim((string)$row['valoare']) : '';
        if ($rel === '' || !file_exists(__DIR__ . '/../' . $rel)) {
            return null;
        }
        return realpath(__DIR__ . '/../' . $rel) ?: (__DIR__ . '/../' . $rel);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Lista tagurilor disponibile cu descriere
 */
function get_taguri_disponibile() {
    return [
        ['tag' => 'nume', 'desc' => 'Nume membru'],
        ['tag' => 'prenume', 'desc' => 'Prenume membru'],
        ['tag' => 'dosarnr', 'desc' => 'Nr. dosar'],
        ['tag' => 'dosardata', 'desc' => 'Data dosarului (DD.MM.YYYY)'],
        ['tag' => 'cnp', 'desc' => 'CNP'],
        ['tag' => 'datanastere', 'desc' => 'Data nașterii (DD.MM.YYYY)'],
        ['tag' => 'locnastere', 'desc' => 'Locul nașterii'],
        ['tag' => 'judnastere', 'desc' => 'Județul nașterii'],
        ['tag' => 'sex', 'desc' => 'Sex'],
        ['tag' => 'telefonnev', 'desc' => 'Telefon membru'],
        ['tag' => 'telefonapartinator', 'desc' => 'Telefon aparținător'],
        ['tag' => 'email', 'desc' => 'Email'],
        ['tag' => 'ciseria', 'desc' => 'Seria CI'],
        ['tag' => 'cinumar', 'desc' => 'Număr CI'],
        ['tag' => 'cielib', 'desc' => 'CI eliberat de'],
        ['tag' => 'cidataelib', 'desc' => 'Data eliberării CI (DD.MM.YYYY)'],
        ['tag' => 'cidataexp', 'desc' => 'Data expirării CI (DD.MM.YYYY)'],
        ['tag' => 'codpost', 'desc' => 'Cod postal'],
        ['tag' => 'domloc', 'desc' => 'Localitatea domiciliu'],
        ['tag' => 'judet_domiciliu', 'desc' => 'Județ domiciliu'],
        ['tag' => 'domstr', 'desc' => 'Strada'],
        ['tag' => 'domnr', 'desc' => 'Nr. (casă)'],
        ['tag' => 'dombl', 'desc' => 'Bloc'],
        ['tag' => 'domsc', 'desc' => 'Scara'],
        ['tag' => 'domet', 'desc' => 'Etaj'],
        ['tag' => 'domap', 'desc' => 'Apartament'],
        ['tag' => 'adresa_completa', 'desc' => 'Adresă completă formatată'],
        ['tag' => 'primaria', 'desc' => 'Primăria de domiciliu'],
        ['tag' => 'hgrad', 'desc' => 'Grad handicap'],
        ['tag' => 'hmotiv', 'desc' => 'Motiv handicap'],
        ['tag' => 'hdur', 'desc' => 'Valabilitate certificat (Permanent/Revizuibil)'],
        ['tag' => 'cenr', 'desc' => 'Nr. certificat handicap'],
        ['tag' => 'cedata', 'desc' => 'Data certificatului (DD.MM.YYYY)'],
        ['tag' => 'ceexp', 'desc' => 'Data expirării certificat (DD.MM.YYYY)'],
        ['tag' => 'data_curenta', 'desc' => 'Data curentă (DD.MM.YYYY)'],
        ['tag' => 'datagenerare', 'desc' => 'Data generării documentului (DD.MM.YYYY) – se completează dacă bifați opțiunea la generare'],
        ['tag' => 'nrregistratura', 'desc' => 'Nr. înregistrare (ex. contract voluntariat)'],
    ];
}

/**
 * Returnează lista de nume de taguri (pentru completare cu spațiu dacă lipsesc)
 */
function get_toate_numele_tagurilor() {
    $taguri = get_taguri_disponibile();
    return array_column($taguri, 'tag');
}

/**
 * Convertește date membru în array pentru înlocuire taguri
 */
function membru_la_valori_tag($membru) {
    if (empty($membru)) return [];
    
    $formatDate = function($val) {
        if (empty($val)) return ' ';
        $d = DateTime::createFromFormat('Y-m-d', $val);
        return $d ? $d->format(DATE_FORMAT) : $val;
    };
    
    $v = function($val) {
        return (trim((string)$val) === '' || $val === null) ? ' ' : (string)$val;
    };
    
    // Adresă completă
    $adresa = [];
    if (!empty($membru['codpost'])) $adresa[] = 'Cod ' . $membru['codpost'];
    if (!empty($membru['domloc'])) $adresa[] = $membru['domloc'];
    if (!empty($membru['judet_domiciliu'])) $adresa[] = 'jud. ' . $membru['judet_domiciliu'];
    if (!empty($membru['domstr'])) $adresa[] = 'str. ' . $membru['domstr'];
    if (!empty($membru['domnr'])) $adresa[] = 'nr. ' . $membru['domnr'];
    if (!empty($membru['dombl'])) $adresa[] = 'bl. ' . $membru['dombl'];
    if (!empty($membru['domsc'])) $adresa[] = 'sc. ' . $membru['domsc'];
    if (!empty($membru['domet'])) $adresa[] = 'et. ' . $membru['domet'];
    if (!empty($membru['domap'])) $adresa[] = 'ap. ' . $membru['domap'];
    $adresa_completa = implode(', ', $adresa);
    
    return [
        'nume' => $v($membru['nume'] ?? null),
        'prenume' => $v($membru['prenume'] ?? null),
        'dosarnr' => $v($membru['dosarnr'] ?? null),
        'dosardata' => $formatDate($membru['dosardata'] ?? null),
        'cnp' => $v($membru['cnp'] ?? null),
        'datanastere' => $formatDate($membru['datanastere'] ?? null),
        'locnastere' => $v($membru['locnastere'] ?? null),
        'judnastere' => $v($membru['judnastere'] ?? null),
        'sex' => $v($membru['sex'] ?? null),
        'telefonnev' => $v($membru['telefonnev'] ?? null),
        'telefonapartinator' => $v($membru['telefonapartinator'] ?? null),
        'email' => $v($membru['email'] ?? null),
        'ciseria' => $v($membru['ciseria'] ?? null),
        'cinumar' => $v($membru['cinumar'] ?? null),
        'cielib' => $v($membru['cielib'] ?? null),
        'cidataelib' => $formatDate($membru['cidataelib'] ?? null),
        'cidataexp' => $formatDate($membru['cidataexp'] ?? null),
        'codpost' => $v($membru['codpost'] ?? null),
        'domloc' => $v($membru['domloc'] ?? null),
        'judet_domiciliu' => $v($membru['judet_domiciliu'] ?? null),
        'domstr' => $v($membru['domstr'] ?? null),
        'domnr' => $v($membru['domnr'] ?? null),
        'dombl' => $v($membru['dombl'] ?? null),
        'domsc' => $v($membru['domsc'] ?? null),
        'domet' => $v($membru['domet'] ?? null),
        'domap' => $v($membru['domap'] ?? null),
        'adresa_completa' => $adresa_completa ?: ' ',
        'primaria' => $v($membru['primaria'] ?? null),
        'hgrad' => $v($membru['hgrad'] ?? null),
        'hmotiv' => $v($membru['hmotiv'] ?? null),
        'hdur' => $v($membru['hdur'] ?? null),
        'cenr' => $v($membru['cenr'] ?? null),
        'cedata' => $formatDate($membru['cedata'] ?? null),
        'ceexp' => $formatDate($membru['ceexp'] ?? null),
        'data_curenta' => date(DATE_FORMAT),
    ];
}

/**
 * Aplică înlocuiri de taguri pe conținut XML (document.xml sau header/footer).
 * Folosit pentru a remedia taguri neînlocuite de PhpWord (ex. [nume] fragmentat în run-uri).
 */
function docx_aplica_inlocuiri_xml($xml, $valori) {
    if ($xml === false || $xml === '') return $xml;
    $esc = function ($v) {
        return htmlspecialchars($v, ENT_XML1, 'UTF-8');
    };
    foreach ($valori as $tag => $valoare) {
        $xml = str_replace('[' . $tag . ']', $esc($valoare), $xml);
    }
    $part = '(?:<[^>]+>|[^[\]])*';
    foreach ($valori as $tag => $valoare) {
        $chars = preg_split('//u', $tag, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false || count($chars) === 0) continue;
        $pattern = '/\[' . $part . implode($part, array_map(function ($c) {
            return preg_quote($c, '/');
        }, $chars)) . $part . '\]/u';
        $xml = preg_replace($pattern, '<w:r><w:t>' . $esc($valoare) . '</w:t></w:r>', $xml);
    }
    $xml = preg_replace('/\[[a-zA-Z0-9_]+\]/u', ' ', $xml);
    return $xml;
}

/**
 * În DOCX (după PhpWord saveAs), aplică înlocuiri pentru toate tagurile în body + header/footer,
 * inclusiv taguri fragmentate ([nume] etc.), apoi înlocuiește orice [tag] rămas cu spațiu.
 */
function docx_aplica_inlocuiri_complet($docx_path, $valori) {
    if (!file_exists($docx_path) || !is_array($valori)) return false;
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($docx_path, ZipArchive::CREATE) !== true) return false;
    $parti = ['word/document.xml'];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#^word/(header\d*|footer\d*)\.xml$#', $name)) {
            $parti[] = $name;
        }
    }
    foreach ($parti as $part) {
        $xml = $zip->getFromName($part);
        if ($xml !== false) {
            $zip->addFromString($part, docx_aplica_inlocuiri_xml($xml, $valori));
        }
    }
    $zip->close();
    return true;
}

/**
 * Înlocuiește în documentul DOCX orice tag rămas [nume_tag] cu un spațiu (când nu avem $valori).
 */
function docx_inlocuieste_taguri_ramase_cu_spatiu($docx_path) {
    return docx_aplica_inlocuiri_complet($docx_path, []);
}

/**
 * Generează document DOCX din șablon cu datele membrului.
 * Suportă taguri [nume], [prenume], [datagenerare], etc. Câmpurile fără date în profil devin spațiu; tagurile necunoscute la fel.
 * Opțiuni: include_data_generare (bool) – dacă true, [datagenerare] devine data curentă (DD.MM.YYYY), altfel spațiu.
 *
 * @param string $template_path Calea la fișierul .docx șablon
 * @param array $membru Date membru
 * @param string|null $output_filename Nume fișier ieșire (opțional)
 * @param array $opts Opțiuni: include_data_generare => bool
 * @return array ['success'=>bool, 'path'=>string|null, 'filename'=>string|null, 'error'=>string|null]
 */
/**
 * Generează nume de fișier pentru document: [nume] [prenume] - [nume document] [data generării].ext
 * Caractere invalide pentru fișier (\ / : * ? " < > |) sunt înlocuite cu _.
 */
function nume_fisier_document_generat($membru, $nume_template, $ext = 'docx') {
    $formatDate = defined('DATE_FORMAT') ? DATE_FORMAT : 'd.m.Y';
    $nume = trim($membru['nume'] ?? '');
    $prenume = trim($membru['prenume'] ?? '');
    $nume_doc = trim($nume_template ?? 'Document');
    $data_gen = date($formatDate);
    $base = $nume . ' ' . $prenume . ' - ' . $nume_doc . ' ' . $data_gen;
    $base = preg_replace('/[\\\\\/:*?"<>|]/u', '_', $base);
    $base = trim(preg_replace('/\s+/u', ' ', $base));
    if ($base === '') $base = 'document';
    $base = mb_substr($base, 0, 180);
    return $base . '.' . (strtolower($ext) === 'pdf' ? 'pdf' : 'docx');
}

/**
 * Convertește date voluntar + nr_registratura în array pentru înlocuire taguri (inclusiv [nrregistratura]).
 * Folosit la contract voluntariat.
 */
function voluntar_la_valori_tag($voluntar, $nr_registratura = '') {
    if (!is_array($voluntar)) return ['nrregistratura' => (string)$nr_registratura];
    if (!function_exists('contacte_data_nasterii_din_cnp')) {
        require_once __DIR__ . '/contacte_helper.php';
    }
    $formatDate = function($val) {
        if (empty($val)) return ' ';
        $d = DateTime::createFromFormat('Y-m-d', $val);
        return $d ? $d->format(DATE_FORMAT) : $val;
    };
    $v = function($val) {
        return (trim((string)$val) === '' || $val === null) ? ' ' : (string)$val;
    };
    $adresa = [];
    if (!empty($voluntar['codpost'])) $adresa[] = 'Cod ' . $voluntar['codpost'];
    if (!empty($voluntar['domloc'])) $adresa[] = $voluntar['domloc'];
    if (!empty($voluntar['judet_domiciliu'])) $adresa[] = 'jud. ' . $voluntar['judet_domiciliu'];
    if (!empty($voluntar['domstr'])) $adresa[] = 'str. ' . $voluntar['domstr'];
    if (!empty($voluntar['domnr'])) $adresa[] = 'nr. ' . $voluntar['domnr'];
    if (!empty($voluntar['dombl'])) $adresa[] = 'bl. ' . $voluntar['dombl'];
    if (!empty($voluntar['domsc'])) $adresa[] = 'sc. ' . $voluntar['domsc'];
    if (!empty($voluntar['domet'])) $adresa[] = 'et. ' . $voluntar['domet'];
    if (!empty($voluntar['domap'])) $adresa[] = 'ap. ' . $voluntar['domap'];
    $adresa_completa = implode(', ', $adresa);
    $cnp = $voluntar['cnp'] ?? '';
    $datanastere = $cnp && function_exists('contacte_data_nasterii_din_cnp') ? contacte_data_nasterii_din_cnp($cnp) : null;
    return [
        'nume' => $v($voluntar['nume'] ?? null),
        'prenume' => $v($voluntar['prenume'] ?? null),
        'cnp' => $v($cnp),
        'ciseria' => $v($voluntar['seria_ci'] ?? null),
        'cinumar' => $v($voluntar['nr_ci'] ?? null),
        'codpost' => $v($voluntar['codpost'] ?? null),
        'domloc' => $v($voluntar['domloc'] ?? null),
        'judet_domiciliu' => $v($voluntar['judet_domiciliu'] ?? null),
        'domstr' => $v($voluntar['domstr'] ?? null),
        'domnr' => $v($voluntar['domnr'] ?? null),
        'dombl' => $v($voluntar['dombl'] ?? null),
        'domsc' => $v($voluntar['domsc'] ?? null),
        'domet' => $v($voluntar['domet'] ?? null),
        'domap' => $v($voluntar['domap'] ?? null),
        'adresa_completa' => $adresa_completa ?: ' ',
        'telefonnev' => $v($voluntar['telefon'] ?? null),
        'email' => $v($voluntar['email'] ?? null),
        'datanastere' => $datanastere ? $formatDate($datanastere) : ' ',
        'data_curenta' => date(DATE_FORMAT),
        'datagenerare' => date(DATE_FORMAT),
        'nrregistratura' => (string)$nr_registratura,
    ];
}

/**
 * Generează document DOCX din șablon folosind un array de valori (tag => valoare). Pentru contract voluntariat cu [nrregistratura].
 *
 * @param string $template_path Calea la .docx
 * @param array $valori [ 'nume' => '...', 'nrregistratura' => '...', ... ]
 * @param string|null $output_filename Nume fișier sau null pentru auto
 * @return array ['success'=>bool, 'path'=>string|null, 'filename'=>string|null, 'error'=>string|null]
 */
function genereaza_document_din_valori($template_path, array $valori, $output_filename = null) {
    if (!file_exists($template_path)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Șablonul nu există.'];
    }
    foreach (get_toate_numele_tagurilor() as $numeTag) {
        if (!array_key_exists($numeTag, $valori)) {
            $valori[$numeTag] = ' ';
        }
    }
    if (!is_dir(UPLOAD_GENERATE_DIR)) {
        mkdir(UPLOAD_GENERATE_DIR, 0755, true);
    }
    $output_filename = $output_filename ?: 'contract_voluntariat_' . time() . '_' . uniqid() . '.docx';
    $output_path = UPLOAD_GENERATE_DIR . $output_filename;
    $classFile = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($classFile)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Composer autoload lipsă.'];
    }
    require_once $classFile;
    try {
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($template_path);
        $templateProcessor->setMacroOpeningChars('[');
        $templateProcessor->setMacroClosingChars(']');
        foreach ($valori as $tag => $valoare) {
            $templateProcessor->setValue($tag, (string)$valoare);
        }
        $templateProcessor->saveAs($output_path);
        docx_aplica_inlocuiri_complet($output_path, $valori);
        return ['success' => true, 'path' => $output_path, 'filename' => $output_filename, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => $e->getMessage()];
    }
}

/**
 * Generează contract voluntariat: încarcă template-ul după id, completează [nrregistratura] și date voluntar.
 *
 * @param PDO $pdo
 * @param int $template_id ID din documente_template
 * @param array $voluntar Date voluntar (nume, prenume, cnp, seria_ci, nr_ci, domiciliu, telefon, email)
 * @param string $nr_registratura Nr. înregistrare
 * @return array|null ['success'=>bool, 'filename'=>string|null, ...] sau null la eroare
 */
function genereaza_document_voluntar_contract(PDO $pdo, $template_id, array $voluntar, $nr_registratura) {
    $template_id = (int)$template_id;
    if ($template_id <= 0) return null;
    try {
        $stmt = $pdo->prepare('SELECT nume_fisier FROM documente_template WHERE id = ? AND activ = 1');
        $stmt->execute([$template_id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$t || !file_exists(UPLOAD_TEMPLATE_DIR . $t['nume_fisier'])) return null;
    } catch (PDOException $e) {
        return null;
    }
    $template_path = UPLOAD_TEMPLATE_DIR . $t['nume_fisier'];
    $valori = voluntar_la_valori_tag($voluntar, $nr_registratura);
    $nume_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($voluntar['nume'] ?? '') . '_' . trim($voluntar['prenume'] ?? ''));
    $out_name = 'Contract_voluntariat_' . $nume_safe . '_' . $nr_registratura . '.docx';
    return genereaza_document_din_valori($template_path, $valori, $out_name);
}

function genereaza_document_docx($template_path, $membru, $output_filename = null, $opts = []) {
    if (!file_exists($template_path)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Șablonul nu există.'];
    }

    $include_data_generare = !empty($opts['include_data_generare']);
    $formatDate = defined('DATE_FORMAT') ? DATE_FORMAT : 'd.m.Y';

    $valori = membru_la_valori_tag($membru);
    $valori['datagenerare'] = $include_data_generare ? date($formatDate) : ' ';

    // Asigură că toate tagurile cunoscute există în $valori (lipsă = spațiu)
    foreach (get_toate_numele_tagurilor() as $numeTag) {
        if (!array_key_exists($numeTag, $valori)) {
            $valori[$numeTag] = ' ';
        }
    }

    if (!is_dir(UPLOAD_GENERATE_DIR)) {
        mkdir(UPLOAD_GENERATE_DIR, 0755, true);
    }

    if ($output_filename === null && !empty($opts['nume_template'])) {
        $output_filename = nume_fisier_document_generat($membru, $opts['nume_template'], 'docx');
        $output_path = UPLOAD_GENERATE_DIR . $output_filename;
        if (file_exists($output_path)) {
            $base = pathinfo($output_filename, PATHINFO_FILENAME);
            $output_filename = $base . '_' . date('His') . '.docx';
            $output_path = UPLOAD_GENERATE_DIR . $output_filename;
        }
    } else {
        $output_filename = $output_filename ?: 'doc_' . ($membru['id'] ?? '0') . '_' . time() . '_' . uniqid() . '.docx';
        $output_path = UPLOAD_GENERATE_DIR . $output_filename;
    }

    $classFile = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($template_path);
            $templateProcessor->setMacroOpeningChars('[');
            $templateProcessor->setMacroClosingChars(']');
            foreach ($valori as $tag => $valoare) {
                $templateProcessor->setValue($tag, $valoare);
            }
            $templateProcessor->saveAs($output_path);
            docx_aplica_inlocuiri_complet($output_path, $valori);
            return ['success' => true, 'path' => $output_path, 'filename' => $output_filename, 'error' => null];
        } catch (Exception $e) {
            return ['success' => false, 'path' => null, 'filename' => null, 'error' => $e->getMessage()];
        }
    }

    // Fallback: substituție directă în document.xml (necesită extensia Zip)
    if (!class_exists('ZipArchive')) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Extensia PHP Zip nu este activă. Contactați administratorul.'];
    }
    try {
        $zip = new ZipArchive();
        if ($zip->open($template_path) !== true) {
            return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Nu s-a putut deschide șablonul.'];
        }
        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Format DOCX invalid.'];
        }
        $esc = function ($v) {
            return htmlspecialchars($v, ENT_XML1, 'UTF-8');
        };
        foreach ($valori as $tag => $valoare) {
            $xml = str_replace('[' . $tag . ']', $esc($valoare), $xml);
        }
        // Înlocuire taguri fragmentate în mai multe run-uri Word (ex: [nu</w:t></w:r><w:r><w:t>me])
        $part = '(?:<[^>]+>|[^[\]])*';
        foreach ($valori as $tag => $valoare) {
            $chars = preg_split('//u', $tag, -1, PREG_SPLIT_NO_EMPTY);
            if ($chars === false || count($chars) === 0) continue;
            $pattern = '/\[' . $part . implode($part, array_map(function ($c) {
                return preg_quote($c, '/');
            }, $chars)) . $part . '\]/u';
            $replacement = '<w:r><w:t>' . $esc($valoare) . '</w:t></w:r>';
            $xml = preg_replace($pattern, $replacement, $xml);
        }
        // Taguri rămase (necunoscute) → spațiu
        $xml = preg_replace('/\[[a-zA-Z0-9_]+\]/u', ' ', $xml);
        $zipOut = new ZipArchive();
        if ($zipOut->open($output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $zip->close();
            return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Nu s-a putut crea fișierul.'];
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $content = ($name === 'word/document.xml') ? $xml : $zip->getFromIndex($i);
            $zipOut->addFromString($name, $content);
        }
        $zipOut->close();
        $zip->close();
        return ['success' => true, 'path' => $output_path, 'filename' => $output_filename, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => $e->getMessage()];
    }
}

/**
 * Extrage text din XML OOXML (header/footer); elimină taguri și decodează entități.
 */
function docx_extrage_text_din_xml($xml) {
    if ($xml === false || $xml === '') return '';
    $text = preg_replace('/<[^>]+>/u', ' ', $xml);
    $text = html_entity_decode($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}

/**
 * Extrage conținutul de antet și subsol din DOCX (pentru a le adăuga pe PDF).
 */
function docx_extrage_antet_subsol($docx_path) {
    $headerText = '';
    $footerText = '';
    if (!class_exists('ZipArchive')) return ['header' => $headerText, 'footer' => $footerText];
    $zip = new ZipArchive();
    if ($zip->open($docx_path) !== true) return ['header' => $headerText, 'footer' => $footerText];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === 'word/header1.xml' || preg_match('#^word/header\d+\.xml$#', $name)) {
            if ($headerText === '') $headerText = docx_extrage_text_din_xml($zip->getFromName($name));
        }
        if ($name === 'word/footer1.xml' || preg_match('#^word/footer\d+\.xml$#', $name)) {
            if ($footerText === '') $footerText = docx_extrage_text_din_xml($zip->getFromName($name));
        }
    }
    $zip->close();
    return ['header' => $headerText, 'footer' => $footerText];
}

/**
 * Suprapune antet și subsol pe un PDF existent (generat de PhpWord fără antet/subsol).
 */
function pdf_adauga_antet_subsol($pdf_path, $headerText, $footerText) {
    if ((string)$headerText === '' && (string)$footerText === '') return true;
    $tempPath = '';
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) return false;
    require_once $autoload;
    if (!class_exists('setasign\Fpdi\Fpdi')) return false;
    try {
        $h = (string)$headerText;
        $f = (string)$footerText;
        $pdf = new class extends \setasign\Fpdi\Fpdi {
            public $headerText = '';
            public $footerText = '';
            public function Header() {
                if ($this->headerText !== '') {
                    $this->SetFont('Helvetica', '', 8);
                    $this->SetY(8);
                    $this->Cell(0, 6, $this->headerText, 0, 1, 'C');
                }
            }
            public function Footer() {
                if ($this->footerText !== '') {
                    $this->SetY(-18);
                    $this->SetFont('Helvetica', '', 8);
                    $this->Cell(0, 6, $this->footerText, 0, 0, 'C');
                }
            }
        };
        $pdf->headerText = $h;
        $pdf->footerText = $f;
        $pageCount = $pdf->setSourceFile($pdf_path);
        for ($n = 1; $n <= $pageCount; $n++) {
            $tpl = $pdf->importPage($n);
            $size = $pdf->getTemplateSize($tpl);
            if ($size && isset($size['width'], $size['height'], $size['orientation'])) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            } else {
                $pdf->AddPage();
            }
            $pdf->useTemplate($tpl);
        }
        $tempPath = $pdf_path . '.tmp.' . uniqid();
        $pdf->Output('F', $tempPath);
        if (file_exists($tempPath)) {
            @unlink($pdf_path);
            @rename($tempPath, $pdf_path);
            return true;
        }
    } catch (Exception $e) {
        if ($tempPath !== '' && file_exists($tempPath)) @unlink($tempPath);
        return false;
    }
    return false;
}

/**
 * Convertește DOCX în PDF folosind PhpWord + mPDF (fără LibreOffice).
 * Extrage antet/subsol din DOCX și îi adaugă pe PDF (PhpWord nu le păstrează la export).
 */
function docx_la_pdf_phpword_mpdf($docx_path) {
    $docx_path = realpath($docx_path);
    if (!$docx_path || !file_exists($docx_path)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Fișierul DOCX nu există.'];
    }
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Composer autoload lipsă. Rulați: composer install'];
    }
    $mpdfPath = __DIR__ . '/../vendor/mpdf/mpdf';
    if (!is_dir($mpdfPath)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'mPDF lipsă. Rulați: composer require mpdf/mpdf'];
    }
    try {
        require_once $autoload;
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($docx_path);
        \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_MPDF);
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($mpdfPath);
        $pdf_path = preg_replace('/\.docx$/i', '.pdf', $docx_path);
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        $writer->save($pdf_path);
        if (file_exists($pdf_path)) {
            return ['success' => true, 'path' => $pdf_path, 'filename' => basename($pdf_path), 'error' => null];
        }
    } catch (Exception $e) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => $e->getMessage()];
    }
    return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Conversia PDF a eșuat.'];
}

/**
 * Convertește DOCX în PDF: încearcă LibreOffice (dacă este configurat), apoi PhpWord + mPDF.
 */
function converteste_docx_la_pdf($docx_path, $pdo = null) {
    $docx_path = realpath($docx_path);
    if (!$docx_path || !file_exists($docx_path)) {
        return ['success' => false, 'path' => null, 'filename' => null, 'error' => 'Fișierul DOCX nu există.'];
    }
    $pdf_path = preg_replace('/\.docx$/i', '.pdf', $docx_path);
    $filename = basename($pdf_path);

    if ($pdo === null && isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'cale_libreoffice'");
            $stmt->execute();
            $row = $stmt->fetch();
            $libreoffice = $row ? trim($row['valoare']) : '';
        } catch (Exception $e) {
            $libreoffice = '';
        }
        if (!empty($libreoffice)) {
            $output_dir = dirname($docx_path);
            $cmd = sprintf('"%s" --headless --convert-to pdf --outdir "%s" "%s" 2>&1', $libreoffice, $output_dir, $docx_path);
            exec($cmd, $output, $return_var);
            if (file_exists($pdf_path)) {
                return ['success' => true, 'path' => $pdf_path, 'filename' => $filename, 'error' => null];
            }
        }
    }

    return docx_la_pdf_phpword_mpdf($docx_path);
}
