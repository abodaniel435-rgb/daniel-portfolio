-- FarmCoop — produce aggregation marketplace
-- MySQL 5.7+ / MariaDB 10.3+ · utf8mb4 · InnoDB

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('farmer','buyer','admin') NOT NULL DEFAULT 'buyer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS farm_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  farm_name VARCHAR(160) NOT NULL,
  state VARCHAR(60) NOT NULL DEFAULT '',
  lga VARCHAR(90) NOT NULL DEFAULT '',
  produce_focus VARCHAR(190) NOT NULL DEFAULT '',
  about TEXT NULL,
  photo_url VARCHAR(255) NOT NULL DEFAULT '',
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  verified_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_farm_user (user_id),
  INDEX idx_farm_state (state),
  CONSTRAINT fk_farm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(24) NOT NULL UNIQUE,
  farmer_id INT UNSIGNED NOT NULL,
  produce_name VARCHAR(160) NOT NULL,
  category ENUM('vegetables','grains','tubers','fruits','spices','other') NOT NULL DEFAULT 'vegetables',
  quantity DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'remaining quantity available',
  quantity_listed DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'quantity at creation, for reporting',
  unit ENUM('kg','crate','basket') NOT NULL DEFAULT 'kg',
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  available_from DATE NOT NULL,
  pickup_window VARCHAR(120) NOT NULL DEFAULT '',
  state VARCHAR(60) NOT NULL DEFAULT '',
  lga VARCHAR(90) NOT NULL DEFAULT '',
  location VARCHAR(190) NOT NULL DEFAULT '',
  description TEXT NULL,
  image_url VARCHAR(255) NOT NULL DEFAULT '',
  status ENUM('open','paused','reserved','sold','expired') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_listing_status (status, available_from),
  INDEX idx_listing_farmer (farmer_id, status),
  INDEX idx_listing_filter (category, state, status),
  CONSTRAINT fk_listing_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(24) NOT NULL UNIQUE,
  listing_id INT UNSIGNED NOT NULL,
  buyer_id INT UNSIGNED NOT NULL,
  farmer_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit ENUM('kg','crate','basket') NOT NULL DEFAULT 'kg',
  unit_price DECIMAL(12,2) NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL,
  delivery_method ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
  delivery_address VARCHAR(255) NOT NULL DEFAULT '',
  requested_date DATE NOT NULL,
  notes VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('pending','accepted','rejected','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  reminder_sent_at DATETIME NULL,
  accepted_at DATETIME NULL,
  ready_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order_buyer (buyer_id, status),
  INDEX idx_order_farmer (farmer_id, status),
  INDEX idx_order_listing (listing_id, status),
  INDEX idx_order_created (created_at),
  CONSTRAINT fk_order_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE RESTRICT,
  CONSTRAINT fk_order_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  note VARCHAR(255) NOT NULL DEFAULT '',
  actor_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_order (order_id, created_at),
  CONSTRAINT fk_event_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS states (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  zone VARCHAR(40) NOT NULL DEFAULT '',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
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
  INDEX idx_outbox_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL,
  INDEX idx_attempt_lookup (email, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
