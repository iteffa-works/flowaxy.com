<?php
/**
 * Клас для роботи з файлами
 * Загальні операції з файлами: читання, запис, копіювання, видалення, перейменування
 * 
 * @package Engine\Classes\Files
 * @version 1.1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../../interfaces/FileInterface.php';

class File implements FileInterface {
    private string $filePath;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Шлях до файла
     */
    public function __construct(?string $filePath = null) {
        if ($filePath !== null) {
            $this->filePath = $filePath;
        }
    }
    
    /**
     * Встановлення шляху до файла
     * 
     * @param string $filePath Шлях до файла
     * @return self
     */
    public function setPath(string $filePath): self {
        $this->filePath = $filePath;
        return $this;
    }
    
    /**
     * Отримання шляху до файла
     * 
     * @return string
     */
    public function getPath(): string {
        return $this->filePath;
    }
    
    /**
     * Перевірка існування файла
     * 
     * @return bool
     */
    public function exists(): bool {
        return file_exists($this->filePath) && is_file($this->filePath);
    }
    
    /**
     * Читання вмісту файла
     * 
     * @return string
     * @throws Exception Якщо файл не існує або не може бути прочитаний
     */
    public function read(): string {
        if (!$this->exists()) {
            throw new Exception("Файл не існує: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("Файл недоступний для читання: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не вдалося прочитати файл: {$this->filePath}");
        }
        
        return $content;
    }
    
    /**
     * Запис вмісту в файл
     * 
     * @param string $content Вміст для запису
     * @param bool $append Додавати в кінець файла (false = перезаписати)
     * @return bool
     * @throws Exception Якщо не вдалося записати файл
     */
    public function write(string $content, bool $append = false): bool {
        $this->ensureDirectory();
        
        $flags = $append ? FILE_APPEND | LOCK_EX : LOCK_EX;
        $result = @file_put_contents($this->filePath, $content, $flags);
        
        if ($result === false) {
            throw new Exception("Не вдалося записати файл: {$this->filePath}");
        }
        
        @chmod($this->filePath, 0644);
        return true;
    }
    
    /**
     * Копіювання файла
     * 
     * @param string $destinationPath Шлях призначення
     * @return bool
     * @throws Exception Якщо не вдалося скопіювати
     */
    public function copy(string $destinationPath): bool {
        if (!$this->exists()) {
            throw new Exception("Вихідний файл не існує: {$this->filePath}");
        }
        
        $this->ensureDirectory($destinationPath);
        
        if (!@copy($this->filePath, $destinationPath)) {
            throw new Exception("Не вдалося скопіювати файл з '{$this->filePath}' в '{$destinationPath}'");
        }
        
        @chmod($destinationPath, 0644);
        return true;
    }
    
    /**
     * Переміщення/перейменування файла
     * 
     * @param string $destinationPath Шлях призначення
     * @return bool
     * @throws Exception Якщо не вдалося перемістити
     */
    public function move(string $destinationPath): bool {
        if (!$this->exists()) {
            throw new Exception("Вихідний файл не існує: {$this->filePath}");
        }
        
        $this->ensureDirectory($destinationPath);
        
        if (!@rename($this->filePath, $destinationPath)) {
            throw new Exception("Не вдалося перемістити файл з '{$this->filePath}' в '{$destinationPath}'");
        }
        
        $this->filePath = $destinationPath;
        return true;
    }
    
    /**
     * Видалення файла
     * 
     * @return bool
     * @throws Exception Якщо не вдалося видалити
     */
    public function delete(): bool {
        if (!$this->exists()) {
            return true;
        }
        
        if (!@unlink($this->filePath)) {
            throw new Exception("Не вдалося видалити файл: {$this->filePath}");
        }
        
        return true;
    }
    
    /**
     * Отримання розміру файла
     * 
     * @return int Розмір в байтах
     */
    public function getSize(): int {
        return $this->exists() ? filesize($this->filePath) : 0;
    }
    
    /**
     * Отримання MIME типу файла
     * 
     * @return string|false
     */
    public function getMimeType() {
        if (!$this->exists()) {
            return false;
        }
        
        if (function_exists('mime_content_type')) {
            return @mime_content_type($this->filePath);
        }
        
        if (function_exists('finfo_file')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                return false;
            }
            
            $mimeType = @finfo_file($finfo, $this->filePath);
            // finfo_close() is deprecated in PHP 8.1+, resource is automatically closed
            return $mimeType;
        }
        
        return false;
    }
    
    /**
     * Отримання часу останньої зміни
     * 
     * @return int|false Unix timestamp
     */
    public function getMTime() {
        return $this->exists() ? @filemtime($this->filePath) : false;
    }
    
    /**
     * Отримання часу створення
     * 
     * @return int|false Unix timestamp
     */
    public function getCTime() {
        return $this->exists() ? @filectime($this->filePath) : false;
    }
    
    /**
     * Отримання розширення файла
     * 
     * @return string
     */
    public function getExtension(): string {
        return strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
    }
    
    /**
     * Отримання імені файла з розширенням
     * 
     * @return string
     */
    public function getBasename(): string {
        return pathinfo($this->filePath, PATHINFO_BASENAME);
    }
    
    /**
     * Отримання імені файла без шляху та розширення
     * 
     * @return string
     */
    public function getFilename(): string {
        return pathinfo($this->filePath, PATHINFO_FILENAME);
    }
    
    /**
     * Отримання директорії файла
     * 
     * @return string
     */
    public function getDirectory(): string {
        return dirname($this->filePath);
    }
    
    /**
     * Перевірка, чи є файл читабельним
     * 
     * @return bool
     */
    public function isReadable(): bool {
        return $this->exists() && is_readable($this->filePath);
    }
    
    /**
     * Перевірка, чи є файл доступним для запису
     * 
     * @return bool
     */
    public function isWritable(): bool {
        return $this->exists() && is_writable($this->filePath);
    }
    
    /**
     * Встановлення прав доступу до файла
     * 
     * @param int $mode Права доступу (наприклад, 0644)
     * @return bool
     */
    public function chmod(int $mode): bool {
        return $this->exists() && @chmod($this->filePath, $mode);
    }
    
    /**
     * Створення порожнього файла
     * 
     * @return bool
     * @throws Exception Якщо не вдалося створити
     */
    public function create(): bool {
        return $this->exists() ? true : $this->write('');
    }
    
    /**
     * Додавання вмісту в кінець файла
     * 
     * @param string $content Вміст для додавання
     * @return bool
     */
    public function append(string $content): bool {
        return $this->write($content, true);
    }
    
    /**
     * Переконатися, що директорія існує
     * 
     * @param string|null $filePath Шлях до файла (якщо null, використовується поточний)
     * @return void
     * @throws Exception Якщо не вдалося створити директорію
     */
    private function ensureDirectory(?string $filePath = null): void {
        $dir = dirname($filePath ?? $this->filePath);
        
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new Exception("Не вдалося створити директорію: {$dir}");
        }
    }
    
    /**
     * Статичний метод: Перевірка існування файла
     * Перейменовано з exists() щоб уникнути конфлікту з методом екземпляра
     * 
     * @param string $filePath Шлях до файла
     * @return bool
     */
    public static function fileExists(string $filePath): bool {
        return file_exists($filePath) && is_file($filePath);
    }
    
    /**
     * Статичний метод: Читання файла
     * Перейменовано з read() щоб уникнути конфлікту з методом екземпляра
     * 
     * @param string $filePath Шлях до файла
     * @return string|false
     */
    public static function readFile(string $filePath) {
        try {
            return (new self($filePath))->read();
        } catch (Exception $e) {
            error_log("File::readFile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статичний метод: Запис в файл
     * Перейменовано з write() щоб уникнути конфлікту з методом екземпляра
     * 
     * @param string $filePath Шлях до файла
     * @param string $content Вміст
     * @param bool $append Додавати в кінець
     * @return bool
     */
    public static function writeFile(string $filePath, string $content, bool $append = false): bool {
        try {
            return (new self($filePath))->write($content, $append);
        } catch (Exception $e) {
            error_log("File::writeFile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статичний метод: Копіювання файла
     * Перейменовано з copy() щоб уникнути конфлікту з методом екземпляра
     * 
     * @param string $sourcePath Вихідний шлях
     * @param string $destinationPath Шлях призначення
     * @return bool
     */
    public static function copyFile(string $sourcePath, string $destinationPath): bool {
        try {
            return (new self($sourcePath))->copy($destinationPath);
        } catch (Exception $e) {
            error_log("File::copyFile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статичний метод: Видалення файла
     * Перейменовано з delete() щоб уникнути конфлікту з методом екземпляра
     * 
     * @param string $filePath Шлях до файла
     * @return bool
     */
    public static function deleteFile(string $filePath): bool {
        try {
            return (new self($filePath))->delete();
        } catch (Exception $e) {
            error_log("File::deleteFile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статичний метод: Отримання розміру файла
     * 
     * @param string $filePath Шлях до файла
     * @return int
     */
    public static function size(string $filePath): int {
        return (new self($filePath))->getSize();
    }
    
    /**
     * Статичний метод: Отримання MIME типу
     * 
     * @param string $filePath Шлях до файла
     * @return string|false
     */
    public static function mimeType(string $filePath) {
        return (new self($filePath))->getMimeType();
    }
}
