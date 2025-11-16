<?php
/**
 * Класс для работы с JSON файлами
 * Чтение, запись и валидация JSON данных
 * 
 * @package Engine\Classes
 * @version 1.0.0
 */

declare(strict_types=1);

class Json {
    private string $filePath;
    private $data = null;
    private bool $hasData = false;
    private int $encodeFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    private int $decodeFlags = JSON_BIGINT_AS_STRING;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к JSON файлу
     */
    public function __construct(?string $filePath = null) {
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Установка пути к файлу
     * 
     * @param string $filePath Путь к JSON файлу
     * @return self
     * @throws Exception Если файл существует, но недоступен для чтения
     */
    public function setFile(string $filePath): self {
        if (!is_readable($filePath) && file_exists($filePath)) {
            throw new Exception("JSON файл существует, но недоступен для чтения: {$filePath}");
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
     * Загрузка данных из JSON файла
     * 
     * @param bool $assoc Преобразовывать ли в ассоциативный массив
     * @param int $depth Максимальная глубина вложенности
     * @return self
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public function load(bool $assoc = true, int $depth = 512): self {
        if (empty($this->filePath)) {
            throw new Exception("Путь к файлу не установлен");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("JSON файл не существует: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("JSON файл недоступен для чтения: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не удалось прочитать JSON файл: {$this->filePath}");
        }
        
        // Проверяем, не пустой ли файл
        $content = trim($content);
        if ($content === '') {
            $this->data = $assoc ? [] : new stdClass();
            $this->hasData = true;
            return $this;
        }
        
        $data = json_decode($content, $assoc, $depth, $this->decodeFlags);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            throw new Exception("Ошибка парсинга JSON файла '{$this->filePath}': {$error}");
        }
        
        $this->data = $data;
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Сохранение данных в JSON файл
     * 
     * @param string|null $filePath Путь к файлу (если null, используется текущий)
     * @param mixed|null $data Данные для сохранения (если null, используются текущие)
     * @param int|null $encodeFlags Флаги кодирования (если null, используются текущие)
     * @return bool
     * @throws Exception Если не удалось сохранить файл
     */
    public function save(?string $filePath = null, $data = null, ?int $encodeFlags = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        $dataToSave = $data ?? $this->data;
        $flags = $encodeFlags ?? $this->encodeFlags;
        
        if (empty($targetPath)) {
            throw new Exception("Путь к файлу не установлен");
        }
        
        // Создаем директорию, если её нет
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$dir}");
            }
        }
        
        $json = json_encode($dataToSave, $flags);
        
        if ($json === false) {
            $error = json_last_error_msg();
            throw new Exception("Ошибка кодирования JSON: {$error}");
        }
        
        $result = @file_put_contents($targetPath, $json, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не удалось сохранить JSON файл: {$targetPath}");
        }
        
        @chmod($targetPath, 0644);
        
        // Обновляем текущие данные
        if ($filePath === null) {
            $this->data = $dataToSave;
            $this->hasData = true;
        }
        
        return true;
    }
    
    /**
     * Получение данных
     * 
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get($default = null) {
        if (!$this->hasData) {
            return $default;
        }
        
        return $this->data;
    }
    
    /**
     * Получение значения по пути (точечная нотация)
     * 
     * @param string $path Путь к значению (например, "user.name" или "users.0.email")
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getPath(string $path, $default = null) {
        if (!$this->hasData) {
            return $default;
        }
        
        $keys = explode('.', $path);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (is_array($value)) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return $default;
                }
            } elseif (is_object($value)) {
                if (isset($value->$key)) {
                    $value = $value->$key;
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Установка данных
     * 
     * @param mixed $data Данные
     * @return self
     */
    public function set($data): self {
        $this->data = $data;
        $this->hasData = true;
        return $this;
    }
    
    /**
     * Установка значения по пути (точечная нотация)
     * 
     * @param string $path Путь к значению
     * @param mixed $value Значение
     * @return self
     */
    public function setPath(string $path, $value): self {
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
                // Преобразуем в массив
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
     * Удаление значения по пути
     * 
     * @param string $path Путь к значению
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
            if (is_array($target)) {
                if (!isset($target[$key])) {
                    return $this;
                }
                $target = &$target[$key];
            } elseif (is_object($target)) {
                if (!isset($target->$key)) {
                    return $this;
                }
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
     * Проверка наличия значения по пути
     * 
     * @param string $path Путь к значению
     * @return bool
     */
    public function hasPath(string $path): bool {
        if (!$this->hasData) {
            return false;
        }
        
        $keys = explode('.', $path);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (is_array($value)) {
                if (!isset($value[$key])) {
                    return false;
                }
                $value = $value[$key];
            } elseif (is_object($value)) {
                if (!isset($value->$key)) {
                    return false;
                }
                $value = $value->$key;
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Очистка всех данных
     * 
     * @return self
     */
    public function clear(): self {
        $this->data = null;
        $this->hasData = false;
        return $this;
    }
    
    /**
     * Проверка, загружены ли данные
     * 
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->hasData;
    }
    
    /**
     * Получение пути к файлу
     * 
     * @return string
     */
    public function getFilePath(): string {
        return $this->filePath;
    }
    
    /**
     * Установка флагов кодирования
     * 
     * @param int $flags Флаги кодирования
     * @return self
     */
    public function setEncodeFlags(int $flags): self {
        $this->encodeFlags = $flags;
        return $this;
    }
    
    /**
     * Установка флагов декодирования
     * 
     * @param int $flags Флаги декодирования
     * @return self
     */
    public function setDecodeFlags(int $flags): self {
        $this->decodeFlags = $flags;
        return $this;
    }
    
    /**
     * Получение флагов кодирования
     * 
     * @return int
     */
    public function getEncodeFlags(): int {
        return $this->encodeFlags;
    }
    
    /**
     * Получение флагов декодирования
     * 
     * @return int
     */
    public function getDecodeFlags(): int {
        return $this->decodeFlags;
    }
    
    /**
     * Валидация JSON строки
     * 
     * @param string $json JSON строка
     * @param bool $assoc Преобразовывать ли в ассоциативный массив
     * @return array ['valid' => bool, 'data' => mixed|null, 'error' => string|null]
     */
    public static function validate(string $json, bool $assoc = true): array {
        $json = trim($json);
        
        if ($json === '') {
            return [
                'valid' => false,
                'data' => null,
                'error' => 'Пустая строка'
            ];
        }
        
        $data = json_decode($json, $assoc);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'data' => null,
                'error' => json_last_error_msg()
            ];
        }
        
        return [
            'valid' => true,
            'data' => $data,
            'error' => null
        ];
    }
    
    /**
     * Кодирование данных в JSON строку
     * 
     * @param mixed $data Данные
     * @param int $flags Флаги кодирования
     * @return string
     * @throws Exception Если не удалось закодировать
     */
    public static function encode($data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string {
        $json = json_encode($data, $flags);
        
        if ($json === false) {
            $error = json_last_error_msg();
            throw new Exception("Ошибка кодирования JSON: {$error}");
        }
        
        return $json;
    }
    
    /**
     * Декодирование JSON строки
     * 
     * @param string $json JSON строка
     * @param bool $assoc Преобразовывать ли в ассоциативный массив
     * @param int $depth Максимальная глубина вложенности
     * @return mixed
     * @throws Exception Если не удалось декодировать
     */
    public static function decode(string $json, bool $assoc = true, int $depth = 512) {
        $json = trim($json);
        
        if ($json === '') {
            return $assoc ? [] : new stdClass();
        }
        
        $data = json_decode($json, $assoc, $depth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            throw new Exception("Ошибка декодирования JSON: {$error}");
        }
        
        return $data;
    }
    
    /**
     * Чтение JSON файла (статический метод)
     * 
     * @param string $filePath Путь к файлу
     * @param bool $assoc Преобразовывать ли в ассоциативный массив
     * @return mixed
     */
    public static function read(string $filePath, bool $assoc = true) {
        $json = new self($filePath);
        $json->load($assoc);
        return $json->get();
    }
    
    /**
     * Запись JSON файла (статический метод)
     * 
     * @param string $filePath Путь к файлу
     * @param mixed $data Данные
     * @param int $flags Флаги кодирования
     * @return bool
     */
    public static function write(string $filePath, $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): bool {
        $json = new self();
        return $json->save($filePath, $data, $flags);
    }
    
    /**
     * Статический метод: Парсинг JSON строки
     * 
     * @param string $json JSON строка
     * @param bool $assoc Преобразовывать ли в ассоциативный массив
     * @param int $depth Максимальная глубина вложенности
     * @return mixed
     * @throws Exception Если не удалось декодировать
     */
    public static function parse(string $json, bool $assoc = true, int $depth = 512) {
        return self::decode($json, $assoc, $depth);
    }
    
    /**
     * Статический метод: Преобразование данных в JSON строку
     * 
     * @param mixed $data Данные
     * @param int $flags Флаги кодирования
     * @return string
     * @throws Exception Если не удалось закодировать
     */
    public static function stringify($data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string {
        return self::encode($data, $flags);
    }
    
    /**
     * Статический метод: Проверка, является ли строка валидным JSON
     * 
     * @param string $json JSON строка
     * @return bool
     */
    public static function isValid(string $json): bool {
        $result = self::validate($json);
        return $result['valid'];
    }
    
    /**
     * Статический метод: Минификация JSON (удаление пробелов и переносов строк)
     * 
     * @param string $json JSON строка
     * @return string
     * @throws Exception Если не удалось обработать
     */
    public static function minify(string $json): string {
        $data = self::decode($json);
        return self::encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Статический метод: Форматирование JSON (добавление отступов)
     * 
     * @param string $json JSON строка
     * @param int $flags Флаги кодирования
     * @return string
     * @throws Exception Если не удалось обработать
     */
    public static function format(string $json, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): string {
        $data = self::decode($json);
        return self::encode($data, $flags);
    }
    
    /**
     * Статический метод: Слияние JSON данных
     * 
     * @param array ...$jsonData Массивы JSON данных для слияния
     * @return array
     */
    public static function merge(array ...$jsonData): array {
        return array_merge_recursive(...$jsonData);
    }
    
    /**
     * Статический метод: Сравнение двух JSON структур
     * 
     * @param mixed $data1 Первые данные
     * @param mixed $data2 Вторые данные
     * @param bool $strict Строгое сравнение (учитывать порядок ключей)
     * @return bool
     */
    public static function equals($data1, $data2, bool $strict = false): bool {
        if ($strict) {
            return $data1 === $data2;
        }
        
        // Нормализуем данные через JSON для сравнения
        try {
            $json1 = self::encode($data1, JSON_UNESCAPED_UNICODE);
            $json2 = self::encode($data2, JSON_UNESCAPED_UNICODE);
            return $json1 === $json2;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Статический метод: Получение значения по пути из JSON данных
     * 
     * @param array|object $data JSON данные
     * @param string $path Путь (точечная нотация, например "user.name")
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public static function getValue($data, string $path, $default = null) {
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
            if (is_array($value)) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return $default;
                }
            } elseif (is_object($value)) {
                if (isset($value->$key)) {
                    $value = $value->$key;
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Статический метод: Установка значения по пути в JSON данных
     * 
     * @param array $data JSON данные (массив)
     * @param string $path Путь (точечная нотация)
     * @param mixed $value Значение
     * @return array
     */
    public static function setValue(array $data, string $path, $value): array {
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

