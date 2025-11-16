<?php
/**
 * Клас для роботи з сесіями
 * Управління сесіями користувачів з додатковою безпекою
 * 
 * @package Engine\Classes\Security
 * @version 1.1.0
 */

declare(strict_types=1);

class Session {
    private static bool $started = false;
    private static array $config = [
        'name' => 'PHPSESSID',
        'lifetime' => 7200,
        'domain' => '',
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    /**
     * Ініціалізація сесії
     * 
     * @param array $config Конфігурація сесії
     * @return void
     */
    public static function start(array $config = []): void {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        
        self::$config = array_merge(self::$config, $config);
        
        // Налаштування параметрів сесії
        ini_set('session.cookie_lifetime', self::$config['lifetime']);
        ini_set('session.cookie_path', self::$config['path']);
        ini_set('session.cookie_domain', self::$config['domain']);
        ini_set('session.cookie_secure', self::$config['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', self::$config['httponly'] ? '1' : '0');
        
        if (isset(self::$config['samesite'])) {
            ini_set('session.cookie_samesite', self::$config['samesite']);
        }
        
        session_name(self::$config['name']);
        
        if (!headers_sent()) {
            session_start();
            self::$started = true;
        }
    }
    
    /**
     * Отримання значення з сесії
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Встановлення значення в сесію
     * 
     * @param string $key Ключ
     * @param mixed $value Значення
     * @return void
     */
    public static function set(string $key, $value): void {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Перевірка наявності ключа в сесії
     * 
     * @param string $key Ключ
     * @return bool
     */
    public static function has(string $key): bool {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Видалення значення з сесії
     * 
     * @param string $key Ключ
     * @return void
     */
    public static function remove(string $key): void {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }
    
    /**
     * Отримання всіх даних сесії
     * 
     * @return array
     */
    public static function all(): array {
        self::ensureStarted();
        return $_SESSION ?? [];
    }
    
    /**
     * Очищення всіх даних сесії
     * 
     * @return void
     */
    public static function clear(): void {
        self::ensureStarted();
        $_SESSION = [];
    }
    
    /**
     * Видалення сесії
     * 
     * @return void
     */
    public static function destroy(): void {
        if (!self::$started) {
            return;
        }
        
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, self::$config['path'], self::$config['domain']);
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Регенерація ID сесії
     * 
     * @param bool $deleteOldSession Видаляти стару сесію
     * @return bool
     */
    public static function regenerate(bool $deleteOldSession = true): bool {
        self::ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Отримання ID сесії
     * 
     * @return string
     */
    public static function getId(): string {
        self::ensureStarted();
        return session_id();
    }
    
    /**
     * Встановлення ID сесії
     * 
     * @param string $id ID сесії
     * @return bool
     */
    public static function setId(string $id): bool {
        if (self::$started) {
            return false;
        }
        
        return session_id($id) !== '';
    }
    
    /**
     * Перевірка, чи запущена сесія
     * 
     * @return bool
     */
    public static function isStarted(): bool {
        return self::$started && session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Отримання Flash повідомлення (читається один раз)
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public static function flash(string $key, $default = null) {
        $value = self::get('_flash.' . $key, $default);
        self::remove('_flash.' . $key);
        return $value;
    }
    
    /**
     * Встановлення Flash повідомлення
     * 
     * @param string $key Ключ
     * @param mixed $value Значення
     * @return void
     */
    public static function setFlash(string $key, $value): void {
        self::set('_flash.' . $key, $value);
    }
    
    /**
     * Переконатися, що сесія запущена
     * 
     * @return void
     */
    private static function ensureStarted(): void {
        if (!self::$started) {
            self::start();
        }
    }
}
