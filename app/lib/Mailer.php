<?php
declare(strict_types=1);

/**
 * Envoi d'emails : file d'attente en base (traitée par le cron) + envoi direct.
 * SMTP natif (SSL/STARTTLS + AUTH LOGIN) — compatible SMTP Hostinger — ou mail().
 */
final class Mailer
{
    public static function queue(string $to, string $subject, string $html): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return;
        db()->insert('email_queue', ['to_email' => $to, 'subject' => $subject, 'html' => $html]);
    }

    /** Envoi immédiat (mot de passe oublié, etc.). Retourne true si accepté. */
    public static function sendNow(string $to, string $subject, string $html): bool
    {
        try {
            self::deliver($to, $subject, $html);
            return true;
        } catch (Throwable $e) {
            error_log('Mailer: ' . $e->getMessage());
            self::queue($to, $subject, $html);
            return false;
        }
    }

    public static function processQueue(int $limit = 40): array
    {
        $sent = 0; $failed = 0;
        $rows = db()->all("SELECT * FROM email_queue WHERE status = 'queued' AND attempts < 5 ORDER BY id LIMIT " . (int)$limit);
        foreach ($rows as $m) {
            try {
                self::deliver($m['to_email'], $m['subject'], $m['html']);
                db()->exec("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$m['id']]);
                $sent++;
            } catch (Throwable $e) {
                $att = (int)$m['attempts'] + 1;
                db()->exec('UPDATE email_queue SET attempts = ?, last_error = ?, status = ? WHERE id = ?',
                    [$att, mb_substr($e->getMessage(), 0, 500), $att >= 5 ? 'failed' : 'queued', $m['id']]);
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }

    private static function deliver(string $to, string $subject, string $html): void
    {
        $from = (string)config('mail.from', 'no-reply@localhost');
        $fromName = (string)config('mail.from_name', setting('association_name', "Les Jardins d'à Côté"));
        $text = self::toText($html);
        $boundary = 'b' . random_token(12);
        $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encFrom = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
        $msgId = '<' . random_token(12) . '@' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost') . '>';

        $headers = [
            'From: ' . $encFrom,
            'Reply-To: ' . (setting('contact_email') ?: $from),
            'Message-ID: ' . $msgId,
            'Date: ' . date('r'),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";

        if (config('env') === 'dev' && config('mail.log_only')) {
            @file_put_contents(APP_DIR . '/storage/logs/mail.log', "To: $to\nSubject: $subject\n$text\n-----\n", FILE_APPEND);
            return;
        }

        $host = (string)config('mail.smtp_host', '');
        if ($host === '') {
            if (!mail($to, $encSubject, $body, implode("\r\n", $headers), '-f' . $from)) {
                throw new RuntimeException('mail() a refusé le message');
            }
            return;
        }
        self::smtp($host, (int)config('mail.smtp_port', 465), (string)config('mail.smtp_secure', 'ssl'),
            (string)config('mail.smtp_user', ''), (string)config('mail.smtp_pass', ''), $from, $to,
            'To: <' . $to . ">\r\nSubject: " . $encSubject . "\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $body);
    }

    private static function smtp(string $host, int $port, string $secure, string $user, string $pass, string $from, string $to, string $data): void
    {
        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) throw new RuntimeException("SMTP connexion impossible : $errstr ($errno)");
        stream_set_timeout($fp, 20);
        $read = function () use ($fp): string {
            $out = '';
            while (($line = fgets($fp, 515)) !== false) {
                $out .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') break;
            }
            return $out;
        };
        $cmd = function (string $c, array $okCodes) use ($fp, $read): string {
            if ($c !== '') fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int)substr($r, 0, 3), $okCodes, true)) {
                throw new RuntimeException('SMTP ' . (str_starts_with($c, 'AUTH') || strlen($c) > 60 ? '[commande]' : $c) . ' → ' . trim($r));
            }
            return $r;
        };
        $ehloHost = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
        $cmd('', [220]);
        $cmd('EHLO ' . $ehloHost, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('SMTP STARTTLS a échoué');
            }
            $cmd('EHLO ' . $ehloHost, [250]);
        }
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334]);
            $cmd(base64_encode($pass), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $data = preg_replace('/^\./m', '..', $data);
        $cmd($data . "\r\n.", [250]);
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
    }

    private static function toText(string $html): string
    {
        $t = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $html);
        $t = preg_replace('#<(br|/p|/h\d|/tr|/li)[^>]*>#i', "\n", (string)$t);
        $t = html_entity_decode(strip_tags((string)$t), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $t)));
    }

    /** Gabarit HTML des emails, aux couleurs Ardoise / Craie / Bois. */
    public static function layout(string $title, string $bodyHtml, ?string $ctaLabel = null, ?string $ctaUrl = null): string
    {
        $name = e(setting('association_name', "Les Jardins d'à Côté"));
        $addr = e(setting('association_address', ''));
        $cta = $ctaLabel && $ctaUrl
            ? '<p style="margin:28px 0 8px"><a href="' . e($ctaUrl) . '" style="background:#1E5B3A;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:999px;font-weight:700;display:inline-block">' . e($ctaLabel) . '</a></p>'
            : '';
        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . e($title) . '</title></head>'
            . '<body style="margin:0;background:#F3F5F1;font-family:Helvetica,Arial,sans-serif;color:#152820">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F5F1;padding:24px 12px"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px">'
            . '<tr><td style="background:#1E5B3A;border-radius:14px 14px 0 0;padding:22px 26px;border-bottom:5px solid #F1B33C">'
            . '<div style="color:#BFE0C7;font-size:14px">' . $name . '</div>'
            . '<div style="color:#F8FAFC;font-size:22px;font-weight:700;margin-top:6px;font-family:Helvetica,Arial,sans-serif">' . e($title) . '</div></td></tr>'
            . '<tr><td style="background:#ffffff;padding:26px;border-radius:0 0 14px 14px;font-size:15px;line-height:1.6">' . $bodyHtml . $cta . '</td></tr>'
            . '<tr><td style="padding:18px 8px;font-size:12px;color:#5D6E64;text-align:center">' . $name . ' · ' . $addr . '<br>Achats groupés solidaires · <a href="' . e(base_url()) . '" style="color:#1E5B3A">' . e(parse_url(base_url(), PHP_URL_HOST) ?: '') . '</a></td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
