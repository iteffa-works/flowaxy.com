<?php
/**
 * Клас керування медіафайлами для плагіна
 * 
 * @package Plugins\MediaLibrary
 * @version 1.0.0
 */

declare(strict_types=1);

// Перевіряємо та завантажуємо клас Directory якщо потрібно
if (!class_exists('Directory')) {
    $directoryClassFile = dirname(__DIR__, 2) . '/engine/classes/files/Directory.php';
    if (file_exists($directoryClassFile)) {
        require_once $directoryClassFile;
    }
}

class Media {
    private $db;
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
     * Конструктор
     */
    public function __construct() {
        $this->uploadsDir = rtrim(UPLOADS_DIR, '/') . '/';
        $host = $_SERVER['HTTP_HOST'] ?? 'spokinoki.local';
        $this->uploadsUrl = '//' . $host . '/uploads/';
        $this->ensureUploadsDir();
    }
    
    /**
     * Отримання підключення до БД
     */
    protected function getDB(): ?PDO {
        if ($this->db === null) {
            $this->db = DatabaseHelper::getConnection();
        }
        return $this->db;
    }
    
    
    /**
     * Обробник хука для рендерингу селектора медіа
     * 
     * @param array $params Параметри хука
     * @return string HTML
     */
    public function hookRenderMediaSelector($params = null): string {
        if (!is_array($params)) {
            $params = [];
        }
        
        $targetInputId = $params['targetInputId'] ?? 'mediaTargetInput';
        $previewContainerId = $params['previewContainerId'] ?? '';
        $mediaType = $params['mediaType'] ?? 'image';
        
        return $this->renderMediaSelector($targetInputId, $previewContainerId, $mediaType);
    }
    
    /**
     * Обробник хука для отримання файлів
     * 
     * @param array $params Параметри хука
     * @return array
     */
    public function hookGetMediaFiles($params = null): array {
        if (!is_array($params)) {
            $params = [];
        }
        
        $filters = $params['filters'] ?? $params;
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 24;
        
        return $this->getFiles($filters, $page, $perPage);
    }
    
