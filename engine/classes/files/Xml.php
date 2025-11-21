<?php
/**
 * Клас для роботи з XML файлами
 * Парсинг, створення та маніпуляції з XML документами
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
     * @param string|null $filePath Шлях до XML файлу
     * @param string $version Версія XML (за замовчуванням "1.0")
     * @param string $encoding Кодування (за замовчуванням "UTF-8")
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
     * Встановлення шляху до файлу
     * 
     * @param string $filePath Шлях до XML файлу
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
     * Завантаження XML файлу
     * 
     * @param int $options Прапорці для завантаження
     * @return self
     * @throws Exception Якщо файл не існує або не може бути прочитаний
     */
    public function load(int $options = 0): self {
        if (empty($this->filePath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("XML файл не існує: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("XML файл недоступний для читання: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не вдалося прочитати XML файл: {$this->filePath}");
        }
        
        if (!@$this->document->loadXML($content, $options)) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : "Невідома помилка парсингу XML";
            libxml_clear_errors();
            throw new Exception("Помилка парсингу XML файлу '{$this->filePath}': {$errorMsg}");
        }
        
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Завантаження XML з рядка
     * 
     * @param string $xmlString XML рядок
     * @param int $options Прапорці для завантаження
     * @return self
     * @throws Exception Якщо не вдалося завантажити
     */
    public function loadString(string $xmlString, int $options = 0): self {
        if (!@$this->document->loadXML($xmlString, $options)) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : "Невідома помилка парсингу XML";
            libxml_clear_errors();
            throw new Exception("Помилка парсингу XML рядка: {$errorMsg}");
        }
        
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Збереження XML у файл
     * 
     * @param string|null $filePath Шлях до файлу (якщо null, використовується поточний)
     * @return bool
     * @throws Exception Якщо не вдалося зберегти
     */
    public function save(?string $filePath = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        
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
        
        $xml = $this->document->saveXML();
        
        if ($xml === false) {
            throw new Exception("Не вдалося сформувати XML");
        }
        
        $result = @file_put_contents($targetPath, $xml, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не вдалося зберегти XML файл: {$targetPath}");
        }
        
        @chmod($targetPath, 0644);
        
        if ($filePath === null) {
            $this->filePath = $targetPath;
        }
        
        return true;
    }
    
    /**
     * Отримання XML у вигляді рядка
     * 
     * @return string
     */
    public function toString(): string {
        $xml = $this->document->saveXML();
        return $xml !== false ? $xml : '';
    }
    
    /**
     * Отримання DOMDocument
     * 
     * @return DOMDocument
     */
    public function getDocument(): DOMDocument {
        return $this->document;
    }
    
    /**
     * Отримання кореневого елемента
     * 
     * @return DOMElement|null
     */
    public function getRoot(): ?DOMElement {
        return $this->document->documentElement;
    }
    
    /**
     * Отримання значення елемента за XPath
     * 
     * @param string $xpath XPath вираз
     * @param mixed $default Значення за замовчуванням
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
     * Встановлення значення елемента за XPath
     * 
     * @param string $xpath XPath вираз
     * @param string $value Значення
     * @return self
     * @throws Exception Якщо елемент не знайдено
     */
    public function setValue(string $xpath, string $value): self {
        if (!$this->hasData) {
            $this->document->appendChild($this->document->createElement('root'));
            $this->hasData = true;
        }
        
        $xpathObj = new DOMXPath($this->document);
        $nodes = $xpathObj->query($xpath);
        
        if ($nodes === false || $nodes->length === 0) {
            throw new Exception("Елемент не знайдено за XPath: {$xpath}");
        }
        
        $nodes->item(0)->nodeValue = $value;
        
        return $this;
    }
    
    /**
     * Додавання елемента
     * 
     * @param string $tagName Ім'я тега
     * @param string $content Вміст
     * @param string|null $parentXPath XPath батьківського елемента (null = корінь)
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
                throw new Exception("Батьківський елемент не знайдено за XPath: {$parentXPath}");
            }
            
            $nodes->item(0)->appendChild($element);
        }
        
        return $element;
    }
    
    /**
     * Видалення елемента за XPath
     * 
     * @param string $xpath XPath вираз
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
     * Валідація XML за схемою
     * 
     * @param string $schemaPath Шлях до XSD схеми
     * @return bool
     */
    public function validate(string $schemaPath): bool {
        if (!$this->hasData) {
            return false;
        }
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Схема не існує: {$schemaPath}");
        }
        
        return @$this->document->schemaValidate($schemaPath);
    }
    
    /**
     * Статичний метод: Читання XML файлу
     * 
     * @param string $filePath Шлях до файлу
     * @param int $options Прапорці для завантаження
     * @return Xml
     */
    public static function read(string $filePath, int $options = 0): Xml {
        $xml = new self($filePath);
        $xml->load($options);
        return $xml;
    }
    
    /**
     * Статичний метод: Парсинг XML рядка
     * 
     * @param string $xmlString XML рядок
     * @param int $options Прапорці для завантаження
     * @return Xml
     */
    public static function parse(string $xmlString, int $options = 0): Xml {
        $xml = new self();
        $xml->loadString($xmlString, $options);
        return $xml;
    }
    
    /**
     * Статичний метод: Створення XML з масиву
     * 
     * @param array $data Дані
     * @param string $rootElement Ім'я кореневого елемента
     * @return Xml
     */
    public static function fromArray(array $data, string $rootElement = 'root'): Xml {
        $xml = new self();
        $xml->document->appendChild($xml->arrayToXml($data, $rootElement));
        $xml->hasData = true;
        return $xml;
    }
    
    /**
     * Перетворення масиву в XML елементи
     * 
     * @param array $data Дані
     * @param string $rootName Ім'я кореневого елемента
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

