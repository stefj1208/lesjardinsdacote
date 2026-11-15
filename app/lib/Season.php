<?php
declare(strict_types=1);

/**
 * Campagne mise en avant : les graines. Catalogue de variétés bio et reproductibles,
 * chacune avec la photo de la variété elle-même. Les autres univers restent ouverts.
 *
 * Le fournisseur et ses références restent internes (back-office) : rien de ce fichier
 * ne l'affiche au public. Prix relevés au catalogue professionnel en novembre 2026.
 */
final class Season
{
    public const SUPPLIER = 'Agrosemens';
    private const VAT = 1.055;

    /** Univers mis en avant cette saison (tous les univers restent ouverts). */
    public static function featured(string $cat): bool
    {
        return in_array($cat, array_map('trim', explode(',', (string)setting('season_focus', 'seeds'))), true);
    }

    /** Ordre d'affichage des familles dans le catalogue. */
    public const FAMILY_ORDER = ['Tomate', 'Aubergine', 'Poivron', 'Piment doux', 'Piment', 'Courgette', 'Courge', 'Concombre', 'Melon', 'Racine',
        'Salade', 'Feuille', 'Légumineuse', 'Maïs', 'Oignon', 'Fruit', 'Fleur', 'Aromatique'];

    /** Variétés mises en avant sur l'accueil (références internes). */
    public const SHOWCASE = ['TOM31', 'POT10', 'BET04', 'SOU01', 'TOM04', 'POT04', 'PIM14', 'CON26', 'RAD19', 'BLT14', 'BOU01', 'MEL01'];

