<?php
/**
 * Helper pentru generarea documentelor din șabloane Word
 * Taguri în template: [nume], [prenume], etc. (format [tag])
 */

define('UPLOAD_TEMPLATE_DIR', __DIR__ . '/../uploads/documente_template/');
define('UPLOAD_GENERATE_DIR', __DIR__ . '/../uploads/documente_generate/');

/**
 * Asigură existența tabelei pentru documentele generate per membru.
 * Folosită pentru listarea robustă în profil (fără parsare fragilă din log-uri).
 */
function documente_ensure_generated_table(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS documente_generate (
            id INT AUTO_INCREMENT PRIMARY KEY,
            membru_id INT NOT NULL,
            template_id INT NULL,
            template_nume VARCHAR(255) NULL,
            tip_template VARCHAR(10) NULL,
            fisier_pdf VARCHAR(255) NOT NULL,
            fisier_docx VARCHAR(255) NULL,
            nr_inregistrare VARCHAR(50) NULL,
            created_by VARCHAR(255) NULL,
            trimis_email_at DATETIME NULL,
            trimis_whatsapp_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_doc_gen_membru_data (membru_id, created_at),
            INDEX idx_doc_gen_template (template_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        // Nu blocăm fluxurile de generare în caz de eroare schema.
    }
}

/**
 * Curăță un segment de nume fișier pentru a evita caractere invalide.
 */
function documente_filename_slug($value, $fallback = 'document') {
    $value = trim((string)$value);
    $value = preg_replace('/[\\\\\/:*?"<>|]+/u', ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    $value = trim($value);
    if ($value === '') {
        $value = (string)$fallback;
    }
    return mb_substr($value, 0, 120);
}

/**
 * Salvează metadatele unui document generat în tabelul documente_generate.
 * Returnează ID-ul nou creat sau null dacă salvarea eșuează.
 */
function documente_save_generated(PDO $pdo, array $payload): ?int {
    documente_ensure_generated_table($pdo);
    try {
        $stmt = $pdo->prepare("INSERT INTO documente_generate
            (membru_id, template_id, template_nume, tip_template, fisier_pdf, fisier_docx, nr_inregistrare, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)($payload['membru_id'] ?? 0),
            isset($payload['template_id']) ? (int)$payload['template_id'] : null,
            $payload['template_nume'] ?? null,
            $payload['tip_template'] ?? null,
            (string)($payload['fisier_pdf'] ?? ''),
            $payload['fisier_docx'] ?? null,
            $payload['nr_inregistrare'] ?? null,
            $payload['created_by'] ?? ($_SESSION['utilizator'] ?? 'Sistem'),
        ]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Marchează înregistrarea unui document generat ca trimisă pe un canal (email/whatsapp).
 */
function documente_mark_generated_action(PDO $pdo, int $document_generat_id, string $channel): void {
    if ($document_generat_id <= 0) return;
    $channel = strtolower(trim($channel));
    $field = null;
    if ($channel === 'email') $field = 'trimis_email_at';
    if ($channel === 'whatsapp') $field = 'trimis_whatsapp_at';
    if ($field === null) return;
    documente_ensure_generated_table($pdo);
    try {
        $pdo->prepare("UPDATE documente_generate SET {$field} = NOW() WHERE id = ?")->execute([$document_generat_id]);
    } catch (PDOException $e) {
        // Ignorăm fără a bloca fluxul principal.
    }
}

/**
 * Formatează o dată Y-m-d conform DATE_FORMAT. Returnează spațiu dacă e gol.
 * Extrasă din closures duplicate din membru_la_valori_tag / voluntar_la_valori_tag.
 */
function _doc_format_date($val) {
    if (empty($val)) return ' ';
    $d = DateTime::createFromFormat('Y-m-d', $val);
    return $d ? $d->format(DATE_FORMAT) : $val;
}

/**
 * Returnează valoarea ca string sau spațiu dacă e gol/null.
 * Extrasă din closures duplicate din membru_la_valori_tag / voluntar_la_valori_tag.
 */
function _doc_val($val) {
    return (trim((string)$val) === '' || $val === null) ? ' ' : (string)$val;
}

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
 * Returnează HTML-ul implicit pentru antetul documentelor platformei.
 * Acest antet este folosit pentru liste/tabele/print-uri (excluderi: Librărie documente, Generare documente, chitanțe).
 */
function documente_antet_implicit_html($pdo = null) {
    $logo = defined('PLATFORM_LOGO_URL') ? (string)PLATFORM_LOGO_URL : '';
    if ($pdo === null && isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'logo_url' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['valoare'])) {
                $logo = trim((string)$row['valoare']);
            }
        } catch (Exception $e) {
            // fallback pe PLATFORM_LOGO_URL
        }
    }
    $logoEsc = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');

    return
        '<div class="erp-doc-antet-grid">' .
            '<div class="erp-doc-antet-logo">' .
                ($logoEsc !== '' ? '<img src="' . $logoEsc . '" alt="Logo Asociația Nevăzătorilor Bihor">' : '') .
            '</div>' .
            '<div class="erp-doc-antet-text">' .
                '<div class="erp-doc-antet-title">ASOCIAȚIA NEVĂZĂTORILOR<br>DIN ROMÂNIA<br>FILIALA BIHOR</div>' .
                '<div class="erp-doc-antet-subtitle">ORGANIZAȚIE DE UTILITATE PUBLICĂ</div>' .
                '<div class="erp-doc-antet-meta">Conf. H.G. Nr. 1033/03.09.2008</div>' .
                '<div class="erp-doc-antet-meta erp-doc-antet-meta-small">Operator de date cu caracter personal nr. 19677</div>' .
            '</div>' .
        '</div>';
}

/**
 * Sanitizare HTML pentru antet documente.
 * Permite formatare avansată (imagini/linkuri/tabele), elimină scripturi și atribute periculoase.
 */
function documente_antet_sanitize_html($html) {
    $html = trim((string)$html);
    if ($html === '') return '';

    if (!class_exists('DOMDocument')) {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*([\'"]).*?\1#is', '', $html);
        $html = preg_replace('/\s(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '', $html);
        return trim($html);
    }

    $allowedTags = [
        'div', 'p', 'span', 'strong', 'b', 'em', 'i', 'u', 'br',
        'img', 'figure', 'figcaption', 'a',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'colgroup', 'col',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li',
    ];
    $removeWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'meta', 'link'];
    $allowedAttrs = ['class', 'style', 'href', 'target', 'rel', 'src', 'alt', 'title', 'width', 'height', 'colspan', 'rowspan', 'align', 'cellpadding', 'cellspacing', 'border', 'span', 'data-mce-style'];

    $internalErrors = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<!DOCTYPE html><html><body><div id="__erp_doc_antet_root__">' . $html . '</div></body></html>';
    $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($internalErrors);

    $xpath = new DOMXPath($dom);
    $rootNode = $xpath->query('//div[@id="__erp_doc_antet_root__"]')->item(0);
    if (!$rootNode) return '';

    $nodes = [];
    $all = $rootNode->getElementsByTagName('*');
    foreach ($all as $node) { $nodes[] = $node; }

    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement || !$node->parentNode) continue;
        $tag = strtolower($node->tagName);

        if (!in_array($tag, $allowedTags, true)) {
            if (in_array($tag, $removeWithContent, true)) {
                $node->parentNode->removeChild($node);
                continue;
            }
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
            continue;
        }

        $attrNames = [];
        if ($node->hasAttributes()) {
            for ($i = 0; $i < $node->attributes->length; $i++) {
                $attrNames[] = $node->attributes->item($i)->nodeName;
            }
        }

        foreach ($attrNames as $attrName) {
            $attrValue = (string)$node->getAttribute($attrName);
            $nameLower = strtolower($attrName);

            if (strpos($nameLower, 'on') === 0 || !in_array($nameLower, $allowedAttrs, true)) {
                $node->removeAttribute($attrName);
                continue;
            }

            if ($nameLower === 'style' && preg_match('/expression|javascript:|vbscript:|url\s*\(\s*[\'"]?\s*javascript:/i', $attrValue)) {
                $node->removeAttribute($attrName);
                continue;
            }

            if ($nameLower === 'href' || $nameLower === 'src') {
                $url = trim(html_entity_decode($attrValue, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($url === '') {
                    $node->removeAttribute($attrName);
                    continue;
                }
                if (preg_match('/^\s*(javascript:|vbscript:)/i', $url)) {
                    $node->removeAttribute($attrName);
                    continue;
                }
                if (strpos($url, '#') === 0 || strpos($url, '/') === 0) {
                    continue;
                }
                if (stripos($url, 'data:') === 0) {
                    if ($nameLower !== 'src' || !preg_match('#^data:image/(png|jpe?g|gif|webp|svg\+xml);base64,#i', $url)) {
                        $node->removeAttribute($attrName);
                    }
                    continue;
                }
                $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
                if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                    $node->removeAttribute($attrName);
                    continue;
                }
            }
        }

        if ($tag === 'a' && strtolower((string)$node->getAttribute('target')) === '_blank') {
            $rel = trim((string)$node->getAttribute('rel'));
            if (stripos($rel, 'noopener') === false || stripos($rel, 'noreferrer') === false) {
                $node->setAttribute('rel', trim($rel . ' noopener noreferrer'));
            }
        }
    }

    $clean = '';
    foreach ($rootNode->childNodes as $child) {
        $clean .= $dom->saveHTML($child);
    }
    return trim($clean);
}

/**
 * Returnează HTML-ul antetului documente din setări (sau fallback implicit).
 */
function documente_antet_html($pdo = null) {
    if ($pdo === null && isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    }
    if (!$pdo) {
        return documente_antet_implicit_html();
    }
    try {
        $source = 'html';
        $stmtSource = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'documente_antet_source' LIMIT 1");
        $stmtSource->execute();
        $rowSource = $stmtSource->fetch(PDO::FETCH_ASSOC);
        if (!empty($rowSource['valoare']) && trim((string)$rowSource['valoare']) === 'image') {
            $source = 'image';
        }

        if ($source === 'image') {
            $stmtImg = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'documente_antet_image_path' LIMIT 1");
            $stmtImg->execute();
            $rowImg = $stmtImg->fetch(PDO::FETCH_ASSOC);
            $relImg = trim((string)($rowImg['valoare'] ?? ''));
            $absImg = $relImg !== '' ? (__DIR__ . '/../' . ltrim($relImg, '/')) : '';

            if ($relImg !== '' && is_file($absImg)) {
                $stmtAlt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'documente_antet_image_alt' LIMIT 1");
                $stmtAlt->execute();
                $rowAlt = $stmtAlt->fetch(PDO::FETCH_ASSOC);
                $alt = trim((string)($rowAlt['valoare'] ?? ''));
                if ($alt === '') {
                    $alt = 'Antet documente platformă';
                }
                return '<div class="erp-doc-antet-image-wrap"><img class="erp-doc-antet-image" src="/' . htmlspecialchars(ltrim($relImg, '/'), ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"></div>';
            }
        }

        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'documente_antet_html' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $saved = trim((string)($row['valoare'] ?? ''));
        if ($saved === '') {
            return documente_antet_implicit_html($pdo);
        }
        $sanitized = documente_antet_sanitize_html($saved);
        return $sanitized !== '' ? $sanitized : documente_antet_implicit_html($pdo);
    } catch (Exception $e) {
        return documente_antet_implicit_html($pdo);
    }
}

/**
 * CSS reutilizabil pentru antetul documentelor în paginile de print/tabele.
 */
function documente_antet_print_css() {
    return '
.erp-doc-antet { margin: 0 0 12px 0; page-break-inside: avoid; break-inside: avoid; }
.erp-doc-antet-grid { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; border-bottom: 1px solid #8b8b8b; padding-bottom: 8px; }
.erp-doc-antet-logo { flex: 1 1 52%; min-height: 70px; }
.erp-doc-antet-logo img { max-width: 100%; max-height: 88px; object-fit: contain; display: block; }
.erp-doc-antet-text { flex: 1 1 48%; text-align: center; }
.erp-doc-antet-title { font-size: 21px; line-height: 1.08; font-weight: 700; letter-spacing: 0.2px; margin: 0; }
.erp-doc-antet-subtitle { margin-top: 6px; font-size: 30px; line-height: 1.15; letter-spacing: 0.4px; text-transform: uppercase; }
.erp-doc-antet-meta { margin-top: 3px; font-size: 22px; line-height: 1.15; }
.erp-doc-antet-meta-small { font-size: 18px; }
.erp-doc-antet-image-wrap { width: 100%; }
.erp-doc-antet-image { display: block; width: 100%; max-width: 100%; height: auto; object-fit: contain; }
.erp-two-col { display:flex; gap:16px; align-items:flex-start; }
.erp-two-col .erp-col-left { flex:1 1 50%; }
.erp-two-col .erp-col-right { flex:1 1 50%; }
img.erp-img-left { float:left; margin:0 12px 8px 0; max-width:50%; height:auto; }
img.erp-img-right { float:right; margin:0 0 8px 12px; max-width:50%; height:auto; }
img.erp-img-inline { display:inline-block; max-width:100%; height:auto; }
@media print {
  .erp-doc-antet { margin-bottom: 10px; }
  .erp-two-col { display:flex !important; }
}
';
}

/**
 * Randare antet documente gata de inserat în paginile HTML.
 */
function documente_antet_render($pdo = null) {
    $html = documente_antet_html($pdo);
    if (trim($html) === '') return '';
    return '<section class="erp-doc-antet" aria-label="Antet document">' . $html . '</section>';
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
    
    $formatDate = '_doc_format_date';
    $v = '_doc_val';

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
    $formatDate = '_doc_format_date';
    $v = '_doc_val';
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

/**
 * Returnează extensia template-ului (docx/pdf) pe baza numelui de fișier.
 */
function documente_template_extension($template_filename) {
    $ext = strtolower((string)pathinfo((string)$template_filename, PATHINFO_EXTENSION));
    if ($ext === 'pdf') return 'pdf';
    return 'docx';
}

/**
 * Conversie puncte PDF -> mm (unitatea implicită FPDF/FPDI).
 */
function documente_pdf_pt_to_mm($pt) {
    return ((float)$pt) * 25.4 / 72.0;
}

/**
 * Decodează un string literal PDF "(...)"
 * și aplică secvențele escape uzuale.
 */
function documente_pdf_decode_literal_string($literal) {
    $s = (string)$literal;
    if (strlen($s) >= 2 && $s[0] === '(' && substr($s, -1) === ')') {
        $s = substr($s, 1, -1);
    }

    $out = '';
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $ch = $s[$i];
        if ($ch !== '\\') {
            $out .= $ch;
            continue;
        }

        $i++;
        if ($i >= $len) {
            break;
        }
        $esc = $s[$i];
        if ($esc === 'n') { $out .= "\n"; continue; }
        if ($esc === 'r') { $out .= "\r"; continue; }
        if ($esc === 't') { $out .= "\t"; continue; }
        if ($esc === 'b') { $out .= "\x08"; continue; }
        if ($esc === 'f') { $out .= "\x0C"; continue; }
        if ($esc === '(' || $esc === ')' || $esc === '\\') { $out .= $esc; continue; }

        // octal \ddd
        if ($esc >= '0' && $esc <= '7') {
            $oct = $esc;
            for ($k = 0; $k < 2 && ($i + 1) < $len; $k++) {
                $n = $s[$i + 1];
                if ($n >= '0' && $n <= '7') {
                    $oct .= $n;
                    $i++;
                } else {
                    break;
                }
            }
            $out .= chr(octdec($oct));
            continue;
        }

        $out .= $esc;
    }
    return $out;
}

/**
 * Parsează simplificat stream-ul de conținut PDF în tokeni.
 * Suficient pentru a localiza textul-tag și poziția (Tm/Tj/TJ).
 */
function documente_pdf_tokenize_stream($content) {
    $tokens = [];
    $s = (string)$content;
    $len = strlen($s);
    $i = 0;

    $isDelim = static function ($c) {
        return $c === '' || ctype_space($c) || strpos('()<>[]{}/%', $c) !== false;
    };

    while ($i < $len) {
        $ch = $s[$i];

        if (ctype_space($ch)) { $i++; continue; }
        if ($ch === '%') {
            while ($i < $len && $s[$i] !== "\n" && $s[$i] !== "\r") $i++;
            continue;
        }

        // Literal string (...), cu nested paranteze
        if ($ch === '(') {
            $start = $i;
            $depth = 1;
            $i++;
            while ($i < $len && $depth > 0) {
                $c = $s[$i];
                if ($c === '\\') { $i += 2; continue; }
                if ($c === '(') $depth++;
                elseif ($c === ')') $depth--;
                $i++;
            }
            $tokens[] = ['type' => 'string', 'value' => substr($s, $start, $i - $start)];
            continue;
        }

        // Array [...]
        if ($ch === '[') {
            $start = $i;
            $depth = 1;
            $i++;
            while ($i < $len && $depth > 0) {
                $c = $s[$i];
                if ($c === '\\') { $i += 2; continue; }
                if ($c === '(') {
                    // sărim peste string literal din array
                    $strStart = $i;
                    $strDepth = 1;
                    $i++;
                    while ($i < $len && $strDepth > 0) {
                        $cc = $s[$i];
                        if ($cc === '\\') { $i += 2; continue; }
                        if ($cc === '(') $strDepth++;
                        elseif ($cc === ')') $strDepth--;
                        $i++;
                    }
                    continue;
                }
                if ($c === '[') $depth++;
                elseif ($c === ']') $depth--;
                $i++;
            }
            $tokens[] = ['type' => 'array', 'value' => substr($s, $start, $i - $start)];
            continue;
        }

        // Name /F1
        if ($ch === '/') {
            $start = $i;
            $i++;
            while ($i < $len && !$isDelim($s[$i])) $i++;
            $tokens[] = ['type' => 'name', 'value' => substr($s, $start, $i - $start)];
            continue;
        }

        // word/number/operator
        $start = $i;
        while ($i < $len && !$isDelim($s[$i])) $i++;
        $raw = substr($s, $start, $i - $start);
        if ($raw === '') continue;
        if (preg_match('/^[+-]?(?:\d+\.?\d*|\.\d+)$/', $raw)) {
            $tokens[] = ['type' => 'number', 'value' => (float)$raw, 'raw' => $raw];
        } else {
            $tokens[] = ['type' => 'word', 'value' => $raw];
        }
    }
    return $tokens;
}

/**
 * Parsează elementele din array-ul TJ: string-uri + ajustări de kerning.
 */
function documente_pdf_parse_tj_array($arrayRaw) {
    $raw = trim((string)$arrayRaw);
    if (strlen($raw) >= 2 && $raw[0] === '[' && substr($raw, -1) === ']') {
        $raw = substr($raw, 1, -1);
    }
    return documente_pdf_tokenize_stream($raw);
}

/**
 * Extrage obiectele PDF și stream-urile de conținut per pagină.
 * Implementare tolerantă pentru PDF-uri standard (fără object streams avansate).
 */
function documente_pdf_extract_page_streams($pdf_path) {
    $bin = @file_get_contents($pdf_path);
    if ($bin === false || $bin === '') return [];

    $objects = [];
    $ordered = [];
    if (!preg_match_all('/(\d+)\s+(\d+)\s+obj(.*?)endobj/s', $bin, $m, PREG_SET_ORDER)) {
        return [];
    }
    foreach ($m as $match) {
        $objNo = (int)$match[1];
        $body = (string)$match[3];
        $objects[$objNo] = $body;
        $ordered[] = $objNo;
    }

    $extractStream = static function ($objBody) {
        $objBody = (string)$objBody;
        $p = strpos($objBody, 'stream');
        if ($p === false) return ['', ''];
        $dict = substr($objBody, 0, $p);
        $stream = substr($objBody, $p + 6);
        if (strpos($stream, "\r\n") === 0) $stream = substr($stream, 2);
        elseif (strpos($stream, "\n") === 0 || strpos($stream, "\r") === 0) $stream = substr($stream, 1);
        $e = strrpos($stream, 'endstream');
        if ($e === false) return ['', $dict];
        return [substr($stream, 0, $e), $dict];
    };

    $decodeStream = static function ($raw, $dict) {
        $raw = (string)$raw;
        $dict = (string)$dict;
        if ($raw === '') return '';
        if (stripos($dict, '/FlateDecode') === false) return $raw;
        $decoded = @gzuncompress($raw);
        if ($decoded !== false) return $decoded;
        $decoded = @gzinflate($raw);
        if ($decoded !== false) return $decoded;
        if (strlen($raw) > 6) {
            $decoded = @gzinflate(substr($raw, 2));
            if ($decoded !== false) return $decoded;
        }
        return '';
    };

    $pages = [];
    foreach ($ordered as $objNo) {
        $body = $objects[$objNo] ?? '';
        if ($body === '') continue;
        if (!preg_match('/\/Type\s*\/Page\b/', $body) || preg_match('/\/Type\s*\/Pages\b/', $body)) {
            continue;
        }
        $contentRefs = [];
        if (preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/s', $body, $mc)) {
            $contentRefs[] = (int)$mc[1];
        } elseif (preg_match('/\/Contents\s*\[(.*?)\]/s', $body, $mc)) {
            if (preg_match_all('/(\d+)\s+\d+\s+R/', $mc[1], $mr)) {
                foreach ($mr[1] as $r) $contentRefs[] = (int)$r;
            }
        }

        $pageStream = '';
        foreach ($contentRefs as $refNo) {
            if (!isset($objects[$refNo])) continue;
            [$rawStream, $dict] = $extractStream($objects[$refNo]);
            $decoded = $decodeStream($rawStream, $dict);
            if ($decoded !== '') {
                $pageStream .= "\n" . $decoded;
            }
        }
        $pages[] = $pageStream;
    }
    return $pages;
}

/**
 * Localizează pozițiile tagurilor într-un stream PDF (Tm/Tj/TJ).
 * Returnează coordonate în puncte (sistem PDF, origine jos-stânga).
 */
function documente_pdf_detect_tag_positions($stream, array $tagValues, $pageIndex = 1) {
    $tokens = documente_pdf_tokenize_stream((string)$stream);
    $stack = [];
    $placements = [];

    $x = 0.0;
    $y = 0.0;
    $fontSize = 11.0;
    $leading = 13.0;

    $collectFromText = static function ($text, $baseX, $baseY, $fontPt, array $values, $pg) use (&$placements) {
        $decoded = (string)$text;
        if ($decoded === '' || strpos($decoded, '[') === false) return;
        if (!preg_match_all('/\[([a-zA-Z0-9_]+)\]/', $decoded, $mm, PREG_OFFSET_CAPTURE)) return;
        $charWidth = max(2.8, ((float)$fontPt) * 0.50); // estimare pragmatică pentru Helvetica
        foreach ($mm[1] as $idx => $tagItem) {
            $tag = (string)$tagItem[0];
            if (!array_key_exists($tag, $values)) continue;
            $fullTag = (string)$mm[0][$idx][0];
            $offset = (int)$mm[0][$idx][1];
            $prefix = substr($decoded, 0, $offset);
            $prefixLen = strlen($prefix);
            $tagLen = strlen($fullTag);
            $placements[] = [
                'page' => (int)$pg,
                'tag' => $tag,
                'value' => (string)$values[$tag],
                'x_pt' => (float)$baseX + ($prefixLen * $charWidth),
                'y_pt' => (float)$baseY,
                'font_pt' => (float)$fontPt,
                'tag_width_pt' => max(8.0, $tagLen * $charWidth),
            ];
        }
    };

    $advanceText = static function ($text, $fontPt) {
        $charWidth = max(2.8, ((float)$fontPt) * 0.50);
        return strlen((string)$text) * $charWidth;
    };

    foreach ($tokens as $token) {
        if ($token['type'] !== 'word') {
            $stack[] = $token;
            continue;
        }
        $op = (string)$token['value'];

        switch ($op) {
            case 'BT':
                $stack = [];
                break;

            case 'ET':
                $stack = [];
                break;

            case 'Tf':
                if (count($stack) >= 2) {
                    $last = $stack[count($stack) - 1];
                    if (($last['type'] ?? '') === 'number') {
                        $fontSize = max(6.0, (float)$last['value']);
                    }
                }
                $stack = [];
                break;

            case 'TL':
                if (!empty($stack)) {
                    $last = $stack[count($stack) - 1];
                    if (($last['type'] ?? '') === 'number') {
                        $leading = (float)$last['value'];
                    }
                }
                $stack = [];
                break;

            case 'Tm':
                if (count($stack) >= 6) {
                    $x = (float)($stack[count($stack) - 2]['value'] ?? 0.0);
                    $y = (float)($stack[count($stack) - 1]['value'] ?? 0.0);
                }
                $stack = [];
                break;

            case 'Td':
            case 'TD':
                if (count($stack) >= 2) {
                    $x += (float)($stack[count($stack) - 2]['value'] ?? 0.0);
                    $dy = (float)($stack[count($stack) - 1]['value'] ?? 0.0);
                    $y += $dy;
                    if ($op === 'TD') {
                        $leading = -$dy;
                    }
                }
                $stack = [];
                break;

            case 'T*':
                $y -= $leading;
                $stack = [];
                break;

            case 'Tj':
                if (!empty($stack)) {
                    $s = $stack[count($stack) - 1];
                    if (($s['type'] ?? '') === 'string') {
                        $text = documente_pdf_decode_literal_string($s['value']);
                        $collectFromText($text, $x, $y, $fontSize, $tagValues, $pageIndex);
                        $x += $advanceText($text, $fontSize);
                    }
                }
                $stack = [];
                break;

            case 'TJ':
                if (!empty($stack)) {
                    $a = $stack[count($stack) - 1];
                    if (($a['type'] ?? '') === 'array') {
                        $elements = documente_pdf_parse_tj_array($a['value']);
                        foreach ($elements as $el) {
                            if (($el['type'] ?? '') === 'string') {
                                $text = documente_pdf_decode_literal_string($el['value']);
                                $collectFromText($text, $x, $y, $fontSize, $tagValues, $pageIndex);
                                $x += $advanceText($text, $fontSize);
                            } elseif (($el['type'] ?? '') === 'number') {
                                // ajustare kerning (unități text-space)
                                $x += -((float)$el['value']) * ($fontSize / 1000.0);
                            }
                        }
                    }
                }
                $stack = [];
                break;

            case "'":
                $y -= $leading;
                if (!empty($stack)) {
                    $s = $stack[count($stack) - 1];
                    if (($s['type'] ?? '') === 'string') {
                        $text = documente_pdf_decode_literal_string($s['value']);
                        $collectFromText($text, $x, $y, $fontSize, $tagValues, $pageIndex);
                        $x += $advanceText($text, $fontSize);
                    }
                }
                $stack = [];
                break;

            case '"':
                $y -= $leading;
                if (count($stack) >= 3) {
                    $s = $stack[count($stack) - 1];
                    if (($s['type'] ?? '') === 'string') {
                        $text = documente_pdf_decode_literal_string($s['value']);
                        $collectFromText($text, $x, $y, $fontSize, $tagValues, $pageIndex);
                        $x += $advanceText($text, $fontSize);
                    }
                }
                $stack = [];
                break;

            default:
                // păstrăm stack-ul; operatorul curent poate fi parte din alt context.
                break;
        }
    }

    return $placements;
}

/**
 * Generează PDF final pornind din template PDF:
 * păstrează fișierul PDF, detectează tagurile în stream și scrie valorile peste ele.
 */
function documente_genereaza_pdf_din_template_pdf($template_pdf_path, array $membru, array $opts = [], $pdo = null) {
    $template_pdf_path = realpath((string)$template_pdf_path);
    if (!$template_pdf_path || !file_exists($template_pdf_path)) {
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => 'Template-ul PDF nu există.'];
    }

    if (!is_dir(UPLOAD_GENERATE_DIR)) {
        @mkdir(UPLOAD_GENERATE_DIR, 0755, true);
    }

    $formatDate = defined('DATE_FORMAT') ? DATE_FORMAT : 'd.m.Y';
    $tagValues = membru_la_valori_tag($membru);
    $tagValues['datagenerare'] = !empty($opts['include_data_generare']) ? date($formatDate) : ' ';
    foreach (get_toate_numele_tagurilor() as $numeTag) {
        if (!array_key_exists($numeTag, $tagValues)) {
            $tagValues[$numeTag] = ' ';
        }
    }

    $streams = documente_pdf_extract_page_streams($template_pdf_path);
    if (empty($streams)) {
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => 'Nu s-au putut citi stream-urile PDF pentru localizarea tagurilor.'];
    }

    $placementsByPage = [];
    $tagFoundInRawText = false;
    foreach ($streams as $idx => $stream) {
        if (preg_match('/\[[a-zA-Z0-9_]+\]/', (string)$stream)) {
            $tagFoundInRawText = true;
        }
        $p = documente_pdf_detect_tag_positions($stream, $tagValues, $idx + 1);
        if (!empty($p)) {
            $placementsByPage[$idx + 1] = $p;
        }
    }

    if (empty($placementsByPage)) {
        $msg = $tagFoundInRawText
            ? 'Tagurile din template-ul PDF au fost găsite, dar nu s-au putut localiza coordonatele de scriere.'
            : 'Template-ul PDF nu conține taguri detectabile de forma [nume_tag].';
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => $msg];
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => 'Composer autoload lipsă. Rulați: composer install'];
    }
    require_once $autoload;
    if (!class_exists('\setasign\Fpdi\Fpdi')) {
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => 'FPDI nu este disponibil pentru procesarea PDF.'];
    }

    $output_filename = nume_fisier_document_generat($membru, $opts['nume_template'] ?? 'Document PDF', 'pdf');
    $output_path = UPLOAD_GENERATE_DIR . $output_filename;
    if (file_exists($output_path)) {
        $base = pathinfo($output_filename, PATHINFO_FILENAME);
        $output_filename = $base . '_' . date('His') . '.pdf';
        $output_path = UPLOAD_GENERATE_DIR . $output_filename;
    }

    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pageCount = $pdf->setSourceFile($template_pdf_path);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            if ($size && isset($size['width'], $size['height'], $size['orientation'])) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            } else {
                $pdf->AddPage();
            }
            $pdf->useTemplate($tpl);

            $pageHeightMm = (float)($size['height'] ?? 297.0);
            $placements = $placementsByPage[$pageNo] ?? [];
            foreach ($placements as $pl) {
                $value = (string)($pl['value'] ?? '');
                $fontPt = max(7.0, min(16.0, (float)($pl['font_pt'] ?? 11.0)));
                $xMm = documente_pdf_pt_to_mm((float)($pl['x_pt'] ?? 0.0));
                $yMm = $pageHeightMm - documente_pdf_pt_to_mm((float)($pl['y_pt'] ?? 0.0));
                $tagWidthMm = documente_pdf_pt_to_mm((float)($pl['tag_width_pt'] ?? 20.0));
                $fontMm = documente_pdf_pt_to_mm($fontPt);

                // Curățăm vizual tagul original și scriem valoarea în același loc.
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect(max(0.0, $xMm - 0.5), max(0.0, $yMm - ($fontMm * 0.95)), max(2.0, $tagWidthMm + 1.5), max(1.8, $fontMm * 1.35), 'F');

                $enc = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);
                if ($enc === false) $enc = $value;
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Helvetica', '', $fontPt);
                if (trim((string)$enc) !== '') {
                    $pdf->Text($xMm, $yMm, $enc);
                }
            }
        }
        $pdf->Output('F', $output_path);
    } catch (Exception $e) {
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => 'Eroare procesare PDF nativ: ' . $e->getMessage()];
    }

    if (!file_exists($output_path)) {
        return ['success' => false, 'pdf_path' => null, 'pdf_filename' => null, 'docx_path' => null, 'error' => 'Nu s-a putut salva PDF-ul final.'];
    }

    return ['success' => true, 'pdf_path' => $output_path, 'pdf_filename' => $output_filename, 'docx_path' => null, 'error' => null];
}

