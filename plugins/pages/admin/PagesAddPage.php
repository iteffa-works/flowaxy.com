<?php
/**
 * Сторінка додавання/редагування сторінки
 * 
 * @package Plugins\Pages\Admin
 * @version 1.0.0
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';
require_once dirname(__DIR__) . '/Pages.php';

class PagesAddPage extends AdminPage {
    
    private $pagesModule;
    private $pageId = null;
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        $this->pagesModule = new Pages();
        
        // Визначаємо, чи це редагування
        $this->pageId = !empty($_GET['id']) ? (int)$_GET['id'] : null;
        
        if ($this->pageId) {
            $this->pageTitle = 'Редагувати сторінку - Flowaxy CMS';
            $this->templateName = 'pages-edit';
            
            $headerButtons = '<a href="' . UrlHelper::admin('pages') . '" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад до списку
            </a>';
            
            $this->setPageHeader(
                'Редагувати сторінку',
                'Змінити інформацію про сторінку',
                'fas fa-edit',
                $headerButtons
            );
        } else {
            $this->pageTitle = 'Додати сторінку - Flowaxy CMS';
            $this->templateName = 'pages-add';
            
            $headerButtons = '<a href="' . UrlHelper::admin('pages') . '" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад до списку
            </a>';
            
            $this->setPageHeader(
                'Додати сторінку',
                'Створити нову сторінку на сайті',
                'fas fa-plus',
                $headerButtons
            );
        }
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
        
        // Отримання даних сторінки для редагування
        $page = null;
        if ($this->pageId) {
            $page = $this->pagesModule->getPage($this->pageId);
            if (!$page) {
                $this->setMessage('Сторінку не знайдено', 'danger');
                Response::redirectStatic(UrlHelper::admin('pages'));
                return;
            }
        }
        
        // Отримання категорій
        $categories = $this->pagesModule->getCategories();
        
        // Рендеринг сторінки
        $this->render([
            'page' => $page,
            'categories' => $categories,
            'pageId' => $this->pageId
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
        
        $data = [
            'title' => SecurityHelper::sanitizeInput($_POST['title'] ?? ''),
            'slug' => SecurityHelper::sanitizeInput($_POST['slug'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'excerpt' => SecurityHelper::sanitizeInput($_POST['excerpt'] ?? ''),
            'status' => SecurityHelper::sanitizeInput($_POST['status'] ?? 'draft'),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null
        ];
        
        if ($this->pageId) {
            // Оновлення
            $result = $this->pagesModule->updatePage($this->pageId, $data);
            if ($result['success']) {
                $this->setMessage('Сторінку оновлено', 'success');
                Response::redirectStatic(UrlHelper::admin('pages'));
            } else {
                $this->setMessage('Помилка оновлення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
            }
        } else {
            // Створення
            $result = $this->pagesModule->createPage($data);
            if ($result['success']) {
                $this->setMessage('Сторінку створено', 'success');
                Response::redirectStatic(UrlHelper::admin('pages'));
            } else {
                $this->setMessage('Помилка створення: ' . ($result['error'] ?? 'Невідома помилка'), 'danger');
            }
        }
        
        // Якщо є помилка, залишаємося на сторінці
        $this->render([
            'page' => $data,
            'categories' => $this->pagesModule->getCategories(),
            'pageId' => $this->pageId
        ]);
    }
    
    /**
     * Отримання шляху до шаблону
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
}

