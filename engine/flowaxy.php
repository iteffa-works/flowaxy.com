<?php
/**
 * Flowaxy CMS - Core Engine
 * Запуск ядра: определение констант и важной информации
 * 
 * @package Engine
 * @version 7.0.0
 */

declare(strict_types=1);

// Проверка версии PHP
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

// Определяем базовые переменные
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstaller = strpos($requestUri, '/install') === 0;
$databaseIniFile = __DIR__ . '/data/database.ini';
$rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);

// Первая проверка: если database.ini нет и это не установщик - перенаправляем
if (!$isInstaller && !file_exists($databaseIniFile) && php_sapi_name() !== 'cli') {
    header('Location: /install');
    exit;
}

// Определяем протокол и хост
// Проверяем протокол с учетом прокси-серверов и балансировщиков нагрузки
$isHttps = false;

// Проверка через заголовки прокси (для Load Balancer, CloudFlare, Nginx и т.д.)
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHttps = true;
}

// Проверка через заголовок X-Forwarded-Ssl
if (!$isHttps && isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
    $isHttps = true;
}

// Проверка через стандартные переменные сервера
if (!$isHttps) {
    $isHttps = (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') ||
        (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
}

$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Определяем константы
if (!defined('SITE_URL')) define('SITE_URL', $protocol . $host);
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', $rootDir . '/uploads/');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', SITE_URL . '/uploads/');
if (!defined('CACHE_DIR')) define('CACHE_DIR', $rootDir . '/storage/cache/');
if (!defined('LOGS_DIR')) define('LOGS_DIR', $rootDir . '/storage/logs/');
if (!defined('ADMIN_SESSION_NAME')) define('ADMIN_SESSION_NAME', 'cms_admin_logged_in');
if (!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', 'csrf_token');
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);

// Буферизация вывода
if (!ob_get_level()) ob_start();

/**
 * Автозагрузчик классов
 */
spl_autoload_register(function (string $className): void {
    if (strpos($className, '\\') !== false) return;
    
    $classesDir = __DIR__ . '/classes/';
    $managersDir = __DIR__ . '/classes/managers/';
    
    // Автоматическая загрузка менеджеров из директории managers/
    // Проверяем, является ли класс менеджером (имеет суффикс Manager, Customizer или Installer)
    $isManager = (
        substr($className, -7) === 'Manager' || 
        $className === 'ThemeCustomizer' || 
        $className === 'Installer'
    );
    
    if ($isManager) {
        $file = $managersDir . $className . '.php';
        if (file_exists($file)) { 
            require_once $file; 
            return; 
        }
    }
    
    // Маппинг классов
    $map = [
        'BaseModule' => 'base', 'BasePlugin' => 'base',
        'Ini' => 'files', 'Json' => 'files', 'Zip' => 'files', 'File' => 'files',
        'Xml' => 'files', 'Csv' => 'files', 'Yaml' => 'files', 'Image' => 'files',
        'Directory' => 'files', 'Upload' => 'files', 'MimeType' => 'files',
        'Cache' => 'data', 'Database' => 'data', 'Logger' => 'data', 'Config' => 'data',
        'UrlHelper' => 'helpers', 'DatabaseHelper' => 'helpers', 'SecurityHelper' => 'helpers',
        'ScssCompiler' => 'compilers', 'Validator' => 'validators',
        'Security' => 'security', 'Hash' => 'security', 'Encryption' => 'security', 'Session' => 'security',
        'Cookie' => 'http', 'Response' => 'http', 'Request' => 'http', 'Router' => 'http', 'AjaxHandler' => 'http',
        'View' => 'view', 'Mail' => 'mail', 'ModalHandler' => 'ui',
        'ModuleLoader' => 'system', 'HookManager' => 'system',
        'LoginPage' => 'skins/pages', 'LogoutPage' => 'skins/pages', 'DashboardPage' => 'skins/pages',
        'SettingsPage' => 'skins/pages', 'ProfilePage' => 'skins/pages', 'PluginsPage' => 'skins/pages',
        'ThemesPage' => 'skins/pages', 'CustomizerPage' => 'skins/pages',
        'ThemeEditorPage' => 'skins/pages', 'CacheViewPage' => 'skins/pages', 'LogsViewPage' => 'skins/pages',
    ];
    
    if (isset($map[$className])) {
        $file = $classesDir . $map[$className] . '/' . $className . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
    
    // Поиск в подкаталогах
    foreach (['base', 'files', 'data', 'managers', 'compilers', 'validators', 'security', 'http', 'view', 'mail', 'helpers', 'ui', 'system'] as $dir) {
        $file = $classesDir . $dir . '/' . $className . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
    
    // Страницы админки
    if (strpos($className, 'Page') !== false && strpos($className, 'AdminPage') === false) {
        $file = __DIR__ . '/skins/pages/' . $className . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
    
    // Менеджеры уже загружены выше, дополнительная проверка не нужна
});

// Подключаем функции
require_once __DIR__ . '/includes/functions.php';

// Загружаем конфигурацию БД
loadDatabaseConfig();

// Загрузка Cache
if (!class_exists('Cache')) {
    require_once __DIR__ . '/classes/data/Cache.php';
}

// Создание системных директорий
$dirs = [UPLOADS_DIR, CACHE_DIR, LOGS_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if ((strpos($dir, 'cache') !== false || strpos($dir, 'logs') !== false) && !file_exists($dir . '.htaccess')) {
        @file_put_contents($dir . '.htaccess', "Deny from all\n");
    }
}
