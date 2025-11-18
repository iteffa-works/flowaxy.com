<?php
/**
 * Базовий клас для сторінок адмінки
 * Спрощена архітектура без MVC
 */

// Подключаем хелпер для компонентов
require_once __DIR__ . '/ComponentHelper.php';

// Интегрируем полезные классы из engine/classes
if (class_exists('Request')) {
    require_once __DIR__ . '/AdminRequest.php';
}
if (class_exists('AjaxHandler')) {
    require_once __DIR__ . '/AdminAjaxHandler.php';
}

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
        include __DIR__ . '/../layouts/base-plugin.php';
    }
    
    /**
     * Обробка запиту (перевизначається в дочірніх класах)
     */
    public function handle() {
        // Перевизначається в дочірніх класах
    }
    
    /**
     * Вспомогательный метод для рендеринга компонента alert
     */
    protected function renderAlert($message, $type = 'info', $dismissible = true, $icon = null) {
        $alertPath = __DIR__ . '/../components/alert.php';
        if (file_exists($alertPath)) {
            include $alertPath;
        }
    }
    
    /**
     * Вспомогательный метод для рендеринга компонента button
     */
    protected function renderButton($text, $type = 'primary', $options = []) {
        $buttonPath = __DIR__ . '/../components/button.php';
        if (file_exists($buttonPath)) {
            $url = $options['url'] ?? null;
            $icon = $options['icon'] ?? null;
            $attributes = $options['attributes'] ?? [];
            $submit = $options['submit'] ?? false;
            include $buttonPath;
        }
    }
    
    /**
     * Вспомогательный метод для рендеринга компонента empty-state
     */
    protected function renderEmptyState($icon, $title, $message, $actions = '', $classes = []) {
        $emptyStatePath = __DIR__ . '/../components/empty-state.php';
        if (file_exists($emptyStatePath)) {
            include $emptyStatePath;
        }
    }
    
    /**
     * Вспомогательный метод для получения HTML компонента через ob_start/ob_get_clean
     */
    protected function getComponent($componentName, $data = []) {
        $componentPath = __DIR__ . '/../components/' . $componentName . '.php';
        if (!file_exists($componentPath)) {
            return '';
        }
        
        // Извлекаем переменные из данных
        extract($data);
        
        ob_start();
        include $componentPath;
        return ob_get_clean();
    }
    
    /**
     * Создание кнопки через компонент и возврат HTML
     * 
     * @param string $text Текст кнопки
     * @param string $type Тип кнопки (primary, secondary, success, danger, warning, info, outline-primary, etc.)
     * @param array $options Опции: url, icon, attributes, submit
     * @return string HTML кнопки
     */
    protected function createButton($text, $type = 'primary', $options = []) {
        return $this->getComponent('button', array_merge([
            'text' => $text,
            'type' => $type
        ], $options));
    }
    
    /**
     * Создание группы кнопок для header
     * 
     * @param array $buttons Массив кнопок: [['text' => '...', 'type' => '...', 'options' => [...]], ...]
     * @param string $wrapperClass CSS класс для обертки (по умолчанию 'd-flex gap-2')
     * @return string HTML группы кнопок
     */
    protected function createButtonGroup($buttons, $wrapperClass = 'd-flex gap-2') {
        $buttonsHtml = '';
        foreach ($buttons as $button) {
            $text = $button['text'] ?? '';
            $type = $button['type'] ?? 'primary';
            $options = $button['options'] ?? [];
            $buttonsHtml .= $this->createButton($text, $type, $options);
        }
        
        return '<div class="' . htmlspecialchars($wrapperClass) . '">' . $buttonsHtml . '</div>';
    }
    
    /**
     * Проверка на AJAX запрос
     * 
     * @return bool
     */
    protected function isAjaxRequest() {
        if (class_exists('AdminAjaxHandler')) {
            return AdminAjaxHandler::isAjax();
        }
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Получение данных из запроса (использует AdminRequest если доступен)
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    protected function request(string $key, $default = null) {
        if (class_exists('AdminRequest')) {
            return AdminRequest::input($key, $default);
        }
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
    
    /**
     * Получение POST данных
     */
    protected function post(string $key, $default = null) {
        if (class_exists('AdminRequest')) {
            return AdminRequest::post($key, $default);
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Получение GET данных
     */
    protected function query(string $key, $default = null) {
        if (class_exists('AdminRequest')) {
            return AdminRequest::query($key, $default);
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Отправка JSON ответа (для AJAX)
     * 
     * @param array $data Данные для отправки
     * @param int $statusCode HTTP статус код
     */
    protected function sendJsonResponse($data, $statusCode = 200) {
        // Очищаємо всі буфери перед виводом JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        if (class_exists('Json')) {
            echo Json::encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
    
    /**
     * Редирект на страницу
     * 
     * @param string $page Страница для редиректа
     * @param array $params Параметры GET
     */
    protected function redirect($page, $params = []) {
        if (class_exists('Response')) {
            $url = UrlHelper::admin($page);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            Response::redirectStatic($url);
        } else {
            $url = UrlHelper::admin($page);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            header('Location: ' . $url);
            exit;
        }
    }
}
