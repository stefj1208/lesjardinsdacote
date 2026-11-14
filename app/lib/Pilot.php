<?php
declare(strict_types=1);

/**
 * Poste de pilotage : registre des agents (pause, dernier passage), niveaux d'autonomie par
 * catégorie, liste « à faire » du président, pipelines et journal.
 */
final class Pilot
{
    /** Agents connus. status : live (tâche planifiée active) ou planned (à venir). */
    public const AGENTS = [
        'brief'        => ['label' => 'Brief quotidien', 'when' => 'Chaque jour à 7 h 17', 'does' => 'Santé du site, file à valider, chiffres, décisions du jour', 'status' => 'live'],
        'mail'         => ['label' => 'Boîte mail', 'when' => '7 h 47 et 16 h 47', 'does' => 'Rédige un brouillon de réponse pour chaque message reçu', 'status' => 'live'],
        'fournisseurs' => ['label' => 'Prospection fournisseurs', 'when' => 'Le lundi à 8 h 03', 'does' => 'Trouve des pépiniéristes, prépare demandes de tarifs et relances', 'status' => 'live'],
        'collectivites'=> ['label' => 'Mairies & collectivités', 'when' => 'Le mardi à 8 h 11', 'does' => 'Cible mairies, CCAS, jardins partagés et prépare les propositions de partenariat', 'status' => 'live'],
        'social'       => ['label' => 'Réseaux sociaux', 'when' => 'Le jeudi à 8 h 07', 'does' => 'Prépare 3 à 5 posts pour la semaine suivante', 'status' => 'live'],
        'catalogue'    => ['label' => 'Catalogue', 'when' => 'Avec la boîte mail', 'does' => 'Extrait produits et prix des réponses fournisseurs', 'status' => 'planned'],
        'achats'       => ['label' => 'Achats groupés', 'when' => 'Toutes les 5 min (site)', 'does' => 'Votes, lots de 10, rappels, annulations : géré par le site', 'status' => 'site'],
    ];

    /** Catégories qui peuvent passer en envoi automatique, et celles qui restent toujours validées. */
    public const AUTO_ALLOWED = ['mail', 'fournisseur'];
    public const AUTO_MIN_DECIDED = 20;
    public const AUTO_MIN_RATE = 90;

    public static function paused(string $agent): bool { return setting('agent_paused_' . $agent, '0') === '1'; }
    public static function setPaused(string $agent, bool $p): void
    {
        if (!isset(self::AGENTS[$agent])) return;
        set_setting('agent_paused_' . $agent, $p ? '1' : '0');
        audit('pilot.pause', $agent . ($p ? ' en pause' : ' relancé'));
    }

    public static function auto(string $category): bool
    {
        return in_array($category, self::AUTO_ALLOWED, true) && setting('auto_' . $category, '0') === '1';
    }

    /** Une catégorie est éligible à l'automatique après 20 décisions dont 90 % validées telles quelles. */
    public static function eligible(array $catStats): bool
    {
        return ($catStats['decided'] ?? 0) >= self::AUTO_MIN_DECIDED && ($catStats['clean_rate'] ?? 0) >= self::AUTO_MIN_RATE;
    }

    public static function setAuto(string $category, bool $on, array $stats): bool
    {
        if (!in_array($category, self::AUTO_ALLOWED, true)) return false;
        if ($on && !self::eligible($stats['categories'][$category] ?? [])) return false;
        set_setting('auto_' . $category, $on ? '1' : '0');
        audit('pilot.auto', $category . ($on ? ' → envoi automatique' : ' → validation manuelle'));
        return true;
    }

    /* ---------------- Passages des agents ---------------- */

    public static function logRun(string $agent, string $summary, int $created = 0, bool $ok = true): void
    {
        db()->insert('agent_runs', ['agent' => mb_substr($agent, 0, 40), 'summary' => mb_substr($summary, 0, 2000), 'items_created' => $created, 'ok' => $ok ? 1 : 0]);
    }

    public static function lastRuns(): array
    {
        $out = [];
        foreach (db()->all('SELECT r.* FROM agent_runs r JOIN (SELECT agent, MAX(id) AS id FROM agent_runs GROUP BY agent) m ON m.id = r.id') as $r) $out[$r['agent']] = $r;
        return $out;
    }

    /* ---------------- Liste « à faire » ---------------- */

