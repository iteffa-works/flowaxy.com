<?php
/**
 * Сторінка діагностики системи
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class DiagnosticsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Діагностика системи - Landing CMS';
        $this->templateName = 'diagnostics';
        
        $this->setPageHeader(
            'Діагностика системи',
            'Перевірка конфігурації та можливостей системи',
            'fas fa-stethoscope'
        );
    }
    
    public function handle() {
        // Отримуємо результати діагностики
        $diagnostics = $this->runDiagnostics();
        
        // Рендеримо сторінку
        $this->render([
            'diagnostics' => $diagnostics
        ]);
    }
    
    /**
     * Запуск діагностики системи
     */
    private function runDiagnostics() {
        $diagnostics = [
            'php' => $this->checkPhp(),
            'database' => $this->checkDatabase(),
            'permissions' => $this->checkPermissions(),
            'extensions' => $this->checkExtensions(),
            'configuration' => $this->checkConfiguration(),
            'modules' => $this->checkModules(),
            'plugins' => $this->checkPlugins()
        ];
        
        return $diagnostics;
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
            'status' => version_compare($phpVersion, '7.4.0', '>=') ? 'success' : 'error',
            'message' => version_compare($phpVersion, '7.4.0', '>=') ? 
                'Версія PHP достатня' : 
                'Рекомендується PHP 7.4 або вище'
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

