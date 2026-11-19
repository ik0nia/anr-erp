<?php
/**
 * Trimiterea si partajarea chitantei de incasare: email cu PDF atasat si
 * mesaj pentru partajare (WhatsApp / functia nativa de partajare a telefonului).
 */

require_once __DIR__ . '/incasari_helper.php';
require_once __DIR__ . '/incasari_chitanta_pdf.php';
require_once __DIR__ . '/mailer_functions.php';
require_once __DIR__ . '/log_helper.php';

const INCASARI_EMAIL_SUBIECT_CHITANTA = 'Chitanta dvs. de la Asociatia Nevazatorilor Bihor';

/**
 * Primul element nevid dintr-un rand de baza de date, pentru coloanele date.
 */
function incasari_partaj_prima_valoare(?array $rand, array $coloane): ?string {
    if (!$rand) return null;
    foreach ($coloane as $col) {
        $val = isset($rand[$col]) ? trim((string)$rand[$col]) : '';
        if ($val !== '') return $val;
    }
    return null;
}

/**
 * Datele de contact ale persoanei de pe chitanta (membru sau contact donator).
 *
 * @return array{nume:string,email:?string,telefon:?string,sursa:string}
 */
function incasari_destinatar(PDO $pdo, array $inc): array {
    $rezultat = [
        'nume' => trim(((string)($inc['nume'] ?? '')) . ' ' . ((string)($inc['prenume'] ?? ''))),
        'email' => null,
        'telefon' => null,
        'sursa' => '',
    ];

    $membru_id = (int)($inc['membru_id'] ?? 0);
    if ($membru_id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
            $stmt->execute([$membru_id]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $rezultat['email'] = incasari_partaj_prima_valoare($m, ['email', 'email_personal']);
            $rezultat['telefon'] = incasari_partaj_prima_valoare($m, ['telefonnev', 'telefon', 'telefonapartinator']);
            $rezultat['sursa'] = 'membru';
        } catch (PDOException $e) {
            // fara date de contact
        }
    }

    $contact_id = (int)($inc['contact_id'] ?? 0);
    if ($contact_id > 0 && ($rezultat['email'] === null || $rezultat['telefon'] === null)) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM contacte WHERE id = ?');
            $stmt->execute([$contact_id]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($rezultat['email'] === null) {
                $rezultat['email'] = incasari_partaj_prima_valoare($c, ['email', 'email_personal']);
            }
            if ($rezultat['telefon'] === null) {
                $rezultat['telefon'] = incasari_partaj_prima_valoare($c, ['telefon', 'telefon_personal']);
            }
            if ($rezultat['sursa'] === '') $rezultat['sursa'] = 'contact';
        } catch (PDOException $e) {
            // fara date de contact
        }
    }

    if ($rezultat['email'] !== null && !filter_var($rezultat['email'], FILTER_VALIDATE_EMAIL)) {
        $rezultat['email'] = null;
    }
    if ($rezultat['telefon'] !== null && incasari_telefon_normalizat($rezultat['telefon']) === null) {
        $rezultat['telefon'] = null;
    }

    return $rezultat;
}

/**
 * Normalizeaza un numar de telefon pentru link-urile WhatsApp (format internațional).
 *
 * @return string|null doar cifre, cu prefix de tara, sau null daca nu e un numar utilizabil
 */
function incasari_telefon_normalizat(?string $telefon): ?string {
    $cifre = preg_replace('/\D+/', '', (string)$telefon);
    if ($cifre === '' || strlen($cifre) < 9) {
        return null;
    }
    if (strpos($cifre, '00') === 0) {
        $cifre = substr($cifre, 2);
    }
    if (strlen($cifre) === 10 && strpos($cifre, '0') === 0) {
        // numar national romanesc: 07xxxxxxxx -> 407xxxxxxxx
        $cifre = '4' . $cifre;
    } elseif (strlen($cifre) === 9) {
        // lipseste zeroul national: 7xxxxxxxx -> 407xxxxxxxx
        $cifre = '40' . $cifre;
    }
    return $cifre;
}

