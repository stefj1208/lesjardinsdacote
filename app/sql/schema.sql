-- =====================================================================
-- Les Jardins d'à Côté — schéma v5 (groupes locaux, vote des variétés, packs composés, lots, prix marché)
-- MySQL / MariaDB, InnoDB, utf8mb4
-- =====================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(80) NOT NULL DEFAULT '',
  last_name VARCHAR(80) NOT NULL DEFAULT '',
  phone VARCHAR(30) NULL,
  postal_code VARCHAR(10) NULL,
  role ENUM('client','volunteer','admin') NOT NULL DEFAULT 'client',
  wants_volunteer TINYINT(1) NOT NULL DEFAULT 0,
  notify_tiers TINYINT(1) NOT NULL DEFAULT 1,
  stripe_customer_id VARCHAR(64) NULL,
  reset_token_hash CHAR(64) NULL,
  reset_expires_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(190) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_la_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groupes locaux : « Les Jardins d'à Côté Pins-Justaret », « … Pinsaguel »
CREATE TABLE IF NOT EXISTS `groups` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  commune VARCHAR(120) NOT NULL,
  postal_code VARCHAR(10) NULL,
  description TEXT NULL,
  organizer_type ENUM('collectivite','entreprise','association','jardin_partage','habitants','autre') NOT NULL DEFAULT 'habitants',
  organizer_name VARCHAR(160) NULL,
  contact_name VARCHAR(120) NULL,
  contact_email VARCHAR(190) NULL,
  contact_phone VARCHAR(30) NULL,
  pickup_place VARCHAR(160) NULL,
  pickup_address VARCHAR(255) NULL,
  photo VARCHAR(500) NULL,
  volunteers_needed TINYINT UNSIGNED NOT NULL DEFAULT 2,
  status ENUM('proposed','active','archived') NOT NULL DEFAULT 'proposed',
  proposed_by INT UNSIGNED NULL,
  validated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_groups_slug (slug),
  KEY idx_groups_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_members (
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('member','volunteer') NOT NULL DEFAULT 'member',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, user_id),
  KEY idx_gm_user (user_id),
  CONSTRAINT fk_gm_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  CONSTRAINT fk_gm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogue de packs (réutilisables d'un groupe à l'autre)
