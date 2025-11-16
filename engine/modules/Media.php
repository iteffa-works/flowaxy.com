<?php
/**
 * Модуль керування медіафайлами
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

// BaseModule тепер завантажується через автозавантажувач з base/BaseModule.php

class Media extends BaseModule {
    private $uploadsDir;
    private $uploadsUrl;
    
    /**
     * Дозволені типи файлів
     */
    private $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
        'video' => ['mp4', 'webm', 'ogg', 'avi', 'mov'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz']
    ];
    
    /**
     * Максимальний розмір файлу (10 MB)
     */
    private $maxFileSize = 10485760;
    
    /**
     * Ініціалізація модуля
     */
    protected function init(): void {
        $this->uploadsDir = rtrim(UPLOADS_DIR, '/') . '/';
        $host = $_SERVER['HTTP_HOST'] ?? 'spokinoki.local';
        $this->uploadsUrl = '//' . $host . '/uploads/';
        $this->ensureUploadsDir();
    }
    
    /**
     * Реєстрація хуків модуля
     */
    public function registerHooks(): void {
        // Реєстрація пункту меню в адмінці
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        
        // Реєстрація сторінки адмінки
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Додавання пункту меню в адмінку
     * 
     * @param array $menu Поточне меню
     * @return array Оновлене меню
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            'href' => adminUrl('media'),
            'icon' => 'fas fa-images',
            'text' => 'Медіа-бібліотека',
            'page' => 'media',
            'order' => 20
        ];
        return $menu;
    }
    
    /**
     * Реєстрація маршруту адмінки
     * 
     * @param Router|null $router Роутер адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return; // Роутер ще не створено
        }
        
        require_once dirname(__DIR__) . '/skins/pages/MediaPage.php';
        $router->add(['GET', 'POST'], 'media', 'MediaPage');
    }
    
    /**
     * Отримання інформації про модуль
     * 
     * @return array
     */
    public function getInfo(): array {
        return [
            'name' => 'Media',
            'title' => 'Медіафайли',
            'description' => 'Керування медіафайлами системи',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Отримання API методів модуля
     * 
     * @return array
     */
    public function getApiMethods(): array {
        return [
            'uploadFile' => 'Завантаження файлу',
            'deleteFile' => 'Видалення файлу',
            'getFile' => 'Отримання файлу за ID',
            'getFiles' => 'Отримання списку файлів з фільтрацією',
            'updateFile' => 'Оновлення інформації про файл',
            'getStats' => 'Отримання статистики медіа'
        ];
    }
    
    /**
     * Створення директорій для завантаження
     */
    private function ensureUploadsDir(): void {
        $year = date('Y');
        $month = date('m');
        
        $directories = [
            $this->uploadsDir,
            $this->uploadsDir . $year,
            $this->uploadsDir . $year . '/' . $month
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Завантаження файлу
     * 
     * @param array $file Масив $_FILES
     * @param string|null $title Назва файлу
     * @param string $description Опис файлу
     * @param string $alt Alt текст для зображень
     * @return array Результат операції
     */
    public function uploadFile($file, $title = null, $description = '', $alt = '') {
        // Перевірка наявності файлу
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Файл не завантажено'];
        }
        
        // Перевірка помилок завантаження
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadError($file['error'])];
        }
        
        // Перевірка розміру файлу
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'Файл занадто великий. Максимальний розмір: ' . $this->formatFileSize($this->maxFileSize)];
        }
        
        // Перевірка типу файлу
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        if (empty($extension) || !$this->isAllowedType($extension)) {
            return ['success' => false, 'error' => 'Тип файлу не дозволено: ' . $extension];
        }
        
        // Генерація унікального імені файлу
        $originalName = $fileInfo['filename'] ?? 'file';
        $fileName = $this->generateFileName($originalName, $extension);
        $year = date('Y');
        $month = date('m');
        $uploadPath = $year . '/' . $month . '/' . $fileName;
        $fullPath = $this->uploadsDir . $uploadPath;
        
        // Переміщення файлу
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Помилка збереження файлу'];
        }
        
        // Визначення типу медіа
        $mediaType = $this->getMediaType($extension);
        
        // Отримання розмірів для зображень
        $width = null;
        $height = null;
        $fileSize = filesize($fullPath);
        $mimeType = $file['type'] ?? mime_content_type($fullPath);
        
        if ($mediaType === 'image') {
            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                if (isset($imageInfo['mime'])) {
                    $mimeType = $imageInfo['mime'];
                }
            }
        }
        
        // Збереження інформації в БД
        $title = $title ?: $originalName;
        $db = $this->getDB();
        if (!$db) {
            return ['success' => false, 'error' => 'База даних недоступна'];
        }
        $stmt = $db->prepare("
            INSERT INTO media_files (
                filename, original_name, file_path, file_url, file_size, 
                mime_type, media_type, width, height, title, description, alt_text,
                uploaded_by, uploaded_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $fileUrl = $this->uploadsUrl . $uploadPath;
        $uploadedBy = $_SESSION['admin_user_id'] ?? 1;
        
        try {
            $stmt->execute([
                $fileName,
                sanitizeInput($file['name']),
                $uploadPath,
                $fileUrl,
                $fileSize,
                $mimeType,
                $mediaType,
                $width,
                $height,
                sanitizeInput($title),
                sanitizeInput($description),
                sanitizeInput($alt),
                $uploadedBy
            ]);
            
            $mediaId = $db->lastInsertId();
            
            return [
                'success' => true,
                'id' => $mediaId,
                'file_url' => $fileUrl,
                'file_path' => $uploadPath,
                'filename' => $fileName,
                'media_type' => $mediaType,
                'width' => $width,
                'height' => $height,
                'file_size' => $fileSize
            ];
        } catch (Exception $e) {
            @unlink($fullPath);
            error_log("Media upload DB error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка збереження інформації про файл'];
        }
    }
    
    /**
     * Видалення медіафайлу
     * 
     * @param int $mediaId ID файлу
     * @return array Результат операції
     */
    public function deleteFile($mediaId) {
        if (empty($mediaId) || !is_numeric($mediaId)) {
            return ['success' => false, 'error' => 'Невірний ID файлу'];
        }
        
        try {
            $db = $this->getDB();
            if (!$db) {
                return ['success' => false, 'error' => 'База даних недоступна'];
            }
            $stmt = $db->prepare("SELECT file_path FROM media_files WHERE id = ?");
            $stmt->execute([$mediaId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                return ['success' => false, 'error' => 'Файл не знайдено'];
            }
            
            $fullPath = $this->uploadsDir . $file['file_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            
            $stmt = $db->prepare("DELETE FROM media_files WHERE id = ?");
            $stmt->execute([$mediaId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Media delete error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка видалення файлу'];
        }
    }
    
    /**
     * Отримання медіафайлу за ID
     * 
     * @param int $mediaId ID файлу
     * @return array|null Дані файлу або null
     */
    public function getFile($mediaId) {
        if (empty($mediaId) || !is_numeric($mediaId)) {
            return null;
        }
        
        try {
            $db = $this->getDB();
            if (!$db) {
                return null;
            }
            $stmt = $db->prepare("SELECT * FROM media_files WHERE id = ?");
            $stmt->execute([$mediaId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($file && !empty($file['file_url'])) {
                $file['file_url'] = toProtocolRelativeUrl($file['file_url']);
            }
            
            return $file ?: null;
        } catch (Exception $e) {
            error_log("Media get error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Отримання списку медіафайлів з фільтрацією та пагінацією
     * 
     * @param array $filters Фільтри
     * @param int $page Номер сторінки
     * @param int $perPage Кількість на сторінці
     * @return array Список файлів та метадані
     */
    public function getFiles($filters = [], $page = 1, $perPage = 24) {
        try {
            $where = [];
            $params = [];
            
            if (!empty($filters['media_type']) && isset($this->allowedTypes[$filters['media_type']])) {
                $where[] = "media_type = ?";
                $params[] = $filters['media_type'];
            }
            
            if (!empty($filters['search'])) {
                $where[] = "(title LIKE ? OR original_name LIKE ? OR description LIKE ?)";
                $search = '%' . sanitizeInput($filters['search']) . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "uploaded_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "uploaded_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $db = $this->getDB();
            if (!$db) {
                return ['files' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
            }
            
            $countStmt = $db->prepare("SELECT COUNT(*) FROM media_files $whereClause");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            
            $page = max(1, (int)$page);
            $perPage = max(1, min(100, (int)$perPage));
            $offset = ($page - 1) * $perPage;
            
            $allowedOrderBy = ['uploaded_at', 'title', 'file_size', 'media_type'];
            $orderBy = in_array($filters['order_by'] ?? 'uploaded_at', $allowedOrderBy) 
                ? $filters['order_by'] 
                : 'uploaded_at';
            $orderDir = strtoupper($filters['order_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
            
            $stmt = $db->prepare("
                SELECT * FROM media_files 
                $whereClause 
                ORDER BY $orderBy $orderDir 
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute(array_merge($params, [$perPage, $offset]));
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                if (!empty($file['file_url'])) {
                    $file['file_url'] = toProtocolRelativeUrl($file['file_url']);
                }
            }
            unset($file);
            
            return [
                'files' => $files,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => $total > 0 ? ceil($total / $perPage) : 0
            ];
        } catch (Exception $e) {
            error_log("Media getFiles error: " . $e->getMessage());
            return [
                'files' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'pages' => 0
            ];
        }
    }
    
    /**
     * Оновлення інформації про медіафайл
     * 
     * @param int $mediaId ID файлу
     * @param array $data Дані для оновлення
     * @return array Результат операції
     */
    public function updateFile($mediaId, $data) {
        if (empty($mediaId) || !is_numeric($mediaId)) {
            return ['success' => false, 'error' => 'Невірний ID файлу'];
        }
        
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['title', 'description', 'alt_text'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = sanitizeInput($data[$field]);
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Немає даних для оновлення'];
            }
            
            $params[] = $mediaId;
            $db = $this->getDB();
            if (!$db) {
                return ['success' => false, 'error' => 'База даних недоступна'];
            }
            $sql = "UPDATE media_files SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Media update error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка оновлення файлу'];
        }
    }
    
    /**
     * Отримання статистики медіа
     * 
     * @return array Статистика
     */
    public function getStats() {
        try {
            $db = $this->getDB();
            if (!$db) {
                return ['total_files' => 0, 'total_size' => 0, 'by_type' => []];
            }
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_files,
                    COALESCE(SUM(file_size), 0) as total_size,
                    media_type,
                    COUNT(*) as type_count
                FROM media_files 
                GROUP BY media_type
            ");
            
            $stats = [
                'total_files' => 0,
                'total_size' => 0,
                'by_type' => []
            ];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $count = (int)($row['type_count'] ?? 0);
                $stats['total_files'] += $count;
                $stats['total_size'] += (int)($row['total_size'] ?? 0);
                $stats['by_type'][$row['media_type'] ?? 'unknown'] = $count;
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Media stats error: " . $e->getMessage());
            return [
                'total_files' => 0,
                'total_size' => 0,
                'by_type' => []
            ];
        }
    }
    
    /**
     * Перевірка дозволеного типу файлу
     */
    private function isAllowedType($extension) {
        foreach ($this->allowedTypes as $types) {
            if (in_array($extension, $types, true)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Визначення типу медіа
     */
    private function getMediaType($extension) {
        foreach ($this->allowedTypes as $type => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return $type;
            }
        }
        return 'other';
    }
    
    /**
     * Генерація унікального імені файлу
     */
    private function generateFileName($originalName, $extension) {
        $name = $this->sanitizeFileName($originalName);
        $fileName = $name . '-' . time() . '-' . substr(md5(uniqid((string)rand(), true)), 0, 8) . '.' . $extension;
        return $fileName;
    }
    
    /**
     * Очищення імені файлу
     */
    private function sanitizeFileName($fileName) {
        $fileName = transliterate($fileName);
        $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $fileName);
        $fileName = preg_replace('/-+/', '-', $fileName);
        $fileName = trim($fileName, '-');
        return $fileName ?: 'file';
    }
    
    /**
     * Отримання тексту помилки завантаження
     */
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл перевищує максимальний розмір, встановлений в php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Файл перевищує максимальний розмір, встановлений в формі',
            UPLOAD_ERR_PARTIAL => 'Файл було завантажено частково',
            UPLOAD_ERR_NO_FILE => 'Файл не було завантажено',
            UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова папка',
            UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск',
            UPLOAD_ERR_EXTENSION => 'Завантаження файлу було зупинено розширенням'
        ];
        
        return $errors[$errorCode] ?? 'Невідома помилка завантаження';
    }
    
    /**
     * Форматування розміру файлу
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max(0, (int)$bytes);
        
        if ($bytes === 0) {
            return '0 B';
        }
        
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

/**
 * Глобальна функція для отримання екземпляру модуля Media
 * 
 * @return Media
 */
function mediaModule() {
    // Завантажуємо модуль за вимогою
    if (!class_exists('Media')) {
        require_once dirname(__DIR__) . '/modules/Media.php';
    }
    
    // Переконуємося, що модуль завантажено через ModuleLoader
    if (!ModuleLoader::isModuleLoaded('Media')) {
        ModuleLoader::loadModule('Media');
    }
    
    return Media::getInstance();
}

/**
 * Функція транслитерації
 */
if (!function_exists('transliterate')) {
    function transliterate($text) {
        $translit = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            'і' => 'i', 'ї' => 'yi', 'є' => 'ye', 'І' => 'I', 'Ї' => 'Yi', 'Є' => 'Ye'
        ];
        
        return strtr($text, $translit);
    }
}

