-- Database Schema for TA Payslip System
-- Create this database in phpMyAdmin with name 'payslip'

CREATE TABLE IF NOT EXISTS `credentials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100),
    `email` VARCHAR(100),
    `password` VARCHAR(255) NOT NULL,
    `reset_token_hash` VARCHAR(64),
    `reset_expires` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payslips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100),
    `email` VARCHAR(100),
    `payroll_date` DATE,
    `gross_pay` DECIMAL(10, 2),
    `net_pay` DECIMAL(10, 2),
    `deductions` DECIMAL(10, 2),
    `benefits` DECIMAL(10, 2),
    `tax` DECIMAL(10, 2),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_payroll_date` (`payroll_date`),
    FOREIGN KEY (`employee_id`) REFERENCES `credentials` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional)
-- INSERT INTO `credentials` (`employee_id`, `name`, `email`, `password`) VALUES
-- ('EMP001', 'John Doe', 'john@example.com', 'password123'),
-- ('EMP002', 'Jane Smith', 'jane@example.com', 'password456');
