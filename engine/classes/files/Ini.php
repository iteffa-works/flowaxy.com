<?php
/**
 * Класс для работы с INI файлами
 * Чтение, запись и манипуляции с INI конфигурационными файлами
 * 
 * @package Engine\Classes
 * @version 1.0.0
 */

declare(strict_types=1);

class Ini {
    private string $filePath;
    private array $data = [];
    private bool $hasData = false;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к INI файлу
     */
    public function __construct(?string $filePath = null) {
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Установка пути к файлу
     * 
     * @param string $filePath Путь к INI файлу
     * @return self
     * @throws Exception Если файл не существует или недоступен
     */
    public function setFile(string $filePath): self {
        if (!is_readable($filePath) && file_exists($filePath)) {
            throw new Exception("INI файл существует, но недоступен для чтения: {$filePath}");
        }
        
        $this->filePath = $filePath;
        $this->hasData = false;
        
        if (file_exists($filePath)) {
            $this->load();
        }
        
        return $this;
    }
    
    /**
     * Загрузка данных из INI файла
     * 
     * @param int $mode Режим парсинга (INIScanner::NORMAL или INIScanner::RAW)
     * @return self
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public function load(int $mode = INI_SCANNER_NORMAL): self {
        if (empty($this->filePath)) {
            throw new Exception("Путь к файлу не установлен");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("INI файл не существует: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("INI файл недоступен для чтения: {$this->filePath}");
        }
        
        $data = @parse_ini_file($this->filePath, true, $mode);
        
        if ($data === false) {
            throw new Exception("Ошибка парсинга INI файла: {$this->filePath}");
        }
        
        $this->data = $data;
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Сохранение данных в INI файл
     * 
     * @param string|null $filePath Путь к файлу (если null, используется текущий)
     * @param array|null $data Данные для сохранения (если null, используются текущие)
     * @return bool
     * @throws Exception Если не удалось сохранить файл
     */
    public function save(?string $filePath = null, ?array $data = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        $dataToSave = $data ?? $this->data;
        
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
        
        $content = $this->arrayToIni($dataToSave);
        
        $result = @file_put_contents($targetPath, $content, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не удалось сохранить INI файл: {$targetPath}");
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
     * Получение значения из INI
     * 
     * @param string $key Ключ (может быть в формате "section.key" или просто "key")
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null) {
        if (!$this->hasData) {
            return $default;
        }
        
        // Проверяем, есть ли секция в ключе
        if (strpos($key, '.') !== false) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (isset($this->data[$section]) && is_array($this->data[$section])) {
                return $this->data[$section][$sectionKey] ?? $default;
            }
        }
        
        // Если это простая секция
        if (isset($this->data[$key])) {
            if (is_array($this->data[$key])) {
                return $this->data[$key];
            }
            return $this->data[$key];
        }
        
        // Ищем в корне
        foreach ($this->data as $sectionKey => $sectionValue) {
            if (is_array($sectionValue) && isset($sectionValue[$key])) {
                return $sectionValue[$key];
            }
        }
        
        return $default;
    }
    
    /**
     * Установка значения в INI
     * 
     * @param string $key Ключ (может быть в формате "section.key")
     * @param mixed $value Значение
     * @return self
     */
    public function set(string $key, $value): self {
        if (!$this->hasData) {
            $this->data = [];
            $this->hasData = true;
        }
        
        // Проверяем, есть ли секция в ключе
        if (strpos($key, '.') !== false) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (!isset($this->data[$section]) || !is_array($this->data[$section])) {
                $this->data[$section] = [];
            }
            
            $this->data[$section][$sectionKey] = $value;
        } else {
            // Устанавливаем в корень
            $this->data[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Проверка наличия ключа
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool {
        if (!$this->hasData) {
            return false;
        }
        
        if (strpos($key, '.') !== false) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (isset($this->data[$section]) && is_array($this->data[$section])) {
                return isset($this->data[$section][$sectionKey]);
            }
        }
        
        return isset($this->data[$key]);
    }
    
    /**
     * Удаление ключа
     * 
     * @param string $key Ключ
     * @return self
     */
    public function remove(string $key): self {
        if (!$this->hasData) {
            return $this;
        }
        
        if (strpos($key, '.') !== false) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (isset($this->data[$section]) && is_array($this->data[$section])) {
                unset($this->data[$section][$sectionKey]);
                
                // Удаляем секцию, если она пуста
                if (empty($this->data[$section])) {
                    unset($this->data[$section]);
                }
            }
        } else {
            unset($this->data[$key]);
        }
        
        return $this;
    }
    
    /**
     * Получение всех данных
     * 
     * @return array
     */
    public function all(): array {
        return $this->data;
    }
    
    /**
     * Очистка всех данных
     * 
     * @return self
     */
    public function clear(): self {
        $this->data = [];
        $this->hasData = false;
        return $this;
    }
    
    /**
     * Получение секции
     * 
     * @param string $section Имя секции
     * @param array $default Значение по умолчанию
     * @return array
     */
    public function getSection(string $section, array $default = []): array {
        if (!$this->hasData) {
            return $default;
        }
        
        if (isset($this->data[$section]) && is_array($this->data[$section])) {
            return $this->data[$section];
        }
        
        return $default;
    }
    
    /**
     * Установка секции
     * 
     * @param string $section Имя секции
     * @param array $data Данные секции
     * @return self
     */
    public function setSection(string $section, array $data): self {
        if (!$this->hasData) {
            $this->data = [];
            $this->hasData = true;
        }
        
        $this->data[$section] = $data;
        
        return $this;
    }
    
    /**
     * Удаление секции
     * 
     * @param string $section Имя секции
     * @return self
     */
    public function removeSection(string $section): self {
        if (!$this->hasData) {
            return $this;
        }
        
        unset($this->data[$section]);
        
        return $this;
    }
    
    /**
     * Проверка существования секции
     * 
     * @param string $section Имя секции
     * @return bool
     */
    public function hasSection(string $section): bool {
        if (!$this->hasData) {
            return false;
        }
        
        return isset($this->data[$section]) && is_array($this->data[$section]);
    }
    
    /**
     * Получение списка всех секций
     * 
     * @return array
     */
    public function getSections(): array {
        if (!$this->hasData) {
            return [];
        }
        
        $sections = [];
        
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $sections[] = $key;
            }
        }
        
