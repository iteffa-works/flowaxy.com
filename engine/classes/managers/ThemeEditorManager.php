<?php
/**
 * Менеджер редактора теми
 * 
 * @package Engine\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

class ThemeEditorManager {
    private static ?ThemeEditorManager $instance = null;
    private ?PDO $db = null;
    
    private function __construct() {
        try {
            $this->db = DatabaseHelper::getConnection();
        } catch (Exception $e) {
            error_log("ThemeEditorManager constructor error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    public static function getInstance(): ThemeEditorManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Отримання списку файлів теми
     */
    public function getThemeFiles(string $themePath, array $allowedExtensions = ['php', 'css', 'js', 'json', 'html', 'htm', 'txt', 'md', 'xml', 'yaml', 'yml']): array {
        $files = [];
        
        if (!is_dir($themePath) || !is_readable($themePath)) {
            return $files;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($themePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = strtolower($file->getExtension());
                    if (in_array($extension, $allowedExtensions, true)) {
                        $relativePath = str_replace($themePath, '', $file->getPathname());
                        $relativePath = str_replace('\\', '/', $relativePath);
                        $relativePath = ltrim($relativePath, '/');
                        
                        $files[] = [
                            'path' => $relativePath,
                            'fullPath' => $file->getPathname(),
                            'name' => $file->getFilename(),
                            'extension' => $extension,
                            'size' => $file->getSize(),
                            'modified' => $file->getMTime(),
                            'directory' => dirname($relativePath) ?: '.'
                        ];
                    }
                }
            }
            
            // Сортуємо файли: спочатку основні, потім за ім'ям
            usort($files, function($a, $b) {
                $priority = ['index.php', 'style.css', 'script.js', 'theme.json', 'customizer.php', 'functions.php'];
                $aPriority = array_search($a['name'], $priority, true);
                $bPriority = array_search($b['name'], $priority, true);
                
                if ($aPriority !== false && $bPriority !== false) {
                    return $aPriority <=> $bPriority;
                }
                if ($aPriority !== false) {
                    return -1;
                }
                if ($bPriority !== false) {
                    return 1;
                }
                
                return strcmp($a['path'], $b['path']);
            });
        } catch (Exception $e) {
            error_log("Error getting theme files: " . $e->getMessage());
        }
        
        return $files;
    }
    
    /**
     * Отримання вмісту файлу
     */
    public function getFileContent(string $filePath): ?string {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        
        // Перевіряємо, що файл знаходиться в допустимій директорії теми
        $realPath = realpath($filePath);
        if ($realPath === false) {
            return null;
        }
        
        return file_get_contents($realPath) ?: null;
    }
    
    /**
     * Збереження файлу
     */
    public function saveFile(string $filePath, string $content, string $themePath): array {
        // Перевіряємо безпеку шляху
        $realFilePath = realpath($filePath);
        $realThemePath = realpath($themePath);
        
        if ($realFilePath === false || $realThemePath === false) {
            return ['success' => false, 'error' => 'Невірний шлях до файлу'];
        }
        
        // Перевіряємо, що файл знаходиться в директорії теми
        if (!str_starts_with($realFilePath, $realThemePath)) {
            return ['success' => false, 'error' => 'Файл не належить до теми'];
        }
        
        // Перевіряємо права на запис
        if (!is_writable($realFilePath) && file_exists($realFilePath)) {
            return ['success' => false, 'error' => 'Немає прав на запис файлу'];
        }
        
        // Перевіряємо права на запис директорії (якщо файл не існує)
        $fileDir = dirname($realFilePath);
        if (!file_exists($realFilePath) && !is_writable($fileDir)) {
            return ['success' => false, 'error' => 'Немає прав на створення файлу'];
        }
        
        try {
            // Створюємо резервну копію перед збереженням
            if (file_exists($realFilePath)) {
                $backupPath = $realFilePath . '.backup.' . date('Y-m-d_H-i-s');
                @copy($realFilePath, $backupPath);
            }
            
            // Зберігаємо файл
            $result = file_put_contents($realFilePath, $content);
            
            if ($result === false) {
                return ['success' => false, 'error' => 'Помилка збереження файлу'];
            }
            
            return [
                'success' => true,
                'message' => 'Файл успішно збережено',
                'size' => $result
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Створення нового файлу
     */
    public function createFile(string $filePath, string $themePath, string $content = ''): array {
        $realThemePath = realpath($themePath);
        if ($realThemePath === false) {
            return ['success' => false, 'error' => 'Невірний шлях до теми'];
        }
        
        // Нормалізуємо шлях
        $filePath = str_replace('\\', '/', $filePath);
        $filePath = ltrim($filePath, '/');
        $fullPath = $realThemePath . '/' . $filePath;
        
        // Перевіряємо, що файл знаходиться в директорії теми
        $realFullPath = realpath(dirname($fullPath));
        if ($realFullPath === false || !str_starts_with($realFullPath, $realThemePath)) {
            return ['success' => false, 'error' => 'Невірний шлях до файлу'];
        }
        
        // Перевіряємо, чи файл вже існує
        if (file_exists($fullPath)) {
            return ['success' => false, 'error' => 'Файл вже існує'];
        }
        
        // Створюємо директорію, якщо потрібно
        $fileDir = dirname($fullPath);
        if (!is_dir($fileDir)) {
            if (!mkdir($fileDir, 0755, true)) {
                return ['success' => false, 'error' => 'Неможливо створити директорію'];
            }
        }
        
        try {
            $result = file_put_contents($fullPath, $content);
            if ($result === false) {
                return ['success' => false, 'error' => 'Помилка створення файлу'];
            }
            
            return [
                'success' => true,
                'message' => 'Файл успішно створено',
                'path' => $filePath
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Видалення файлу
     */
    public function deleteFile(string $filePath, string $themePath): array {
        $realFilePath = realpath($filePath);
        $realThemePath = realpath($themePath);
        
        if ($realFilePath === false || $realThemePath === false) {
            return ['success' => false, 'error' => 'Невірний шлях'];
        }
        
        // Перевіряємо, що файл знаходиться в директорії теми
        if (!str_starts_with($realFilePath, $realThemePath)) {
            return ['success' => false, 'error' => 'Файл не належить до теми'];
        }
        
        // Забороняємо видалення критичних файлів
        $criticalFiles = ['index.php', 'theme.json', 'functions.php'];
        $fileName = basename($realFilePath);
        if (in_array($fileName, $criticalFiles, true)) {
            return ['success' => false, 'error' => 'Неможливо видалити критичний файл'];
        }
        
        try {
            if (!unlink($realFilePath)) {
                return ['success' => false, 'error' => 'Помилка видалення файлу'];
            }
            
            return ['success' => true, 'message' => 'Файл успішно видалено'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Створення директорії
     */
    public function createDirectory(string $dirPath, string $themePath): array {
        $realThemePath = realpath($themePath);
        if ($realThemePath === false) {
            return ['success' => false, 'error' => 'Невірний шлях до теми'];
        }
        
        // Нормалізуємо шлях
        $dirPath = str_replace('\\', '/', $dirPath);
        $dirPath = ltrim($dirPath, '/');
        $fullPath = $realThemePath . '/' . $dirPath;
        
        // Перевіряємо, що директорія знаходиться в теми
        $realFullPath = realpath(dirname($fullPath));
        if ($realFullPath === false || !str_starts_with($realFullPath, $realThemePath)) {
            return ['success' => false, 'error' => 'Невірний шлях до директорії'];
        }
        
        // Перевіряємо, чи директорія вже існує
        if (is_dir($fullPath)) {
            return ['success' => false, 'error' => 'Директорія вже існує'];
        }
        
        try {
            if (!mkdir($fullPath, 0755, true)) {
                return ['success' => false, 'error' => 'Помилка створення директорії'];
            }
            
            return [
                'success' => true,
                'message' => 'Директорію успішно створено',
                'path' => $dirPath
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отримання структури директорій теми
     */
    public function getDirectoryStructure(string $themePath): array {
        $structure = [];
        
        if (!is_dir($themePath) || !is_readable($themePath)) {
            return $structure;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($themePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                $relativePath = str_replace($themePath, '', $item->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                $relativePath = ltrim($relativePath, '/');
                
                if ($item->isDir()) {
                    $structure['directories'][] = $relativePath;
                } else {
                    $structure['files'][] = [
                        'path' => $relativePath,
                        'name' => $item->getFilename(),
                        'extension' => strtolower($item->getExtension()),
                        'size' => $item->getSize()
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error getting directory structure: " . $e->getMessage());
        }
        
        return $structure;
    }
    
    /**
     * Завантаження файлу в папку теми
     */
    public function uploadFile(array $uploadedFile, string $folderPath, string $themePath): array {
        try {
            if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
                return ['success' => false, 'error' => 'Файл не було завантажено'];
            }
            
            $fileName = $uploadedFile['name'];
            $tmpName = $uploadedFile['tmp_name'];
            
            // Перевірка на небезпечні символи в імені файлу
            if (preg_match('/[\/\\\?\*\|<>:"]/', $fileName)) {
                return ['success' => false, 'error' => 'Недопустимі символи в імені файлу'];
            }
            
            // Формуємо повний шлях
            $targetPath = rtrim($themePath, '/\\') . '/';
            if (!empty($folderPath)) {
                $targetPath .= trim($folderPath, '/\\') . '/';
            }
            $targetPath .= $fileName;
            
            // Перевірка безпеки шляху
            $realThemePath = realpath($themePath);
            $realTargetPath = realpath(dirname($targetPath));
            
            if ($realThemePath === false || $realTargetPath === false || 
                !str_starts_with($realTargetPath, $realThemePath)) {
                return ['success' => false, 'error' => 'Недопустимий шлях для завантаження'];
            }
            
            // Створюємо директорію, якщо вона не існує
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    return ['success' => false, 'error' => 'Не вдалося створити директорію'];
                }
            }
            
            // Переміщуємо файл
            if (!move_uploaded_file($tmpName, $targetPath)) {
                return ['success' => false, 'error' => 'Не вдалося зберегти файл'];
            }
            
            // Встановлюємо права доступу
            @chmod($targetPath, 0644);
            
            return [
                'success' => true,
                'message' => 'Файл успішно завантажено',
                'path' => str_replace($themePath, '', $targetPath),
                'name' => $fileName
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Створення ZIP архіву папки
     */
    public function createFolderZip(string $folderPath, string $themePath): ?string {
        try {
            $fullFolderPath = rtrim($themePath, '/\\') . '/' . trim($folderPath, '/\\');
            
            if (!is_dir($fullFolderPath)) {
                return null;
            }
            
            // Перевірка безпеки шляху
            $realThemePath = realpath($themePath);
            $realFolderPath = realpath($fullFolderPath);
            
            if ($realThemePath === false || $realFolderPath === false || 
                !str_starts_with($realFolderPath, $realThemePath)) {
                return null;
            }
            
            // Створюємо тимчасовий файл для архіву
            $zipPath = sys_get_temp_dir() . '/theme_folder_' . uniqid() . '.zip';
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return null;
            }
            
            // Додаємо всі файли з папки до архіву
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullFolderPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($fullFolderPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $zip->addFile($file->getPathname(), $relativePath);
                }
            }
            
            $zip->close();
            
            return $zipPath;
        } catch (Exception $e) {
            error_log("Error creating folder zip: " . $e->getMessage());
            return null;
        }
    }
}

