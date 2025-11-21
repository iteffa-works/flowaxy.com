<?php
/**
 * Миграция: Добавление полей для отслеживания активности сессии
 * last_activity - время последней активности
 * is_active - статус активности пользователя
 * 
 * @package Engine\Includes\Migrations
 */

declare(strict_types=1);

/**
 * Выполнение миграции
 * 
 * @param PDO $db Подключение к базе данных
 * @return bool Успешность выполнения
 */
function migration_add_session_activity_to_users(PDO $db): bool {
    try {
        // Проверяем, существует ли поле last_activity
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
        $lastActivityExists = $stmt->fetch() !== false;
        
        if (!$lastActivityExists) {
            // Добавляем поле last_activity
            $db->exec("ALTER TABLE users ADD COLUMN last_activity DATETIME DEFAULT NULL COMMENT 'Время последней активности пользователя' AFTER session_token");
            
            // Добавляем индекс для быстрого поиска
            $db->exec("ALTER TABLE users ADD INDEX idx_last_activity (last_activity)");
            
            if (class_exists('Logger')) {
                Logger::getInstance()->logInfo("Migration: Added last_activity field to users table");
            }
        }
        
        // Проверяем, существует ли поле is_active
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        $isActiveExists = $stmt->fetch() !== false;
        
        if (!$isActiveExists) {
            // Добавляем поле is_active (по умолчанию 1 - активен)
            $db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT 'Статус активности пользователя (1 - активен, 0 - неактивен)' AFTER last_activity");
            
            // Добавляем индекс для быстрого поиска
            $db->exec("ALTER TABLE users ADD INDEX idx_is_active (is_active)");
            
            // Устанавливаем всех существующих пользователей как активных
            $db->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
            
            if (class_exists('Logger')) {
                Logger::getInstance()->logInfo("Migration: Added is_active field to users table");
            }
        }
        
        return true;
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::getInstance()->logError('Migration error', ['error' => $e->getMessage()]);
        }
        return false;
    }
}

