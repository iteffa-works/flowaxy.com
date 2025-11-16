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
    
    /**
     * Конструктор
     */
    protected function __construct() {
        $this->db = getDB();
        $this->init();
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

