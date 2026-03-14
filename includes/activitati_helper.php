<?php
/**
 * Helper activități: expansiune recurență, zile în română
 */

$ZILE_RO = ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];

function data_cu_ziua_ro($data_str) {
    global $ZILE_RO;
    $d = $data_str instanceof DateTime ? $data_str : new DateTime($data_str);
    $zi = (int)$d->format('N') - 1; // 1=Luni -> 0
    return ($ZILE_RO[$zi] ?? '') . ', ' . $d->format(DATE_FORMAT);
}

/**
 * Expandează activitățile cu recurență în multiple occurrence-uri pentru intervalul dat
 */
function expandeaza_activitati_recurente($activitati, $data_start, $data_end) {
    $rezultat = [];
    foreach ($activitati as $a) {
        if (empty($a['recurenta'])) {
            $d = new DateTime($a['data_ora']);
            if ($d->format('Y-m-d') >= $data_start && $d->format('Y-m-d') <= $data_end) {
                $rezultat[] = $a;
            }
            continue;
        }
        $start = new DateTime($a['data_ora']);
        $end = new DateTime($data_end);
        $interval_start = new DateTime($data_start);
        $rec = $a['recurenta'];
        if ($start > $end) continue;
        $occ = clone $start;
        $max_iter = 500;
        while ($occ <= $end && $max_iter-- > 0) {
            if ($occ >= $interval_start) {
                $copie = $a;
                $copie['data_ora'] = $occ->format('Y-m-d') . ' ' . $start->format('H:i:s');
                $copie['_expandat'] = true;
                $rezultat[] = $copie;
            }
            if ($rec === 'zilnic') {
                $occ->modify('+1 day');
            } elseif ($rec === 'saptamanal') {
                $occ->modify('+1 week');
            } elseif ($rec === 'lunar') {
                $occ->modify('+1 month');
            } elseif ($rec === 'anual') {
                $occ->modify('+1 year');
            } else {
                break;
            }
        }
    }
    usort($rezultat, function($x, $y) {
        return strcmp($x['data_ora'], $y['data_ora']);
    });
    return $rezultat;
}
