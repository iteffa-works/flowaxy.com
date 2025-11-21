<?php
/**
 * Менеджер для работы с cookies
 * Централизованное управление cookies с расширенными возможностями
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../storage/StorageInterface.php';

class CookieManager implements StorageInterface {
    private static ?self $instance = null;
    private array $defaultOptions = [
        'expire' => 0,          // 0 = до закрытия браузера
        'path' => '/',
        'domain' => null,
        'secure' => false,      // Автоматически определяется
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    private function __construct() {
        // Определяем secure автоматически на основе протокола из настроек
        if (class_exists('UrlHelper')) {
            $this->defaultOptions['secure'] = UrlHelper::isHttps();
        } elseif (function_exists('detectProtocol')) {
            $protocol = detectProtocol();
            $this->defaultOptions['secure'] = ($protocol === 'https://');
        } else {
            // Fallback на автоматическое определение
            $this->defaultOptions['secure'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        }
        $this->defaultOptions['domain'] = $_SERVER['HTTP_HOST'] ?? null;
    }
    
    /**
     * Получение экземпляра (Singleton)
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
     * Получение значения из cookie
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return Cookie::get($key, $default);
    }
    
    /**
     * Установка значения в cookie
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @param array $options Дополнительные опции
     * @return bool
     */
    public function set(string $key, $value, array $options = []): bool {
        $options = array_merge($this->defaultOptions, $options);
        
        // Поддержка массива/объекта (JSON)
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        // Поддержка времени в днях
        $expire = $options['expire'];
        if (is_int($expire) && $expire > 0) {
            $expire = time() + $expire;
        } elseif (is_string($expire) && strpos($expire, '+') === 0) {
            // Поддержка формата "+30 days"
            $expire = strtotime($expire);
        }
        
        return Cookie::set(
            $key,
            (string)$value,
            $expire,
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly'],
            $options['samesite']
        );
    }
    
    /**
     * Проверка наличия ключа в cookie
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool {
        return Cookie::has($key);
    }
    
    /**
     * Удаление значения из cookie
     * 
     * @param string $key Ключ
     * @param array $options Дополнительные опции
     * @return bool
     */
    public function remove(string $key, array $options = []): bool {
        $options = array_merge($this->defaultOptions, $options);
        return Cookie::delete($key, $options['path'], $options['domain']);
    }
    
    /**
     * Получение всех данных из cookie
     * 
     * @return array
     */
    public function all(): array {
        return $_COOKIE ?? [];
    }
    
    /**
     * Очистка всех данных из cookie (не рекомендуется)
     * 
     * @return bool
     */
    public function clear(): bool {
        $result = true;
        foreach ($_COOKIE as $key => $value) {
            if (!$this->remove($key)) {
                $result = false;
            }
        }
        return $result;
    }
    
    /**
     * Получение нескольких значений по ключам
     * 
     * @param array $keys Массив ключей
     * @return array
     */
    public function getMultiple(array $keys): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }
    
    /**
     * Установка нескольких значений
     * 
     * @param array $values Массив ключ => значение
     * @return bool
     */
    public function setMultiple(array $values): bool {
        $result = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value)) {
                $result = false;
            }
        }
        return $result;
    }
    
    /**
     * Установка нескольких значений с опциями
     * 
     * @param array $values Массив ключ => значение
     * @param array $options Дополнительные опции
     * @return bool
     */
    public function setMultipleWithOptions(array $values, array $options = []): bool {
        $result = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $options)) {
                $result = false;
            }
        }
        return $result;
    }
    
    /**
     * Удаление нескольких значений
     * 
     * @param array $keys Массив ключей
     * @param array $options Дополнительные опции
     * @return bool
     */
    public function removeMultiple(array $keys, array $options = []): bool {
        $result = true;
        foreach ($keys as $key) {
            if (!$this->remove($key, $options)) {
                $result = false;
            }
        }
        return $result;
    }
    
    /**
     * Установка постоянной cookie (на год)
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @param int $days Количество дней
     * @return bool
     */
    public function forever(string $key, $value, int $days = 365): bool {
        return $this->set($key, $value, ['expire' => time() + ($days * 86400)]);
    }
    
    /**
     * Установка временной cookie (до закрытия браузера)
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return bool
     */
    public function temporary(string $key, $value): bool {
        return $this->set($key, $value, ['expire' => 0]);
    }
    
    /**
     * Установка зашифрованной cookie
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @param int $expire Время истечения
     * @param string|null $encryptionKey Ключ шифрования
     * @return bool
     */
    public function encrypted(string $key, $value, int $expire = 0, ?string $encryptionKey = null): bool {
        return Cookie::encrypted($key, (string)$value, $expire, $encryptionKey);
    }
    
    /**
     * Получение расшифрованной cookie
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @param string|null $encryptionKey Ключ шифрования
     * @return mixed
     */
    public function decrypted(string $key, $default = null, ?string $encryptionKey = null) {
        return Cookie::decrypted($key, $default, $encryptionKey);
    }
    
    /**
     * Получение значения как JSON (автоматическое декодирование)
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getJson(string $key, $default = null) {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }
        
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }
    
    /**
     * Установка значения как JSON (автоматическое кодирование)
     * 
     * @param string $key Ключ
     * @param mixed $value Значение (будет закодировано в JSON)
     * @param array $options Дополнительные опции
     * @return bool
     */
    public function setJson(string $key, $value, array $options = []): bool {
        $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        return $this->set($key, $jsonValue, $options);
    }
    
    /**
     * Установка настроек по умолчанию
     * 
     * @param array $options Опции
     * @return void
     */
    public function setDefaultOptions(array $options): void {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }
    
    /**
     * Получение настроек по умолчанию
     * 
     * @return array
     */
    public function getDefaultOptions(): array {
        return $this->defaultOptions;
    }
}

