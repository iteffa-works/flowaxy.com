<?php
/**
 * Клас для роботи з JSON файлами
 * Читання, запис та валідація JSON даних
 * 
 * @package Engine\Classes\Files
 * @version 1.1.0
 */

declare(strict_types=1);

class Json {
    private string $filePath = '';
    private mixed $data = null;
    private bool $hasData = false;
    private int $encodeFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    private int $decodeFlags = JSON_BIGINT_AS_STRING;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Шлях до JSON файлу
     */
    public function __construct(?string $filePath = null) {
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Встановлення шляху до файлу
     * 
     * @param string $filePath Шлях до JSON файлу
     * @return self
     * @throws Exception Якщо файл існує, але недоступний для читання
     */
    public function setFile(string $filePath): self {
        if (!is_readable($filePath) && file_exists($filePath)) {
            throw new Exception("JSON файл існує, але недоступний для читання: {$filePath}");
        }
        
        $this->filePath = $filePath;
        $this->hasData = false;
        $this->data = null;
        
        if (file_exists($filePath)) {
            $this->load();
        }
        
        return $this;
    }
    
    /**
     * Завантаження даних з JSON файлу
     * 
     * @param bool $assoc Перетворювати в асоціативний масив
     * @param int $depth Максимальна глибина вкладеності
     * @return self
     * @throws Exception Якщо файл не існує або не може бути прочитаний
     */
    public function load(bool $assoc = true, int $depth = 512): self {
        if (empty($this->filePath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("JSON файл не існує: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("JSON файл недоступний для читання: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не вдалося прочитати JSON файл: {$this->filePath}");
        }
        
        $content = trim($content);
        if ($content === '') {
            $this->data = $assoc ? [] : new stdClass();
            $this->hasData = true;
            return $this;
        }
        
        $data = json_decode($content, $assoc, $depth, $this->decodeFlags);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Помилка парсингу JSON файлу '{$this->filePath}': " . json_last_error_msg());
        }
        
        $this->data = $data;
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Збереження даних у JSON файл
     * 
     * @param string|null $filePath Шлях до файлу (якщо null, використовується поточний)
     * @param mixed|null $data Дані для збереження (якщо null, використовуються поточні)
     * @param int|null $encodeFlags Прапорці кодування (якщо null, використовуються поточні)
     * @return bool
     * @throws Exception Якщо не вдалося зберегти файл
     */
    public function save(?string $filePath = null, mixed $data = null, ?int $encodeFlags = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        $dataToSave = $data ?? $this->data;
        $flags = $encodeFlags ?? $this->encodeFlags;
        
        if (empty($targetPath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        $dir = dirname($targetPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new Exception("Не вдалося створити директорію: {$dir}");
        }
        
        $json = json_encode($dataToSave, $flags);
        
        if ($json === false) {
            throw new Exception("Помилка кодування JSON: " . json_last_error_msg());
        }
        
        if (@file_put_contents($targetPath, $json, LOCK_EX) === false) {
            throw new Exception("Не вдалося зберегти JSON файл: {$targetPath}");
        }
        
        @chmod($targetPath, 0644);
        
        if ($filePath === null) {
            $this->data = $dataToSave;
            $this->hasData = true;
        }
        
        return true;
    }
    
    /**
     * Отримання даних
     * 
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function get(mixed $default = null): mixed {
        return $this->hasData ? $this->data : $default;
    }
    
    /**
     * Отримання значення за шляхом (крапкова нотація)
     * 
     * @param string $path Шлях до значення (наприклад, "user.name" або "users.0.email")
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function getPath(string $path, mixed $default = null): mixed {
        if (!$this->hasData) {
            return $default;
        }
        
        $keys = explode('.', $path);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Встановлення даних
     * 
     * @param mixed $data Дані
     * @return self
     */
    public function set(mixed $data): self {
        $this->data = $data;
        $this->hasData = true;
        return $this;
    }
    
    /**
     * Встановлення значення за шляхом (крапкова нотація)
     * 
     * @param string $path Шлях до значення
     * @param mixed $value Значення
     * @return self
     */
    public function setPath(string $path, mixed $value): self {
        if (!$this->hasData) {
            $this->data = [];
            $this->hasData = true;
        }
        
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $target = &$this->data;
        
        foreach ($keys as $key) {
            if (is_array($target)) {
                if (!isset($target[$key]) || !is_array($target[$key])) {
                    $target[$key] = [];
                }
                $target = &$target[$key];
            } elseif (is_object($target)) {
                if (!isset($target->$key) || !is_array($target->$key)) {
                    $target->$key = [];
                }
                $target = &$target->$key;
            } else {
                $target = (array)$target;
                if (!isset($target[$key])) {
                    $target[$key] = [];
                }
                $target = &$target[$key];
            }
        }
        
        if (is_array($target)) {
            $target[$lastKey] = $value;
        } elseif (is_object($target)) {
            $target->$lastKey = $value;
        } else {
            $target = [$lastKey => $value];
        }
        
        return $this;
    }
    
    /**
     * Видалення значення за шляхом
     * 
     * @param string $path Шлях до значення
     * @return self
     */
    public function removePath(string $path): self {
        if (!$this->hasData) {
            return $this;
        }
        
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $target = &$this->data;
        
        foreach ($keys as $key) {
            if (is_array($target) && isset($target[$key])) {
                $target = &$target[$key];
            } elseif (is_object($target) && isset($target->$key)) {
                $target = &$target->$key;
            } else {
                return $this;
            }
        }
        
        if (is_array($target)) {
            unset($target[$lastKey]);
        } elseif (is_object($target)) {
            unset($target->$lastKey);
        }
        
        return $this;
    }
    
    /**
     * Перевірка наявності значення за шляхом
     * 
     * @param string $path Шлях до значення
     * @return bool
     */
    public function hasPath(string $path): bool {
        if (!$this->hasData) {
            return false;
        }
        
        $keys = explode('.', $path);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Очищення всіх даних
     * 
     * @return self
     */
    public function clear(): self {
        $this->data = null;
        $this->hasData = false;
        return $this;
    }
    
    /**
     * Перевірка, чи завантажені дані
     * 
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->hasData;
    }
    
    /**
     * Отримання шляху до файлу
     * 
     * @return string
     */
    public function getFilePath(): string {
        return $this->filePath;
    }
    
    /**
     * Встановлення прапорців кодування
     * 
     * @param int $flags Прапорці кодування
     * @return self
     */
    public function setEncodeFlags(int $flags): self {
        $this->encodeFlags = $flags;
        return $this;
    }
    
    /**
     * Встановлення прапорців декодування
     * 
     * @param int $flags Прапорці декодування
     * @return self
     */
    public function setDecodeFlags(int $flags): self {
        $this->decodeFlags = $flags;
        return $this;
    }
    
    /**
     * Отримання прапорців кодування
     * 
     * @return int
     */
    public function getEncodeFlags(): int {
        return $this->encodeFlags;
    }
    
    /**
     * Отримання прапорців декодування
     * 
     * @return int
     */
    public function getDecodeFlags(): int {
        return $this->decodeFlags;
    }
    
    /**
     * Валідація JSON рядка
     * 
     * @param string $json JSON рядок
     * @param bool $assoc Перетворювати в асоціативний масив
     * @return array ['valid' => bool, 'data' => mixed|null, 'error' => string|null]
     */
    public static function validate(string $json, bool $assoc = true): array {
        $json = trim($json);
        
        if ($json === '') {
            return ['valid' => false, 'data' => null, 'error' => 'Порожній рядок'];
        }
        
        $data = json_decode($json, $assoc);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'data' => null, 'error' => json_last_error_msg()];
        }
        
        return ['valid' => true, 'data' => $data, 'error' => null];
    }
    
    /**
     * Кодування даних у JSON рядок
     * 
     * @param mixed $data Дані
     * @param int $flags Прапорці кодування
     * @return string
     * @throws Exception Якщо не вдалося закодувати
     */
    public static function encode(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string {
        $json = json_encode($data, $flags);
        
        if ($json === false) {
            throw new Exception("Помилка кодування JSON: " . json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Декодування JSON рядка
     * 
     * @param string $json JSON рядок
     * @param bool $assoc Перетворювати в асоціативний масив
     * @param int $depth Максимальна глибина вкладеності
     * @return mixed
     * @throws Exception Якщо не вдалося декодувати
     */
    public static function decode(string $json, bool $assoc = true, int $depth = 512): mixed {
        $json = trim($json);
        
        if ($json === '') {
            return $assoc ? [] : new stdClass();
        }
        
        $data = json_decode($json, $assoc, $depth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Помилка декодування JSON: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Читання JSON файлу (статичний метод)
     * 
     * @param string $filePath Шлях до файлу
     * @param bool $assoc Перетворювати в асоціативний масив
     * @return mixed
     */
    public static function read(string $filePath, bool $assoc = true): mixed {
        return (new self($filePath))->load($assoc)->get();
    }
    
    /**
     * Запис JSON файлу (статичний метод)
     * 
     * @param string $filePath Шлях до файлу
     * @param mixed $data Дані
     * @param int $flags Прапорці кодування
     * @return bool
     */
    public static function write(string $filePath, mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): bool {
        return (new self())->save($filePath, $data, $flags);
    }
    
    /**
     * Парсинг JSON рядка
     * 
     * @param string $json JSON рядок
     * @param bool $assoc Перетворювати в асоціативний масив
     * @param int $depth Максимальна глибина вкладеності
     * @return mixed
     * @throws Exception Якщо не вдалося декодувати
     */
    public static function parse(string $json, bool $assoc = true, int $depth = 512): mixed {
        return self::decode($json, $assoc, $depth);
    }
    
    /**
     * Перетворення даних у JSON рядок
     * 
     * @param mixed $data Дані
     * @param int $flags Прапорці кодування
     * @return string
     * @throws Exception Якщо не вдалося закодувати
     */
    public static function stringify(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string {
        return self::encode($data, $flags);
    }
    
    /**
     * Перевірка, чи є рядок валідним JSON
     * 
     * @param string $json JSON рядок
     * @return bool
     */
    public static function isValid(string $json): bool {
        return self::validate($json)['valid'];
    }
    
    /**
     * Мініфікація JSON (видалення пробілів та переносів рядків)
     * 
     * @param string $json JSON рядок
     * @return string
     * @throws Exception Якщо не вдалося обробити
     */
    public static function minify(string $json): string {
        return self::encode(self::decode($json), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Форматування JSON (додавання відступів)
     * 
     * @param string $json JSON рядок
     * @param int $flags Прапорці кодування
     * @return string
     * @throws Exception Якщо не вдалося обробити
     */
    public static function format(string $json, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string {
        return self::encode(self::decode($json), $flags);
    }
    
    /**
     * Злиття JSON даних
     * 
     * @param array ...$jsonData Масиви JSON даних для злиття
     * @return array
     */
    public static function merge(array ...$jsonData): array {
        return array_merge_recursive(...$jsonData);
    }
    
    /**
     * Порівняння двох JSON структур
     * 
     * @param mixed $data1 Перші дані
     * @param mixed $data2 Другі дані
     * @param bool $strict Строге порівняння (враховувати порядок ключів)
     * @return bool
     */
    public static function equals(mixed $data1, mixed $data2, bool $strict = false): bool {
        if ($strict) {
            return $data1 === $data2;
        }
        
        try {
            return self::encode($data1, JSON_UNESCAPED_UNICODE) === self::encode($data2, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Отримання значення за шляхом з JSON даних
     * 
     * @param array|object|string $data JSON дані
     * @param string $path Шлях (крапкова нотація, наприклад "user.name")
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public static function getValue(array|object|string $data, string $path, mixed $default = null): mixed {
        if (is_string($data)) {
            try {
                $data = self::decode($data);
            } catch (Exception $e) {
                return $default;
            }
        }
        
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Встановлення значення за шляхом у JSON даних
     * 
     * @param array $data JSON дані (масив)
     * @param string $path Шлях (крапкова нотація)
     * @param mixed $value Значення
     * @return array
     */
    public static function setValue(array $data, string $path, mixed $value): array {
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $target = &$data;
        
        foreach ($keys as $key) {
            if (!isset($target[$key]) || !is_array($target[$key])) {
                $target[$key] = [];
            }
            $target = &$target[$key];
        }
        
        $target[$lastKey] = $value;
        
        return $data;
    }
}

