CREATE DATABASE IF NOT EXISTS spk_laptop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE spk_laptop;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  label VARCHAR(60) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (code, label) VALUES
('admin', 'Administrator'),
('cashier', 'Kasir'),
('user', 'Pengguna')
ON DUPLICATE KEY UPDATE
label = VALUES(label);

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL,
  name VARCHAR(120) NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_id (role_id),
  CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS brands (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO brands (name) VALUES
('Unknown'),
('Acer'),
('ASUS'),
('Lenovo'),
('HP')
ON DUPLICATE KEY UPDATE
name = VALUES(name);

CREATE TABLE IF NOT EXISTS laptops (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  price INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_laptops_brand_id (brand_id),
  INDEX idx_laptops_name (name),
  INDEX idx_laptops_price (price),
  CONSTRAINT fk_laptops_brand
    FOREIGN KEY (brand_id) REFERENCES brands(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS criteria (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  attribute_type ENUM('benefit', 'cost') NOT NULL,
  weight DECIMAL(6,4) NOT NULL,
  unit VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO criteria (code, name, attribute_type, weight, unit) VALUES
('ram', 'RAM', 'benefit', 0.3000, 'GB'),
('storage', 'Storage', 'benefit', 0.2000, 'GB'),
('processor', 'Processor Score', 'benefit', 0.3000, 'score'),
('price', 'Harga', 'cost', 0.2000, 'IDR')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
attribute_type = VALUES(attribute_type),
weight = VALUES(weight),
unit = VALUES(unit);

CREATE TABLE IF NOT EXISTS laptop_criteria_values (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laptop_id INT UNSIGNED NOT NULL,
  criterion_id TINYINT UNSIGNED NOT NULL,
  numeric_value DECIMAL(14,4) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_laptop_criterion (laptop_id, criterion_id),
  INDEX idx_laptop_criteria_criterion_id (criterion_id),
  CONSTRAINT fk_laptop_criteria_laptop
    FOREIGN KEY (laptop_id) REFERENCES laptops(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_laptop_criteria_criterion
    FOREIGN KEY (criterion_id) REFERENCES criteria(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(40) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  cashier_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  customer_note VARCHAR(120) NULL,
  order_status ENUM('paid', 'unpaid', 'cancelled', 'refunded') NOT NULL DEFAULT 'paid',
  grand_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sales_orders_cashier_id (cashier_id),
  INDEX idx_sales_orders_customer_id (customer_id),
  INDEX idx_sales_orders_created_at (created_at),
  CONSTRAINT fk_sales_orders_cashier
    FOREIGN KEY (cashier_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_sales_orders_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  laptop_id INT UNSIGNED NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  unit_price INT UNSIGNED NOT NULL,
  line_total BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sales_items_order_id (order_id),
  INDEX idx_sales_items_laptop_id (laptop_id),
  CONSTRAINT fk_sales_items_order
    FOREIGN KEY (order_id) REFERENCES sales_orders(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_sales_items_laptop
    FOREIGN KEY (laptop_id) REFERENCES laptops(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recommendation_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  session_token VARCHAR(128) NULL,
  source_page VARCHAR(40) NULL,
  filters_json JSON NOT NULL,
  weights_json JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reco_sessions_user_id (user_id),
  INDEX idx_reco_sessions_created_at (created_at),
  CONSTRAINT fk_reco_sessions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recommendation_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recommendation_session_id BIGINT UNSIGNED NOT NULL,
  laptop_id INT UNSIGNED NULL,
  rank_position SMALLINT UNSIGNED NOT NULL,
  wp_score DECIMAL(18,8) NOT NULL,
  snapshot_json JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reco_results_session_id (recommendation_session_id),
  INDEX idx_reco_results_laptop_id (laptop_id),
  CONSTRAINT fk_reco_results_session
    FOREIGN KEY (recommendation_session_id) REFERENCES recommendation_sessions(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_reco_results_laptop
    FOREIGN KEY (laptop_id) REFERENCES laptops(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
