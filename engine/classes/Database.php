<?php
/**
 * Улучшенный класс для работы с базой данных
 * Интегрирован с модулем Logger для отслеживания медленных запросов и ошибок
 * 
 * @package Engine\Classes
 * @version 2.0.0
 */

declare(strict_types=1);

class Database {
    private static ?self $instance = null;
    private ?PDO $connection = null;
    private bool $isConnected = false;
    private int $connectionAttempts = 0;
    private const MAX_CONNECTION_ATTEMPTS = 3;
    private const CONNECTION_TIMEOUT = 30;
    
    // Статистика запросов
    private array $queryList = [];
    private array $queryErrors = [];
    private int $queryCount = 0;
    private float $totalQueryTime = 0.0;
    private float $slowQueryThreshold = 1.0; // Порог медленных запросов в секундах
    
    // Logger instance
    private $logger = null;
    
    /**
     * Конструктор (приватный для Singleton)
     */
    private function __construct() {
        // Инициализация logger
        $this->initLogger();
        
        // Загружаем порог медленных запросов из настроек Logger
        if ($this->logger && method_exists($this->logger, 'getSetting')) {
            $threshold = (float)$this->logger->getSetting('slow_query_threshold', '1.0');
            $this->slowQueryThreshold = $threshold;
        }
    }
    
