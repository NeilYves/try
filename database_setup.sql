-- Barangay Management System - Database Setup Script
-- This script defines the schema for the Barangay Management System database.
-- It creates tables for system settings, residents, officials, certificates, activities, and announcements.
-- It also includes sample data for initial setup.

-- --- Database Initialization ---
-- The following commands are typically run once to create and select the database.
-- It's recommended to create the database manually or via a tool like phpMyAdmin first.
--
-- CREATE DATABASE IF NOT EXISTS `barangay_mingming` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `barangay_mingming`;

-- --- Table: `system_settings` ---
-- Stores system-wide configuration and settings for the barangay.
-- Uses a key-value pair structure for flexibility.
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(100) NOT NULL,      -- Unique key for the setting (e.g., 'barangay_name').
  `setting_value` text DEFAULT NULL,        -- Value of the setting (can be text, path, ID, etc.).
  PRIMARY KEY (`setting_key`)               -- `setting_key` is the primary key, ensuring uniqueness.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; -- InnoDB engine for transaction support, utf8mb4 for broad character support.

-- Initial data for `system_settings`.
-- I am commenting out this block because it causes a "Duplicate entry" error if the script is run more than once.
-- The subsequent INSERT IGNORE block correctly handles this situation.
-- INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
-- ('barangay_name', 'Barangay Example'),              -- Default name of the barangay.
-- ('barangay_address_line1', '123 Main Street'),      -- First line of the barangay's address.
-- ('barangay_address_line2', 'Example City, Province'), -- Second line of the barangay's address.
-- ('barangay_logo_path', 'assets/images/uploads/default_logo.png'), -- Path to the barangay logo image. Updated to reflect typical upload folder.
-- ('current_punong_barangay_id', NULL),             -- Foreign key to `officials` table, for the current Punong Barangay.
-- ('default_certificate_fee', '50.00');              -- Default fee for certificates if not specified by type.

-- Initial data for `system_settings` (only insert if not exists).
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('barangay_name', 'Barangay Example'),              -- Default name of the barangay.
('barangay_address_line1', '123 Main Street'),      -- First line of the barangay's address.
('barangay_address_line2', 'Example City, Province'), -- Second line of the barangay's address.
('barangay_logo_path', 'assets/images/uploads/default_logo.png'), -- Path to the barangay logo image. Updated to reflect typical upload folder.
('current_punong_barangay_id', NULL),             -- Foreign key to `officials` table, for the current Punong Barangay.
('default_certificate_fee', '50.00');

-- --- Table: `certificate_types` ---
-- Defines the different types of certificates the barangay can issue.
CREATE TABLE IF NOT EXISTS `certificate_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `template_file` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `certificate_types`
-- Changed to INSERT IGNORE to prevent errors on subsequent script runs.
INSERT IGNORE INTO `certificate_types` (`id`, `name`, `description`, `template_file`, `is_active`) VALUES
(1, 'Barangay Clearance', 'Issued to residents to certify that they are of good moral character.', 'template_barangay_clearance.php', 1),
(2, 'Certificate of Indigency', 'Issued to prove that a resident is part of an indigent family.', 'template_cert_indigency.php', 1),
(3, 'Certificate of Residency', 'Issued to certify that a resident lives in the barangay.', 'template_cert_residency.php', 1);

-- --- Table: `residents` ---
-- Stores information about the residents of the barangay.
CREATE TABLE IF NOT EXISTS `residents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,         -- Unique identifier for each resident.
  `fullname` varchar(255) NOT NULL,           -- Full name of the resident.
  `gender` enum('Male','Female','Other') NOT NULL, -- Gender of the resident.
  `birthdate` date DEFAULT NULL,              -- Resident's date of birth.
  `address` text DEFAULT NULL,                -- Resident's full address within the barangay.
  `contact_number` varchar(20) DEFAULT NULL,  -- Resident's contact phone number.
  `email` varchar(100) DEFAULT NULL,          -- Resident's email address.
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(), -- Date and time of registration.
  `status` enum('Active','Deceased','Moved Out') NOT NULL DEFAULT 'Active',
  `purok_id` int(11) DEFAULT NULL,
  `date_status_changed` date DEFAULT NULL,
  `status_remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),                          -- `id` is the primary key.
  INDEX `idx_status` (`status`),
  INDEX `idx_purok_id` (`purok_id`),
  CONSTRAINT `fk_residents_purok` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Table: `officials` ---
-- Stores information about the barangay officials.
CREATE TABLE IF NOT EXISTS `officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,         -- Unique identifier for each official.
  `fullname` varchar(255) NOT NULL,           -- Full name of the official.
  `position` varchar(100) NOT NULL,           -- Official's position (e.g., 'Barangay Captain', 'Kagawad').
  `gender` enum('Male','Female','Other') DEFAULT NULL, -- Gender of the official (added for consistency).
  `term_start_date` date DEFAULT NULL,        -- Start date of the official's term.
  `term_end_date` date DEFAULT NULL,          -- End date of the official's term.
  `contact_number` varchar(20) DEFAULT NULL,  -- Official's contact phone number.
  `image_path` varchar(255) DEFAULT NULL,     -- Path to the official's photograph.
  `display_order` int(11) DEFAULT 0,          -- Order in which officials are displayed (e.g., in lists).
  PRIMARY KEY (`id`)                          -- `id` is the primary key.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Table: `issued_certificates` ---
