<?php
declare(strict_types=1);

/** Univers de produits, statuts et libellés partagés. */
final class Cat
{
    public const HERO = 'commons:Vegetable_garden_1.JPG|commons:Raised_bed.jpg|commons:Vegetable_garden_detail.jpg';

    public const CATEGORIES = [
        'seeds'       => ['label' => 'Graines',          'short' => 'Graines',      'photo' => 'commons:Pumpkin_seeds_in_hand.jpg|commons:Beans_in_hand,_gene_bank.jpg', 'intro' => 'Des variétés bio et reproductibles, triées par les bénévoles en sachets prêts à semer.'],
        'vegetables'  => ['label' => 'Plants potagers',  'short' => 'Plants',       'photo' => 'commons:Dar_cucumber_seedlings.jpg|commons:Seedling_planting.jpg', 'intro' => 'Des plants robustes de producteurs locaux, à repiquer directement au jardin.'],
        'fruit_trees' => ['label' => 'Arbres fruitiers', 'short' => 'Fruitiers',    'photo' => 'commons:Apple_orchard.jpg|commons:Piper-Orchard-Old-apple-tree-3369.jpg', 'intro' => 'Pommiers, poiriers, petits fruits : des variétés anciennes adaptées au climat du Sud-Ouest.'],
        'aromatics'   => ['label' => 'Aromatiques',      'short' => 'Aromatiques',  'photo' => 'commons:Rosemary_Plant.jpg|commons:Fresh_thyme.jpg', 'intro' => 'Thym, romarin, basilic, menthe : un coin d\'herbes fraîches pour toute l\'année.'],
    ];

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
