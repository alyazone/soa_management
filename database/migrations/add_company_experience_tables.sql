-- Migration: Add Company Experience (Pengalaman Syarikat) tables
-- Date: 2026-02-16

-- Step 1: Create experience_categories table
CREATE TABLE IF NOT EXISTS experience_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed initial categories
INSERT INTO experience_categories (category_name) VALUES
('Antivirus'),
('Firewall'),
('SPA'),
('Active Directory'),
('Hardware Provider'),
('Troubleshoot');

-- Step 2: Create company_experiences table
CREATE TABLE IF NOT EXISTS company_experiences (
    experience_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    client_id INT DEFAULT NULL,
    agency_name VARCHAR(255) NOT NULL,
    contract_name VARCHAR(255) NOT NULL,
    contract_year YEAR NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    source_soa_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES experience_categories(category_id),
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE SET NULL,
    FOREIGN KEY (source_soa_id) REFERENCES client_soa(soa_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES staff(staff_id) ON DELETE SET NULL
);

-- Step 3: Add category_id to client_soa table
ALTER TABLE client_soa ADD COLUMN category_id INT DEFAULT NULL AFTER service_description;
ALTER TABLE client_soa ADD FOREIGN KEY (category_id) REFERENCES experience_categories(category_id) ON DELETE SET NULL;
