<?php
declare(strict_types=1);

/**
 * Données de démonstration : un fournisseur, 8 semences avec grilles tarifaires
 * d'exemple, des campagnes à différents stades et des créneaux bénévoles.
 * Les tarifs sont fictifs : remplacez-les par la grille pro Agrosemens 2027.
 */
final class Demo
{
    public static function seed(): void
    {
        $db = db();
        $supplierId = $db->insert('suppliers', [
            'name' => 'Agrosemens (démo)', 'catalog_url' => 'https://www.agrosemens.com/catalogue-2027.html',
            'min_order_franco_ht' => 350, 'shipping_cost_ht' => 18, 'lead_time_days' => 6,
            'contact_email' => 'pro@exemple.test', 'notes' => 'Tarifs fictifs pour la démonstration.',
        ]);
        set_setting('demo_supplier_id', (string)$supplierId);

        $windowId = $db->insert('pickup_windows', [
            'label' => 'Grande distribution de printemps', 'location' => 'Boutique Les Jardins d\'à Côté',
            'address' => '17 boulevard d\'Arcole, 31000 Toulouse',
            'starts_at' => date('Y-m-d 10:00:00', strtotime('+26 days')), 'ends_at' => date('Y-m-d 13:00:00', strtotime('+26 days')),
        ]);
        $batchId = $db->insert('supplier_batches', ['supplier_id' => $supplierId, 'name' => 'Commande Agrosemens — printemps', 'franco_ht' => 350]);

        $catalogue = [
            ['DEMO-TOM-CDB', 'Tomate', 'Cœur de Bœuf', 'Solanum lycopersicum', 'Solanacées', 'seeds', 3.2, 'sun', 'high', 'medium', '2,3,4', '0,5 cm',
             'Grosse tomate charnue côtelée, peu de graines, saveur douce. Variété de population reproductible.',
             'Semer au chaud (20-25 °C) en godets de février à avril, repiquer en pleine terre après les Saints de Glace.',
             [['Sachet 250 graines', 250, 6.10], ['Sac 1000 graines', 1000, 17.90], ['Sac 5000 graines', 5000, 66.00]],
             '25,50,100', 25, 0.62, 12, 14, 3.50],
            ['DEMO-BAS-GV', 'Basilic', 'Grand Vert', 'Ocimum basilicum', 'Lamiacées', 'g', 1.7, 'sun', 'medium', 'easy', '3,4,5,6', '0,2 cm',
             'Le basilic génois classique, très aromatique, grandes feuilles. Idéal pesto.',
             'Semer en surface au chaud dès mars, en pleine terre à partir de mai. Pincer les fleurs.',
             [['Sachet 10 g', 10, 4.20], ['Sac 50 g', 50, 13.50], ['Sac 250 g', 250, 49.00], ['Sac 1 kg', 1000, 160.00]],
             '2,5,10', 2, 0.85, 21, 10, 2.90],
            ['DEMO-CAR-NAN', 'Carotte', 'de Nantes 2', 'Daucus carota', 'Apiacées', 'g', 1.1, 'sun', 'medium', 'easy', '3,4,5,6,7', '1 cm',
             'Racine cylindrique, sucrée, sans cœur. La carotte de référence pour tout le monde.',
             'Semer en ligne de mars à juillet dans une terre légère, éclaircir à 5 cm.',
             [['Sachet 25 g', 25, 5.40], ['Sac 100 g', 100, 14.80], ['Sac 500 g', 500, 52.00]],
             '5,10,25', 5, 0.20, 6, 7, 2.60],
            ['DEMO-RAD-FB', 'Radis', '18 jours', 'Raphanus sativus', 'Brassicacées', 'g', 8.5, 'partial', 'medium', 'easy', '3,4,5,6,7,8,9', '1 cm',
             'Radis demi-long rose à bout blanc, très rapide : récolte en trois semaines.',
             'Semer en place toutes les deux semaines de mars à septembre, arroser régulièrement.',
             [['Sachet 50 g', 50, 4.30], ['Sac 250 g', 250, 12.00], ['Sac 1 kg', 1000, 34.00]],
             '10,25,50', 10, 0.95, 18, 3, 2.40],
            ['DEMO-HAR-CON', 'Haricot nain', 'Contender', 'Phaseolus vulgaris', 'Fabacées', 'g', 330, 'sun', 'medium', 'easy', '5,6,7', '3 cm',
             'Haricot vert filet sans fil, productif et précoce, résistant aux maladies.',
             'Semer en poquets de 4-5 graines quand la terre est réchauffée (mai), espacés de 40 cm.',
             [['Sac 250 g', 250, 7.50], ['Sac 1 kg', 1000, 19.00], ['Sac 5 kg', 5000, 75.00]],
             '50,100,250', 50, 0.35, 9, 18, 3.20],
            ['DEMO-COU-NDN', 'Courgette', 'Noire maraîchère', 'Cucurbita pepo', 'Cucurbitacées', 'seeds', 150, 'sun', 'high', 'easy', '4,5,6', '2 cm',
             'Courgette longue vert foncé, très productive, port buissonnant.',
             'Semer en godets en avril ou en place en mai, 2 graines par poquet.',
             [['Sachet 100 graines', 100, 6.00], ['Sac 500 graines', 500, 21.00], ['Sac 2500 graines', 2500, 85.00]],
             '10,20,50', 10, 0.70, 4, 21, 3.30],
            ['DEMO-LAI-MER', 'Laitue', 'Merveille des 4 saisons', 'Lactuca sativa', 'Astéracées', 'g', 1.0, 'partial', 'medium', 'easy', '2,3,4,5,6,7,8,9', '0,5 cm',
             'Laitue pommée à bords rouges, résistante au froid comme à la chaleur.',
             'Semer en pépinière ou en place de février à septembre, repiquer à 30 cm.',
             [['Sachet 10 g', 10, 5.00], ['Sac 50 g', 50, 15.00], ['Sac 250 g', 250, 52.00]],
             '1,2,5', 1, 0.15, 8, 2, 2.50],
            ['DEMO-PHA-ENG', 'Phacélie', 'engrais vert', 'Phacelia tanacetifolia', 'Boraginacées', 'g', 2.0, 'sun', 'low', 'easy', '3,4,5,6,7,8,9', 'à la volée',
             'Engrais vert mellifère : structure le sol, attire les pollinisateurs, se broie avant floraison complète.',
             'Semer à la volée (2 g/m²) de mars à septembre, ratisser et tasser.',
             [['Sac 250 g', 250, 6.00], ['Sac 1 kg', 1000, 15.00], ['Sac 5 kg', 5000, 55.00]],
             '25,50,100', 25, 0.40, 15, 1, 3.90],
        ];

        // Voisins fictifs pour animer la démonstration
        $users = [];
        $names = [['Camille', 'Arnaud'], ['Yanis', 'Benoît'], ['Léa', 'Cazes'], ['Hugo', 'Delmas'], ['Inès', 'Esteve'], ['Malik', 'Fabre'],
                  ['Chloé', 'Garcia'], ['Nathan', 'Huc'], ['Sarah', 'Izard'], ['Théo', 'Jourdan'], ['Manon', 'Lacoste'], ['Rayan', 'Moreau']];
        foreach ($names as $i => [$f, $l]) {
            $users[] = $db->insert('users', ['email' => 'voisin' . ($i + 1) . '@exemple.test', 'password_hash' => password_hash(random_token(), PASSWORD_DEFAULT),
                'first_name' => $f, 'last_name' => $l, 'role' => 'client']);
        }

        $margin = 0.15; $waste = 0.04; $packet = 0.08; $vat = 0.0;
        foreach ($catalogue as $k => $p) {
            [$sku, $name, $variety, $latin, $family, $unit, $pmg, $expo, $water, $diff, $months, $depth, $desc, $advice, $packs, $portions, $ref, $fill, $endDays, $ship, $publicPart] = $p;
            $pid = $db->insert('products', [
                'supplier_id' => $supplierId, 'sku_supplier' => $sku, 'category' => 'seeds', 'name' => $name, 'variety' => $variety,
                'latin_name' => $latin, 'family' => $family, 'description' => $desc, 'sowing_advice' => $advice, 'sowing_months' => $months,
                'sowing_depth' => $depth, 'exposure' => $expo, 'water' => $water, 'difficulty' => $diff, 'is_organic' => 1,
                'unit' => $unit, 'pmg' => $unit === 'g' ? $pmg : ($pmg ?: null),
                'source_url' => 'https://www.agrosemens.com/catalogue-2027.html',
            ]);
            foreach ($packs as [$lbl, $q, $cost]) $db->insert('product_packs', ['product_id' => $pid, 'label' => $lbl, 'qty' => $q, 'cost_ht' => $cost]);

            $params = ['ref_portion' => $ref, 'vat_rate' => $vat, 'margin_rate' => $margin, 'waste_coeff' => $waste, 'packet_cost' => $packet, 'shipping_share_ht' => round($ship * 0.3, 2)];
            $packRows = array_map(fn($x) => ['label' => $x[0], 'qty' => $x[1], 'cost_ht' => $x[2]], $packs);
            $gen = Pricing::generateTiers($packRows, $params + ['include_smallest' => count($packs) < 3]);
            $title = $name . ' ' . $variety;
            $cid = $db->insert('campaigns', [
                'product_id' => $pid, 'supplier_batch_id' => $k < 6 ? $batchId : null, 'pickup_window_id' => $windowId,
                'slug' => Campaigns::uniqueSlug($title), 'title' => $title,
                'pitch' => $k === 0 ? 'La star du potager toulousain, à semer dès février.' : null,
                'trigger_mode' => 'volume', 'status' => 'active',
                'start_at' => date('Y-m-d H:i:s', strtotime('-6 days')), 'end_at' => date('Y-m-d 20:00:00', strtotime("+$endDays days")),
                'ref_portion' => $ref, 'portion_options' => $portions, 'public_unit_price' => round($publicPart / $ref, 5),
                'shipping_share_ht' => $params['shipping_share_ht'], 'waste_coeff' => $waste, 'packet_cost' => $packet,
                'margin_rate' => $margin, 'vat_rate' => $vat, 'packed_year' => (int)date('Y'), 'is_featured' => $k === 0 ? 1 : 0,
            ]);
            foreach ($gen['tiers'] as $t) $db->insert('campaign_tiers', ['campaign_id' => $cid] + $t);

            // Réservations fictives pour atteindre le taux de remplissage voulu
            $tiers = Campaigns::tiers($cid);
            $target = $tiers ? (float)$tiers[count($tiers) - 1]['min_qty'] * $fill : 0;
            $opts = Units::portions($portions);
            $reserved = 0; $u = 0;
            while ($reserved < $target && $u < 40) {
                $portion = $opts[$u % count($opts)];
                $n = 1 + ($u % 3);
                $userId = $users[$u % count($users)];
                $oid = $db->insert('orders', ['reference' => 'DEMO-' . $cid . '-' . $u, 'user_id' => $userId, 'pickup_window_id' => $windowId,
                    'payment_mode' => 'on_pickup', 'pickup_token' => random_token(16), 'pickup_code' => strtoupper(substr(random_token(4), 0, 6)),
                    'bin_code' => mb_substr($names[$u % count($names)][1], 0, 1), 'cgv_accepted_at' => now()]);
                $price = $tiers[0]['unit_price_ttc'] ?? 0;
                $db->insert('order_items', ['order_id' => $oid, 'campaign_id' => $cid, 'portion_qty' => $portion, 'packets' => $n,
                    'quantity' => $portion * $n, 'unit_price_commit' => $price]);
                $db->exec('UPDATE orders SET amount_committed = ? WHERE id = ?', [Pricing::packetPrice((float)$price, $portion) * $n, $oid]);
                $reserved += $portion * $n; $u++;
            }
            $r = Campaigns::refresh($cid);
            $db->exec('UPDATE campaigns SET notified_tier = ? WHERE id = ?', [$r['new_tier'], $cid]);
        }

        // Créneaux bénévoles
        $shifts = [
            ['unloading', 'Réception commande Agrosemens', '+22 days 09:00', 2, 2],
            ['packing', 'Atelier pesée & ensachage graines', '+23 days 10:00', 3, 4],
            ['packing', 'Atelier pesée & ensachage (soirée)', '+24 days 18:30', 2.5, 3],
            ['distribution', 'Permanence distribution boutique', '+26 days 10:00', 3, 3],
        ];
        foreach ($shifts as [$type, $title, $when, $hours, $need]) {
            $start = strtotime($when);
            $db->insert('shifts', ['type' => $type, 'title' => $title, 'location' => '17 boulevard d\'Arcole, 31000 Toulouse',
                'starts_at' => date('Y-m-d H:i:s', $start), 'ends_at' => date('Y-m-d H:i:s', (int)($start + $hours * 3600)), 'volunteers_needed' => $need,
                'description' => $type === 'packing' ? 'Balance de précision, sachets kraft et étiquettes fournis. Venez avec votre bonne humeur.' : null]);
        }
        set_setting('demo_data', '1');
    }