/**
 * Salvează o înregistrare în istoricul documentelor generate per membru.
 */
function documente_inregistreaza_generare(PDO $pdo, array $data) {
    documente_ensure_generated_table($pdo);
    $membru_id = (int)($data['membru_id'] ?? 0);
    $fisier_pdf = trim((string)($data['fisier_pdf'] ?? ''));
    if ($membru_id <= 0 || $fisier_pdf === '') {
        return null;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO documente_generate
            (membru_id, template_id, template_nume, tip_template, fisier_pdf, fisier_docx, nr_inregistrare, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $membru_id,
            !empty($data['template_id']) ? (int)$data['template_id'] : null,
            $data['template_nume'] ?? null,
            $data['tip_template'] ?? null,
            $fisier_pdf,
            $data['fisier_docx'] ?? null,
            $data['nr_inregistrare'] ?? null,
            $data['created_by'] ?? ($_SESSION['utilizator'] ?? 'Sistem'),
        ]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Marchează acțiuni ulterioare pe documentul generat (email/whatsapp).
 */
function documente_marcheaza_actiune(PDO $pdo, $document_generat_id, $actiune) {
    documente_ensure_generated_table($pdo);
    $id = (int)$document_generat_id;
    $actiune = strtolower(trim((string)$actiune));
    if ($id <= 0) return false;
    $camp = null;
    if ($actiune === 'email') $camp = 'trimis_email_at';
    if ($actiune === 'whatsapp') $camp = 'trimis_whatsapp_at';
    if ($camp === null) return false;
    try {
        $stmt = $pdo->prepare("UPDATE documente_generate SET {$camp} = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Lista documentelor generate pentru profilul membrului.
 */
function documente_lista_generate_membru(PDO $pdo, $membru_id) {
    documente_ensure_generated_table($pdo);
    $membru_id = (int)$membru_id;
    if ($membru_id <= 0) return [];
    try {
        $stmt = $pdo->prepare("SELECT id, template_nume, fisier_pdf, nr_inregistrare, created_by, created_at
                               FROM documente_generate
                               WHERE membru_id = ?
                               ORDER BY created_at DESC, id DESC
                               LIMIT 200");
        $stmt->execute([$membru_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }

    $list = [];
    foreach ($rows as $r) {
        $pdf = (string)($r['fisier_pdf'] ?? '');
        $token = $pdf !== '' ? base64_encode($pdf) : '';
        $list[] = [
            'id' => (int)$r['id'],
            'nume' => trim((string)($r['template_nume'] ?? '')) !== '' ? (string)$r['template_nume'] : $pdf,
            'data' => $r['created_at'] ?? null,
            'utilizator' => $r['created_by'] ?? '',
            'nr_inregistrare' => $r['nr_inregistrare'] ?? null,
            'url' => $token !== '' ? ('/util/descarca-document.php?token=' . rawurlencode($token) . '&type=pdf') : null,
            'fisier_pdf' => $pdf,
        ];
    }
    return $list;
}
