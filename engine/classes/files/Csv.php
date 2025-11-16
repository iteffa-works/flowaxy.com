<?php
/**
 * Класс для работы с CSV файлами
 * Чтение, запись и манипуляции с CSV данными
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 */

declare(strict_types=1);

class Csv {
    private string $filePath;
    private array $data = [];
    private bool $hasData = false;
    private string $delimiter = ',';
    private string $enclosure = '"';
    private string $escape = '\\';
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к CSV файлу
     * @param string $delimiter Разделитель полей (по умолчанию ',')
     * @param string $enclosure Символ обрамления полей (по умолчанию '"')
     * @param string $escape Символ экранирования (по умолчанию '\\')
     */
    public function __construct(?string $filePath = null, string $delimiter = ',', string $enclosure = '"', string $escape = '\\') {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Установка пути к файлу
     * 
     * @param string $filePath Путь к CSV файлу
     * @return self
     */
    public function setFile(string $filePath): self {
        $this->filePath = $filePath;
        $this->hasData = false;
        $this->data = [];
        
        if (file_exists($filePath)) {
            $this->load();
        }
        
        return $this;
    }
    
    /**
     * Загрузка CSV файла
     * 
     * @param bool $hasHeader Имеет ли файл заголовки в первой строке
     * @return self
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public function load(bool $hasHeader = true): self {
        if (empty($this->filePath)) {
            throw new Exception("Путь к файлу не установлен");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("CSV файл не существует: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("CSV файл недоступен для чтения: {$this->filePath}");
        }
        
        $this->data = [];
        $header = null;
        
        if (($handle = @fopen($this->filePath, 'r')) === false) {
            throw new Exception("Не удалось открыть CSV файл: {$this->filePath}");
        }
        
        $lineNumber = 0;
        
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            if ($lineNumber === 0 && $hasHeader) {
                $header = $row;
            } else {
                if ($hasHeader && $header !== null) {
                    // Используем заголовки как ключи
                    $rowData = [];
                    foreach ($header as $index => $key) {
                        $rowData[$key] = $row[$index] ?? '';
                    }
                    $this->data[] = $rowData;
                } else {
                    $this->data[] = $row;
                }
            }
            $lineNumber++;
        }
        
        fclose($handle);
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Сохранение данных в CSV файл
     * 
     * @param string|null $filePath Путь к файлу (если null, используется текущий)
     * @param bool $writeHeader Записывать ли заголовки
     * @return bool
     * @throws Exception Если не удалось сохранить
     */
    public function save(?string $filePath = null, bool $writeHeader = true): bool {
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
        
        if (($handle = @fopen($targetPath, 'w')) === false) {
            throw new Exception("Не удалось открыть CSV файл для записи: {$targetPath}");
        }
        
        if (!empty($this->data)) {
            // Определяем заголовки
            $headers = null;
            if ($writeHeader && is_array($this->data[0]) && !is_numeric(key($this->data[0]))) {
                $headers = array_keys($this->data[0]);
                fputcsv($handle, $headers, $this->delimiter, $this->enclosure, $this->escape);
            }
            
            // Записываем данные
            foreach ($this->data as $row) {
                if ($headers !== null) {
                    // Используем порядок заголовков
                    $orderedRow = [];
                    foreach ($headers as $key) {
                        $orderedRow[] = $row[$key] ?? '';
                    }
                    fputcsv($handle, $orderedRow, $this->delimiter, $this->enclosure, $this->escape);
                } else {
                    fputcsv($handle, $row, $this->delimiter, $this->enclosure, $this->escape);
                }
            }
        }
        
        fclose($handle);
        @chmod($targetPath, 0644);
        
        if ($filePath === null) {
            $this->filePath = $targetPath;
        }
        
        return true;
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
     * Получение строки по индексу
     * 
     * @param int $index Индекс строки
     * @param array $default Значение по умолчанию
     * @return array
     */
    public function getRow(int $index, array $default = []): array {
        return $this->data[$index] ?? $default;
    }
    
    /**
     * Добавление строки
     * 
     * @param array $row Данные строки
     * @return self
     */
    public function addRow(array $row): self {
        $this->data[] = $row;
        $this->hasData = true;
        return $this;
    }
    
    /**
     * Обновление строки
     * 
     * @param int $index Индекс строки
     * @param array $row Новые данные
     * @return self
     */
    public function updateRow(int $index, array $row): self {
        if (isset($this->data[$index])) {
            $this->data[$index] = $row;
        }
        return $this;
    }
    
    /**
     * Удаление строки
     * 
     * @param int $index Индекс строки
     * @return self
     */
    public function removeRow(int $index): self {
        if (isset($this->data[$index])) {
            unset($this->data[$index]);
            $this->data = array_values($this->data); // Переиндексация
        }
        return $this;
    }
    
    /**
     * Получение количества строк
     * 
     * @return int
     */
    public function count(): int {
        return count($this->data);
    }
    
    /**
     * Очистка данных
     * 
     * @return self
     */
    public function clear(): self {
        $this->data = [];
        $this->hasData = false;
        return $this;
    }
    
    /**
     * Установка данных
     * 
     * @param array $data Данные
     * @return self
     */
    public function setData(array $data): self {
        $this->data = $data;
        $this->hasData = true;
        return $this;
    }
    
    /**
     * Установка разделителя
     * 
     * @param string $delimiter Разделитель
     * @return self
     */
    public function setDelimiter(string $delimiter): self {
        $this->delimiter = $delimiter;
        return $this;
    }
    
    /**
     * Установка символа обрамления
     * 
     * @param string $enclosure Символ обрамления
     * @return self
     */
    public function setEnclosure(string $enclosure): self {
        $this->enclosure = $enclosure;
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
     * Статический метод: Чтение CSV файла
     * 
     * @param string $filePath Путь к файлу
     * @param string $delimiter Разделитель
     * @param string $enclosure Символ обрамления
     * @param bool $hasHeader Имеет ли файл заголовки
     * @return array
     */
    public static function read(string $filePath, string $delimiter = ',', string $enclosure = '"', bool $hasHeader = true): array {
        $csv = new self($filePath, $delimiter, $enclosure);
        $csv->load($hasHeader);
        return $csv->all();
    }
    
    /**
     * Статический метод: Запись CSV файла
     * 
     * @param string $filePath Путь к файлу
     * @param array $data Данные
     * @param string $delimiter Разделитель
     * @param string $enclosure Символ обрамления
     * @param bool $writeHeader Записывать ли заголовки
     * @return bool
     */
    public static function write(string $filePath, array $data, string $delimiter = ',', string $enclosure = '"', bool $writeHeader = true): bool {
        $csv = new self($filePath, $delimiter, $enclosure);
        $csv->setData($data);
        return $csv->save(null, $writeHeader);
    }
    
    /**
     * Статический метод: Конвертация массива в CSV строку
     * 
     * @param array $data Данные
     * @param string $delimiter Разделитель
     * @param string $enclosure Символ обрамления
     * @return string
     */
    public static function arrayToString(array $data, string $delimiter = ',', string $enclosure = '"'): string {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row, $delimiter, $enclosure);
        }
        
        rewind($output);
        $string = stream_get_contents($output);
        fclose($output);
        
        return $string;
    }
}