/**
 * Mesajul scurt folosit la partajare (WhatsApp sau partajarea nativa a telefonului).
 */
function incasari_mesaj_partajare(PDO $pdo, array $inc): string {
    $d = incasari_chitanta_date($pdo, $inc);
    $suma = number_format((float)$inc['suma'], 2, ',', '.');
    $mesaj = 'Chitanța ' . $d['seria'] . ' nr. ' . $d['nr'] . ' din ' . $d['data']
        . ', în valoare de ' . $suma . ' RON, reprezentând ' . $d['reprezentant'] . '.';
    $mesaj .= ' Vă mulțumim pentru sprijinul acordat Asociației Nevăzătorilor Bihor!';
    return $mesaj;
}

/**
 * Corpul HTML al emailului cu chitanta atasata.
 */
function incasari_email_corp_chitanta(PDO $pdo, array $inc): string {
    $d = incasari_chitanta_date($pdo, $inc);
    $suma = number_format((float)$inc['suma'], 2, ',', '.');
    $salut = trim($d['nume']) !== '' ? 'Bună ziua, ' . htmlspecialchars($d['nume']) . ',' : 'Bună ziua,';

    $corp = '<p>' . $salut . '</p>';
    $corp .= '<p>Vă mulțumim pentru sprijinul acordat Asociației Nevăzătorilor Bihor.</p>';
    $corp .= '<p>Atașat găsiți chitanța <strong>' . htmlspecialchars($d['seria']) . ' nr. ' . htmlspecialchars($d['nr'])
        . '</strong> din ' . htmlspecialchars($d['data']) . ', în valoare de <strong>' . htmlspecialchars($suma)
        . ' RON</strong>, reprezentând ' . htmlspecialchars($d['reprezentant']) . '.</p>';
    $corp .= '<p>Vă rugăm să păstrați acest email ca dovadă a plății. Pentru orice nelămurire ne puteți răspunde la acest mesaj.</p>';
    $corp .= '<p>Cu stimă,<br>Asociația Nevăzătorilor Bihor</p>';
    return $corp;
}

/**
 * Trimite chitanta pe email, cu PDF-ul atasat.
 *
 * @return array{ok:bool,eroare:?string,email:?string}
 */
function incasari_trimite_chitanta_email(PDO $pdo, array $inc, string $email, ?string $format = null): array {
    $email = trim($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'eroare' => 'Adresa de email nu este validă.', 'email' => null];
    }
    if (empty($inc['seria_chitanta'])) {
        return ['ok' => false, 'eroare' => 'Încasarea nu are chitanță emisă, deci nu poate fi trimisă pe email.', 'email' => $email];
    }

    $format = incasari_chitanta_format($pdo, $format);
    $cale = incasari_chitanta_pdf_fisier($pdo, $inc, $format);
    if ($cale === null) {
        return ['ok' => false, 'eroare' => 'Nu s-a putut genera PDF-ul chitanței.', 'email' => $email];
    }

    $nume_fisier = incasari_chitanta_pdf_nume($inc, $format);
    $trimis = sendEmailWithAttachment(
        $pdo,
        $email,
        INCASARI_EMAIL_SUBIECT_CHITANTA,
        incasari_email_corp_chitanta($pdo, $inc),
        $cale,
        $nume_fisier
    );

    @unlink($cale);

    $d = incasari_chitanta_date($pdo, $inc);
    if (!$trimis) {
        log_activitate($pdo, 'Chitanță ' . $d['seria'] . ' nr. ' . $d['nr'] . ': trimiterea pe email către ' . $email . ' a eșuat.');
        return ['ok' => false, 'eroare' => 'Emailul nu a putut fi trimis. Verificați setările de email.', 'email' => $email];
    }

    log_activitate($pdo, 'Chitanță ' . $d['seria'] . ' nr. ' . $d['nr'] . ' trimisă pe email către ' . $email . '.');
    return ['ok' => true, 'eroare' => null, 'email' => $email];
}
