-- =====================================================
-- Migration: Add Payment Tracking & Statement Generation
-- Date: 2026-02-15
-- Description: Adds soa_payments table for tracking individual payments,
--              client_statements table for grouped statement generation,
--              and paid_amount column to client_soa for balance tracking.
-- =====================================================

-- 1. Add paid_amount column to client_soa for tracking payments received
ALTER TABLE client_soa
ADD COLUMN paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount;

-- 2. Create soa_payments table for individual payment records
CREATE TABLE IF NOT EXISTS soa_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    soa_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Bank Transfer', 'Cash', 'Cheque', 'Online Payment', 'Credit Card', 'Other') NOT NULL DEFAULT 'Bank Transfer',
    payment_reference VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soa_id) REFERENCES client_soa(soa_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create client_statements table for generated account summaries
CREATE TABLE IF NOT EXISTS client_statements (
    statement_id INT PRIMARY KEY AUTO_INCREMENT,
    statement_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    statement_date DATE NOT NULL,
    period_from DATE NOT NULL,
    period_to DATE NOT NULL,
    total_invoiced DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    generated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES staff(staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create linking table for statements to SOAs
CREATE TABLE IF NOT EXISTS statement_soa_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    statement_id INT NOT NULL,
    soa_id INT NOT NULL,
    FOREIGN KEY (statement_id) REFERENCES client_statements(statement_id) ON DELETE CASCADE,
    FOREIGN KEY (soa_id) REFERENCES client_soa(soa_id) ON DELETE CASCADE,
    UNIQUE KEY unique_statement_soa (statement_id, soa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
