<?php
/**
 * Configuration — Les Jardins d'à Côté
 *
 * Ce fichier est normalement généré automatiquement par l'assistant
 * d'installation (https://votre-domaine/installation).
 * Vous pouvez aussi le copier en "config.php" et le remplir à la main.
 */
return [
    'env'      => 'prod',                       // 'prod' ou 'dev' (dev = erreurs affichées)
    'base_url' => 'https://lesjardinsdacote.fr', // sans slash final
    'app_key'  => 'CHANGEZ-MOI-chaine-aleatoire-de-64-caracteres',
    'cron_key' => 'CHANGEZ-MOI-autre-chaine-aleatoire',

    'db' => [
        'host' => 'localhost',
        'name' => 'u000000000_jdc',
        'user' => 'u000000000_jdc',
        'pass' => '',
    ],

    // Laisser vide tant que le compte Stripe de l'association n'est pas créé :
    // les engagements fonctionnent alors en mode "paiement au retrait".
    'stripe' => [
        'public_key'     => '', // pk_test_... puis pk_live_...
        'secret_key'     => '', // sk_test_... puis sk_live_...
        'webhook_secret' => '', // whsec_...
    ],

    'mail' => [
        'from'        => 'contact@lesjardinsdacote.fr',
        'from_name'   => "Les Jardins d'à Côté",
        // SMTP Hostinger : smtp.hostinger.com, port 465, 'ssl'
        // Laisser smtp_host vide pour utiliser la fonction mail() de PHP.
        'smtp_host'   => 'smtp.hostinger.com',
        'smtp_port'   => 465,
        'smtp_secure' => 'ssl', // 'ssl', 'tls' (STARTTLS) ou ''
        'smtp_user'   => 'contact@lesjardinsdacote.fr',
        'smtp_pass'   => '',
    ],
];
