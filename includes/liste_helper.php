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

define('LISTA_SOCIALIZARE_ACTIVITATE', 'Socializare si Terapie de grup');
define('LISTA_SOCIALIZARE_DETALII_SUS', "In cadrul Centrului Multifunctional pentru Nevazatori din Oradea, a avut loc intalnirea saptamanala a nevazatorilor, avand ca principala tema - Socializarea, timp in care participantii la activitate au fost implicati activ intr-o terapie de grup suport. Participantii au avut la dispozitie, din partea asociatiei apa, ceai, cafea, etc. Tot in cadrul acestei intalniri, s-au discutat aspecte legate de subiectele de interes si actualitate prinvind drepturile persoanelor cu dizabilitati si aspecte ce tin de activitatile asociatiei, astfel incat sa ne adaptam permanent proiectele si directiile de actiune pentru a raspunde cat mai rapid nevoilor reale ale comunitatii de nevazatori.");

/**
 * Preset pentru lista de prezență "Socializare".
 */
function lista_socializare_defaults(): array {
    return [
        'tip_titlu' => 'Lista prezenta',
        'detalii_activitate' => LISTA_SOCIALIZARE_ACTIVITATE,
        'detalii_suplimentare_sus' => LISTA_SOCIALIZARE_DETALII_SUS,
        'coloane' => ['nr_crt', 'nume_prenume', 'varsta', 'domloc'],
        'semn_stanga_nume' => 'Merca Mihai-Nicolae',
        'semn_stanga_functie' => 'Presedinte',
        'semn_dreapta_nume' => 'Cristina Cociuba',
        'semn_dreapta_functie' => 'Coordonator activitate',
        'creaza_activitate' => 1,
    ];
}

/**
 * Verifică dacă o listă aparține tipului "Socializare".
 */
function lista_este_socializare(?string $detalii_activitate): bool {
    return trim((string)$detalii_activitate) === LISTA_SOCIALIZARE_ACTIVITATE;
}

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
