<?php
/**
 * Validator CNP - Cod Numeric Personal
 * Validează CNP-ul conform algoritmului oficial românesc
 */

/**
 * Validează un CNP
 * 
 * @param string $cnp CNP-ul de validat (doar cifre, 13 caractere)
 * @return array ['valid' => bool, 'error' => string|null]
 */
function valideaza_cnp($cnp) {
    // Elimină spații și caractere non-numerice
    $cnp = preg_replace('/\D/', '', $cnp);
    
    // Verifică lungimea
    if (strlen($cnp) !== 13) {
        return ['valid' => false, 'error' => 'CNP-ul trebuie să conțină exact 13 cifre.'];
    }
    
    // Verifică că toate caracterele sunt cifre
    if (!ctype_digit($cnp)) {
        return ['valid' => false, 'error' => 'CNP-ul trebuie să conțină doar cifre.'];
    }
    
    // Extrage prima cifră (sex și secol)
    $s = (int)$cnp[0];
    if ($s < 1 || $s > 9) {
        return ['valid' => false, 'error' => 'Prima cifră a CNP-ului nu este validă.'];
    }
    
    // Extrage data nașterii
    $aa = (int)substr($cnp, 1, 2);
    $ll = (int)substr($cnp, 3, 2);
    $zz = (int)substr($cnp, 5, 2);
    
    // Determină secolul
    $secol = 0;
    if ($s == 1 || $s == 2) $secol = 1900;
    elseif ($s == 3 || $s == 4) $secol = 1800;
    elseif ($s == 5 || $s == 6) $secol = 2000;
    elseif ($s == 7 || $s == 8 || $s == 9) $secol = 2000;
    
    $an = $secol + $aa;
    
    // Verifică validitatea datei
    if (!checkdate($ll, $zz, $an)) {
        return ['valid' => false, 'error' => 'Data de naștere din CNP nu este validă.'];
    }
    
    // Verifică județul (cifrele 7-8)
    $jj = (int)substr($cnp, 7, 2);
    if ($jj < 1 || $jj > 52) {
        return ['valid' => false, 'error' => 'Codul județului din CNP nu este valid.'];
    }
    
    // Verifică cifra de control
    $cifra_control = (int)$cnp[12];
    $coeficienti = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
    $suma = 0;
    
    for ($i = 0; $i < 12; $i++) {
        $suma += (int)$cnp[$i] * $coeficienti[$i];
    }
    
    $rest = $suma % 11;
    $cifra_control_calculata = ($rest < 10) ? $rest : 1;
    
    if ($cifra_control !== $cifra_control_calculata) {
        return ['valid' => false, 'error' => 'Cifra de control a CNP-ului nu este validă.'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Extrage informații din CNP
 * 
 * @param string $cnp CNP-ul validat
 * @return array Informații despre CNP
 */
function extrage_info_cnp($cnp) {
    $cnp = preg_replace('/\D/', '', $cnp);
    
    if (strlen($cnp) !== 13) {
        return null;
    }
    
    $s = (int)$cnp[0];
    $aa = (int)substr($cnp, 1, 2);
    $ll = (int)substr($cnp, 3, 2);
    $zz = (int)substr($cnp, 5, 2);
    
    // Determină secolul și anul
    $secol = 0;
    if ($s == 1 || $s == 2) $secol = 1900;
    elseif ($s == 3 || $s == 4) $secol = 1800;
    elseif ($s == 5 || $s == 6) $secol = 2000;
    elseif ($s == 7 || $s == 8 || $s == 9) $secol = 2000;
    
    $an = $secol + $aa;
    
    // Determină sexul
    $sex = ($s % 2 == 1) ? 'Masculin' : 'Feminin';
    
    // Determină județul
    $jj = (int)substr($cnp, 7, 2);
    
    return [
        'sex' => $sex,
        'data_nastere' => sprintf('%04d-%02d-%02d', $an, $ll, $zz),
        'judet' => $jj
    ];
}
