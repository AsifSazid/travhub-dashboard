CREATE TABLE air_ticket_quotations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(100) NOT NULL,
  sys_id VARCHAR(100) NOT NULL,
  informations LONGTEXT NOT NULL,
  quotations LONGTEXT NULL,
  percentage DECIMAL(10,2) NULL,
  ve_fixed_price DECIMAL(12,2) NULL DEFAULT 0,
  meta_data JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- If table already exists, run:
-- ALTER TABLE air_ticket_quatations
--   ADD COLUMN ve_fixed_price DECIMAL(12,2) NULL DEFAULT 0 AFTER percentage;
