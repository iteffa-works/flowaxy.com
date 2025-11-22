<?php
/**
 * Flowaxy CMS - Основний рушій системи
 * 
 * @package Engine
 * @version 7.0.0
 */

declare(strict_types=1);

if (!function_exists('detectProtocol')) {
    /**
     * Визначення протоколу (HTTP/HTTPS)
     * Перевіряє налаштування з бази даних, якщо доступна, інакше визначає автоматично
     * 
     * @return string Протокол (http:// або https://)
     */
    function detectProtocol(): string {
        // Спочатку перевіряємо глобальну змінну (встановлену в init.php)
        if (isset($GLOBALS['_SITE_PROTOCOL']) && !empty($GLOBALS['_SITE_PROTOCOL'])) {
            return $GLOBALS['_SITE_PROTOCOL'];
        }
        
        // Потім перевіряємо налаштування з бази даних (якщо доступна)
        if (class_exists('SettingsManager') && file_exists(__DIR__ . '/data/database.ini')) {
            try {
                $settingsManager = settingsManager();
                $protocolSetting = $settingsManager->get('site_protocol', 'auto');
                
                // Якщо налаштування встановлено явно, використовуємо його
                if ($protocolSetting === 'https') {
                    $GLOBALS['_SITE_PROTOCOL'] = 'https://';
                    return 'https://';
                } elseif ($protocolSetting === 'http') {
                    $GLOBALS['_SITE_PROTOCOL'] = 'http://';
                    return 'http://';
                }
                // Якщо 'auto', продовжуємо автоматичне визначення
            } catch (Exception $e) {
                // Якщо не вдалося завантажити налаштування, продовжуємо автоматичне визначення
                error_log('detectProtocol: Не вдалося завантажити налаштування: ' . $e->getMessage());
            }
        }
        
        // Автоматичне визначення протоколу
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https://';
            $GLOBALS['_SITE_PROTOCOL'] = $protocol;
            return $protocol;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            $protocol = 'https://';
            $GLOBALS['_SITE_PROTOCOL'] = $protocol;
            return $protocol;
        }
        
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
        
        $protocol = $isHttps ? 'https://' : 'http://';
        $GLOBALS['_SITE_PROTOCOL'] = $protocol;
        return $protocol;
    }
}

if (version_compare(PHP_VERSION, '8.4.0', '<')) {
    if (php_sapi_name() !== 'cli') {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Помилка версії PHP</title></head><body><h1>Потрібно PHP 8.4+</h1><p>Поточна версія: ' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . '</p></body></html>');
    }
    die('Ця CMS потребує PHP 8.4 або вище. Поточна версія: ' . PHP_VERSION . PHP_EOL);
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstaller = str_starts_with($requestUri, '/install');
$databaseIniFile = __DIR__ . '/data/database.ini';
$rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);

if (!$isInstaller && !file_exists($databaseIniFile) && php_sapi_name() !== 'cli') {
    header('Location: /install');
    exit;
}

$protocol = detectProtocol();
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Зберігаємо протокол у глобальній змінній для можливого оновлення після завантаження налаштувань
$GLOBALS['_SITE_PROTOCOL'] = $protocol;

