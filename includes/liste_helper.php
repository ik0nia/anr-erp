<?php
/**
 * Helper pentru liste de prezență
 */

define('LISTE_COLOANE', [
    'nr_crt' => 'Nr. crt.',
    'nume_prenume' => 'Nume și prenume',
    'datanastere' => 'Data nașterii',
    'varsta' => 'Vârstă',
    'ci' => 'Seria și nr. buletin',
    'domloc' => 'Localitatea domiciliu',
    'semnatura' => 'Semnătură',
]);

function calculeaza_varsta($data_nastere) {
    if (empty($data_nastere)) return null;
    $birth = new DateTime($data_nastere);
    return (new DateTime())->diff($birth)->y;
}

/**
 * Calculeaza varsta pentru afisare in liste de aniversari.
 * Returneaza '-' in loc de null cand data lipseste.
 */
function calculeaza_varsta_aniversari($data_nastere) {
    if (empty($data_nastere)) return '-';
    $birth = new DateTime($data_nastere);
    return (new DateTime())->diff($birth)->y;
}
