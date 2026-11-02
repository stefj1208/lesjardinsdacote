<?php
/**
 * Assistant d'installation : crée config.php, la base, le compte administrateur
 * et (optionnellement) les données de démonstration. Se verrouille ensuite.
 */
$errors = [];
$cfg = $GLOBALS['CONFIG'] ?? [];
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$defaults = [
    'base_url' => $cfg['base_url'] ?? ($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
    'db_host' => $cfg['db']['host'] ?? 'localhost',
    'db_name' => $cfg['db']['name'] ?? '',
    'db_user' => $cfg['db']['user'] ?? '',
    'admin_first' => '', 'admin_last' => '', 'admin_email' => '',
    'mail_from' => $cfg['mail']['from'] ?? '',
];

if (is_file(APP_DIR . '/storage/installed.lock')) { header('Location: /'); exit; }
if (!is_writable(APP_DIR) || !is_dir(APP_DIR . '/storage') || !is_writable(APP_DIR . '/storage')) {
    $errors[] = 'Le dossier « app/storage » (et « app ») doit être accessible en écriture par PHP.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$errors) {
    if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) $errors[] = 'Session expirée, rechargez la page.';
    $in = fn($k) => trim((string)($_POST[$k] ?? ''));
    foreach (array_keys($defaults) as $k) $defaults[$k] = $in($k);
    $pass = (string)($_POST['admin_pass'] ?? '');
    if (!filter_var($defaults['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email administrateur invalide.';
    if (mb_strlen($pass) < 10) $errors[] = 'Le mot de passe administrateur doit faire au moins 10 caractères.';
    if (!preg_match('#^https?://[^/]+$#', rtrim($defaults['base_url'], '/'))) $errors[] = 'Adresse du site invalide (ex. https://lesjardinsdacote.fr).';

    if (!$errors) {
        try {
            $GLOBALS['CONFIG'] = [
                'env' => 'prod',
                'base_url' => rtrim($defaults['base_url'], '/'),
                'app_key' => random_token(32),
                'cron_key' => random_token(16),
                'db' => ['host' => $defaults['db_host'], 'name' => $defaults['db_name'], 'user' => $defaults['db_user'], 'pass' => (string)($_POST['db_pass'] ?? ($cfg['db']['pass'] ?? ''))],
                'stripe' => $cfg['stripe'] ?? ['public_key' => '', 'secret_key' => '', 'webhook_secret' => ''],
                'mail' => $cfg['mail'] ?? ['from' => $defaults['mail_from'] ?: $defaults['admin_email'], 'from_name' => "Les Jardins d'à Côté",
                    'smtp_host' => '', 'smtp_port' => 465, 'smtp_secure' => 'ssl', 'smtp_user' => '', 'smtp_pass' => ''],
            ];
            if ($defaults['mail_from']) $GLOBALS['CONFIG']['mail']['from'] = $defaults['mail_from'];
            db()->pdo;
            Migrator::execFile(APP_DIR . '/sql/schema.sql');
            set_setting('schema_version', (string)Migrator::VERSION);
            $email = mb_strtolower($defaults['admin_email']);
            if (db()->val('SELECT id FROM users WHERE email = ?', [$email])) {
                db()->exec("UPDATE users SET role = 'admin', password_hash = ? WHERE email = ?", [password_hash($pass, PASSWORD_DEFAULT), $email]);
            } else {
                db()->insert('users', ['email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                    'first_name' => $defaults['admin_first'], 'last_name' => $defaults['admin_last'], 'role' => 'admin']);
            }
            set_setting('contact_email', $email);
            Demo::seedCatalog();
            if (!empty($_POST['demo']) && !db()->val("SELECT id FROM `groups` LIMIT 1")) Demo::seed();

            $export = "<?php\n// Généré par l'assistant d'installation le " . date('d/m/Y H:i') . "\nreturn " . var_export($GLOBALS['CONFIG'], true) . ";\n";
            if (file_put_contents(APP_DIR . '/config.php', $export) === false) throw new RuntimeException('Impossible d\'écrire app/config.php');
            @chmod(APP_DIR . '/config.php', 0640);
            file_put_contents(APP_DIR . '/storage/installed.lock', date('c'));
            $_SESSION['uid'] = (int)db()->val('SELECT id FROM users WHERE email = ?', [$email]);
            $_SESSION['_flash'][] = ['type' => 'success', 'message' => 'Installation terminée ! Bienvenue dans votre back-office.'];
            header('Location: /admin');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Connexion à la base impossible : ' . $e->getMessage();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
$csrf = csrf_token();
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation · Les Jardins d'à Côté</title><link rel="stylesheet" href="/assets/css/app.css"><meta name="robots" content="noindex"></head>
<body>
<header class="site-head"><div class="wrap"><span class="brand"><?php include APP_DIR . '/views/partials/logo.php' ?><span class="brand-name">Les Jardins <em>d'à Côté</em></span></span></div></header>
<main class="wrap page" style="max-width:760px">
  <h1>Installation de la plateforme</h1>
  <p class="muted">Créez d'abord une base MySQL dans hPanel (Bases de données → Gestion), puis remplissez ce formulaire. Il ne s'affichera plus une fois l'installation terminée.</p>
  <?php foreach ($errors as $er): ?><div class="flash flash--error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="panel" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <h2>Le site</h2>
    <div class="field"><label for="base_url">Adresse du site</label><input id="base_url" name="base_url" type="url" required value="<?= e($defaults['base_url']) ?>"><span class="hint">Sans barre finale, en https une fois le certificat SSL activé.</span></div>
    <div class="field"><label for="mail_from">Adresse d'envoi des emails</label><input id="mail_from" name="mail_from" type="email" value="<?= e($defaults['mail_from']) ?>" placeholder="contact@lesjardinsdacote.fr"><span class="hint">Une boîte créée dans hPanel → Emails. Les réglages SMTP se complètent ensuite dans app/config.php.</span></div>
    <h2 class="mt-6">Base de données MySQL</h2>
    <div class="grid-2">
      <div class="field"><label for="db_host">Serveur</label><input id="db_host" name="db_host" required value="<?= e($defaults['db_host']) ?>"></div>
      <div class="field"><label for="db_name">Nom de la base</label><input id="db_name" name="db_name" required value="<?= e($defaults['db_name']) ?>" placeholder="u123456789_jdc"></div>
      <div class="field"><label for="db_user">Utilisateur</label><input id="db_user" name="db_user" required value="<?= e($defaults['db_user']) ?>"></div>
      <div class="field"><label for="db_pass">Mot de passe</label><input id="db_pass" name="db_pass" type="password"></div>
    </div>
    <h2 class="mt-6">Compte administrateur</h2>
    <div class="grid-2">
      <div class="field"><label for="admin_first">Prénom</label><input id="admin_first" name="admin_first" required value="<?= e($defaults['admin_first']) ?>"></div>
      <div class="field"><label for="admin_last">Nom</label><input id="admin_last" name="admin_last" value="<?= e($defaults['admin_last']) ?>"></div>
      <div class="field"><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" required value="<?= e($defaults['admin_email']) ?>"></div>
      <div class="field"><label for="admin_pass">Mot de passe</label><input id="admin_pass" name="admin_pass" type="password" minlength="10" required><span class="hint">10 caractères minimum.</span></div>
    </div>
    <label class="check mt-4"><input type="checkbox" name="demo" value="1" checked> Charger les données de démonstration (packs avec photos, 4 groupes à différents stades). Supprimables en un clic depuis le back-office.</label>
    <p class="mt-6"><button class="btn btn--lg" type="submit">Installer la plateforme</button></p>
  </form>
</main>
</body></html>