-- Tracks all certificates issued by the barangay.
-- Links residents, certificate types, and issuing officials.
CREATE TABLE IF NOT EXISTS `issued_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,         -- Unique identifier for each issued certificate record.
  `resident_id` int(11) NOT NULL,             -- Foreign key referencing `residents.id`.
  `certificate_type_id` int(11) NOT NULL,     -- Foreign key referencing `certificate_types.id`.
  `control_number` varchar(50) NOT NULL,      -- Unique control number for the certificate.
  `issue_date` date NOT NULL,                 -- Date when the certificate was issued.
  `purpose` text DEFAULT NULL,                -- Specific purpose for which the certificate was issued.
  `issuing_official_id` int(11) DEFAULT NULL, -- Foreign key referencing `officials.id` (who signed/issued it).
  `fee_paid` decimal(10,2) DEFAULT 0.00,      -- Amount paid for the certificate.
  `or_number` varchar(50) DEFAULT NULL,       -- Official Receipt number, if applicable.
  `remarks` text DEFAULT NULL,                -- Any additional remarks or notes.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), -- Timestamp of when the record was created.
  PRIMARY KEY (`id`),                          -- `id` is the primary key.
  UNIQUE KEY `control_number` (`control_number`), -- Ensures `control_number` is unique.
  KEY `resident_id` (`resident_id`),           -- Index for faster lookups on `resident_id`.
  KEY `certificate_type_id` (`certificate_type_id`), -- Index for `certificate_type_id`.
  KEY `issuing_official_id` (`issuing_official_id`), -- Index for `issuing_official_id`.
  -- Foreign Key Constraints:
  CONSTRAINT `fk_issued_certificates_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    -- If a resident is deleted, restrict deletion if they have issued certificates. If resident ID changes, update here.
  CONSTRAINT `fk_issued_certificates_type` FOREIGN KEY (`certificate_type_id`) REFERENCES `certificate_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    -- If a certificate type is deleted, restrict. If type ID changes, update here.
  CONSTRAINT `fk_issued_certificates_official` FOREIGN KEY (`issuing_official_id`) REFERENCES `officials` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    -- If an official is deleted, set `issuing_official_id` to NULL. If official ID changes, update here.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- Table: `activities` ---
-- Logs significant actions performed within the system (audit trail).
CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,         -- Unique identifier for each activity log entry.
  `activity_description` text NOT NULL,       -- Description of the activity performed.
  `activity_type` varchar(50) DEFAULT NULL,   -- Category of the activity (e.g., 'New Resident', 'System Update').
  `user_id` int(11) DEFAULT NULL,             -- Optional: ID of the user who performed the action (if user authentication is implemented).
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(), -- Date and time when the activity occurred.
  PRIMARY KEY (`id`)                          -- `id` is the primary key.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Table: `announcements` ---
-- Stores announcements to be displayed in the system (e.g., on the dashboard).
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,         -- Unique identifier for each announcement.
  `title` varchar(255) NOT NULL,              -- Title of the announcement.
  `content` text NOT NULL,                    -- Full content of the announcement.
  `publish_date` timestamp NOT NULL DEFAULT current_timestamp(), -- Date and time when the announcement was published.
  `event_date` datetime DEFAULT NULL,           -- Optional: Date and time of an event related to the announcement.
  `author_id` int(11) DEFAULT NULL,           -- Optional: ID of the user/official who posted the announcement.
  `is_active` tinyint(1) NOT NULL DEFAULT 1,  -- Flag to show (1) or hide (0) the announcement.
  PRIMARY KEY (`id`)                          -- `id` is the primary key.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Table: `puroks` ---
-- Stores purok (zone) information within the barangay
CREATE TABLE IF NOT EXISTS `puroks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purok_name` varchar(100) NOT NULL,
  `purok_leader` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `purok_name` (`purok_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Purok Data
