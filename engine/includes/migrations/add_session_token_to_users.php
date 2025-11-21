<?php
/**
 * Миграция: Добавление поля session_token в таблицу users
 * Для защиты от одновременного входа с разных устройств
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
function migration_add_session_token_to_users(PDO $db): bool {
    try {
        // Проверяем, существует ли поле session_token
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'session_token'");
        $columnExists = $stmt->fetch() !== false;
        
        if (!$columnExists) {
            // Добавляем поле session_token
            $db->exec("ALTER TABLE users ADD COLUMN session_token VARCHAR(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Токен сессии для защиты от одновременного входа' AFTER role_ids");
            
            // Добавляем индекс для быстрого поиска
            $db->exec("ALTER TABLE users ADD INDEX idx_session_token (session_token)");
            
            error_log("Migration: Added session_token field to users table");
            return true;
        } else {
            error_log("Migration: session_token field already exists in users table");
            return true;
        }
    } catch (Exception $e) {
        error_log("Migration error: " . $e->getMessage());
        return false;
    }
}

