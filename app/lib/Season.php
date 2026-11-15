<?php
declare(strict_types=1);

/**
 * Saison en cours : l'association se concentre sur un seul univers (les graines) et
 * propose au vote un catalogue de variétés bio et reproductibles, chacune avec la photo
 * de la variété elle-même.
 *
 * Le fournisseur et ses références restent internes (back-office) : rien de ce fichier
 * ne l'affiche au public. Prix relevés au catalogue professionnel en novembre 2026.
 */
final class Season
{
    public const SUPPLIER = 'Agrosemens';
    private const VAT = 1.055;

    /** Univers ouverts au public cette saison (les autres sont annoncés « à venir »). */
    public static function focus(): array
    {
        $f = array_filter(array_map('trim', explode(',', (string)setting('season_focus', 'seeds'))));
        return $f ? array_values(array_intersect($f, array_keys(Cat::CATEGORIES))) : array_keys(Cat::CATEGORIES);
    }

    public static function isOpen(string $cat): bool
    {
        return in_array($cat, self::focus(), true);
    }

    /** Variétés mises en avant sur l'accueil (références internes). */
    public const SHOWCASE = ['TOM31', 'POT10', 'BET04', 'SOU01', 'TOM04', 'POT04', 'PIM14', 'CON26', 'RAD19', 'BLT14', 'BOU01', 'MEL01'];

