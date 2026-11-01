<?php
/** @var Router $router */
require APP_DIR . '/controllers/front.php';
require APP_DIR . '/controllers/account.php';
require APP_DIR . '/controllers/checkout.php';
require APP_DIR . '/controllers/volunteer.php';
require APP_DIR . '/controllers/admin.php';

/* Public */
$router->get('/', 'front_home');
$router->get('/campagnes', 'front_catalogue');
$router->get('/campagne/{slug}', 'front_campaign');
$router->get('/api/campagnes', 'front_api_campaigns');
$router->get('/api/campagne/{id}', 'front_api_campaign');
$router->get('/og/{slug}', 'front_og_image');
$router->get('/c/{id}', 'front_short_campaign');
$router->get('/comment-ca-marche', fn() => front_static('how'));
$router->get('/devenir-benevole', 'front_volunteer_info');
$router->post('/devenir-benevole', 'front_volunteer_apply');
$router->get('/cgv', fn() => front_static('cgv'));
$router->get('/mentions-legales', fn() => front_static('legal'));
$router->get('/confidentialite', fn() => front_static('privacy'));
$router->get('/qr/{token}', 'front_qr_png');
$router->get('/r/{token}', 'front_pass_redirect');
$router->get('/retrait/{token}', 'front_pass');
$router->get('/robots.txt', 'front_robots');
$router->get('/sitemap.xml', 'front_sitemap');

/* Panier & engagement */
$router->get('/panier', 'cart_show');
$router->post('/panier/ajouter', 'cart_add');
$router->post('/panier/modifier', 'cart_update');
$router->get('/engagement', 'checkout_show');
$router->post('/engagement', 'checkout_submit');
$router->post('/stripe/webhook', 'stripe_webhook');

/* Compte */
$router->get('/connexion', 'account_login_form');
$router->post('/connexion', 'account_login');
$router->get('/inscription', 'account_register_form');
$router->post('/inscription', 'account_register');
$router->post('/deconnexion', 'account_logout');
$router->get('/mot-de-passe-oublie', 'account_forgot_form');
$router->post('/mot-de-passe-oublie', 'account_forgot');
$router->get('/reinitialiser/{token}', 'account_reset_form');
$router->post('/reinitialiser/{token}', 'account_reset');
$router->get('/compte', 'account_home');
$router->get('/compte/profil', 'account_profile_form');
$router->post('/compte/profil', 'account_profile');
$router->get('/compte/commande/{ref}', 'account_order');
$router->post('/compte/commande/{ref}/annuler', 'account_order_cancel');
$router->get('/compte/commande/{ref}/facture', 'account_invoice');
$router->get('/compte/paiement/{ref}', 'account_pay_form');
$router->post('/compte/paiement/{ref}', 'account_pay_confirm');

/* Bénévoles */
$router->get('/benevoles', 'vol_home');
$router->get('/benevoles/planning', 'vol_planning');
$router->post('/benevoles/planning/{id}', 'vol_signup');
$router->get('/benevoles/planning/{id}/ics', 'vol_shift_ics');
$router->get('/benevoles/atelier', 'vol_workshops');
$router->get('/benevoles/atelier/{id}', 'vol_workshop');
$router->post('/benevoles/atelier/{id}/lot', 'vol_workshop_lot');
$router->post('/benevoles/ensacher/{itemId}', 'vol_pack_item');
$router->post('/benevoles/atelier/{id}/termine', 'vol_workshop_done');
$router->get('/benevoles/atelier/{id}/etiquettes', 'vol_labels');
$router->get('/benevoles/distribution', 'vol_distribution');
$router->get('/benevoles/distribution/{token}', 'vol_handover');
$router->post('/benevoles/distribution/{token}', 'vol_handover_confirm');

/* Back-office */
$router->get('/admin', 'admin_dashboard');
$router->get('/admin/campagnes', 'admin_campaigns');
$router->get('/admin/campagnes/nouvelle', 'admin_campaign_form');
$router->get('/admin/campagnes/{id}', 'admin_campaign_form');
$router->post('/admin/campagnes/enregistrer', 'admin_campaign_save');
$router->post('/admin/campagnes/{id}/action', 'admin_campaign_action');
$router->get('/admin/campagnes/{id}/participants.csv', 'admin_campaign_participants_csv');
$router->post('/admin/api/paliers', 'admin_api_tiers');
$router->get('/admin/bon-de-commande', 'admin_purchase_order');
$router->get('/admin/produits', 'admin_products');
$router->get('/admin/produits/nouveau', 'admin_product_form');
$router->get('/admin/produits/{id}', 'admin_product_form');
$router->post('/admin/produits/enregistrer', 'admin_product_save');
$router->post('/admin/produits/{id}/supprimer', 'admin_product_delete');
$router->get('/admin/import', 'admin_import_form');
$router->post('/admin/import', 'admin_import_preview');
$router->post('/admin/import/confirmer', 'admin_import_confirm');
$router->get('/admin/import/modele.csv', 'admin_import_template');
$router->get('/admin/fournisseurs', 'admin_suppliers');
$router->post('/admin/fournisseurs', 'admin_supplier_save');
$router->get('/admin/paniers-fournisseur', 'admin_batches');
$router->post('/admin/paniers-fournisseur', 'admin_batch_save');
$router->get('/admin/commandes', 'admin_orders');
$router->get('/admin/commandes/{id}', 'admin_order');
$router->post('/admin/commandes/{id}/action', 'admin_order_action');
$router->get('/admin/creneaux', 'admin_shifts');
$router->post('/admin/creneaux', 'admin_shift_save');
$router->get('/admin/retraits', 'admin_windows');
$router->post('/admin/retraits', 'admin_window_save');
$router->get('/admin/utilisateurs', 'admin_users');
$router->post('/admin/utilisateurs/{id}', 'admin_user_role');
$router->get('/admin/parametres', 'admin_settings');
$router->post('/admin/parametres', 'admin_settings_save');
$router->post('/admin/taches', 'admin_run_tasks');

/* Cron via URL (si l'hébergeur ne permet pas la ligne de commande) */
$router->get('/cron/{key}', function (string $key) {
    if (!hash_equals((string)config('cron_key'), $key)) abort(404);
    json_out(Tasks::run());
});
