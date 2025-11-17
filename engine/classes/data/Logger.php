<?php
/**
 * Централизованная система логирования
 * Поддержка различных уровней логирования, ротации файлов, фильтрации
 * 
 * @package Engine\Classes\Data
 * @version 1.0.0
 */

declare(strict_types=1);

class Logger {
    private static ?self $instance = null;
    private string $logDir;
    private string $logFile;
    private int $maxFileSize = 10 * 1024 * 1024; // 10 MB
    private int $maxFiles = 5;
    private array $settings = [];
    
    // Уровни логирования
    public const LEVEL_DEBUG = 0;
    public const LEVEL_INFO = 1;
    public const LEVEL_WARNING = 2;
    public const LEVEL_ERROR = 3;
    public const LEVEL_CRITICAL = 4;
    
    private const LEVEL_NAMES = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];
    
    /**
     * Конструктор (приватный для Singleton)
     */
    private function __construct() {
        $this->logDir = defined('LOGS_DIR') ? LOGS_DIR : dirname(__DIR__, 2) . '/storage/logs/';
        $this->logDir = rtrim($this->logDir, '/') . '/';
        $this->ensureLogDir();
        
        // Имя файла лога с датой
        $this->logFile = $this->logDir . 'app-' . date('Y-m-d') . '.log';
        
        // Загружаем настройки
        $this->loadSettings();
    }
    
    /**
     * Получение экземпляра класса (Singleton)
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Создание директории логов
     * 
     * @return void
     */
    private function ensureLogDir(): void {
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
        
        // Создаем .htaccess для защиты
        $htaccessFile = $this->logDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
    
    /**
     * Загрузка настроек из БД или файла
     * 
     * @return void
     */
    private function loadSettings(): void {
        // Настройки по умолчанию
        $this->settings = [
            'min_level' => self::LEVEL_DEBUG,
            'log_to_file' => true,
            'log_to_error_log' => false,
            'log_db_queries' => false,
            'log_db_errors' => true,
            'log_slow_queries' => true,
            'slow_query_threshold' => 1.0,
            'max_file_size' => $this->maxFileSize,
            'max_files' => $this->maxFiles
        ];
        
        // Загружаем из БД, если доступна
        if (class_exists('SettingsManager')) {
            $settings = settingsManager();
            $this->settings['min_level'] = (int)$settings->get('logger_min_level', (string)self::LEVEL_DEBUG);
            $this->settings['log_to_file'] = $settings->get('logger_log_to_file', '1') === '1';
            $this->settings['log_to_error_log'] = $settings->get('logger_log_to_error_log', '0') === '1';
            $this->settings['log_db_queries'] = $settings->get('logger_log_db_queries', '0') === '1';
            $this->settings['log_db_errors'] = $settings->get('logger_log_db_errors', '1') === '1';
            $this->settings['log_slow_queries'] = $settings->get('logger_log_slow_queries', '1') === '1';
            $this->settings['slow_query_threshold'] = (float)$settings->get('logger_slow_query_threshold', '1.0');
        }
    }
    
    /**
     * Получение настройки
     * 
     * @param string $key Ключ настройки
     * @param string $default Значение по умолчанию
     * @return string
     */
    public function getSetting(string $key, string $default = ''): string {
        return (string)($this->settings[$key] ?? $default);
    }
    
    /**
     * Установка настройки
     * 
     * @param string $key Ключ настройки
     * @param string $value Значение
     * @return void
     */
    public function setSetting(string $key, string $value): void {
        $this->settings[$key] = $value;
        
        // Сохраняем в БД, если доступна
        if (class_exists('SettingsManager')) {
            settingsManager()->set('logger_' . $key, $value);
        }
    }
    
    /**
     * Логирование сообщения
     * 
     * @param int $level Уровень логирования
     * @param string $message Сообщение
     * @param array $context Контекст (дополнительные данные)
     * @return void
     */
    public function log(int $level, string $message, array $context = []): void {
        // Проверяем минимальный уровень
        if ($level < $this->settings['min_level']) {
            return;
        }
        
        $levelName = self::LEVEL_NAMES[$level] ?? 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Формируем контекстную строку
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        // Формируем строку лога
        $logLine = sprintf(
            "[%s] %s: %s | IP: %s | %s %s%s\n",
            $timestamp,
            $levelName,
            $message,
            $ip,
            $method,
            $uri,
            $contextStr
        );
        
        // Логируем в файл
        if ($this->settings['log_to_file']) {
            $this->writeToFile($logLine);
        }
        
        // Логируем в error_log
        if ($this->settings['log_to_error_log']) {
            error_log(trim($logLine));
        }
    }
    
    /**
     * Запись в файл с ротацией
     * 
     * @param string $logLine Строка для записи
     * @return void
     */
    private function writeToFile(string $logLine): void {
        // Проверяем размер файла и ротируем при необходимости
        if (file_exists($this->logFile) && filesize($this->logFile) >= $this->settings['max_file_size']) {
            $this->rotateLogs();
        }
        
        // Записываем в файл
        @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Ротация логов
     * 
     * @return void
     */
    private function rotateLogs(): void {
        $pattern = $this->logDir . 'app-*.log';
        $files = glob($pattern);
        
        if ($files === false) {
            return;
        }
        
        // Сортируем по дате изменения (новые первыми)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Удаляем старые файлы
        $maxFiles = $this->settings['max_files'];
        for ($i = $maxFiles; $i < count($files); $i++) {
            @unlink($files[$i]);
        }
        
        // Переименовываем текущий файл
        if (file_exists($this->logFile)) {
            $newName = $this->logDir . 'app-' . date('Y-m-d') . '-' . time() . '.log';
            @rename($this->logFile, $newName);
        }
    }
    
    /**
     * Логирование DEBUG
     * 
     * @param string $message Сообщение
     * @param array $context Контекст
     * @return void
     */
    public function logDebug(string $message, array $context = []): void {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Логирование INFO
     * 
     * @param string $message Сообщение
     * @param array $context Контекст
     * @return void
     */
    public function logInfo(string $message, array $context = []): void {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Логирование WARNING
     * 
     * @param string $message Сообщение
     * @param array $context Контекст
     * @return void
     */
    public function logWarning(string $message, array $context = []): void {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Логирование ERROR
     * 
     * @param string $message Сообщение
     * @param array $context Контекст
     * @return void
     */
    public function logError(string $message, array $context = []): void {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Логирование CRITICAL
     * 
     * @param string $message Сообщение
     * @param array $context Контекст
     * @return void
     */
    public function logCritical(string $message, array $context = []): void {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Логирование исключения
     * 
     * @param Throwable $exception Исключение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public function logException(\Throwable $exception, array $context = []): void {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        $this->logError('Exception: ' . $exception->getMessage(), $context);
    }
    
    /**
     * Получение последних записей лога
     * 
     * @param int $lines Количество строк
     * @return array
     */
    public function getRecentLogs(int $lines = 100): array {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = @file_get_contents($this->logFile);
        if ($content === false) {
            return [];
        }
        
        $allLines = explode("\n", $content);
        $allLines = array_filter($allLines, fn($line) => !empty(trim($line)));
        
        return array_slice($allLines, -$lines);
    }
    
    /**
     * Очистка логов
     * 
     * @return bool
     */
    public function clearLogs(): bool {
        $pattern = $this->logDir . 'app-*.log';
        $files = glob($pattern);
        
        if ($files === false) {
            return false;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Получение статистики логов
     * 
     * @return array
     */
    public function getStats(): array {
        $pattern = $this->logDir . 'app-*.log';
        $files = glob($pattern);
        
        if ($files === false) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'latest_file' => null,
                'latest_size' => 0
            ];
        }
        
        $totalSize = 0;
        $latestFile = null;
        $latestTime = 0;
        
        foreach ($files as $file) {
            $size = @filesize($file);
            if ($size !== false) {
                $totalSize += $size;
            }
            
            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime > $latestTime) {
                $latestTime = $mtime;
                $latestFile = $file;
            }
        }
        
        $latestSize = $latestFile ? @filesize($latestFile) : 0;
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'latest_file' => $latestFile ? basename($latestFile) : null,
            'latest_size' => $latestSize !== false ? $latestSize : 0
        ];
    }
    
    // Предотвращение клонирования
    private function __clone() {}
    
    /**
     * @return void
     * @throws Exception
     */
    public function __wakeup(): void {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Глобальная функция для получения экземпляра Logger
 * 
 * @return Logger
 */
function logger(): Logger {
    return Logger::getInstance();
}

