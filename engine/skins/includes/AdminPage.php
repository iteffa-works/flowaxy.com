<?php
/**
 * Базовый класс для страниц админки
 * Упрощенная архитектура без MVC
 */

class AdminPage {
    protected $db;
    protected $message = '';
    protected $messageType = '';
    protected $pageTitle = 'Landing CMS';
    protected $pageHeaderTitle = '';
    protected $pageHeaderDescription = '';
    protected $pageHeaderIcon = '';
    protected $pageHeaderButtons = '';
    protected $templateName = '';
    protected $additionalCSS = [];
    protected $additionalJS = [];
    
    public function __construct() {
        // Проверка авторизации
        requireAdmin();
        
        // Подключение к БД с обработкой ошибок
        try {
            $this->db = getDB(true);
            if ($this->db === null) {
                // Если подключение не удалось, getDB() уже показал страницу ошибки
                exit;
            }
        } catch (Exception $e) {
            // Показываем страницу ошибки
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
     * Установка сообщения
     */
    protected function setMessage($message, $type = 'info') {
        $this->message = $message;
        $this->messageType = $type;
    }
    
    /**
     * Установка заголовка страницы
     */
    protected function setPageHeader($title, $description = '', $icon = '', $buttons = '') {
        $this->pageHeaderTitle = $title;
        $this->pageHeaderDescription = $description;
        $this->pageHeaderIcon = $icon;
        $this->pageHeaderButtons = $buttons;
    }
    
    /**
     * Проверка CSRF токена
     */
    protected function verifyCsrf() {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->setMessage('Помилка безпеки', 'danger');
            return false;
        }
        return true;
    }
    
    /**
     * Получение данных для шаблона
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
     * Получение пути к шаблону (может быть переопределено в дочерних классах)
     */
    protected function getTemplatePath() {
        return __DIR__ . '/../templates/';
    }
    
    /**
     * Рендеринг страницы
     */
    protected function render($data = []) {
        $templateData = array_merge($this->getTemplateData(), $data);
        
        // Проверяем, есть ли кастомный путь к шаблону
        $customTemplatePath = $this->getTemplatePath();
        $defaultTemplatePath = __DIR__ . '/../templates/';
        
        if ($customTemplatePath !== $defaultTemplatePath) {
            // Используем кастомный шаблон из плагина
            $this->renderCustomTemplate($customTemplatePath, $templateData);
        } else {
            // Используем стандартный шаблон
            renderTemplate($this->templateName, $templateData);
        }
    }
    
    /**
     * Рендеринг кастомного шаблона (для плагинов)
     */
    protected function renderCustomTemplate($templatePath, $data) {
        // Извлекаем переменные из данных
        extract($data);
        
        $templateFile = $templatePath . $this->templateName . '.php';
        
        if (!file_exists($templateFile)) {
            die("Template not found: " . $templateFile);
        }
        
        // Сохраняем путь к кастомному шаблону для использования в layout
        $customTemplateFile = $templateFile;
        
        // Используем стандартный layout админки для плагинов
        include __DIR__ . '/../templates/layout/base-plugin.php';
    }
    
    /**
     * Обработка запроса (переопределяется в дочерних классах)
     */
    public function handle() {
        // Переопределяется в дочерних классах
    }
}
