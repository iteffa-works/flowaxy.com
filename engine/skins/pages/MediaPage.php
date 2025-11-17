<?php
/**
 * Сторінка керування медіафайлами
 * 
 * @package Admin
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class MediaPage extends AdminPage {
    
    private $mediaModule;
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Медіафайли - Flowaxy CMS';
        $this->templateName = 'media';
        
        $this->setPageHeader(
            'Медіафайли',
            'Керування медіафайлами системи',
            'fas fa-images'
        );
        
        $this->mediaModule = mediaModule();
        
        // Додаємо CSS та JS для медіа
        $this->additionalCSS[] = 'styles/media.css?v=' . time();
        $this->additionalJS[] = 'scripts/media.js?v=' . time();
    }
    
    /**
     * Обробка запиту
     */
    public function handle() {
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обробка звичайних POST запитів
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $this->handlePost();
        }
        
        // Отримання параметрів фільтрації
        $filters = [
            'media_type' => SecurityHelper::sanitizeInput($_GET['type'] ?? ''),
            'search' => SecurityHelper::sanitizeInput($_GET['search'] ?? ''),
            'date_from' => SecurityHelper::sanitizeInput($_GET['date_from'] ?? ''),
            'date_to' => SecurityHelper::sanitizeInput($_GET['date_to'] ?? ''),
            'order_by' => SecurityHelper::sanitizeInput($_GET['order_by'] ?? 'uploaded_at'),
            'order_dir' => SecurityHelper::sanitizeInput($_GET['order_dir'] ?? 'DESC')
        ];
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 24;
        
        // Отримання файлів
        $result = $this->mediaModule->getFiles($filters, $page, $perPage);
        
        // Отримання статистики
        $stats = $this->mediaModule->getStats();
        
        // Рендеринг сторінки
        $this->render([
            'files' => $result['files'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => $filters,
            'stats' => $stats
        ]);
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax() {
        // Використовуємо Response клас для встановлення заголовків
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        $action = SecurityHelper::sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
        
        switch ($action) {
            case 'upload':
                $this->handleUpload();
                break;
                
            case 'delete':
                $this->handleDelete();
                break;
                
            case 'update':
                $this->handleUpdate();
                break;
                
            case 'get_file':
                $this->handleGetFile();
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    /**
     * Обробка завантаження файлу
     */
    private function handleUpload() {
        if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
            echo json_encode(['success' => false, 'error' => 'Файл не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $title = !empty($_POST['title']) ? SecurityHelper::sanitizeInput($_POST['title']) : null;
        $description = SecurityHelper::sanitizeInput($_POST['description'] ?? '');
        $alt = SecurityHelper::sanitizeInput($_POST['alt_text'] ?? '');
        
        $result = $this->mediaModule->uploadFile($_FILES['file'], $title, $description, $alt);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обробка видалення файлу
     */
    private function handleDelete() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
        
        if (!$mediaId) {
            echo json_encode(['success' => false, 'error' => 'ID файлу не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $result = $this->mediaModule->deleteFile($mediaId);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обробка оновлення файлу
     */
    private function handleUpdate() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
        $data = [
            'title' => SecurityHelper::sanitizeInput($_POST['title'] ?? ''),
            'description' => SecurityHelper::sanitizeInput($_POST['description'] ?? ''),
            'alt_text' => SecurityHelper::sanitizeInput($_POST['alt_text'] ?? '')
        ];
        
        if (!$mediaId) {
            echo json_encode(['success' => false, 'error' => 'ID файлу не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $result = $this->mediaModule->updateFile($mediaId, $data);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Отримання інформації про файл
     */
    private function handleGetFile() {
        $mediaId = isset($_GET['media_id']) ? (int)$_GET['media_id'] : 0;
        
        if (!$mediaId) {
            echo json_encode(['success' => false, 'error' => 'ID файлу не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $file = $this->mediaModule->getFile($mediaId);
        
        if ($file) {
            echo json_encode(['success' => true, 'file' => $file], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Файл не знайдено'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * Обробка звичайних POST запитів
     */
    private function handlePost() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $action = SecurityHelper::sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'delete') {
            $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
            if ($mediaId) {
                $result = $this->mediaModule->deleteFile($mediaId);
                if ($result['success']) {
                    $this->setMessage('Файл видалено', 'success');
                } else {
                    $this->setMessage('Помилка видалення: ' . $result['error'], 'danger');
                }
            }
        }
    }
}
