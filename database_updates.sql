-- This script contains database updates. If you get a 'Duplicate column' error,
-- it means that part of the script has already been run.

-- The section below for adding columns to the `residents` table appears to have been run already.
-- I am commenting it out to prevent errors.
--
-- ALTER TABLE residents 
-- ADD COLUMN maintenance_medicine VARCHAR(50) DEFAULT NULL,
-- ADD COLUMN other_medicine VARCHAR(100) DEFAULT NULL,
-- ADD COLUMN gender_other VARCHAR(50) DEFAULT NULL;
-- 
-- -- Update existing records to set default values
-- UPDATE residents SET maintenance_medicine = 'Not Applicable' WHERE maintenance_medicine IS NULL;
-- UPDATE residents SET other_medicine = '' WHERE other_medicine IS NULL;
-- UPDATE residents SET gender_other = '' WHERE gender_other IS NULL;


-- This section adds the 'role' column to the 'users' table for role-based access control.
-- If you get a 'Duplicate column' error on this next line, you can also comment it out.
-- ALTER TABLE `users` ADD `role` VARCHAR(50) NOT NULL DEFAULT 'staff' AFTER `password`;

-- Update existing users to have roles. 
-- You might want to adjust these roles based on your actual users.
-- For example, we assume the 'admin' user is the 'Barangay Secretary'.
UPDATE `users` SET `role` = 'Barangay Secretary' WHERE `username` = 'admin';

-- You can add more users with specific roles here.
-- Example for a staff member:
-- INSERT INTO `users` (`username`, `password`, `role`) VALUES ('staff_user', 'staff_password', 'staff') ON DUPLICATE KEY UPDATE `role` = 'staff';

-- Add civil_status to residents table
-- This line is commented out because it has already been successfully executed.
-- ALTER TABLE `residents` ADD `civil_status` VARCHAR(50) NOT NULL DEFAULT 'Single' AFTER `gender`;

-- -----------------------------------------------------------------------------------------
-- === MAJOR SCHEMA UPDATE FOR CERTIFICATES AND RESIDENTS ===
-- This section brings the database schema in line with the latest application code.
-- It's designed to be run once. If you get errors on a re-run (e.g., 'Duplicate column'),
-- you can comment out the lines that have already been successfully executed.
-- -----------------------------------------------------------------------------------------

-- Step 1: Update the `certificate_types` table schema.
-- This adds the `description` column required for the certificate management system.
ALTER TABLE `certificate_types` ADD COLUMN `description` TEXT NULL DEFAULT NULL AFTER `name`;

-- This removes old columns that are no longer used.
-- It's okay if these `DROP` commands fail; it just means the columns were already removed.
ALTER TABLE `certificate_types` DROP COLUMN IF EXISTS `default_purpose`;
ALTER TABLE `certificate_types` DROP COLUMN IF EXISTS `default_fee`;


-- Step 2: Update the `residents` table to match the detailed resident form.
-- This adds individual name fields and other new fields, and removes obsolete ones.
ALTER TABLE `residents`
  ADD COLUMN `first_name` VARCHAR(100) NOT NULL AFTER `id`,
  ADD COLUMN `middle_name` VARCHAR(100) NULL DEFAULT NULL AFTER `first_name`,
  ADD COLUMN `last_name` VARCHAR(100) NOT NULL AFTER `middle_name`,
  ADD COLUMN `suffix` VARCHAR(20) NULL DEFAULT NULL AFTER `last_name`,
  ADD COLUMN `age` INT(11) NULL DEFAULT NULL AFTER `birthdate`,
  ADD COLUMN `educational_attainment` VARCHAR(100) NULL DEFAULT NULL AFTER `age`,
  ADD COLUMN `family_planning` VARCHAR(50) NULL DEFAULT NULL AFTER `educational_attainment`,
  ADD COLUMN `no_maintenance` VARCHAR(10) NULL DEFAULT NULL AFTER `family_planning`,
  ADD COLUMN `water_source` VARCHAR(100) NULL DEFAULT NULL AFTER `no_maintenance`,
  ADD COLUMN `toilet_facility` VARCHAR(100) NULL DEFAULT NULL AFTER `water_source`,
  ADD COLUMN `pantawid_4ps` VARCHAR(10) NULL DEFAULT NULL AFTER `toilet_facility`,
  ADD COLUMN `backyard_gardening` VARCHAR(10) NULL DEFAULT NULL AFTER `pantawid_4ps`,
  ADD COLUMN `household_id` INT(11) NULL DEFAULT NULL AFTER `status_remarks`,
  DROP COLUMN `fullname`,
  DROP COLUMN `address`,
  DROP COLUMN `email`;
