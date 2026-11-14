<?php
declare(strict_types=1);

/**
 * File « À valider » : les agents (tâches planifiées Claude) déposent des propositions —
 * réponses aux mails, prospection, posts — et rien ne part sans validation humaine,
 * sauf les catégories passées explicitement en automatique (règle des 90 %).
 *
 * Authentification : jeton d'API (haché en base). Première récupération par /api/agents/claim,
 * tant qu'aucun jeton n'existe ; régénération dans Paramètres.
 */
final class Agents
{
    /** Catégories connues (libellé + envoi d'e-mail possible + mention RGPD en prospection). */
    public const CATEGORIES = [
        'mail'         => ['label' => 'Réponses aux mails', 'outreach' => false],
        'fournisseur'  => ['label' => 'Prospection fournisseurs', 'outreach' => true],
        'collectivite' => ['label' => 'Mairies & collectivités', 'outreach' => true],
        'social'       => ['label' => 'Réseaux sociaux', 'outreach' => false],
        'catalogue'    => ['label' => 'Catalogue', 'outreach' => false],
        'autre'        => ['label' => 'Divers', 'outreach' => false],
    ];

    public const RGPD_FOOTER = "Conformément au RGPD, vous pouvez vous opposer à tout moment à de nouveaux messages de notre part : répondez simplement « stop » et nous retirerons votre adresse.";

    /* ---------------- Jeton d'API ---------------- */

    public static function claim(): ?string
    {
        if ((string)setting('agent_key_hash', '') !== '') return null;
        return self::regenerate();
    }

    public static function regenerate(): string
    {
        $token = 'jdc_' . random_token(24);
        set_setting('agent_key_hash', hash('sha256', $token));
        audit('agents.key', 'jeton d\'API des agents (re)généré');
        return $token;
    }

    public static function verify(?string $token): bool
    {
        $h = (string)setting('agent_key_hash', '');
        return $h !== '' && $token && hash_equals($h, hash('sha256', $token));
    }

    /* ---------------- Dépôt et lecture ---------------- */

    public static function create(array $d, string $source = 'api'): int
    {
        $cat = isset(self::CATEGORIES[$d['category'] ?? '']) ? $d['category'] : 'autre';
        $to = trim((string)($d['to'] ?? ''));
        return db()->insert('agent_items', [
            'category' => $cat,
            'status' => ($d['status'] ?? '') === 'draft_needed' ? 'draft_needed' : 'pending',
            'title' => mb_substr(trim((string)($d['title'] ?? 'Proposition')), 0, 200),
            'summary' => mb_substr(trim((string)($d['summary'] ?? '')), 0, 2000),
            'to_email' => filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : null,
            'subject' => mb_substr(trim((string)($d['subject'] ?? '')), 0, 255) ?: null,
            'body' => (string)($d['body'] ?? '') ?: null,
            'meta' => isset($d['meta']) ? json_encode($d['meta'], JSON_UNESCAPED_UNICODE) : null,
            'inbox_id' => isset($d['inbox_id']) ? (int)$d['inbox_id'] : null,
            'source' => mb_substr($source, 0, 100),
        ]);
    }

    /** Dépose le brouillon d'un élément créé par la boîte mail (draft_needed → pending). */
    public static function draft(int $id, array $d): bool
    {
        $it = db()->one('SELECT * FROM agent_items WHERE id = ?', [$id]);
        if (!$it || $it['status'] !== 'draft_needed') return false;
        db()->exec('UPDATE agent_items SET status = "pending", subject = ?, body = ?, summary = ?, category = ? WHERE id = ?', [
            mb_substr(trim((string)($d['subject'] ?? ($it['subject'] ?: 'Re : '))), 0, 255),
            (string)($d['body'] ?? ''),
            mb_substr(trim((string)($d['summary'] ?? $it['summary'])), 0, 2000),
            isset(self::CATEGORIES[$d['category'] ?? '']) ? $d['category'] : $it['category'],
            $id,
        ]);
        return true;
    }

    public static function items(?string $status = null, ?string $category = null, int $limit = 50): array
    {
        $w = []; $p = [];
        if ($status) { $w[] = 'a.status = ?'; $p[] = $status; }
        if ($category) { $w[] = 'a.category = ?'; $p[] = $category; }
        return db()->all('SELECT a.*, i.from_email AS inbox_from, i.from_name AS inbox_from_name, i.subject AS inbox_subject,
                i.body_text AS inbox_body, i.received_at AS inbox_received_at
            FROM agent_items a LEFT JOIN inbox_messages i ON i.id = a.inbox_id'
            . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
            . ' ORDER BY a.id DESC LIMIT ' . max(1, min(200, $limit)), $p);
    }

    /* ---------------- Décision et envoi ---------------- */

