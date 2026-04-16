<?php
// ============================================================
//  mailer.php — Gmail SMTP pour XAMPP Windows (port 587)
// ============================================================

define('SMTP_USER', 'mahamoudisman830@gmail.com');
define('SMTP_PASS', 'lkbf fmue zwzf evxa');

function sendMail(string $to, string $subject, string $body): bool
{
    $host = 'smtp.gmail.com';
    $port = 587;
    $user = SMTP_USER;
    $pass = str_replace(' ', '', SMTP_PASS);

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ]);

    $socket = @stream_socket_client(
        "tcp://$host:$port",
        $errno, $errstr, 15,
        STREAM_CLIENT_CONNECT, $ctx
    );

    if (!$socket) {
        error_log("[MAILER] Connexion échouée: $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, 15);

    $read = function() use ($socket): string {
        $out = '';
        while ($line = fgets($socket, 512)) {
            $out .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $out;
    };

    $cmd = function(string $c) use ($socket): void {
        fwrite($socket, $c . "\r\n");
    };

    try {
        $r = $read();
        if (!str_starts_with(trim($r), '220')) { fclose($socket); return false; }

        $cmd("EHLO localhost"); $read();

        // STARTTLS — obligatoire sur port 587
        $cmd("STARTTLS");
        $r = $read();
        if (!str_starts_with(trim($r), '220')) { fclose($socket); return false; }

        // Activer le chiffrement TLS
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("[MAILER] TLS échoué");
                fclose($socket); return false;
            }
        }

        $cmd("EHLO localhost"); $read();

        $cmd("AUTH LOGIN"); $read();
        $cmd(base64_encode($user)); $read();
        $cmd(base64_encode($pass));
        $r = $read();

        if (!str_starts_with(trim($r), '235')) {
            error_log("[MAILER] Auth échouée: " . trim($r));
            fclose($socket); return false;
        }

        $cmd("MAIL FROM:<$user>"); $r = $read();
        if (!str_starts_with(trim($r), '250')) { fclose($socket); return false; }

        $cmd("RCPT TO:<$to>"); $r = $read();
        if (!str_starts_with(trim($r), '250')) { fclose($socket); return false; }

        $cmd("DATA"); $r = $read();
        if (!str_starts_with(trim($r), '354')) { fclose($socket); return false; }

        $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $bodyEncoded    = chunk_split(base64_encode($body));

        $msg = "Date: " . date('r') . "\r\n"
             . "From: LUXE.CO <$user>\r\n"
             . "To: <$to>\r\n"
             . "Subject: $subjectEncoded\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n"
             . "\r\n"
             . $bodyEncoded
             . "\r\n.\r\n";

        fwrite($socket, $msg);
        $r = $read();
        if (!str_starts_with(trim($r), '250')) { fclose($socket); return false; }

        $cmd("QUIT");
        fclose($socket);
        return true;

    } catch (\Throwable $e) {
        error_log("[MAILER] Exception: " . $e->getMessage());
        @fclose($socket);
        return false;
    }
}