    /**
     * [famille affichée, variété, nom latin, réf., prix HT du conditionnement, quantité achetée, unité, portion par pack, texte, photo Commons]
     * Les photos montrent la variété elle-même (vérifiées une à une).
     */
    public const CATALOG = [
        ['Tomate', 'Noire de Crimée', 'Solanum lycopersicum', 'TOM04', 5.45, 100, 'graines', 10, 'Ancienne variété de Crimée : gros fruits pourpre sombre, chair dense et sucrée, sans acidité. Résiste bien à la chaleur du Sud-Ouest.', 'Black_krim_tomato.jpg'],
        ['Tomate', 'Green Zebra', 'Solanum lycopersicum', 'TOM31', 5.45, 100, 'graines', 10, 'Verte zébrée de jaune à maturité, acidulée et très parfumée. Splendide en salade mêlée.', 'Tomato_Cultivar_Green_Zebra_2021.jpg'],
        ['Tomate', 'Rose de Berne', 'Solanum lycopersicum', 'TOM02', 4.30, 100, 'graines', 10, 'Ancienne variété suisse à peau fine, rose, juteuse et douce : l\'une des meilleures à croquer.', 'Tomate_Berner_Rose_01_(fcm).jpg'],
        ['Tomate', 'Marmande', 'Solanum lycopersicum', 'TOM05', 4.90, 100, 'graines', 10, 'La tomate du Sud-Ouest : côtelée, précoce, charnue. Une variété de chez nous, cultivée depuis plus d\'un siècle.', 'Tomates_Marmande.jpg'],
        ['Tomate', 'Black Cherry', 'Solanum lycopersicum', 'TOM42', 4.90, 100, 'graines', 10, 'Tomate cerise rouge-noir, très sucrée, en longues grappes jusqu\'aux gelées. Les enfants la mangent au jardin.', 'Tomate_Black_Cherry_15671.jpg'],
        ['Tomate', 'San Marzano', 'Solanum lycopersicum', 'TOM12', 10.80, 100, 'graines', 10, 'L\'italienne allongée des sauces et des conserves : peu de jus, peu de graines, beaucoup de goût.', 'Tomatensorte_San_Marzano_03_(fcm).jpg'],
        ['Tomate', 'Brandywine rosée', 'Solanum lycopersicum', 'TOM07', 4.30, 100, 'graines', 10, 'Variété de collection transmise depuis 1885 : énormes fruits roses à la saveur réputée inégalée.', 'Brandywine-Tomato.jpg'],
        ['Tomate', 'Beefsteak', 'Solanum lycopersicum', 'TOM98', 5.45, 100, 'graines', 10, 'La tomate à farcir et à trancher : fruits rouges de 300 g et plus, chair ferme.', 'Lufa_Farms_Beefsteak_Tomato.jpg'],
        ['Aubergine', 'Black Beauty', 'Solanum melongena', 'AUB05', 5.05, 100, 'graines', 10, 'Variété ancienne (1902) à gros fruits ovales violet-noir. Productive et fiable en pleine terre.', 'Black_Beauty_eggplant.jpg'],
        ['Poivron', 'California Wonder', 'Capsicum annuum', 'POV01', 10.95, 100, 'graines', 10, 'Le poivron carré classique, vert puis rouge, épais et doux. Une valeur sûre depuis 1928.', 'Cal_wonder_pepper.JPG'],
        ['Piment', 'Basque « Gorria »', 'Capsicum annuum', 'PIM14', 5.00, 100, 'graines', 10, 'La variété du piment d\'Espelette : parfumée, chaleureuse sans brûler. À sécher en cordes au soleil.', 'Piments_d\'Espelette_sur_pied.jpg'],
        ['Piment', 'de Cayenne', 'Capsicum annuum', 'PIM02', 5.70, 100, 'graines', 10, 'Longs piments rouges et fins, forts, à sécher et à réduire en poudre. Très productif.', 'Large_Cayenne.jpg'],
        ['Piment', 'Habanero orange', 'Capsicum chinense', 'PIM03', 5.80, 100, 'graines', 10, 'Petit lampion orange au parfum d\'abricot, extrêmement fort. Pour les amateurs avertis.', 'Orange_habanero.jpg'],
        ['Courge', 'Butternut', 'Cucurbita moschata', 'POT04', 13.15, 100, 'graines', 5, 'Chair orange fondante au goût de noisette, se conserve tout l\'hiver sans frigo.', 'Cucurbita_moschata_Butternut_2012_G2.jpg'],
        ['Courge', 'Potimarron Red Kuri', 'Cucurbita maxima', 'POT16', 14.40, 100, 'graines', 5, 'Goût de châtaigne, peau qui se mange : rien à éplucher, rien à jeter.', 'Cucurbita_maxima_(hokkaido_group)_03.jpg'],
        ['Courge', 'Musquée de Provence', 'Cucurbita moschata', 'POT01', 10.35, 100, 'graines', 5, 'La grande courge côtelée des marchés du Midi, ocre à maturité. Une seule nourrit toute la rue.', 'Cucurbita_moschata_Musquée_de_Provence_-_Une_belle_musquee.jpg'],
        ['Courge', 'Sweet Dumpling (patidou)', 'Cucurbita pepo', 'POT10', 11.25, 100, 'graines', 5, 'Petite courge crème striée de vert, sucrée, parfaite farcie : une par personne.', 'Pumpkin_\'Sweet_Dumpling\'_01.jpg'],
        ['Courge', 'Pâtisson blanc', 'Cucurbita pepo', 'PAT01', 12.00, 100, 'graines', 5, 'La soucoupe blanche festonnée des potagers d\'autrefois, à cueillir jeune.', 'Pattypan_squash_J3.JPG'],
        ['Courge', 'Jack be Little', 'Cucurbita pepo', 'POT13', 13.10, 100, 'graines', 5, 'Mini-citrouilles de 200 g, décoratives et comestibles. Les enfants adorent les récolter.', 'Mini_pumpkins.jpg'],
        ['Courge', 'Delicata', 'Cucurbita pepo', 'POT33', 15.95, 100, 'graines', 5, 'Allongée, crème rayée de vert, chair fine au goût de patate douce. Peau comestible.', 'Cucurbita_pepo_Delicata_squash_Green_Mountain_Girls_Farm.jpg'],
        ['Courge', 'Giraumon turban', 'Cucurbita maxima', 'POT18', 11.40, 100, 'graines', 5, 'Le « bonnet turc » multicolore : spectaculaire au jardin comme sur la table.', 'Turban_squash_J1.jpg'],
        ['Courge', 'Spaghetti', 'Cucurbita pepo', 'POT05', 11.40, 100, 'graines', 5, 'Sa chair cuite se défait en filaments, comme des spaghettis végétaux.', 'Spaghetti_squash_in_a_box.jpg'],
        ['Courgette', 'Ronde de Nice', 'Cucurbita pepo', 'COU01', 8.50, 100, 'graines', 5, 'La petite courgette ronde vert pâle du Midi, à farcir. Précoce et généreuse.', 'Round_zucchini_2017_A3.jpg'],
        ['Concombre', 'Lemon', 'Cucumis sativus', 'CON26', 11.50, 100, 'graines', 5, 'Rond et jaune comme un citron, croquant, doux, jamais amer. Une curiosité de 1894.', 'Lemon_cucumber_J1.JPG'],
        ['Concombre', 'Arménien', 'Cucumis melo var. flexuosus', 'CON14', 14.55, 100, 'graines', 5, 'Long, cannelé, vert tendre : un melon qui se mange en concombre. Adore la chaleur.', 'Armenian_cucumber_1.jpg'],
        ['Melon', 'Charentais précoce du Roc', 'Cucumis melo', 'MEL02', 8.85, 100, 'graines', 5, 'Le vrai charentais à chair orange, parfumé, sélectionné pour mûrir tôt.', 'Charentais_melons.jpg'],
        ['Melon', 'Petit gris de Rennes', 'Cucumis melo', 'MEL01', 8.85, 100, 'graines', 5, 'Variété bretonne du XVIIᵉ siècle, très sucrée, sauvée de l\'oubli par des jardiniers.', 'Melon_"Petit_gris"_du_pays_de_Rennes.jpeg'],
        ['Melon', 'Pastèque Sugar Baby', 'Citrullus lanatus', 'PAS01', 14.50, 100, 'graines', 5, 'Petite pastèque ronde vert sombre, chair rouge très sucrée. Mûrit bien sous nos étés.', '1643Sugar_Baby_Watermelon_02.jpg'],
        ['Racine', 'Betterave de Chioggia', 'Beta vulgaris', 'BET04', 13.20, 50, 'g', 3, 'Italienne du XIXᵉ siècle : chair en cercles roses et blancs, douce, à croquer crue.', 'Betterave_Rossa_di_Chioggia_Montpellier.JPG'],
        ['Racine', 'Carotte nantaise', 'Daucus carota', 'CAR06', 5.70, 5000, 'graines', 300, 'La carotte de référence : cylindrique, sucrée, sans cœur. De quoi semer trois beaux rangs.', 'Carottes_nantaises.jpg'],
        ['Racine', 'Radis Red Meat', 'Raphanus sativus', 'RAD19', 10.10, 1000, 'graines', 60, 'Le radis « pastèque » : blanc dehors, rose vif dedans. Semis d\'été, récolte d\'automne.', 'Rettich-Wassermelonen-Rettich-5-Josef_Schlaghecken.jpg'],
        ['Racine', 'Panais de Guernesey', 'Pastinaca sativa', 'PAN01', 7.75, 1000, 'graines', 80, 'Légume oublié au goût sucré et noisetté, encore meilleur après les premières gelées.', 'Parsnips.JPG'],
        ['Feuille', 'Blette aux cinq couleurs', 'Beta vulgaris var. cicla', 'BLT14', 11.50, 25, 'g', 2, 'Cardes jaunes, roses, rouges, orange et blanches : se récolte feuille à feuille pendant des mois.', 'Rainbow_chard.jpg'],
        ['Feuille', 'Chou Nero di Toscana', 'Brassica oleracea', 'CHO44', 10.05, 1000, 'graines', 50, 'Le chou palmier toscan aux feuilles gaufrées presque noires. Tient tout l\'hiver au jardin.', 'Nero_di_toscana_kale.jpg'],
        ['Feuille', 'Roquette cultivée', 'Eruca sativa', 'ROQ01', 6.20, 10, 'g', 1, 'Prête en quatre semaines, se ressème toute seule : la salade la plus facile qui soit.', 'Eruca_sativa_sl5.jpg'],
        ['Légumineuse', 'Haricot Big Borlotto', 'Phaseolus vulgaris', 'HAR13', 5.10, 250, 'graines', 25, 'Gousses et grains marbrés de rouge. Comme tous les haricots, il enrichit le sol en azote.', 'Borlotti_beans_(4700599465).jpg'],
        ['Légumineuse', 'Pois Blauwschokker', 'Pisum sativum', 'POS13', 12.05, 125, 'g', 12, 'Pois hollandais à fleurs bicolores et gousses violettes. Fixe l\'azote de l\'air : un engrais naturel.', 'Blauwschokker_Kapucijner_rijserwt_Pisum_sativum.jpg'],
        ['Fruit', 'Physalis coqueret du Pérou', 'Physalis peruviana', 'PHY01', 11.40, 1, 'g', 0.1, 'Petites baies dorées et acidulées dans leur lanterne de papier. Un emballage 100 % naturel.', 'Physalis_peruviana_fruits_close-up.jpg'],
        ['Fleur', 'Souci des jardins', 'Calendula officinalis', 'SOU01', 7.05, 5, 'g', 0.5, 'Attire les pollinisateurs et les auxiliaires, éloigne certains ravageurs. Pétales comestibles.', 'Calendula_(Calendula_officinalis).jpg'],
        ['Fleur', 'Bourrache', 'Borago officinalis', 'BOU01', 6.60, 5, 'g', 0.5, 'L\'une des fleurs préférées des abeilles : du nectar du printemps à l\'automne. Fleurs au goût d\'huître.', 'Borago_officinalis_(flower).jpg'],
        ['Fleur', 'Capucine naine', 'Tropaeolum majus', 'CAP01', 6.85, 10, 'g', 1, 'Détourne les pucerons des légumes, couvre le sol, et tout se mange : fleurs, feuilles, boutons.', 'Tropaeolum_majus_2005_G1.jpg'],
        ['Fleur', 'Cosmos Sensation', 'Cosmos bipinnatus', 'COS01', 4.45, 1, 'g', 0.1, 'Grandes fleurs légères tout l\'été : un buffet pour les papillons et les syrphes.', 'Cosmos_bipinnatus_(flowers)_01.jpg'],
        ['Aromatique', 'Basilic Genovese', 'Ocimum basilicum', 'BAS02', 8.15, 10, 'g', 0.5, 'Le basilic du pesto, à grandes feuilles. Planté au pied des tomates, il les protège.', 'Basilico_Genovese_di_Prà-2.jpg'],
        ['Aromatique', 'Basilic rouge Osmin', 'Ocimum basilicum', 'BAS06', 2.90, 2, 'g', 0.2, 'Feuillage pourpre intense, parfum épicé : aussi beau dans l\'assiette que dans le massif.', 'Purple_Basil.jpg'],
        ['Aromatique', 'Aneth', 'Anethum graveolens', 'ANE01', 1.80, 2, 'g', 0.2, 'Ses ombelles jaunes nourrissent coccinelles et syrphes, grands mangeurs de pucerons.', 'Aneth_FR_2012.jpg'],
        ['Aromatique', 'Coriandre', 'Coriandrum sativum', 'COI01', 6.20, 10, 'g', 1, 'Feuilles fraîches, puis graines à moudre : une plante, deux épices.', 'Koriander_(Coriandrum_sativum)_Blätter--_Josef_Schlaghecken.jpg'],
        ['Aromatique', 'Ciboulette', 'Allium schoenoprasum', 'CIB02', 10.20, 10, 'g', 0.5, 'Vivace : semée une fois, récoltée pendant des années. Ses pompons mauves régalent les abeilles.', 'Allium_schoenoprasum_J1.JPG'],
    ];