        return $sections;
    }
    
    /**
     * Преобразование массива в INI формат
     * 
     * @param array $data Данные
     * @param string $prefix Префикс для ключей (внутреннее использование)
     * @return string
     */
    private function arrayToIni(array $data, string $prefix = ''): string {
        $content = '';
        
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                // Проверяем, является ли это секцией (все значения - скаляры)
                $isSection = true;
                foreach ($value as $v) {
                    if (is_array($v) || is_object($v) || is_resource($v)) {
                        $isSection = false;
                        break;
                    }
                }
                
                if ($isSection) {
                    // Это секция INI
                    $content .= "\n[{$key}]\n";
                    foreach ($value as $k => $v) {
                        $content .= $this->formatIniLine($k, $v);
                    }
                } else {
                    // Вложенный массив - рекурсивно обрабатываем
                    $content .= $this->arrayToIni($value, $fullKey);
                }
            } else {
                // Скалярное значение
                if ($prefix === '') {
                    // Это корневое значение
                    $content .= $this->formatIniLine($key, $value);
                } else {
                    // Это вложенное значение - используем точечную нотацию
                    $content .= $this->formatIniLine($fullKey, $value);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Форматирование строки INI
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return string
     */
    private function formatIniLine(string $key, $value): string {
        $key = $this->escapeKey($key);
        
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = '';
        } else {
            $value = $this->escapeValue((string)$value);
        }
        
        return "{$key} = {$value}\n";
    }
    
    /**
     * Экранирование ключа INI
     * 
     * @param string $key Ключ
     * @return string
     */
    private function escapeKey(string $key): string {
        // Экранируем специальные символы в ключах
        $key = str_replace(['=', ';', '"'], ['\=', '\;', '\"'], $key);
        
        // Если ключ содержит пробелы или специальные символы, заключаем в кавычки
        if (preg_match('/[\s\[\]]/', $key)) {
            $key = '"' . str_replace('"', '\"', $key) . '"';
        }
        
        return $key;
    }
    
    /**
     * Экранирование значения INI
     * 
     * @param string $value Значение
     * @return string
     */
    private function escapeValue(string $value): string {
        // Если значение содержит специальные символы, кавычки или начинается с пробела, заключаем в кавычки
        if (preg_match('/[;\s"\[\]]/', $value) || $value === '' || (isset($value[0]) && $value[0] === ' ')) {
            $value = '"' . str_replace('"', '\"', $value) . '"';
        }
        
        return $value;
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
     * Проверка, загружены ли данные
     * 
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->hasData;
    }
    
    /**
     * Статический метод: Парсинг INI файла
     * 
     * @param string $path Путь к INI файлу
     * @param bool $processSections Обрабатывать ли секции
     * @param int $mode Режим парсинга
     * @return array
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public static function parse(string $path, bool $processSections = true, int $mode = INI_SCANNER_NORMAL): array {
        if (!file_exists($path)) {
            throw new Exception("INI файл не существует: {$path}");
        }
        
        if (!is_readable($path)) {
            throw new Exception("INI файл недоступен для чтения: {$path}");
        }
        
        $data = @parse_ini_file($path, $processSections, $mode);
        
        if ($data === false) {
            throw new Exception("Ошибка парсинга INI файла: {$path}");
        }
        
        return $data;
    }
    
    /**
     * Статический метод: Обновление значения в INI файле
     * 
     * @param string $path Путь к INI файлу
     * @param string $key Ключ для обновления
     * @param mixed $value Новое значение
     * @param bool $processSections Обрабатывать ли секции
     * @return bool
     * @throws Exception Если файл не существует или не удалось обновить
     */
    public static function upgrade(string $path, string $key, $value, bool $processSections = true): bool {
        try {
            $ini = new self($path);
            if ($processSections) {
                $ini->load(INI_SCANNER_NORMAL);
            } else {
                $data = self::parse($path, false);
                $ini->clear();
                foreach ($data as $k => $v) {
                    $ini->set($k, $v);
                }
            }
            
            $ini->set($key, $value);
            return $ini->save();
        } catch (Exception $e) {
            error_log("Ini::upgrade error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Добавление/установка значения в INI файле
     * 
     * @param string $path Путь к INI файлу
     * @param string $key Ключ
     * @param mixed $value Значение
     * @param bool $processSections Обрабатывать ли секции
     * @return bool
     * @throws Exception Если не удалось добавить
     */
    public static function add(string $path, string $key, $value, bool $processSections = true): bool {
        return self::upgrade($path, $key, $value, $processSections);
    }
    
    /**
     * Статический метод: Удаление ключа из INI файла
     * 
     * @param string $path Путь к INI файлу
     * @param string $key Ключ для удаления
     * @param bool $processSections Обрабатывать ли секции
     * @return bool
     * @throws Exception Если файл не существует или не удалось удалить
     */
    public static function delete(string $path, string $key, bool $processSections = true): bool {
        if (!file_exists($path)) {
            return false;
        }
        
        try {
            $ini = new self($path);
            if ($processSections) {
                $ini->load(INI_SCANNER_NORMAL);
            } else {
                $data = self::parse($path, false);
                $ini->clear();
                foreach ($data as $k => $v) {
                    $ini->set($k, $v);
                }
            }
            
            $ini->remove($key);
            return $ini->save();
        } catch (Exception $e) {
            error_log("Ini::delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Чтение INI файла
     * 
     * @param string $path Путь к INI файлу
     * @param bool $processSections Обрабатывать ли секции
     * @param int $mode Режим парсинга
     * @return array
     */
    public static function read(string $path, bool $processSections = true, int $mode = INI_SCANNER_NORMAL): array {
        return self::parse($path, $processSections, $mode);
    }
    
    /**
     * Статический метод: Запись данных в INI файл
     * 
     * @param string $path Путь к INI файлу
     * @param array $data Данные для записи
     * @return bool
     */
    public static function write(string $path, array $data): bool {
        try {
            $ini = new self($path);
            // Используем методы для установки данных
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // Это секция
                    $ini->setSection($key, $value);
                } else {
                    // Это простое значение
                    $ini->set($key, $value);
                }
            }
            return $ini->save();
        } catch (Exception $e) {
            error_log("Ini::write error: " . $e->getMessage());
            return false;
        }
    }
}

