<?php
/**
 * Менеджер редактора теми
 * 
 * @package Engine\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../files/File.php';
require_once __DIR__ . '/../files/Directory.php';
require_once __DIR__ . '/../files/Zip.php';
require_once __DIR__ . '/../files/Ini.php';
require_once __DIR__ . '/../files/Upload.php';
require_once __DIR__ . '/../data/Logger.php';
require_once __DIR__ . '/../data/Cache.php';
require_once __DIR__ . '/../validators/Validator.php';

class ThemeEditorManager {
    private static ?ThemeEditorManager $instance = null;
    private ?PDO $db = null;
    
    private function __construct() {
        try {
            $this->db = DatabaseHelper::getConnection();
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorManager constructor error', ['error' => $e->getMessage()]);
            } else {
                error_log("ThemeEditorManager constructor error: " . $e->getMessage());
            }
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
    public function getThemeFiles(string $themePath, array $allowedExtensions = null): array {
        // Если расширения не указаны, используем все типы файлов
        if ($allowedExtensions === null) {
            $allowedExtensions = ['php', 'css', 'js', 'json', 'html', 'htm', 'txt', 'md', 'xml', 'yaml', 'yml', 
                                  'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp',
                                  'mp4', 'webm', 'ogg', 'mp3', 'wav', 'flac',
                                  'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'tar', 'gz',
                                  'woff', 'woff2', 'ttf', 'eot', 'otf'];
        }
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
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Error getting theme files', ['error' => $e->getMessage(), 'themePath' => $themePath]);
            } else {
                error_log("Error getting theme files: " . $e->getMessage());
            }
        }
        
        return $files;
    }
    
    /**
     * Отримання вмісту файлу
     */
    public function getFileContent(string $filePath): ?string {
        try {
            $file = new File($filePath);
            
            if (!$file->exists() || !$file->isReadable()) {
                return null;
            }
            
            return $file->read();
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorManager: Error reading file content', ['error' => $e->getMessage(), 'filePath' => $filePath]);
            } else {
                error_log("ThemeEditorManager: Error reading file content: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Збереження файлу
     */
    public function saveFile(string $filePath, string $content, string $themePath): array {
        try {
            // Перевіряємо безпеку шляху
            $realThemePath = realpath($themePath);
            $realFilePath = realpath($filePath);
            
            // Якщо файл не існує, створюємо його шлях
            if ($realFilePath === false) {
                $realFilePath = realpath(dirname($filePath));
                if ($realFilePath === false) {
                    return ['success' => false, 'error' => 'Невірний шлях до файлу'];
                }
                $realFilePath = $filePath;
            }
            
            if ($realThemePath === false) {
                return ['success' => false, 'error' => 'Невірний шлях до теми'];
            }
            
            // Перевіряємо, що файл знаходиться в директорії теми
            $file = new File($filePath);
            if (!$file->isPathSafe($realThemePath)) {
                return ['success' => false, 'error' => 'Файл не належить до теми'];
            }
            
            // Використовуємо клас File для збереження
            $file = new File($realFilePath);
            
            // Перевіряємо права на запис
            if ($file->exists() && !$file->isWritable()) {
                return ['success' => false, 'error' => 'Немає прав на запис файлу'];
            }
            
            // Зберігаємо файл
            $file->write($content);
            
            return [
                'success' => true,
                'message' => 'Файл успішно збережено',
                'size' => strlen($content)
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
        try {
            $dir = new Directory($fileDir);
            if (!$dir->exists()) {
                $dir->create(0755, true);
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Неможливо створити директорію: ' . $e->getMessage()];
        }
        
        try {
            // Використовуємо клас File для створення
            $file = new File($fullPath);
            $file->write($content);
            
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
        $file = new File($realFilePath);
        if (!$file->isPathSafe($realThemePath)) {
            return ['success' => false, 'error' => 'Файл не належить до теми'];
        }
        
        // Забороняємо видалення критичних файлів
        $criticalFiles = ['index.php', 'theme.json', 'functions.php'];
        $file = new File($realFilePath);
        $fileName = $file->getBasename();
        if (in_array($fileName, $criticalFiles, true)) {
            return ['success' => false, 'error' => 'Неможливо видалити критичний файл'];
        }
        
        try {
            // Використовуємо клас File для видалення
            $file = new File($realFilePath);
            $file->delete();
            
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
        $dir = new Directory($fullPath);
        $file = new File($fullPath);
        if (!$file->isPathSafe($realThemePath)) {
            return ['success' => false, 'error' => 'Невірний шлях до директорії'];
        }
        
        // Перевіряємо, чи директорія вже існує
        if (is_dir($fullPath)) {
            return ['success' => false, 'error' => 'Директорія вже існує'];
        }
        
        try {
            // Використовуємо клас Directory для створення
            $dir = new Directory($fullPath);
            $dir->create(0755, true);
            
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
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Error getting directory structure', ['error' => $e->getMessage(), 'themePath' => $themePath]);
            } else {
                error_log("Error getting directory structure: " . $e->getMessage());
            }
        }
        
        return $structure;
    }
    
    /**
     * Завантаження файлу в папку теми
     */
    public function uploadFile(array $uploadedFile, string $folderPath, string $themePath): array {
        try {
            // Перевірка безпеки шляху
            $realThemePath = realpath($themePath);
            if ($realThemePath === false) {
                return ['success' => false, 'error' => 'Невірний шлях до теми'];
            }
            
            // Формуємо повний шлях до директорії завантаження
            $targetDir = rtrim($realThemePath, '/\\') . DIRECTORY_SEPARATOR;
            if (!empty($folderPath)) {
                $targetDir .= trim($folderPath, '/\\') . DIRECTORY_SEPARATOR;
            }
            
            // Перевірка безпеки шляху директорії
            $realTargetDir = realpath($targetDir);
            if ($realTargetDir === false) {
                // Спробуємо створити директорію
                try {
                    $dir = new Directory($targetDir);
                    $dir->create(0755, true);
                    $realTargetDir = realpath($targetDir);
                } catch (Exception $e) {
                    if (class_exists('Logger')) {
                        Logger::getInstance()->logError('ThemeEditorManager: Failed to create upload directory', ['error' => $e->getMessage(), 'targetDir' => $targetDir]);
                    }
                    return ['success' => false, 'error' => 'Не вдалося створити директорію: ' . $e->getMessage()];
                }
            }
            
            // Перевіряємо безпеку шляху через клас File
            $targetFile = new File($realTargetDir . DIRECTORY_SEPARATOR . ($uploadedFile['name'] ?? 'temp'));
            if (!$targetFile->isPathSafe($realThemePath)) {
                return ['success' => false, 'error' => 'Недопустимий шлях для завантаження'];
            }
            
            // Використовуємо клас Upload для завантаження файлу
            $upload = new Upload($realTargetDir);
            
            // Валідуємо ім'я файлу через Validator
            $fileName = $uploadedFile['name'] ?? '';
            if (empty($fileName)) {
                return ['success' => false, 'error' => 'Ім\'я файлу не може бути порожнім'];
            }
            
            // Перевіряємо на небезпечні символи (використовуємо валідацію slug як базову, але розширюємо для файлів)
            if (!Validator::validateString($fileName, 1, 255) || preg_match('/[\/\\\?\*\|<>:"]/', $fileName)) {
                return ['success' => false, 'error' => 'Недопустимі символи в імені файлу'];
            }
            
            // Завантажуємо файл
            $result = $upload->upload($uploadedFile);
            
            if (!$result['success']) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('ThemeEditorManager: File upload failed', ['error' => $result['error'] ?? 'Unknown error', 'fileName' => $fileName]);
                }
                return ['success' => false, 'error' => $result['error'] ?? 'Помилка завантаження файлу'];
            }
            
            // Отримуємо відносний шлях від теми
            $relativePath = str_replace($realThemePath, '', $result['file']);
            $relativePath = str_replace('\\', '/', $relativePath);
            $relativePath = ltrim($relativePath, '/');
            
            if (class_exists('Logger')) {
                Logger::getInstance()->logInfo('ThemeEditorManager: File uploaded successfully', [
                    'fileName' => $result['name'] ?? $fileName,
                    'path' => $relativePath,
                    'size' => $result['size'] ?? 0
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Файл успішно завантажено',
                'path' => $relativePath,
                'name' => $result['name'] ?? $fileName,
                'size' => $result['size'] ?? 0
            ];
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorManager: Exception in uploadFile', [
                    'error' => $e->getMessage(),
                    'folderPath' => $folderPath,
                    'themePath' => $themePath
                ]);
            }
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Створення ZIP архіву папки
     */
    public function createFolderZip(string $folderPath, string $themePath): ?string {
        try {
            // Нормалізуємо шлях до папки
            $folderPath = trim($folderPath, '/\\');
            $fullFolderPath = rtrim($themePath, '/\\') . '/' . $folderPath;
            
            // Перевірка безпеки шляху
            $realThemePath = realpath($themePath);
            $realFolderPath = realpath($fullFolderPath);
            
            if ($realThemePath === false || $realFolderPath === false) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('ThemeEditorManager: Invalid paths', ['theme' => $themePath, 'folder' => $fullFolderPath]);
                } else {
                    error_log("ThemeEditorManager: Invalid paths - theme: {$themePath}, folder: {$fullFolderPath}");
                }
                return null;
            }
            
            // Перевіряємо безпеку шляху через клас File
            $folderFile = new File($realFolderPath);
            if (!$folderFile->isPathSafe($realThemePath)) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logWarning('ThemeEditorManager: Folder path is outside theme directory', ['folderPath' => $realFolderPath, 'themePath' => $realThemePath]);
                } else {
                    error_log("ThemeEditorManager: Folder path is outside theme directory");
                }
                return null;
            }
            
            if (!is_dir($realFolderPath) || !is_readable($realFolderPath)) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('ThemeEditorManager: Folder does not exist or is not readable', ['folderPath' => $realFolderPath]);
                } else {
                    error_log("ThemeEditorManager: Folder does not exist or is not readable: {$realFolderPath}");
                }
                return null;
            }
            
            // Створюємо тимчасовий файл для архіву
            $tempDir = sys_get_temp_dir();
            if (!is_writable($tempDir)) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('ThemeEditorManager: Temporary directory is not writable', ['tempDir' => $tempDir]);
                } else {
                    error_log("ThemeEditorManager: Temporary directory is not writable: {$tempDir}");
                }
                return null;
            }
            
            $zipPath = rtrim($tempDir, '/\\') . DIRECTORY_SEPARATOR . 'theme_folder_' . uniqid() . '.zip';
            
            // Створюємо ZIP архів використовуючи клас Zip
            try {
                $zip = new Zip($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                
                // Додаємо директорію до архіву
                $folderFile = new File($realFolderPath);
                $baseName = $folderFile->getBasename();
                $zip->addDirectory($realFolderPath, $baseName);
                
                // Закриваємо архів
                if (!$zip->close()) {
                    if (class_exists('Logger')) {
                        Logger::getInstance()->logError('ThemeEditorManager: Failed to close ZIP archive', ['zipPath' => $zipPath]);
                    } else {
                        error_log("ThemeEditorManager: Failed to close ZIP archive");
                    }
                    @unlink($zipPath);
                    return null;
                }
                
                // Перевіряємо, що файл архіву створено і доступний
                $zipFile = new File($zipPath);
                if (!$zipFile->exists() || !$zipFile->isReadable()) {
                    if (class_exists('Logger')) {
                        Logger::getInstance()->logError('ThemeEditorManager: ZIP archive file was not created or is not readable', ['zipPath' => $zipPath]);
                    } else {
                        error_log("ThemeEditorManager: ZIP archive file was not created or is not readable: {$zipPath}");
                    }
                    return null;
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('ThemeEditorManager: Error creating ZIP archive', ['error' => $e->getMessage(), 'zipPath' => $zipPath]);
                } else {
                    error_log("ThemeEditorManager: Error creating ZIP archive: " . $e->getMessage());
                }
                if (file_exists($zipPath)) {
                    @unlink($zipPath);
                }
                return null;
            }
            
            return $zipPath;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorManager: Exception in createFolderZip', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'folderPath' => $folderPath,
                    'themePath' => $themePath
                ]);
            } else {
                error_log("ThemeEditorManager: Exception in createFolderZip: " . $e->getMessage());
                error_log("ThemeEditorManager: Stack trace: " . $e->getTraceAsString());
            }
            return null;
        }
    }
}

