-- ============================================================================
-- Flowaxy CMS - Core Database Tables Structure
-- ============================================================================
-- Все системные таблицы движка в чистом виде
-- Версия: 7.0.0
-- Дата: 2025-11-21
-- PHP: 8.4.0+
-- MySQL: 5.7+ / MariaDB 10.2+
-- ============================================================================

-- ============================================================================
-- 1. СИСТЕМА ПОЛЬЗОВАТЕЛЕЙ И РОЛЕЙ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1.1. Таблица пользователей
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `password` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `role_ids` JSON DEFAULT NULL COMMENT 'JSON массив ID ролей пользователя',
    `session_token` VARCHAR(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Токен сессии для защиты от одновременного входа',
    `last_activity` DATETIME DEFAULT NULL COMMENT 'Время последней активности пользователя',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Статус активности пользователя (1 - активен, 0 - неактивен)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    KEY `idx_email` (`email`),
    KEY `idx_session_token` (`session_token`),
    KEY `idx_last_activity` (`last_activity`),
    KEY `idx_is_active` (`is_active`)
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
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `description` TEXT COLLATE utf8mb4_unicode_ci,
    `version` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `author` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT '0',
    `settings` TEXT COLLATE utf8mb4_unicode_ci,
    `installed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_plugins_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2.2. Настройки плагинов
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plugin_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `plugin_slug` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `setting_key` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `setting_value` TEXT COLLATE utf8mb4_unicode_ci,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `plugin_setting` (`plugin_slug`, `setting_key`),
    KEY `idx_plugin_slug` (`plugin_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. СИСТЕМА ТЕМ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 3.1. Настройки тем
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `theme_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `theme_slug` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `setting_key` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `setting_value` TEXT COLLATE utf8mb4_unicode_ci,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `theme_setting` (`theme_slug`, `setting_key`),
    KEY `idx_theme_slug` (`theme_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. СИСТЕМНЫЕ НАСТРОЙКИ
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 4.1. Настройки сайта
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `setting_value` TEXT COLLATE utf8mb4_unicode_ci,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
--    - Добавлены поля: session_token, last_activity, is_active
--    - Добавлены индексы: idx_session_token, idx_last_activity, idx_is_active
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
-- 
-- ПРИМЕЧАНИЯ:
-- - Все таблицы используют кодировку utf8mb4_unicode_ci
-- - Все таблицы используют движок InnoDB
-- - Все таблицы имеют поля created_at и updated_at с автоматическим обновлением
-- - Таблица users содержит дополнительные поля для управления сессиями
-- - Все внешние ключи используют ON DELETE CASCADE
-- ============================================================================
