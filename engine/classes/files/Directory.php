<?php
/**
 * Класс для работы с директориями
 * Создание, удаление, копирование директорий и работа с файлами внутри
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 */

declare(strict_types=1);

class Directory {
    private string $path;
    
    /**
     * Конструктор
     * 
     * @param string|null $path Путь к директории
     */
    public function __construct(?string $path = null) {
        if ($path !== null) {
            $this->setPath($path);
        }
    }
    
    /**
     * Установка пути к директории
     * 
     * @param string $path Путь к директории
     * @return self
     */
    public function setPath(string $path): self {
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        return $this;
    }
    
    /**
     * Получение пути к директории
     * 
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }
    
    /**
     * Проверка существования директории
     * 
     * @return bool
     */
    public function exists(): bool {
        return is_dir($this->path);
    }
    
    /**
     * Создание директории
     * 
     * @param int $mode Права доступа (по умолчанию 0755)
     * @param bool $recursive Создавать ли вложенные директории
     * @return bool
     * @throws Exception Если не удалось создать
     */
    public function create(int $mode = 0755, bool $recursive = true): bool {
        if ($this->exists()) {
            return true; // Директория уже существует
        }
        
        if (!@mkdir($this->path, $mode, $recursive)) {
            throw new Exception("Не удалось создать директорию: {$this->path}");
        }
        
        return true;
    }
    
    /**
     * Удаление директории
     * 
     * @param bool $recursive Удалять ли содержимое рекурсивно
     * @return bool
     * @throws Exception Если не удалось удалить
     */
    public function delete(bool $recursive = true): bool {
        if (!$this->exists()) {
            return true; // Директория уже не существует
        }
        
        if ($recursive) {
            return $this->removeRecursive($this->path);
        } else {
            if (!@rmdir($this->path)) {
                throw new Exception("Не удалось удалить директорию: {$this->path}");
            }
            return true;
        }
    }
    
    /**
     * Рекурсивное удаление директории
     * 
     * @param string $dir Путь к директории
     * @return bool
     */
    private function removeRecursive(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($filePath)) {
                $this->removeRecursive($filePath);
            } else {
                @unlink($filePath);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * Копирование директории
     * 
     * @param string $destinationPath Путь назначения
     * @param bool $overwrite Перезаписывать ли существующие файлы
     * @return bool
     * @throws Exception Если не удалось скопировать
     */
    public function copy(string $destinationPath, bool $overwrite = true): bool {
        if (!$this->exists()) {
            throw new Exception("Исходная директория не существует: {$this->path}");
        }
        
        $destinationPath = rtrim($destinationPath, '/\\') . DIRECTORY_SEPARATOR;
        
        // Создаем директорию назначения
        if (!is_dir($destinationPath)) {
            if (!@mkdir($destinationPath, 0755, true)) {
                throw new Exception("Не удалось создать директорию назначения: {$destinationPath}");
            }
        }
        
        return $this->copyRecursive($this->path, $destinationPath, $overwrite);
    }
    
    /**
     * Рекурсивное копирование
     * 
     * @param string $source Исходный путь
     * @param string $destination Путь назначения
     * @param bool $overwrite Перезаписывать ли
     * @return bool
     */
    private function copyRecursive(string $source, string $destination, bool $overwrite): bool {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            if (!@mkdir($destination, 0755, true)) {
                return false;
            }
        }
        
        $files = array_diff(scandir($source), ['.', '..']);
        
        foreach ($files as $file) {
            $sourcePath = $source . $file;
            $destinationPath = $destination . $file;
            
            if (is_dir($sourcePath)) {
                if (!$this->copyRecursive($sourcePath . DIRECTORY_SEPARATOR, $destinationPath . DIRECTORY_SEPARATOR, $overwrite)) {
                    return false;
                }
            } else {
                if (!$overwrite && file_exists($destinationPath)) {
                    continue;
                }
                
                if (!@copy($sourcePath, $destinationPath)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Получение списка файлов в директории
     * 
     * @param bool $recursive Рекурсивно
     * @param string|null $pattern Паттерн для фильтрации (например, '*.php')
     * @return array
     */
    public function getFiles(bool $recursive = false, ?string $pattern = null): array {
        if (!$this->exists()) {
            return [];
        }
        
        $files = [];
        
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getRealPath();
                    
                    if ($pattern === null || fnmatch($pattern, $file->getFilename())) {
                        $files[] = $filePath;
                    }
                }
            }
        } else {
            $items = scandir($this->path);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $filePath = $this->path . $item;
                
                if (is_file($filePath)) {
                    if ($pattern === null || fnmatch($pattern, $item)) {
                        $files[] = $filePath;
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Получение списка директорий
     * 
     * @param bool $recursive Рекурсивно
     * @return array
     */
    public function getDirectories(bool $recursive = false): array {
        if (!$this->exists()) {
            return [];
        }
        
        $directories = [];
        
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    $directories[] = $file->getRealPath() . DIRECTORY_SEPARATOR;
                }
            }
        } else {
            $items = scandir($this->path);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $itemPath = $this->path . $item;
                
                if (is_dir($itemPath)) {
                    $directories[] = $itemPath . DIRECTORY_SEPARATOR;
                }
            }
        }
        
        return $directories;
    }
    
    /**
     * Получение размера директории
     * 
     * @param bool $format Форматировать ли размер (KB, MB и т.д.)
     * @return int|string
     */
    public function getSize(bool $format = false) {
        if (!$this->exists()) {
            return $format ? '0 B' : 0;
        }
        
        $size = 0;
        $files = $this->getFiles(true);
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $size += filesize($file);
            }
        }
        
