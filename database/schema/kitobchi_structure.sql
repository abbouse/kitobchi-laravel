-- Avtogeneratsiya: kitobchi.sql dan CREATE TABLE (INSERT yo‘q)
/*!40101 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('superadmin','admin','moderator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moderator',
  `permissions` json DEFAULT NULL COMMENT 'Ruxsatlar: ["users","books",...]',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`),
  KEY `admins_role_index` (`role`),
  KEY `admins_is_active_index` (`is_active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `api_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abilities` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_id` (`app_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `icon` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_uz` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ru` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ja` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_club` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_type` enum('book','stationery','order') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_id` bigint DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `repost` tinyint(1) DEFAULT '0',
  `reposted_user_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`,`product_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_club_comment_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comment_id` (`comment_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `book_club_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `content` text NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_id` (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `book_club_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_club_likes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_club_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('like','comment','reply','comment_like','vote','new_post','follow','repost') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `post_id` bigint unsigned DEFAULT NULL,
  `group_key` varchar(255) DEFAULT NULL,
  `data` json NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `book_club_themes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firework` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_club_voted_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `option_id` bigint unsigned NOT NULL,
  `post_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_club_voted_users_vote_id_user_id_unique` (`option_id`,`user_id`),
  KEY `book_club_voted_users_user_id_foreign` (`user_id`),
  CONSTRAINT `book_club_voted_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_club_voted_users_vote_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `book_club_votes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_club_votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `option_text` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_discount` (
  `book_id` int unsigned NOT NULL,
  `discount_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`book_id`,`discount_id`),
  KEY `discount_id` (`discount_id`),
  KEY `book_id` (`book_id`),
  KEY `book_id_2` (`book_id`),
  CONSTRAINT `book_discount_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `book_discount_ibfk_2` FOREIGN KEY (`discount_id`) REFERENCES `seller_staff_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `book_tag_relations` (
  `book_id` int unsigned NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`book_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `book_tag_relations_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`),
  CONSTRAINT `book_tag_relations_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `book_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `book_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tag_name_uz` varchar(255) NOT NULL,
  `tag_name_ru` varchar(255) NOT NULL,
  `tag_name_en` varchar(255) NOT NULL,
  `tag_name_ja` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name_uz`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `books` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int NOT NULL,
  `images` json DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int NOT NULL,
  `discountPrice` int DEFAULT '0',
  `discountExpiresAt` timestamp NULL DEFAULT NULL,
  `count` int NOT NULL DEFAULT '0',
  `lang` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'O''zbek',
  `langType` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Lotin',
  `coverType` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yumshoq',
  `year` int unsigned NOT NULL DEFAULT '2024',
  `pages` int DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `seller_id` bigint NOT NULL,
  `is_hidden` tinyint(1) DEFAULT '0',
  `is_approved` tinyint(1) DEFAULT '0',
  `totalSales` int NOT NULL DEFAULT '0',
  `totalRevenue` int NOT NULL DEFAULT '0',
  `totalClients` int NOT NULL DEFAULT '0',
  `totalSalesWeek` int DEFAULT '0',
  `totalClientsWeek` int DEFAULT '0',
  `totalRevenueWeek` int DEFAULT '0',
  `recommended` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `vectorData` json DEFAULT NULL,
  `recommendedExpiresAt` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `books_fulltext_search` (`name`,`author`,`description`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `bot_operator_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operator_id` bigint DEFAULT NULL,
  `handled` int DEFAULT '0',
  `closed` int DEFAULT '0',
  `total_rated` int DEFAULT '0',
  `avg_rating` decimal(3,2) DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operator_id` (`operator_id`),
  KEY `idx_operator_id` (`operator_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `bot_operators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `telegram_id` bigint NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `status` enum('online','busy','offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'online',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `telegram_id` (`telegram_id`),
  KEY `idx_telegram_id` (`telegram_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `bot_ticket_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `file_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `sent_by` enum('user','operator') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_ticket_attachments_ticket_id_index` (`ticket_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `bot_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'empty',
  `name` varchar(255) DEFAULT NULL,
  `operator_id` bigint DEFAULT NULL,
  `status` enum('queue','active','closed','rated') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'queue',
  `first_msg` text,
  `rating` tinyint unsigned DEFAULT NULL,
  `close_reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_operator_id` (`operator_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `cashback_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fromUzs` int DEFAULT '0',
  `toUzs` int DEFAULT '0',
  `cashback` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `category_tag_relations` (
  `category_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`category_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `category_tag_relations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`id`),
  CONSTRAINT `category_tag_relations_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `book_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `chat_message_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_message_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_type` enum('stationery','book') DEFAULT NULL,
  `type` varchar(50) DEFAULT 'books',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_message_id` (`chat_message_id`),
  CONSTRAINT `chat_message_items_ibfk_1` FOREIGN KEY (`chat_message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_ai` tinyint(1) DEFAULT '0',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `commission_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `priceFrom` int NOT NULL,
  `priceTo` int NOT NULL,
  `percent` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `connected_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `user_type` enum('seller','courier','user') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `device_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `platform` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `fcm_token` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('personal','shop') NOT NULL DEFAULT 'personal',
  `user_id` bigint unsigned NOT NULL,
  `receiver_id` bigint unsigned DEFAULT NULL,
  `shop_id` bigint DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `hidden_by` json DEFAULT NULL,
  `messages_hidden_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shop_id` (`shop_id`),
  KEY `idx_type` (`type`),
  KEY `idx_user_id` (`user_id`) USING BTREE,
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversations_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `courier_ban_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `courier_id` bigint NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('warning','unban') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `courier_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `courier_id` int NOT NULL,
  `text` text NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `courier_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `seller_id` int NOT NULL,
  `seller_location_id` int DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `price` int DEFAULT '0',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `courier_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `courier_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` enum('pay_process','pending','in_delivery','delivered','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pay_process',
  `amount` int DEFAULT '0',
  `courierPrice` int DEFAULT '0',
  `courierBonus` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_courier_orders_user` (`user_id`),
  CONSTRAINT `fk_courier_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `courier_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `courier_id` int NOT NULL,
  `card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int NOT NULL,
  `commissionPercent` int DEFAULT '0',
  `commissionPrice` int DEFAULT '0',
  `netAmount` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejected_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `couriers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `region` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fcm_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` int DEFAULT '0',
  `total_withdrawal` int NOT NULL DEFAULT '0',
  `payment_card` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `deleted_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `delivery_services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('courier_service','mail_service') COLLATE utf8mb4_unicode_ci DEFAULT 'mail_service',
  `priceKg` int NOT NULL,
  `muddat` int NOT NULL DEFAULT '3',
  `forCountry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'uzbekistan',
  `capital` tinyint(1) DEFAULT '0',
  `freePriceFrom` int NOT NULL DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `favourite_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_type` enum('book','stationery','ebook','audiobook') NOT NULL,
  `variant_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `fcm_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `who` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `gift_certificates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `buyer_user_id` bigint unsigned NOT NULL,
  `recipient_user_id` bigint unsigned DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal_uzs` int unsigned NOT NULL,
  `status` enum('pending_payment','paid','used','cancelled','active','payment_cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending_payment',
  `message` text COLLATE utf8mb4_unicode_ci,
  `activated_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_gift_buyer` (`buyer_user_id`),
  KEY `idx_gift_status` (`status`),
  KEY `fk_gift_recipient` (`recipient_user_id`),
  CONSTRAINT `fk_gift_buyer` FOREIGN KEY (`buyer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gift_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `gifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `images` json NOT NULL,
  `is_approved` int DEFAULT '0',
  `stock` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `priceFrom` int NOT NULL DEFAULT '0',
  `priceTo` int NOT NULL DEFAULT '50000',
  `totalSales` bigint DEFAULT '0',
  `totalRevenue` bigint DEFAULT '0',
  `totalClients` bigint DEFAULT '0',
  `totalSalesWeek` bigint DEFAULT '0',
  `totalRevenueWeek` bigint DEFAULT '0',
  `totalClientsWeek` bigint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `haggle_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `cart_total` double(12,2) NOT NULL,
  `floor_price` double(12,2) NOT NULL,
  `current_offer` double(12,2) NOT NULL DEFAULT '0.00',
  `round_count` int NOT NULL DEFAULT '0',
  `stage` int NOT NULL DEFAULT '1',
  `agreed` tinyint(1) NOT NULL DEFAULT '0',
  `agreed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `haggle_sessions_user_id_index` (`user_id`),
  KEY `haggle_sessions_user_id_agreed_created_at_index` (`user_id`,`agreed`,`created_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `interests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `lat` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullAddress` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `isDeleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `market_news` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `imgUrl` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `align` enum('top','center') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'top',
  `action` enum('to_shop','to_product','news') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_id` bigint DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `sender_type` varchar(255) NOT NULL,
  `reply_to_id` bigint DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) DEFAULT '0',
  `hidden_by` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `my_carts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `count_item` int NOT NULL DEFAULT '0',
  `product_type` enum('book','stationery','gift') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `mystery_box_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `month_number` tinyint unsigned NOT NULL,
  `book_ids` json DEFAULT NULL,
  `status` enum('pending','preparing','shipped','delivered') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `tracking_note` text COLLATE utf8mb4_unicode_ci,
  `prepared_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub_month` (`subscription_id`,`month_number`),
  KEY `idx_delivery_status` (`status`),
  CONSTRAINT `fk_delivery_sub` FOREIGN KEY (`subscription_id`) REFERENCES `mystery_box_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `mystery_box_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_uz` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ru` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_en` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ja` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `months` tinyint unsigned NOT NULL,
  `price_uzs` int unsigned NOT NULL,
  `books_per_month` tinyint unsigned DEFAULT '2',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` smallint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `mystery_box_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `address` json DEFAULT NULL,
  `status` enum('pending_payment','active','paused','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending_payment',
  `total_months` tinyint unsigned NOT NULL,
  `books_per_month` tinyint unsigned NOT NULL,
  `price_uzs` int unsigned NOT NULL,
  `delivered_months` tinyint unsigned DEFAULT '0',
  `next_delivery_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sub_status_delivery` (`status`,`next_delivery_at`),
  KEY `idx_sub_user` (`user_id`),
  KEY `fk_sub_plan` (`plan_id`),
  CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `mystery_box_plans` (`id`),
  CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `project_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `business_version_ios` varchar(20) DEFAULT '1.0.0',
  `business_version_android` varchar(20) DEFAULT '1.0.0',
  `courier_version_ios` varchar(20) DEFAULT '1.0.0',
  `courier_version_android` varchar(20) DEFAULT '1.0.0',
  `market_version_ios` varchar(20) DEFAULT '1.0.0',
  `market_version_android` varchar(20) DEFAULT '1.0.0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `promocode_histories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `promocode_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `promocodes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percent','uzs') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'uzs',
  `amount` int NOT NULL DEFAULT '0',
  `min_order_amount` int DEFAULT '0',
  `usesLimit` int DEFAULT '0',
  `usedCount` int DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `reel_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reel_id` bigint unsigned NOT NULL,
  `video_720p` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_480p` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_360p` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_reel_items_reel_id` (`reel_id`),
  CONSTRAINT `fk_reel_items_reel_id` FOREIGN KEY (`reel_id`) REFERENCES `reels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `reels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `regions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_uz` varchar(60) DEFAULT NULL,
  `name_oz` varchar(60) DEFAULT NULL,
  `name_ru` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3;


CREATE TABLE IF NOT EXISTS `reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `reportable_id` bigint unsigned NOT NULL,
  `reportable_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','reviewed','dismissed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reportable` (`reportable_type`,`reportable_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `role_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `emoji` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_uz` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ru` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ja` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `needs_place` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `search_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `session_id` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `search_count` int DEFAULT '0',
  `result_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_draft` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_ad_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('top_banner','center_banner','recommendation','premium_1m','premium_3m','premium_6m') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `price` int NOT NULL,
  `region` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `seller_ads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `type` enum('top_banner','center_banner') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` text,
  `action` enum('to_book','to_shop') NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_type` enum('book','chancellery','none') NOT NULL,
  `banner_img` varchar(255) DEFAULT NULL,
  `days` int NOT NULL,
  `expire_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `moderation` enum('pending','approved','rejected') DEFAULT 'pending',
  `paymentStatus` enum('pending','success','rejected') DEFAULT 'pending',
  `amount` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `seller_ban_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('warning','unban') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_ban_logs_seller_id_is_read_index` (`seller_id`,`is_read`),
  KEY `seller_ban_logs_created_at_index` (`created_at`),
  CONSTRAINT `seller_ban_logs_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_contest_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_contest_id` bigint unsigned NOT NULL,
  `participant_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_participant_id` (`participant_id`),
  KEY `idx_seller_contest_id` (`seller_contest_id`) USING BTREE,
  CONSTRAINT `seller_contest_participants_ibfk_1` FOREIGN KEY (`seller_contest_id`) REFERENCES `seller_contests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_contests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_date` datetime NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `winner_count` int DEFAULT '1',
  `winners` json DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_status` (`status`),
  KEY `idx_end_date` (`end_date`),
  CONSTRAINT `seller_contests_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_location_workdays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `location_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `seller_locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `lat` varchar(50) NOT NULL,
  `lon` varchar(50) NOT NULL,
  `fullAddress` varchar(255) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `seller_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` bigint NOT NULL,
  `title` varchar(64) NOT NULL,
  `content` text NOT NULL,
  `isRead` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `seller_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint DEFAULT NULL,
  `type` enum('book','stationery','gift') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'book',
  `quantity` int DEFAULT '1',
  `price` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `courier_id` bigint DEFAULT NULL,
  `courierName` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `delivery_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` json DEFAULT NULL,
  `status` int DEFAULT '0',
  `reject_desc` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_staff_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `seller_staff_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `seller_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'income',
  `category` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` int NOT NULL,
  `commissionPercent` int DEFAULT '0',
  `commissionPrice` int DEFAULT '0',
  `netAmount` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejected_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `sellers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT '0',
  `role` int DEFAULT '1',
  `staff_status` enum('active','inactive') DEFAULT 'active',
  `shop_name` varchar(55) DEFAULT NULL,
  `balance` int DEFAULT '0',
  `total_withdrawal` int DEFAULT '0',
  `commission_percent` int DEFAULT NULL,
  `payment_card` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `firstname` varchar(40) DEFAULT NULL,
  `lastname` varchar(40) DEFAULT NULL,
  `phone_number` varchar(13) DEFAULT NULL,
  `region` varchar(30) DEFAULT NULL,
  `activity_types` json DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_reset_limit` int DEFAULT '3',
  `remember_token` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','banned') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `isVerified` tinyint(1) DEFAULT '0',
  `is_hidden` tinyint(1) DEFAULT '1',
  `isPremiumShop` tinyint(1) DEFAULT '0',
  `isPremiumExpiresAt` timestamp NULL DEFAULT NULL,
  `tg_channel_id` bigint DEFAULT NULL,
  `tg_token` varchar(255) DEFAULT NULL,
  `tg_lang` enum('uz','ru') DEFAULT 'uz',
  `tg_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT '0.00',
  `successful_orders` int DEFAULT '0',
  `total_reviews` int DEFAULT '0',
  `response_time_hours` decimal(5,2) DEFAULT '24.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `shared_carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT 'kim ulashgan',
  `slug` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'random havola kodi',
  `items` json NOT NULL COMMENT 'ulashilgan mahsulotlar snapshot',
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cart' COMMENT '''cart'' | ''order''',
  `order_id` bigint unsigned DEFAULT NULL COMMENT 'agar orderdan bo''lsa',
  `view_count` int unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'null = abadiy',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shared_carts_slug_unique` (`slug`),
  KEY `shared_carts_user_id_foreign` (`user_id`),
  CONSTRAINT `shared_carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `solds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deliveryType` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deliveryPrice` int DEFAULT NULL,
  `items` json DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `courier_id` int DEFAULT NULL,
  `courierName` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` int DEFAULT '0',
  `status` enum('A','P','B','C','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'A',
  `paymentStatus` int DEFAULT '1',
  `paymentMethod` enum('naqd','payme','click','rahmatpay') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` json DEFAULT NULL,
  `qr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift` bigint unsigned DEFAULT NULL,
  `buyerWish` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `promocode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift_certificate_id` int DEFAULT NULL,
  `giftCertAmount` int unsigned DEFAULT '0',
  `is_gift_to_other` tinyint(1) NOT NULL DEFAULT '0',
  `with_packaging` tinyint(1) NOT NULL DEFAULT '0',
  `packaging_price` int NOT NULL DEFAULT '0',
  `recipient_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_region` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `withCashback` tinyint(1) DEFAULT '0',
  `discountAmount` int DEFAULT '0',
  `cashbackAmount` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_solds_user` (`user_id`),
  CONSTRAINT `fk_solds_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `stationeries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `images` json NOT NULL,
  `material` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `price` int NOT NULL,
  `discount_price` int DEFAULT NULL,
  `discountExpiresAt` timestamp NULL DEFAULT NULL,
  `stock` int NOT NULL,
  `description` text NOT NULL,
  `is_approved` int DEFAULT '0',
  `is_hidden` tinyint(1) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `totalSales` int DEFAULT '0',
  `totalRevenue` int DEFAULT '0',
  `totalSalesWeek` int DEFAULT '0',
  `totalClients` int DEFAULT '0',
  `totalClientsWeek` int DEFAULT '0',
  `totalRevenueWeek` int DEFAULT '0',
  `recommended` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `vectorData` json DEFAULT NULL,
  `recommendedExpiresAt` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `stationeries_fulltext_search` (`name`,`material`,`description`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `stationery_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ru` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_uz` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ja` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `stationery_categories_slug_unique` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `stationery_category_tag` (
  `category_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`category_id`,`tag_id`),
  KEY `stationery_category_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `stationery_category_tag_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `stationery_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stationery_category_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `stationery_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `stationery_tag_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stationery_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_stationery` (`stationery_id`),
  KEY `fk_tag` (`tag_id`),
  CONSTRAINT `fk_stationery` FOREIGN KEY (`stationery_id`) REFERENCES `stationeries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tag` FOREIGN KEY (`tag_id`) REFERENCES `stationery_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `stationery_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_ru` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_uz` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ja` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `stationery_tags_slug_unique` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `stationery_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `color_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stationery_variants_product_id_index` (`product_id`),
  CONSTRAINT `stationery_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `stationeries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'monthly, quarterly, yearly',
  `starts_at` timestamp NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'active, cancelled, expired',
  `payment_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_user_id_index` (`user_id`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paycom_transaction_id` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paycom_time` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paycom_time_datetime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `perform_time` datetime DEFAULT NULL,
  `cancel_time` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` int DEFAULT NULL,
  `payment_type` enum('order','gift_certificate','mystery_box') COLLATE utf8mb4_unicode_ci DEFAULT 'order',
  `state` tinyint DEFAULT NULL,
  `reason` tinyint DEFAULT NULL,
  `receivers` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order_id` int DEFAULT NULL,
  `perform_time_unix` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `user_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `card_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Niqoblangan karta raqami: 860006******6311',
  `payme_token` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Payme tomonidan berilgan recurrent tolov uchun token',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT 'Karta SMS orqali tasdiqlanganmi',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_cards_user_id` (`user_id`),
  CONSTRAINT `fk_user_cards_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `user_follows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `follower_id` bigint unsigned NOT NULL,
  `following_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_follows_unique` (`follower_id`,`following_id`),
  KEY `fk_following` (`following_id`),
  CONSTRAINT `fk_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `telegram_id` bigint DEFAULT '0',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sex` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'O''quvchi',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `role_emoji` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_place` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_preset_id` int DEFAULT NULL,
  `real_balance` int DEFAULT '0',
  `cashback` int DEFAULT '0',
  `status` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isDeleted` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'no',
  `mainAddressID` int DEFAULT NULL,
  `ai_limit` int NOT NULL DEFAULT '25',
  `firstEdit` tinyint(1) DEFAULT '1',
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `verifyCode` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isVerified` tinyint(1) DEFAULT '0',
  `isSupport` tinyint(1) DEFAULT '0',
  `is_premium` tinyint(1) DEFAULT '0',
  `premium_until` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_seconds_spent` bigint DEFAULT '0',
  `locale` enum('uz','ru','ja','en') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'uz',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `fk_users_main_address` (`mainAddressID`),
  CONSTRAINT `fk_users_main_address` FOREIGN KEY (`mainAddressID`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*!40101 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
