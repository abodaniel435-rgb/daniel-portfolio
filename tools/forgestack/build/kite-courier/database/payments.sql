-- Manual payment capture ledger (no card data is stored)

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(24) NOT NULL UNIQUE,
  record_id INT UNSIGNED NULL,
  order_id INT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  method ENUM('manual','transfer','cash','pos') NOT NULL DEFAULT 'manual',
  status ENUM('recorded','refunded') NOT NULL DEFAULT 'recorded',
  note VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payment_status (status, created_at),
  CONSTRAINT fk_payment_record FOREIGN KEY (record_id) REFERENCES shipments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
