<?php
/**
 * Базовий клас для системних модулів
 * 
 * @package Engine
 * @version 1.0.0
 */

declare(strict_types=1);

abstract class BaseModule {
    protected ?PDO $db = null;
    protected static array $instances = [];
    private static bool $initializing = false;
    
    /**
     * Конструктор
     * Важливо: БД завантажується ліниво, щоб уникнути циклічних залежностей
     */
    protected function __construct() {
        // НЕ завантажуємо БД в конструкторі, щоб уникнути циклічних залежностей
        // БД буде завантажена пізніше через getDB() метод
        $this->db = null;
        $this->init();
    }
    
    /**
     * Ліниве отримання підключення до БД
     * 
     * @return PDO|null
     */
    protected function getDB(): ?PDO {
        if ($this->db === null && !self::$initializing) {
            try {
                self::$initializing = true;
                $this->db = DatabaseHelper::getConnection(false); // Не показуємо помилку, щоб уникнути рекурсії
            } catch (Exception $e) {
                // Ігноруємо помилки БД в конструкторі модулів
                if (function_exists('logger')) {
                    logger()->logError('BaseModule: Failed to get DB connection', ['error' => $e->getMessage()]);
                } else {
                    error_log("BaseModule: Failed to get DB connection: " . $e->getMessage());
                }
            } finally {
                self::$initializing = false;
            }
        }
        return $this->db;
    }
    
    /**
     * Ініціалізація модуля (перевизначається в дочірніх класах)
     */
    protected function init(): void {
        // Перевизначається в дочірніх класах
    }
    
    /**
     * Отримання екземпляра модуля (Singleton)
     * 
     * @return static
     */
    public static function getInstance() {
        $className = static::class;
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new static();
        }
        return self::$instances[$className];
    }
    
    /**
     * Реєстрація хуків модуля
     * Викликається автоматично при завантаженні модуля
     */
    public function registerHooks(): void {
        // Перевизначається в дочірніх класах
    }
    
    /**
     * Отримання інформації про модуль
     * 
     * @return array
     */
    abstract public function getInfo(): array;
    
    /**
     * Отримання API методів модуля
     * 
     * @return array Масив з описом доступних методів
     */
    public function getApiMethods(): array {
        return [];
    }
}

