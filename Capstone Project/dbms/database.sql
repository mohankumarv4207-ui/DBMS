CREATE DATABASE IF NOT EXISTS smart_water_distribution
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE smart_water_distribution;

CREATE TABLE IF NOT EXISTS zones (
    zone_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL UNIQUE,
    area VARCHAR(150) NOT NULL,
    pipeline_length_m DECIMAL(10,2) NOT NULL DEFAULT 0,
    pipeline_material VARCHAR(80) NOT NULL DEFAULT '',
    status ENUM('Active', 'Maintenance', 'Inactive') NOT NULL DEFAULT 'Active',
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL DEFAULT '',
    phone VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,
    zone_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customers_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS meters (
    meter_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meter_number VARCHAR(60) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED NULL,
    installation_date DATE NOT NULL,
    status ENUM('Active', 'Inactive', 'Faulty') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meters_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_meters_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sensor_readings (
    reading_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meter_id INT UNSIGNED NOT NULL,
    reading_date DATETIME NOT NULL,
    consumption_m3 DECIMAL(12,3) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_readings_meter FOREIGN KEY (meter_id) REFERENCES meters(meter_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_readings_date (reading_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Open', 'Investigating', 'Resolved', 'Closed') NOT NULL DEFAULT 'Open',
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_complaints_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_complaints_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inspections (
    inspection_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    inspected_at DATETIME NOT NULL,
    findings TEXT NOT NULL,
    worker_name VARCHAR(120) NOT NULL,
    status ENUM('Scheduled', 'Completed') NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inspections_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS repair_activities (
    repair_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT UNSIGNED NOT NULL,
    worker_name VARCHAR(120) NOT NULL,
    job_description TEXT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    status ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    notes TEXT NULL,
    CONSTRAINT fk_repairs_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
