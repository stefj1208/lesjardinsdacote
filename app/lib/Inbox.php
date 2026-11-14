<?php
declare(strict_types=1);

/**
 * Boîte de réception contact@ : relevée en IMAP par la tâche planifiée du site.
 * Chaque nouveau message est enregistré puis mis dans la file « À valider »
 * (statut draft_needed) ; l'agent de tri y dépose ensuite un brouillon de réponse.
 *
 * Réglages (Paramètres) : imap_host, imap_user, imap_password. Nécessite l'extension PHP imap
 * (hPanel → Avancé → Configuration PHP → extensions).
 */
final class Inbox
{
    /** Adresse relevée (par défaut : l'adresse de contact de l'association). */
    public static function user(): string
    {
        return (string)(setting('imap_user', '') ?: setting('contact_email', ''));
    }

    public static function ready(): bool
    {
        return extension_loaded('imap') && setting('imap_password', '') !== '' && self::user() !== '';
    }

    /** Adresses à ignorer (boucles, rebonds, notifications automatiques). */
    private static function ignored(string $from): bool
    {
        $from = mb_strtolower($from);
        if (preg_match('/^(mailer-daemon|postmaster|no-?reply|notifications?)@/i', $from)) return true;
        $self = mb_strtolower(self::user());
        return $self !== '' && $from === $self;
    }

    /** Pré-classement par mots-clés ; l'agent affine ensuite. */
    public static function classify(string $from, string $subject, string $body): string
    {
        $t = mb_strtolower($subject . ' ' . mb_substr($body, 0, 1500) . ' ' . $from);
        $tests = [
            'fournisseur' => '/p[ée]pini|semenc|producteur|horticult|grossiste|tarif|catalogue|bon de commande|proforma/u',
            'collectivite' => '/mairie|ccas|commune|collectivit|m[ée]tropole|intercommunal|bailleur|comit[ée] d\'entreprise|\.gouv\.fr|@mairie/u',
            'mail' => '/commande|pack|panier|retrait|remboursement|adh[ée]sion|b[ée]n[ée]vol|vote|graine|plant/u',
        ];
        foreach ($tests as $cat => $re) if (preg_match($re, $t)) return $cat;
        return 'mail';
    }

    public static function poll(int $max = 20): array
    {
        if (!extension_loaded('imap')) return ['skipped' => 'extension imap absente'];
        if (!self::ready()) return ['skipped' => 'boîte non configurée'];
        $user = self::user();
        $host = setting('imap_host', 'imap.hostinger.com');
        $box = '{' . $host . ':993/imap/ssl}INBOX';
        $st = @imap_open($box, $user, (string)setting('imap_password'), 0, 1);
        if (!$st) { imap_errors(); return ['error' => 'connexion IMAP impossible']; }
        $added = 0;
        try {
            $lastUid = (int)setting('imap_last_uid', '0');
            $n = imap_num_msg($st);
            $from = max(1, $n - 100);
            $maxUid = $lastUid;
            for ($i = $n; $i >= $from && $added < $max; $i--) {
                $uid = imap_uid($st, $i);
                if ($uid <= $lastUid) break; // les suivants sont plus anciens
                $maxUid = max($maxUid, $uid);
                $h = imap_headerinfo($st, $i);
                if (!$h) continue;
                $fromEmail = isset($h->from[0]) ? mb_strtolower($h->from[0]->mailbox . '@' . $h->from[0]->host) : '';
                $fromName = isset($h->from[0]->personal) ? self::decodeHeader($h->from[0]->personal) : '';
                $subject = isset($h->subject) ? self::decodeHeader($h->subject) : '(sans objet)';
                if (self::ignored($fromEmail)) continue;
                $body = self::bodyText($st, $i);
                $cat = self::classify($fromEmail, $subject, $body);
                $msgId = isset($h->message_id) ? mb_substr($h->message_id, 0, 250) : null;
                if ($msgId && db()->val('SELECT id FROM inbox_messages WHERE message_id = ?', [$msgId])) continue;
                $id = db()->insert('inbox_messages', [
                    'uid' => $uid, 'message_id' => $msgId,
                    'from_email' => mb_substr($fromEmail, 0, 190), 'from_name' => mb_substr($fromName, 0, 190),
                    'subject' => mb_substr($subject, 0, 255), 'body_text' => mb_substr($body, 0, 30000),
                    'received_at' => isset($h->udate) ? date('Y-m-d H:i:s', $h->udate) : now(),
                    'category' => $cat,
                ]);
                Agents::create([
                    'category' => $cat === 'mail' ? 'mail' : $cat, 'status' => 'draft_needed',
                    'title' => 'Répondre : ' . $subject,
                    'summary' => 'De ' . ($fromName ?: $fromEmail) . ' — en attente d\'un brouillon de réponse.',
                    'to' => $fromEmail, 'subject' => (stripos($subject, 're:') === 0 ? $subject : 'Re: ' . $subject),
                    'inbox_id' => $id,
                ], 'inbox');
                $added++;
            }
            if ($maxUid > $lastUid) set_setting('imap_last_uid', (string)$maxUid);
            set_setting('imap_last_poll', now());
        } finally {
            imap_close($st);
            imap_errors();
        }
        return ['new' => $added];
    }

    private static function decodeHeader(string $s): string
    {
        $out = '';
        foreach (imap_mime_header_decode($s) as $part) {
            $cs = strtoupper($part->charset) === 'DEFAULT' ? 'ASCII' : $part->charset;
            $out .= @mb_convert_encoding($part->text, 'UTF-8', $cs) ?: $part->text;
        }
        return $out;
    }

    /** Première partie text/plain (sinon HTML dépouillé). */
    private static function bodyText($st, int $msgno): string
    {
        $struct = imap_fetchstructure($st, $msgno);
        $plain = self::findPart($struct, 'PLAIN');
        if ($plain) return self::decodePart($st, $msgno, $plain[0], $plain[1]);
        $html = self::findPart($struct, 'HTML');
        if ($html) {
            $t = self::decodePart($st, $msgno, $html[0], $html[1]);
            return trim(preg_replace('/\n{3,}/', "\n\n", strip_tags(preg_replace('/<(br|\/p|\/div)[^>]*>/i', "\n", $t))));
        }
        $raw = imap_body($st, $msgno) ?: '';
        return trim(mb_substr($raw, 0, 10000));
    }

    private static function findPart($struct, string $subtype, string $prefix = ''): ?array
    {
        if (!$struct) return null;
        if (empty($struct->parts)) {
            return strtoupper($struct->subtype ?? '') === $subtype ? [$prefix ?: '1', $struct] : null;
        }
        foreach ($struct->parts as $i => $p) {
            $num = $prefix === '' ? (string)($i + 1) : $prefix . '.' . ($i + 1);
            if (strtoupper($p->subtype ?? '') === $subtype && empty($p->parts)) return [$num, $p];
            $found = self::findPart($p, $subtype, $num);
            if ($found) return $found;
        }
        return null;
    }

    private static function decodePart($st, int $msgno, string $section, $part): string
    {
        $data = imap_fetchbody($st, $msgno, $section) ?: '';
        $data = match ((int)($part->encoding ?? 0)) {
            3 => base64_decode($data) ?: '',
            4 => quoted_printable_decode($data),
            default => $data,
        };
        $charset = 'UTF-8';
        foreach (($part->parameters ?? []) as $p) if (strtoupper($p->attribute) === 'CHARSET') $charset = $p->value;
        if (strtoupper($charset) !== 'UTF-8') $data = @mb_convert_encoding($data, 'UTF-8', $charset) ?: $data;
        return trim($data);
    }
}
