<?php
/**
 * Класс для определения MIME типов файлов
 * Определение типов файлов по расширению и содержимому
 * 
 * @package Engine\Classes\Files
 * @version 1.0.0
 */

declare(strict_types=1);

class MimeType {
    /**
     * Карта расширений файлов к MIME типам
     * 
     * @var array
     */
    private static array $extensionMap = [
        // Изображения
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff',
        
        // Аудио
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'flac' => 'audio/flac',
        
        // Видео
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        
        // Документы
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        
        // Текстовые файлы
        'txt' => 'text/plain',
        'html' => 'text/html',
        'htm' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'csv' => 'text/csv',
        
        // Архивы
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        
        // Другое
        'php' => 'application/x-httpd-php',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
    ];
    
    /**
     * Определение MIME типа по расширению файла
     * 
     * @param string $filePath Путь к файлу или расширение
     * @return string|false MIME тип или false
     */
    public static function fromExtension(string $filePath) {
        // Если передан путь к файлу, извлекаем расширение
        if (strpos($filePath, '.') !== false && file_exists($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        } else {
            // Предполагаем, что это расширение
            $extension = strtolower(ltrim($filePath, '.'));
        }
        
        return self::$extensionMap[$extension] ?? false;
    }
    
    /**
     * Определение MIME типа по содержимому файла
     * 
     * @param string $filePath Путь к файлу
     * @return string|false MIME тип или false
     */
    public static function fromContent(string $filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (!is_readable($filePath)) {
            return false;
        }
        
        // Используем mime_content_type если доступно
        if (function_exists('mime_content_type')) {
            $mimeType = @mime_content_type($filePath);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }
        
        // Используем finfo если доступно
        if (function_exists('finfo_file')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = @finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if ($mimeType !== false) {
                    return $mimeType;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Определение MIME типа файла (комбинированный метод)
     * Сначала пытается определить по содержимому, затем по расширению
     * 
     * @param string $filePath Путь к файлу
     * @return string MIME тип или 'application/octet-stream' по умолчанию
     */
    public static function get(string $filePath): string {
        // Пробуем определить по содержимому
        $mimeType = self::fromContent($filePath);
        
        if ($mimeType !== false) {
            return $mimeType;
        }
        
        // Пробуем определить по расширению
        $mimeType = self::fromExtension($filePath);
        
        if ($mimeType !== false) {
            return $mimeType;
        }
        
        return 'application/octet-stream';
    }
    
    /**
     * Проверка, является ли файл изображением
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function isImage(string $filePath): bool {
        $mimeType = self::get($filePath);
        return strpos($mimeType, 'image/') === 0;
    }
    
    /**
     * Проверка, является ли файл видео
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function isVideo(string $filePath): bool {
        $mimeType = self::get($filePath);
        return strpos($mimeType, 'video/') === 0;
    }
    
    /**
     * Проверка, является ли файл аудио
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function isAudio(string $filePath): bool {
        $mimeType = self::get($filePath);
        return strpos($mimeType, 'audio/') === 0;
    }
    
    /**
     * Проверка, является ли файл документом
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function isDocument(string $filePath): bool {
        $mimeType = self::get($filePath);
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.oasis.opendocument',
            'text/plain',
            'text/html',
            'text/css',
        ];
        
        foreach ($documentTypes as $type) {
            if (strpos($mimeType, $type) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Проверка, является ли файл архивом
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    public static function isArchive(string $filePath): bool {
        $mimeType = self::get($filePath);
        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
        ];
        
        return in_array($mimeType, $archiveTypes, true);
    }
    
    /**
     * Получение расширения файла по MIME типу
     * 
     * @param string $mimeType MIME тип
     * @return string|false Расширение или false
     */
    public static function getExtension(string $mimeType) {
        $reverseMap = array_flip(self::$extensionMap);
        return $reverseMap[$mimeType] ?? false;
    }
    
    /**
     * Добавление пользовательского сопоставления расширения и MIME типа
     * 
     * @param string $extension Расширение
     * @param string $mimeType MIME тип
     * @return void
     */
    public static function addMapping(string $extension, string $mimeType): void {
        self::$extensionMap[strtolower($extension)] = $mimeType;
    }
    
    /**
     * Получение всех доступных расширений для MIME типа
     * 
     * @param string $mimeType MIME тип
     * @return array Массив расширений
     */
    public static function getExtensions(string $mimeType): array {
        $extensions = [];
        
        foreach (self::$extensionMap as $ext => $mime) {
            if ($mime === $mimeType || strpos($mime, $mimeType) === 0) {
                $extensions[] = $ext;
            }
        }
        
        return $extensions;
    }
    
    /**
     * Валидация MIME типа файла
     * 
     * @param string $filePath Путь к файлу
     * @param array $allowedMimeTypes Разрешенные MIME типы
     * @return bool
     */
    public static function validate(string $filePath, array $allowedMimeTypes): bool {
        $mimeType = self::get($filePath);
        return in_array($mimeType, $allowedMimeTypes, true);
    }
}