        if ($format) {
            return $this->formatSize($size);
        }
        
        return $size;
    }
    
    /**
     * Форматирование размера
     * 
     * @param int $bytes Размер в байтах
     * @return string
     */
    private function formatSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Очистка директории (удаление всех файлов и поддиректорий)
     * 
     * @return bool
     */
    public function clean(): bool {
        if (!$this->exists()) {
            return true;
        }
        
        return $this->delete(true) && $this->create();
    }
    
    /**
     * Установка прав доступа к директории
     * 
     * @param int $mode Права доступа
     * @return bool
     */
    public function chmod(int $mode): bool {
        if (!$this->exists()) {
            return false;
        }
        
        return @chmod($this->path, $mode);
    }
    
    /**
     * Статический метод: Создание директории
     * 
     * @param string $path Путь к директории
     * @param int $mode Права доступа
     * @param bool $recursive Рекурсивно
     * @return bool
     */
    public static function create(string $path, int $mode = 0755, bool $recursive = true): bool {
        $dir = new self($path);
        try {
            return $dir->create($mode, $recursive);
        } catch (Exception $e) {
            error_log("Directory::create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Удаление директории
     * 
     * @param string $path Путь к директории
     * @param bool $recursive Рекурсивно
     * @return bool
     */
    public static function delete(string $path, bool $recursive = true): bool {
        $dir = new self($path);
        try {
            return $dir->delete($recursive);
        } catch (Exception $e) {
            error_log("Directory::delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Копирование директории
     * 
     * @param string $sourcePath Исходный путь
     * @param string $destinationPath Путь назначения
     * @param bool $overwrite Перезаписывать ли
     * @return bool
     */
    public static function copy(string $sourcePath, string $destinationPath, bool $overwrite = true): bool {
        $dir = new self($sourcePath);
        try {
            return $dir->copy($destinationPath, $overwrite);
        } catch (Exception $e) {
            error_log("Directory::copy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Проверка существования директории
     * 
     * @param string $path Путь к директории
     * @return bool
     */
    public static function exists(string $path): bool {
        return is_dir($path);
    }
}

