<?php
/**
 * Date Helper — parsare date cu formate configurabile.
 *
 * Extrasă 1:1 din membri_import_helper.php (membri_import_parse_date).
 * Funcție pură: string in → string|null out, zero side effects.
 */

/**
 * Normalizează o valoare de dată la format Y-m-d.
 *
 * @param string   $value   Valoarea de parsat
 * @param string[] $formats Lista de formate acceptate (ordinea contează)
 * @return string|null Data în format Y-m-d sau null
 */
function parse_date_to_ymd(string $value, array $formats): ?string {
    $value = trim($value);
    if ($value === '') return null;
    foreach ($formats as $fmt) {
        $dt = date_create_from_format($fmt, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }
    return null;
}

/**
 * Lista standard de formate acceptate la introducerea manuala a unei date.
 *
 * Pastrata pentru compatibilitate si pentru importurile care au nevoie de
 * lista explicita de formate.
 *
 * @return string[]
 */
function date_formate_intrare(): array {
    return ['d/m/Y', 'd.m.Y', 'd-m-Y', 'Y-m-d'];
}

/**
 * Parseaza strict o data dupa un format dat (fara rotunjiri de tip 32.01 -> 01.02).
 *
 * @return string|null Data in format Y-m-d sau null
 */
function parse_date_strict(string $value, string $format): ?string {
    $dt = date_create_from_format('!' . $format, $value);
    $err = DateTime::getLastErrors();
    if (!($dt instanceof DateTime)) return null;
    if (is_array($err) && (!empty($err['warning_count']) || !empty($err['error_count']))) return null;
    return $dt->format('Y-m-d');
}

/**
 * Parseaza o data introdusa manual si o normalizeaza la Y-m-d (formatul din baza).
 *
 * Accepta zz/ll/aaaa, zz.ll.aaaa, zz-ll-aaaa, zz/ll/aa, zzllaaaa si aaaa-ll-zz,
 * cu ora optionala in coada. Zilele si lunile inexistente sunt respinse
 * (31/02/2026 => null), fara rotunjire.
 *
 * @param string   $value          Valoarea introdusa de operator
 * @param string[] $formate_extra  Formate suplimentare incercate la final (ex. 'm/d/Y')
 * @return string|null Data in format Y-m-d sau null daca nu se poate parsa
 */
function parse_date_flexibil(string $value, array $formate_extra = []): ?string {
    $v = trim($value);
    if ($v === '') return null;

    $coada = '(?:[ T]\\d{1,2}:\\d{2}(?::\\d{2})?)?';
    $zz = $ll = $aaaa = null;

    if (preg_match('#^(\\d{1,2})[./-](\\d{1,2})[./-](\\d{2}|\\d{4})' . $coada . '$#', $v, $m)) {
        $zz = (int)$m[1];
        $ll = (int)$m[2];
        $aaaa = (int)$m[3];
        if (strlen($m[3]) === 2) {
            $aaaa += ($aaaa <= 69 ? 2000 : 1900);
        }
    } elseif (preg_match('#^(\\d{4})[./-](\\d{1,2})[./-](\\d{1,2})' . $coada . '$#', $v, $m)) {
        $aaaa = (int)$m[1];
        $ll = (int)$m[2];
        $zz = (int)$m[3];
    } elseif (preg_match('#^(\\d{2})(\\d{2})(\\d{4})$#', $v, $m)) {
        $zz = (int)$m[1];
        $ll = (int)$m[2];
        $aaaa = (int)$m[3];
    }

    if ($aaaa !== null && checkdate($ll, $zz, $aaaa)) {
        return sprintf('%04d-%02d-%02d', $aaaa, $ll, $zz);
    }

    foreach ($formate_extra as $fmt) {
        $rez = parse_date_strict($v, $fmt);
        if ($rez !== null) return $rez;
    }

    return null;
}