    /**
     * Инициализация Logger
     */
    private function initLogger(): void {
        try {
            // Пытаемся получить Logger через функцию
            if (function_exists('logger')) {
                $this->logger = logger();
            } elseif (class_exists('Logger') && method_exists('Logger', 'getInstance')) {
                $this->logger = Logger::getInstance();
            }
        } catch (Exception $e) {
            // Logger недоступен, продолжаем без него
            error_log("Database: Logger initialization failed: " . $e->getMessage());
        }
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
     * Получение подключения к базе данных
     * 
     * @return PDO
     * @throws Exception
     */
    public function getConnection(): PDO {
        if (!$this->isConnected || $this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Подключение к базе данных
     * 
     * @return void
     * @throws Exception
     */
    private function connect(): void {
        if ($this->connectionAttempts >= self::MAX_CONNECTION_ATTEMPTS) {
            $error = 'Превышено максимальное количество попыток подключения к базе данных';
            $this->logError('Database connection failed', ['error' => $error, 'attempts' => $this->connectionAttempts]);
            throw new Exception($error);
        }
        
        $this->connectionAttempts++;
        $timeStart = $this->getRealTime();
        
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT => self::CONNECTION_TIMEOUT,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->isConnected = true;
            $this->connectionAttempts = 0;
            
            $connectionTime = $this->getRealTime() - $timeStart;
            
            // Оптимизация MySQL настроек
            $this->connection->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            $this->connection->exec("SET SESSION time_zone = '+00:00'");
            $this->connection->exec("SET SESSION sql_auto_is_null = 0");
            
            // Логируем подключение
            $this->logQuery('Connection with MySQL Server', [], $connectionTime, false);
            
            // Получаем версию MySQL
            $version = $this->connection->query("SELECT VERSION() AS version")->fetch(PDO::FETCH_ASSOC);
            if ($version && isset($version['version'])) {
                $this->logInfo('Database connected', [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'mysql_version' => $version['version'],
                    'connection_time' => round($connectionTime, 4)
                ]);
            }
            
        } catch (PDOException $e) {
            $this->isConnected = false;
            $this->connection = null;
            $connectionTime = $this->getRealTime() - $timeStart;
            
            $errorContext = [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'attempt' => $this->connectionAttempts,
                'connection_time' => round($connectionTime, 4),
                'host' => DB_HOST,
                'database' => DB_NAME
            ];
            
            $this->logError('Database connection error', $errorContext);
            error_log("Database connection error (attempt {$this->connectionAttempts}): " . $e->getMessage());
            
            if ($this->connectionAttempts >= self::MAX_CONNECTION_ATTEMPTS) {
                throw new Exception("Ошибка подключения к базе данных после " . self::MAX_CONNECTION_ATTEMPTS . " попыток: " . $e->getMessage());
            }
            
            // Повторная попытка подключения
            sleep(1);
            $this->connect();
        }
    }
    
    /**
     * Выполнение подготовленного запроса
     * 
     * @param string $query SQL запрос
     * @param array $params Параметры для подготовленного запроса
     * @param bool $logQuery Логировать запрос
     * @return PDOStatement
     * @throws PDOException
     */
    public function query(string $query, array $params = [], bool $logQuery = true): PDOStatement {
        $timeStart = $this->getRealTime();
        
        try {
            if (!$this->isConnected || $this->connection === null) {
                $this->connect();
            }
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            
            $executionTime = $this->getRealTime() - $timeStart;
            $this->totalQueryTime += $executionTime;
            $this->queryCount++;
            
            if ($logQuery) {
                $this->logQuery($query, $params, $executionTime);
                
                // Проверка на медленный запрос
                if ($executionTime >= $this->slowQueryThreshold) {
                    $this->logSlowQuery($query, $params, $executionTime);
                }
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $executionTime = $this->getRealTime() - $timeStart;
            
            $errorContext = [
                'query' => $query,
                'params' => $params,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'execution_time' => round($executionTime, 4)
            ];
            
            // Вызываем хук для логирования ошибок БД
            if (function_exists('doHook') && $this->getSetting('log_db_errors', '1') === '1') {
                doHook('db_error', $errorContext);
            }
            
            $this->logError('Database query error', $errorContext);
            $this->queryErrors[] = $errorContext;
            
            throw $e;
        }
    }
    
    /**
     * Получение одной строки
     * 
     * @param string $query SQL запрос
     * @param array $params Параметры
     * @return array|false
     */
    public function getRow(string $query, array $params = []): array|false {
        $stmt = $this->query($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение всех строк
     * 
     * @param string $query SQL запрос
     * @param array $params Параметры
     * @return array
     */
    public function getAll(string $query, array $params = []): array {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение одного значения (первого столбца первой строки)
     * 
     * @param string $query SQL запрос
     * @param array $params Параметры
     * @return mixed
     */
    public function getValue(string $query, array $params = []): mixed {
        $stmt = $this->query($query, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Вставка записи и получение ID
     * 
     * @param string $query SQL запрос INSERT
     * @param array $params Параметры
     * @return int|false ID вставленной записи или false при ошибке
     */
    public function insert(string $query, array $params = []): int|false {
        try {
            $stmt = $this->query($query, $params);
            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Обновление/удаление записей
     * 
     * @param string $query SQL запрос UPDATE/DELETE
     * @param array $params Параметры
     * @return int Количество затронутых строк
     */
    public function execute(string $query, array $params = []): int {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Выполнение транзакции
     * 
     * @param callable $callback Функция для выполнения в транзакции
     * @return mixed
     * @throws Exception
     */
    public function transaction(callable $callback) {
        if ($this->connection === null) {
            throw new Exception("Нет подключения к базе данных");
        }
        
        $timeStart = $this->getRealTime();
        
        try {
            $this->connection->beginTransaction();
            $result = $callback($this->connection);
            $this->connection->commit();
            
            $executionTime = $this->getRealTime() - $timeStart;
            $this->logInfo('Transaction committed', ['execution_time' => round($executionTime, 4)]);
            
            return $result;
        } catch (Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            
            $executionTime = $this->getRealTime() - $timeStart;
            $this->logError('Transaction rolled back', [
                'error' => $e->getMessage(),
                'execution_time' => round($executionTime, 4)
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Безопасное экранирование строки
     * 
     * @param string $string
     * @return string
     */
    public function escape(string $string): string {
        if ($this->connection === null) {
            return addslashes($string);
        }
        return substr($this->connection->quote($string), 1, -1);
    }
    
    /**
     * Логирование запроса
     */
    private function logQuery(string $query, array $params, float $executionTime, bool $isUserQuery = true): void {
        // Логируем системные запросы только если включено
        if (!$isUserQuery && $this->getSetting('log_db_queries', '0') !== '1') {
            return;
        }
        
        $queryInfo = [
            'query' => $this->normalizeQuery($query),
            'params' => $params,
            'time' => round($executionTime, 4),
            'num' => $this->queryCount
        ];
        
        $this->queryList[] = $queryInfo;
        
        // Ограничиваем размер списка запросов
        if (count($this->queryList) > 100) {
            array_shift($this->queryList);
        }
        
        // Логируем через Logger, если включена опция логирования запросов
        if ($this->getSetting('log_db_queries', '0') === '1') {
            // Вызываем хук для логирования
            if (function_exists('doHook')) {
                doHook('db_query', $queryInfo);
            }
            $this->logDebug('Database query', $queryInfo);
        }
    }
    
    /**
     * Логирование медленного запроса
     */
    private function logSlowQuery(string $query, array $params, float $executionTime): void {
        $slowQueryInfo = [
            'query' => $this->normalizeQuery($query),
            'params' => $params,
            'execution_time' => round($executionTime, 4),
            'threshold' => $this->slowQueryThreshold
        ];
        
        // Вызываем хук для логирования медленных запросов
        if (function_exists('doHook') && $this->getSetting('log_slow_queries', '1') === '1') {
            doHook('db_slow_query', $slowQueryInfo);
        }
        
        $this->logWarning('Slow database query detected', $slowQueryInfo);
    }
    
    /**
     * Нормализация SQL запроса (удаление лишних пробелов)
     */
    private function normalizeQuery(string $query): string {
        return preg_replace('/\s+/', ' ', trim($query));
    }
    
    /**
     * Получение реального времени (микросекунды)
     */
    private function getRealTime(): float {
        list($seconds, $microSeconds) = explode(' ', microtime());
        return ((float)$seconds + (float)$microSeconds);
    }
    
    /**
     * Получение настройки
     */
    private function getSetting(string $key, string $default = ''): string {
        // Используем настройки Logger, если доступны
        if ($this->logger && method_exists($this->logger, 'getSetting')) {
            $loggerKey = strpos($key, 'logger_') === 0 ? substr($key, 7) : $key;
            $value = $this->logger->getSetting($loggerKey, $default);
            return $value !== null ? (string)$value : $default;
        }
        
        // Fallback на глобальную функцию
        if (function_exists('getSetting')) {
            $settingKey = 'logger_' . $key;
            return getSetting($settingKey, $default);
        }
        
        return $default;
    }
    
    /**
     * Логирование через Logger
     */
    private function logError(string $message, array $context = []): void {
        if ($this->logger && method_exists($this->logger, 'logError')) {
            $this->logger->logError($message, $context);
        } else {
            error_log("Database ERROR: {$message} " . json_encode($context));
        }
    }
    
    private function logWarning(string $message, array $context = []): void {
        if ($this->logger && method_exists($this->logger, 'logWarning')) {
            $this->logger->logWarning($message, $context);
        } else {
            error_log("Database WARNING: {$message} " . json_encode($context));
        }
    }
    
    private function logInfo(string $message, array $context = []): void {
        if ($this->logger && method_exists($this->logger, 'logInfo')) {
            $this->logger->logInfo($message, $context);
        }
    }
    
    private function logDebug(string $message, array $context = []): void {
        if ($this->logger && method_exists($this->logger, 'logDebug')) {
            $this->logger->logDebug($message, $context);
        }
    }
    
    /**
     * Проверка доступности БД
     * 
     * @return bool
     */
    public function isAvailable(): bool {
        try {
            if (!$this->isConnected || $this->connection === null) {
                $this->connect();
            }
            
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            $this->logError('Database availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Проверка активного соединения
     * 
     * @return bool
     */
    public function ping(): bool {
        try {
            if ($this->connection === null) {
                return false;
            }
            
            $this->connection->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            $this->isConnected = false;
            $this->connection = null;
            return false;
        }
    }
    
    /**
     * Закрытие соединения
     * 
     * @return void
     */
    public function disconnect(): void {
        $this->connection = null;
        $this->isConnected = false;
        $this->connectionAttempts = 0;
    }
    
    /**
     * Получение статистики
     * 
     * @return array
     */
    public function getStats(): array {
        $stats = [
            'connected' => $this->isConnected,
            'query_count' => $this->queryCount,
            'total_query_time' => round($this->totalQueryTime, 4),
            'average_query_time' => $this->queryCount > 0 ? round($this->totalQueryTime / $this->queryCount, 4) : 0,
            'slow_queries' => 0,
            'error_count' => count($this->queryErrors),
            'query_list_size' => count($this->queryList)
        ];
        
        // Подсчет медленных запросов
        foreach ($this->queryList as $query) {
            if (isset($query['time']) && $query['time'] >= $this->slowQueryThreshold) {
                $stats['slow_queries']++;
            }
        }
        
        // Статистика MySQL
        if ($this->connection !== null) {
            try {
                $stmt = $this->connection->query("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Threads_running', 'Uptime', 'Questions', 'Slow_queries')");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = strtolower($row['Variable_name']);
                    $stats['mysql_' . $key] = (int)($row['Value'] ?? 0);
                }
            } catch (PDOException $e) {
                // Игнорируем ошибки получения статистики
            }
        }
        
        return $stats;
    }
    
    /**
     * Получение списка запросов
     * 
     * @return array
     */
    public function getQueryList(): array {
        return $this->queryList;
    }
    
    /**
     * Получение списка ошибок
     * 
     * @return array
     */
    public function getQueryErrors(): array {
        return $this->queryErrors;
    }
    
    /**
     * Установка порога медленных запросов
     * 
     * @param float $seconds
     * @return void
     */
    public function setSlowQueryThreshold(float $seconds): void {
        $this->slowQueryThreshold = $seconds;
        
        // Сохраняем в настройки Logger, если доступен
        if ($this->logger && method_exists($this->logger, 'setSetting')) {
            $this->logger->setSetting('slow_query_threshold', (string)$seconds);
        }
    }
    
    /**
     * Очистка статистики
     * 
     * @return void
     */
    public function clearStats(): void {
        $this->queryList = [];
        $this->queryErrors = [];
        $this->queryCount = 0;
        $this->totalQueryTime = 0.0;
    }
    
    // Предотвращение клонирования
    private function __clone() {}
    
    // Предотвращение десериализации
    public function __wakeup(): void {
        throw new Exception("Cannot unserialize singleton");
    }
}

