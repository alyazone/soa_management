CREATE TABLE IF NOT EXISTS`supplier_soa` (
  `soa_id` INT NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50) NOT NULL,
  `supplier_id` INT NOT NULL,
  `po_id` INT DEFAULT NULL,
  `issue_date` DATE NOT NULL,
  `payment_due_date` DATE NOT NULL,
  `purchase_description` TEXT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('Pending', 'Paid', 'Overdue') DEFAULT 'Pending',
  `payment_method`  ENUM('Cash', 'Bank Transfer', 'Cheque') DEFAULT NULL,
  `created_by` INT NOT NULL,
  `credit_duration` INT NULL,
  `client_id` INT NULL,
  `amount_paid` DECIMAL(10,2) NULL DEFAULT 0.00,
  `receipt_number` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Primary Key
  PRIMARY KEY (`soa_id`),
  
  -- Foreign Key Constraints
  CONSTRAINT `supplier_soa_ibfk_1` 
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) 
    ON DELETE RESTRICT ON UPDATE RESTRICT,
    
  CONSTRAINT `supplier_soa_ibfk_2` 
    FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`) 
    ON DELETE RESTRICT ON UPDATE RESTRICT,
    
  CONSTRAINT `supplier_soa_ibfk_3` 
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) 
    ON DELETE SET NULL ON UPDATE RESTRICT,

  CONSTRAINT `supplier_soa_ibfk_4` 
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) 
    ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;