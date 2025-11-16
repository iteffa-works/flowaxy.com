<?php
/**
 * Ініціалізація ядра системи
 * Підключення всіх основних класів та функцій
 * 
 * @package Engine
 * @version 2.0.0
 */

declare(strict_types=1);

// Перевірка версії PHP
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    if (php_sapi_name() !== 'cli') {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Помилка версії PHP</title></head><body><h1>Потрібно PHP 8.3+</h1><p>Поточна версія: ' . htmlspecialchars(PHP_VERSION) . '</p></body></html>');
    }
    die('Ця CMS потребує PHP 8.3 або вище. Поточна версія: ' . PHP_VERSION . PHP_EOL);
}

// Підключаємо глобальну конфігурацію
require_once __DIR__ . '/data/config.php';

// Підключаємо конфігурацію бази даних
require_once __DIR__ . '/data/database.php';

// Безпечна функція для htmlspecialchars
if (!function_exists('safe_html')) {
    /**
     * Безпечний вивід HTML
     * 
     * @param mixed $value Значення для виводу
     * @param string $default Значення за замовчуванням
     * @return string
     */
    function safe_html($value, string $default = ''): string {
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars((string)($value ?: $default), ENT_QUOTES, 'UTF-8');
    }
}

// Вмикаємо буферизацію виводу для запобігання проблем з headers
if (!ob_get_level()) {
    ob_start();
}

// Запуск сесії (тільки якщо заголовки ще не відправлені)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Налаштування безпеки сесії
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Автозавантаження класів (сучасний підхід з перевірками та підтримкою підкаталогів)
spl_autoload_register(function (string $className): void {
    // Перевіряємо тільки класи з нашого простору імен
    if (strpos($className, '\\') === false) {
        $classesDir = __DIR__ . '/classes/';
        
        // Спочатку перевіряємо модулі (Config тепер в modules)
        $modulesDir = __DIR__ . '/modules/';
        if ($className === 'Config' || in_array($className, ['Logger', 'Media', 'Menu', 'PluginManager'])) {
            $moduleFile = $modulesDir . $className . '.php';
            if (file_exists($moduleFile) && is_readable($moduleFile)) {
                require_once $moduleFile;
                return;
            }
        }
        
        // Визначаємо підкаталог на основі імені класу
        $subdirectories = [
            'BaseModule' => 'base',
            'BasePlugin' => 'base',
            'ThemePlugin' => 'base',
            'Ini' => 'files',
            'Json' => 'files',
            'Zip' => 'files',
            'File' => 'files',
            'Xml' => 'files',
            'Csv' => 'files',
            'Yaml' => 'files',
            'Image' => 'files',
            'Directory' => 'files',
            'Upload' => 'files',
            'MimeType' => 'files',
            'Cache' => 'data',
            'Database' => 'data',
            'MenuManager' => 'managers',
            'ThemeManager' => 'managers',
            'ScssCompiler' => 'compilers',
            'Validator' => 'validators',
            // Нові класи для CMS
            'Security' => 'security',
            'Hash' => 'security',
            'Encryption' => 'security',
            'Session' => 'security',
            'Cookie' => 'http',
            'Response' => 'http',
            'Request' => 'http',
            'View' => 'view',
            'Mail' => 'mail',
        ];
        
        $subdir = $subdirectories[$className] ?? '';
        
        // Пробуємо знайти файл в підкаталозі
        if ($subdir) {
            $classFile = $classesDir . $subdir . '/' . $className . '.php';
            if (file_exists($classFile) && is_readable($classFile)) {
                require_once $classFile;
                return;
            }
        }
        
        // Якщо не знайдено в підкаталозі, шукаємо в корені (для зворотної сумісності)
        $classFile = $classesDir . $className . '.php';
        if (file_exists($classFile) && is_readable($classFile)) {
            require_once $classFile;
            return;
        }
        
        // Також пробуємо знайти в будь-якому підкаталозі (для майбутніх класів)
        $subdirs = ['base', 'files', 'data', 'managers', 'compilers', 'validators', 'security', 'http', 'view', 'mail'];
        foreach ($subdirs as $dir) {
            $classFile = $classesDir . $dir . '/' . $className . '.php';
            if (file_exists($classFile) && is_readable($classFile)) {
                require_once $classFile;
                return;
            }
        }
        
        // Також пробуємо знайти в modules
        $moduleFile = $modulesDir . $className . '.php';
        if (file_exists($moduleFile) && is_readable($moduleFile)) {
            require_once $moduleFile;
            return;
        }
    }
});

// Функції для роботи з БД
/**
 * Глобальна функція для отримання підключення до БД
 * Показує красиву сторінку помилки, якщо підключення не вдалося
 * 
 * @param bool $showError Показувати сторінку помилки (за замовчуванням true)
 * @return PDO|null
 */
