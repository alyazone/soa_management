-- Outstation Leave Tracking Module Database Schema
-- Created: 2025-11-18

-- Create outstation_applications table
CREATE TABLE IF NOT EXISTS `outstation_applications` (
  `application_id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_number` VARCHAR(50) NOT NULL UNIQUE,
  `staff_id` INT(11) NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `destination` VARCHAR(255) NOT NULL,
  `departure_date` DATE NOT NULL,
  `departure_time` TIME DEFAULT NULL,
  `return_date` DATE NOT NULL,
  `return_time` TIME DEFAULT NULL,
  `total_nights` INT(11) NOT NULL DEFAULT 0,
  `is_claimable` TINYINT(1) NOT NULL DEFAULT 0,
  `transportation_mode` VARCHAR(100) NOT NULL,
  `estimated_cost` DECIMAL(10,2) DEFAULT 0.00,
  `accommodation_details` TEXT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Pending',
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  KEY `fk_staff` (`staff_id`),
  KEY `fk_approver` (`approved_by`),
  KEY `idx_status` (`status`),
  KEY `idx_departure_date` (`departure_date`),
  KEY `idx_is_claimable` (`is_claimable`),
  CONSTRAINT `fk_outstation_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_outstation_approver` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create outstation_claims table (for tracking who claimed their leave)
CREATE TABLE IF NOT EXISTS `outstation_claims` (
  `claim_id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) NOT NULL,
  `staff_id` INT(11) NOT NULL,
  `claim_date` DATE NOT NULL,
  `claim_status` ENUM('Submitted', 'Approved', 'Rejected', 'Paid') NOT NULL DEFAULT 'Submitted',
  `claim_amount` DECIMAL(10,2) DEFAULT 0.00,
  `actual_expenses` DECIMAL(10,2) DEFAULT 0.00,
  `supporting_documents` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `processed_by` INT(11) DEFAULT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`claim_id`),
  KEY `fk_claim_application` (`application_id`),
  KEY `fk_claim_staff` (`staff_id`),
  KEY `fk_claim_processor` (`processed_by`),
  CONSTRAINT `fk_claim_application` FOREIGN KEY (`application_id`) REFERENCES `outstation_applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_claim_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_claim_processor` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create outstation_settings table (for configurable allowances and rules)
CREATE TABLE IF NOT EXISTS `outstation_settings` (
  `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `outstation_settings` (`setting_key`, `setting_value`, `description`) VALUES
('minimum_nights_claimable', '1', 'Minimum number of nights required to qualify for outstation leave claim'),
('default_allowance_per_day', '100.00', 'Default daily allowance amount in RM'),
('require_manager_approval', '1', 'Whether applications require manager approval (1=yes, 0=no)'),
('auto_approve_days', '0', 'Number of days after which pending applications are auto-approved (0=disabled)');

-- Create indexes for better performance
CREATE INDEX idx_application_number ON outstation_applications(application_number);
CREATE INDEX idx_claim_status ON outstation_claims(claim_status);
CREATE INDEX idx_created_at ON outstation_applications(created_at);
