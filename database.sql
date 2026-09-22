-- ==========================================================
-- Startup Founder & Investor Portal - Database Schema (Phase 1)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `startup_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `startup_portal`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('founder', 'investor', 'admin') NOT NULL DEFAULT 'founder',
    `status` ENUM('active', 'pending', 'suspended') NOT NULL DEFAULT 'active',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `email_verified_at` DATETIME NULL,
    `avatar_url` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Founder Profiles
CREATE TABLE IF NOT EXISTS `founder_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `bio` TEXT NULL,
    `linkedin_url` VARCHAR(255) NULL,
    `website_url` VARCHAR(255) NULL,
    `pan_number` VARCHAR(20) NULL,
    `designation` VARCHAR(100) DEFAULT 'Founder & CEO',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Investor Profiles
CREATE TABLE IF NOT EXISTS `investor_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `investor_type` VARCHAR(100) DEFAULT 'Angel Investor', -- Angel, VC, Family Office, Syndicate
    `experience_years` INT DEFAULT 3,
    `pan_number` VARCHAR(20) NULL,
    `accreditation_status` ENUM('verified', 'self_declared', 'pending') DEFAULT 'verified',
    `risk_disclosure_accepted` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Investor Preferences
CREATE TABLE IF NOT EXISTS `investor_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `preferred_industries` VARCHAR(255) DEFAULT 'FinTech, AI/SaaS, HealthTech, CleanTech',
    `preferred_stages` VARCHAR(255) DEFAULT 'Seed, Pre-Series A, Series A',
    `min_ticket` DECIMAL(15,2) DEFAULT 200000.00,
    `max_ticket` DECIMAL(15,2) DEFAULT 5000000.00,
    `geography` VARCHAR(100) DEFAULT 'India, Global',
    `investment_thesis` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Companies (Startup Master Record)
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `legal_name` VARCHAR(255) NULL,
    `cin_number` VARCHAR(50) NULL,
    `incorporation_date` DATE NULL,
    `industry` VARCHAR(100) NOT NULL DEFAULT 'FinTech',
    `stage` VARCHAR(50) NOT NULL DEFAULT 'Seed',
    `business_model` VARCHAR(100) DEFAULT 'B2B SaaS',
    `pitch` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `target_market` VARCHAR(255) NULL,
    `employee_count` INT DEFAULT 10,
    `website` VARCHAR(255) NULL,
    `logo_url` VARCHAR(255) NULL,
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) DEFAULT 'Bengaluru',
    `state` VARCHAR(100) DEFAULT 'Karnataka',
    `country` VARCHAR(100) DEFAULT 'India',
    `verified_status` ENUM('unverified', 'pending', 'verified', 'rejected') DEFAULT 'verified',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Company Founders Mapping