-- Commenting out this redundant block to prevent errors.
-- INSERT INTO `puroks` (`purok_name`, `purok_leader`, `description`) VALUES
-- ('Purok 1 - Malaya', 'Jose Rizal', 'Northern part of the barangay'),
-- ('Purok 2 - Katipunan', 'Andres Bonifacio', 'Central area near the main road'),
-- ('Purok 3 - Bagong Lipunan', 'Maria Clara', 'Eastern section with residential areas'),
-- ('Purok 4 - Masagana', 'Lapu-Lapu', 'Western area with commercial establishments'),
-- ('Purok 5 - Maligaya', 'Gabriela Silang', 'Southern part near the river'),
-- ('Purok 6 - Matatag', 'Antonio Luna', 'Mountainous area in the northeast'),
-- ('Purok 7 - Mapagkakatiwalaan', 'Juan Luna', 'Area near the school and health center');

-- Sample Purok Data (only insert if not exists)
INSERT IGNORE INTO `puroks` (`purok_name`, `purok_leader`, `description`) VALUES
('Purok 1 - Malaya', 'Jose Rizal', 'Northern part of the barangay'),
('Purok 2 - Katipunan', 'Andres Bonifacio', 'Central area near the main road'),
('Purok 3 - Bagong Lipunan', 'Maria Clara', 'Eastern section with residential areas'),
('Purok 4 - Masagana', 'Lapu-Lapu', 'Western area with commercial establishments'),
('Purok 5 - Maligaya', 'Gabriela Silang', 'Southern part near the river'),
('Purok 6 - Matatag', 'Antonio Luna', 'Mountainous area in the northeast'),
('Purok 7 - Mapagkakatiwalaan', 'Juan Luna', 'Area near the school and health center');

-- Update existing sample residents with purok assignments
UPDATE `residents` SET 
`purok_id` = 1,
`status` = 'Active'
WHERE `id` = 1;

UPDATE `residents` SET 
`purok_id` = 2,
`status` = 'Active'
WHERE `id` = 2;

UPDATE `residents` SET 
`purok_id` = 3,
`status` = 'Active'
WHERE `id` = 3;

UPDATE `residents` SET 
`purok_id` = 1,
`status` = 'Active'
WHERE `id` = 4;

UPDATE `residents` SET 
`purok_id` = 2,
`status` = 'Active'
WHERE `id` = 5;

-- --- Table: `users` ---
-- Stores user accounts for system access.
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default admin user (password: password123)
INSERT IGNORE INTO `users` (`username`, `password`) VALUES
('admin', 'password123');

-- --- Sample Data Insertion ---
-- The following INSERT statements populate the tables with some initial sample data.
-- This is optional and can be modified or removed.

-- Sample Officials Data
-- Commenting out this redundant block to prevent errors.
-- INSERT INTO `officials` (`fullname`, `position`, `gender`, `term_start_date`, `contact_number`, `display_order`) VALUES
-- ('Juan Dela Cruz', 'Barangay Captain', 'Male', '2023-01-01', '09123456789', 1),
-- ('Maria Santos', 'Barangay Secretary', 'Female', '2023-01-01', '09987654321', 2),
-- ('Pedro Reyes', 'Barangay Treasurer', 'Male', '2023-01-01', '09112233445', 3),
-- ('Ana Garcia', 'Kagawad', 'Female', '2023-01-01', '09223344556', 4);

