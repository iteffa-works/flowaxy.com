-- ============================================================================
-- Flowaxy CMS - Core Database Tables Structure
-- ============================================================================
-- Все системные таблицы движка в чистом виде
-- Версия: 1.0.0
-- Дата: 2025-01-21
-- ============================================================================

-- ============================================================================
-- 1. СИСТЕМА ПОЛЬЗОВАТЕЛЕЙ И РОЛЕЙ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1.1. Таблица пользователей
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `role_ids` JSON DEFAULT NULL COMMENT 'JSON массив ID ролей пользователя',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 1.2. Таблица ролей
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_system` TINYINT(1) DEFAULT 0 COMMENT 'Системная роль (нельзя удалить)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 1.3. Таблица разрешений (permissions)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL COMMENT 'Категория разрешения (admin, cabinet, plugin, etc.)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `category` (`category`),
    KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 1.4. Связь ролей и разрешений (many-to-many)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` INT(11) UNSIGNED NOT NULL,
    `permission_id` INT(11) UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_permission` (`role_id`, `permission_id`),
    KEY `role_id` (`role_id`),
    KEY `permission_id` (`permission_id`),
    CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. СИСТЕМА ПЛАГИНОВ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 2.1. Таблица плагинов
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 2.2. Настройки плагинов
-- ----------------------------------------------------------------------------
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

-- ============================================================================
-- 3. СИСТЕМА ТЕМ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 3.1. Настройки тем
-- ----------------------------------------------------------------------------
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

-- ============================================================================
-- 4. СИСТЕМНЫЕ НАСТРОЙКИ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 4.1. Настройки сайта
-- ----------------------------------------------------------------------------
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

-- ============================================================================
-- 5. API И WEBHOOKS
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 5.1. API ключи
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Название API ключа',
    `key_hash` VARCHAR(255) NOT NULL COMMENT 'Хеш API ключа',
    `key_preview` VARCHAR(20) NOT NULL COMMENT 'Первые 4 символа ключа для отображения',
    `permissions` TEXT DEFAULT NULL COMMENT 'JSON массив разрешений',
    `last_used_at` DATETIME DEFAULT NULL COMMENT 'Последнее использование',
    `expires_at` DATETIME DEFAULT NULL COMMENT 'Срок действия',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активен ли ключ',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_hash` (`key_hash`),
    KEY `idx_key_hash` (`key_hash`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5.2. Webhooks
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `webhooks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Название webhook',
    `url` TEXT NOT NULL COMMENT 'URL для отправки',
    `secret` VARCHAR(255) DEFAULT NULL COMMENT 'Секретный ключ для подписи',
    `events` TEXT DEFAULT NULL COMMENT 'JSON массив событий для отслеживания',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активен ли webhook',
    `last_triggered_at` DATETIME DEFAULT NULL COMMENT 'Последний вызов',
    `success_count` INT UNSIGNED DEFAULT 0 COMMENT 'Количество успешных отправок',
    `failure_count` INT UNSIGNED DEFAULT 0 COMMENT 'Количество неудачных отправок',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ИТОГО: 10 системных таблиц
-- ============================================================================
-- 1. users - пользователи системы
-- 2. roles - роли пользователей
-- 3. permissions - разрешения
-- 4. role_permissions - связь ролей и разрешений
-- 5. plugins - плагины
-- 6. plugin_settings - настройки плагинов
-- 7. theme_settings - настройки тем
-- 8. site_settings - настройки сайта
-- 9. api_keys - API ключи
-- 10. webhooks - webhooks для интеграций
-- ============================================================================