CREATE TABLE IF NOT EXISTS `company_founders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `designation` VARCHAR(100) DEFAULT 'Co-Founder & CEO',
    `equity_percent` DECIMAL(5,2) DEFAULT 45.00,
    `is_signatory` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Company Documents
CREATE TABLE IF NOT EXISTS `company_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `document_type` VARCHAR(100) NOT NULL, -- Pitch Deck, Incorporation Cert, GST, Financials, Cap Table
    `title` VARCHAR(200) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` VARCHAR(50) DEFAULT '2.4 MB',
    `is_verified` TINYINT(1) DEFAULT 1,
    `access_level` ENUM('public', 'registered_investors', 'request_only') DEFAULT 'registered_investors',
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Verification Requests (KYC & DigiLocker)
CREATE TABLE IF NOT EXISTS `verification_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `company_id` INT NULL,
    `verification_type` VARCHAR(50) DEFAULT 'digilocker_kyc', -- digilocker_kyc, manual_cin, gst_auth
    `status` ENUM('pending', 'verified', 'rejected', 'additional_info') DEFAULT 'verified',
    `provider_name` VARCHAR(100) DEFAULT 'DigiLocker / National ID',
    `provider_ref_id` VARCHAR(100) NULL,
    `digilocker_uri` VARCHAR(255) NULL,
    `remarks` TEXT NULL,
    `verified_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Funding Rounds
CREATE TABLE IF NOT EXISTS `funding_rounds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `round_name` VARCHAR(150) NOT NULL DEFAULT 'Seed Round',
    `target_amount` DECIMAL(15,2) NOT NULL DEFAULT 5000000.00,
    `min_investment` DECIMAL(15,2) NOT NULL DEFAULT 100000.00,
    `max_investment` DECIMAL(15,2) NULL,
    `amount_raised` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valuation` DECIMAL(15,2) NOT NULL DEFAULT 35000000.00,
    `equity_offered` DECIMAL(5,2) NOT NULL DEFAULT 12.50,
    `status` ENUM('DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'LIVE', 'PARTIALLY_FUNDED', 'FULLY_FUNDED', 'CLOSED', 'REJECTED') DEFAULT 'LIVE',
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `purpose` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Investment Orders
CREATE TABLE IF NOT EXISTS `investment_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `funding_round_id` INT NOT NULL,
    `investor_user_id` INT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `status` ENUM('PENDING', 'CONFIRMED', 'CANCELLED', 'FAILED') DEFAULT 'CONFIRMED',
    `terms_accepted` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`funding_round_id`) REFERENCES `funding_rounds`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`investor_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Confirmed Investments
CREATE TABLE IF NOT EXISTS `investments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `funding_round_id` INT NOT NULL,
    `investor_user_id` INT NOT NULL,
    `company_id` INT NOT NULL,
    `amount_invested` DECIMAL(15,2) NOT NULL,
    `equity_allotted_percent` DECIMAL(6,3) DEFAULT 1.250,
    `certificate_number` VARCHAR(100) NULL,
    `confirmed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `investment_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`funding_round_id`) REFERENCES `funding_rounds`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`investor_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Financial Transactions
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `investment_id` INT NULL,
    `order_id` INT NOT NULL,
    `transaction_ref` VARCHAR(100) NOT NULL UNIQUE,
    `payment_mode` VARCHAR(50) DEFAULT 'Escrow Wire / UPI',
    `amount` DECIMAL(15,2) NOT NULL,
    `status` ENUM('INITIATED', 'SUCCESS', 'FAILED', 'REFUNDED') DEFAULT 'SUCCESS',
    `gateway_response` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `investment_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Conversations (Chat)
CREATE TABLE IF NOT EXISTS `conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_one_id` INT NOT NULL,
    `user_two_id` INT NOT NULL,
    `company_id` INT NULL,
    `last_message_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_one_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_two_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Messages
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender_user_id` INT NOT NULL,
    `message_text` TEXT NOT NULL,
    `attachment_path` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Watchlists
CREATE TABLE IF NOT EXISTS `watchlists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `investor_user_id` INT NOT NULL,
    `company_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_watchlist` (`investor_user_id`, `company_id`),
    FOREIGN KEY (`investor_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. In-App Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) DEFAULT 'info',
    `action_url` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actor_user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` INT NULL,
    `details` TEXT NULL,
    `previous_value` TEXT NULL,
    `new_value` TEXT NULL,
    `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Categories & Industry Taxonomy
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'layers',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Startup Updates (Founder Newsfeed)
CREATE TABLE IF NOT EXISTS `startup_updates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `founder_user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(50) DEFAULT 'Milestone',
    `metrics_summary` VARCHAR(255) NULL,
    `image_url` VARCHAR(255) NULL,
    `content` TEXT NOT NULL,
    `visibility` ENUM('all_investors', 'portfolio_only') DEFAULT 'all_investors',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`founder_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19b. Company Blogs & Trust Stories
CREATE TABLE IF NOT EXISTS `company_blogs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `author_user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NULL,
    `category` VARCHAR(50) DEFAULT 'Company Story',
    `summary` VARCHAR(500) NULL,
    `content` LONGTEXT NOT NULL,
    `cover_image` VARCHAR(255) NULL,
    `read_time_minutes` INT DEFAULT 3,
    `views_count` INT DEFAULT 0,
    `is_published` TINYINT(1) DEFAULT 1,
    `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`author_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Verification Documents
CREATE TABLE IF NOT EXISTS `verification_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `verification_request_id` INT NULL,
    `user_id` INT NOT NULL,
    `company_id` INT NULL,
    `document_type` VARCHAR(100) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` VARCHAR(50) DEFAULT '1.2 MB',
    `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    `verified_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Verification Logs
CREATE TABLE IF NOT EXISTS `verification_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `verification_request_id` INT NOT NULL,
    `actor_user_id` INT NULL,
    `old_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `remarks` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`verification_request_id`) REFERENCES `verification_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Login Sessions & Device Tracking
CREATE TABLE IF NOT EXISTS `login_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `session_token` VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
    `user_agent` VARCHAR(255) NULL,
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Security Events
CREATE TABLE IF NOT EXISTS `security_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `severity` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
    `user_agent` VARCHAR(255) NULL,
    `details` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Password Resets (Secure Token-Based Recovery)
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

