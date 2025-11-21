<?php
/**
 * Міграція: Додавання поля session_token в таблицю users
 * Для захисту від одночасного входу з різних пристроїв
 * 
 * @package Engine\Includes\Migrations
 */

declare(strict_types=1);

/**
 * Виконання міграції
 * 
 * @param PDO $db Підключення до бази даних
 * @return bool Успішність виконання
 */
function migration_add_session_token_to_users(PDO $db): bool {
    try {
        // Перевіряємо, чи існує поле session_token
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'session_token'");
        $columnExists = $stmt->fetch() !== false;
        
        if (!$columnExists) {
            // Додаємо поле session_token
            $db->exec("ALTER TABLE users ADD COLUMN session_token VARCHAR(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Токен сесії для захисту від одночасного входу' AFTER role_ids");
            
            // Додаємо індекс для швидкого пошуку
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