function getDB(bool $showError = true): ?PDO {
    try {
        return Database::getInstance()->getConnection();
    } catch (Exception $e) {
        error_log("getDB error: " . $e->getMessage());
        
        if ($showError && php_sapi_name() !== 'cli') {
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
 * Перевірка доступності БД
 * 
 * @param bool $showError Показувати сторінку помилки при недоступності
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

/**
 * Відображення сторінки помилки підключення до БД
 * 
 * @param array $errorDetails Деталі помилки
 * @return void
 */
function showDatabaseError(array $errorDetails = []): void {
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    if (isset($errorDetails['host']) && !isset($errorDetails['port'])) {
        $host = $errorDetails['host'];
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
            $errorDetails['host'] = $host;
            $errorDetails['port'] = (int)$port;
        } else {
            $errorDetails['port'] = 3306;
        }
    }
    
    $errorTemplate = __DIR__ . '/templates/database-error.php';
    if (file_exists($errorTemplate) && is_readable($errorTemplate)) {
        include $errorTemplate;
    } else {
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

// Підключення ядра системи
require_once __DIR__ . '/modules/loader.php';
ModuleLoader::init();

// Функції безпеки з типізацією
/**
 * Генерація CSRF токена
 * 
 * @return string
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Перевірка CSRF токена
 * 
 * @param string|null $token Токен для перевірки
 * @return bool
 */
function verifyCSRFToken(?string $token): bool {
    if ($token === null || !isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION[ADMIN_SESSION_NAME]) && $_SESSION[ADMIN_SESSION_NAME] === true;
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        if (!headers_sent()) {
            header('Location: ' . ADMIN_URL . '/login');
            exit;
        } else {
            // Якщо headers вже відправлені, використовуємо JavaScript редірект
            echo '<script>window.location.href = "' . ADMIN_URL . '/login";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . ADMIN_URL . '/login"></noscript>';
            exit;
        }
    }
}

/**
 * Санітизація вхідних даних
 * 
 * @param mixed $input Вхідні дані
 * @return string
 */
function sanitizeInput($input): string {
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    if (is_numeric($input)) {
        return (string)$input;
    }
    
    if (is_array($input)) {
        try {
            return json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            error_log("JSON encoding error: " . $e->getMessage());
            return '';
        }
    }
    
    if (is_bool($input)) {
        return $input ? '1' : '0';
    }
    
    return '';
}

function redirectTo(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        // Якщо headers вже відправлені, використовуємо JavaScript редірект
        echo '<script>window.location.href = "' . htmlspecialchars($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
        exit;
    }
}

/**
 * Форматування розміру в байтах
 * 
 * @param int $bytes Розмір в байтах
 * @param int $precision Кількість знаків після коми
 * @return string
 */
if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes, int $precision = 2): string {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Функції для роботи з URL
/**
 * Отримання протокол-відносного URL (для уникнення Mixed Content)
 * 
 * @param string $path Шлях
 * @return string
 */
function getProtocolRelativeUrl(string $path = ''): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return '//' . $host . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Отримання URL завантажень з правильним протоколом
 * 
 * @param string $filePath Шлях до файлу
 * @return string
 */
function getUploadsUrl(string $filePath = ''): string {
    return getProtocolRelativeUrl('uploads' . ($filePath ? '/' . ltrim($filePath, '/') : ''));
}

/**
 * Конвертація абсолютного URL в протокол-відносний
 * 
 * @param string $url URL
 * @return string
 */
function toProtocolRelativeUrl(string $url): string {
    if (empty($url)) {
        return $url;
    }
    
    // Якщо URL вже протокол-відносний, повертаємо як є
    if (strpos($url, '//') === 0) {
        return $url;
    }
    
    // Якщо URL відносний, повертаємо як є
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        return $url;
    }
    
    // Конвертуємо абсолютний URL в протокол-відносний
    $parsed = parse_url($url);
    if ($parsed && isset($parsed['host'])) {
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        return '//' . $parsed['host'] . $path . $query . $fragment;
    }
    
    return $url;
}

// Оптимізована функція для отримання налаштувань сайту з кешуванням
/**
 * Отримання всіх налаштувань сайту
 * 
 * @return array
 */
function getSiteSettings(): array {
    if (!function_exists('cache_remember')) {
        return [];
    }
    
    return cache_remember('site_settings', function(): array {
        $db = getDB(false); // Не показуємо помилку, якщо БД недоступна
        if ($db === null) {
            return [];
        }
        
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            if ($stmt === false) {
                return [];
            }
            
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (PDOException $e) {
            error_log("Error getting site settings: " . $e->getMessage());
            return [];
        }
    }, 3600); // Кешуємо на 1 годину
}

/**
 * Отримання налаштування сайту
 * 
 * @param string $key Ключ налаштування
 * @param string $default Значення за замовчуванням
 * @return string
 */
function getSetting(string $key, string $default = ''): string {
    $settings = getSiteSettings();
    return $settings[$key] ?? $default;
}

// Створення необхідних директорій якщо не існують
$directories = [
    UPLOADS_DIR,
    CACHE_DIR,
    LOGS_DIR
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    // Створюємо .htaccess для захисту директорій
    $htaccessFile = rtrim($dir, '/') . '/.htaccess';
    if (!file_exists($htaccessFile) && (strpos($dir, 'cache') !== false || strpos($dir, 'logs') !== false)) {
        @file_put_contents($htaccessFile, "Deny from all\n");
    }
}