    /** Supprime toutes les données de démonstration. */
    public static function purge(): void
    {
        $sid = (int)setting('demo_supplier_id', 0);
        db()->tx(function (DB $db) use ($sid) {
            $db->exec("DELETE oi FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.user_id WHERE u.email LIKE '%@exemple.test'");
            $db->exec("DELETE o FROM orders o JOIN users u ON u.id = o.user_id WHERE u.email LIKE '%@exemple.test'");
            $db->exec("DELETE FROM users WHERE email LIKE '%@exemple.test'");
            if ($sid) {
                $camps = $db->col('SELECT c.id FROM campaigns c JOIN products p ON p.id = c.product_id WHERE p.supplier_id = ?', [$sid]);
                if ($camps) {
                    $in = implode(',', array_map('intval', $camps));
                    $db->exec("DELETE oi FROM order_items oi WHERE oi.campaign_id IN ($in)");
                    $db->exec("DELETE FROM orders WHERE id NOT IN (SELECT DISTINCT order_id FROM order_items)");
                    $db->exec("UPDATE shifts SET campaign_id = NULL WHERE campaign_id IN ($in)");
                    $db->exec("DELETE FROM campaigns WHERE id IN ($in)");
                }
                $db->exec('DELETE FROM products WHERE supplier_id = ?', [$sid]);
                $db->exec('DELETE FROM supplier_batches WHERE supplier_id = ?', [$sid]);
                $db->exec('DELETE FROM suppliers WHERE id = ?', [$sid]);
            }
            $db->exec("DELETE FROM shifts WHERE starts_at > NOW() AND id NOT IN (SELECT shift_id FROM shift_signups)");
        });
        set_setting('demo_data', '0');
        set_setting('demo_supplier_id', '');
    }
}
