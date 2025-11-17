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
        $phpVersion = htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Помилка версії PHP</title></head><body><h1>Потрібно PHP 8.3+</h1><p>Поточна версія: ' . $phpVersion . '</p></body></html>');
    }
    die('Ця CMS потребує PHP 8.3 або вище. Поточна версія: ' . PHP_VERSION . PHP_EOL);
}

// Підключаємо глобальну конфігурацію
require_once __DIR__ . '/data/config.php';

// Підключаємо конфігурацію бази даних
require_once __DIR__ . '/data/database.php';

// Вмикаємо буферизацію виводу для запобігання проблем з headers
if (!ob_get_level()) {
    ob_start();
}

// Автозавантаження класів (сучасний підхід з перевірками та підтримкою підкаталогів)
// Класи завантажуються автоматично перед використанням
spl_autoload_register(function (string $className): void {
    // Перевіряємо тільки класи з нашого простору імен
    if (strpos($className, '\\') === false) {
        $classesDir = __DIR__ . '/classes/';
        
        // Спочатку перевіряємо модулі (Config тепер в modules)
        $modulesDir = __DIR__ . '/modules/';
        // MailModule завантажується тільки через ModuleLoader (lazy loading)
        if ($className === 'Config' || in_array($className, ['Menu', 'PluginManager'])) {
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
            'Logger' => 'data',
            'MenuManager' => 'managers',
            'ThemeManager' => 'managers',
            'SettingsManager' => 'managers',
            'UrlHelper' => 'helpers',
            'DatabaseHelper' => 'helpers',
            'SecurityHelper' => 'helpers',
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
            'Router' => 'http',
            'AjaxHandler' => 'http',
            'View' => 'view',
            'Mail' => 'mail',
            // Сторінки адмінки (для автозавантаження)
            'LoginPage' => 'skins/pages',
            'LogoutPage' => 'skins/pages',
            'DashboardPage' => 'skins/pages',
            'SettingsPage' => 'skins/pages',
            'ProfilePage' => 'skins/pages',
            'PluginsPage' => 'skins/pages',
            'ThemesPage' => 'skins/pages',
            'CustomizerPage' => 'skins/pages',
            'MenusPage' => 'skins/pages',
            'DiagnosticsPage' => 'skins/pages',
            'DocumentationPage' => 'skins/pages',
            'ThemeEditorPage' => 'skins/pages',
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
        $subdirs = ['base', 'files', 'data', 'managers', 'compilers', 'validators', 'security', 'http', 'view', 'mail', 'helpers'];
        foreach ($subdirs as $dir) {
            $classFile = $classesDir . $dir . '/' . $className . '.php';
            if (file_exists($classFile) && is_readable($classFile)) {
                require_once $classFile;
                return;
            }
        }
        
        // Перевіряємо сторінки адмінки
        if (strpos($className, 'Page') !== false && strpos($className, 'AdminPage') === false) {
            $pagesDir = __DIR__ . '/skins/pages/';
            $pageFile = $pagesDir . $className . '.php';
            if (file_exists($pageFile) && is_readable($pageFile)) {
                require_once $pageFile;
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

// Функції для роботи з БД видалені - використовуйте DatabaseHelper::getConnection() або Database::getInstance()->getConnection()

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
                [$host, $port] = explode(':', $host, 2);
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
                    <p><strong>Помилка:</strong> <?= Security::clean($errorDetails['error'] ?? 'Unknown error') ?></p>
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

// Ініціалізація системи логування та обробки помилок
if (class_exists('Logger')) {
    // Встановлюємо обробник помилок
    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
        $logger = Logger::getInstance();
        $context = [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ];
        
        // Визначаємо рівень помилки
        $level = match($errno) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR => Logger::LEVEL_ERROR,
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => Logger::LEVEL_WARNING,
            E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED => Logger::LEVEL_INFO,
            default => Logger::LEVEL_WARNING
        };
        
        $logger->log($level, $errstr, $context);
        
        // Продовжуємо стандартну обробку помилок
        return false;
    });
    
    // Встановлюємо обробник винятків
    set_exception_handler(function(\Throwable $exception): void {
        $logger = Logger::getInstance();
        $logger->logException($exception);
        
        // Показуємо помилку тільки в режимі розробки
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo '<pre>' . htmlspecialchars($exception->getMessage()) . "\n" . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        } else {
            http_response_code(500);
            echo '<h1>Внутрішня помилка сервера</h1>';
        }
    });
    
    // Встановлюємо обробник завершення скрипта
    register_shutdown_function(function(): void {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $logger = Logger::getInstance();
            $logger->logCritical('Fatal error: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    });
}

// Ініціалізація сесії через клас Session
Session::start([
    'name' => 'PHPSESSID',
    'lifetime' => 7200,
    'domain' => '',
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Функції безпеки видалені - використовуйте SecurityHelper клас

/**
 * Редірект на URL (використовує Response клас)
 * 
 * @param string $url URL
 * @return void
 */
function redirectTo(string $url): void {
    Response::redirectStatic($url);
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

// Функції для роботи з URL видалені - використовуйте UrlHelper клас

// Функції для роботи з налаштуваннями видалені - використовуйте SettingsManager клас

// Створення необхідних директорій якщо не існують (використовує Directory клас)
// Явно завантажуємо класи Directory та File перед використанням
if (!class_exists('Directory')) {
    $directoryClassFile = __DIR__ . '/classes/files/Directory.php';
    if (file_exists($directoryClassFile)) {
        require_once $directoryClassFile;
    }
}
if (!class_exists('File')) {
    $fileClassFile = __DIR__ . '/classes/files/File.php';
    if (file_exists($fileClassFile)) {
        require_once $fileClassFile;
    }
}

$directories = [
    UPLOADS_DIR,
    CACHE_DIR,
    LOGS_DIR
];

foreach ($directories as $dir) {
    // Використовуємо клас Directory якщо доступний, інакше стандартні PHP функції
    if (class_exists('Directory')) {
        try {
            $directory = new Directory($dir);
            if (method_exists($directory, 'exists') && !$directory->exists()) {
                if (method_exists($directory, 'create')) {
                    $directory->create(0755, true);
                } else {
                    // Fallback на стандартні PHP функції
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to create directory {$dir} using Directory class: " . $e->getMessage());
            // Fallback на стандартні PHP функції
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    } else {
        // Fallback: використовуємо стандартні PHP функції
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    
    // Створюємо .htaccess для захисту директорій
    if (strpos($dir, 'cache') !== false || strpos($dir, 'logs') !== false) {
        $htaccessFile = rtrim($dir, '/') . '/.htaccess';
        if (class_exists('File')) {
            try {
                $file = new File($htaccessFile);
                if (method_exists($file, 'exists') && !$file->exists()) {
                    if (method_exists($file, 'write')) {
                        $file->write("Deny from all\n");
                    } else {
                        // Fallback на стандартні PHP функції
                        if (!file_exists($htaccessFile)) {
                            @file_put_contents($htaccessFile, "Deny from all\n");
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Failed to write .htaccess to {$htaccessFile} using File class: " . $e->getMessage());
                // Fallback на стандартні PHP функції
                if (!file_exists($htaccessFile)) {
                    @file_put_contents($htaccessFile, "Deny from all\n");
                }
            }
        } else {
            // Fallback: використовуємо стандартні PHP функції
            if (!file_exists($htaccessFile)) {
                @file_put_contents($htaccessFile, "Deny from all\n");
            }
        }
    }
}