CREATE TABLE IF NOT EXISTS packs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL,
  category ENUM('seeds','vegetables','fruit_trees','aromatics','plants') NOT NULL DEFAULT 'seeds',
  name VARCHAR(160) NOT NULL,
  tagline VARCHAR(255) NULL,
  description TEXT NULL,
  price DECIMAL(8,2) NOT NULL DEFAULT 30.00,
  photo VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_packs_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pack_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pack_id INT UNSIGNED NOT NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(120) NOT NULL,
  variety VARCHAR(120) NULL,
  latin_name VARCHAR(160) NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit ENUM('graines','g','plants','godets','arbre','pieds') NOT NULL DEFAULT 'graines',
  market_price DECIMAL(8,2) NULL,
  unit_cost DECIMAL(8,3) NULL,
  variety_id INT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  photo VARCHAR(500) NULL,
  KEY idx_items_pack (pack_id, position),
  CONSTRAINT fk_items_pack FOREIGN KEY (pack_id) REFERENCES packs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogue des variétés proposées au vote (prix d'achat en gros et prix public en jardinerie)
CREATE TABLE IF NOT EXISTS varieties (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category ENUM('seeds','vegetables','fruit_trees','aromatics','plants') NOT NULL DEFAULT 'seeds',
  name VARCHAR(120) NOT NULL,
  variety VARCHAR(120) NULL,
  latin_name VARCHAR(160) NULL,
  note VARCHAR(255) NULL,
  photo VARCHAR(500) NULL,
  portion_qty DECIMAL(10,2) NOT NULL DEFAULT 10,
  unit ENUM('graines','g','plants','godets','arbre','pieds') NOT NULL DEFAULT 'graines',
  purchase_price DECIMAL(8,2) NULL,
  purchase_qty DECIMAL(10,2) NULL,
  market_price DECIMAL(8,2) NULL,
  supplier VARCHAR(120) NULL,
  supplier_ref VARCHAR(60) NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_varieties_cat (category, is_active, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ventes d'un groupe (une par univers et par saison)
CREATE TABLE IF NOT EXISTS sales (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  category ENUM('seeds','vegetables','fruit_trees','aromatics','plants') NOT NULL DEFAULT 'seeds',
  title VARCHAR(160) NOT NULL,
  status ENUM('waiting','voting','open','closed','cancelled') NOT NULL DEFAULT 'waiting',
  vote_days SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  sale_days SMALLINT UNSIGNED NOT NULL DEFAULT 21,
  batch_size SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  max_per_order TINYINT UNSIGNED NOT NULL DEFAULT 3,
  price DECIMAL(8,2) NOT NULL DEFAULT 30.00,
  max_choices TINYINT UNSIGNED NOT NULL DEFAULT 10,
  max_varieties SMALLINT UNSIGNED NULL,
  vote_starts_at DATETIME NULL,
  vote_ends_at DATETIME NULL,
  opened_at DATETIME NULL,
  sale_ends_at DATETIME NULL,
  closed_at DATETIME NULL,
  winning_pack_id INT UNSIGNED NULL,
  pickup_at DATETIME NULL,
  pickup_place VARCHAR(160) NULL,
  pickup_address VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sales_group (group_id, status),
  KEY idx_sales_status (status),
  CONSTRAINT fk_sales_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  CONSTRAINT fk_sales_winner FOREIGN KEY (winning_pack_id) REFERENCES packs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Variétés soumises au vote d'une vente
CREATE TABLE IF NOT EXISTS sale_varieties (
  sale_id INT UNSIGNED NOT NULL,
  variety_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (sale_id, variety_id),
  CONSTRAINT fk_sv_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_sv_variety FOREIGN KEY (variety_id) REFERENCES varieties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un membre choisit plusieurs variétés (max_choices)
CREATE TABLE IF NOT EXISTS variety_votes (
  sale_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  variety_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (sale_id, user_id, variety_id),
  KEY idx_vv_variety (sale_id, variety_id),
  CONSTRAINT fk_vv_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_vv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_vv_variety FOREIGN KEY (variety_id) REFERENCES varieties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Panier enregistré : le pack gagnant y est déposé pour chaque votant
CREATE TABLE IF NOT EXISTS cart_items (
  user_id INT UNSIGNED NOT NULL,
  sale_id INT UNSIGNED NOT NULL,
  pack_id INT UNSIGNED NOT NULL,
  quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
  source ENUM('vote','manual') NOT NULL DEFAULT 'manual',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, sale_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_pack FOREIGN KEY (pack_id) REFERENCES packs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lots de N achats (10 par défaut) : un lot complet déclenche le débit
CREATE TABLE IF NOT EXISTS sale_batches (
  sale_id INT UNSIGNED NOT NULL,
  batch_no SMALLINT UNSIGNED NOT NULL,
  status ENUM('open','complete','cancelled') NOT NULL DEFAULT 'open',
  completed_at DATETIME NULL,
  PRIMARY KEY (sale_id, batch_no),
  CONSTRAINT fk_batches_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(24) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  sale_id INT UNSIGNED NOT NULL,
  pack_id INT UNSIGNED NOT NULL,
  quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(8,2) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  goods_amount DECIMAL(10,2) NULL,
  fee_amount DECIMAL(8,2) NOT NULL DEFAULT 0,
  fund_amount DECIMAL(8,2) NOT NULL DEFAULT 0,
  membership_amount DECIMAL(8,2) NOT NULL DEFAULT 0,
  batch_no SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('pending','confirmed','cancelled','ready','collected') NOT NULL DEFAULT 'pending',
  payment_mode ENUM('stripe','on_pickup') NOT NULL DEFAULT 'on_pickup',
  payment_status ENUM('pending','not_required','processing','paid','action_required','failed','to_collect','refunded') NOT NULL DEFAULT 'pending',
  stripe_setup_intent_id VARCHAR(64) NULL,
  stripe_payment_method_id VARCHAR(64) NULL,
  stripe_payment_intent_id VARCHAR(64) NULL,
  from_vote TINYINT(1) NOT NULL DEFAULT 0,
  notes VARCHAR(500) NULL,
  pickup_token CHAR(32) NOT NULL,
  pickup_code CHAR(6) NOT NULL,
  bin_code VARCHAR(4) NULL,
  cgv_accepted_at DATETIME NOT NULL,
  invoice_number VARCHAR(24) NULL,
  invoiced_at DATETIME NULL,
  prepared_by INT UNSIGNED NULL,
  ready_at DATETIME NULL,
  reminded_at DATETIME NULL,
  collected_at DATETIME NULL,
  collected_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_ref (reference),
  UNIQUE KEY uq_orders_token (pickup_token),
  KEY idx_orders_user (user_id),
  KEY idx_orders_sale (sale_id, batch_no, status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_sale FOREIGN KEY (sale_id) REFERENCES sales(id),
  CONSTRAINT fk_orders_pack FOREIGN KEY (pack_id) REFERENCES packs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shifts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NULL,
  type ENUM('packing','unloading','distribution','other') NOT NULL DEFAULT 'packing',
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  location VARCHAR(255) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  volunteers_needed SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_shifts_start (starts_at),
  CONSTRAINT fk_shifts_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_signups (
  shift_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reminded_at DATETIME NULL,
  PRIMARY KEY (shift_id, user_id),
  CONSTRAINT fk_signup_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
  CONSTRAINT fk_signup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos : cache local + crédits (auteur, licence) pour Wikimedia Commons
CREATE TABLE IF NOT EXISTS photos (
  ref VARCHAR(500) NOT NULL,
  ref_hash CHAR(32) NOT NULL PRIMARY KEY,
  status ENUM('pending','cached','missing','failed') NOT NULL DEFAULT 'pending',
  local_path VARCHAR(255) NULL,
  source_page VARCHAR(500) NULL,
  author VARCHAR(255) NULL,
  license VARCHAR(120) NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_queue (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  html MEDIUMTEXT NOT NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  KEY idx_mail_status (status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sequences (
  name VARCHAR(40) NOT NULL PRIMARY KEY,
  value INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stripe_events (
  id VARCHAR(80) NOT NULL PRIMARY KEY,
  type VARCHAR(80) NOT NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  details VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  amount DECIMAL(8,2) NOT NULL DEFAULT 0,
  order_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_memberships (user_id, year),
  CONSTRAINT fk_memberships_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inbox_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  uid INT UNSIGNED NOT NULL,
  message_id VARCHAR(255) NULL,
  from_email VARCHAR(190) NOT NULL,
  from_name VARCHAR(190) NULL,
  subject VARCHAR(255) NOT NULL DEFAULT '',
  body_text MEDIUMTEXT NULL,
  received_at DATETIME NULL,
  category VARCHAR(30) NULL,
  status ENUM('new','answered','archived') NOT NULL DEFAULT 'new',
  answered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_inbox_status (status),
  UNIQUE KEY uq_inbox_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(30) NOT NULL DEFAULT 'autre',
  status ENUM('draft_needed','pending','approved','rejected','sent','failed') NOT NULL DEFAULT 'pending',
  title VARCHAR(200) NOT NULL,
  summary TEXT NULL,
  to_email VARCHAR(190) NULL,
  subject VARCHAR(255) NULL,
  body MEDIUMTEXT NULL,
  meta JSON NULL,
  inbox_id INT UNSIGNED NULL,
  edited TINYINT(1) NOT NULL DEFAULT 0,
  decided_at DATETIME NULL,
  decided_by INT UNSIGNED NULL,
  decision_note VARCHAR(500) NULL,
  sent_at DATETIME NULL,
  error VARCHAR(500) NULL,
  source VARCHAR(100) NOT NULL DEFAULT 'api',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_agent_status (status),
  KEY idx_agent_cat (category),
  CONSTRAINT fk_agent_inbox FOREIGN KEY (inbox_id) REFERENCES inbox_messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO sequences (name, value) VALUES ('order', 0), ('invoice', 0);
INSERT IGNORE INTO settings (k, v) VALUES
 ('association_name', 'Les Jardins d''à Côté'),
 ('association_legal', 'Association loi 1901 — SIRET à compléter'),
 ('association_address', '17 boulevard d''Arcole, 31000 Toulouse'),
 ('contact_email', 'contact@lesjardinsdacote.fr'),
 ('vat_mention', 'TVA non applicable, article 293 B du CGI'),
 ('pickup_reminder_hours', '48'),
 ('default_vote_days', '10'),
 ('default_sale_days', '21'),
 ('default_batch_size', '10'),
 ('default_pack_price', '30'),
 ('sachet_cost', '0.10'),
 ('pack_fixed_costs', '0'),
 ('market_price_note', 'Prix publics constatés en magasin de jardinage (relevés en novembre 2026) : un sachet ou un plant de la même variété, à l''unité.'),
 ('order_fee', '5'),
 ('order_fee_fund', '1'),
 ('membership_fee', '0'),
 ('president_name', ''),
 ('treasurer_name', ''),
 ('imap_host', 'imap.hostinger.com'),
 ('imap_user', ''),
 ('agent_key_hash', '');
