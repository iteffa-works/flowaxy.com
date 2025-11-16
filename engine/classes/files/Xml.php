<?php
/**
 * Класс для работы с XML файлами
 * Парсинг, создание и манипуляции с XML документами
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 */

declare(strict_types=1);

class Xml {
    private string $filePath;
    private ?DOMDocument $document = null;
    private bool $hasData = false;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к XML файлу
     * @param string $version Версия XML (по умолчанию "1.0")
     * @param string $encoding Кодировка (по умолчанию "UTF-8")
     */
    public function __construct(?string $filePath = null, string $version = '1.0', string $encoding = 'UTF-8') {
        $this->document = new DOMDocument($version, $encoding);
        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = false;
        
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Установка пути к файлу
     * 
     * @param string $filePath Путь к XML файлу
     * @return self
     */
    public function setFile(string $filePath): self {
        $this->filePath = $filePath;
        $this->hasData = false;
        
        if (file_exists($filePath)) {
            $this->load();
        }
        
        return $this;
    }
    
    /**
     * Загрузка XML файла
     * 
     * @param int $options Флаги для загрузки
     * @return self
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public function load(int $options = 0): self {
        if (empty($this->filePath)) {
            throw new Exception("Путь к файлу не установлен");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("XML файл не существует: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("XML файл недоступен для чтения: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не удалось прочитать XML файл: {$this->filePath}");
        }
        
        if (!@$this->document->loadXML($content, $options)) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : "Неизвестная ошибка парсинга XML";
            libxml_clear_errors();
            throw new Exception("Ошибка парсинга XML файла '{$this->filePath}': {$errorMsg}");
        }
        
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Загрузка XML из строки
     * 
     * @param string $xmlString XML строка
     * @param int $options Флаги для загрузки
     * @return self
     * @throws Exception Если не удалось загрузить
     */
    public function loadString(string $xmlString, int $options = 0): self {
        if (!@$this->document->loadXML($xmlString, $options)) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : "Неизвестная ошибка парсинга XML";
            libxml_clear_errors();
            throw new Exception("Ошибка парсинга XML строки: {$errorMsg}");
        }
        
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Сохранение XML в файл
     * 
     * @param string|null $filePath Путь к файлу (если null, используется текущий)
     * @return bool
     * @throws Exception Если не удалось сохранить
     */
    public function save(?string $filePath = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        
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
        
        $xml = $this->document->saveXML();
        
        if ($xml === false) {
            throw new Exception("Не удалось сформировать XML");
        }
        
        $result = @file_put_contents($targetPath, $xml, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не удалось сохранить XML файл: {$targetPath}");
        }
        
        @chmod($targetPath, 0644);
        
        if ($filePath === null) {
            $this->filePath = $targetPath;
        }
        
        return true;
    }
    
    /**
     * Получение XML в виде строки
     * 
     * @return string
     */
    public function toString(): string {
        $xml = $this->document->saveXML();
        return $xml !== false ? $xml : '';
    }
    
    /**
     * Получение DOMDocument
     * 
     * @return DOMDocument
     */
    public function getDocument(): DOMDocument {
        return $this->document;
    }
    
    /**
     * Получение корневого элемента
     * 
     * @return DOMElement|null
     */
    public function getRoot(): ?DOMElement {
        return $this->document->documentElement;
    }
    
    /**
     * Получение значения элемента по XPath
     * 
     * @param string $xpath XPath выражение
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getValue(string $xpath, $default = null) {
        if (!$this->hasData) {
            return $default;
        }
        
        $xpathObj = new DOMXPath($this->document);
        $nodes = $xpathObj->query($xpath);
        
        if ($nodes === false || $nodes->length === 0) {
            return $default;
        }
        
        $node = $nodes->item(0);
        
        if ($node instanceof DOMElement) {
            return $node->nodeValue;
        }
        
        return $default;
    }
    
    /**
     * Установка значения элемента по XPath
     * 
     * @param string $xpath XPath выражение
     * @param string $value Значение
     * @return self
     * @throws Exception Если элемент не найден
     */
    public function setValue(string $xpath, string $value): self {
        if (!$this->hasData) {
            $this->document->appendChild($this->document->createElement('root'));
            $this->hasData = true;
        }
        
        $xpathObj = new DOMXPath($this->document);
        $nodes = $xpathObj->query($xpath);
        
        if ($nodes === false || $nodes->length === 0) {
            throw new Exception("Элемент не найден по XPath: {$xpath}");
        }
        
        $nodes->item(0)->nodeValue = $value;
        
        return $this;
    }
    
    /**
     * Добавление элемента
     * 
     * @param string $tagName Имя тега
     * @param string $content Содержимое
     * @param string|null $parentXPath XPath родительского элемента (null = корень)
     * @return DOMElement
     */
    public function addElement(string $tagName, string $content = '', ?string $parentXPath = null): DOMElement {
        $element = $this->document->createElement($tagName, $content);
        
        if ($parentXPath === null) {
            $root = $this->document->documentElement;
            if ($root === null) {
                $root = $this->document->createElement('root');
                $this->document->appendChild($root);
                $this->hasData = true;
            }
            $root->appendChild($element);
        } else {
            $xpathObj = new DOMXPath($this->document);
            $nodes = $xpathObj->query($parentXPath);
            
            if ($nodes === false || $nodes->length === 0) {
                throw new Exception("Родительский элемент не найден по XPath: {$parentXPath}");
            }
            
            $nodes->item(0)->appendChild($element);
        }
        
        return $element;
    }
    
    /**
     * Удаление элемента по XPath
     * 
     * @param string $xpath XPath выражение
     * @return bool
     */
    public function removeElement(string $xpath): bool {
        if (!$this->hasData) {
            return false;
        }
        
        $xpathObj = new DOMXPath($this->document);
        $nodes = $xpathObj->query($xpath);
        
        if ($nodes === false || $nodes->length === 0) {
            return false;
        }
        
        $node = $nodes->item(0);
        $parent = $node->parentNode;
        
        if ($parent !== null) {
            $parent->removeChild($node);
            return true;
        }
        
        return false;
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
     * Валидация XML по схеме
     * 
     * @param string $schemaPath Путь к XSD схеме
     * @return bool
     */
    public function validate(string $schemaPath): bool {
        if (!$this->hasData) {
            return false;
        }
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Схема не существует: {$schemaPath}");
        }
        
        return @$this->document->schemaValidate($schemaPath);
    }
    
    /**
     * Статический метод: Чтение XML файла
     * 
     * @param string $filePath Путь к файлу
     * @param int $options Флаги для загрузки
     * @return Xml
     */
    public static function read(string $filePath, int $options = 0): Xml {
        $xml = new self($filePath);
        $xml->load($options);
        return $xml;
    }
    
    /**
     * Статический метод: Парсинг XML строки
     * 
     * @param string $xmlString XML строка
     * @param int $options Флаги для загрузки
     * @return Xml
     */
    public static function parse(string $xmlString, int $options = 0): Xml {
        $xml = new self();
        $xml->loadString($xmlString, $options);
        return $xml;
    }
    
    /**
     * Статический метод: Создание XML из массива
     * 
     * @param array $data Данные
     * @param string $rootElement Имя корневого элемента
     * @return Xml
     */
    public static function fromArray(array $data, string $rootElement = 'root'): Xml {
        $xml = new self();
        $xml->document->appendChild($xml->arrayToXml($data, $rootElement));
        $xml->hasData = true;
        return $xml;
    }
    
    /**
     * Преобразование массива в XML элементы
     * 
     * @param array $data Данные
     * @param string $rootName Имя корневого элемента
     * @return DOMElement
     */
    private function arrayToXml(array $data, string $rootName): DOMElement {
        $root = $this->document->createElement($rootName);
        
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            
            if (is_array($value)) {
                $child = $this->arrayToXml($value, $key);
                $root->appendChild($child);
            } else {
                $element = $this->document->createElement($key, htmlspecialchars((string)$value, ENT_XML1, 'UTF-8'));
                $root->appendChild($element);
            }
        }
        
        return $root;
    }
}

