-- ===============================================
-- PUROK AND STATUS ENHANCEMENTS FOR BARANGAY MANAGEMENT SYSTEM
-- ===============================================
-- Run this script to add purok management and resident status tracking features
-- This script is safe to run multiple times

-- --- Create Puroks Table ---
CREATE TABLE IF NOT EXISTS `puroks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purok_name` varchar(100) NOT NULL,
  `purok_leader` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `purok_name` (`purok_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Add New Columns to Residents Table (if they don't exist) ---
-- Check if columns exist before adding them to prevent errors

-- Add status column
SET @exist := (SELECT count(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='residents' AND COLUMN_NAME='status' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD COLUMN `status` enum(\'Active\',\'Deceased\',\'Moved Out\') NOT NULL DEFAULT \'Active\' AFTER `email`','SELECT \'Column status already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add purok_id column
SET @exist := (SELECT count(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='residents' AND COLUMN_NAME='purok_id' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD COLUMN `purok_id` int(11) DEFAULT NULL AFTER `status`','SELECT \'Column purok_id already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add date_status_changed column
SET @exist := (SELECT count(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='residents' AND COLUMN_NAME='date_status_changed' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD COLUMN `date_status_changed` date DEFAULT NULL AFTER `purok_id`','SELECT \'Column date_status_changed already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status_remarks column
SET @exist := (SELECT count(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='residents' AND COLUMN_NAME='status_remarks' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD COLUMN `status_remarks` text DEFAULT NULL AFTER `date_status_changed`','SELECT \'Column status_remarks already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- Add Indexes (if they don't exist) ---
-- Add index for status
SET @exist := (SELECT count(*) FROM information_schema.STATISTICS WHERE TABLE_NAME='residents' AND INDEX_NAME='idx_status' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD INDEX `idx_status` (`status`)','SELECT \'Index idx_status already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for purok_id
SET @exist := (SELECT count(*) FROM information_schema.STATISTICS WHERE TABLE_NAME='residents' AND INDEX_NAME='idx_purok_id' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD INDEX `idx_purok_id` (`purok_id`)','SELECT \'Index idx_purok_id already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- Add Foreign Key Constraint (if it doesn't exist) ---
SET @exist := (SELECT count(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='residents' AND CONSTRAINT_NAME='fk_residents_purok' AND TABLE_SCHEMA=DATABASE());
SET @sqlstmt := IF(@exist=0,'ALTER TABLE `residents` ADD CONSTRAINT `fk_residents_purok` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE','SELECT \'Foreign key fk_residents_purok already exists\' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- Insert Sample Purok Data (only if not exists) ---
INSERT IGNORE INTO `puroks` (`purok_name`, `purok_leader`, `description`) VALUES
('Purok 1 - Malaya', 'Jose Rizal', 'Northern part of the barangay'),
('Purok 2 - Katipunan', 'Andres Bonifacio', 'Central area near the main road'),
('Purok 3 - Bagong Lipunan', 'Maria Clara', 'Eastern section with residential areas'),
('Purok 4 - Masagana', 'Lapu-Lapu', 'Western area with commercial establishments'),
('Purok 5 - Maligaya', 'Gabriela Silang', 'Southern part near the river'),
('Purok 6 - Matatag', 'Antonio Luna', 'Mountainous area in the northeast'),
('Purok 7 - Mapagkakatiwalaan', 'Juan Luna', 'Area near the school and health center');

-- --- Update Existing Residents with Default Values ---
-- Set default status for residents that don't have it set
UPDATE `residents` SET `status` = 'Active' WHERE `status` IS NULL OR `status` = '';

-- Assign existing residents to random puroks if they don't have one assigned
UPDATE `residents` r 
LEFT JOIN `puroks` p ON r.purok_id = p.id 
SET r.purok_id = (
    SELECT id FROM puroks ORDER BY RAND() LIMIT 1
) 
WHERE r.purok_id IS NULL AND EXISTS (SELECT 1 FROM puroks);

-- ===============================================
-- SCRIPT COMPLETED SUCCESSFULLY
-- ===============================================
-- The following features have been added:
-- ✓ Puroks table with 7 sample puroks
-- ✓ Resident status tracking (Active, Deceased, Moved Out)
-- ✓ Purok assignment for residents
-- ✓ Status change date tracking
-- ✓ Status remarks field
-- ✓ Proper indexes and foreign key relationships
-- ✓ Sample data populated safely
-- ===============================================

-- Enhanced Database Schema for Barangay Management System
-- This file adds new resident fields and is safe to run multiple times

-- Check if first_name column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'first_name') = 0,
        'ALTER TABLE residents ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT "" AFTER fullname',
        'SELECT "first_name column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if middle_name column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'middle_name') = 0,
        'ALTER TABLE residents ADD COLUMN middle_name VARCHAR(100) DEFAULT NULL AFTER first_name',
        'SELECT "middle_name column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if last_name column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'last_name') = 0,
        'ALTER TABLE residents ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT "" AFTER middle_name',
        'SELECT "last_name column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if suffix column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'suffix') = 0,
        'ALTER TABLE residents ADD COLUMN suffix VARCHAR(20) DEFAULT NULL AFTER last_name',
        'SELECT "suffix column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if age column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'age') = 0,
        'ALTER TABLE residents ADD COLUMN age INT DEFAULT NULL AFTER birthdate',
        'SELECT "age column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if educational_attainment column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'educational_attainment') = 0,
        'ALTER TABLE residents ADD COLUMN educational_attainment ENUM("No Formal Education", "Elementary", "Elementary Graduate", "High School", "High School Graduate", "Vocational", "College", "College Graduate", "Post Graduate") DEFAULT NULL AFTER age',
        'SELECT "educational_attainment column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if family_planning column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'family_planning') = 0,
        'ALTER TABLE residents ADD COLUMN family_planning ENUM("Yes", "No", "Not Applicable") DEFAULT "Not Applicable" AFTER educational_attainment',
        'SELECT "family_planning column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if no_maintenance column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'no_maintenance') = 0,
        'ALTER TABLE residents ADD COLUMN no_maintenance ENUM("Yes", "No") DEFAULT "No" AFTER family_planning',
        'SELECT "no_maintenance column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if water_source column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'water_source') = 0,
        'ALTER TABLE residents ADD COLUMN water_source ENUM("Deep Well", "Shallow Well", "Public Faucet", "Spring/River", "Piped Water", "Bottled Water", "Rainwater", "Other") DEFAULT NULL AFTER no_maintenance',
        'SELECT "water_source column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if toilet_facility column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'toilet_facility') = 0,
        'ALTER TABLE residents ADD COLUMN toilet_facility ENUM("Water Sealed", "Closed Pit", "Open Pit", "None/No Toilet", "Other") DEFAULT NULL AFTER water_source',
        'SELECT "toilet_facility column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if pantawid_4ps column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'pantawid_4ps') = 0,
        'ALTER TABLE residents ADD COLUMN pantawid_4ps ENUM("Yes", "No") DEFAULT "No" AFTER toilet_facility',
        'SELECT "pantawid_4ps column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if backyard_gardening column exists, if not add it
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND column_name = 'backyard_gardening') = 0,
        'ALTER TABLE residents ADD COLUMN backyard_gardening ENUM("Yes", "No") DEFAULT "No" AFTER pantawid_4ps',
        'SELECT "backyard_gardening column already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for better performance on new fields
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.statistics 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND index_name = 'idx_last_name') = 0,
        'CREATE INDEX idx_last_name ON residents (last_name)',
        'SELECT "idx_last_name index already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.statistics 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND index_name = 'idx_educational_attainment') = 0,
        'CREATE INDEX idx_educational_attainment ON residents (educational_attainment)',
        'SELECT "idx_educational_attainment index already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.statistics 
         WHERE table_schema = DATABASE() 
         AND table_name = 'residents' 
         AND index_name = 'idx_4ps') = 0,
        'CREATE INDEX idx_4ps ON residents (pantawid_4ps)',
        'SELECT "idx_4ps index already exists"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update trigger to auto-calculate age when birthdate changes
DROP TRIGGER IF EXISTS update_age_on_birthdate_change;

DELIMITER $$
CREATE TRIGGER update_age_on_birthdate_change
BEFORE UPDATE ON residents
FOR EACH ROW
BEGIN
    IF NEW.birthdate IS NOT NULL THEN
        SET NEW.age = FLOOR(DATEDIFF(CURDATE(), NEW.birthdate) / 365.25);
    ELSE
        SET NEW.age = NULL;
    END IF;
END$$
DELIMITER ;

-- Insert trigger to auto-calculate age when birthdate is set
DROP TRIGGER IF EXISTS calculate_age_on_insert;

DELIMITER $$
CREATE TRIGGER calculate_age_on_insert
BEFORE INSERT ON residents
FOR EACH ROW
BEGIN
    IF NEW.birthdate IS NOT NULL THEN
        SET NEW.age = FLOOR(DATEDIFF(CURDATE(), NEW.birthdate) / 365.25);
    ELSE
        SET NEW.age = NULL;
    END IF;
END$$
DELIMITER ;

-- Create a view for comprehensive resident information
CREATE OR REPLACE VIEW comprehensive_residents AS
SELECT 
    r.id,
    CONCAT(
        COALESCE(r.first_name, ''), 
        CASE WHEN r.middle_name IS NOT NULL AND r.middle_name != '' 
             THEN CONCAT(' ', r.middle_name) 
             ELSE '' END,
        CASE WHEN r.last_name IS NOT NULL AND r.last_name != '' 
             THEN CONCAT(' ', r.last_name) 
             ELSE '' END,
        CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' 
             THEN CONCAT(' ', r.suffix) 
             ELSE '' END
    ) AS constructed_fullname,
    r.fullname,
    r.first_name,
    r.middle_name,
    r.last_name,
    r.suffix,
    r.gender,
    r.birthdate,
    r.age,
    r.educational_attainment,
    r.family_planning,
    r.no_maintenance,
    r.water_source,
    r.toilet_facility,
    r.pantawid_4ps,
    r.backyard_gardening,
    r.address,
    r.contact_number,
    r.email,
    r.status,
    r.purok_id,
    p.purok_name,
    r.date_status_changed,
    r.status_remarks,
    r.registration_date
FROM residents r
LEFT JOIN puroks p ON r.purok_id = p.id;

-- Success message
SELECT 'Enhanced resident fields have been successfully added to the database!' as message;

-- ===============================================
-- SCRIPT COMPLETED SUCCESSFULLY
-- ===============================================
-- The following features have been added:
-- ✓ Puroks table with 7 sample puroks
-- ✓ Resident status tracking (Active, Deceased, Moved Out)
-- ✓ Purok assignment for residents
-- ✓ Status change date tracking
-- ✓ Status remarks field
-- ✓ Proper indexes and foreign key relationships
-- ✓ Sample data populated safely
-- ✓ New resident fields added
-- ✓ Automated age calculation
-- ✓ Comprehensive resident view
-- =============================================== 