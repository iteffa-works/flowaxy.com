<?php
/**
 * Основной конфигурационный файл CMS
 * Требует PHP 8.3+
 */

declare(strict_types=1);

// Проверка версии PHP
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    if (php_sapi_name() !== 'cli') {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка версии PHP</title></head><body><h1>Требуется PHP 8.3+</h1><p>Текущая версия: ' . htmlspecialchars(PHP_VERSION) . '</p></body></html>');
    }
    die('Эта CMS требует PHP 8.3 или выше. Текущая версия: ' . PHP_VERSION . PHP_EOL);
}


// Безопасная функция для htmlspecialchars
if (!function_exists('safe_html')) {
    /**
     * Безопасный вывод HTML
     * 
     * @param mixed $value Значение для вывода
     * @param string $default Значение по умолчанию
     * @return string
     */
    function safe_html($value, string $default = ''): string {
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars((string)($value ?: $default), ENT_QUOTES, 'UTF-8');
    }
}

// Включаем буферизацию вывода для предотвращения проблем с headers
if (!ob_get_level()) {
    ob_start();
}

// Запуск сессии (только если заголовки еще не отправлены)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Настройки безопасности сессии
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Основные настройки
// Определяем протокол автоматически
$protocol = 'http://';
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)) {
    $protocol = 'https://';
}

$host = $_SERVER['HTTP_HOST'] ?? 'spokinoki.local';
define('SITE_URL', $protocol . $host);
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('UPLOADS_URL', SITE_URL . '/uploads/');
define('CACHE_DIR', __DIR__ . '/../cache/');

// Функция для получения протокол-относительного URL (для избежания Mixed Content)
function getProtocolRelativeUrl($path = '') {
    $host = $_SERVER['HTTP_HOST'] ?? 'spokinoki.local';
    return '//' . $host . ($path ? '/' . ltrim($path, '/') : '');
}

// Функция для получения URL загрузок с правильным протоколом
function getUploadsUrl($filePath = '') {
    return getProtocolRelativeUrl('uploads' . ($filePath ? '/' . ltrim($filePath, '/') : ''));
}

// Функция для конвертации абсолютного URL в протокол-относительный
function toProtocolRelativeUrl($url) {
    if (empty($url)) {
        return $url;
    }
    
    // Если URL уже протокол-относительный, возвращаем как есть
    if (strpos($url, '//') === 0) {
        return $url;
    }
    
    // Если URL относительный, возвращаем как есть
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        return $url;
    }
    
    // Конвертируем абсолютный URL в протокол-относительный
    $parsed = parse_url($url);
    if ($parsed && isset($parsed['host'])) {
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        return '//' . $parsed['host'] . $path . $query . $fragment;
    }
    
    return $url;
}

// Настройки безопасности
define('ADMIN_SESSION_NAME', 'cms_admin_logged_in');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Автозагрузка классов (современный подход с проверками)
spl_autoload_register(function (string $className): void {
    // Проверяем только классы из нашего пространства имен
    if (strpos($className, '\\') === false) {
        $classFile = __DIR__ . '/../engine/classes/' . $className . '.php';
        if (file_exists($classFile) && is_readable($classFile)) {
            require_once $classFile;
        }
    }
});

// Подключение к базе данных
require_once __DIR__ . '/database.php';

// Подключение ядра системы
require_once __DIR__ . '/../engine/init.php';

// Инициализация менеджеров (ленивая загрузка)
// Менеджеры будут инициализированы при первом обращении через глобальные функции

// Современные функции безопасности с типизацией
/**
 * Генерация CSRF токена
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
 * Проверка CSRF токена
 * 
 * @param string|null $token Токен для проверки
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
            // Если headers уже отправлены, используем JavaScript редирект
            echo '<script>window.location.href = "' . ADMIN_URL . '/login";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . ADMIN_URL . '/login"></noscript>';
            exit;
        }
    }
}

/**
 * Санитизация входных данных
 * 
 * @param mixed $input Входные данные
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
        // Если headers уже отправлены, используем JavaScript редирект
        echo '<script>window.location.href = "' . htmlspecialchars($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
        exit;
    }
}

// Оптимизированная функция для получения настроек сайта с кешированием
/**
 * Получение всех настроек сайта
 * 
 * @return array
 */
function getSiteSettings(): array {
    if (!function_exists('cache_remember')) {
        return [];
    }
    
    return cache_remember('site_settings', function(): array {
        $db = getDB();
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
    }, 3600); // Кешируем на 1 час
}

/**
 * Получение настройки сайта
 * 
 * @param string $key Ключ настройки
 * @param string $default Значение по умолчанию
 * @return string
 */
function getSetting(string $key, string $default = ''): string {
    $settings = getSiteSettings();
    return $settings[$key] ?? $default;
}

// Создание необходимых директорий если не существуют
$directories = [
    UPLOADS_DIR,
    CACHE_DIR
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    // Создаем .htaccess для защиты директорий
    $htaccessFile = rtrim($dir, '/') . '/.htaccess';
    if (!file_exists($htaccessFile) && strpos($dir, 'cache') !== false) {
        @file_put_contents($htaccessFile, "Deny from all\n");
    }
}

// Инициализация плагинов (вызов хуков init)
if (function_exists('pluginManager')) {
    try {
        pluginManager()->initializePlugins();
    } catch (Exception $e) {
        error_log("Plugin initialization error: " . $e->getMessage());
    }
}
?>