    /**
     * Обробник хука для завантаження файлу
     * 
     * @param array $params Параметри хука
     * @return array
     */
    public function hookUploadMediaFile($params = null): array {
        if (!is_array($params)) {
            return ['success' => false, 'error' => 'Невірні параметри'];
        }
        
        $file = $params['file'] ?? null;
        $title = $params['title'] ?? null;
        $description = $params['description'] ?? '';
        $alt = $params['alt'] ?? '';
        
        if (!$file || !isset($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Файл не передано'];
        }
        
        return $this->uploadFile($file, $title, $description, $alt);
    }
    
    
    /**
     * Рендеринг HTML списку медіафайлів
     * 
     * @param array $files Масив файлів
     * @param string $viewMode Режим відображення: 'grid' або 'list'
     * @return string HTML
     */
    public function renderMediaItems(array $files, string $viewMode = 'grid'): string {
        if (empty($files)) {
            return '<div class="media-empty-state">
                <div class="media-empty-icon"><i class="fas fa-images"></i></div>
                <h4 class="media-empty-title">Медіафайли відсутні</h4>
                <p class="media-empty-description">Завантажте перший файл, щоб почати роботу з медіа-бібліотекою.</p>
            </div>';
        }
        
        ob_start();
        include __DIR__ . '/templates/media-items.php';
        return ob_get_clean();
    }
    
    /**
     * Рендеринг селектора медіафайлів для вбудовування в інші модулі
     * 
     * @param string $targetInputId ID інпута для вставки URL
     * @param string $previewContainerId ID контейнера для прев'ю
     * @param string $mediaType Тип медіа ('image', 'video', 'audio' або '')
     * @return string HTML
     */
    public function renderMediaSelector(string $targetInputId, string $previewContainerId = '', string $mediaType = 'image'): string {
        // Кешування для покращення продуктивності
        $cacheKey = 'media_selector_' . md5($targetInputId . $previewContainerId . $mediaType);
        if (function_exists('cache_get') && function_exists('cache_set')) {
            $cached = cache_get($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $mediaModule = $this;
        $filters = ['media_type' => $mediaType];
        $result = $mediaModule->getFiles($filters, 1, 24);
        $files = $result['files'];
        
        ob_start();
        ?>
        <div class="media-selector-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="flex-grow-1 me-3">
                    <input type="text" 
                           class="form-control media-selector-search" 
                           placeholder="Пошук <?= $mediaType ?: 'файлів' ?>...">
                </div>
                <div>
                    <button type="button" class="btn btn-primary media-selector-upload-btn">
                        <i class="fas fa-upload me-2"></i>Завантажити
                    </button>
                    <input type="file" 
                           class="d-none media-selector-file-input" 
                           accept="<?= $mediaType === 'image' ? 'image/*' : ($mediaType === 'video' ? 'video/*' : ($mediaType === 'audio' ? 'audio/*' : '')) ?>" 
                           multiple>
                </div>
            </div>
            
            <div class="row media-selector-grid">
                <?php foreach ($files as $file): ?>
                    <div class="col-md-2 col-sm-3 col-4 mb-3">
                        <div class="media-selector-item" 
                             data-url="<?= htmlspecialchars(UrlHelper::toProtocolRelative($file['file_url'])) ?>"
                             data-id="<?= $file['id'] ?>"
                             data-target="<?= htmlspecialchars($targetInputId) ?>"
                             data-preview="<?= htmlspecialchars($previewContainerId) ?>">
                            <?php if ($file['media_type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars(UrlHelper::toProtocolRelative($file['file_url'])) ?>" 
                                     alt="<?= htmlspecialchars($file['title'] ?? '') ?>"
                                     class="img-thumbnail w-100"
                                     loading="lazy"
                                     decoding="async">
                            <?php else: ?>
                                <div class="media-selector-icon">
                                    <i class="fas fa-<?= $file['media_type'] === 'video' ? 'video' : ($file['media_type'] === 'audio' ? 'music' : 'file') ?> fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        // Зберігаємо в кеш на 5 хвилин
        if (function_exists('cache_set')) {
            cache_set($cacheKey, $html, 300);
        }
        
        return $html;
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
            try {
                // Перевіряємо чи клас Directory завантажений
                if (!class_exists('Directory')) {
                    // Явно завантажуємо клас
                    $directoryClassFile = dirname(__DIR__, 2) . '/engine/classes/files/Directory.php';
                    if (file_exists($directoryClassFile)) {
                        require_once $directoryClassFile;
                    }
                }
                
                if (class_exists('Directory')) {
                    $directory = new Directory($dir);
                    if (method_exists($directory, 'exists') && !$directory->exists()) {
                        if (method_exists($directory, 'create')) {
                            $directory->create(0755, true);
                        } else {
                            // Fallback
                            if (!is_dir($dir)) {
                                @mkdir($dir, 0755, true);
                            }
                        }
                    }
                } else {
                    // Fallback на стандартні PHP функції
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                }
            } catch (Exception $e) {
                error_log("Media: Failed to create directory {$dir}: " . $e->getMessage());
                // Fallback
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
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
        
        // Перевірка типу файлу
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        if (empty($extension) || !$this->isAllowedType($extension)) {
            return ['success' => false, 'error' => 'Тип файлу не дозволено: ' . $extension];
        }
        
        // Підготовка директорії для завантаження
        $year = date('Y');
        $month = date('m');
        $uploadSubDir = $year . '/' . $month . '/';
        $uploadDir = $this->uploadsDir . $uploadSubDir;
        
        // Створюємо директорію через клас Directory
        try {
            // Перевіряємо чи клас Directory завантажений
            if (!class_exists('Directory')) {
                // Явно завантажуємо клас
                $directoryClassFile = dirname(__DIR__, 2) . '/engine/classes/files/Directory.php';
                if (file_exists($directoryClassFile)) {
                    require_once $directoryClassFile;
                }
            }
            
            if (class_exists('Directory')) {
                $directory = new Directory($uploadDir);
                if (method_exists($directory, 'exists') && !$directory->exists()) {
                    if (method_exists($directory, 'create')) {
                        $directory->create(0755, true);
                    } else {
                        // Fallback
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0755, true);
                        }
                    }
                }
            } else {
                // Fallback на стандартні PHP функції
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0755, true)) {
                        return ['success' => false, 'error' => 'Помилка створення директорії для завантаження'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Media: Failed to create upload directory: " . $e->getMessage());
            // Fallback на стандартні PHP функції
            if (!is_dir($uploadDir)) {
                if (!@mkdir($uploadDir, 0755, true)) {
                    return ['success' => false, 'error' => 'Помилка створення директорії для завантаження'];
                }
            }
        }
        
        // Використовуємо клас Upload для завантаження
        $upload = new Upload($uploadDir);
        $upload->setMaxFileSize($this->maxFileSize);
        
        // Встановлюємо дозволені розширення
        $allowedExtensions = [];
        foreach ($this->allowedTypes as $types) {
            $allowedExtensions = array_merge($allowedExtensions, $types);
        }
        $upload->setAllowedExtensions($allowedExtensions);
        
        // Встановлюємо дозволені MIME типи для додаткової безпеки
        $allowedMimeTypes = [];
        foreach ($this->allowedTypes as $type => $extensions) {
            foreach ($extensions as $ext) {
                // Отримуємо всі можливі MIME типи для розширення
                $mimeTypes = MimeType::getAllMimeTypes($ext);
                foreach ($mimeTypes as $mimeType) {
                    if ($mimeType) {
                        $allowedMimeTypes[] = $mimeType;
                    }
                }
            }
        }
        if (!empty($allowedMimeTypes)) {
            $upload->setAllowedMimeTypes(array_unique($allowedMimeTypes));
        }
        
        // Генерація унікального імені файлу
        $originalName = $fileInfo['filename'] ?? 'file';
        $fileName = $this->generateFileName($originalName, $extension);
        
        // Завантаження файлу через клас Upload
        $uploadResult = $upload->upload($file, $fileName);
        
        if (!$uploadResult['success']) {
            return ['success' => false, 'error' => $uploadResult['error'] ?? 'Помилка завантаження файлу'];
        }
        
        $fullPath = $uploadResult['file'] ?? '';
        if (empty($fullPath)) {
            return ['success' => false, 'error' => 'Помилка: файл не було завантажено'];
        }
        
        // Отримуємо ім'я файлу з повного шляху
        $uploadedFileName = basename($fullPath);
        $uploadPath = $uploadSubDir . $uploadedFileName;
        
        // Визначення типу медіа
        $mediaType = $this->getMediaType($extension);
        
        // Отримання розмірів для зображень через клас Image
        $width = null;
        $height = null;
        $fileSize = $file['size'] ?? filesize($fullPath);
        $mimeType = $uploadResult['mime_type'] ?? MimeType::get($fullPath);
        
        if ($mediaType === 'image') {
            try {
                $image = new Image($fullPath);
                $width = $image->getWidth();
                $height = $image->getHeight();
                $mimeType = $image->getMimeType();
                
                // Перевірка розміру зображення (захист від дуже великих зображень)
                $maxImageWidth = 10000;
                $maxImageHeight = 10000;
                if ($width > $maxImageWidth || $height > $maxImageHeight) {
                    error_log("Media: Image dimensions too large: {$width}x{$height}");
                    // Можна додати автоматичне зменшення, але поки просто логуємо
                }
            } catch (Exception $e) {
                error_log("Media: Failed to get image info: " . $e->getMessage());
                // Fallback на getimagesize
                $imageInfo = @getimagesize($fullPath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    if (isset($imageInfo['mime'])) {
                        $mimeType = $imageInfo['mime'];
                    }
                }
            }
        }
        
        // Збереження інформації в БД
        $title = $title ?: $originalName;
        $db = $this->getDB();
        if (!$db) {
            // Видаляємо файл якщо БД недоступна
            try {
                $fileObj = new File($fullPath);
                $fileObj->delete();
            } catch (Exception $e) {
                @unlink($fullPath);
            }
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
                $uploadResult['name'],
                SecurityHelper::sanitizeInput($file['name']),
                $uploadPath,
                $fileUrl,
                $fileSize,
                $mimeType,
                $mediaType,
                $width,
                $height,
                SecurityHelper::sanitizeInput($title),
                SecurityHelper::sanitizeInput($description),
                SecurityHelper::sanitizeInput($alt),
                $uploadedBy
            ]);
            
            $mediaId = $db->lastInsertId();
            
            // Очищаємо кеш після додавання нового файлу
            if (function_exists('cache_clear')) {
                cache_clear('media_files_');
                cache_clear('media_selector_');
            }
            
            return [
                'success' => true,
                'id' => $mediaId,
                'file_url' => $fileUrl,
                'file_path' => $uploadPath,
                'filename' => $uploadedFileName,
                'media_type' => $mediaType,
                'width' => $width,
                'height' => $height,
                'file_size' => $fileSize
            ];
        } catch (Exception $e) {
            // Видаляємо файл при помилці БД
            try {
                $fileObj = new File($fullPath);
                $fileObj->delete();
            } catch (Exception $fileEx) {
                @unlink($fullPath);
            }
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
            
            // Видалення файлу через клас File
            $fullPath = $this->uploadsDir . $file['file_path'];
            try {
                $fileObj = new File($fullPath);
                if ($fileObj->exists()) {
                    $fileObj->delete();
                }
            } catch (Exception $e) {
                error_log("Media: Failed to delete file using File class: " . $e->getMessage());
                // Fallback
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            
            $stmt = $db->prepare("DELETE FROM media_files WHERE id = ?");
            $stmt->execute([$mediaId]);
            
            // Очищаємо кеш після видалення файлу
            if (function_exists('cache_clear')) {
                cache_clear('media_files_');
                cache_clear('media_selector_');
                cache_clear('media_file_' . $mediaId);
            }
            
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
        
        // Кешування для покращення продуктивності
        $cacheKey = 'media_file_' . $mediaId;
        if (function_exists('cache_get') && function_exists('cache_set')) {
            $cached = cache_get($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        try {
            $db = $this->getDB();
            if (!$db) {
                return null;
            }
            $stmt = $db->prepare("SELECT * FROM media_files WHERE id = ?");
            $stmt->execute([$mediaId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($file) {
                // Перевіряємо, чи файл існує на диску
                if (!empty($file['file_path'])) {
                    $fullPath = $this->uploadsDir . $file['file_path'];
                    if (!file_exists($fullPath)) {
                        error_log("Media: File not found on disk: {$fullPath} (ID: {$mediaId})");
                        // Файл видалений з диску, але запис залишився в БД
                        // Можна додати автоматичне видалення запису, але поки просто логуємо
                    }
                }
                
                if (!empty($file['file_url'])) {
                    $file['file_url'] = UrlHelper::toProtocolRelative($file['file_url']);
                }
                
                // Зберігаємо в кеш на 5 хвилин
                if (function_exists('cache_set')) {
                    cache_set($cacheKey, $file, 300);
                }
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
    public function getFiles($filters = [], $page = 1, $perPage = 24, $skipCache = false) {
        // Кешування для покращення продуктивності (тільки якщо не пропущено)
        if (!$skipCache) {
            $cacheKey = 'media_files_' . md5(serialize($filters) . $page . $perPage);
            if (function_exists('cache_get') && function_exists('cache_set')) {
                $cached = cache_get($cacheKey);
                if ($cached !== false && is_array($cached)) {
                    // Перевіряємо, чи є всі необхідні ключі
                    if (isset($cached['files']) && isset($cached['total']) && isset($cached['page']) && isset($cached['pages'])) {
                        return $cached;
                    }
                    // Якщо структура некоректна, очищаємо кеш
                    if (function_exists('cache_delete')) {
                        cache_delete($cacheKey);
                    }
                }
            }
        }
        
        try {
            $where = [];
            $params = [];
            
            if (!empty($filters['media_type']) && isset($this->allowedTypes[$filters['media_type']])) {
                $where[] = "media_type = ?";
                $params[] = $filters['media_type'];
            }
            
            if (!empty($filters['search'])) {
                $where[] = "(title LIKE ? OR original_name LIKE ? OR description LIKE ?)";
                $search = '%' . SecurityHelper::sanitizeInput($filters['search']) . '%';
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
                return [
                    'files' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                    'pages' => 0
                ];
            }
            
            $countStmt = $db->prepare("SELECT COUNT(*) FROM media_files $whereClause");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            
            $page = max(1, (int)$page);
            $perPage = max(1, min(100, (int)$perPage));
            $offset = ($page - 1) * $perPage;
            
            // Дозволені поля для сортування
            $allowedOrderBy = [
                'uploaded_at' => 'uploaded_at',
                'title' => 'title',
                'file_size' => 'file_size',
                'media_type' => 'media_type'
            ];
            
            $orderByField = $filters['order_by'] ?? 'uploaded_at';
            $orderBy = $allowedOrderBy[$orderByField] ?? 'uploaded_at';
            $orderDir = strtoupper($filters['order_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
            
            // Використовуємо бібліотеку Database для безпечного формування запиту
            $stmt = $db->prepare("
                SELECT * FROM media_files 
                $whereClause 
                ORDER BY `$orderBy` $orderDir 
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute(array_merge($params, [$perPage, $offset]));
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                if (!empty($file['file_url'])) {
                    $file['file_url'] = UrlHelper::toProtocolRelative($file['file_url']);
                }
            }
            unset($file);
            
            $result = [
                'files' => $files,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => $total > 0 ? ceil($total / $perPage) : 0
            ];
            
            // Зберігаємо в кеш на 2 хвилини (тільки якщо не пропущено)
            if (!$skipCache && function_exists('cache_set')) {
                $cacheKey = 'media_files_' . md5(serialize($filters) . $page . $perPage);
                cache_set($cacheKey, $result, 120);
            }
            
            return $result;
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
                    $params[] = SecurityHelper::sanitizeInput($data[$field]);
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
            
            // Очищаємо кеш після оновлення файлу
            if (function_exists('cache_clear')) {
                cache_clear('media_files_');
                cache_clear('media_selector_');
                cache_clear('media_file_' . $mediaId);
            }
            
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

