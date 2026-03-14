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
