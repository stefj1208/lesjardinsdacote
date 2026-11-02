<?php
declare(strict_types=1);

/** Univers de produits, statuts et libellés partagés. */
final class Cat
{
    public const HERO = 'commons:Vegetable_garden_1.JPG|commons:Raised_bed.jpg|commons:Vegetable_garden_detail.jpg';

    /** Univers d'achat groupé (l'ordre est celui de l'affichage). */
    public const CATEGORIES = [
        'vegetables'  => ['label' => 'Plants potagers', 'short' => 'Plants', 'slug' => 'plants-potagers', 'season' => 'Avril – mai',
            'photo' => 'commons:Vegetable_garden_1.JPG|commons:Dar_cucumber_seedlings.jpg',
            'intro' => 'Tomates anciennes, aubergines, courges, fraisiers : des plants bio prêts à repiquer, achetés directement aux horticulteurs.',
            'examples' => ['Tomates anciennes', 'Aubergines', 'Courges', 'Fraisiers']],
        'fruit_trees' => ['label' => 'Arbres fruitiers', 'short' => 'Fruitiers', 'slug' => 'arbres-fruitiers', 'season' => 'Novembre – mars',
            'photo' => 'commons:Piper-Orchard-Old-apple-tree-3369.jpg|commons:Apple_orchard.jpg',
            'intro' => 'Abricotiers, pêchers, pruniers, pommiers en racines nues : des variétés anciennes adaptées au Sud-Ouest, au prix pépiniériste.',
            'examples' => ['Abricotiers', 'Pêchers', 'Pruniers', 'Pommiers']],
        'aromatics'   => ['label' => 'Aromatiques', 'short' => 'Aromatiques', 'slug' => 'aromatiques', 'season' => 'Avril – juin',
            'photo' => 'commons:Basil_leaves.jpg|commons:Thyme.jpg',
            'intro' => 'Thym, romarin, menthes, verveine : des plants en pot pour un coin d\'herbes fraîches toute l\'année.',
            'examples' => ['Romarin', 'Menthes', 'Estragon', 'Verveine']],
        'seeds'       => ['label' => 'Graines rares', 'short' => 'Graines', 'slug' => 'graines', 'season' => 'Janvier – mars',
            'photo' => 'commons:Pumpkin_seeds_in_hand.jpg|commons:Beans_in_hand,_gene_bank.jpg',
            'intro' => 'Des dizaines de variétés bio et reproductibles, en sachets préparés par les bénévoles à partir des grands conditionnements.',
            'examples' => ['Tomates noires', 'Aubergines zébrées', 'Piments', 'Basilics']],
        'plants'      => ['label' => 'Fleurs & vivaces', 'short' => 'Fleurs', 'slug' => 'fleurs-vivaces', 'season' => 'Bientôt',
            'photo' => 'commons:Echinacea.JPG|commons:Lavender_fields_in_India.jpg|commons:Vegetable_garden_detail.jpg',
            'intro' => 'Vivaces mellifères, haies fleuries, plantes de massif : pour fleurir jardins, balcons et espaces partagés.',
            'examples' => ['Vivaces mellifères', 'Lavandes', 'Haies fleuries', 'Plantes de massif']],
    ];

    /** Prix de pack conseillé par univers (les arbres coûtent plus cher à l'unité). */
    public static function packPrice(string $cat): float
    {
        return $cat === 'fruit_trees' ? 60.0 : (float)setting('default_pack_price', '30');
    }

    public static function bySlug(string $slug): ?string
    {
        foreach (self::CATEGORIES as $k => $c) if ($c['slug'] === $slug) return $k;
        return null;
    }

    public const UNITS = [
        'graines' => ['graine', 'graines'],
        'g'       => ['g', 'g'],
        'plants'  => ['plant', 'plants'],
        'godets'  => ['godet', 'godets'],
        'arbre'   => ['arbre', 'arbres'],
        'pieds'   => ['pied', 'pieds'],
    ];

    public const ORGANIZERS = [
        'collectivite'   => 'Mairie, CCAS ou collectivité',
        'entreprise'     => 'Entreprise ou comité d\'entreprise',
        'association'    => 'Association',
        'jardin_partage' => 'Jardin partagé ou familial',
        'habitants'      => 'Groupe d\'habitants',
        'autre'          => 'Autre',
    ];

    /** Statuts publics d'un groupe (cahier : 3 statuts). */
    public const GROUP_STATUS = [
        'recruiting' => ['label' => 'À la recherche de bénévoles', 'short' => 'Bénévoles recherchés', 'tone' => 'sun'],
        'preopening' => ['label' => 'Pré-ouverture : vote des variétés', 'short' => 'Pré-ouverture', 'tone' => 'leaf'],
        'open'       => ['label' => 'Ventes ouvertes', 'short' => 'Ventes ouvertes', 'tone' => 'tomato'],
    ];

    public const SALE_STATUS = [
        'waiting' => 'En attente de bénévoles', 'voting' => 'Vote en cours', 'open' => 'Vente ouverte',
        'closed' => 'Terminée', 'cancelled' => 'Annulée',
    ];

    public static function label(string $cat): string
    {
        return self::CATEGORIES[$cat]['label'] ?? $cat;
    }

    public static function qty(float|int|string $q, string $unit): string
    {
        $q = (float)$q;
        $u = self::UNITS[$unit] ?? [$unit, $unit];
        if ($unit === 'g') return num($q, 2) . "\u{00A0}g";
        return num($q, 2) . "\u{00A0}" . ($q >= 2 ? $u[1] : $u[0]);
    }
}
