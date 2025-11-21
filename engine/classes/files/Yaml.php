<?php
/**
 * Клас для роботи з YAML файлами
 * Читання, запис та маніпуляції з YAML даними
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 * 
 * Примітка: Потрібне розширення yaml або symfony/yaml компонент
 */

declare(strict_types=1);

class Yaml {
    private string $filePath;
    private mixed $data = null;
    private bool $hasData = false;
    private int $inline = 2; // Рівень вкладеності для інлайн запису
    private int $indent = 2; // Розмір відступу
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Шлях до YAML файлу
     */
    public function __construct(?string $filePath = null) {
        $this->checkExtension();
        
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Перевірка наявності розширення YAML
     * 
     * @return void
     * @throws Exception Якщо розширення не встановлено
     */
    private function checkExtension(): void {
        if (!extension_loaded('yaml') && !function_exists('yaml_parse')) {
            // Намагаємося використати symfony/yaml якщо доступний через composer
            if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
                throw new Exception("YAML розширення не встановлено. Встановіть php-yaml або symfony/yaml через composer");
            }
        }
    }
    
    /**
     * Встановлення шляху до файлу
     * 
     * @param string $filePath Шлях до YAML файлу
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
     * Завантаження YAML файлу
     * 
     * @param int $pos Позиція початку парсингу (для розширення yaml)
     * @param int $ndocs Кількість документів (для розширення yaml)
     * @param array $callbacks Callbacks для парсингу (для розширення yaml)
     * @return self
     * @throws Exception Якщо файл не існує або не може бути прочитаний
     */
    public function load(int $pos = 0, int &$ndocs = 0, array $callbacks = []): self {
        if (empty($this->filePath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("YAML файл не існує: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("YAML файл недоступний для читання: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не вдалося прочитати YAML файл: {$this->filePath}");
        }
        
        $this->data = $this->parse($content, $pos, $ndocs, $callbacks);
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Завантаження YAML з рядка
     * 
     * @param string $yamlString YAML рядок
     * @param int $pos Позиція початку парсингу
     * @param int $ndocs Кількість документів
     * @param array $callbacks Callbacks
     * @return self
     * @throws Exception Якщо не вдалося завантажити
     */
    public function loadString(string $yamlString, int $pos = 0, int &$ndocs = 0, array $callbacks = []): self {
        $this->data = $this->parse($yamlString, $pos, $ndocs, $callbacks);
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Парсинг YAML рядка
     * 
     * @param string $yamlString YAML рядок
     * @param int $pos Позиція початку парсингу
     * @param int $ndocs Кількість документів
     * @param array $callbacks Callbacks
     * @return mixed
     * @throws Exception Якщо не вдалося розпарсити
     */
    private function parse(string $yamlString, int $pos = 0, int &$ndocs = 0, array $callbacks = []) {
        // Перевіряємо наявність вбудованого розширення yaml
        if (extension_loaded('yaml') && function_exists('yaml_parse')) {
            // Використовуємо вбудоване розширення через змінну для уникнення помилок статичного аналізу
            $yamlParseFunc = 'yaml_parse';
            $data = @$yamlParseFunc($yamlString, $pos, $ndocs, $callbacks);
            
            if ($data === false) {
                throw new Exception("Помилка парсингу YAML");
            }
            
            return $data;
        }
        
        // Перевіряємо наявність Symfony YAML компонента
        $symfonyYamlClass = '\Symfony\Component\Yaml\Yaml';
        if (class_exists($symfonyYamlClass)) {
            // Використовуємо Symfony YAML компонент
            try {
                return $symfonyYamlClass::parse($yamlString);
            } catch (Exception $e) {
                throw new Exception("Помилка парсингу YAML: " . $e->getMessage());
            }
        }
        
        throw new Exception("YAML розширення не доступне");
    }
    
    /**
     * Збереження даних у YAML файл
     * 
     * @param string|null $filePath Шлях до файлу (якщо null, використовується поточний)
     * @param mixed $data Дані для збереження (якщо null, використовуються поточні)
     * @return bool
     * @throws Exception Якщо не вдалося зберегти
     */
    public function save(?string $filePath = null, $data = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        $dataToSave = $data ?? $this->data;
        
        if (empty($targetPath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        // Створюємо директорію, якщо її немає
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не вдалося створити директорію: {$dir}");
            }
        }
        
        $yaml = $this->dump($dataToSave);
        
        $result = @file_put_contents($targetPath, $yaml, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не вдалося зберегти YAML файл: {$targetPath}");
        }
        
        @chmod($targetPath, 0644);
        
        // Оновлюємо поточні дані
        if ($filePath === null) {
            $this->data = $dataToSave;
            $this->hasData = true;
        }
        
        return true;
    }
    
    /**
     * Перетворення даних у YAML рядок
     * 
     * @param mixed $data Дані
     * @return string
     * @throws Exception Якщо не вдалося перетворити
     */
    private function dump($data): string {
        // Перевіряємо наявність вбудованого розширення yaml
        if (extension_loaded('yaml') && function_exists('yaml_emit')) {
            // Використовуємо вбудоване розширення через змінну для уникнення помилок статичного аналізу
            $yamlEmitFunc = 'yaml_emit';
            // Перевіряємо наявність константи YAML_UTF8_ENCODING
            $encoding = 0;
            if (defined('YAML_UTF8_ENCODING')) {
                $encoding = constant('YAML_UTF8_ENCODING');
            }
            $yaml = @$yamlEmitFunc($data, $encoding);
            
            if ($yaml === false) {
                throw new Exception("Помилка перетворення даних у YAML");
            }
            
            return $yaml;
        }
        
        // Перевіряємо наявність Symfony YAML компонента
        $symfonyYamlClass = '\Symfony\Component\Yaml\Yaml';
        if (class_exists($symfonyYamlClass)) {
            // Використовуємо Symfony YAML компонент
            try {
                return $symfonyYamlClass::dump($data, $this->inline, $this->indent);
            } catch (Exception $e) {
                throw new Exception("Помилка перетворення даних у YAML: " . $e->getMessage());
            }
        }
        
        throw new Exception("YAML розширення не доступне");
    }
    
    /**
     * Отримання даних
     * 
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function get($default = null) {
        if (!$this->hasData) {
            return $default;
        }
        
        return $this->data;
    }
    
    /**
     * Встановлення даних
     * 
     * @param mixed $data Дані
     * @return self
     */
    public function set($data): self {
        $this->data = $data;
        $this->hasData = true;
        return $this;
    }
    
    /**
     * Отримання значення за шляхом (крапкова нотація)
     * 
     * @param string $path Шлях до значення
     * @param mixed $default Значення за замовчуванням
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
     * Очищення даних
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
     * Встановлення рівня вкладеності для інлайн запису
     * 
     * @param int $inline Рівень вкладеності
     * @return self
     */
    public function setInline(int $inline): self {
        $this->inline = $inline;
        return $this;
    }
    
    /**
     * Встановлення розміру відступу
     * 
     * @param int $indent Розмір відступу
     * @return self
     */
    public function setIndent(int $indent): self {
        $this->indent = $indent;
        return $this;
    }
    
    /**
     * Статичний метод: Читання YAML файлу
     * 
     * @param string $filePath Шлях до файлу
     * @return mixed
     */
    public static function read(string $filePath) {
        $yaml = new self($filePath);
        $yaml->load();
        return $yaml->get();
    }
    
    /**
     * Статичний метод: Запис YAML файлу
     * 
     * @param string $filePath Шлях до файлу
     * @param mixed $data Дані
     * @return bool
     */
    public static function write(string $filePath, $data): bool {
        $yaml = new self();
        $yaml->set($data);
        return $yaml->save($filePath);
    }
    
    /**
     * Статичний метод: Парсинг YAML рядка
     * 
     * @param string $yamlString YAML рядок
     * @return mixed
     */
    public static function parseString(string $yamlString) {
        $yaml = new self();
        return $yaml->loadString($yamlString)->get();
    }
    
    /**
     * Статичний метод: Перетворення даних у YAML рядок
     * 
     * @param mixed $data Дані
     * @return string
     */
    public static function dumpData($data): string {
        $yaml = new self();
        return $yaml->dump($data);
    }
}

