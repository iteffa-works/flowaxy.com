<?php
/**
 * Класс для работы с YAML файлами
 * Чтение, запись и манипуляции с YAML данными
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 * 
 * Примечание: Требуется расширение yaml или symfony/yaml компонент
 */

declare(strict_types=1);

class Yaml {
    private string $filePath;
    private $data = null;
    private bool $hasData = false;
    private int $inline = 2; // Уровень вложенности для инлайн записи
    private int $indent = 2; // Размер отступа
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к YAML файлу
     */
    public function __construct(?string $filePath = null) {
        $this->checkExtension();
        
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Проверка наличия расширения YAML
     * 
     * @return void
     * @throws Exception Если расширение не установлено
     */
    private function checkExtension(): void {
        if (!extension_loaded('yaml') && !function_exists('yaml_parse')) {
            // Пробуем использовать symfony/yaml если доступен через composer
            if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
                throw new Exception("YAML расширение не установлено. Установите php-yaml или symfony/yaml через composer");
            }
        }
    }
    
    /**
     * Установка пути к файлу
     * 
     * @param string $filePath Путь к YAML файлу
     * @return self
     */
    public function setFile(string $filePath): self {
        $this->filePath = $filePath;
        $this->hasData = false;
        $this->data = null;
        
        if (file_exists($filePath)) {
            $this->load();
        }
        
        return $this;
    }
    
    /**
     * Загрузка YAML файла
     * 
     * @param int $pos Позиция начала парсинга (для расширения yaml)
     * @param int $ndocs Количество документов (для расширения yaml)
     * @param array $callbacks Callbacks для парсинга (для расширения yaml)
     * @return self
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public function load(int $pos = 0, int &$ndocs = 0, array $callbacks = []): self {
        if (empty($this->filePath)) {
            throw new Exception("Путь к файлу не установлен");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("YAML файл не существует: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("YAML файл недоступен для чтения: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не удалось прочитать YAML файл: {$this->filePath}");
        }
        
        $this->data = $this->parse($content, $pos, $ndocs, $callbacks);
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Загрузка YAML из строки
     * 
     * @param string $yamlString YAML строка
     * @param int $pos Позиция начала парсинга
     * @param int $ndocs Количество документов
     * @param array $callbacks Callbacks
     * @return self
     * @throws Exception Если не удалось загрузить
     */
    public function loadString(string $yamlString, int $pos = 0, int &$ndocs = 0, array $callbacks = []): self {
        $this->data = $this->parse($yamlString, $pos, $ndocs, $callbacks);
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Парсинг YAML строки
     * 
     * @param string $yamlString YAML строка
     * @param int $pos Позиция начала парсинга
     * @param int $ndocs Количество документов
     * @param array $callbacks Callbacks
     * @return mixed
     * @throws Exception Если не удалось распарсить
     */
    private function parse(string $yamlString, int $pos = 0, int &$ndocs = 0, array $callbacks = []) {
        if (extension_loaded('yaml') && function_exists('yaml_parse')) {
            // Используем встроенное расширение
            $data = @yaml_parse($yamlString, $pos, $ndocs, $callbacks);
            
            if ($data === false) {
                throw new Exception("Ошибка парсинга YAML");
            }
            
            return $data;
        } elseif (class_exists('\Symfony\Component\Yaml\Yaml')) {
            // Используем Symfony YAML компонент
            try {
                return \Symfony\Component\Yaml\Yaml::parse($yamlString);
            } catch (Exception $e) {
                throw new Exception("Ошибка парсинга YAML: " . $e->getMessage());
            }
        }
        
        throw new Exception("YAML расширение не доступно");
    }
    
    /**
     * Сохранение данных в YAML файл
     * 
     * @param string|null $filePath Путь к файлу (если null, используется текущий)
     * @param mixed $data Данные для сохранения (если null, используются текущие)
     * @return bool
     * @throws Exception Если не удалось сохранить
     */
    public function save(?string $filePath = null, $data = null): bool {
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
        
        $yaml = $this->dump($dataToSave);
        
        $result = @file_put_contents($targetPath, $yaml, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не удалось сохранить YAML файл: {$targetPath}");
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
     * Преобразование данных в YAML строку
     * 
     * @param mixed $data Данные
     * @return string
     * @throws Exception Если не удалось преобразовать
     */
    private function dump($data): string {
        if (extension_loaded('yaml') && function_exists('yaml_emit')) {
            // Используем встроенное расширение
            $yaml = @yaml_emit($data, YAML_UTF8_ENCODING);
            
            if ($yaml === false) {
                throw new Exception("Ошибка преобразования данных в YAML");
            }
            
            return $yaml;
        } elseif (class_exists('\Symfony\Component\Yaml\Yaml')) {
            // Используем Symfony YAML компонент
            try {
                return \Symfony\Component\Yaml\Yaml::dump($data, $this->inline, $this->indent);
            } catch (Exception $e) {
                throw new Exception("Ошибка преобразования данных в YAML: " . $e->getMessage());
            }
        }
        
        throw new Exception("YAML расширение не доступно");
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
     * Получение значения по пути (точечная нотация)
     * 
     * @param string $path Путь к значению
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getPath(string $path, $default = null) {
        if (!$this->hasData || !is_array($this->data)) {
            return $default;
        }
        
        $keys = explode('.', $path);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Очистка данных
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
     * Установка уровня вложенности для инлайн записи
     * 
     * @param int $inline Уровень вложенности
     * @return self
     */
    public function setInline(int $inline): self {
        $this->inline = $inline;
        return $this;
    }
    
    /**
     * Установка размера отступа
     * 
     * @param int $indent Размер отступа
     * @return self
     */
    public function setIndent(int $indent): self {
        $this->indent = $indent;
        return $this;
    }
    
    /**
     * Статический метод: Чтение YAML файла
     * 
     * @param string $filePath Путь к файлу
     * @return mixed
     */
    public static function read(string $filePath) {
        $yaml = new self($filePath);
        $yaml->load();
        return $yaml->get();
    }
    
    /**
     * Статический метод: Запись YAML файла
     * 
     * @param string $filePath Путь к файлу
     * @param mixed $data Данные
     * @return bool
     */
    public static function write(string $filePath, $data): bool {
        $yaml = new self();
        $yaml->set($data);
        return $yaml->save($filePath);
    }
    
    /**
     * Статический метод: Парсинг YAML строки
     * 
     * @param string $yamlString YAML строка
     * @return mixed
     */
    public static function parse(string $yamlString) {
        $yaml = new self();
        return $yaml->loadString($yamlString)->get();
    }
    
    /**
     * Статический метод: Преобразование данных в YAML строку
     * 
     * @param mixed $data Данные
     * @return string
     */
    public static function dump($data): string {
        $yaml = new self();
        return $yaml->dump($data);
    }
}

