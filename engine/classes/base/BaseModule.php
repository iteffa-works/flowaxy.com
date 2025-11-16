<?php
/**
 * Базовый класс для системных модулей
 * 
 * @package Engine
 * @version 1.0.0
 */

declare(strict_types=1);

abstract class BaseModule {
    protected $db;
    protected static $instances = [];
    private static bool $initializing = false;
    
    /**
     * Конструктор
     * Важно: БД загружается лениво, чтобы избежать циклических зависимостей
     */
    protected function __construct() {
        // НЕ загружаем БД в конструкторе, чтобы избежать циклических зависимостей:
        // BaseModule -> getDB() -> Database -> logger() -> Logger -> BaseModule
        // БД будет загружена позже через getDB() метод
        $this->db = null;
        $this->init();
    }
    
    /**
     * Ленивое получение подключения к БД
     * 
     * @return PDO|null
     */
    protected function getDB(): ?PDO {
        if ($this->db === null && !self::$initializing) {
            try {
                self::$initializing = true;
                $this->db = getDB(false); // Не показываем ошибку, чтобы избежать рекурсии
            } catch (Exception $e) {
                // Игнорируем ошибки БД в конструкторе модулей
                error_log("BaseModule: Failed to get DB connection: " . $e->getMessage());
            } finally {
                self::$initializing = false;
            }
        }
        return $this->db;
    }
    
    /**
     * Инициализация модуля (переопределяется в дочерних классах)
     */
    protected function init(): void {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Получение экземпляра модуля (Singleton)
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
     * Регистрация хуков модуля
     * Вызывается автоматически при загрузке модуля
     */
    public function registerHooks(): void {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Получение информации о модуле
     * 
     * @return array
     */
    abstract public function getInfo(): array;
    
    /**
     * Получение API методов модуля
     * 
     * @return array Массив с описанием доступных методов
     */
    public function getApiMethods(): array {
        return [];
    }
}

