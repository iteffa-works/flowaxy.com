<?php
/**
 * Сторінка керування сторінками (список)
 * 
 * @package Plugins\Pages\Admin
 * @version 1.0.0
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';
require_once dirname(__DIR__) . '/Pages.php';

class PagesAdminPage extends AdminPage {
    
    private $pagesModule;
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Всі сторінки - Flowaxy CMS';
        $this->templateName = 'pages-list';
        
        $headerButtons = '<a href="' . UrlHelper::admin('pages-add') . '" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Додати сторінку
        </a>';
        
        $this->setPageHeader(
            'Всі сторінки',
            'Керування сторінками сайту',
            'fas fa-file-alt',
            $headerButtons
        );
        
        $this->pagesModule = new Pages();
    }
    
    /**
     * Обробка запиту
     */
    public function handle() {
        // Обробка POST запитів
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $this->handlePost();
            return;
        }
        
        // Отримання параметрів фільтрації
        $filters = [
            'status' => SecurityHelper::sanitizeInput($_GET['status'] ?? ''),
            'category_id' => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'search' => SecurityHelper::sanitizeInput($_GET['search'] ?? ''),
            'order_by' => SecurityHelper::sanitizeInput($_GET['order_by'] ?? 'created_at'),
            'order_dir' => SecurityHelper::sanitizeInput($_GET['order_dir'] ?? 'DESC')
        ];
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 20;
        
        // Отримання сторінок
        $result = $this->pagesModule->getPages($filters, $page, $perPage);
        
        // Отримання категорій для фільтра
        $categories = $this->pagesModule->getCategories();
        
        // Рендеринг сторінки
        $this->render([
            'pages' => $result['pages'] ?? [],
            'total' => $result['total'] ?? 0,
            'page' => $result['page'] ?? 1,
            'pages' => $result['pages'] ?? 0,
            'filters' => $filters,
            'categories' => $categories
        ]);
    }
    
    /**
     * Обробка POST запитів
     */
    private function handlePost() {
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setMessage('Помилка безпеки', 'danger');
            Response::redirectStatic(UrlHelper::admin('pages'));
            return;
        }
        
        $action = SecurityHelper::sanitizeInput($_POST['action'] ?? '');
        
        // Обробка видалення сторінки
        if ($action === 'delete') {
            $pageId = isset($_POST['page_id']) ? (int)$_POST['page_id'] : 0;
            if ($pageId) {
                $result = $this->pagesModule->deletePage($pageId);
                if ($result['success']) {
                    $this->setMessage('Сторінку видалено', 'success');
                } else {
                    $this->setMessage('Помилка видалення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
                }
            } else {
                $this->setMessage('ID сторінки не вказано', 'danger');
            }
        }
        
        // Обробка масового видалення
        if ($action === 'bulk_delete') {
            $pageIds = $_POST['page_ids'] ?? [];
            if (empty($pageIds) || !is_array($pageIds)) {
                $this->setMessage('Сторінки не вибрані', 'danger');
            } else {
                $deleted = 0;
                $errors = 0;
                
                foreach ($pageIds as $pageId) {
                    $pageId = (int)$pageId;
                    if ($pageId > 0) {
                        $result = $this->pagesModule->deletePage($pageId);
                        if ($result['success']) {
                            $deleted++;
                        } else {
                            $errors++;
                        }
                    }
                }
                
                if ($deleted > 0) {
                    $this->setMessage("Видалено сторінок: $deleted" . ($errors > 0 ? " (помилок: $errors)" : ''), $errors > 0 ? 'warning' : 'success');
                } else {
                    $this->setMessage('Помилка видалення сторінок', 'danger');
                }
            }
        }
        
        // Редирект
        $redirectUrl = UrlHelper::admin('pages');
        $queryParams = [];
        
        if (!empty($_GET['page'])) {
            $queryParams['page'] = (int)$_GET['page'];
        }
        if (!empty($_GET['status'])) {
            $queryParams['status'] = SecurityHelper::sanitizeInput($_GET['status']);
        }
        if (!empty($_GET['category_id'])) {
            $queryParams['category_id'] = (int)$_GET['category_id'];
        }
        if (!empty($_GET['search'])) {
            $queryParams['search'] = SecurityHelper::sanitizeInput($_GET['search']);
        }
        
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
        
        Response::redirectStatic($redirectUrl);
    }
    
    /**
     * Отримання шляху до шаблону
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
}

