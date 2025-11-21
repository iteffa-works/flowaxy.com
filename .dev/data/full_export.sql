-- --------------------------------------------------------
-- Сервер:                       127.0.1.26
-- Версія сервера:               5.7.44 - MySQL Community Server (GPL)
-- ОС сервера:                   Win64
-- HeidiSQL Версія:              12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for db_flowaxy
CREATE DATABASE IF NOT EXISTS `db_flowaxy` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `db_flowaxy`;

-- Dumping structure for таблиця db_flowaxy.api_keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название API ключа',
  `key_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Хеш API ключа',
  `key_preview` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Первые 4 символа ключа для отображения',
  `permissions` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON массив разрешений',
  `last_used_at` datetime DEFAULT NULL COMMENT 'Последнее использование',
  `expires_at` datetime DEFAULT NULL COMMENT 'Срок действия',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Активен ли ключ',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`),
  KEY `idx_key_hash` (`key_hash`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.api_keys: ~0 rows (приблизно)

-- Dumping structure for таблиця db_flowaxy.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Категория разрешения (admin, cabinet, plugin, etc.)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category` (`category`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.permissions: ~11 rows (приблизно)
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `category`, `created_at`, `updated_at`) VALUES
	(1, 'Доступ к админ-панели', 'admin.access', 'Доступ к административной панели', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(2, 'Управление плагинами', 'admin.plugins', 'Установка, активация и удаление плагинов', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(3, 'Управление темами', 'admin.themes', 'Установка и активация тем', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(4, 'Управление настройками', 'admin.settings', 'Изменение системных настроек', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(5, 'Просмотр логов', 'admin.logs.view', 'Просмотр системных логов', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(6, 'Управление пользователями', 'admin.users', 'Создание, редактирование и удаление пользователей', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(7, 'Управление ролями', 'admin.roles', 'Управление ролями и правами доступа', 'admin', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(8, 'Доступ к кабинету', 'cabinet.access', 'Доступ к личному кабинету', 'cabinet', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(9, 'Редактирование профиля', 'cabinet.profile.edit', 'Редактирование собственного профиля', 'cabinet', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(10, 'Просмотр настроек', 'cabinet.settings.view', 'Просмотр настроек кабинета', 'cabinet', '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(11, 'Изменение настроек', 'cabinet.settings.edit', 'Изменение настроек кабинета', 'cabinet', '2025-11-21 07:24:48', '2025-11-21 07:24:48');

-- Dumping structure for таблиця db_flowaxy.plugins
CREATE TABLE IF NOT EXISTS `plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `settings` text COLLATE utf8mb4_unicode_ci,
  `installed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_plugins_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.plugins: ~0 rows (приблизно)

-- Dumping structure for таблиця db_flowaxy.plugin_settings
CREATE TABLE IF NOT EXISTS `plugin_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plugin_setting` (`plugin_slug`,`setting_key`),
  KEY `idx_plugin_slug` (`plugin_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.plugin_settings: ~0 rows (приблизно)

-- Dumping structure for таблиця db_flowaxy.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) DEFAULT '0' COMMENT 'Системная роль (нельзя удалить)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.roles: ~4 rows (приблизно)
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
	(1, 'Разработчик', 'developer', 'Полный доступ ко всем функциям системы. Роль создается только при установке движка и не может быть удалена.', 1, '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(2, 'Администратор', 'admin', 'Полный доступ ко всем функциям системы', 1, '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(3, 'Пользователь', 'user', 'Обычный пользователь с базовыми правами', 1, '2025-11-21 07:24:48', '2025-11-21 07:24:48'),
	(4, 'Модератор', 'moderator', 'Модератор с расширенными правами', 0, '2025-11-21 07:24:48', '2025-11-21 07:24:48');

-- Dumping structure for таблиця db_flowaxy.role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL,
  `permission_id` int(11) unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission` (`role_id`,`permission_id`),
  KEY `role_id` (`role_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.role_permissions: ~22 rows (приблизно)
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
	(1, 1, 1, '2025-11-21 07:24:48'),
	(2, 1, 2, '2025-11-21 07:24:48'),
	(3, 1, 3, '2025-11-21 07:24:48'),
	(4, 1, 4, '2025-11-21 07:24:48'),
	(5, 1, 5, '2025-11-21 07:24:48'),
	(6, 1, 6, '2025-11-21 07:24:48'),
	(7, 1, 7, '2025-11-21 07:24:48'),
	(8, 1, 8, '2025-11-21 07:24:48'),
	(9, 1, 9, '2025-11-21 07:24:48'),
	(10, 1, 10, '2025-11-21 07:24:48'),
	(11, 1, 11, '2025-11-21 07:24:48'),
	(12, 2, 1, '2025-11-21 07:24:48'),
	(13, 2, 2, '2025-11-21 07:24:48'),
	(14, 2, 3, '2025-11-21 07:24:48'),
	(15, 2, 4, '2025-11-21 07:24:48'),
	(16, 2, 5, '2025-11-21 07:24:48'),
	(17, 2, 6, '2025-11-21 07:24:48'),
	(18, 2, 7, '2025-11-21 07:24:48'),
	(19, 3, 8, '2025-11-21 07:24:48'),
	(20, 3, 9, '2025-11-21 07:24:48'),
	(21, 3, 11, '2025-11-21 07:24:48'),
	(22, 3, 10, '2025-11-21 07:24:48');

-- Dumping structure for таблиця db_flowaxy.site_settings
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.site_settings: ~0 rows (приблизно)

-- Dumping structure for таблиця db_flowaxy.theme_settings
CREATE TABLE IF NOT EXISTS `theme_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `theme_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme_setting` (`theme_slug`,`setting_key`),
  KEY `idx_theme_slug` (`theme_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.theme_settings: ~0 rows (приблизно)

-- Dumping structure for таблиця db_flowaxy.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.users: ~1 rows (приблизно)
INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`, `updated_at`) VALUES
	(1, 'admin', '$2y$12$en.oqP5W5uBoAIuBes4fnOXh4fV7Tn1p6T/kwK7kViTnc2PfoOZeW', 'ua.iteffa@gmail.com', '2025-11-21 07:24:48', '2025-11-21 07:24:48');

-- Dumping structure for таблиця db_flowaxy.user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `role_id` int(11) unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_role` (`user_id`,`role_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.user_roles: ~1 rows (приблизно)
INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `created_at`) VALUES
	(1, 1, 1, '2025-11-21 07:24:48');

-- Dumping structure for таблиця db_flowaxy.webhooks
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название webhook',
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL для отправки',
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Секретный ключ для подписи',
  `events` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON массив событий для отслеживания',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Активен ли webhook',
  `last_triggered_at` datetime DEFAULT NULL COMMENT 'Последний вызов',
  `success_count` int(10) unsigned DEFAULT '0' COMMENT 'Количество успешных отправок',
  `failure_count` int(10) unsigned DEFAULT '0' COMMENT 'Количество неудачных отправок',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_flowaxy.webhooks: ~0 rows (приблизно)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
