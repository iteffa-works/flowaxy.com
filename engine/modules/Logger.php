<?php
/**
 * Модуль логирования ошибок и событий системы
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/classes/BaseModule.php';

class Logger extends BaseModule {
    private $logsDir;
    private $maxLogFileSize = 10485760; // 10 MB
    private $logRetentionDays = 30; // Хранить логи 30 дней
    
    /**
     * Типы логов
     */
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_DEBUG = 'debug';
    
    /**
     * Инициализация модуля
     */
    protected function init(): void {
        // Определяем директорию для логов
        $this->logsDir = dirname(__DIR__, 2) . '/storage/logs/';
        
        // Получаем подключение к БД (если не установлено в BaseModule)
        if ($this->db === null) {
            $this->db = getDB();
        }
        
        // Загружаем настройки
        $this->loadSettings();
        
        // Создаем директорию, если её нет
        $this->ensureLogsDir();
        
        // Регистрируем обработчик ошибок PHP
        $this->registerErrorHandler();
        
        // Регистрируем автоматическое логирование
        $this->registerAutoLogging();
    }
    
    /**
     * Регистрация хуков модуля
     */
    public function registerHooks(): void {
        // Регистрация пункта меню в админке
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        
        // Регистрация страницы админки
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
        
        // Хуки для логирования различных событий
        addHook('system_error', [$this, 'logError']);
        addHook('system_warning', [$this, 'logWarning']);
        addHook('system_info', [$this, 'logInfo']);
        addHook('system_success', [$this, 'logSuccess']);
        addHook('system_debug', [$this, 'logDebug']);
    }
    
    /**
     * Добавление пункта меню в админку
     * 
     * @param array $menu Текущее меню
     * @return array Обновленное меню
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            'href' => adminUrl('logs'),
            'icon' => 'fas fa-file-alt',
            'text' => 'Логи системы',
            'page' => 'logs',
            'order' => 100,
            'submenu' => [
                [
                    'href' => adminUrl('logs'),
                    'text' => 'Просмотр логов',
                    'page' => 'logs',
                    'order' => 1
                ],
                [
                    'href' => adminUrl('logs-settings'),
                    'text' => 'Настройки',
                    'page' => 'logs-settings',
                    'order' => 2
                ]
            ]
        ];
        return $menu;
    }
    
    /**
     * Регистрация маршрута админки
     * 
     * @param Router|null $router Роутер админки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return; // Роутер еще не создан
        }
        
        require_once dirname(__DIR__) . '/skins/pages/LogsPage.php';
        require_once dirname(__DIR__) . '/skins/pages/LoggerSettingsPage.php';
        $router->add('logs', 'LogsPage');
        $router->add('logs-settings', 'LoggerSettingsPage');
    }
    
    /**
     * Получение информации о модуле
     */
    public function getInfo(): array {
        return [
            'name' => 'Logger',
            'title' => 'Система логирования',
            'description' => 'Логирование ошибок и событий системы для разработчиков',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Получение API методов модуля
     */
    public function getApiMethods(): array {
        return [
            'log' => 'Записать лог (type, message, context)',
            'logError' => 'Записать ошибку (message, context)',
            'logWarning' => 'Записать предупреждение (message, context)',
            'logInfo' => 'Записать информацию (message, context)',
            'logSuccess' => 'Записать успешное событие (message, context)',
            'logDebug' => 'Записать отладочную информацию (message, context)',
            'getLogs' => 'Получить логи (type, limit, offset)',
            'clearLogs' => 'Очистить логи (type, days)',
            'getLogStats' => 'Получить статистику логов'
        ];
    }
    
    /**
     * Загрузка настроек из БД
     */
    private function loadSettings(): void {
        if (!$this->db) {
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN (?, ?)");
            $stmt->execute(['logger_max_file_size', 'logger_retention_days']);
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            if (isset($settings['logger_max_file_size'])) {
                $this->maxLogFileSize = (int)$settings['logger_max_file_size'];
            }
            
            if (isset($settings['logger_retention_days'])) {
                $this->logRetentionDays = (int)$settings['logger_retention_days'];
            }
        } catch (Exception $e) {
            // Игнорируем ошибки при загрузке настроек
        }
    }
    
    /**
     * Получение настройки
     */
    public function getSetting(string $key, $default = null) {
        if (!$this->db) {
            return $default;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute(['logger_' . $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Сохранение настройки
     */
    public function setSetting(string $key, $value): bool {
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            $result = $stmt->execute(['logger_' . $key, $value]);
            
            // Обновляем локальные переменные
            if ($key === 'max_file_size') {
                $this->maxLogFileSize = (int)$value;
            } elseif ($key === 'retention_days') {
                $this->logRetentionDays = (int)$value;
            }
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Получение всех настроек
     */
    public function getSettings(): array {
        return [
            'max_file_size' => $this->maxLogFileSize,
            'retention_days' => $this->logRetentionDays,
            'log_errors' => $this->getSetting('log_errors', '1') === '1',
            'log_warnings' => $this->getSetting('log_warnings', '1') === '1',
            'log_info' => $this->getSetting('log_info', '1') === '1',
            'log_success' => $this->getSetting('log_success', '1') === '1',
            'log_debug' => $this->getSetting('log_debug', '0') === '1',
            'log_db_queries' => $this->getSetting('log_db_queries', '0') === '1',
            'log_file_operations' => $this->getSetting('log_file_operations', '0') === '1',
            'log_plugin_events' => $this->getSetting('log_plugin_events', '1') === '1',
            'log_module_events' => $this->getSetting('log_module_events', '1') === '1',
        ];
    }
    
    /**
     * Регистрация автоматического логирования системных событий
     */
    private function registerAutoLogging(): void {
        // Логирование ошибок БД
        if ($this->getSetting('log_db_queries', '0') === '1') {
            addHook('db_error', [$this, 'handleDbError']);
            addHook('db_query', [$this, 'handleDbQuery']);
        }
        
        // Логирование операций с файлами
        if ($this->getSetting('log_file_operations', '0') === '1') {
            addHook('file_operation', [$this, 'handleFileOperation']);
        }
        
        // Логирование событий плагинов
        if ($this->getSetting('log_plugin_events', '1') === '1') {
            addHook('plugin_activated', [$this, 'handlePluginActivated']);
            addHook('plugin_deactivated', [$this, 'handlePluginDeactivated']);
            addHook('plugin_installed', [$this, 'handlePluginInstalled']);
            addHook('plugin_uninstalled', [$this, 'handlePluginUninstalled']);
        }
        
        // Логирование событий модулей
        if ($this->getSetting('log_module_events', '1') === '1') {
            addHook('module_loaded', [$this, 'handleModuleLoaded']);
            addHook('module_error', [$this, 'handleModuleError']);
        }
    }
    
    /**
     * Обработчик ошибок БД
     */
    public function handleDbError($error): void {
        if ($this->getSetting('log_errors', '1') === '1') {
            $this->logError('Database Error: ' . (is_string($error) ? $error : json_encode($error)), [
                'type' => 'database',
                'error' => $error
            ]);
        }
    }
    
    /**
     * Обработчик запросов БД
     */
    public function handleDbQuery($query): void {
        if ($this->getSetting('log_debug', '0') === '1') {
            $this->logDebug('Database Query', [
                'type' => 'database',
                'query' => is_string($query) ? $query : json_encode($query)
            ]);
        }
    }
    
    /**
     * Обработчик операций с файлами
     */
    public function handleFileOperation($operation): void {
        if (is_array($operation)) {
            $type = $operation['type'] ?? 'unknown';
            $file = $operation['file'] ?? 'unknown';
            $success = $operation['success'] ?? false;
            
            if ($success) {
                $this->logInfo("File Operation: {$type} - {$file}", [
                    'type' => 'file_operation',
                    'operation' => $type,
                    'file' => $file
                ]);
            } else {
                $this->logError("File Operation Failed: {$type} - {$file}", [
                    'type' => 'file_operation',
                    'operation' => $type,
                    'file' => $file
                ]);
            }
        }
    }
    
    /**
     * Обработчик активации плагина
     */
    public function handlePluginActivated($pluginSlug): void {
        $this->logSuccess("Plugin Activated: {$pluginSlug}", [
            'type' => 'plugin',
            'action' => 'activated',
            'plugin' => $pluginSlug
        ]);
    }
    
    /**
     * Обработчик деактивации плагина
     */
    public function handlePluginDeactivated($pluginSlug): void {
        $this->logInfo("Plugin Deactivated: {$pluginSlug}", [
            'type' => 'plugin',
            'action' => 'deactivated',
            'plugin' => $pluginSlug
        ]);
    }
    
    /**
     * Обработчик установки плагина
     */
    public function handlePluginInstalled($pluginSlug): void {
        $this->logSuccess("Plugin Installed: {$pluginSlug}", [
            'type' => 'plugin',
            'action' => 'installed',
            'plugin' => $pluginSlug
        ]);
    }
    
    /**
     * Обработчик удаления плагина
     */
    public function handlePluginUninstalled($pluginSlug): void {
        $this->logWarning("Plugin Uninstalled: {$pluginSlug}", [
            'type' => 'plugin',
            'action' => 'uninstalled',
            'plugin' => $pluginSlug
        ]);
    }
    
    /**
     * Обработчик загрузки модуля
     */
    public function handleModuleLoaded($moduleName): void {
        // Логируем загрузку модуля как информацию (если включено логирование событий модулей)
        // Это помогает отслеживать, какие модули загружаются при каждом запросе
        if ($this->getSetting('log_module_events', '1') === '1') {
            $this->logInfo("Module Loaded: {$moduleName}", [
                'type' => 'module',
                'action' => 'loaded',
                'module' => $moduleName,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Обработчик ошибки модуля
     */
    public function handleModuleError($error): void {
        if (is_array($error)) {
            $module = $error['module'] ?? 'unknown';
            $message = $error['message'] ?? 'Unknown error';
            $this->logError("Module Error: {$module} - {$message}", [
                'type' => 'module',
                'action' => 'error',
                'module' => $module,
                'error' => $error
            ]);
        }
    }
    
    /**
     * Создание директории для логов
     */
    private function ensureLogsDir(): void {
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0755, true);
        }
        
        // Создаем .htaccess для защиты логов
        $htaccessFile = $this->logsDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
    
    /**
     * Регистрация обработчика ошибок PHP
     */
    private function registerErrorHandler(): void {
        // Сохраняем предыдущий обработчик ошибок
        $previousHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$previousHandler) {
            // Логируем ошибку
            try {
                $this->logError("PHP Error [{$errno}]: {$errstr}", [
                    'file' => $errfile,
                    'line' => $errline,
                    'error_code' => $errno
                ]);
            } catch (Exception $e) {
                // Если не удалось записать лог, используем стандартный error_log
                error_log("Logger error: " . $e->getMessage());
            }
            
            // Вызываем предыдущий обработчик, если он был
            if ($previousHandler !== null) {
                return call_user_func($previousHandler, $errno, $errstr, $errfile, $errline);
            }
            
            return false; // Продолжаем стандартную обработку
        });
        
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                try {
                    $this->logError("Fatal Error: {$error['message']}", [
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'error_type' => $error['type']
                    ]);
                } catch (Exception $e) {
                    error_log("Logger error: " . $e->getMessage());
                }
            }
        });
    }
    
    /**
     * Запись лога
     * 
     * @param string $type Тип лога
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return bool
     */
    public function log(string $type, string $message, array $context = []): bool {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $date = date('Y-m-d');
            $logFile = $this->logsDir . $date . '.log';
            
            // Формируем запись лога
            $logEntry = [
                'timestamp' => $timestamp,
                'type' => $type,
                'message' => $message,
                'context' => $context,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ];
            
            $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            
            // Записываем в файл
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
            // Проверяем размер файла и ротируем при необходимости
            $this->rotateLogIfNeeded($logFile);
            
            // Очищаем старые логи
            $this->cleanOldLogs();
            
            return true;
        } catch (Exception $e) {
            // Если не удалось записать лог, пытаемся записать в error_log
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Запись ошибки
     */
    public function logError(string $message, array $context = []): bool {
        if ($this->getSetting('log_errors', '1') !== '1') {
            return false;
        }
        return $this->log(self::TYPE_ERROR, $message, $context);
    }
    
    /**
     * Запись предупреждения
     */
    public function logWarning(string $message, array $context = []): bool {
        if ($this->getSetting('log_warnings', '1') !== '1') {
            return false;
        }
        return $this->log(self::TYPE_WARNING, $message, $context);
    }
    
    /**
     * Запись информации
     */
    public function logInfo(string $message, array $context = []): bool {
        if ($this->getSetting('log_info', '1') !== '1') {
            return false;
        }
        return $this->log(self::TYPE_INFO, $message, $context);
    }
    
    /**
     * Запись успешного события
     */
    public function logSuccess(string $message, array $context = []): bool {
        if ($this->getSetting('log_success', '1') !== '1') {
            return false;
        }
        return $this->log(self::TYPE_SUCCESS, $message, $context);
    }
    
    /**
     * Запись отладочной информации
     */
    public function logDebug(string $message, array $context = []): bool {
        if ($this->getSetting('log_debug', '0') !== '1') {
            return false;
        }
        return $this->log(self::TYPE_DEBUG, $message, $context);
    }
    
    /**
     * Получение логов
     * 
     * @param string|null $type Тип лога (null для всех)
     * @param int $limit Количество записей
     * @param int $offset Смещение
     * @return array
     */
    public function getLogs(?string $type = null, int $limit = 100, int $offset = 0): array {
        $logs = [];
        $files = glob($this->logsDir . '*.log');
        
        if ($files === false) {
            return $logs;
        }
        
        // Сортируем файлы по дате (новые первыми)
        rsort($files);
        
        $count = 0;
        foreach ($files as $file) {
            if (!is_readable($file)) {
                continue;
            }
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }
            
            // Читаем файл с конца
            $lines = array_reverse($lines);
            
            foreach ($lines as $line) {
                if ($count < $offset) {
                    $count++;
                    continue;
                }
                
                if (count($logs) >= $limit) {
                    break 2;
                }
                
                $logEntry = json_decode($line, true);
                if ($logEntry === null) {
                    continue;
                }
                
                // Фильтруем по типу, если указан
                if ($type !== null && ($logEntry['type'] ?? '') !== $type) {
                    continue;
                }
                
                $logs[] = $logEntry;
                $count++;
            }
        }
        
        return $logs;
    }
    
    /**
     * Очистка логов
     * 
     * @param string|null $type Тип лога (null для всех)
     * @param int|null $days Количество дней (null для всех)
     * @return bool
     */
    public function clearLogs(?string $type = null, ?int $days = null): bool {
        try {
            $files = glob($this->logsDir . '*.log');
            
            if ($files === false) {
                return false;
            }
            
            $deleted = 0;
            $now = time();
            
            foreach ($files as $file) {
                $fileDate = filemtime($file);
                $fileAge = ($now - $fileDate) / 86400; // Дни
                
                // Проверяем возраст файла
                if ($days !== null && $fileAge < $days) {
                    continue;
                }
                
                // Если указан тип, проверяем содержимое файла
                if ($type !== null) {
                    $hasType = false;
                    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines !== false) {
                        foreach ($lines as $line) {
                            $logEntry = json_decode($line, true);
                            if ($logEntry !== null && ($logEntry['type'] ?? '') === $type) {
                                $hasType = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$hasType) {
                        continue;
                    }
                    
                    // Удаляем только записи указанного типа
                    $newLines = [];
                    foreach ($lines as $line) {
                        $logEntry = json_decode($line, true);
                        if ($logEntry !== null && ($logEntry['type'] ?? '') !== $type) {
                            $newLines[] = $line;
                        }
                    }
                    file_put_contents($file, implode("\n", $newLines) . "\n");
                } else {
                    // Удаляем весь файл
                    unlink($file);
                }
                
                $deleted++;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error clearing logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение статистики логов
     * 
     * @return array
     */
    public function getLogStats(): array {
        $stats = [
            'total' => 0,
            'by_type' => [
                self::TYPE_ERROR => 0,
                self::TYPE_WARNING => 0,
                self::TYPE_INFO => 0,
                self::TYPE_SUCCESS => 0,
                self::TYPE_DEBUG => 0
            ],
            'by_date' => [],
            'total_size' => 0
        ];
        
        $files = glob($this->logsDir . '*.log');
        
        if ($files === false) {
            return $stats;
        }
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            $date = date('Y-m-d', filemtime($file));
            
            if (!isset($stats['by_date'][$date])) {
                $stats['by_date'][$date] = 0;
            }
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }
            
            foreach ($lines as $line) {
                $logEntry = json_decode($line, true);
                if ($logEntry === null) {
                    continue;
                }
                
                $stats['total']++;
                $logType = $logEntry['type'] ?? 'unknown';
                if (isset($stats['by_type'][$logType])) {
                    $stats['by_type'][$logType]++;
                }
                
                $logDate = substr($logEntry['timestamp'] ?? '', 0, 10);
                if ($logDate) {
                    if (!isset($stats['by_date'][$logDate])) {
                        $stats['by_date'][$logDate] = 0;
                    }
                    $stats['by_date'][$logDate]++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Ротация лога при превышении размера
     */
    private function rotateLogIfNeeded(string $logFile): void {
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) > $this->maxLogFileSize) {
            $backupFile = $logFile . '.' . time() . '.bak';
            rename($logFile, $backupFile);
        }
    }
    
    /**
     * Очистка старых логов
     */
    private function cleanOldLogs(): void {
        $files = glob($this->logsDir . '*.log*');
        
        if ($files === false) {
            return;
        }
        
        $now = time();
        $maxAge = $this->logRetentionDays * 86400;
        
        foreach ($files as $file) {
            if (filemtime($file) < ($now - $maxAge)) {
                unlink($file);
            }
        }
    }
}

/**
 * Глобальная функция для получения экземпляра Logger
 * Загружает модуль по требованию (ленивая загрузка)
 *
 * @return Logger
 */
if (!function_exists('logger')) {
    function logger(): Logger {
        // Загружаем модуль по требованию
        if (!class_exists('Logger')) {
            require_once dirname(__DIR__) . '/modules/Logger.php';
        }
        
        // Убеждаемся, что модуль загружен через ModuleLoader
        if (!ModuleLoader::isModuleLoaded('Logger')) {
            ModuleLoader::loadModule('Logger');
        }
        
        return Logger::getInstance();
    }
}

