<?php
declare(strict_types=1);

/** Les 6 unités natives du catalogue polymorphe (cf. §2 du cahier des charges). */
final class Units
{
    public const ALL = [
        'g'     => ['name' => 'Gramme',            'short' => 'g',      'ref' => 1000, 'ref_label' => 'le kg',          'step' => 5],
        'seeds' => ['name' => 'Nombre de graines', 'short' => 'graines','ref' => 1000, 'ref_label' => 'les 1000 graines','step' => 50],
        'l'     => ['name' => 'Litre',             'short' => 'L',      'ref' => 1,    'ref_label' => 'le litre',       'step' => 5],
        'kg'    => ['name' => 'Kilogramme',        'short' => 'kg',     'ref' => 1,    'ref_label' => 'le kg',          'step' => 1],
        'pot'   => ['name' => 'Unité / pot',       'short' => 'pot',    'ref' => 1,    'ref_label' => "l'unité",        'step' => 1],
        'piece' => ['name' => 'Pièce unique',      'short' => 'pièce',  'ref' => 1,    'ref_label' => 'la pièce',       'step' => 1],
    ];

    public const CATEGORIES = [
        'seeds'       => ['label' => 'Semences Bio',          'icon' => 'seed'],
        'indoor'      => ['label' => "Plantes d'intérieur",   'icon' => 'leaf'],
        'shrubs'      => ['label' => 'Arbustes & Fruitiers',  'icon' => 'tree'],
        'substrate'   => ['label' => 'Terreau & Engrais vrac','icon' => 'soil'],
        'pots'        => ['label' => 'Pots & Cache-pots',     'icon' => 'pot'],
        'second_hand' => ['label' => 'Seconde main',          'icon' => 'heart'],
    ];

    public const EXPOSURE = ['shade' => 'Ombre', 'partial' => 'Mi-ombre', 'sun' => 'Soleil'];
    public const WATER = ['low' => 'Peu d\'eau', 'medium' => 'Arrosage modéré', 'high' => 'Beaucoup d\'eau'];
    public const DIFFICULTY = ['easy' => 'Increvable / Débutant', 'medium' => 'Intermédiaire', 'expert' => 'Jardinier confirmé'];
    public const MODES = [
        'volume'   => 'Par volume / poids produit',
        'logistic' => 'Par unité logistique (roll / palette)',
        'franco'   => 'Franco de port global (panier mutuel)',
    ];
    public const STATUSES = [
        'draft' => 'Brouillon', 'active' => 'En cours', 'validated' => 'Validée', 'failed' => 'Non atteinte',
        'receiving' => 'Réception', 'packaging' => 'Ensachage', 'ready_pickup' => 'Prête au retrait', 'closed' => 'Terminée',
    ];

    public static function short(string $unit): string
    {
        return self::ALL[$unit]['short'] ?? $unit;
    }

    public static function qty(float|string $qty, string $unit): string
    {
        $q = (float)$qty;
        if ($unit === 'g' && $q >= 1000) return num($q / 1000, 3) . "\u{00A0}kg";
        if ($unit === 'seeds') return num($q, 0) . "\u{00A0}" . ($q >= 2 ? 'graines' : 'graine');
        if ($unit === 'pot') return num($q, 0) . "\u{00A0}" . ($q >= 2 ? 'pots' : 'pot');
        if ($unit === 'piece') return num($q, 0) . "\u{00A0}" . ($q >= 2 ? 'pièces' : 'pièce');
        return num($q, 3) . "\u{00A0}" . self::short($unit);
    }

    /** Prix de référence lisible : 12,40 € le kg, 3,10 € les 1000 graines… */
    public static function refPrice(float $unitPrice, string $unit): string
    {
        $ref = self::ALL[$unit]['ref'] ?? 1;
        return money($unitPrice * $ref) . ' ' . (self::ALL[$unit]['ref_label'] ?? '');
    }

    /** Nombre approximatif de graines dans X grammes grâce au PMG. */
    public static function seedsFromGrams(float $grams, ?float $pmg): ?int
    {
        if (!$pmg || $pmg <= 0) return null;
        return (int)round($grams / $pmg * 1000);
    }

    public static function gramsFromSeeds(float $seeds, ?float $pmg): ?float
    {
        if (!$pmg || $pmg <= 0) return null;
        return round($seeds * $pmg / 1000, 3);
    }

    /** Simulateur de contenant (terreau vrac). */
    public static function containerHint(float $litres): string
    {
        $buckets = $litres / 10;
        $cabas = $litres / 25;
        $parts = [];
        $parts[] = '≈ ' . num($buckets, 1) . ' ' . plural($buckets, 'seau classique (10 L)', 'seaux classiques (10 L)');
        if ($litres >= 20) $parts[] = num($cabas, 1) . ' ' . plural($cabas, 'grand sac cabas', 'grands sacs cabas');
        return implode(' ou ', $parts);
    }

    public static function portions(string $csv): array
    {
        $out = [];
        foreach (explode(',', $csv) as $p) {
            $p = (float)str_replace(',', '.', trim($p));
            if ($p > 0) $out[] = $p;
        }
        sort($out);
        return array_values(array_unique($out));
    }
}
