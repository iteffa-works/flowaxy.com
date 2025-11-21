<?php
/**
 * Менеджер для роботи з сесіями
 * Централізоване управління сесіями з розширеними можливостями
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../storage/StorageInterface.php';

class SessionManager implements StorageInterface {
    private static ?self $instance = null;
    private string $prefix = '';
    private bool $initialized = false;
    
    private function __construct() {
        // Переконуємося, що сесія запущена
        if (!Session::isStarted()) {
            Session::start();
        }
        $this->initialized = true;
    }
    
    /**
     * Отримання екземпляра (Singleton)
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
     * Встановлення префіксу для ключів
     * 
     * @param string $prefix Префікс
     * @return void
     */
    public function setPrefix(string $prefix): void {
        $this->prefix = $prefix;
    }
    
    /**
     * Отримання префіксу
     * 
     * @return string
     */
    public function getPrefix(): string {
        return $this->prefix;
    }
    
    /**
     * Формування повного ключа з префіксом
     * 
     * @param string $key Ключ
     * @return string
     */
    private function getFullKey(string $key): string {
        return $this->prefix ? $this->prefix . '.' . $key : $key;
    }
    
    /**
     * Отримання значення з сесії
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return Session::get($this->getFullKey($key), $default);
    }
    
    /**
     * Встановлення значення в сесію
     * 
     * @param string $key Ключ
     * @param mixed $value Значення
     * @return bool
     */
    public function set(string $key, $value): bool {
        Session::set($this->getFullKey($key), $value);
        return true;
    }
    
    /**
     * Перевірка наявності ключа в сесії
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool {
        return Session::has($this->getFullKey($key));
    }
    
    /**
     * Удаление значения из сессии
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function remove(string $key): bool {
        Session::remove($this->getFullKey($key));
        return true;
    }
    
    /**
     * Получение всех данных из сессии
     * 
     * @param bool $withPrefix Включить только ключи с префиксом
     * @return array
     */
    public function all(bool $withPrefix = true): array {
        $all = Session::all();
        
        if (!$withPrefix || !$this->prefix) {
            return $all;
        }
        
        // Фильтруем только ключи с префиксом
        $result = [];
        $prefixLen = strlen($this->prefix) + 1; // +1 для точки
        
        foreach ($all as $key => $value) {
            if (strpos($key, $this->prefix . '.') === 0) {
                $resultKey = substr($key, $prefixLen);
                $result[$resultKey] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Очистка всех данных из сессии
     * 
     * @param bool $withPrefix Очистить только ключи с префиксом
     * @return bool
     */
    public function clear(bool $withPrefix = true): bool {
        if (!$withPrefix || !$this->prefix) {
            Session::clear();
            return true;
        }
        
        // Очищаем только ключи с префиксом
        $all = Session::all();
        $prefix = $this->prefix . '.';
        $prefixLen = strlen($prefix);
        
        foreach ($all as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                Session::remove($key);
            }
        }
        
        return true;
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
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
        return true;
    }
    
    /**
     * Удаление нескольких значений
     * 
     * @param array $keys Массив ключей
     * @return bool
     */
    public function removeMultiple(array $keys): bool {
        foreach ($keys as $key) {
            $this->remove($key);
        }
        return true;
    }
    
    /**
     * Получение Flash сообщения (читается один раз)
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function flash(string $key, $default = null) {
        return Session::flash($this->getFullKey($key), $default);
    }
    
    /**
     * Установка Flash сообщения
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return void
     */
    public function setFlash(string $key, $value): void {
        Session::setFlash($this->getFullKey($key), $value);
    }
    
    /**
     * Регенерация ID сессии
     * 
     * @param bool $deleteOldSession Удалить старую сессию
     * @return bool
     */
    public function regenerate(bool $deleteOldSession = true): bool {
        return Session::regenerate($deleteOldSession);
    }
    
    /**
     * Получение ID сессии
     * 
     * @return string
     */
    public function getId(): string {
        return Session::getId();
    }
    
    /**
     * Уничтожение сессии
     * 
     * @return void
     */
    public function destroy(): void {
        Session::destroy();
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
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
        }
        
        return $value;
    }
    
    /**
     * Установка значения как JSON (автоматическое кодирование)
     * 
     * @param string $key Ключ
     * @param mixed $value Значение (будет закодировано в JSON, если это строка)
     * @return bool
     */
    public function setJson(string $key, $value): bool {
        // Если значение уже массив/объект, сохраняем как есть (PHP сессия автоматически сериализует)
        if (is_array($value) || is_object($value)) {
            return $this->set($key, $value);
        }
        
        // Если это строка, которая похожа на JSON, проверяем и сохраняем
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Это валидный JSON, сохраняем декодированным
                return $this->set($key, $decoded);
            }
        }
        
        return $this->set($key, $value);
    }
    
    /**
     * Увеличение числового значения
     * 
     * @param string $key Ключ
     * @param int $increment Шаг увеличения
     * @return int Новое значение
     */
    public function increment(string $key, int $increment = 1): int {
        $current = (int)$this->get($key, 0);
        $newValue = $current + $increment;
        $this->set($key, $newValue);
        return $newValue;
    }
    
    /**
     * Уменьшение числового значения
     * 
     * @param string $key Ключ
     * @param int $decrement Шаг уменьшения
     * @return int Новое значение
     */
    public function decrement(string $key, int $decrement = 1): int {
        return $this->increment($key, -$decrement);
    }
    
    /**
     * Получение значения и удаление его из сессии (pull)
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function pull(string $key, $default = null) {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }
}

