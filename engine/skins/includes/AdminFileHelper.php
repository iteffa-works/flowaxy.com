<?php
/**
 * Обертка над File, Directory для админ-панели
 * Интегрирует функционал из engine/classes/files/
 * 
 * @package Engine\Skins\Includes
 */

class AdminFileHelper {
    
    /**
     * Проверка существования файла
     */
    public static function exists(string $filePath): bool {
        if (class_exists('File')) {
            $file = new File($filePath);
            return $file->exists();
        }
        return file_exists($filePath) && is_file($filePath);
    }
    
    /**
     * Чтение содержимого файла
     */
    public static function read(string $filePath): string {
        if (class_exists('File')) {
            $file = new File($filePath);
            return $file->read();
        }
        
        if (!file_exists($filePath)) {
            throw new Exception("Файл не існує: {$filePath}");
        }
        
        $content = @file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Не вдалося прочитати файл: {$filePath}");
        }
        
        return $content;
    }
    
    /**
     * Запись содержимого в файл
     */
    public static function write(string $filePath, string $content, bool $append = false): bool {
        if (class_exists('File')) {
            $file = new File($filePath);
            return $file->write($content, $append);
        }
        
        // Создаем директорию если нужно
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        $flags = $append ? FILE_APPEND | LOCK_EX : LOCK_EX;
        $result = @file_put_contents($filePath, $content, $flags);
        
        if ($result === false) {
            throw new Exception("Не вдалося записати файл: {$filePath}");
        }
        
        @chmod($filePath, 0644);
        return true;
    }
    
    /**
     * Удаление файла
     */
    public static function delete(string $filePath): bool {
        if (class_exists('File')) {
            $file = new File($filePath);
            return $file->delete();
        }
        
        if (file_exists($filePath) && is_file($filePath)) {
            return @unlink($filePath);
        }
        
        return false;
    }
    
    /**
     * Копирование файла
     */
    public static function copy(string $source, string $destination): bool {
        if (class_exists('File')) {
            $file = new File($source);
            return $file->copyTo($destination);
        }
        
        // Создаем директорию если нужно
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        return @copy($source, $destination);
    }
    
    /**
     * Проверка существования директории
     */
    public static function dirExists(string $dirPath): bool {
        if (class_exists('Directory')) {
            $dir = new Directory($dirPath);
            return $dir->exists();
        }
        return is_dir($dirPath);
    }
    
    /**
     * Создание директории
     */
    public static function createDir(string $dirPath, int $permissions = 0755, bool $recursive = true): bool {
        if (class_exists('Directory')) {
            $dir = new Directory($dirPath);
            if (!$dir->exists()) {
                return $dir->create($permissions, $recursive);
            }
            return true;
        }
        
        if (!is_dir($dirPath)) {
            return @mkdir($dirPath, $permissions, $recursive);
        }
        
        return true;
    }
    
    /**
     * Получение размера файла
     */
    public static function size(string $filePath): int {
        if (class_exists('File')) {
            $file = new File($filePath);
            return $file->size();
        }
        
        if (file_exists($filePath) && is_file($filePath)) {
            return filesize($filePath);
        }
        
        return 0;
    }
    
    /**
     * Получение расширения файла
     */
    public static function extension(string $filePath): string {
        if (class_exists('File')) {
            $file = new File($filePath);
            return $file->extension();
        }
        
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }
    
    /**
     * Получение имени файла без расширения
     */
    public static function basename(string $filePath): string {
        return pathinfo($filePath, PATHINFO_BASENAME);
    }
    
    /**
     * Получение имени файла без пути и расширения
     */
    public static function filename(string $filePath): string {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }
}

