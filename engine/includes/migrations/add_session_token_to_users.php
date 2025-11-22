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
            
            // Додаємо індекс для швидкого пошуку (якщо його ще немає)
            try {
                $indexStmt = $db->query("SHOW INDEX FROM users WHERE Key_name = 'idx_session_token'");
                $indexExists = $indexStmt->fetch() !== false;
                if (!$indexExists) {
                    $db->exec("ALTER TABLE users ADD INDEX idx_session_token (session_token)");
                }
            } catch (Exception $e) {
                // Індекс може не додатися, якщо вже існує - це нормально
            }
            
            error_log("Migration: Added session_token field to users table");
            return true;
        }
        
        // Поле вже існує - це нормально, міграція вже була виконана
        return true;
    } catch (Exception $e) {
        error_log("Migration error: " . $e->getMessage());
        return false;
    }
}

