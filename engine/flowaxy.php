<?php
/**
 * Flowaxy CMS - Core Engine
 * 
 * @package Engine
 * @version 7.0.0
 */

declare(strict_types=1);

if (!function_exists('detectProtocol')) {
    /**
     * Определение протокола (HTTP/HTTPS)
     * Проверяет настройку из базы данных, если доступна, иначе определяет автоматически
     * 
     * @return string Протокол (http:// или https://)
     */
    function detectProtocol(): string {
        // Сначала проверяем глобальную переменную (установленную в init.php)
        if (isset($GLOBALS['_SITE_PROTOCOL']) && !empty($GLOBALS['_SITE_PROTOCOL'])) {
            return $GLOBALS['_SITE_PROTOCOL'];
        }
        
        // Затем проверяем настройку из базы данных (если доступна)
        if (class_exists('SettingsManager') && file_exists(__DIR__ . '/data/database.ini')) {
            try {
                $settingsManager = settingsManager();
                $protocolSetting = $settingsManager->get('site_protocol', 'auto');
                
                // Если настройка установлена явно, используем её
                if ($protocolSetting === 'https') {
                    $GLOBALS['_SITE_PROTOCOL'] = 'https://';
                    return 'https://';
                } elseif ($protocolSetting === 'http') {
                    $GLOBALS['_SITE_PROTOCOL'] = 'http://';
                    return 'http://';
                }
                // Если 'auto', продолжаем автоматическое определение
            } catch (Exception $e) {
                // Если не удалось загрузить настройки, продолжаем автоматическое определение
                error_log('detectProtocol: Could not load settings: ' . $e->getMessage());
            }
        }
        
        // Автоматическое определение протокола
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

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    if (php_sapi_name() !== 'cli') {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Помилка версії PHP</title></head><body><h1>Потрібно PHP 8.3+</h1><p>Поточна версія: ' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . '</p></body></html>');
    }
    die('Ця CMS потребує PHP 8.3 або вище. Поточна версія: ' . PHP_VERSION . PHP_EOL);
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstaller = strpos($requestUri, '/install') === 0;
$databaseIniFile = __DIR__ . '/data/database.ini';
$rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);

if (!$isInstaller && !file_exists($databaseIniFile) && php_sapi_name() !== 'cli') {
    header('Location: /install');
    exit;
}

$protocol = detectProtocol();
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Сохраняем протокол в глобальной переменной для возможного обновления после загрузки настроек
$GLOBALS['_SITE_PROTOCOL'] = $protocol;

if (!defined('SITE_URL')) define('SITE_URL', $protocol . $host);
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', $rootDir . '/uploads/');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', SITE_URL . '/uploads/');
if (!defined('CACHE_DIR')) define('CACHE_DIR', $rootDir . '/storage/cache/');
if (!defined('LOGS_DIR')) define('LOGS_DIR', $rootDir . '/storage/logs/');
if (!defined('ADMIN_SESSION_NAME')) define('ADMIN_SESSION_NAME', 'cms_admin_logged_in');
if (!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', 'csrf_token');
// PASSWORD_MIN_LENGTH теперь загружается из настроек через SystemConfig
if (!defined('PASSWORD_MIN_LENGTH')) {
    if (class_exists('SystemConfig')) {
        define('PASSWORD_MIN_LENGTH', SystemConfig::getInstance()->getPasswordMinLength());
    } else {
        define('PASSWORD_MIN_LENGTH', 8);
    }
}

if (!ob_get_level()) ob_start();

spl_autoload_register(function (string $className): void {
    if (strpos($className, '\\') !== false) return;
    
    $classesDir = __DIR__ . '/classes/';
    $managersDir = __DIR__ . '/classes/managers/';
    
    $map = [
        'BaseModule' => 'base', 'BasePlugin' => 'base',
        'Ini' => 'files', 'Json' => 'files', 'Zip' => 'files', 'File' => 'files',
        'Xml' => 'files', 'Csv' => 'files', 'Yaml' => 'files', 'Image' => 'files',
        'Directory' => 'files', 'Upload' => 'files', 'MimeType' => 'files',
        'Cache' => 'data', 'Database' => 'data', 'Logger' => 'data', 'Config' => 'data', 'SystemConfig' => 'data',
        'UrlHelper' => 'helpers', 'DatabaseHelper' => 'helpers', 'SecurityHelper' => 'helpers',
        'ScssCompiler' => 'compilers', 'Validator' => 'validators',
        'Security' => 'security', 'Hash' => 'security', 'Encryption' => 'security', 'Session' => 'security',
        'Cookie' => 'http', 'Response' => 'http', 'Request' => 'http', 'Router' => 'http', 'AjaxHandler' => 'http',
        'StorageInterface' => 'storage',
        'RouterManager' => 'managers', 'CookieManager' => 'managers', 'SessionManager' => 'managers', 
        'StorageManager' => 'managers', 'StorageFactory' => 'managers',
        'View' => 'view', 'Mail' => 'mail', 'ModalHandler' => 'ui',
        'ModuleLoader' => 'system', 'HookManager' => 'system',
        'RoleManager' => 'managers',
        'LoginPage' => 'skins/pages', 'LogoutPage' => 'skins/pages', 'DashboardPage' => 'skins/pages',
        'SettingsPage' => 'skins/pages', 'SiteSettingsPage' => 'skins/pages', 'ProfilePage' => 'skins/pages', 'PluginsPage' => 'skins/pages',
        'ThemesPage' => 'skins/pages', 'CustomizerPage' => 'skins/pages',
        'ThemeEditorPage' => 'skins/pages', 'CacheViewPage' => 'skins/pages', 'LogsViewPage' => 'skins/pages',
    ];
    
    if (isset($map[$className])) {
        $file = $classesDir . $map[$className] . '/' . $className . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
    
    $isManager = substr($className, -7) === 'Manager' || $className === 'ThemeCustomizer';
    
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
    
    foreach (['base', 'files', 'data', 'managers', 'compilers', 'validators', 'security', 'http', 'view', 'mail', 'helpers', 'ui', 'system', 'storage'] as $dir) {
        $file = $classesDir . $dir . '/' . $className . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
    
    if (strpos($className, 'Page') !== false && strpos($className, 'AdminPage') === false) {
        $file = __DIR__ . '/skins/pages/' . $className . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/role-functions.php';
loadDatabaseConfig();

if (!class_exists('Cache')) {
    require_once __DIR__ . '/classes/data/Cache.php';
}
