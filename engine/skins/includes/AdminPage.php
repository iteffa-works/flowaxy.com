<?php
/**
 * Базовий клас для сторінок адмінки
 * Спрощена архітектура без MVC
 */

// Подключаем хелпер для компонентов
require_once __DIR__ . '/ComponentHelper.php';

class AdminPage {
    protected $db;
    protected $message = '';
    protected $messageType = '';
    protected $pageTitle = 'Flowaxy CMS';
    protected $pageHeaderTitle = '';
    protected $pageHeaderDescription = '';
    protected $pageHeaderIcon = '';
    protected $pageHeaderButtons = '';
    protected $templateName = '';
    protected $additionalCSS = [];
    protected $additionalJS = [];
    
    public function __construct() {
        // Перевірка авторизації
        SecurityHelper::requireAdmin();
        
        // Підключення до БД з обробкою помилок
        try {
            $this->db = DatabaseHelper::getConnection(true);
            if ($this->db === null) {
                // Якщо підключення не вдалося, DatabaseHelper::getConnection() вже показав сторінку помилки
                exit;
            }
        } catch (Exception $e) {
            // Показуємо сторінку помилки
            if (function_exists('showDatabaseError')) {
                showDatabaseError([
                    'host' => DB_HOST ?? 'unknown',
                    'database' => DB_NAME ?? 'unknown',
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
            } else {
                die('Database connection error: ' . Security::clean($e->getMessage()));
            }
            exit;
        }
    }
    
    /**
     * Встановлення повідомлення
     */
    protected function setMessage($message, $type = 'info') {
        $this->message = $message;
        $this->messageType = $type;
    }
    
    /**
     * Встановлення заголовка сторінки
     */
    protected function setPageHeader($title, $description = '', $icon = '', $buttons = '') {
        $this->pageHeaderTitle = $title;
        $this->pageHeaderDescription = $description;
        $this->pageHeaderIcon = $icon;
        $this->pageHeaderButtons = $buttons;
    }
    
    /**
     * Перевірка CSRF токена
     */
    protected function verifyCsrf() {
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setMessage('Помилка безпеки', 'danger');
            return false;
        }
        return true;
    }
    
    /**
     * Отримання даних для шаблону
     */
    protected function getTemplateData() {
        return [
            'message' => $this->message,
            'messageType' => $this->messageType,
            'pageTitle' => $this->pageTitle,
            'pageHeaderTitle' => $this->pageHeaderTitle,
            'pageHeaderDescription' => $this->pageHeaderDescription,
            'pageHeaderIcon' => $this->pageHeaderIcon,
            'pageHeaderButtons' => $this->pageHeaderButtons,
            'templateName' => $this->templateName,
            'additionalCSS' => $this->additionalCSS,
            'additionalJS' => $this->additionalJS
        ];
    }
    
    /**
     * Отримання шляху до шаблону (може бути перевизначено в дочірніх класах)
     */
    protected function getTemplatePath() {
        return __DIR__ . '/../templates/';
    }
    
    /**
     * Рендеринг сторінки
     */
    protected function render($data = []) {
        $templateData = array_merge($this->getTemplateData(), $data);
        
        // Перевіряємо, чи є кастомний шлях до шаблону
        $customTemplatePath = $this->getTemplatePath();
        $defaultTemplatePath = __DIR__ . '/../templates/';
        
        if ($customTemplatePath !== $defaultTemplatePath) {
            // Використовуємо кастомний шаблон з плагіна
            $this->renderCustomTemplate($customTemplatePath, $templateData);
        } else {
            // Використовуємо стандартний шаблон
            renderTemplate($this->templateName, $templateData);
        }
    }
    
    /**
     * Рендеринг кастомного шаблону (для плагінів)
     */
    protected function renderCustomTemplate($templatePath, $data) {
        // Витягуємо змінні з даних
        extract($data);
        
        $templateFile = $templatePath . $this->templateName . '.php';
        
        if (!file_exists($templateFile)) {
            die("Template not found: " . $templateFile);
        }
        
        // Зберігаємо шлях до кастомного шаблону для використання в layout
        $customTemplateFile = $templateFile;
        
        // Використовуємо стандартний layout адмінки для плагінів
        include __DIR__ . '/../templates/layout/base-plugin.php';
    }
    
    /**
     * Обробка запиту (перевизначається в дочірніх класах)
     */
    public function handle() {
        // Перевизначається в дочірніх класах
    }
}
