<?php
/**
 * Инициализация системы ролей
 * Проверяет наличие таблиц и создает их при необходимости
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

if (!function_exists('initializeRolesSystem')) {
    function initializeRolesSystem(): void {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                return;
            }
            
            // Проверяем наличие таблицы roles
            $stmt = $db->query("SHOW TABLES LIKE 'roles'");
            $rolesTableExists = $stmt->rowCount() > 0;
            
            // Если таблицы нет, создаем систему ролей
            if (!$rolesTableExists) {
                $sqlFile = __DIR__ . '/../db/roles_permissions.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    if (!empty($sql)) {
                        // Выполняем SQL по частям
                        $statements = array_filter(
                            array_map('trim', explode(';', $sql)),
                            fn($stmt) => !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^\/\*/', $stmt)
                        );
                        
                        foreach ($statements as $statement) {
                            // Пропускаем комментарии и пустые строки
                            $statement = preg_replace('/--.*$/m', '', $statement);
                            $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
                            $statement = trim($statement);
                            
                            if (!empty($statement)) {
                                try {
                                    $db->exec($statement);
                                } catch (Exception $e) {
                                    // Игнорируем ошибки типа "таблица уже существует"
                                    if (strpos($e->getMessage(), 'already exists') === false && 
                                        strpos($e->getMessage(), 'Duplicate') === false) {
                                        error_log("Roles system init SQL error: " . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Roles system initialization error: " . $e->getMessage());
        }
    }
}

