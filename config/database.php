<?php
/**
 * Конфигурация базы данных
 * Использует улучшенный класс Database из engine/classes/Database.php
 * 
 * @package Config
 * @version 2.0.0
 */

declare(strict_types=1);

// Настройки подключения к базе данных
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.1.26');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'db_flowaxy');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Подключаем улучшенный класс Database
// Пробуем загрузить из новой структуры
$databaseFile = dirname(__DIR__) . '/engine/classes/data/Database.php';
if (file_exists($databaseFile)) {
    require_once $databaseFile;
} else {
    // Обратная совместимость - старая структура
    require_once dirname(__DIR__) . '/engine/classes/Database.php';
}

/**
 * Глобальная функция для получения подключения к БД
 * Показывает красивую страницу ошибки, если подключение не удалось
 * 
 * @param bool $showError Показывать страницу ошибки (по умолчанию true)
 * @return PDO|null
 */
function getDB(bool $showError = true): ?PDO {
    try {
        return Database::getInstance()->getConnection();
    } catch (Exception $e) {
        error_log("getDB error: " . $e->getMessage());
        
        if ($showError && php_sapi_name() !== 'cli') {
            // Показываем страницу ошибки подключения
            $errorDetails = [
                'host' => DB_HOST,
                'database' => DB_NAME,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
            
            showDatabaseError($errorDetails);
            exit;
        }
        
        return null;
    }
}

/**
 * Отображение страницы ошибки подключения к БД
 * 
 * @param array $errorDetails Детали ошибки
 * @return void
 */
function showDatabaseError(array $errorDetails = []): void {
    // Устанавливаем HTTP статус 503 (Service Unavailable)
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    // Добавляем порт в детали ошибки, если не указан
    if (isset($errorDetails['host']) && !isset($errorDetails['port'])) {
        // Разбираем хост:port если указано
        $host = $errorDetails['host'];
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
            $errorDetails['host'] = $host;
            $errorDetails['port'] = (int)$port;
        } else {
            $errorDetails['port'] = 3306; // Порт MySQL по умолчанию
        }
    }
    
    // Пытаемся загрузить шаблон ошибки
    $errorTemplate = dirname(__DIR__) . '/engine/templates/database-error.php';
    if (file_exists($errorTemplate) && is_readable($errorTemplate)) {
        include $errorTemplate;
    } else {
        // Fallback - простая HTML страница
        ?>
        <!DOCTYPE html>
        <html lang="uk">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Помилка підключення до бази даних</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 50px auto;
                    padding: 20px;
                    background: #f5f5f5;
                }
                .error-box {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                h1 { color: #e74c3c; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>⚠ Помилка підключення до бази даних</h1>
                <p>Не вдалося підключитися до бази даних. Будь ласка, перевірте налаштування підключення.</p>
                <?php if (!empty($errorDetails) && defined('DEBUG_MODE') && DEBUG_MODE): ?>
                    <p><strong>Помилка:</strong> <?= htmlspecialchars($errorDetails['error'] ?? 'Unknown error') ?></p>
                <?php endif; ?>
                <p><a href="javascript:location.reload()">Оновити сторінку</a></p>
            </div>
        </body>
        </html>
        <?php
    }
}

/**
 * Проверка доступности БД
 * 
 * @param bool $showError Показывать страницу ошибки при недоступности
 * @return bool
 */
function isDatabaseAvailable(bool $showError = false): bool {
    try {
        $isAvailable = Database::getInstance()->isAvailable();
        
        if (!$isAvailable && $showError && php_sapi_name() !== 'cli') {
            showDatabaseError([
                'host' => DB_HOST,
                'database' => DB_NAME,
                'error' => 'База даних недоступна'
            ]);
            exit;
        }
        
        return $isAvailable;
    } catch (Exception $e) {
        error_log("isDatabaseAvailable error: " . $e->getMessage());
        
        if ($showError && php_sapi_name() !== 'cli') {
            showDatabaseError([
                'host' => DB_HOST,
                'database' => DB_NAME,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            exit;
        }
        
        return false;
    }
}
