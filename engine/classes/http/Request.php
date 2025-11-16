<?php
/**
 * Клас для роботи з HTTP запитами
 * Обробка GET, POST, FILES та інших даних запиту
 * 
 * @package Engine\Classes\Http
 * @version 1.1.0
 */

declare(strict_types=1);

class Request {
    private static ?self $instance = null;
    private array $data;
    
    private function __construct() {
        $this->data = array_merge($_GET, $_POST);
    }
    
    /**
     * Отримання екземпляра (Singleton)
     * 
     * @return self
     */
    public static function getInstance(): self {
        return self::$instance ??= new self();
    }
    
    /**
     * Отримання значення з запиту
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Отримання всіх даних запиту
     * 
     * @return array
     */
    public function all(): array {
        return $this->data;
    }
    
    /**
     * Отримання тільки вказаних ключів
     * 
     * @param array $keys Масив ключів
     * @return array
     */
    public function only(array $keys): array {
        return array_intersect_key($this->data, array_flip($keys));
    }
    
    /**
     * Отримання всіх даних крім вказаних ключів
     * 
     * @param array $keys Масив ключів
     * @return array
     */
    public function except(array $keys): array {
        return array_diff_key($this->data, array_flip($keys));
    }
    
    /**
     * Перевірка наявності ключа
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }
    
    /**
     * Отримання значення з POST
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function post(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Отримання значення з GET
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function query(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Отримання методу запиту
     * 
     * @return string
     */
    public function method(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * Перевірка методу запиту
     * 
     * @param string $method Метод
     * @return bool
     */
    public function isMethod(string $method): bool {
        return strcasecmp($this->method(), $method) === 0;
    }
    
    /**
     * Отримання URL
     * 
     * @return string
     */
    public function url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    }
    
    /**
     * Отримання шляху
     * 
     * @return string
     */
    public function path(): string {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }
    
    /**
     * Отримання IP адреси клієнта
     * 
     * @return string
     */
    public function ip(): string {
        return Security::getClientIp();
    }
    
    /**
     * Отримання User Agent
     * 
     * @return string
     */
    public function userAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Отримання завантажених файлів
     * 
     * @return array
     */
    public function files(): array {
        return $_FILES ?? [];
    }
    
    /**
     * Статичний метод: Швидке отримання значення
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public static function input(string $key, $default = null) {
        return self::getInstance()->get($key, $default);
    }
    
    /**
     * Статичний метод: Швидке отримання методу
     * 
     * @return string
     */
    public static function method(): string {
        return self::getInstance()->method();
    }
}
