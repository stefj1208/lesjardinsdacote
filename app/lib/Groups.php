<?php
declare(strict_types=1);

/** Groupes locaux, membres et bénévoles. */
final class Groups
{
    public static function find(int $id): ?array
    {
        return db()->one('SELECT * FROM `groups` WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return db()->one('SELECT * FROM `groups` WHERE slug = ?', [$slug]);
    }

    /** Groupes publiés, enrichis de leur état (bénévoles, vente en cours, statut public). */
    public static function listActive(string $q = ''): array
    {
        $sql = "SELECT * FROM `groups` WHERE status = 'active'";
        $p = [];
        if ($q !== '') {
            $sql .= ' AND (commune LIKE ? OR name LIKE ? OR postal_code LIKE ?)';
            $p = array_fill(0, 3, '%' . $q . '%');
        }
        return self::hydrate(db()->all($sql . ' ORDER BY commune', $p));
    }

    public static function hydrate(array $groups): array
    {
        if (!$groups) return [];
        $ids = implode(',', array_map(fn($g) => (int)$g['id'], $groups));
        $counts = [];
        foreach (db()->all("SELECT group_id, SUM(role = 'volunteer') vol, COUNT(*) total FROM group_members WHERE group_id IN ($ids) GROUP BY group_id") as $r) {
            $counts[(int)$r['group_id']] = $r;
        }
        $sales = [];
        foreach (db()->all("SELECT * FROM sales WHERE group_id IN ($ids) AND status IN ('waiting','voting','open') ORDER BY FIELD(status,'open','voting','waiting'), id") as $s) {
            $sales[(int)$s['group_id']] ??= $s;
        }
        foreach ($groups as &$g) {
            $c = $counts[(int)$g['id']] ?? ['vol' => 0, 'total' => 0];
            $g['volunteers'] = (int)$c['vol'];
            $g['members'] = (int)$c['total'];
            $g['sale'] = isset($sales[(int)$g['id']]) ? Sales::hydrate($sales[(int)$g['id']]) : null;
            $g['public_status'] = self::publicStatus($g);
        }
        return $groups;
    }

    public static function one(int $id): ?array
    {
        $g = self::find($id);
        return $g ? self::hydrate([$g])[0] : null;
    }

    /** recruiting | preopening | open — les 3 statuts du cahier des charges. */
    public static function publicStatus(array $g): string
    {
        if (!empty($g['sale']) && $g['sale']['status'] === 'open') return 'open';
        if ($g['volunteers'] < (int)$g['volunteers_needed']) return 'recruiting';
        return 'preopening';
    }

    public static function role(int $groupId, ?int $userId): ?string
    {
        if (!$userId) return null;
        $r = db()->val('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId]);
        return $r ?: null;
    }

    public static function join(int $groupId, int $userId, string $role): void
    {
        $role = $role === 'volunteer' ? 'volunteer' : 'member';
        $cur = self::role($groupId, $userId);
        if ($cur === 'volunteer' && $role === 'member') return;
        db()->exec('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role)', [$groupId, $userId, $role]);
        if ($role === 'volunteer' && $cur !== 'volunteer') {
            $g = self::find($groupId);
            $u = db()->one('SELECT * FROM users WHERE id = ?', [$userId]);
            if ($u && $u['role'] === 'client') db()->exec('UPDATE users SET wants_volunteer = 1 WHERE id = ?', [$userId]);
            foreach (db()->col("SELECT email FROM users WHERE role = 'admin'") as $a) {
                Mailer::queue($a, 'Nouveau bénévole pour ' . $g['name'], Mailer::layout('Nouveau bénévole',
                    '<p>' . e(Auth::displayName($u)) . ' (' . e($u['email']) . ') devient bénévole de <strong>' . e($g['name']) . '</strong>.</p>'
                    . '<p>Son inscription compte déjà pour le seuil du groupe. Validez son accès aux outils bénévoles (préparation, remise des commandes) dans le back-office.</p>',
                    'Voir les membres', abs_url('/admin/utilisateurs?filtre=candidats')));
            }
            Sales::checkGroup($groupId);
        }
        audit('group.join', '#' . $groupId . ' ' . $role);
    }

    public static function leave(int $groupId, int $userId): void
    {
        db()->exec('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId]);
    }

    public static function uniqueSlug(string $name, ?int $except = null): string
    {
        $base = slugify(preg_replace("/^les jardins d['’]à côté\s*(de |d['’])?/iu", '', $name) ?: $name);
        $slug = $base; $i = 2;
        while (db()->val('SELECT id FROM `groups` WHERE slug = ?' . ($except ? ' AND id <> ' . (int)$except : ''), [$slug])) $slug = $base . '-' . $i++;
        return $slug;
    }

    /** Nom par défaut : « Les Jardins d'à Côté de Pinsaguel ». */
    public static function defaultName(string $commune): string
    {
        $c = trim($commune);
        $vowel = preg_match('/^[aeiouyhâàéèêîôûAEIOUYHÂÀÉÈÊÎÔÛ]/u', $c);
        return "Les Jardins d'à Côté " . ($vowel ? "d'" : 'de ') . $c;
    }

    public static function memberEmails(int $groupId, ?string $role = null): array
    {
        return db()->all('SELECT u.id, u.email, u.first_name FROM group_members gm JOIN users u ON u.id = gm.user_id WHERE gm.group_id = ?' . ($role ? ' AND gm.role = ' . db()->pdo->quote($role) : ''), [$groupId]);
    }
}
