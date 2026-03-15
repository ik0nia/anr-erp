<?php
/**
 * Helper modul Librărie documente - documente model pentru membri
 */

define('LIBRARIE_DOC_MAX_MB', 10);
define('LIBRARIE_DOC_UPLOAD_DIR', 'librarie_documente');
define('LIBRARIE_DOC_EXTENSII', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

/**
 * Asigură tabelul librarie_documente
 */
function librarie_documente_ensure_tables(PDO $pdo): void {
    // No-op: schema is managed by install/schema/migration.php
    return;
}

/**
 * Director upload absolut
 */
function librarie_documente_upload_path(): string {
    return rtrim(__DIR__, '/\\') . '/../' . LIBRARIE_DOC_UPLOAD_DIR . '/';
}

/**
 * Lista documente (opțional filtru căutare)
 */
function librarie_documente_lista(PDO $pdo, string $cautare = ''): array {
    librarie_documente_ensure_tables($pdo);
    if ($cautare !== '') {
        $term = '%' . $cautare . '%';
        $stmt = $pdo->prepare('SELECT id, institutie, nume_document, nume_fisier, ordine, created_at FROM librarie_documente WHERE institutie LIKE ? OR nume_document LIKE ? ORDER BY ordine ASC, institutie, nume_document');
        $stmt->execute([$term, $term]);
    } else {
        $stmt = $pdo->query('SELECT id, institutie, nume_document, nume_fisier, ordine, created_at FROM librarie_documente ORDER BY ordine ASC, institutie, nume_document');
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Un document după id
 */
function librarie_documente_get(PDO $pdo, int $id): ?array {
    librarie_documente_ensure_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM librarie_documente WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Încarcă document în librărie. Returnează id sau 0 la eroare.
 */
function librarie_documente_adauga(PDO $pdo, string $institutie, string $nume_document, array $file): int {
    librarie_documente_ensure_tables($pdo);
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return 0;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, LIBRARIE_DOC_EXTENSII, true)) {
        return 0;
    }
    $max_bytes = LIBRARIE_DOC_MAX_MB * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        return 0;
    }
    $dir = librarie_documente_upload_path();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $nume_fisier = basename($file['name']);
    $cale_rel = LIBRARIE_DOC_UPLOAD_DIR . '/' . 'lib_' . time() . '_' . uniqid() . '.' . $ext;
    $cale_abs = rtrim(__DIR__, '/\\') . '/../' . $cale_rel;
    if (!move_uploaded_file($file['tmp_name'], $cale_abs)) {
        return 0;
    }
    $ord = (int) $pdo->query('SELECT COALESCE(MAX(ordine), 0) + 1 AS next_ord FROM librarie_documente')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO librarie_documente (institutie, nume_document, nume_fisier, cale_fisier, ordine) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$institutie, $nume_document, $nume_fisier, $cale_rel, $ord]);
    return (int) $pdo->lastInsertId();
}

/**
 * Reordonează documentele după lista de id-uri (poziția în array = ordine).
 */
function librarie_documente_reordoneaza(PDO $pdo, array $id_ordine): bool {
    librarie_documente_ensure_tables($pdo);
    $stmt = $pdo->prepare('UPDATE librarie_documente SET ordine = ? WHERE id = ?');
    foreach ($id_ordine as $ord => $id) {
        $id = (int) $id;
        if ($id <= 0) continue;
        $stmt->execute([$ord, $id]);
    }
    return true;
}

/**
 * Actualizează institutie și/sau nume_document
 */
function librarie_documente_actualizeaza(PDO $pdo, int $id, string $institutie, string $nume_document): bool {
    $doc = librarie_documente_get($pdo, $id);
    if (!$doc) {
        return false;
    }
    $stmt = $pdo->prepare('UPDATE librarie_documente SET institutie = ?, nume_document = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$institutie, $nume_document, $id]);
    return $stmt->rowCount() > 0;
}

/**
 * Șterge document (înregistrare + fișier)
 */
function librarie_documente_sterge(PDO $pdo, int $id): bool {
    $doc = librarie_documente_get($pdo, $id);
    if (!$doc) {
        return false;
    }
    $cale_abs = rtrim(__DIR__, '/\\') . '/../' . $doc['cale_fisier'];
    if (is_file($cale_abs)) {
        @unlink($cale_abs);
    }
    $pdo->prepare('DELETE FROM librarie_documente WHERE id = ?')->execute([$id]);
    return true;
}
