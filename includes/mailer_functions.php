<?php
/**
 * EMAILCRM – funcții pentru trimitere emailuri automate
 * Folosește setările din settings_email; PHPMailer dacă e disponibil și SMTP configurat, altfel mail().
 */

/**
 * Asigură existența tabelei settings_email și returnează setările (un singur rând, id=1).
 */
function mailer_ensure_table(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings_email (
        id INT AUTO_INCREMENT PRIMARY KEY,
        smtp_host VARCHAR(255) DEFAULT NULL,
        smtp_port INT DEFAULT 587,
        smtp_user VARCHAR(255) DEFAULT NULL,
        smtp_pass VARCHAR(255) DEFAULT NULL,
        smtp_encryption VARCHAR(20) DEFAULT 'tls',
        from_name VARCHAR(255) DEFAULT NULL,
        from_email VARCHAR(255) DEFAULT NULL,
        email_signature TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $stmt = $pdo->query("SELECT COUNT(*) FROM settings_email");
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO settings_email (id) VALUES (1)");
    }
}

/**
 * Încarcă setările email din baza de date (rândul id=1).
 */
function mailer_get_settings(PDO $pdo): array {
    mailer_ensure_table($pdo);
    $stmt = $pdo->query("SELECT smtp_host, smtp_port, smtp_user, smtp_pass, smtp_encryption, from_name, from_email, email_signature FROM settings_email WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [
            'smtp_host' => '', 'smtp_port' => 587, 'smtp_user' => '', 'smtp_pass' => '',
            'smtp_encryption' => 'tls', 'from_name' => '', 'from_email' => '', 'email_signature' => '',
        ];
    }
    return [
        'smtp_host' => (string)($row['smtp_host'] ?? ''),
        'smtp_port' => (int)($row['smtp_port'] ?? 587),
        'smtp_user' => (string)($row['smtp_user'] ?? ''),
        'smtp_pass' => (string)($row['smtp_pass'] ?? ''),
        'smtp_encryption' => (string)($row['smtp_encryption'] ?? 'tls'),
        'from_name' => (string)($row['from_name'] ?? ''),
        'from_email' => (string)($row['from_email'] ?? ''),
        'email_signature' => (string)($row['email_signature'] ?? ''),
    ];
}

/**
 * Trimite email automat: concatenează $body cu semnătura din setări și trimite.
 * Folosește PHPMailer cu SMTP dacă e disponibil și smtp_host e setat, altfel mail().
 *
 * @param PDO $pdo Conexiune la baza de date
 * @param string $to Adresa destinatar
 * @param string $subject Subiect
 * @param string $body Corpul mesajului (fără semnătură; se adaugă automat)
 * @return bool true la succes, false la eroare
 */
function sendAutomatedEmail(PDO $pdo, string $to, string $subject, string $body): bool {
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $settings = mailer_get_settings($pdo);
    $signature = trim($settings['email_signature'] ?? '');
    $fullBodyPlain = $body;
    if ($signature !== '') {
        $fullBodyPlain .= "\n\n" . $signature;
    }
    $isHtml = (strpos($signature, '<') !== false);
    $fullBody = $fullBodyPlain;
    if ($isHtml) {
        $bodyEscaped = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $fullBody = '<p style="white-space:pre-wrap;">' . nl2br($bodyEscaped) . '</p>';
        if ($signature !== '') {
            $fullBody .= $signature;
        }
    }
    $fromName = trim($settings['from_name'] ?? '');
    $fromEmail = trim($settings['from_email'] ?? '');
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    $useSmtp = trim($settings['smtp_host'] ?? '') !== '';
    $phpmailerAvailable = false;
    if ($useSmtp) {
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailerAvailable = true;
        } elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $phpmailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');
        }
    }

    if ($useSmtp && $phpmailerAvailable) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->Port = $settings['smtp_port'];
            $mail->SMTPAuth = ($settings['smtp_user'] !== '');
            if ($mail->SMTPAuth) {
                $mail->Username = $settings['smtp_user'];
                $mail->Password = $settings['smtp_pass'];
            }
            $enc = strtolower($settings['smtp_encryption'] ?? '');
            if ($enc === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->setFrom($fromEmail, $fromName ?: '');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $fullBody;
            $mail->isHTML($isHtml);
            if ($isHtml) {
                $mail->AltBody = $fullBodyPlain;
            }
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('sendAutomatedEmail PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: " . ($fromName ? '"' . str_replace('"', "'", $fromName) . '" <' . $fromEmail . '>' : $fromEmail) . "\r\n";
    if ($isHtml) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return (bool)@mail($to, $encodedSubject, $fullBody, $headers);
}
