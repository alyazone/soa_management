--  Leave Management Module Database Schema
-- Created: 15/4/2026
-- Last Modified: 23/4/2026


-- Create leave application for staff
CREATE TABLE IF NOT EXISTS `leave_application` (
  `application_id` INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id` INT(11) NOT NULL,
  `leave_reason` ENUM('AL', 'EL', 'ML', 'OL', 'BL', 'CL', 'CPL', 'CML', 'SML', 'SHL', 'HL','ILL') NOT NULL COMMENT 'AL:Annual, EL:Emergency, ML:Medical, OL:Outstation, BL:Birthday, CL:Carryforward, CPL:Paternal, CML:Maternal, SML:Marriage, SHL:Umrah/Haji, HL:Hospitalization, ILL:in Lieu',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL, -- if total day>leave availability (warn n fails application)
  `total_day` INT(11) NOT NULL, -- calculate from start date & end date
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  KEY `idx_staff_app` (`staff_id`),
  CONSTRAINT `fk_leaveapp_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create leave balance record for each staff
-- create at registration for staff
-- updated once application is made, or by MySQL event scheduler (refresh count every year, update quantity left automatically)
CREATE TABLE IF NOT EXISTS `leave_availability` (
  `availability_id` INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id` INT(11) NOT NULL UNIQUE,
  `annual_leave` INT(11) NOT NULL DEFAULT 0,  -- +1 every 28th of the month, max:12 per year
  `emergency_leave` INT(11) NOT NULL DEFAULT 3, 
  `medical_leave` INT(11) NOT NULL DEFAULT 14,  
  `outstation_leave` INT(11) NOT NULL DEFAULT 0, -- +1 following outstation's is_claimable true.
  `birthday_leave` INT(11) NOT NULL DEFAULT 1,
  `carryforward_leave` INT(11) NOT NULL DEFAULT 0,
  `paternal_leave` INT(11) NOT NULL DEFAULT 3,
  `maternal_leave` INT(11) NOT NULL DEFAULT 60,
  `marriage_leave` INT(11) NOT NULL DEFAULT 3,
  `umrah_haji_leave` INT(11) NOT NULL DEFAULT 5,
  `hospitalization_leave` INT(11) NOT NULL DEFAULT 60,
  `in_lieu_leave` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`availability_id`),
  KEY `idx_staff_avail` (`staff_id`),
  CONSTRAINT `fk_leaveavail_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;