    /**
     * Installe le catalogue de la saison : met à jour ou crée chaque variété (clé : référence),
     * désactive les autres graines, et remet la liste complète au vote des ventes pas encore ouvertes.
     */
    public static function install(): int
    {
        $db = db();
        $market = (float)$db->val("SELECT market_price FROM varieties WHERE category = 'seeds' AND market_price IS NOT NULL LIMIT 1") ?: Demo::MARKET;
        $keep = [];
        foreach (self::CATALOG as $i => [$fam, $var, $latin, $ref, $ht, $qty, $unit, $portion, $note, $photo]) {
            $row = ['category' => 'seeds', 'name' => $fam, 'variety' => $var, 'latin_name' => $latin, 'note' => mb_substr($note, 0, 255),
                'photo' => 'commons:' . $photo, 'portion_qty' => $portion, 'unit' => $unit,
                'purchase_price' => round($ht * self::VAT, 2), 'purchase_qty' => $qty, 'market_price' => $market,
                'supplier' => self::SUPPLIER, 'supplier_ref' => $ref, 'position' => $i, 'is_active' => 1];
            $id = (int)$db->val("SELECT id FROM varieties WHERE category = 'seeds' AND supplier_ref = ? ORDER BY id LIMIT 1", [$ref]);
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($row)));
                $db->exec("UPDATE varieties SET $set WHERE id = ?", [...array_values($row), $id]);
            } else {
                $id = $db->insert('varieties', $row);
            }
            $keep[] = $id;
        }
        $in = implode(',', $keep);
        $db->exec("UPDATE varieties SET is_active = 0 WHERE category = 'seeds' AND id NOT IN ($in)");
        foreach ($db->col("SELECT id FROM sales WHERE category = 'seeds' AND status IN ('waiting','voting')") as $sid) {
            Varieties::setForSale((int)$sid, $keep);
        }
        return count($keep);
    }
}
