<?php
/** @var Router $router */
require APP_DIR . '/controllers/front.php';
require APP_DIR . '/controllers/account.php';
require APP_DIR . '/controllers/checkout.php';
require APP_DIR . '/controllers/volunteer.php';
require APP_DIR . '/controllers/admin.php';

/* Vitrine & groupes */
$router->get('/', 'front_home');
$router->get('/univers', 'front_universes');
$router->get('/univers/{slug}', 'front_univers');
$router->get('/groupes', 'front_groups');
$router->get('/groupes/{slug}', 'front_group');
$router->post('/groupes/{slug}/rejoindre', 'front_group_join');
$router->post('/ventes/{id}/voter', 'front_vote');
$router->get('/packs/{slug}', 'front_pack');
$router->get('/lancer-un-groupe', 'front_organizers');
$router->post('/lancer-un-groupe', 'front_organizers_submit');
$router->get('/comment-ca-marche', fn() => front_static('how'));
$router->get('/association', 'front_association');
$router->get('/cgv', fn() => front_static('cgv'));
$router->get('/mentions-legales', fn() => front_static('legal'));
$router->get('/confidentialite', fn() => front_static('privacy'));
$router->get('/credits-photos', 'front_credits');
$router->get('/qr/{token}', 'front_qr_png');
$router->get('/r/{token}', 'front_pass_redirect');
$router->get('/retrait/{token}', 'front_pass');
$router->get('/robots.txt', 'front_robots');
$router->get('/sitemap.xml', 'front_sitemap');
/* Anciennes adresses */
$router->get('/campagnes', fn() => redirect('/groupes', 301));
$router->get('/campagne/{slug}', fn() => redirect('/groupes', 301));
$router->get('/devenir-benevole', fn() => redirect('/groupes', 301));

/* Panier & achat */
$router->get('/panier', 'cart_show');
$router->post('/panier/ajouter', 'cart_add');
$router->post('/panier/modifier', 'cart_update');
$router->get('/commande', 'checkout_show');
$router->post('/commande', 'checkout_submit');
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
$router->get('/benevoles/preparation/{saleId}', 'vol_prep');
$router->post('/benevoles/preparer/{orderId}', 'vol_prep_toggle');
$router->get('/benevoles/preparation/{saleId}/etiquettes', 'vol_labels');
$router->get('/benevoles/distribution', 'vol_distribution');
$router->get('/benevoles/distribution/{token}', 'vol_handover');
$router->post('/benevoles/distribution/{token}', 'vol_handover_confirm');

/* Back-office */
$router->get('/admin', 'admin_dashboard');
$router->get('/admin/groupes', 'admin_groups');
$router->get('/admin/groupes/nouveau', 'admin_group_form');
$router->get('/admin/groupes/{id}', 'admin_group_form');
$router->post('/admin/groupes/enregistrer', 'admin_group_save');
$router->post('/admin/groupes/{id}/action', 'admin_group_action');
$router->get('/admin/ventes', 'admin_sales');
$router->get('/admin/ventes/nouvelle', 'admin_sale_form');
$router->get('/admin/ventes/{id}', 'admin_sale_form');
$router->post('/admin/ventes/enregistrer', 'admin_sale_save');
$router->post('/admin/ventes/{id}/action', 'admin_sale_action');
$router->get('/admin/ventes/{id}/acheteurs.csv', 'admin_sale_csv');
$router->get('/admin/varietes', 'admin_varieties');
$router->get('/admin/varietes/nouvelle', 'admin_variety_form');
$router->get('/admin/varietes/{id}', 'admin_variety_form');
$router->post('/admin/varietes/enregistrer', 'admin_variety_save');
$router->post('/admin/varietes/{id}/supprimer', 'admin_variety_delete');
$router->get('/admin/packs', 'admin_packs');
$router->get('/admin/packs/nouveau', 'admin_pack_form');
$router->get('/admin/packs/{id}', 'admin_pack_form');
$router->post('/admin/packs/enregistrer', 'admin_pack_save');
$router->post('/admin/packs/{id}/supprimer', 'admin_pack_delete');
$router->get('/admin/commandes', 'admin_orders');
$router->get('/admin/commandes/{id}', 'admin_order');
$router->post('/admin/commandes/{id}/action', 'admin_order_action');
$router->get('/admin/creneaux', 'admin_shifts');
$router->post('/admin/creneaux', 'admin_shift_save');
$router->get('/admin/utilisateurs', 'admin_users');
$router->post('/admin/utilisateurs/{id}', 'admin_user_role');
$router->get('/admin/parametres', 'admin_settings');
$router->post('/admin/parametres', 'admin_settings_save');
$router->post('/admin/taches', 'admin_run_tasks');
$router->get('/admin/a-valider', 'admin_validate');
$router->post('/admin/a-valider/{id}', 'admin_validate_decide');
$router->post('/admin/agents/regenerer', 'admin_agents_regenerate');

/* API des agents (tâches planifiées) */
require APP_DIR . '/controllers/api.php';
$router->get('/api/health', 'api_health');
$router->any('/api/agents/claim', 'api_agents_claim');
$router->get('/api/agents/items', 'api_agents_items');
$router->post('/api/agents/items', 'api_agents_items_create');
$router->post('/api/agents/items/{id}/draft', 'api_agents_items_draft');
$router->get('/api/agents/stats', 'api_agents_stats');

$router->get('/cron/{key}', function (string $key) {
    if (!hash_equals((string)config('cron_key'), $key)) abort(404);
    json_out(Tasks::run());
});
