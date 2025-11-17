<?php
/**
 * Сторінка керування категоріями сторінок
 * 
 * @package Plugins\Pages\Admin
 * @version 1.0.0
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';
require_once dirname(__DIR__) . '/Pages.php';

class PagesCategoriesPage extends AdminPage {
    
    private $pagesModule;
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Категорії сторінок - Flowaxy CMS';
        $this->templateName = 'pages-categories';
        
        $headerButtons = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="fas fa-plus me-1"></i>Додати категорію
        </button>';
        
        $this->setPageHeader(
            'Категорії сторінок',
            'Керування категоріями для організації сторінок',
            'fas fa-folder',
            $headerButtons
        );
        
        $this->pagesModule = new Pages();
    }
    
    /**
     * Обробка запиту
     */
    public function handle() {
        // Обробка AJAX запитів
        if (!empty($_GET['action']) && $_GET['action'] === 'get_category' && !empty($_GET['id'])) {
            $categoryId = (int)$_GET['id'];
            $category = $this->pagesModule->getCategory($categoryId);
            
            header('Content-Type: application/json');
            if ($category) {
                echo json_encode(['success' => true, 'category' => $category], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Категорію не знайдено'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        // Обробка POST запитів
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $this->handlePost();
            return;
        }
        
        // Отримання категорій
        $categories = $this->pagesModule->getCategories();
        
        // Рендеринг сторінки
        $this->render([
            'categories' => $categories
        ]);
    }
    
    /**
     * Обробка POST запитів
     */
    private function handlePost() {
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setMessage('Помилка безпеки', 'danger');
            Response::redirectStatic(UrlHelper::admin('pages-categories'));
            return;
        }
        
        $action = SecurityHelper::sanitizeInput($_POST['action'] ?? '');
        
        // Створення категорії
        if ($action === 'create') {
            $data = [
                'name' => SecurityHelper::sanitizeInput($_POST['name'] ?? ''),
                'slug' => SecurityHelper::sanitizeInput($_POST['slug'] ?? ''),
                'description' => SecurityHelper::sanitizeInput($_POST['description'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
            ];
            
            $result = $this->pagesModule->createCategory($data);
            if ($result['success']) {
                $this->setMessage('Категорію створено', 'success');
            } else {
                $this->setMessage('Помилка створення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
            }
        }
        
        // Оновлення категорії
        if ($action === 'update') {
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            if ($categoryId) {
                $data = [
                    'name' => SecurityHelper::sanitizeInput($_POST['name'] ?? ''),
                    'slug' => SecurityHelper::sanitizeInput($_POST['slug'] ?? ''),
                    'description' => SecurityHelper::sanitizeInput($_POST['description'] ?? ''),
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
                ];
                
                $result = $this->pagesModule->updateCategory($categoryId, $data);
                if ($result['success']) {
                    $this->setMessage('Категорію оновлено', 'success');
                } else {
                    $this->setMessage('Помилка оновлення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
                }
            } else {
                $this->setMessage('ID категорії не вказано', 'danger');
            }
        }
        
        // Видалення категорії
        if ($action === 'delete') {
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            if ($categoryId) {
                $result = $this->pagesModule->deleteCategory($categoryId);
                if ($result['success']) {
                    $this->setMessage('Категорію видалено', 'success');
                } else {
                    $this->setMessage('Помилка видалення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
                }
            } else {
                $this->setMessage('ID категорії не вказано', 'danger');
            }
        }
        
        Response::redirectStatic(UrlHelper::admin('pages-categories'));
    }
    
    /**
     * Отримання шляху до шаблону
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
}

