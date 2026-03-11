-- SQL Script for XAMPP Server - Camp John Hay Manor Project
-- This script sets up the necessary tables for item declarations and schedules

CREATE DATABASE IF NOT EXISTS manor;
USE manor;

-- 1. Users table (already exists, but here's the structure for reference)
-- CREATE TABLE IF NOT EXISTS `users` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `user_id` varchar(20) NOT NULL,
--   `fullname` varchar(100) NOT NULL,
--   `email` varchar(100) NOT NULL,
--   `password` varchar(255) NOT NULL,
--   `department` varchar(100) DEFAULT NULL,
--   `role` varchar(50) NOT NULL,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `email` (`email`),
--   UNIQUE KEY `user_id` (`user_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Schedules (Shifts) table
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(100) NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Item Declarations table
CREATE TABLE IF NOT EXISTS `item_declarations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `declaration_date` date NOT NULL,
  `time_in` time NOT NULL,
  `items_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shift_id` (`shift_id`),
  CONSTRAINT `item_declarations_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert some default shifts if they don't exist
INSERT INTO `schedules` (`shift_name`, `time_from`, `time_to`, `status`) 
SELECT * FROM (SELECT 'Morning Shift', '06:00:00', '14:00:00', 'Active') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `schedules` WHERE `shift_name` = 'Morning Shift') LIMIT 1;

INSERT INTO `schedules` (`shift_name`, `time_from`, `time_to`, `status`) 
SELECT * FROM (SELECT 'Afternoon Shift', '14:00:00', '22:00:00', 'Active') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `schedules` WHERE `shift_name` = 'Afternoon Shift') LIMIT 1;

INSERT INTO `schedules` (`shift_name`, `time_from`, `time_to`, `status`) 
SELECT * FROM (SELECT 'Night Shift', '22:00:00', '06:00:00', 'Active') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `schedules` WHERE `shift_name` = 'Night Shift') LIMIT 1;
