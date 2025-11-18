<?php
/**
 * Обертка над Request для админ-панели
 * Интегрирует функционал из engine/classes/http/Request
 * 
 * @package Engine\Skins\Includes
 */

class AdminRequest {
    private static ?Request $instance = null;
    
    /**
     * Получить экземпляр Request
     */
    public static function instance(): Request {
        if (self::$instance === null) {
            self::$instance = Request::getInstance();
        }
        return self::$instance;
    }
    
    /**
     * Быстрое получение значения из запроса
     */
    public static function input(string $key, $default = null) {
        return self::instance()->get($key, $default);
    }
    
    /**
     * Получить значение из POST
     */
    public static function post(string $key, $default = null) {
        return self::instance()->post($key, $default);
    }
    
    /**
     * Получить значение из GET
     */
    public static function query(string $key, $default = null) {
        return self::instance()->query($key, $default);
    }
    
    /**
     * Получить метод запроса
     */
    public static function method(): string {
        return self::instance()->method();
    }
    
    /**
     * Проверка метода запроса
     */
    public static function isMethod(string $method): bool {
        return self::instance()->isMethod($method);
    }
    
    /**
     * Проверка наличия ключа
     */
    public static function has(string $key): bool {
        return self::instance()->has($key);
    }
    
    /**
     * Получить все данные запроса
     */
    public static function all(): array {
        return self::instance()->all();
    }
    
    /**
     * Получить только указанные ключи
     */
    public static function only(array $keys): array {
        return self::instance()->only($keys);
    }
    
    /**
     * Получить все данные кроме указанных ключей
     */
    public static function except(array $keys): array {
        return self::instance()->except($keys);
    }
    
    /**
     * Получить загруженные файлы
     */
    public static function files(): array {
        return self::instance()->files();
    }
    
    /**
     * Получить IP адрес клиента
     */
    public static function ip(): string {
        return self::instance()->ip();
    }
    
    /**
     * Получить User Agent
     */
    public static function userAgent(): string {
        return self::instance()->userAgent();
    }
}

