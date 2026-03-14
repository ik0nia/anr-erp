<?php
/**
 * Helper pentru gestionarea fișierelor încărcate
 */

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

/**
 * Creează directorul de upload dacă nu există
 */
function init_upload_dir() {
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        mkdir(UPLOAD_DIR . 'ci/', 0755, true);
        mkdir(UPLOAD_DIR . 'ch/', 0755, true);
    }
}

/**
 * Validează și salvează un fișier încărcat
 * 
 * @param array $file Array $_FILES['nume_camp']
 * @param string $tip Tipul fișierului ('ci' sau 'ch')
 * @param int $membru_id ID-ul membrului
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function salveaza_fisier($file, $tip, $membru_id) {
    init_upload_dir();
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'filename' => null, 'error' => null];
        }
        return ['success' => false, 'filename' => null, 'error' => 'Eroare la încărcarea fișierului.'];
    }
    
    // Verifică dimensiunea
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'filename' => null, 'error' => 'Fișierul depășește 5 MB.'];
    }
    
    // Verifică tipul fișierului
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'filename' => null, 'error' => 'Tipul fișierului nu este permis. Permise: JPG, PNG, GIF, PDF.'];
    }
    
    // Generează nume unic
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $tip . '_' . $membru_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $destination = UPLOAD_DIR . $tip . '/' . $filename;
    
    // Mută fișierul
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Eroare la salvarea fișierului.'];
    }
    
    return ['success' => true, 'filename' => $filename, 'error' => null];
}

/**
 * Șterge un fișier
 * 
 * @param string $filename Numele fișierului
 * @param string $tip Tipul fișierului ('ci' sau 'ch')
 * @return bool
 */
function sterge_fisier($filename, $tip) {
    if (empty($filename)) {
        return true;
    }
    
    $filepath = UPLOAD_DIR . $tip . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}