    public static function decide(int $id, string $action, array $edit, ?int $userId, string $note = ''): array
    {
        $it = db()->one('SELECT * FROM agent_items WHERE id = ?', [$id]);
        if (!$it || !in_array($it['status'], ['pending', 'draft_needed'], true)) return ['ok' => false, 'error' => 'Déjà traité.'];
        if ($action === 'reject') {
            db()->exec('UPDATE agent_items SET status = "rejected", decided_at = NOW(), decided_by = ?, decision_note = ? WHERE id = ?', [$userId, mb_substr($note, 0, 500), $id]);
            audit('agents.reject', '#' . $id . ' ' . $it['title']);
            return ['ok' => true, 'status' => 'rejected'];
        }
        $nl = fn(?string $s) => trim(str_replace("\r\n", "\n", (string)$s));
        $subject = $nl($edit['subject'] ?? $it['subject'] ?? '');
        $body = $nl($edit['body'] ?? $it['body'] ?? '');
        $edited = ($subject !== $nl($it['subject']) || $body !== $nl($it['body'])) ? 1 : 0;
        db()->exec('UPDATE agent_items SET status = "approved", edited = ?, subject = ?, body = ?, decided_at = NOW(), decided_by = ?, decision_note = ? WHERE id = ?',
            [$edited, mb_substr($subject, 0, 255), $body, $userId, mb_substr($note, 0, 500), $id]);
        audit('agents.approve', '#' . $id . ' ' . $it['title'] . ($edited ? ' (modifié)' : ''));
        if ($it['to_email'] && $subject !== '' && $body !== '') return self::dispatch($id);
        return ['ok' => true, 'status' => 'approved'];
    }

    /** Envoie un élément approuvé qui comporte un destinataire. */
    public static function dispatch(int $id): array
    {
        $it = db()->one('SELECT * FROM agent_items WHERE id = ?', [$id]);
        if (!$it || $it['status'] !== 'approved' || !$it['to_email']) return ['ok' => false, 'error' => 'Rien à envoyer.'];
        $text = (string)$it['body'];
        if (!empty(self::CATEGORIES[$it['category']]['outreach']) && mb_stripos($text, 'RGPD') === false) {
            $text .= "\n\n—\n" . self::RGPD_FOOTER;
        }
        try {
            Mailer::queue($it['to_email'], (string)$it['subject'], Mailer::layout((string)$it['subject'], nl2p($text)));
            db()->exec('UPDATE agent_items SET status = "sent", sent_at = NOW() WHERE id = ?', [$id]);
            if ($it['inbox_id']) db()->exec('UPDATE inbox_messages SET status = "answered", answered_at = NOW() WHERE id = ?', [$it['inbox_id']]);
            audit('agents.send', '#' . $id . ' → ' . $it['to_email']);
            return ['ok' => true, 'status' => 'sent'];
        } catch (Throwable $e) {
            db()->exec('UPDATE agent_items SET status = "failed", error = ? WHERE id = ?', [mb_substr($e->getMessage(), 0, 500), $id]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /* ---------------- Statistiques (règle des 90 %) ---------------- */

    public static function stats(): array
    {
        $cats = [];
        foreach (db()->all("SELECT category,
                SUM(status IN ('pending','draft_needed')) AS pending,
                SUM(status IN ('approved','sent','rejected','failed')) AS decided,
                SUM(status IN ('approved','sent') AND edited = 0) AS clean,
                SUM(status = 'rejected') AS rejected
            FROM agent_items GROUP BY category") as $r) {
            $decided = (int)$r['decided'];
            $cats[$r['category']] = [
                'label' => self::CATEGORIES[$r['category']]['label'] ?? $r['category'],
                'pending' => (int)$r['pending'], 'decided' => $decided, 'rejected' => (int)$r['rejected'],
                'clean_rate' => $decided ? round((int)$r['clean'] / $decided * 100) : null,
            ];
        }
        return [
            'pending' => (int)db()->val("SELECT COUNT(*) FROM agent_items WHERE status IN ('pending','draft_needed')"),
            'categories' => $cats,
            'inbox' => [
                'new' => (int)db()->val("SELECT COUNT(*) FROM inbox_messages WHERE status = 'new'"),
                'imap_ready' => Inbox::ready(),
                'last_poll' => setting('imap_last_poll', ''),
            ],
            'kpi' => [
                'fund' => Asso::fundTotal(),
                'members' => (int)db()->val('SELECT COUNT(DISTINCT user_id) FROM memberships WHERE year = ?', [Asso::year()]),
                'groups_active' => (int)db()->val("SELECT COUNT(*) FROM `groups` WHERE status = 'active'"),
                'groups_proposed' => (int)db()->val("SELECT COUNT(*) FROM `groups` WHERE status = 'proposed'"),
                'orders_30d' => (int)db()->val('SELECT COUNT(*) FROM orders WHERE created_at > (NOW() - INTERVAL 30 DAY)'),
                'emails_queued' => (int)db()->val("SELECT COUNT(*) FROM email_queue WHERE status = 'queued'"),
            ],
        ];
    }

    public static function pendingCount(): int
    {
        try { return (int)db()->val("SELECT COUNT(*) FROM agent_items WHERE status IN ('pending','draft_needed')"); }
        catch (Throwable) { return 0; }
    }
}
