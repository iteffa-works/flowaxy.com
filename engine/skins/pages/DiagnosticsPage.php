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
        
        $action = sanitizeInput($_POST['cache_action'] ?? '');
        
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
            'plugins' => $this->checkPlugins()
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
            $db = getDB(false);
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
            $requiredTables = ['users', 'plugins', 'themes', 'site_settings'];
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
        $baseDir = dirname(__DIR__, 2);
        
        $folders = [
            'uploads' => defined('UPLOADS_DIR') ? UPLOADS_DIR : $baseDir . '/uploads',
            'cache' => defined('CACHE_DIR') ? CACHE_DIR : $baseDir . '/storage/cache',
            'plugins' => $baseDir . '/plugins',
            'themes' => $baseDir . '/themes',
            'config' => $baseDir . '/engine/data'
        ];
        
        foreach ($folders as $name => $path) {
            $path = str_replace(['\\', '//'], ['/', '/'], $path);
            $path = rtrim($path, '/');
            
            $exists = file_exists($path) && is_dir($path);
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);
            
            $checks[$name] = [
                'name' => ucfirst($name),
                'value' => $exists ? $path : 'Не існує',
                'status' => ($exists && $readable && $writable) ? 'success' : ($exists ? 'warning' : 'error'),
                'message' => $exists ? 
                    ($readable && $writable ? 'Читається та записується' : 
                     ($readable ? 'Тільки читається' : 'Немає доступу')) : 
                    'Директорія не існує'
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

