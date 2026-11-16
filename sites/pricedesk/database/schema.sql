-- PriceDesk — MySQL 5.7+ / MariaDB 10.3+
-- Applied by public/install.php; safe to re-run.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(24) NOT NULL DEFAULT 'member',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email),
  KEY users_role (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL DEFAULT '',
  ip VARCHAR(64) NOT NULL DEFAULT '',
  succeeded TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY login_attempts_lookup (email, attempted_at),
  KEY login_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reference data an administrator curates. `unit_weight_kg` is what makes a
-- crate, mudu or bag comparable: every price is converted to naira per
-- kilogram before anything is averaged, so a 50-mudu basket and a 100-kg bag
-- sit on the same axis instead of being quietly added together.
CREATE TABLE IF NOT EXISTS commodities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(90) NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'crop',
  units JSON NOT NULL,
  unit_weight_kg DECIMAL(10,3) NOT NULL DEFAULT 1,
  unit_label VARCHAR(40) NOT NULL DEFAULT 'kg',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY commodities_name (name),
  KEY commodities_category (category, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS markets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  state VARCHAR(60) NOT NULL DEFAULT 'Lagos',
  kind VARCHAR(24) NOT NULL DEFAULT 'retail',
  haul_km DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY markets_name (name),
  KEY markets_state (state, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The board itself. One member may file one price per commodity per market per
-- day — the unique key is what stops a single phone from becoming a trend.
-- `status` is the curation: a report is only counted once an administrator
-- verifies it, and the rejection reason is stored so the reporter can see why.
CREATE TABLE IF NOT EXISTS price_reports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  market_id INT UNSIGNED NOT NULL,
  commodity_id INT UNSIGNED NOT NULL,
  reporter_id INT UNSIGNED NOT NULL,
  report_date DATE NOT NULL,
  price DECIMAL(14,2) NOT NULL,
  unit VARCHAR(24) NOT NULL DEFAULT 'kg',
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  note VARCHAR(255) NOT NULL DEFAULT '',
  status VARCHAR(16) NOT NULL DEFAULT 'pending',
  verified_by INT UNSIGNED NULL,
  verified_at DATETIME NULL,
  reject_reason VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY price_reports_one (market_id, commodity_id, reporter_id, report_date),
  KEY price_reports_board (commodity_id, market_id, status, report_date),
  KEY price_reports_reporter (reporter_id, report_date),
  CONSTRAINT price_reports_market_fk FOREIGN KEY (market_id) REFERENCES markets (id) ON DELETE CASCADE,
  CONSTRAINT price_reports_commodity_fk FOREIGN KEY (commodity_id) REFERENCES commodities (id) ON DELETE CASCADE,
  CONSTRAINT price_reports_reporter_fk FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT price_reports_verifier_fk FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who wants to hear about which commodity in which market. Drives the two
-- automation tasks: a verified price arriving, and a board going stale.
CREATE TABLE IF NOT EXISTS watches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  market_id INT UNSIGNED NOT NULL,
  commodity_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY watches_one (user_id, market_id, commodity_id),
  CONSTRAINT watches_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT watches_market_fk FOREIGN KEY (market_id) REFERENCES markets (id) ON DELETE CASCADE,
  CONSTRAINT watches_commodity_fk FOREIGN KEY (commodity_id) REFERENCES commodities (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every comparison the server performed, with the exact payload the engine
-- consumed and returned, so a member can be shown the working behind a number
-- they are about to act on.
CREATE TABLE IF NOT EXISTS calculations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  tool VARCHAR(24) NOT NULL,
  label VARCHAR(160) NOT NULL DEFAULT '',
  inputs JSON NOT NULL,
  result JSON NOT NULL,
  headline VARCHAR(60) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY calculations_user (user_id, created_at),
  KEY calculations_tool (tool, created_at),
  CONSTRAINT calculations_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Editable so a haulage rate that moves does not need a redeploy. The engine
-- reads these, never a hard-coded number.
CREATE TABLE IF NOT EXISTS rates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tool VARCHAR(24) NOT NULL,
  code VARCHAR(48) NOT NULL,
  label VARCHAR(160) NOT NULL,
  value DECIMAL(16,4) NOT NULL,
  unit VARCHAR(24) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  updated_by INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY rates_unique (tool, code),
  KEY rates_tool (tool)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_outbox (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  body_text MEDIUMTEXT NOT NULL,
  message_type VARCHAR(60) NOT NULL DEFAULT 'general',
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  error VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  INDEX idx_outbox_status (status, created_at),
  INDEX idx_outbox_type (message_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id INT UNSIGNED NULL,
  action VARCHAR(64) NOT NULL,
  target VARCHAR(120) NOT NULL DEFAULT '',
  meta JSON NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY audit_actor (actor_id, created_at),
  CONSTRAINT audit_actor_fk FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
