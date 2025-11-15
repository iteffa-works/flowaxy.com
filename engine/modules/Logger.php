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
        
        // Создаем директорию, если её нет
        $this->ensureLogsDir();
        
        // Регистрируем обработчик ошибок PHP
        $this->registerErrorHandler();
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
            'order' => 100
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
        $router->add('logs', 'LogsPage');
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
        return $this->log(self::TYPE_ERROR, $message, $context);
    }
    
    /**
     * Запись предупреждения
     */
    public function logWarning(string $message, array $context = []): bool {
        return $this->log(self::TYPE_WARNING, $message, $context);
    }
    
    /**
     * Запись информации
     */
    public function logInfo(string $message, array $context = []): bool {
        return $this->log(self::TYPE_INFO, $message, $context);
    }
    
    /**
     * Запись успешного события
     */
    public function logSuccess(string $message, array $context = []): bool {
        return $this->log(self::TYPE_SUCCESS, $message, $context);
    }
    
    /**
     * Запись отладочной информации
     */
    public function logDebug(string $message, array $context = []): bool {
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
 * 
 * @return Logger
 */
if (!function_exists('logger')) {
    function logger(): Logger {
        return Logger::getInstance();
    }
}

