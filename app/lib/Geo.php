<?php
declare(strict_types=1);

/**
 * Coordonnées approximatives (centre de la commune) pour la carte des groupes.
 * Table locale : pas d'appel à un service externe. Les communes absentes restent hors carte
 * jusqu'à ce que l'administrateur saisisse leurs coordonnées dans la fiche du groupe.
 */
final class Geo
{
    public const COMMUNES = [
        'toulouse' => [43.6045, 1.4440], 'pins-justaret' => [43.4796, 1.3871], 'pinsaguel' => [43.5106, 1.3889],
        'roques' => [43.5079, 1.3550], 'portet-sur-garonne' => [43.5233, 1.4058], 'villate' => [43.4637, 1.3830],
        'saubens' => [43.4787, 1.3537], 'roquettes' => [43.4947, 1.3694], 'muret' => [43.4614, 1.3267],
        'labarthe-sur-leze' => [43.4544, 1.4006], 'eaunes' => [43.4247, 1.3542], 'lacroix-falgarde' => [43.4995, 1.4119],
        'vieille-toulouse' => [43.5262, 1.4396], 'pechbusque' => [43.5281, 1.4572], 'venerque' => [43.4342, 1.4459],
        'le vernet' => [43.4321, 1.4248], 'lagardelle-sur-leze' => [43.4144, 1.3933], 'seysses' => [43.4979, 1.3122],
        'frouzins' => [43.5167, 1.3247], 'cugnaux' => [43.5370, 1.3445], 'villeneuve-tolosane' => [43.5240, 1.3460],
        'plaisance-du-touch' => [43.5652, 1.2964], 'tournefeuille' => [43.5856, 1.3436], 'colomiers' => [43.6112, 1.3354],
        'blagnac' => [43.6371, 1.3933], 'balma' => [43.6111, 1.4994], 'l\'union' => [43.6575, 1.4842],
        'ramonville-saint-agne' => [43.5459, 1.4757], 'castanet-tolosan' => [43.5160, 1.4983], 'labege' => [43.5317, 1.5317],
        'saint-orens-de-gameville' => [43.5530, 1.5348], 'auzeville-tolosane' => [43.5280, 1.4820], 'escalquens' => [43.5197, 1.5578],
        'quint-fonsegrives' => [43.5866, 1.5272], 'aucamville' => [43.6697, 1.4317], 'fenouillet' => [43.6808, 1.3919],
        'saint-jean' => [43.6650, 1.5039], 'launaguet' => [43.6716, 1.4570], 'cornebarrieu' => [43.6489, 1.3236],
        'leguevin' => [43.5995, 1.2333], 'fonsorbes' => [43.5356, 1.2316], 'saint-lys' => [43.5131, 1.1786],
        'carbonne' => [43.2995, 1.2236], 'auterive' => [43.3511, 1.4778], 'grenade' => [43.7716, 1.2936],
        'fronton' => [43.8394, 1.3889], 'villemur-sur-tarn' => [43.8667, 1.5039], 'revel' => [43.4589, 2.0042],
        'saint-gaudens' => [43.1083, 0.7231], 'villefranche-de-lauragais' => [43.3997, 1.7164], 'nailloux' => [43.3575, 1.6236],
    ];

    public static function key(string $commune): string
    {
        $s = mb_strtolower(trim($commune));
        $s = strtr($s, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ç' => 'c', '’' => '\'']);
        return preg_replace('/\s+/', '-', preg_replace('/^(st|ste)-/', 'saint-', $s)) ?? $s;
    }

    public static function lookup(string $commune): ?array
    {
        $k = self::key($commune);
        return self::COMMUNES[$k] ?? self::COMMUNES[str_replace('-', ' ', $k)] ?? null;
    }

    /** Renseigne les coordonnées manquantes des groupes à partir de la table. */
    public static function fillGroups(): void
    {
        foreach (db()->all('SELECT id, commune FROM `groups` WHERE lat IS NULL') as $g) {
            if ($c = self::lookup((string)$g['commune'])) db()->exec('UPDATE `groups` SET lat = ?, lng = ? WHERE id = ?', [$c[0], $c[1], $g['id']]);
        }
    }

    /** Points de la carte : groupes actifs ou proposés, avec leur état et leur prochain retrait. */
    public static function mapPoints(): array
    {
        self::fillGroups();
        $pts = [];
        foreach (db()->all("SELECT id, slug, name, commune, lat, lng, status, pickup_place, pickup_address FROM `groups` WHERE status IN ('active','proposed') AND lat IS NOT NULL") as $g) {
            $sale = db()->one("SELECT status FROM sales WHERE group_id = ? AND status IN ('voting','open','waiting') ORDER BY FIELD(status,'open','voting','waiting') LIMIT 1", [$g['id']]);
            $pts[] = [
                'name' => $g['name'], 'commune' => $g['commune'], 'url' => '/groupes/' . $g['slug'],
                'lat' => (float)$g['lat'], 'lng' => (float)$g['lng'],
                'state' => $g['status'] === 'proposed' ? 'proposed' : ($sale['status'] ?? 'active'),
                'pickup' => trim(($g['pickup_place'] ?? '') . ($g['pickup_address'] ? ' — ' . $g['pickup_address'] : '')),
            ];
        }
        return $pts;
    }
}
