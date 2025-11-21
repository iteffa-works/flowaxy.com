-- Система ролей и прав доступа
-- Создание таблиц для управления ролями и разрешениями

-- Таблица ролей
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

-- Таблица разрешений
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

-- Связь ролей и разрешений (многие ко многим)
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

-- Роли пользователей теперь хранятся в таблице users в поле role_ids (JSON)
-- Пример: [1, 2, 3] - массив ID ролей пользователя

-- Вставка базовых ролей (только системные: Guest, user, developer)
INSERT IGNORE INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Разработчик', 'developer', 'Полный доступ ко всем функциям системы. Роль создается только при установке движка и не может быть удалена.', 1),
('Пользователь', 'user', 'Обычный пользователь с базовыми правами', 1),
('Гость', 'guest', 'Базовая роль для неавторизованных пользователей', 1);

-- Вставка базовых разрешений
INSERT IGNORE INTO `permissions` (`name`, `slug`, `description`, `category`) VALUES
-- Админка
('Доступ к админ-панели', 'admin.access', 'Доступ к административной панели', 'admin'),
('Управление плагинами', 'admin.plugins', 'Установка, активация и удаление плагинов', 'admin'),
('Управление темами', 'admin.themes', 'Установка и активация тем', 'admin'),
('Управление настройками', 'admin.settings', 'Изменение системных настроек', 'admin'),
('Просмотр логов', 'admin.logs.view', 'Просмотр системных логов', 'admin'),
('Управление пользователями', 'admin.users', 'Создание, редактирование и удаление пользователей', 'admin'),
('Управление ролями', 'admin.roles', 'Управление ролями и правами доступа', 'admin');

-- Назначение разрешений роли разработчика (все разрешения)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'developer';

-- Назначение базовых разрешений роли пользователя
-- (Разрешения кабинета удалены, так как кабинет - это плагин)