if (!defined('SITE_URL')) define('SITE_URL', $protocol . $host);
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', $rootDir . '/uploads/');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', SITE_URL . '/uploads/');
if (!defined('CACHE_DIR')) define('CACHE_DIR', $rootDir . '/storage/cache/');
if (!defined('LOGS_DIR')) define('LOGS_DIR', $rootDir . '/storage/logs/');
if (!defined('ADMIN_SESSION_NAME')) define('ADMIN_SESSION_NAME', 'cms_admin_logged_in');
if (!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', 'csrf_token');
// PASSWORD_MIN_LENGTH тепер завантажується з налаштувань через SystemConfig
if (!defined('PASSWORD_MIN_LENGTH')) {
    if (class_exists('SystemConfig')) {
        define('PASSWORD_MIN_LENGTH', SystemConfig::getInstance()->getPasswordMinLength());
    } else {
        define('PASSWORD_MIN_LENGTH', 8);
    }
}

if (!ob_get_level()) ob_start();

// Оптимізований автозавантажувач класів з кешуванням мапи
spl_autoload_register(function (string $className): void {
    // Пропускаємо класи з простором імен
    if (str_contains($className, '\\')) return;
    
    // Статична мапа для швидкого доступу (кешується між викликами)
    static $classMap = null;
    static $classesDir = null;
    static $managersDir = null;
    
    if ($classMap === null) {
        $classesDir = __DIR__ . '/classes/';
        $managersDir = $classesDir . 'managers/';
        
        // Оптимізована мапа класів для швидкого пошуку
        $classMap = [
            'BaseModule' => 'base', 'BasePlugin' => 'base',
            'Ini' => 'files', 'Json' => 'files', 'Zip' => 'files', 'File' => 'files',
            'Xml' => 'files', 'Csv' => 'files', 'Yaml' => 'files', 'Image' => 'files',
            'Upload' => 'files', 'MimeType' => 'files',
            'Cache' => 'data', 'Database' => 'data', 'Logger' => 'data', 'Config' => 'data', 'SystemConfig' => 'data',
            'UrlHelper' => 'helpers', 'DatabaseHelper' => 'helpers', 'SecurityHelper' => 'helpers',
            'ScssCompiler' => 'compilers', 'Validator' => 'validators',
            'Security' => 'security', 'Hash' => 'security', 'Encryption' => 'security', 'Session' => 'security',
            'Cookie' => 'http', 'Response' => 'http', 'Request' => 'http', 'Router' => 'http', 'AjaxHandler' => 'http',
            'RouterManager' => 'managers', 'CookieManager' => 'managers', 'SessionManager' => 'managers', 
            'StorageManager' => 'managers', 'StorageFactory' => 'managers', 'ThemeEditorManager' => 'managers',
            'View' => 'view', 'Mail' => 'mail', 'ModalHandler' => 'ui',
            'ModuleLoader' => 'system', 'HookManager' => 'system',
            'RoleManager' => 'managers',
            'LoginPage' => 'skins/pages', 'LogoutPage' => 'skins/pages', 'DashboardPage' => 'skins/pages',
            'SettingsPage' => 'skins/pages', 'SiteSettingsPage' => 'skins/pages', 'ProfilePage' => 'skins/pages', 'PluginsPage' => 'skins/pages',
            'ThemesPage' => 'skins/pages', 'CustomizerPage' => 'skins/pages',
            'ThemeEditorPage' => 'skins/pages', 'CacheViewPage' => 'skins/pages', 'LogsViewPage' => 'skins/pages',
        ];
    }
    
    // Швидкий пошук у мапі
    if (isset($classMap[$className])) {
        $file = $classesDir . $classMap[$className] . '/' . $className . '.php';
        if (file_exists($file)) { 
            require_once $file; 
            return; 
        }
    }
    
    // Перевірка менеджерів (оптимізовано)
    $isManager = str_ends_with($className, 'Manager') || $className === 'ThemeCustomizer';
    
    if ($isManager) {
        if (!class_exists('BaseModule')) {
            $baseModuleFile = $classesDir . 'base/BaseModule.php';
            if (file_exists($baseModuleFile)) {
                require_once $baseModuleFile;
            }
        }
        
        $file = $managersDir . $className . '.php';
        if (file_exists($file)) { 
            require_once $file; 
            return; 
        }
    }
    
    // Пошук у стандартних директоріях (тільки якщо не знайдено в мапі)
    static $dirs = ['base', 'files', 'data', 'managers', 'compilers', 'validators', 'security', 'http', 'view', 'mail', 'helpers', 'ui', 'system', 'storage'];
    foreach ($dirs as $dir) {
        $file = $classesDir . $dir . '/' . $className . '.php';
        if (file_exists($file)) { 
            require_once $file; 
            return; 
        }
    }
    
    // Перевірка сторінок адмінки
    if (str_contains($className, 'Page') && !str_contains($className, 'AdminPage')) {
        $file = __DIR__ . '/skins/pages/' . $className . '.php';
        if (file_exists($file)) { 
            require_once $file; 
            return; 
        }
    }
});

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/role-functions.php';
loadDatabaseConfig();

if (!class_exists('Cache')) {
    require_once __DIR__ . '/classes/data/Cache.php';
}
