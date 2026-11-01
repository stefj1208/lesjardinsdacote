-- =====================================================================
-- Les Jardins d'à Côté — Plateforme d'achat groupé & vrac
-- Schéma MySQL / MariaDB (InnoDB, utf8mb4)
-- Réf. cahier des charges JDC-2026-V3
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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

CREATE TABLE IF NOT EXISTS suppliers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  catalog_url VARCHAR(255) NULL,
  min_order_franco_ht DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping_cost_ht DECIMAL(10,2) NOT NULL DEFAULT 0,
  lead_time_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  contact_email VARCHAR(190) NULL,
  contact_phone VARCHAR(30) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogue polymorphe : 6 unités natives
--   g      = gramme (semences)
--   seeds  = nombre de graines
--   l      = litre (terreau, compost)
--   kg     = kilogramme (engrais, amendements)
--   pot    = unité / pot (plantes, arbustes, cache-pots)
--   piece  = pièce unique (seconde main)
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT UNSIGNED NULL,
  sku_supplier VARCHAR(64) NULL,
  category ENUM('seeds','indoor','shrubs','substrate','pots','second_hand') NOT NULL DEFAULT 'seeds',
  name VARCHAR(160) NOT NULL,
  variety VARCHAR(160) NULL,
  latin_name VARCHAR(160) NULL,
  family VARCHAR(120) NULL,
  description TEXT NULL,
  sowing_advice TEXT NULL,
  sowing_months VARCHAR(40) NULL,
  sowing_depth VARCHAR(40) NULL,
  exposure ENUM('shade','partial','sun') NULL,
  water ENUM('low','medium','high') NULL,
  difficulty ENUM('easy','medium','expert') NULL,
  is_organic TINYINT(1) NOT NULL DEFAULT 1,
  unit ENUM('g','seeds','l','kg','pot','piece') NOT NULL DEFAULT 'g',
  pot_size VARCHAR(40) NULL,
  pmg DECIMAL(10,3) NULL COMMENT 'Poids de mille graines (g)',
  image_url VARCHAR(255) NULL,
  source_url VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_products_supplier (supplier_id),
  UNIQUE KEY uq_products_sku (supplier_id, sku_supplier),
  CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grille tarifaire fournisseur par conditionnement (ex : sac 250 g = 18,40 € HT)
CREATE TABLE IF NOT EXISTS product_packs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  label VARCHAR(80) NOT NULL,
  qty DECIMAL(12,3) NOT NULL COMMENT 'Quantité dans l unité du produit',
  cost_ht DECIMAL(10,2) NOT NULL,
  KEY idx_packs_product (product_id),
  CONSTRAINT fk_packs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Panier global fournisseur (mode 3 : franco de port)
