<?php
/**
 * Сторінка діагностики системи
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class DiagnosticsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Діагностика системи - Flowaxy CMS';
        $this->templateName = 'diagnostics';
        
        $this->setPageHeader(
            'Діагностика системи',
            'Перевірка конфігурації та можливостей системи',
            'fas fa-stethoscope'
        );
    }
    
    public function handle() {
        // Обробка дій з кешем (якщо є POST запит)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cache_action'])) {
            $this->handleCacheAction();
        }
        
        // Отримуємо результати діагностики
        $diagnostics = $this->runDiagnostics();
        
        // Отримуємо системну інформацію
        $systemInfo = $this->getSystemInfo();
        
        // Отримуємо інформацію про кеш
        $cacheInfo = $this->getCacheInfo();
        
        // Рендеримо сторінку
        $this->render([
            'diagnostics' => $diagnostics,
            'systemInfo' => $systemInfo,
            'cacheInfo' => $cacheInfo
        ]);
    }
    
    /**
     * Обробка дій з кешем
     */
    private function handleCacheAction(): void {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Помилка безпеки', 'danger');
            return;
        }
        
        $action = SecurityHelper::sanitizeInput($_POST['cache_action'] ?? '');
        
        try {
            $cache = cache();
            
            switch ($action) {
                case 'clear_all':
                    if ($cache->clear()) {
                        $this->setMessage('Весь кеш успішно очищено', 'success');
                    } else {
                        $this->setMessage('Помилка при очищенні кешу', 'danger');
                    }
                    break;
                    
                case 'clear_expired':
                    $cleared = $cache->cleanup();
                    $this->setMessage("Прострочений кеш успішно очищено ({$cleared} файлів)", 'success');
                    break;
                    
                default:
                    $this->setMessage('Невідома дія', 'danger');
            }
        } catch (Exception $e) {
            $this->setMessage('Помилка при обробці кешу: ' . $e->getMessage(), 'danger');
            error_log("Cache action error: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання інформації про кеш
     */
    private function getCacheInfo(): array {
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        
        $info = [
            'enabled' => true,
            'directory' => $cacheDir,
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0,
            'expired_size' => 0,
            'writable' => is_writable($cacheDir)
        ];
        
        if (!is_dir($cacheDir)) {
            return $info;
        }
        
        $files = glob($cacheDir . '*.cache');
        if ($files === false) {
            return $info;
        }
        
        $now = time();
        $info['total_files'] = count($files);
        
        foreach ($files as $file) {
            $size = @filesize($file);
            if ($size !== false) {
                $info['total_size'] += $size;
            }
            
            $data = @file_get_contents($file);
            if ($data !== false) {
                try {
                    $cached = @unserialize($data, ['allowed_classes' => false]);
                    if (is_array($cached) && isset($cached['expires'])) {
                        if ($cached['expires'] < $now) {
                            $info['expired_files']++;
                            if ($size !== false) {
                                $info['expired_size'] += $size;
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ігноруємо помилки
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Отримання системної інформації
     */
    private function getSystemInfo() {
        $info = [];
        
        // Версія CMS
        $info['cms_version'] = '1.0.0';
        
        // Сервер
        $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info['server_name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
        
        // Час сервера
        $info['server_time'] = date('d.m.Y H:i:s');
        $info['timezone'] = date_default_timezone_get();
        
        // PHP версія
        $info['php_version'] = PHP_VERSION;
        $info['php_sapi'] = php_sapi_name();
        
        // Інформація про логування
        if (class_exists('Logger')) {
            $logger = Logger::getInstance();
            $logStats = $logger->getStats();
            $info['logger'] = [
                'enabled' => true,
                'total_files' => $logStats['total_files'] ?? 0,
                'total_size' => $logStats['total_size'] ?? 0,
                'latest_file' => $logStats['latest_file'] ?? null
            ];
        } else {
            $info['logger'] = ['enabled' => false];
        }
        
        // Статистика БД
        if (class_exists('Database')) {
            try {
                $db = Database::getInstance();
                $dbStats = $db->getStats();
                $info['database'] = [
                    'connected' => $dbStats['connected'] ?? false,
                    'query_count' => $dbStats['query_count'] ?? 0,
                    'total_query_time' => $dbStats['total_query_time'] ?? 0,
                    'average_query_time' => $dbStats['average_query_time'] ?? 0,
                    'slow_queries' => $dbStats['slow_queries'] ?? 0,
                    'error_count' => $dbStats['error_count'] ?? 0
                ];
            } catch (Exception $e) {
                $info['database'] = ['error' => $e->getMessage()];
            }
        }
        
        // Статистика кешу
        if (function_exists('cache')) {
            try {
                $cache = cache();
                $cacheStats = $cache->getStats();
                $info['cache'] = $cacheStats;
            } catch (Exception $e) {
                $info['cache'] = ['error' => $e->getMessage()];
            }
        }
        
        return $info;
    }
    
    /**
     * Запуск діагностики системи
     */
    private function runDiagnostics() {
        $cacheInfo = $this->getCacheInfo();
        
        $diagnostics = [
            'system' => $this->formatSystemInfo(),
            'php' => $this->checkPhp(),
            'database' => $this->checkDatabase(),
            'cache' => $this->formatCacheInfo($cacheInfo),
            'permissions' => $this->checkPermissions(),
            'extensions' => $this->checkExtensions(),
            'configuration' => $this->checkConfiguration(),
            'modules' => $this->checkModules(),
            'plugins' => $this->checkPlugins(),
            'logging' => $this->checkLogging(),
            'security' => $this->checkSecurity()
        ];
        
        return $diagnostics;
    }
    
    /**
     * Форматування системної інформації для таблиці
     */
    private function formatSystemInfo() {
        $info = $this->getSystemInfo();
        $checks = [];
        
        $checks['cms_version'] = [
            'name' => 'Версія CMS',
            'value' => $info['cms_version'] ?? 'Unknown',
            'status' => 'info',
            'message' => 'Версія системи управління контентом'
        ];
        
        $checks['server_software'] = [
            'name' => 'Сервер',
            'value' => $info['server_software'] ?? 'Unknown',
            'status' => 'info',
            'message' => 'Програмне забезпечення сервера'
        ];
        
        $checks['server_name'] = [
            'name' => 'Ім\'я сервера',
            'value' => $info['server_name'] ?? 'Unknown',
            'status' => 'info',
            'message' => 'Домен сервера'
        ];
        
        $checks['timezone'] = [
            'name' => 'Часова зона',
            'value' => $info['timezone'] ?? 'Unknown',
            'status' => 'info',
            'message' => 'Налаштування часової зони'
        ];
        
        $checks['server_time'] = [
            'name' => 'Час сервера',
            'value' => $info['server_time'] ?? 'Unknown',
            'status' => 'info',
            'message' => 'Поточний час на сервері'
        ];
        
        return $checks;
    }
    
    /**
     * Форматування інформації про кеш для таблиці
     */
    private function formatCacheInfo($cacheInfo) {
        $checks = [];
        
        $checks['enabled'] = [
            'name' => 'Статус кешу',
            'value' => $cacheInfo['enabled'] ? 'Увімкнено' : 'Вимкнено',
            'status' => $cacheInfo['enabled'] ? 'success' : 'error',
            'message' => $cacheInfo['enabled'] ? 'Кеш активний' : 'Кеш неактивний'
        ];
        
        $checks['total_files'] = [
            'name' => 'Всього файлів',
            'value' => $cacheInfo['total_files'] . ' файлів',
            'status' => 'info',
            'message' => 'Кількість файлів кешу'
        ];
        
        $checks['total_size'] = [
            'name' => 'Загальний розмір',
            'value' => round($cacheInfo['total_size'] / 1024 / 1024, 2) . ' MB',
            'status' => 'info',
            'message' => 'Загальний розмір файлів кешу'
        ];
        
        $checks['expired_files'] = [
            'name' => 'Прострочених файлів',
            'value' => $cacheInfo['expired_files'] . ' (' . round($cacheInfo['expired_size'] / 1024 / 1024, 2) . ' MB)',
            'status' => $cacheInfo['expired_files'] > 0 ? 'warning' : 'success',
            'message' => 'Кількість прострочених файлів кешу'
        ];
        
        $checks['writable'] = [
            'name' => 'Доступ до запису',
            'value' => $cacheInfo['writable'] ? 'Доступно' : 'Недоступно',
            'status' => $cacheInfo['writable'] ? 'success' : 'error',
            'message' => $cacheInfo['writable'] ? 'Можна записувати в директорію кешу' : 'Немає доступу до запису'
        ];
        
        return $checks;
    }
    
    /**
     * Перевірка PHP
     */
    private function checkPhp() {
        $checks = [];
        
        // Версія PHP
        $phpVersion = PHP_VERSION;
        $checks['version'] = [
            'name' => 'Версія PHP',
            'value' => $phpVersion,
            'status' => version_compare($phpVersion, '8.3.0', '>=') ? 'success' : 'error',
            'message' => version_compare($phpVersion, '8.3.0', '>=') ? 
                'Версія PHP достатня' : 
                'Потрібно PHP 8.3 або вище'
        ];
        
        // Display errors
        $displayErrors = ini_get('display_errors');
        $checks['display_errors'] = [
            'name' => 'Display Errors',
            'value' => $displayErrors ? 'Увімкнено' : 'Вимкнено',
            'status' => !$displayErrors ? 'success' : 'warning',
            'message' => $displayErrors ? 
                'Рекомендується вимкнути в продакшн' : 
                'Помилки приховані (рекомендовано)'
        ];
        
        // Error reporting
        $errorReporting = ini_get('error_reporting');
        $checks['error_reporting'] = [
            'name' => 'Error Reporting',
            'value' => '0x' . dechex((int)$errorReporting),
            'status' => 'info',
            'message' => 'Рівень звітування про помилки'
        ];
        
        // Max execution time
        $maxExecTime = ini_get('max_execution_time');
        $checks['max_execution_time'] = [
            'name' => 'Max Execution Time',
            'value' => $maxExecTime . ' сек',
            'status' => (int)$maxExecTime >= 30 ? 'success' : 'info',
            'message' => 'Максимальний час виконання скрипта'
        ];
        
        // Post max size vs upload max
        $postMax = ini_get('post_max_size');
        $uploadMax = ini_get('upload_max_filesize');
        $checks['post_vs_upload'] = [
            'name' => 'POST vs Upload',
            'value' => "POST: {$postMax}, Upload: {$uploadMax}",
            'status' => $this->convertToBytes($postMax) >= $this->convertToBytes($uploadMax) ? 'success' : 'warning',
            'message' => $this->convertToBytes($postMax) >= $this->convertToBytes($uploadMax) ? 
                'POST достатньо великий для upload' : 
                'POST має бути >= Upload max'
        ];
        
        // Memory limit
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->convertToBytes($memoryLimit);
        $checks['memory_limit'] = [
            'name' => 'Ліміт пам\'яті',
            'value' => $memoryLimit,
            'status' => $memoryBytes >= 128 * 1024 * 1024 ? 'success' : 'warning',
            'message' => $memoryBytes >= 128 * 1024 * 1024 ? 
                'Ліміт пам\'яті достатній' : 
                'Рекомендується щонайменше 128M'
        ];
        
        // Max upload size
        $uploadMax = ini_get('upload_max_filesize');
        $checks['upload_max'] = [
            'name' => 'Максимальний розмір завантаження',
            'value' => $uploadMax,
            'status' => 'info',
            'message' => 'Поточний ліміт завантаження файлів'
        ];
        
        // Max post size
        $postMax = ini_get('post_max_size');
        $checks['post_max'] = [
            'name' => 'Максимальний розмір POST',
            'value' => $postMax,
            'status' => 'info',
            'message' => 'Поточний ліміт розміру POST запитів'
        ];
        
        return $checks;
    }
    
    /**
     * Перевірка бази даних
     */
    private function checkDatabase() {
        $checks = [];
        
        try {
            $db = DatabaseHelper::getConnection(false);
            if (!$db) {
                $checks['connection'] = [
                    'name' => 'Підключення до БД',
                    'value' => 'Неможливо підключитися',
                    'status' => 'error',
                    'message' => 'Перевірте налаштування в engine/data/database.php'
                ];
                return $checks;
            }
            
            $checks['connection'] = [
                'name' => 'Підключення до БД',
                'value' => 'Успішно',
                'status' => 'success',
                'message' => 'Підключення до бази даних встановлено'
            ];
            
            // Версія MySQL
            $stmt = $db->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $mysqlVersion = $result['version'] ?? 'Unknown';
            $checks['version'] = [
                'name' => 'Версія MySQL',
                'value' => $mysqlVersion,
                'status' => 'info',
                'message' => 'Версія сервера бази даних'
            ];
            
            // Перевірка таблиць
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            // Обов'язкові таблиці системи (themes замінено на theme_settings)
            $requiredTables = ['users', 'plugins', 'plugin_settings', 'site_settings', 'theme_settings', 'menus', 'menu_items', 'media_files'];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $tables)) {
                    $missingTables[] = $table;
                }
            }
            
            $checks['tables'] = [
                'name' => 'Системні таблиці',
                'value' => count($tables) . ' таблиць',
                'status' => empty($missingTables) ? 'success' : 'warning',
                'message' => empty($missingTables) ? 
                    'Всі необхідні таблиці присутні' : 
                    'Відсутні таблиці: ' . implode(', ', $missingTables)
            ];
            
        } catch (Exception $e) {
            $checks['error'] = [
                'name' => 'Помилка БД',
                'value' => $e->getMessage(),
                'status' => 'error',
                'message' => 'Помилка при перевірці бази даних'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка прав доступу
     */
    private function checkPermissions() {
        $checks = [];
        // __DIR__ = engine/skins/pages, dirname(__DIR__, 2) = engine
        $engineDir = dirname(__DIR__, 2);
        // Базовий каталог проекту (на рівень вище від engine)
        $baseDir = dirname($engineDir);
        
        // Визначаємо шлях до engine/data (директорія конфігурації)
        // engine/data відносно engine директорії
        $configDir = $engineDir . DIRECTORY_SEPARATOR . 'data';
        $configDir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $configDir);
        
        $folders = [
            'uploads' => defined('UPLOADS_DIR') ? rtrim(UPLOADS_DIR, '/\\') : $baseDir . DIRECTORY_SEPARATOR . 'uploads',
            'cache' => defined('CACHE_DIR') ? rtrim(CACHE_DIR, '/\\') : $baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
            'plugins' => $baseDir . DIRECTORY_SEPARATOR . 'plugins',
            'themes' => $baseDir . DIRECTORY_SEPARATOR . 'themes',
            'config' => $configDir
        ];
        
        foreach ($folders as $name => $path) {
            // Нормалізуємо шлях для поточної ОС
            $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
            $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
            
            // Перевіряємо існування та права
            $exists = file_exists($path) && is_dir($path);
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);
            
            // Для config директорії права на запис не обов'язкові (читання достатньо)
            $requiredWritable = ($name !== 'config') ? true : false;
            
            $status = 'success';
            if (!$exists) {
                $status = 'error';
            } elseif (!$readable) {
                $status = 'error';
            } elseif ($requiredWritable && !$writable) {
                $status = 'warning';
            } elseif (!$requiredWritable && !$writable) {
                $status = 'info';
            }
            
            $message = '';
            if (!$exists) {
                $message = 'Директорія не існує';
            } elseif (!$readable) {
                $message = 'Немає доступу на читання';
            } elseif ($requiredWritable && !$writable) {
                $message = 'Тільки читається (потрібен запис)';
            } elseif (!$requiredWritable && !$writable) {
                $message = 'Тільки читається (нормально для config)';
            } else {
                $message = 'Читається та записується';
            }
            
            $displayPath = rtrim($path, '/\\');
            
            $checks[$name] = [
                'name' => ucfirst($name),
                'value' => $exists ? $displayPath : 'Не існує',
                'status' => $status,
                'message' => $message
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка розширень PHP
     */
    private function checkExtensions() {
        $checks = [];
        
        $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'gd', 'zip', 'curl', 'openssl', 'fileinfo'];
        $recommended = ['imagick', 'redis', 'memcached'];
        
        foreach ($required as $ext) {
            $loaded = extension_loaded($ext);
            $checks[$ext] = [
                'name' => $ext,
                'value' => $loaded ? 'Встановлено' : 'Відсутнє',
                'status' => $loaded ? 'success' : 'error',
                'message' => $loaded ? 'Розширення активно' : 'Обов\'язкове розширення відсутнє'
            ];
        }
        
        foreach ($recommended as $ext) {
            $loaded = extension_loaded($ext);
            $checks[$ext] = [
                'name' => $ext,
                'value' => $loaded ? 'Встановлено' : 'Відсутнє',
                'status' => $loaded ? 'success' : 'info',
                'message' => $loaded ? 'Розширення активно' : 'Рекомендоване розширення (необов\'язкове)'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка конфігурації
     */
    private function checkConfiguration() {
        $checks = [];
        
        // Константи
        $constants = ['SITE_URL', 'ADMIN_URL', 'UPLOADS_DIR', 'CACHE_DIR', 'DB_HOST', 'DB_NAME'];
        foreach ($constants as $const) {
            $checks[$const] = [
                'name' => $const,
                'value' => defined($const) ? constant($const) : 'Не визначено',
                'status' => defined($const) ? 'success' : 'error',
                'message' => defined($const) ? 'Константа визначена' : 'Константа не визначена'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка модулів
     */
    private function checkModules() {
        $checks = [];
        
        if (class_exists('ModuleLoader')) {
            $loadedModules = ModuleLoader::getLoadedModules();
            $moduleNames = array_keys($loadedModules);
            
            $checks['loaded'] = [
                'name' => 'Завантажені модулі',
                'value' => count($moduleNames) . ' модулів',
                'status' => !empty($moduleNames) ? 'success' : 'warning',
                'message' => !empty($moduleNames) ? 
                    'Модулі: ' . implode(', ', $moduleNames) : 
                    'Модулі не завантажені'
            ];
            
            // Перевірка кожного модуля
            foreach ($loadedModules as $moduleName => $module) {
                if (is_object($module)) {
                    $info = [];
                    if (method_exists($module, 'getInfo')) {
                        $info = $module->getInfo();
                    }
                    
                    $checks['module_' . $moduleName] = [
                        'name' => $moduleName,
                        'value' => $info['version'] ?? 'Unknown',
                        'status' => 'success',
                        'message' => $info['description'] ?? 'Модуль завантажено'
                    ];
                }
            }
        } else {
            $checks['error'] = [
                'name' => 'ModuleLoader',
                'value' => 'Не знайдено',
                'status' => 'error',
                'message' => 'Клас ModuleLoader не доступний'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка плагінів
     */
    private function checkPlugins() {
        $checks = [];
        
        if (function_exists('pluginManager')) {
            $pm = pluginManager();
            $activePlugins = $pm->getActivePlugins();
            $allPlugins = $pm->getAllPlugins();
            
            $checks['active'] = [
                'name' => 'Активні плагіни',
                'value' => count($activePlugins) . ' з ' . count($allPlugins),
                'status' => 'info',
                'message' => 'Кількість активних плагінів'
            ];
            
            foreach ($activePlugins as $slug => $plugin) {
                $info = [];
                if (is_object($plugin) && method_exists($plugin, 'getInfo')) {
                    $info = $plugin->getInfo();
                }
                
                $checks['plugin_' . $slug] = [
                    'name' => $slug,
                    'value' => $info['version'] ?? 'Unknown',
                    'status' => 'success',
                    'message' => $info['title'] ?? 'Плагін активний'
                ];
            }
        } else {
            $checks['error'] = [
                'name' => 'PluginManager',
                'value' => 'Не доступний',
                'status' => 'error',
                'message' => 'PluginManager не доступний'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка логування
     */
    private function checkLogging() {
        $checks = [];
        
        if (class_exists('Logger')) {
            $logger = Logger::getInstance();
            $stats = $logger->getStats();
            
            $checks['enabled'] = [
                'name' => 'Система логування',
                'value' => 'Активна',
                'status' => 'success',
                'message' => 'Logger клас доступний та працює'
            ];
            
            $checks['total_files'] = [
                'name' => 'Файлів логів',
                'value' => $stats['total_files'] ?? 0,
                'status' => 'info',
                'message' => 'Кількість файлів логів'
            ];
            
            $checks['total_size'] = [
                'name' => 'Розмір логів',
                'value' => formatBytes($stats['total_size'] ?? 0),
                'status' => 'info',
                'message' => 'Загальний розмір файлів логів'
            ];
            
            $checks['latest_file'] = [
                'name' => 'Останній файл',
                'value' => $stats['latest_file'] ?? 'Немає',
                'status' => 'info',
                'message' => 'Останній створений файл логу'
            ];
            
            // Перевірка директорії логів
            $logDir = defined('LOGS_DIR') ? LOGS_DIR : dirname(__DIR__, 2) . '/storage/logs/';
            $checks['directory'] = [
                'name' => 'Директорія логів',
                'value' => is_writable($logDir) ? 'Доступна для запису' : 'Недоступна',
                'status' => is_writable($logDir) ? 'success' : 'error',
                'message' => is_writable($logDir) ? 'Можна записувати логи' : 'Потрібні права на запис'
            ];
        } else {
            $checks['error'] = [
                'name' => 'Система логування',
                'value' => 'Недоступна',
                'status' => 'error',
                'message' => 'Logger клас не знайдено'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Перевірка безпеки
     */
    private function checkSecurity() {
        $checks = [];
        
        // Перевірка display_errors
        $displayErrors = ini_get('display_errors');
        $checks['display_errors'] = [
            'name' => 'Display Errors',
            'value' => $displayErrors ? 'Увімкнено' : 'Вимкнено',
            'status' => !$displayErrors ? 'success' : 'warning',
            'message' => $displayErrors ? 
                'Рекомендується вимкнути в продакшн' : 
                'Помилки приховані (безпечно)'
        ];
        
        // Перевірка HTTPS
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                   (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https');
        $checks['https'] = [
            'name' => 'HTTPS',
            'value' => $isHttps ? 'Активний' : 'Неактивний',
            'status' => $isHttps ? 'success' : 'warning',
            'message' => $isHttps ? 
                'З\'єднання зашифровано' : 
                'Рекомендується використовувати HTTPS'
        ];
        
        // Перевірка сесії
        if (class_exists('Session')) {
            $sessionStarted = session_status() === PHP_SESSION_ACTIVE;
            $checks['session'] = [
                'name' => 'Сесія',
                'value' => $sessionStarted ? 'Активна' : 'Неактивна',
                'status' => $sessionStarted ? 'success' : 'warning',
                'message' => $sessionStarted ? 
                    'Сесія ініціалізована' : 
                    'Сесія не ініціалізована'
            ];
        }
        
        // Перевірка CSRF захисту
        if (class_exists('Security')) {
            $checks['csrf'] = [
                'name' => 'CSRF захист',
                'value' => 'Доступний',
                'status' => 'success',
                'message' => 'Клас Security доступний для CSRF захисту'
            ];
        }
        
        // Перевірка права на запис у важливі директорії
        $importantDirs = [
            'uploads' => defined('UPLOADS_DIR') ? UPLOADS_DIR : dirname(__DIR__, 2) . '/uploads/',
            'cache' => defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/',
            'logs' => defined('LOGS_DIR') ? LOGS_DIR : dirname(__DIR__, 2) . '/storage/logs/'
        ];
        
        foreach ($importantDirs as $name => $dir) {
            $writable = is_writable($dir);
            $checks['dir_' . $name] = [
                'name' => 'Права на ' . $name,
                'value' => $writable ? 'Доступні' : 'Обмежені',
                'status' => $writable ? 'success' : 'warning',
                'message' => $writable ? 
                    'Директорія доступна для запису' : 
                    'Можуть виникнути проблеми з записом'
            ];
        }
        
        return $checks;
    }
    
    /**
     * Конвертація розміру в байти
     */
    private function convertToBytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int)$value;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
}

