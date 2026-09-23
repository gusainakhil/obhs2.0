CREATE TABLE IF NOT EXISTS `OBHS_chemicals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `station_id` INT NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'Cleaning',
  `unit` VARCHAR(10) NOT NULL DEFAULT 'L',
  `current_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `low_stock_threshold` DECIMAL(12,2) NOT NULL DEFAULT 10.00,
  `created_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chemical_station_name` (`station_id`, `name`),
  KEY `idx_chemical_station` (`station_id`),
  KEY `idx_chemical_stock` (`station_id`, `current_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `OBHS_chemical_daily_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chemical_id` BIGINT UNSIGNED NOT NULL,
  `station_id` INT NOT NULL,
  `entry_date` DATE NOT NULL,
  `opening_quantity` DECIMAL(12,2) NOT NULL,
  `used_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `received_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `closing_quantity` DECIMAL(12,2) NOT NULL,
  `notes` VARCHAR(255) NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_daily_station_date` (`station_id`, `entry_date`),
  KEY `idx_daily_chemical_date` (`chemical_id`, `entry_date`, `id`),
  CONSTRAINT `fk_daily_chemical` FOREIGN KEY (`chemical_id`) REFERENCES `OBHS_chemicals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
