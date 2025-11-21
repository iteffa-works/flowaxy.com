<?php
/**
 * Міграція: Додавання полів для відстеження активності сесії
 * last_activity - час останньої активності
 * is_active - статус активності користувача
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
function migration_add_session_activity_to_users(PDO $db): bool {
    try {
        // Перевіряємо, чи існує поле last_activity
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
        $lastActivityExists = $stmt->fetch() !== false;
        
        if (!$lastActivityExists) {
            // Додаємо поле last_activity
            $db->exec("ALTER TABLE users ADD COLUMN last_activity DATETIME DEFAULT NULL COMMENT 'Час останньої активності користувача' AFTER session_token");
            
            // Додаємо індекс для швидкого пошуку
            $db->exec("ALTER TABLE users ADD INDEX idx_last_activity (last_activity)");
            
            if (class_exists('Logger')) {
                Logger::getInstance()->logInfo("Migration: Added last_activity field to users table");
            }
        }
        
        // Перевіряємо, чи існує поле is_active
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        $isActiveExists = $stmt->fetch() !== false;
        
        if (!$isActiveExists) {
            // Додаємо поле is_active (за замовчуванням 1 - активний)
            $db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT 'Статус активності користувача (1 - активний, 0 - неактивний)' AFTER last_activity");
            
            // Додаємо індекс для швидкого пошуку
            $db->exec("ALTER TABLE users ADD INDEX idx_is_active (is_active)");
            
            // Встановлюємо всіх існуючих користувачів як активних
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