    /**
     * [famille affichée, variété, nom latin, réf., prix HT du conditionnement, quantité achetée, unité, portion par pack, texte, photo Commons, prix estimé ?]
     * Photos vérifiées une à une : la variété elle-même quand elle existe, sinon une variété du même type (même forme, même couleur).
     * Prix estimé : relevé non disponible pour cette référence, prix déduit de la gamme du semencier (même famille, même conditionnement).
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
        // Saison 2027 : extension du catalogue (prix « estimé » = d'après la gamme du semencier, à confirmer avant commande).
        ['Tomate', 'Ananas', 'Solanum lycopersicum', 'TOM58', 6.40, 100, 'graines', 10, 'Énorme tomate jaune striée de rouge, chair fondante et très sucrée. Une des plus spectaculaires du potager.', 'Tomatensorte_Saveol_Ananas_01_(fcm).jpg', false],
        ['Tomate', 'Amish Paste', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'Tomate des communautés amish : charnue, peu de pépins, idéale en coulis comme en salade.', 'Amish_paste_tomato_(20277230435).jpg', true],
        ['Tomate', 'Poire jaune', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'Petites tomates jaunes en forme de poire, en grappes généreuses. Douces et décoratives.', '"Yellow_Pearshaped".JPG', true],
        ['Tomate', 'Tigrella', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'Rouge rayée d\'orange, précoce et productive. Acidulée, parfaite en salade.', 'Balkoncontent_Tomatensorte_Tigerella.jpg', true],
        ['Tomate', 'Summer Cider', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'Grosse tomate orange à côtes, chair dense et fruitée, très peu acide.', 'Solanum_lycopersicum_Summer-Cider_Košice-BotGar_20210904.jpg', true],
        ['Tomate', 'Cœur de bœuf', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'La tomate en cœur à chair pleine, presque sans pépins. À trancher en carpaccio.', 'Tomates_marque_D\'Albenga.jpg', true],
        ['Tomate', 'Cerise rouge', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'Des centaines de petites tomates sucrées jusqu\'aux gelées. Facile, même en pot.', 'Cherry_tomatoes_(1).jpg', true],
        ['Tomate', 'Roma', 'Solanum lycopersicum', null, 5.45, 100, 'graines', 10, 'La tomate allongée des sauces : ferme, charnue, très productive.', '2_x_Tomate_pera_2017_C.jpg', true],
        ['Tomate', 'Gold Nugget', 'Solanum lycopersicum', 'TOM19', 4.30, 100, 'graines', 10, 'Tomate cerise jaune d\'or, compacte et précoce : parfaite en pot sur un balcon.', 'Hängetomate_\'Gold_Nugget\'_(36308332872).jpg', false],
        ['Tomate', 'Black from Tula', 'Solanum lycopersicum', 'TOM87', 5.45, 100, 'graines', 10, 'Tomate noire de Russie, 200 à 400 g, chair douce et très parfumée.', 'Lučenec_-_Black_from_tula.jpg', true],
        ['Aubergine', 'Zébrina', 'Solanum melongena', 'AUB15', 3.50, 100, 'graines', 10, 'Fruits violets striés de blanc, chair fine sans amertume. Très productive.', 'Stripedeggplant.jpg', true],
        ['Aubergine', 'Blanche ronde à œuf', 'Solanum melongena', 'AUB30', 2.85, 100, 'graines', 10, 'Des aubergines blanches de la taille d\'un œuf : étonnantes et délicieuses grillées.', 'Egg_plant.JPG', false],
        ['Aubergine', 'Rosa Bianca', 'Solanum melongena', 'AUB02', 2.85, 100, 'graines', 10, 'Ronde, blanche nuancée de mauve, chair crémeuse : la préférée des cuisiniers italiens.', 'Variétés_d\'aubergines.JPG', false],
        ['Aubergine', 'Japanese Pickling', 'Solanum melongena', null, 3.50, 100, 'graines', 10, 'Longue et fine, violet foncé, très précoce. Se cuisine sans éplucher.', 'YosriTerungPanjang.jpg', true],
        ['Aubergine', 'Violette de Toulouse', 'Solanum melongena', 'AUB32', 3.50, 100, 'graines', 10, 'La variété de chez nous : longue, violet sombre, adaptée à notre climat.', 'Eggplant_-_ត្រប់សណ្ដាយ.JPG', true],
        ['Piment doux', 'Doux long des Landes', 'Capsicum annuum', null, 5.70, 100, 'graines', 10, 'Le piment doux du Sud-Ouest, long et vert clair, à poêler. Sans aucun piquant.', 'Pimientos_Verdes.jpg', true],
        ['Courge', 'Sucrine du Berry', 'Cucurbita moschata', null, 11.40, 100, 'graines', 5, 'Ancienne variété du Berry, chair orange sucrée et musquée. Se garde tout l\'hiver.', 'Cucurbita_moschata(Sucrine_du_Berry)Cl_J_Weber02_(23651248106).jpg', true],
        ['Courge', 'Longue de Naples', 'Cucurbita moschata', null, 11.40, 100, 'graines', 5, 'Courge géante jusqu\'à 80 cm, chair orange abondante : de quoi régaler tout le quartier.', 'Massive_Squash_~"Long_of_Naples"_~_30_inchs_long.jpg', true],
        ['Courge', 'Potiron Tristar', 'Cucurbita maxima', null, 11.40, 100, 'graines', 5, 'Potiron orange à graines nues : chair pour la soupe, graines à griller.', 'Cucurbita_maxima_02_-_Orange.jpg', true],
        ['Courgette', 'Jaune', 'Cucurbita pepo', 'COU38', 8.00, 100, 'graines', 5, 'Courgette jaune d\'or, tendre et décorative. Se cueille jeune, tous les deux jours.', 'Calabacín,_Múnich,_Alemania,_2013-03-30,_DD_01.JPG', true],
        ['Concombre', 'Cornichon vert petit de Paris', 'Cucumis sativus', null, 11.50, 100, 'graines', 5, 'Le cornichon classique à confire au vinaigre. Une cueillette quotidienne en été.', 'Polish_style_pickled_cucumbers_IMGP0413.jpg', true],
        ['Concombre', 'Tanja', 'Cucumis sativus', null, 11.50, 100, 'graines', 5, 'Long concombre lisse et croquant, sans amertume, à palisser.', 'Cucumis_sativus_0001.JPG', true],
        ['Courge', 'Rouge vif d\'Étampes', 'Cucurbita maxima', null, 11.40, 100, 'graines', 5, 'Le potiron des contes, rouge vif et côtelé, cultivé en France depuis le XIXᵉ siècle.', 'Rouge_vif_d\'Étampes-Cucurbita_maxima-01.jpg', true],
        ['Courge', 'Galeuse d\'Eysines', 'Cucurbita maxima', null, 11.40, 100, 'graines', 5, 'Potiron rose couvert de « verrues » de sucre : plus il en a, plus il est sucré. Une curiosité bordelaise.', 'Cucurbita_maxima_-_Courge_galeuse_d\'Eysines01.jpg', true],
        ['Courge', 'Buttercup', 'Cucurbita maxima', null, 11.40, 100, 'graines', 5, 'Petite courge vert foncé à chair sèche et sucrée, goût de châtaigne.', 'Buttercupsquash.JPG', true],
        ['Courge', 'Trompette d\'Albenga', 'Cucurbita moschata', null, 11.40, 100, 'graines', 5, 'Courge-serpent ligure : jeune en courgette, mûre en courge. Spectaculaire sur une treille.', 'Særa_de_tronbétte.jpg', true],
        ['Courge', 'Marina di Chioggia', 'Cucurbita maxima', null, 11.40, 100, 'graines', 5, 'Courge italienne bosselée vert-gris, chair orange très sucrée, idéale en gnocchis.', 'Marina_Di_Chioggia_squash.jpg', true],
        ['Courgette', 'Black Beauty', 'Cucurbita pepo', 'COU03', 8.00, 100, 'graines', 5, 'La courgette vert foncé classique, productive tout l\'été.', 'Cucurbita_pepo_zucchini_group_\'Black_Beauty\'.jpg', false],
        ['Courgette', 'Ortolana di Faenza', 'Cucurbita pepo', null, 8.00, 100, 'graines', 5, 'Courgette striée de Romagne, chair ferme et goûteuse.', 'CSA-Striped-Zucchini.jpg', true],
        ['Concombre', 'Marketmore', 'Cucumis sativus', null, 11.50, 100, 'graines', 5, 'Concombre vert foncé régulier, résistant et productif en pleine terre.', 'Marketmore-Cuc.jpg', true],
        ['Concombre', 'Mini-concombre à grappes', 'Melothria scabra', null, 11.50, 100, 'graines', 5, 'Minuscules concombres de 3 cm qui ressemblent à des pastèques miniatures. Les enfants adorent.', 'Melothria_scabra_861546.jpg', true],
        ['Melon', 'Jaune canari', 'Cucumis melo', null, 8.85, 100, 'graines', 5, 'Melon allongé jaune vif, chair blanche juteuse et sucrée. Se conserve longtemps.', 'Moscow,_end_of_melon_season,_Oct_2025_20.jpg', true],
        ['Racine', 'Betterave jaune Golden', 'Beta vulgaris', null, 5.05, 10, 'g', 2, 'Betterave à chair jaune d\'or, douce, qui ne tache pas. À croquer râpée.', 'Golden_beets_in_a_pile.jpg', true],
        ['Racine', 'Betterave de Détroit', 'Beta vulgaris', null, 5.05, 10, 'g', 2, 'La betterave rouge ronde classique, régulière et sucrée.', 'Au_marché_-_choux_et_betteraves.jpg', true],
        ['Racine', 'Carotte jaune du Doubs', 'Daucus carota', null, 5.70, 5000, 'graines', 300, 'Ancienne carotte fourragère jaune, rustique, douce en purée.', 'Yellow_carrot_from_Poznan.jpg', true],
        ['Racine', 'Radis rond rouge', 'Raphanus sativus', null, 4.50, 25, 'g', 2, 'Radis rond écarlate, prêt en quatre semaines : la récolte la plus rapide du jardin.', 'Radish-raxe.jpg', true],
        ['Racine', 'Navet plat blanc de mai', 'Brassica rapa', null, 3.50, 10, 'g', 1, 'Navet de printemps plat et tendre, prêt en six semaines.', 'Turnip-ja202211-1.jpg', true],
        ['Racine', 'Céleri-rave', 'Apium graveolens', null, 3.50, 2, 'g', 0.2, 'Grosse boule parfumée pour la rémoulade et les purées d\'hiver.', 'Celery_root_(8047803896).jpg', true],
        ['Racine', 'Fenouil bulbeux', 'Foeniculum vulgare', null, 4.00, 10, 'g', 1, 'Bulbe blanc anisé, à croquer cru ou braiser. Ses fleurs nourrissent les auxiliaires.', 'Au_marché_-_Fenouil.JPG', true],
        ['Racine', 'Chou-rave blanc', 'Brassica oleracea', null, 6.00, 1000, 'graines', 50, 'Boule croquante au goût de chou doux, rapide à pousser.', 'Brassica_oleracea_var._gongylodes_Blankyt_2023-05-10_5083.jpg', true],
        ['Salade', 'Romaine mouchetée', 'Lactuca sativa', null, 4.05, 1000, 'graines', 50, 'Romaine ancienne tachetée de rouge, croquante et décorative.', 'Lactuca_sativa_\'Forellenschluss\',_Longwood_Gardens_2023_01.jpg', true],
        ['Salade', 'Batavia Reine des glaces', 'Lactuca sativa', null, 4.05, 1000, 'graines', 50, 'Feuilles très découpées et croquantes, résiste bien à la chaleur.', 'Lactuca_sativa_\'Regina_dei_Ghiacci\'_kz02.jpg', true],
        ['Salade', 'Laitue Reine de mai', 'Lactuca sativa', null, 4.05, 1000, 'graines', 50, 'Laitue pommée de printemps teintée de rouge, tendre et précoce.', 'Lactuca_sativa_May_Queen_2023-04-24_3352.jpg', true],
        ['Salade', 'Laitue rouge à couper', 'Lactuca sativa', null, 4.05, 1000, 'graines', 50, 'Se récolte feuille à feuille pendant des semaines : une salade, plusieurs récoltes.', 'Lactuca_sativa_Red_Sails_0zz.jpg', true],
        ['Feuille', 'Pak choï', 'Brassica rapa', null, 6.00, 1000, 'graines', 50, 'Chou asiatique à côtes blanches, prêt en 45 jours, délicieux sauté.', 'Brassica_rapa_var._chinensis_(leaf).jpg', true],
        ['Feuille', 'Chou kale rouge frisé', 'Brassica oleracea', null, 6.00, 1000, 'graines', 50, 'Kale pourpre très frisé, superbe au jardin d\'hiver et riche en vitamines.', '190909_059_Chicago_Botanic_Gdn_-_Fruit_Veg_Island,_Brassica_oleracea_\'Starbor\',_Brassica_oleracea_\'Redbor\'_Kale,_Salivia_officinalis_\'Tricolor\'_Sage_(48861627326).jpg', true],
        ['Feuille', 'Chou de Milan', 'Brassica oleracea', null, 6.00, 1000, 'graines', 50, 'Chou cloqué tendre et rustique, résiste au gel. Le chou des potées.', 'Savooi_voor_halogeenlamp.JPG', true],
        ['Feuille', 'Chou cabus Marché de Copenhague', 'Brassica oleracea', null, 6.00, 1000, 'graines', 50, 'Chou pommé rond et serré, précoce, pour la choucroute et les salades.', 'Chou_pommé_01.jpg', true],
        ['Feuille', 'Chou de Bruxelles', 'Brassica oleracea', null, 6.00, 1000, 'graines', 50, 'Le légume d\'hiver par excellence, récolté de novembre à février.', 'Brussels_sprouts_on_the_stalk_closeup.jpg', true],
        ['Salade', 'Chicorée Pain de sucre', 'Cichorium intybus', null, 4.50, 1000, 'graines', 50, 'Grosse chicorée allongée, croquante, qui tient jusqu\'aux gelées.', 'Zuckerhut_Zubereitung,_schneiden-3-Josef_Schlaghecken.jpg', true],
        ['Salade', 'Scarole', 'Cichorium endivia', null, 4.50, 1000, 'graines', 50, 'Salade d\'automne aux larges feuilles, légèrement amère.', 'Cichorium_endivia_-_Botanischer_Garten_Mainz_IMG_5453.JPG', true],
        ['Feuille', 'Oseille', 'Rumex acetosa', null, 3.50, 2, 'g', 0.2, 'Vivace acidulée : semée une fois, récoltée pendant des années.', 'Rumex_acetosa_109003595.jpg', true],
        ['Feuille', 'Rhubarbe Victoria', 'Rheum rhabarbarum', null, 4.50, 5, 'g', 0.5, 'Vivace aux tiges rouges pour tartes et compotes, productive dix ans.', 'Rheum_rhabarbarum.2006-04-27.uellue.jpg', true],
        ['Feuille', 'Artichaut', 'Cynara cardunculus', null, 6.50, 100, 'graines', 10, 'Vivace sculpturale ; ses fleurs bleues sont un festin pour les bourdons.', 'Cynara_cardunculus_var._scolymus_in_Jardin_des_Plante_de_Paris.jpg', true],
        ['Feuille', 'Céleri-branche', 'Apium graveolens', null, 3.50, 2, 'g', 0.2, 'Côtes croquantes pour les salades et les bouillons.', 'Starr_080531-4959_Apium_graveolens.jpg', true],
        ['Légumineuse', 'Haricot d\'Espagne', 'Phaseolus coccineus', null, 6.50, 100, 'graines', 10, 'Grimpant à fleurs rouges, nourrit les abeilles et donne de gros grains.', 'Phaseolus_coccineus_flor2.JPG', true],
        ['Légumineuse', 'Haricot beurre nain', 'Phaseolus vulgaris', null, 5.10, 250, 'graines', 25, 'Gousses jaunes tendres, sans fil. Enrichit le sol en azote.', '07896_Wax_bean_serve_with_melted_butter_and_fried_crumbs,_by_Silar_2011.jpg', true],
        ['Légumineuse', 'Haricot vert nain', 'Phaseolus vulgaris', null, 5.10, 250, 'graines', 25, 'Le haricot vert fin des étés, à cueillir tous les deux jours.', 'Buschbohnen_7129.jpg', true],
        ['Légumineuse', 'Haricot Lingot', 'Phaseolus vulgaris', null, 5.10, 250, 'graines', 25, 'Le haricot blanc du cassoulet, à écosser et à sécher.', 'Judias-Sardina_Rancia.jpg', true],
        ['Légumineuse', 'Petit pois Merveille de Kelvedon', 'Pisum sativum', null, 12.05, 125, 'g', 12, 'Petit pois nain sucré, précoce, sans besoin de rames.', '-2019-07-29_Forming_garden_pea_pods_(Pisum_sativum),_Trimingham.JPG', true],
        ['Légumineuse', 'Fève Aguadulce', 'Vicia faba', null, 6.00, 100, 'graines', 12, 'Fève à très longues cosses, semée à l\'automne ou en février. Engrais vert naturel.', 'Broad_bean_pods_(3).jpg', true],
        ['Maïs', 'Maïs doux Golden Bantam', 'Zea mays', 'MAI01', 4.50, 100, 'graines', 15, 'Variété ancienne de 1902 : épis dorés très sucrés, à griller l\'été.', '\'Golden_Bantam\'_-_Corn_(35373809983).jpg', true],
        ['Oignon', 'Doux des Cévennes', 'Allium cepa', null, 3.50, 2, 'g', 0.3, 'L\'oignon doux des terrasses cévenoles, juteux et sucré, à croquer cru.', 'Oignon_doux_des_Cévennes_1.jpg', true],
        ['Oignon', 'Blanc de Rebouillon', 'Allium cepa', null, 3.50, 2, 'g', 0.3, 'Oignon blanc provençal, précoce, à consommer frais.', 'White_onions_drying.jpg', true],
        ['Oignon', 'Ciboule Ishikura', 'Allium fistulosum', null, 3.50, 2, 'g', 0.3, 'Longue tige blanche et feuillage tendre, se récolte toute l\'année.', 'Allium_fistulosum_MHNT.BOT.2011.3.23.jpg', true],
        ['Oignon', 'Poireau géant d\'hiver', 'Allium porrum', null, 3.50, 2, 'g', 0.3, 'Poireau rustique qui passe l\'hiver en terre, récolté selon les besoins.', 'Hochstadt_Pfalz_field_leeks_512.jpg', true],
        ['Fruit', 'Fraise des bois Reine des vallées', 'Fragaria vesca', null, 4.50, 200, 'graines', 30, 'Petites fraises très parfumées de juin aux gelées, parfaites en bordure.', 'Fragaria_vesca_001.JPG', true],
        ['Fleur', 'Tournesol géant', 'Helianthus annuus', null, 4.00, 50, 'graines', 8, 'Jusqu\'à 3 m de haut : les abeilles en été, les oiseaux en automne.', 'Sunflower_head_2026_G1.jpg', true],
        ['Fleur', 'Zinnia', 'Zinnia elegans', null, 4.00, 1, 'g', 0.2, 'Fleurs vives tout l\'été, adorées des papillons, superbes en bouquet.', 'Zinnienblüte_Zinnia_elegans_stack15_20190722-RM-7222254.jpg', true],
        ['Fleur', 'Bleuet des champs', 'Centaurea cyanus', null, 4.00, 1, 'g', 0.2, 'La fleur des moissons, devenue rare : un refuge pour les pollinisateurs.', 'CentaureaCyanus-bloem-kl.jpg', true],
        ['Fleur', 'Coquelicot', 'Papaver rhoeas', null, 4.00, 1, 'g', 0.2, 'Semé une fois, il revient chaque année. Riche en pollen pour les abeilles.', 'Poster_papaver_3a.jpg', true],
        ['Fleur', 'Pavot de Californie', 'Eschscholzia californica', null, 4.00, 1, 'g', 0.2, 'Fleurs orange soyeuses, résiste à la sécheresse sans arrosage.', 'Abeja_(Lasioglossum_calceatum)_en_una_Amapola_de_California_(Eschscholzia_californica),_Jardín_Botánico,_Múnich,_Alemania,_2013-09-08,_DD_03.JPG', true],
        ['Fleur', 'Phacélie', 'Phacelia tanacetifolia', null, 5.00, 100, 'g', 2, 'La plante préférée des abeilles et un excellent engrais vert pour le sol.', 'Phacelia_tanacetifolia_(KPFC)_01.jpg', true],
        ['Fleur', 'Rudbeckia', 'Rudbeckia hirta', null, 4.00, 1, 'g', 0.2, 'Marguerites dorées au cœur noir, fleuries jusqu\'en octobre.', 'Rudbeckia-443.jpg', true],
        ['Fleur', 'Œillet d\'Inde', 'Tagetes patula', null, 4.00, 1, 'g', 0.2, 'Planté entre les tomates, il éloigne nématodes et aleurodes.', 'French_Marigold_--_October_Birthday_Flower_--_Tagetes_patula.jpg', true],
        ['Fleur', 'Capucine grimpante', 'Tropaeolum majus', null, 4.50, 10, 'g', 1, 'Couvre grillages et talus ; fleurs et feuilles se mangent.', 'Tropaeolum_majus_3zz.jpg', true],
        ['Fleur', 'Pois de senteur', 'Lathyrus odoratus', null, 4.50, 10, 'g', 1, 'Fleurs parfumées pour clôtures et tonnelles, à couper en bouquets.', 'Edelwicke_(Lathyrus_odoratus)_Blüte_focus_stack-20200628-RM-175552.jpg', true],
        ['Fleur', 'Chrysanthème tricolore', 'Glebionis carinata', null, 4.00, 1, 'g', 0.2, 'Marguerites cerclées de trois couleurs, très mellifères.', 'Chrysanthemum_-_Ismelia_carinata_from_lalbagh_1899.JPG', true],
        ['Fleur', 'Ipomée', 'Ipomoea purpurea', null, 4.00, 1, 'g', 0.2, 'Liane aux trompettes violettes qui s\'ouvrent chaque matin.', 'Ipomoea_July_2011-4.jpg', true],
        ['Fleur', 'Achillée millefeuille', 'Achillea millefolium', null, 4.00, 1, 'g', 0.2, 'Vivace sauvage qui attire coccinelles et syrphes, alliées contre les pucerons.', 'Yarrow_(Achillea_millefolium).jpg', true],
        ['Fleur', 'Lin bleu vivace', 'Linum perenne', null, 4.50, 10, 'g', 1, 'Nuage de fleurs bleu ciel, rustique et sobre en eau.', 'Len_vytrvalý_(Linum_perenne),_Praha_–_Přírodní_park_Smetanka.jpg', true],
        ['Fleur', 'Trèfle violet', 'Trifolium pratense', null, 5.00, 100, 'g', 2, 'Engrais vert qui fixe l\'azote et nourrit les bourdons.', 'Trifolium_pratense_-_Keila2.jpg', true],
        ['Fleur', 'Lavande vraie', 'Lavandula angustifolia', null, 4.00, 1, 'g', 0.2, 'Vivace parfumée, aimant soleil et sécheresse : un aimant à abeilles.', 'Lavendel_Mailette_(Lavandula_angustifolia)_Blumengärten_Hirschstetten_Wien_2014_c.jpg', true],
        ['Fleur', 'Millepertuis', 'Hypericum perforatum', null, 4.00, 1, 'g', 0.2, 'Vivace aux fleurs jaunes étoilées, plante sauvage des talus.', '(MHNT)_Hypericum_perforatum_flower_and_buttons.jpg', true],
        ['Aromatique', 'Basilic grec', 'Ocimum basilicum var. minimum', 'BAS10', 2.75, 2, 'g', 0.2, 'Boule compacte à petites feuilles, très parfumée : idéal en pot.', 'Bush_basil_AKA_manjerico.jpg', false],
        ['Aromatique', 'Basilic thaï', 'Ocimum basilicum', 'BAS09', 5.70, 2, 'g', 0.2, 'Tiges pourpres et parfum anisé, indispensable de la cuisine asiatique.', 'Ocimum_basilicum_\'Siam_Queen\'_Bazylia_pospolita_2018-08-12_02.jpg', false],
        ['Aromatique', 'Basilic citron', 'Ocimum × africanum', 'BAS04', 5.70, 2, 'g', 0.2, 'Arôme de citron frais, parfait avec le poisson et les desserts.', 'Young_lemon_basil_plant_(Ocimum_×_africanum).jpg', false],
        ['Aromatique', 'Basilic sacré (Tulsi)', 'Ocimum tenuiflorum', 'BAS25', 7.05, 2, 'g', 0.2, 'Plante sacrée en Inde, parfum de clou de girofle, en tisane.', 'Ocimum_tenuiflorum_3_09_2012.JPG', false],
        ['Aromatique', 'Persil géant d\'Italie', 'Petroselinum crispum', null, 4.00, 10, 'g', 1, 'Persil plat à grandes feuilles, le plus parfumé.', 'Petroselinum_crispum_Gigante_dItalia_2022-05-17_9128.jpg', true],
        ['Aromatique', 'Persil frisé', 'Petroselinum crispum', null, 4.00, 10, 'g', 1, 'Feuilles très frisées, décoratives, qui tiennent l\'hiver.', 'Petroselinum_crispum_Mooskrause_2019-04-30_2287.jpg', true],
        ['Aromatique', 'Cerfeuil', 'Anthriscus cerefolium', null, 4.00, 10, 'g', 1, 'Fines feuilles anisées pour omelettes et salades, pousse à mi-ombre.', 'Kerbel_(Anthriscus_cerefolium_var_cerefolium)_blühend--Josef_Schlaghecken.jpg', true],
        ['Aromatique', 'Thym', 'Thymus vulgaris', 'THY02', 3.85, 1, 'g', 0.2, 'Vivace méditerranéenne, mellifère, sans besoin d\'arrosage.', 'Thymian_(Thymus_vulgaris),_Nationalpark_Hohe_Tauern.jpg', false],
        ['Aromatique', 'Sauge officinale', 'Salvia officinalis', null, 3.50, 1, 'g', 0.2, 'Vivace aux feuilles veloutées, fleurs bleues pour les bourdons.', '20260614_Salvia_officinalis_02.jpg', true],
        ['Aromatique', 'Menthe verte', 'Mentha spicata', null, 3.50, 1, 'g', 0.2, 'La menthe douce du thé et des taboulés, vivace et généreuse.', 'Mint_leaves_(Mentha_spicata).jpg', true],
        ['Aromatique', 'Origan', 'Origanum vulgare', null, 3.50, 1, 'g', 0.2, 'Vivace des pizzas, ses fleurs roses sont prises d\'assaut par les papillons.', 'Origanum_vulgare_inflorescence_-_Keila.jpg', true],
        ['Aromatique', 'Romarin', 'Salvia rosmarinus', null, 3.50, 1, 'g', 0.2, 'Arbrisseau persistant, fleuri en plein hiver : le premier repas des abeilles.', 'Rosmarinus_officinalis133095382.jpg', true],
        ['Aromatique', 'Sarriette vivace', 'Satureja montana', 'SAR02', 1.80, 1, 'g', 0.2, 'L\'herbe des haricots, résistante à la sécheresse.', 'Satureja_montana_kz03.jpg', false],
        ['Aromatique', 'Consoude officinale', 'Symphytum officinale', null, 3.50, 1, 'g', 0.2, 'Vivace dont les feuilles font un purin d\'engrais naturel pour tout le potager.', 'Symphytum_officinale_RF.jpg', true],
        ['Aromatique', 'Agastache anisée', 'Agastache foeniculum', 'AGA01', 4.15, 1, 'g', 0.2, 'Feuilles au goût d\'anis, épis mauves pris d\'assaut par les abeilles.', '(MHNT)_Agastache_foeniculum_-_Inflorecense.jpg', false],
        ['Aromatique', 'Hysope', 'Hyssopus officinalis', 'HYS01', 5.05, 1, 'g', 0.2, 'Vivace mellifère à la saveur mentholée.', 'Hyssopus_officinalis_2_RF.jpg', false],
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
        $order = array_flip(self::FAMILY_ORDER);
        foreach (self::CATALOG as $i => $r) {
            [$fam, $var, $latin, $ref, $ht, $qty, $unit, $portion, $note, $photo] = $r;
            $pos = ($order[$fam] ?? 50) * 1000 + $i;
            $est = (bool)($r[10] ?? false);
            $row = ['category' => 'seeds', 'name' => $fam, 'variety' => $var, 'latin_name' => $latin, 'note' => mb_substr($note, 0, 255),
                'photo' => 'commons:' . $photo, 'portion_qty' => $portion, 'unit' => $unit,
                'purchase_price' => round($ht * self::VAT, 2), 'purchase_qty' => $qty, 'market_price' => $market,
                'supplier' => self::SUPPLIER . ($est ? ' (prix estimé d\'après la gamme, à confirmer)' : ''), 'supplier_ref' => $ref, 'position' => $pos, 'is_active' => 1];
            $id = $ref
                ? (int)$db->val("SELECT id FROM varieties WHERE category = 'seeds' AND supplier_ref = ? ORDER BY id LIMIT 1", [$ref])
                : (int)$db->val("SELECT id FROM varieties WHERE category = 'seeds' AND name = ? AND variety = ? ORDER BY id LIMIT 1", [$fam, $var]);
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
