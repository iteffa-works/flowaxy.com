<?php
/**
 * Допоміжні функції системи
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

// Підключаємо функції для роботи з ролями
if (file_exists(__DIR__ . '/role-functions.php')) {
    require_once __DIR__ . '/role-functions.php';
}

function loadDatabaseConfig(bool $reload = false): void {
    $databaseIniFile = __DIR__ . '/../data/database.ini';
    
    if (!file_exists($databaseIniFile) || !is_readable($databaseIniFile)) {
        if (!defined('DB_HOST')) define('DB_HOST', '');
        if (!defined('DB_NAME')) define('DB_NAME', '');
        if (!defined('DB_USER')) define('DB_USER', '');
        if (!defined('DB_PASS')) define('DB_PASS', '');
        if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
        return;
    }
    
    try {
        $dbConfig = null;
        if (class_exists('Ini')) {
            $ini = new Ini($databaseIniFile);
            $dbConfig = $ini->getSection('database', []);
        }
        if (empty($dbConfig)) {
            $parsed = @parse_ini_file($databaseIniFile, true);
            $dbConfig = $parsed['database'] ?? [];
        }
        
        if (!empty($dbConfig)) {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = (int)($dbConfig['port'] ?? 3306);
            
            if ($reload || !defined('DB_HOST') || DB_HOST === '') {
                if (!defined('DB_HOST')) define('DB_HOST', $host . ':' . $port);
                if (!defined('DB_NAME')) define('DB_NAME', $dbConfig['name'] ?? '');
                if (!defined('DB_USER')) define('DB_USER', $dbConfig['user'] ?? 'root');
                if (!defined('DB_PASS')) define('DB_PASS', $dbConfig['pass'] ?? '');
                if (!defined('DB_CHARSET')) define('DB_CHARSET', $dbConfig['charset'] ?? 'utf8mb4');
            }
        } else {
            if (!defined('DB_HOST')) define('DB_HOST', '');
            if (!defined('DB_NAME')) define('DB_NAME', '');
            if (!defined('DB_USER')) define('DB_USER', '');
            if (!defined('DB_PASS')) define('DB_PASS', '');
            if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
        }
    } catch (Exception $e) {
        error_log("Error loading database.ini: " . $e->getMessage());
        if (!defined('DB_HOST')) define('DB_HOST', '');
        if (!defined('DB_NAME')) define('DB_NAME', '');
        if (!defined('DB_USER')) define('DB_USER', '');
        if (!defined('DB_PASS')) define('DB_PASS', '');
        if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
    }
}

function showDatabaseError(array $errorDetails = []): void {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $databaseIniFile = __DIR__ . '/../data/database.ini';
    
    if (strpos($requestUri, '/install') === 0) return;
    if (!file_exists($databaseIniFile) && php_sapi_name() !== 'cli') {
        header('Location: /install');
        exit;
    }
    
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    if (isset($errorDetails['host']) && !isset($errorDetails['port'])) {
        $host = $errorDetails['host'];
        if (strpos($host, ':') !== false) {
            [$host, $port] = explode(':', $host, 2);
            $errorDetails['host'] = $host;
            $errorDetails['port'] = (int)$port;
        } else {
            $errorDetails['port'] = 3306;
        }
    }
    
    $template = __DIR__ . '/../templates/database-error.php';
    if (file_exists($template)) {
        include $template;
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Помилка БД</title></head><body><h1>Помилка підключення до бази даних</h1></body></html>';
    }
}

function initializeSystem(): void {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    if (strpos($requestUri, '/install') === 0) return;
    
    $databaseIniFile = __DIR__ . '/../data/database.ini';
    if (!file_exists($databaseIniFile)) {
        if (php_sapi_name() !== 'cli') {
            header('Location: /install');
            exit;
        }
        return;
    }
    
    if (!DatabaseHelper::isAvailable(false)) {
        showDatabaseError([
            'host' => DB_HOST,
            'database' => DB_NAME,
            'error' => 'Не вдалося підключитися до бази даних. Перевірте налаштування підключення.'
        ]);
        exit;
    }
}

function renderThemeFallback(): bool {
    http_response_code(200);
    $template = __DIR__ . '/../templates/theme-not-installed.php';
    if (file_exists($template)) {
        include $template;
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Встановіть тему</title></head><body><h1>Встановіть тему</h1><p><a href="/admin/themes">Перейти до тем</a></p></body></html>';
    }
    return true;
}

if (!function_exists('redirectTo')) {
    function redirectTo(string $url): void {
        Response::redirectStatic($url);
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes, int $precision = 2): string {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = min(floor(($bytes ? log($bytes) : 0) / log(1024)), count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('installer')) {
    function installer(): ?InstallerManager {
        return class_exists('InstallerManager') ? InstallerManager::getInstance() : null;
    }
}

if (!function_exists('pluginManager')) {
    function pluginManager(): ?PluginManager {
        return class_exists('PluginManager') ? PluginManager::getInstance() : null;
    }
}

// ============================================================================
// Хелпер-функції для роботи з сховищами
// ============================================================================

if (!function_exists('cookieManager')) {
    /**
     * Отримання менеджера cookies
     * 
     * @return CookieManager
     */
    function cookieManager(): CookieManager {
        return CookieManager::getInstance();
    }
}

if (!function_exists('sessionManager')) {
    /**
     * Отримання менеджера сесій
     * 
     * @param string $prefix Префікс для ключів (опціонально)
     * @return SessionManager
     */
    function sessionManager(string $prefix = ''): SessionManager {
        $manager = SessionManager::getInstance();
        if ($prefix) {
            $manager->setPrefix($prefix);
        }
        return $manager;
    }
}

if (!function_exists('storageManager')) {
    /**
     * Отримання менеджера клієнтського сховища
     * 
     * @param string $type Тип сховища (localStorage або sessionStorage)
     * @param string $prefix Префікс для ключів (опціонально)
     * @return StorageManager
     */
    function storageManager(string $type = 'localStorage', string $prefix = ''): StorageManager {
        $manager = StorageManager::getInstance();
        $manager->setType($type);
        if ($prefix) {
            $manager->setPrefix($prefix);
        }
        return $manager;
    }
}

if (!function_exists('storageFactory')) {
    /**
     * Отримання менеджера сховища через фабрику
     * 
     * @param string $type Тип сховища (cookie, session, localStorage, sessionStorage)
     * @param string $prefix Префікс для ключів (опціонально)
     * @return StorageInterface|null
     */
    function storageFactory(string $type = 'session', string $prefix = ''): ?StorageInterface {
        return StorageFactory::get($type, $prefix);
    }
}

