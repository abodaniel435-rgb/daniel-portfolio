-- NairaDesk — MySQL 5.7+ / MariaDB 10.3+
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
  UNIQUE KEY users_email_unique (email)
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

-- One row per calculation the server performed. `inputs` and `result` are the
-- exact payload the engine consumed and returned, so a run can be replayed.
CREATE TABLE IF NOT EXISTS calculations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  company_id INT UNSIGNED NULL,
  tool VARCHAR(24) NOT NULL,
  label VARCHAR(160) NOT NULL DEFAULT '',
  inputs JSON NOT NULL,
  result JSON NOT NULL,
  headline VARCHAR(60) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY calculations_user (user_id, created_at),
  KEY calculations_tool (tool, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The payroll domain. A company owns employees; a pay run is a month; a line
-- freezes the engine's full output for one employee at that moment, so a
-- reopened run shows what was paid, not what today's rates would have paid.
CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  tin VARCHAR(40) NOT NULL DEFAULT '',
  state VARCHAR(60) NOT NULL DEFAULT 'Lagos',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY companies_owner (owner_id),
  CONSTRAINT companies_owner_fk FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employees (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  staff_no VARCHAR(40) NOT NULL DEFAULT '',
  basic DECIMAL(14,2) NOT NULL,
  housing DECIMAL(14,2) NOT NULL DEFAULT 0,
  transport DECIMAL(14,2) NOT NULL DEFAULT 0,
  other DECIMAL(14,2) NOT NULL DEFAULT 0,
  use_pension TINYINT(1) NOT NULL DEFAULT 1,
  pension_rate DECIMAL(5,2) NOT NULL DEFAULT 8,
  use_nhf TINYINT(1) NOT NULL DEFAULT 1,
  use_rent TINYINT(1) NOT NULL DEFAULT 0,
  annual_rent DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY employees_company (company_id),
  CONSTRAINT employees_company_fk FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pay_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  period VARCHAR(7) NOT NULL,
  gross_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  paye_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  pension_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  nhf_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  net_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  employer_cost_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  headcount INT UNSIGNED NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY pay_runs_period (company_id, period),
  CONSTRAINT pay_runs_company_fk FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pay_run_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_id INT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED NOT NULL,
  gross DECIMAL(14,2) NOT NULL,
  taxable_annual DECIMAL(16,2) NOT NULL,
  paye_monthly DECIMAL(14,2) NOT NULL,
  pension_monthly DECIMAL(14,2) NOT NULL,
  nhf_monthly DECIMAL(14,2) NOT NULL,
  net DECIMAL(14,2) NOT NULL,
  employer_cost DECIMAL(14,2) NOT NULL,
  marginal_rate DECIMAL(5,2) NOT NULL,
  detail JSON NOT NULL,
  PRIMARY KEY (id),
  KEY pay_run_lines_run (run_id),
  CONSTRAINT pay_run_lines_run_fk FOREIGN KEY (run_id) REFERENCES pay_runs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Named, editable plans a member keeps. `payload` holds the inputs; the result
-- is always recomputed from the live rate table so a plan cannot go stale.
CREATE TABLE IF NOT EXISTS saved_plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  tool VARCHAR(24) NOT NULL,
  name VARCHAR(160) NOT NULL,
  payload JSON NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY saved_plans_owner (user_id, tool),
  CONSTRAINT saved_plans_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reference data an administrator can change without redeploying: the PAYE
-- bands, the Consolidated Relief Allowance, the rent-relief ceiling, the
-- pension and NHF rates. The engine reads these, never a hard-coded number.
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