-- Sample Residents Data (for initial dashboard counts and testing)
-- Commenting out this redundant block to prevent errors.
-- INSERT INTO `residents` (`fullname`, `gender`, `birthdate`, `address`) VALUES
-- ('John Doe', 'Male', '1990-05-15', '123 Main St, Barangay Example'),
-- ('Jane Smith', 'Female', '1992-08-20', '456 Oak Ave, Barangay Example'),
-- ('Michael Lee', 'Male', '1985-11-10', '789 Pine Rd, Barangay Example'),
-- ('Emily White', 'Female', '2000-02-25', '101 Elm St, Barangay Example'),
-- ('David Brown', 'Male', '1978-07-03', '202 Maple Dr, Barangay Example');

-- Sample Activities Data
-- Commenting out this redundant block to prevent errors.
-- INSERT INTO `activities` (`activity_description`, `activity_type`) VALUES
-- ('System initialized with sample resident John Doe.', 'New Resident'),
-- ('System initialized with sample certificate type: Certificate of Indigency.', 'System Setup'),
-- ('Initial system settings configured by setup script.', 'System Update');

-- Sample Announcements Data
-- Commenting out this redundant block to prevent errors.
-- INSERT INTO `announcements` (`title`, `content`, `event_date`) VALUES
-- ('Quarterly Barangay Assembly', 'All respected residents are cordially invited to attend the quarterly Barangay Assembly. Your presence and participation are highly valued. Venue: Barangay Hall Covered Court.', '2025-06-15 14:00:00'),
-- ('Free Anti-Rabies Vaccination Drive', 'A free anti-rabies vaccination drive for pet dogs and cats will be conducted at the Barangay Plaza. Please bring your pets responsibly.', '2025-06-20 09:00:00');

-- Sample Officials Data (only insert if not exists)
INSERT IGNORE INTO `officials` (`fullname`, `position`, `gender`, `term_start_date`, `contact_number`, `display_order`) VALUES
('Juan Dela Cruz', 'Barangay Captain', 'Male', '2023-01-01', '09123456789', 1),
('Maria Santos', 'Barangay Secretary', 'Female', '2023-01-01', '09987654321', 2),
('Pedro Reyes', 'Barangay Treasurer', 'Male', '2023-01-01', '09112233445', 3),
('Ana Garcia', 'Kagawad', 'Female', '2023-01-01', '09223344556', 4);

-- Sample Residents Data (for initial dashboard counts and testing)
INSERT IGNORE INTO `residents` (`fullname`, `gender`, `birthdate`, `address`) VALUES
('John Doe', 'Male', '1990-05-15', '123 Main St, Barangay Example'),
('Jane Smith', 'Female', '1992-08-20', '456 Oak Ave, Barangay Example'),
('Michael Lee', 'Male', '1985-11-10', '789 Pine Rd, Barangay Example'),
('Emily White', 'Female', '2000-02-25', '101 Elm St, Barangay Example'),
('David Brown', 'Male', '1978-07-03', '202 Maple Dr, Barangay Example');

-- Sample Activities Data (only insert if not exists)
INSERT IGNORE INTO `activities` (`activity_description`, `activity_type`) VALUES
('System initialized with sample resident John Doe.', 'New Resident'),
('System initialized with sample certificate type: Certificate of Indigency.', 'System Setup'),
('Initial system settings configured by setup script.', 'System Update');

-- Sample Announcements Data (only insert if not exists)
INSERT IGNORE INTO `announcements` (`title`, `content`, `event_date`) VALUES
('Quarterly Barangay Assembly', 'All respected residents are cordially invited to attend the quarterly Barangay Assembly. Your presence and participation are highly valued. Venue: Barangay Hall Covered Court.', '2025-06-15 14:00:00'),
('Free Anti-Rabies Vaccination Drive', 'A free anti-rabies vaccination drive for pet dogs and cats will be conducted at the Barangay Plaza. Please bring your pets responsibly.', '2025-06-20 09:00:00');

-- === ENHANCEMENTS FOR PUROK MANAGEMENT AND RESIDENT STATUS ===

-- End of Database Setup Script --