CREATE TABLE IF NOT EXISTS supplier_batches (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  franco_ht DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('open','validated','failed','ordered') NOT NULL DEFAULT 'open',
  ordered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_batches_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pickup_windows (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(160) NOT NULL,
  location VARCHAR(160) NOT NULL,
  address VARCHAR(255) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  notes TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  supplier_batch_id INT UNSIGNED NULL,
  pickup_window_id INT UNSIGNED NULL,
  slug VARCHAR(190) NOT NULL,
  title VARCHAR(190) NOT NULL,
  pitch VARCHAR(255) NULL,
  trigger_mode ENUM('volume','logistic','franco') NOT NULL DEFAULT 'volume',
  status ENUM('draft','active','validated','failed','receiving','packaging','ready_pickup','closed') NOT NULL DEFAULT 'draft',
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  ref_portion DECIMAL(10,3) NOT NULL DEFAULT 10 COMMENT 'Part de référence affichée (ex : 10 g)',
  portion_options VARCHAR(120) NOT NULL DEFAULT '5,10,25',
  max_packets_per_line SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  max_qty_cap DECIMAL(12,3) NULL,
  current_reserved_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
  current_tier TINYINT UNSIGNED NOT NULL DEFAULT 0,
  current_unit_price DECIMAL(12,5) NOT NULL DEFAULT 0 COMMENT 'Prix TTC par unité de base au palier courant',
  public_unit_price DECIMAL(12,5) NOT NULL DEFAULT 0 COMMENT 'Prix public de référence TTC par unité de base',
  shipping_share_ht DECIMAL(10,2) NOT NULL DEFAULT 0,
  waste_coeff DECIMAL(5,4) NOT NULL DEFAULT 0.0400,
  packet_cost DECIMAL(8,4) NOT NULL DEFAULT 0.0800,
  margin_rate DECIMAL(5,4) NOT NULL DEFAULT 0.1500,
  vat_rate DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
  supplier_lot VARCHAR(80) NULL,
  packed_year SMALLINT UNSIGNED NULL,
  shop_buyback_qty DECIMAL(12,3) NOT NULL DEFAULT 0 COMMENT 'Reliquat racheté par la boutique (US-A07)',
  notified_tier TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  closed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_campaigns_slug (slug),
  KEY idx_campaigns_status (status, end_at),
  CONSTRAINT fk_campaigns_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_campaigns_batch FOREIGN KEY (supplier_batch_id) REFERENCES supplier_batches(id) ON DELETE SET NULL,
  CONSTRAINT fk_campaigns_window FOREIGN KEY (pickup_window_id) REFERENCES pickup_windows(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_tiers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED NOT NULL,
  tier_level TINYINT UNSIGNED NOT NULL,
  min_qty DECIMAL(12,3) NOT NULL,
  unit_price_ttc DECIMAL(12,5) NOT NULL,
  label VARCHAR(120) NULL COMMENT 'Ex : Sac 500 g débloqué',
  UNIQUE KEY uq_tiers (campaign_id, tier_level),
  CONSTRAINT fk_tiers_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(24) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  pickup_window_id INT UNSIGNED NULL,
  status ENUM('committed','cancelled','confirmed','failed','ready','collected') NOT NULL DEFAULT 'committed',
  payment_mode ENUM('stripe','on_pickup') NOT NULL DEFAULT 'on_pickup',
  payment_status ENUM('pending','not_required','processing','paid','action_required','failed','to_collect','refunded') NOT NULL DEFAULT 'pending',
  stripe_setup_intent_id VARCHAR(64) NULL,
  stripe_payment_method_id VARCHAR(64) NULL,
  stripe_payment_intent_id VARCHAR(64) NULL,
  amount_committed DECIMAL(10,2) NOT NULL DEFAULT 0,
  amount_final DECIMAL(10,2) NULL,
  container_choice ENUM('kraft','own','deposit') NOT NULL DEFAULT 'kraft',
  notes VARCHAR(500) NULL,
  pickup_token CHAR(32) NOT NULL,
  pickup_code CHAR(6) NOT NULL,
  bin_code VARCHAR(4) NULL,
  cgv_accepted_at DATETIME NOT NULL,
  invoice_number VARCHAR(24) NULL,
  invoiced_at DATETIME NULL,
  ready_at DATETIME NULL,
  reminded_at DATETIME NULL,
  collected_at DATETIME NULL,
  collected_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_ref (reference),
  UNIQUE KEY uq_orders_token (pickup_token),
  KEY idx_orders_user (user_id),
  KEY idx_orders_pay (payment_status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_window FOREIGN KEY (pickup_window_id) REFERENCES pickup_windows(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  campaign_id INT UNSIGNED NOT NULL,
  portion_qty DECIMAL(10,3) NOT NULL COMMENT 'Contenu d un sachet / d une unité',
  packets SMALLINT UNSIGNED NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_price_commit DECIMAL(12,5) NOT NULL,
  unit_price_final DECIMAL(12,5) NULL,
  line_total_final DECIMAL(10,2) NULL,
  status ENUM('reserved','cancelled','validated','failed','packed','collected') NOT NULL DEFAULT 'reserved',
  packed_at DATETIME NULL,
  packed_by INT UNSIGNED NULL,
  KEY idx_items_order (order_id),
  KEY idx_items_campaign (campaign_id, status),
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shifts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type ENUM('packing','unloading','distribution','other') NOT NULL DEFAULT 'packing',
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  location VARCHAR(255) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  volunteers_needed SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  campaign_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_shifts_start (starts_at),
  CONSTRAINT fk_shifts_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
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

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO sequences (name, value) VALUES ('order', 0), ('invoice', 0);
INSERT IGNORE INTO settings (k, v) VALUES
 ('association_name', 'Les Jardins d''à Côté'),
 ('association_legal', 'Association loi 1901 — SIRET à compléter'),
 ('association_address', '17 boulevard d''Arcole, 31000 Toulouse'),
 ('contact_email', 'contact@lesjardinsdacote.fr'),
 ('default_margin_rate', '0.15'),
 ('default_waste_coeff', '0.04'),
 ('default_packet_cost', '0.08'),
 ('default_vat_rate', '0'),
 ('vat_mention', 'TVA non applicable, article 293 B du CGI'),
 ('pickup_reminder_hours', '48'),
 ('deposit_fee', '1.00');
