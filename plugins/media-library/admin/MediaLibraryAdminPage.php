<?php
/**
 * Сторінка керування медіафайлами (для плагіна)
 * 
 * @package Plugins\MediaLibrary\Admin
 * @version 1.0.0
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';
require_once dirname(__DIR__) . '/Media.php';

class MediaLibraryAdminPage extends AdminPage {
    
    private $mediaModule;
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Медіафайли - Flowaxy CMS';
        $this->templateName = 'media';
        
        $headerButtons = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i>Завантажити файли
        </button>';
        
        $this->setPageHeader(
            'Медіафайли',
            'Керування медіафайлами системи',
            'fas fa-images',
            $headerButtons
        );
        
        $this->mediaModule = new Media();
        
        // Додаємо CSS та JS для медіа (з плагіна)
        $pluginUrl = '/plugins/media-library';
        $this->additionalCSS[] = $pluginUrl . '/assets/css/media.css?v=' . time();
        $this->additionalJS[] = $pluginUrl . '/assets/js/media.js?v=' . time();
    }
    
    /**
     * Перевизначення шляху до шаблонів для плагіна
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
    
    /**
     * Обробка запиту
     */
    public function handle() {
        // Обробка GET запиту для отримання інформації про файл (для модальних вікон)
        if (!empty($_GET['action']) && $_GET['action'] === 'get_file' && !empty($_GET['media_id'])) {
            $mediaId = (int)$_GET['media_id'];
            
            if ($mediaId <= 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Невірний ID файлу']);
                exit;
            }
            
            $file = $this->mediaModule->getFile($mediaId);
            
            header('Content-Type: application/json');
            if ($file && is_array($file) && !empty($file['id'])) {
                // Переконуємося, що file_url правильно сформований
                if (!empty($file['file_url']) && !preg_match('/^https?:\/\//', $file['file_url']) && !preg_match('/^\/\//', $file['file_url'])) {
                    $file['file_url'] = '//' . ltrim($file['file_url'], '/');
                }
                echo json_encode(['success' => true, 'file' => $file], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode(['success' => false, 'error' => 'Файл не знайдено'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        // Обробка звичайних POST запитів
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $this->handlePost();
            return; // Після обробки POST робимо редирект
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
        
        // Обробка сортування з select
        if (!empty($_GET['sort'])) {
            $sortParts = explode('_', $_GET['sort']);
            if (count($sortParts) === 2) {
                $filters['order_by'] = $sortParts[0];
                $filters['order_dir'] = strtoupper($sortParts[1]);
            }
        }
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(12, min(96, (int)$_GET['per_page'])) : 24;
        
        // Отримання файлів
        $result = $this->mediaModule->getFiles($filters, $page, $perPage);
        
        // Перевірка наявності результату та валідація структури
        if (!is_array($result)) {
            $result = [
                'files' => [],
                'total' => 0,
                'page' => 1,
                'pages' => 0
            ];
        }
        
        // Забезпечуємо наявність всіх необхідних ключів
        $result = array_merge([
            'files' => [],
            'total' => 0,
            'page' => 1,
            'pages' => 0
        ], $result);
        
        // Рендеринг сторінки (шаблон з плагіна)
        $this->render([
            'files' => $result['files'] ?? [],
            'total' => $result['total'] ?? 0,
            'page' => $result['page'] ?? 1,
            'pages' => $result['pages'] ?? 0,
            'filters' => $filters
        ]);
    }
    
    /**
     * Обробка звичайних POST запитів
     */
    private function handlePost() {
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setMessage('Помилка безпеки', 'danger');
            Response::redirectStatic(UrlHelper::admin('media-library'));
            return;
        }
        
        $action = SecurityHelper::sanitizeInput($_POST['action'] ?? '');
        
        // Обробка завантаження файлів
        if ($action === 'upload') {
            if (empty($_FILES['file'])) {
                $this->setMessage('Файл не вибрано', 'danger');
            } else {
                $title = !empty($_POST['title']) ? SecurityHelper::sanitizeInput($_POST['title']) : null;
                $description = SecurityHelper::sanitizeInput($_POST['description'] ?? '');
                $alt = SecurityHelper::sanitizeInput($_POST['alt_text'] ?? '');
                
                // Перевіряємо, чи це масив файлів (multiple upload)
                $files = $_FILES['file'];
                $uploadedCount = 0;
                $errorCount = 0;
                $errors = [];
                
                if (is_array($files['name'])) {
                    // Множинне завантаження
                    $fileCount = count($files['name']);
                    for ($i = 0; $i < $fileCount; $i++) {
                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        if ($file['error'] === UPLOAD_ERR_OK) {
                            $result = $this->mediaModule->uploadFile($file, $title, $description, $alt);
                            if ($result['success']) {
                                $uploadedCount++;
                            } else {
                                $errorCount++;
                                $errors[] = $file['name'] . ': ' . ($result['error'] ?? 'Невідома помилка');
                            }
                        } else {
                            $errorCount++;
                            $errors[] = $file['name'] . ': Помилка завантаження';
                        }
                    }
                } else {
                    // Одиночне завантаження
                    $result = $this->mediaModule->uploadFile($files, $title, $description, $alt);
                    if ($result['success']) {
                        $uploadedCount = 1;
                    } else {
                        $errorCount = 1;
                        $errors[] = $result['error'] ?? 'Невідома помилка';
                    }
                }
                
                // Формуємо повідомлення
                if ($uploadedCount > 0) {
                    $message = "Завантажено файлів: $uploadedCount";
                    if ($errorCount > 0) {
                        $message .= " (помилок: $errorCount)";
                        if (!empty($errors)) {
                            $message .= ': ' . implode(', ', array_slice($errors, 0, 3));
                            if (count($errors) > 3) {
                                $message .= '...';
                            }
                        }
                        $this->setMessage($message, 'warning');
                    } else {
                        $this->setMessage($message, 'success');
                    }
                } else {
                    $errorMsg = 'Помилка завантаження файлів';
                    if (!empty($errors)) {
                        $errorMsg .= ': ' . implode(', ', array_slice($errors, 0, 3));
                    }
                    $this->setMessage($errorMsg, 'danger');
                }
            }
        }
        
        // Обробка видалення файлу
        if ($action === 'delete') {
            $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
            if ($mediaId) {
                $result = $this->mediaModule->deleteFile($mediaId);
                if ($result['success']) {
                    $this->setMessage('Файл видалено', 'success');
                } else {
                    $this->setMessage('Помилка видалення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
                }
            } else {
                $this->setMessage('ID файлу не вказано', 'danger');
            }
        }
        
        // Обробка оновлення файлу
        if ($action === 'update') {
            $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
            if ($mediaId) {
                $data = [
                    'title' => SecurityHelper::sanitizeInput($_POST['title'] ?? ''),
                    'description' => SecurityHelper::sanitizeInput($_POST['description'] ?? ''),
                    'alt_text' => SecurityHelper::sanitizeInput($_POST['alt_text'] ?? '')
                ];
                
                $result = $this->mediaModule->updateFile($mediaId, $data);
                
                if ($result['success']) {
                    $this->setMessage('Файл оновлено', 'success');
                } else {
                    $this->setMessage('Помилка оновлення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
                }
            } else {
                $this->setMessage('ID файлу не вказано', 'danger');
            }
        }
        
        // Обробка масового видалення
        if ($action === 'bulk_delete') {
            $mediaIds = $_POST['media_ids'] ?? [];
            if (empty($mediaIds) || !is_array($mediaIds)) {
                $this->setMessage('Файли не вибрані', 'danger');
            } else {
                $deleted = 0;
                $errors = 0;
                
                foreach ($mediaIds as $mediaId) {
                    $mediaId = (int)$mediaId;
                    if ($mediaId > 0) {
                        $result = $this->mediaModule->deleteFile($mediaId);
                        if ($result['success']) {
                            $deleted++;
                        } else {
                            $errors++;
                        }
                    }
                }
                
                if ($deleted > 0) {
                    $this->setMessage("Видалено файлів: $deleted" . ($errors > 0 ? " (помилок: $errors)" : ''), $errors > 0 ? 'warning' : 'success');
                } else {
                    $this->setMessage('Помилка видалення файлів', 'danger');
                }
            }
        }
        
        // Редирект на ту саму сторінку з параметрами
        $redirectUrl = UrlHelper::admin('media-library');
        $queryParams = [];
        
        if (!empty($_GET['page'])) {
            $queryParams['page'] = (int)$_GET['page'];
        }
        if (!empty($_GET['type'])) {
            $queryParams['type'] = SecurityHelper::sanitizeInput($_GET['type']);
        }
        if (!empty($_GET['search'])) {
            $queryParams['search'] = SecurityHelper::sanitizeInput($_GET['search']);
        }
        if (!empty($_GET['sort'])) {
            $queryParams['sort'] = SecurityHelper::sanitizeInput($_GET['sort']);
        }
        if (!empty($_GET['per_page'])) {
            $queryParams['per_page'] = (int)$_GET['per_page'];
        }
        
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
        
        Response::redirectStatic($redirectUrl);
    }
}

