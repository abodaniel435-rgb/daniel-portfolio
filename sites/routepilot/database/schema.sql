-- RoutePilot — last-mile dispatch and public tracking platform
-- MySQL 5.7+ / MariaDB 10.3+ / utf8mb4

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('dispatcher','courier','admin') NOT NULL DEFAULT 'courier',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS couriers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  vehicle ENUM('motorcycle','bicycle','van','car') NOT NULL DEFAULT 'motorcycle',
  zone VARCHAR(80) NOT NULL DEFAULT 'Lagos Mainland',
  plate_number VARCHAR(24) NOT NULL DEFAULT '',
  max_daily_shipments SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  notes VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_courier_user (user_id),
  INDEX idx_courier_available (is_available),
  CONSTRAINT fk_courier_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tracking_code VARCHAR(24) NOT NULL UNIQUE,
  sender_name VARCHAR(120) NOT NULL,
  sender_phone VARCHAR(40) NOT NULL,
  recipient_name VARCHAR(120) NOT NULL,
  recipient_phone VARCHAR(40) NOT NULL,
  recipient_email VARCHAR(190) NOT NULL,
  origin VARCHAR(140) NOT NULL,
  destination VARCHAR(140) NOT NULL,
  package_description VARCHAR(255) NOT NULL,
  weight_kg DECIMAL(6,2) NOT NULL DEFAULT 0.50,
  service ENUM('same_day','express','standard') NOT NULL DEFAULT 'standard',
  fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  pickup_date DATE NOT NULL,
  status ENUM('created','assigned','picked_up','in_transit','out_for_delivery','delivered','failed') NOT NULL DEFAULT 'created',
  courier_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  assigned_at DATETIME NULL,
  delivered_at DATETIME NULL,
  failed_reason VARCHAR(255) NULL,
  last_location VARCHAR(140) NOT NULL DEFAULT '',
  flagged_late_pickup_at DATETIME NULL,
  flagged_delayed_at DATETIME NULL,
  eta_reminder_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ship_status (status, created_at),
  INDEX idx_ship_courier (courier_id, status),
  INDEX idx_ship_pickup (pickup_date, status),
  INDEX idx_ship_created (created_at),
  CONSTRAINT fk_ship_courier FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE SET NULL,
  CONSTRAINT fk_ship_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tracking_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id INT UNSIGNED NOT NULL,
  status VARCHAR(32) NOT NULL,
  location VARCHAR(140) NOT NULL DEFAULT '',
  note VARCHAR(500) NOT NULL DEFAULT '',
  source ENUM('dispatch','courier','automation','system') NOT NULL DEFAULT 'courier',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_shipment (shipment_id, created_at),
  CONSTRAINT fk_event_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
  CONSTRAINT fk_event_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
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
