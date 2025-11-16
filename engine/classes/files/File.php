<?php
/**
 * Класс для работы с файлами
 * Общие операции с файлами: чтение, запись, копирование, удаление, переименование
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 */

declare(strict_types=1);

class File {
    private string $filePath;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к файлу
     */
    public function __construct(?string $filePath = null) {
        if ($filePath !== null) {
            $this->setPath($filePath);
        }
    }
    
    /**
     * Установка пути к файлу
     * 
     * @param string $filePath Путь к файлу
     * @return self
     */
    public function setPath(string $filePath): self {
        $this->filePath = $filePath;
        return $this;
    }
    
    /**
     * Получение пути к файлу
     * 
     * @return string
     */
    public function getPath(): string {
        return $this->filePath;
    }
    
    /**
     * Проверка существования файла
     * 
     * @return bool
     */
    public function exists(): bool {
        return file_exists($this->filePath) && is_file($this->filePath);
    }
    
    /**
     * Чтение содержимого файла
     * 
     * @return string|false
     * @throws Exception Если файл не существует или не может быть прочитан
     */
    public function read() {
        if (!$this->exists()) {
            throw new Exception("Файл не существует: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("Файл недоступен для чтения: {$this->filePath}");
        }
        
        $content = @file_get_contents($this->filePath);
        
        if ($content === false) {
            throw new Exception("Не удалось прочитать файл: {$this->filePath}");
        }
        
        return $content;
    }
    
    /**
     * Запись содержимого в файл
     * 
     * @param string $content Содержимое для записи
     * @param bool $append Добавлять ли в конец файла (false = перезаписать)
     * @return bool
     * @throws Exception Если не удалось записать файл
     */
    public function write(string $content, bool $append = false): bool {
        // Создаем директорию, если её нет
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$dir}");
            }
        }
        
        $flags = $append ? FILE_APPEND | LOCK_EX : LOCK_EX;
        $result = @file_put_contents($this->filePath, $content, $flags);
        
        if ($result === false) {
            throw new Exception("Не удалось записать файл: {$this->filePath}");
        }
        
        @chmod($this->filePath, 0644);
        
        return true;
    }
    
    /**
     * Копирование файла
     * 
     * @param string $destinationPath Путь назначения
     * @return bool
     * @throws Exception Если не удалось скопировать
     */
    public function copy(string $destinationPath): bool {
        if (!$this->exists()) {
            throw new Exception("Исходный файл не существует: {$this->filePath}");
        }
        
        // Создаем директорию назначения, если её нет
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$dir}");
            }
        }
        
        if (!@copy($this->filePath, $destinationPath)) {
            throw new Exception("Не удалось скопировать файл из '{$this->filePath}' в '{$destinationPath}'");
        }
        
        @chmod($destinationPath, 0644);
        
        return true;
    }
    
    /**
     * Перемещение/переименование файла
     * 
     * @param string $destinationPath Путь назначения
     * @return bool
     * @throws Exception Если не удалось переместить
     */
    public function move(string $destinationPath): bool {
        if (!$this->exists()) {
            throw new Exception("Исходный файл не существует: {$this->filePath}");
        }
        
        // Создаем директорию назначения, если её нет
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$dir}");
            }
        }
        
        if (!@rename($this->filePath, $destinationPath)) {
            throw new Exception("Не удалось переместить файл из '{$this->filePath}' в '{$destinationPath}'");
        }
        
        $this->filePath = $destinationPath;
        
        return true;
    }
    
    /**
     * Удаление файла
     * 
     * @return bool
     * @throws Exception Если не удалось удалить
     */
    public function delete(): bool {
        if (!$this->exists()) {
            return true; // Файл уже не существует
        }
        
        if (!@unlink($this->filePath)) {
            throw new Exception("Не удалось удалить файл: {$this->filePath}");
        }
        
        return true;
    }
    
    /**
     * Получение размера файла
     * 
     * @return int Размер в байтах
     */
    public function getSize(): int {
        if (!$this->exists()) {
            return 0;
        }
        
        return filesize($this->filePath);
    }
    
    /**
     * Получение MIME типа файла
     * 
     * @return string|false
     */
    public function getMimeType() {
        if (!$this->exists()) {
            return false;
        }
        
        if (function_exists('mime_content_type')) {
            return mime_content_type($this->filePath);
        }
        
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $this->filePath);
            finfo_close($finfo);
            return $mimeType;
        }
        
        return false;
    }
    
    /**
     * Получение времени последнего изменения
     * 
     * @return int|false Unix timestamp
     */
    public function getMTime() {
        if (!$this->exists()) {
            return false;
        }
        
        return filemtime($this->filePath);
    }
    
    /**
     * Получение времени создания
     * 
     * @return int|false Unix timestamp
     */
    public function getCTime() {
        if (!$this->exists()) {
            return false;
        }
        
        return filectime($this->filePath);
    }
    
    /**
     * Получение расширения файла
     * 
     * @return string
     */
    public function getExtension(): string {
        return strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
    }
    
    /**
     * Получение имени файла без расширения
     * 
     * @return string
     */
    public function getBasename(): string {
        return pathinfo($this->filePath, PATHINFO_BASENAME);
    }
    
    /**
     * Получение имени файла без пути
     * 
     * @return string
     */
    public function getFilename(): string {
        return pathinfo($this->filePath, PATHINFO_FILENAME);
    }
    
    /**
     * Получение директории файла
     * 
     * @return string
     */
    public function getDirectory(): string {
        return dirname($this->filePath);
    }
    
    /**
     * Проверка, является ли файл читаемым
     * 
     * @return bool
     */
    public function isReadable(): bool {
        return $this->exists() && is_readable($this->filePath);
    }
    
    /**
     * Проверка, является ли файл записываемым
     * 
     * @return bool
     */
    public function isWritable(): bool {
        return $this->exists() && is_writable($this->filePath);
    }
    
    /**
     * Установка прав доступа к файлу
     * 
     * @param int $mode Права доступа (например, 0644)
     * @return bool
     */
    public function chmod(int $mode): bool {
        if (!$this->exists()) {
            return false;
        }
        
        return @chmod($this->filePath, $mode);
    }
    
    /**
     * Создание пустого файла
     * 
     * @return bool
     * @throws Exception Если не удалось создать
     */
    public function create(): bool {
        if ($this->exists()) {
            return true; // Файл уже существует
        }
        
        return $this->write('');
    }
    
    /**
     * Добавление содержимого в конец файла
     * 
     * @param string $content Содержимое для добавления
     * @return bool
     */
    public function append(string $content): bool {
        return $this->write($content, true);
    }
    
    /**
     * Статический метод: Проверка существования файла
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function exists(string $filePath): bool {
        return file_exists($filePath) && is_file($filePath);
    }
    
    /**
     * Статический метод: Чтение файла
     * 
     * @param string $filePath Путь к файлу
     * @return string|false
     */
    public static function read(string $filePath) {
        $file = new self($filePath);
        try {
            return $file->read();
        } catch (Exception $e) {
            error_log("File::read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Запись в файл
     * 
     * @param string $filePath Путь к файлу
     * @param string $content Содержимое
     * @param bool $append Добавлять ли в конец
     * @return bool
     */
    public static function write(string $filePath, string $content, bool $append = false): bool {
        $file = new self($filePath);
        try {
            return $file->write($content, $append);
        } catch (Exception $e) {
            error_log("File::write error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Копирование файла
     * 
     * @param string $sourcePath Исходный путь
     * @param string $destinationPath Путь назначения
     * @return bool
     */
    public static function copy(string $sourcePath, string $destinationPath): bool {
        $file = new self($sourcePath);
        try {
            return $file->copy($destinationPath);
        } catch (Exception $e) {
            error_log("File::copy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Удаление файла
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function delete(string $filePath): bool {
        $file = new self($filePath);
        try {
            return $file->delete();
        } catch (Exception $e) {
            error_log("File::delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Получение размера файла
     * 
     * @param string $filePath Путь к файлу
     * @return int
     */
    public static function size(string $filePath): int {
        $file = new self($filePath);
        return $file->getSize();
    }
    
    /**
     * Статический метод: Получение MIME типа
     * 
     * @param string $filePath Путь к файлу
     * @return string|false
     */
    public static function mimeType(string $filePath) {
        $file = new self($filePath);
        return $file->getMimeType();
    }
}

