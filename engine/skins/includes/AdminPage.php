<?php
/**
 * Базовий клас для сторінок адмінки
 * Спрощена архітектура без MVC
 */

// Подключаем хелпер для компонентов
require_once __DIR__ . '/ComponentHelper.php';

// Классы из engine/classes загружаются автоматически через autoloader в engine/init.php
// Используем их напрямую: Request, AjaxHandler, Validator, File, Directory, Response и т.д.

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
        // Для AJAX запросов очищаем буфер вывода сразу
        if (AjaxHandler::isAjax()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('display_errors', '0');
        }
        
        // Перевірка авторизації
        SecurityHelper::requireAdmin();
        
        // Підключення до БД з обробкою помилок
        try {
            $this->db = DatabaseHelper::getConnection(true);
            if ($this->db === null) {
                // Якщо підключення не вдалося, DatabaseHelper::getConnection() вже показав сторінку помилки
                // Для AJAX возвращаем JSON ошибку
                if (AjaxHandler::isAjax()) {
                    Response::jsonResponse(['success' => false, 'error' => 'Помилка підключення до бази даних'], 500);
                }
                exit;
            }
        } catch (Exception $e) {
            // Для AJAX возвращаем JSON ошибку
            if (AjaxHandler::isAjax()) {
                Response::jsonResponse([
                    'success' => false, 
                    'error' => 'Помилка підключення до бази даних: ' . $e->getMessage()
                ], 500);
                exit;
            }
            
            // Показуємо сторінку помилки для обычных запросов
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
    protected function verifyCsrf(): bool {
        $token = $this->request()->post('csrf_token', '');
        if (!SecurityHelper::verifyCsrfToken($token)) {
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
     * Получение экземпляра Request (использует класс напрямую из engine/classes)
     * 
     * @return Request
     */
    protected function request(): Request {
        return Request::getInstance();
    }
    
    /**
     * Проверка на AJAX запрос
     * 
     * @return bool
     */
    protected function isAjaxRequest(): bool {
        return AjaxHandler::isAjax();
    }
    
    /**
     * Получение данных из запроса
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    protected function input(string $key, $default = null) {
        return Request::input($key, $default);
    }
    
    /**
     * Получение POST данных
     */
    protected function post(string $key, $default = null) {
        return $this->request()->post($key, $default);
    }
    
    /**
     * Получение GET данных
     */
    protected function query(string $key, $default = null) {
        return $this->request()->query($key, $default);
    }
    
    /**
     * Получение метода запроса
     */
    protected function method(): string {
        return Request::getMethod();
    }
    
    /**
     * Проверка метода запроса
     */
    protected function isMethod(string $method): bool {
        return $this->request()->isMethod($method);
    }
    
    /**
     * Отправка JSON ответа (для AJAX)
     * Использует Response::jsonResponse напрямую из engine/classes
     * 
     * @param array $data Данные для отправки
     * @param int $statusCode HTTP статус код
     */
    protected function sendJsonResponse(array $data, int $statusCode = 200): void {
        // Очищаем буфер вывода перед отправкой JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        Response::jsonResponse($data, $statusCode);
        // exit вызывается внутри Response::jsonResponse()
    }
    
    /**
     * Редирект на страницу
     * 
     * @param string $page Страница для редиректа
     * @param array $params Параметры GET
     */
    protected function redirect(string $page, array $params = []): void {
        $url = UrlHelper::admin($page);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        Response::redirectStatic($url);
    }
    
    /**
     * Получить экземпляр ModalHandler
     * 
     * @return ModalHandler
     */
    protected function modalHandler(): ModalHandler {
        $handler = ModalHandler::getInstance();
        $handler->setContext('admin');
        return $handler;
    }
    
    /**
     * Регистрация модального окна
     * 
     * @param string $id ID модального окна
     * @param array $config Конфигурация
     * @return self
     */
    protected function registerModal(string $id, array $config = []): self {
        $this->modalHandler()->register($id, $config);
        return $this;
    }
    
    /**
     * Регистрация обработчика для модального окна
     * 
     * @param string $modalId ID модального окна
     * @param string $action Действие
     * @param callable $handler Обработчик
     * @return self
     */
    protected function registerModalHandler(string $modalId, string $action, callable $handler): self {
        $this->modalHandler()->registerHandler($modalId, $action, $handler);
        return $this;
    }
    
    /**
     * Обработка AJAX запроса от модального окна
     * 
     * @param string $modalId ID модального окна
     * @param string $action Действие
     * @return void
     */
    protected function handleModalRequest(string $modalId, string $action): void {
        $request = Request::getInstance();
        
        // Получаем все данные из POST и GET
        $data = $request->all();
        
        // Убираем служебные поля
        unset($data['modal_id'], $data['action'], $data['csrf_token']);
        
        $files = $request->files();
        
        $result = $this->modalHandler()->handle($modalId, $action, $data, $files);
        $this->sendJsonResponse($result, $result['success'] ? 200 : 400);
    }
    
    /**
     * Рендеринг модального окна
     * 
     * @param string $id ID модального окна
     * @param array $options Дополнительные опции
     * @return string HTML
     */
    protected function renderModal(string $id, array $options = []): string {
        return $this->modalHandler()->render($id, $options);
    }
}
