<?php
/**
 * Export Membri în Excel - CRM ANR Bihor
 * Generează un fișier CSV/Excel cu toți membrii
 */
require_once __DIR__ . '/config.php';

// Setare headers pentru download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="membri_export_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Deschide output stream
$output = fopen('php://output', 'w');

// Adaugă BOM pentru UTF-8 (pentru Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header-uri CSV
$headers = [
    'Nr. Dosar',
    'Data Dosar',
    'Status Dosar',
    'Nume',
    'Prenume',
    'Telefon',
    'Telefon Aparținător',
    'Email',
    'Data Nașterii',
    'Loc. Nașterii',
    'Jud. Nașterii',
    'CNP',
    'Seria C.I.',
    'Număr C.I.',
    'C.I. eliberat de',
    'C.I. data elib.',
    'C.I. data expirării',
    'Sex',
    'Acord GDPR',
    'Cod Postal',
    'Tip Mediu',
    'Localitatea',
    'Județ Domiciliu',
    'Strada',
    'Nr.',
    'Bl.',
    'Sc.',
    'Et.',
    'Ap.',
    'Primăria de Domiciliu',
    'Grad Handicap',
    'Motiv Handicap',
    'Valabilitate Certificat',
    'Nr. Certificat Handicap',
    'Data Certificatului',
    'Data Expirării',
    'Notă'
];

fputcsv($output, $headers);

// Selectare membri
try {
    $stmt = $pdo->query("SELECT 
        dosarnr, dosardata, status_dosar, nume, prenume, telefonnev, telefonapartinator, email,
        datanastere, locnastere, judnastere, cnp, ciseria, cinumar, cielib, cidataelib, cidataexp,
        sex, gdpr, codpost, tipmediuur, domloc, judet_domiciliu, domstr, domnr, dombl, domsc, domet, domap,
        primaria, hgrad, hmotiv, hdur, cenr, cedata, ceexp, notamembru
        FROM membri 
        ORDER BY dosarnr, nume, prenume");
    
    while ($membru = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = [
            $membru['dosarnr'] ?? '',
            $membru['dosardata'] ? date('d.m.Y', strtotime($membru['dosardata'])) : '',
            $membru['status_dosar'] ?? 'Activ',
            $membru['nume'] ?? '',
            $membru['prenume'] ?? '',
            $membru['telefonnev'] ?? '',
            $membru['telefonapartinator'] ?? '',
            $membru['email'] ?? '',
            $membru['datanastere'] ? date('d.m.Y', strtotime($membru['datanastere'])) : '',
            $membru['locnastere'] ?? '',
            $membru['judnastere'] ?? '',
            $membru['cnp'] ?? '',
            $membru['ciseria'] ?? '',
            $membru['cinumar'] ?? '',
            $membru['cielib'] ?? '',
            $membru['cidataelib'] ? date('d.m.Y', strtotime($membru['cidataelib'])) : '',
            $membru['cidataexp'] ? date('d.m.Y', strtotime($membru['cidataexp'])) : '',
            $membru['sex'] ?? '',
            $membru['gdpr'] ? 'Da' : 'Nu',
            $membru['codpost'] ?? '',
            $membru['tipmediuur'] ?? '',
            $membru['domloc'] ?? '',
            $membru['judet_domiciliu'] ?? '',
            $membru['domstr'] ?? '',
            $membru['domnr'] ?? '',
            $membru['dombl'] ?? '',
            $membru['domsc'] ?? '',
            $membru['domet'] ?? '',
            $membru['domap'] ?? '',
            $membru['primaria'] ?? '',
            $membru['hgrad'] ?? '',
            $membru['hmotiv'] ?? '',
            $membru['hdur'] ?? '',
            $membru['cenr'] ?? '',
            $membru['cedata'] ? date('d.m.Y', strtotime($membru['cedata'])) : '',
            $membru['ceexp'] ? date('d.m.Y', strtotime($membru['ceexp'])) : '',
            $membru['notamembru'] ?? ''
        ];
        
        fputcsv($output, $row);
    }
} catch (PDOException $e) {
    // Dacă există eroare, scrie mesajul în CSV
    fputcsv($output, ['EROARE: ' . $e->getMessage()]);
}

fclose($output);
exit;