    public const SEED_TODOS = [
        ['Saisir le mot de passe de la boîte contact@', 'Admin → Paramètres → Boîte mail entrante. Sans lui, les messages reçus n\'arrivent pas dans « À valider ».', '/admin/parametres', 'imap'],
        ['Autoriser lesjardinsdacote.fr pour les agents', 'Sur claude.ai → Paramètres → Capacités → domaines autorisés, ajouter lesjardinsdacote.fr. Sans cela, les agents ne peuvent rien déposer ici.', null, 'egress'],
        ['Trouver une troisième personne pour le bureau', 'Secrétaire sans lien familial avec le président et la trésorière (gestion désintéressée, signature des conventions).', null, null],
        ['Tenir l\'assemblée constitutive et signer les statuts', 'Dossier prêt : statuts, règlement intérieur, procès-verbal.', null, null],
        ['Déclarer l\'association en ligne (préfecture)', 'Statuts signés, procès-verbal, liste des dirigeants. Gratuit. Donne le numéro RNA.', null, null],
        ['Renseigner le siège, le RNA et le SIRET', 'Admin → Paramètres. Le pied de page affiche encore « SIRET à compléter ».', '/admin/parametres', null],
        ['Ouvrir le compte bancaire et l\'assurance RC', 'Assurance couvrant bénévoles, distributions et chantiers de plantation.', null, null],
        ['Reconnecter Canva', 'claude.ai → Paramètres → Connecteurs : pour les plaquettes mairies/entreprises et les visuels des posts.', null, null],
        ['Créer le compte HelloAsso', 'Après réception du RNA. Paiement gratuit pour les associations.', null, null],
        ['Mettre à jour la tâche Cron dans hPanel', 'Remplacer la commande par : /usr/bin/php /home/u183108258/domains/lesjardinsdacote.fr/public_html/site/app/cron/run.php', null, null],
        ['Signer la cession du nom et du domaine par la SAS', 'Avant la clôture de la liquidation.', null, null],
    ];

    public static function seedTodos(): void
    {
        if ((int)db()->val('SELECT COUNT(*) FROM todos') > 0) return;
        foreach (self::SEED_TODOS as $i => [$t, $d, $l, $k]) db()->insert('todos', ['title' => $t, 'detail' => $d, 'link' => $l, 'auto_key' => $k, 'position' => $i, 'source' => 'plan']);
    }

    public static function todos(): array
    {
        self::autoResolve();
        return db()->all('SELECT * FROM todos ORDER BY (done_at IS NOT NULL), position, id');
    }

    /** Coche toute seule ce que le site peut constater. */
    private static function autoResolve(): void
    {
        $checks = [
            'imap' => Inbox::ready(),
            'egress' => (int)db()->val('SELECT COUNT(*) FROM agent_runs') > 0,
        ];
        foreach ($checks as $key => $ok) if ($ok) db()->exec('UPDATE todos SET done_at = NOW() WHERE auto_key = ? AND done_at IS NULL', [$key]);
    }

    public static function addTodo(string $title, string $detail = '', string $source = 'moi', ?string $link = null): int
    {
        return db()->insert('todos', ['title' => mb_substr(trim($title), 0, 200), 'detail' => mb_substr(trim($detail), 0, 1000), 'link' => $link,
            'position' => 1000 + (int)db()->val('SELECT COUNT(*) FROM todos'), 'source' => mb_substr($source, 0, 40)]);
    }

    public static function toggleTodo(int $id, bool $done): void
    {
        db()->exec('UPDATE todos SET done_at = ' . ($done ? 'NOW()' : 'NULL') . ' WHERE id = ?', [$id]);
    }

    /* ---------------- Pipelines ---------------- */

    public static function pipeline(string $category): array
    {
        $r = db()->one("SELECT
              SUM(status IN ('pending','draft_needed')) AS waiting,
              SUM(status = 'sent' AND inbox_id IS NULL) AS contacted,
              SUM(status = 'rejected') AS rejected
            FROM agent_items WHERE category = ?", [$category]) ?: [];
        // Réponses : messages reçus d'une adresse que nous avons contactée.
        $replied = (int)db()->val("SELECT COUNT(DISTINCT i.from_email) FROM inbox_messages i
            JOIN agent_items a ON a.to_email = i.from_email AND a.category = ? AND a.status = 'sent' AND a.inbox_id IS NULL
            WHERE i.received_at >= a.sent_at", [$category]);
        return ['waiting' => (int)($r['waiting'] ?? 0), 'contacted' => (int)($r['contacted'] ?? 0), 'replied' => $replied, 'rejected' => (int)($r['rejected'] ?? 0)];
    }

    public static function journal(int $limit = 25): array
    {
        $rows = [];
        foreach (db()->all('SELECT created_at, agent, summary, items_created, ok FROM agent_runs ORDER BY id DESC LIMIT ' . $limit) as $r) {
            $rows[] = ['at' => $r['created_at'], 'who' => self::AGENTS[$r['agent']]['label'] ?? $r['agent'], 'what' => $r['summary'] . ($r['items_created'] ? ' (' . $r['items_created'] . ' déposé' . ($r['items_created'] > 1 ? 's' : '') . ')' : ''), 'ok' => (bool)$r['ok']];
        }
        foreach (db()->all("SELECT created_at, action, details FROM audit_log WHERE action LIKE 'agents.%' OR action LIKE 'pilot.%' ORDER BY id DESC LIMIT " . $limit) as $r) {
            $who = ['agents.approve' => 'Vous — validé', 'agents.reject' => 'Vous — refusé', 'agents.send' => 'Site — envoyé', 'agents.auto' => 'Site — envoi automatique', 'pilot.pause' => 'Vous — agent', 'pilot.auto' => 'Vous — autonomie', 'agents.key' => 'Jeton'][$r['action']] ?? $r['action'];
            $rows[] = ['at' => $r['created_at'], 'who' => $who, 'what' => (string)$r['details'], 'ok' => true];
        }
        usort($rows, fn($a, $b) => strcmp($b['at'], $a['at']));
        return array_slice($rows, 0, $limit);
    }
}
