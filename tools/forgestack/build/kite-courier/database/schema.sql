-- Base schema: shared tables + the primary request table.
-- MySQL 5.7+ / 8.x / MariaDB 10.3+ · utf8mb4

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff','member') NOT NULL DEFAULT 'member',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL,
  INDEX idx_attempt_lookup (email, ip, attempted_at)
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

CREATE TABLE IF NOT EXISTS audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(24) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(40) NOT NULL,
  request_date DATE NOT NULL,
  request_time VARCHAR(24) NOT NULL COMMENT 'Exact time (10:00) or a delivery window (10:00–12:00)',
  choice_a VARCHAR(120) NOT NULL COMMENT 'Primary option: treatment / service / seating',
  choice_b VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'Secondary option: clinician / zone / occasion',
  quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Duration, weight, guests or units',
  notes VARCHAR(1000) NOT NULL DEFAULT '',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_status ENUM('not_required','unpaid','paid','refunded') NOT NULL DEFAULT 'not_required',
  status VARCHAR(24) NOT NULL DEFAULT 'pending',
  reminder_sent_at DATETIME NULL,
  payment_reminder_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_record_date (request_date, request_time),
  INDEX idx_record_status (status),
  INDEX idx_record_customer (customer_id),
  INDEX idx_record_email (customer_email),
  CONSTRAINT fk_record_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS record_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  record_id INT UNSIGNED NOT NULL,
  status VARCHAR(24) NOT NULL,
  note VARCHAR(500) NOT NULL DEFAULT '',
  source ENUM('customer','admin','system') NOT NULL DEFAULT 'system',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_record (record_id, id),
  CONSTRAINT fk_event_record FOREIGN KEY (record_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Module: messages.sql
-- Contact-form enquiries

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  subject VARCHAR(160) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('new','handled') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  handled_at DATETIME NULL,
  INDEX idx_message_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
