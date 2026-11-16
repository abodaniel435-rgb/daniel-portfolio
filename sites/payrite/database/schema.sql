-- PayRite — MySQL 5.7+ / MariaDB 10.3+
-- Applied by public/install.php; safe to re-run.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(24) NOT NULL DEFAULT 'staff',
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

-- The PAYE table. Nigerian bands are slices, not floors: the first ₦300,000 of
-- taxable pay is taxed at 7%, the next ₦300,000 at 11%, and so on. `slice_width`
-- is therefore the size of the slice, and NULL on the last row means
-- "everything above". An administrator can edit these without a redeploy.
CREATE TABLE IF NOT EXISTS pay_bands (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  band_order INT UNSIGNED NOT NULL,
  slice_width DECIMAL(14,2) NULL,
  rate DECIMAL(5,2) NOT NULL,
  updated_at DATETIME NOT NULL,
  updated_by INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY pay_bands_order (band_order),
  CONSTRAINT pay_bands_updater_fk FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every constant the engine needs, in the same shape the other builds use so a
-- rate change is a row update rather than a code change.
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

-- The roster. One row per person the business pays. `user_id` is what turns a
-- name on a spreadsheet into someone who can sign in and see only their own
-- payslips; it is set by an administrator, never by the person registering.
CREATE TABLE IF NOT EXISTS employees (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_no VARCHAR(24) NOT NULL,
  name VARCHAR(120) NOT NULL,
  role_title VARCHAR(120) NOT NULL DEFAULT '',
  department VARCHAR(80) NOT NULL DEFAULT 'General',
  user_id INT UNSIGNED NULL,
  basic DECIMAL(14,2) NOT NULL DEFAULT 0,
  housing DECIMAL(14,2) NOT NULL DEFAULT 0,
  transport DECIMAL(14,2) NOT NULL DEFAULT 0,
  other DECIMAL(14,2) NOT NULL DEFAULT 0,
  annual_rent DECIMAL(14,2) NOT NULL DEFAULT 0,
  claim_rent TINYINT(1) NOT NULL DEFAULT 1,
  pension_rate DECIMAL(5,2) NOT NULL DEFAULT 8,
  status VARCHAR(16) NOT NULL DEFAULT 'active',
  query_note VARCHAR(255) NOT NULL DEFAULT '',
  joined_at DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY employees_staff_no (staff_no),
  KEY employees_roster (status, name),
  KEY employees_user (user_id),
  CONSTRAINT employees_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A month of payroll. A month is paid once: Desk::liveRunByPeriod() refuses a
-- second draft while one is still live. `period` is deliberately not UNIQUE,
-- because a voided run stays here as the evidence of what was withdrawn while
-- the same month is rebuilt — MySQL has no partial unique index, so that rule
-- lives in the application rather than in the table.
CREATE TABLE IF NOT EXISTS payroll_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period CHAR(7) NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'draft',
  headcount INT UNSIGNED NOT NULL DEFAULT 0,
  gross_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  paye_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  pension_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  nhf_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  net_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  cost_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NOT NULL DEFAULT '',
  created_by INT UNSIGNED NULL,
  locked_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY payroll_runs_period (period, status),
  KEY payroll_runs_status (status, created_at),
  CONSTRAINT payroll_runs_creator_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A payslip is a record of what was paid, not a live view of the roster: the
-- package, the band table and the relief constants are all stored with it, so a
-- slip printed in December still shows the arithmetic that produced it even
-- after the rates and the salary have moved on.
CREATE TABLE IF NOT EXISTS payslips (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_id INT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED NOT NULL,
  period CHAR(7) NOT NULL,
  employee_name VARCHAR(120) NOT NULL,
  staff_no VARCHAR(24) NOT NULL DEFAULT '',
  role_title VARCHAR(120) NOT NULL DEFAULT '',
  basic DECIMAL(14,2) NOT NULL DEFAULT 0,
  housing DECIMAL(14,2) NOT NULL DEFAULT 0,
  transport DECIMAL(14,2) NOT NULL DEFAULT 0,
  other DECIMAL(14,2) NOT NULL DEFAULT 0,
  gross DECIMAL(14,2) NOT NULL DEFAULT 0,
  annual_gross DECIMAL(16,2) NOT NULL DEFAULT 0,
  cra DECIMAL(14,2) NOT NULL DEFAULT 0,
  rent_relief DECIMAL(14,2) NOT NULL DEFAULT 0,
  taxable_annual DECIMAL(16,2) NOT NULL DEFAULT 0,
  taxable_monthly DECIMAL(14,2) NOT NULL DEFAULT 0,
  paye DECIMAL(14,2) NOT NULL DEFAULT 0,
  pension DECIMAL(14,2) NOT NULL DEFAULT 0,
  nhf DECIMAL(14,2) NOT NULL DEFAULT 0,
  net DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_pension DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_nhf DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
  marginal_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  bands_json JSON NULL,
  rules_json JSON NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY payslips_one (run_id, employee_id),
  KEY payslips_employee (employee_id, period),
  CONSTRAINT payslips_run_fk FOREIGN KEY (run_id) REFERENCES payroll_runs (id) ON DELETE CASCADE,
  CONSTRAINT payslips_employee_fk FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What the public calculator was asked and what the server answered. The
-- arithmetic lives here, not in a browser script, so a figure on the landing
-- page and a figure on a payslip cannot disagree.
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
