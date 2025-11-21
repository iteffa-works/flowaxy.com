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
    protected $additionalInlineCSS = '';
    
    // Флаги для автоматического редиректа после POST
    private $postProcessed = false;
    private $autoRedirectEnabled = true;
    private $redirectPerformed = false;
    private $currentPage = '';
    
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
        
        // Гарантуємо, що перший користувач має доступ до адмінки
        $this->ensureFirstUserHasAdminAccess();
        
        // Проверка прав доступа к адмін-панелі
        if (function_exists('current_user_can')) {
            $currentUserId = (int)Session::get('admin_user_id');
            if (!current_user_can('admin.access')) {
                // Якщо це перший користувач - пробуємо повторно синхронізувати роль та перевірити ще раз
                if ($currentUserId === 1) {
                    $this->ensureFirstUserHasAdminAccess();
                    if (!current_user_can('admin.access')) {
                        http_response_code(403);
                        die('Доступ заборонено. У вас немає прав для доступу до адміністративної панелі.');
                    }
                } else {
                    http_response_code(403);
                    die('Доступ заборонено. У вас немає прав для доступу до адміністративної панелі.');
                }
            }
        }
        
        // Загружаем flash сообщения из сессии (если есть)
        $flashMessage = Session::flash('admin_message');
        $flashMessageType = Session::flash('admin_message_type');
        if ($flashMessage) {
            $this->message = $flashMessage;
            $this->messageType = $flashMessageType ?: 'info';
        }
        
        // Определяем текущую страницу для редиректа
        $request = Request::getInstance();
        // Пытаемся определить из query параметра 'page'
        $this->currentPage = $request->query('page', '');
        if (empty($this->currentPage)) {
            // Пытаемся определить из URL (последняя часть пути после /admin/)
            $path = $request->path();
            $path = trim($path, '/');
            $parts = explode('/', $path);
            // Ищем часть после 'admin'
            $adminIndex = array_search('admin', $parts);
            if ($adminIndex !== false && isset($parts[$adminIndex + 1])) {
                $this->currentPage = $parts[$adminIndex + 1];
            } elseif (!empty($parts)) {
                // Если 'admin' не найден, берем последнюю часть
                $this->currentPage = end($parts);
            }
        }
        
        // Если все еще пусто, используем 'dashboard' по умолчанию
        if (empty($this->currentPage)) {
            $this->currentPage = 'dashboard';
        }
    }
    
    /**
     * Встановлення повідомлення
     * При POST-запросе автоматически сохраняет в flash для передачи через редирект
     */
    protected function setMessage($message, $type = 'info') {
        $this->message = $message;
        $this->messageType = $type;
        
        // Если это POST-запрос, сохраняем сообщение в flash для передачи через редирект
        if (Request::getMethod() === 'POST' && !AjaxHandler::isAjax()) {
            Session::setFlash('admin_message', $message);
            Session::setFlash('admin_message_type', $type);
            $this->postProcessed = true;
        }
    }
    
    /**
     * Отключить автоматический редирект после POST
     * Используйте этот метод, если нужно обработать POST без редиректа
     */
    protected function preventAutoRedirect(): void {
        $this->autoRedirectEnabled = false;
    }
    
    /**
     * Отметить, что редирект был выполнен вручную
     */
    protected function markRedirectPerformed(): void {
        $this->redirectPerformed = true;
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
            'additionalJS' => $this->additionalJS,
            'additionalInlineCSS' => $this->additionalInlineCSS
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
        
        // Нормализуем пути для сравнения (заменяем обратные слеши на прямые)
        $customPathNormalized = str_replace('\\', '/', rtrim($customTemplatePath, '/\\')) . '/';
        $defaultPathNormalized = str_replace('\\', '/', rtrim($defaultTemplatePath, '/\\')) . '/';
        
        if ($customPathNormalized !== $defaultPathNormalized) {
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
        
        // Використовуємо стандартний layout адмінки (тепер підтримує кастомні шаблони)
        include __DIR__ . '/../layouts/base.php';
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
     * @param string $page Страница для редиректа (если пусто, используется текущая)
     * @param array $params Параметры GET
     */
    protected function redirect(string $page = '', array $params = []): void {
        if (empty($page)) {
            $page = $this->currentPage;
        }
        
        $url = UrlHelper::admin($page);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $this->markRedirectPerformed();
        Response::redirectStatic($url);
        exit;
    }
    
    /**
     * Проверка необходимости автоматического редиректа
     */
    private function shouldAutoRedirect(): bool {
        // Не редиректим, если:
        // 1. Это не POST-запрос
        // 2. Это AJAX-запрос
        // 3. Автоматический редирект отключен
        // 4. Редирект уже был выполнен
        // 5. POST не был обработан (нет сообщений)
        
        if (Request::getMethod() !== 'POST') {
            return false;
        }
        
        if (AjaxHandler::isAjax()) {
            return false;
        }
        
        if (!$this->autoRedirectEnabled) {
            return false;
        }
        
        if ($this->redirectPerformed) {
            return false;
        }
        
        if (!$this->postProcessed && empty($this->message)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Деструктор для автоматического редиректа после POST
     */
    public function __destruct() {
        // Автоматический редирект после POST-запроса
        if ($this->shouldAutoRedirect()) {
            $this->redirect();
        }
    }

    /**
     * Гарантує, що перший користувач має роль розробника та доступ до адмінки
     */
    private function ensureFirstUserHasAdminAccess(): void {
        $userId = (int)Session::get('admin_user_id');
        if ($userId !== 1 || !$this->db) {
            return;
        }

        try {
            $this->ensureRolesAndPermissionsExist();

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM user_roles ur
                INNER JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.slug = 'developer'
            ");
            $stmt->execute([$userId]);
            $hasDeveloperRole = (int)$stmt->fetchColumn() > 0;

            if (!$hasDeveloperRole) {
                $stmt = $this->db->prepare("SELECT id FROM roles WHERE slug = 'developer' LIMIT 1");
                $stmt->execute();
                $role = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($role) {
                    $roleId = (int)$role['id'];
                    $stmt = $this->db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $roleId]);
                }
            }

            if (class_exists('RoleManager')) {
                RoleManager::getInstance()->clearUserCache($userId);
            }
        } catch (Exception $e) {
            error_log('AdminPage ensureFirstUserHasAdminAccess error: ' . $e->getMessage());
        }
    }

    /**
     * Створює базові ролі та дозволи, якщо вони відсутні
     */
    private function ensureRolesAndPermissionsExist(): void {
        if (!$this->db) {
            return;
        }

        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM roles");
            $rolesCount = (int)$stmt->fetchColumn();

            if ($rolesCount === 0) {
                $roles = [
                    ['Разработчик', 'developer', 'Повний доступ до всіх функцій системи. Роль не може бути видалена.', 1],
                    ['Администратор', 'admin', 'Повний доступ до адмін-панелі', 1],
                    ['Користувач', 'user', 'Базові права користувача', 1],
                    ['Модератор', 'moderator', 'Розширені права модератора', 0],
                ];

                foreach ($roles as $roleData) {
                    $stmt = $this->db->prepare("INSERT IGNORE INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, ?)");
                    $stmt->execute($roleData);
                }
            }

            $stmt = $this->db->query("SELECT COUNT(*) FROM permissions");
            $permissionsCount = (int)$stmt->fetchColumn();

            if ($permissionsCount === 0) {
                $permissions = [
                    ['Доступ до адмін-панелі', 'admin.access', 'Доступ до адміністративної панелі', 'admin'],
                    ['Управління плагінами', 'admin.plugins', 'Установка, активація та видалення плагінів', 'admin'],
                    ['Управління темами', 'admin.themes', 'Установка та активація тем', 'admin'],
                    ['Управління налаштуваннями', 'admin.settings', 'Зміна системних налаштувань', 'admin'],
                    ['Перегляд логів', 'admin.logs.view', 'Перегляд системних логів', 'admin'],
                    ['Управління користувачами', 'admin.users', 'Створення, редагування та видалення користувачів', 'admin'],
                    ['Управління ролями', 'admin.roles', 'Управління ролями та правами доступу', 'admin'],
                    ['Доступ до кабінету', 'cabinet.access', 'Доступ до особистого кабінету', 'cabinet'],
                    ['Редагування профілю', 'cabinet.profile.edit', 'Редагування власного профілю', 'cabinet'],
                    ['Перегляд налаштувань кабінету', 'cabinet.settings.view', 'Перегляд налаштувань кабінету', 'cabinet'],
                    ['Зміна налаштувань кабінету', 'cabinet.settings.edit', 'Зміна налаштувань кабінету', 'cabinet'],
                ];

                foreach ($permissions as $permissionData) {
                    $stmt = $this->db->prepare("INSERT IGNORE INTO permissions (name, slug, description, category) VALUES (?, ?, ?, ?)");
                    $stmt->execute($permissionData);
                }
            }

            $stmt = $this->db->prepare("SELECT id FROM roles WHERE slug = 'developer' LIMIT 1");
            $stmt->execute();
            $developerRole = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($developerRole) {
                $roleId = (int)$developerRole['id'];
                $permissionIds = $this->db->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
                $insert = $this->db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $permissionId) {
                    $insert->execute([$roleId, $permissionId]);
                }
            }

            $stmt = $this->db->prepare("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1");
            $stmt->execute();
            $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($adminRole) {
                $roleId = (int)$adminRole['id'];
                $permissionIds = $this->db->query("SELECT id FROM permissions WHERE category = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
                $insert = $this->db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $permissionId) {
                    $insert->execute([$roleId, $permissionId]);
                }
            }

            $stmt = $this->db->prepare("SELECT id FROM roles WHERE slug = 'user' LIMIT 1");
            $stmt->execute();
            $userRole = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userRole) {
                $roleId = (int)$userRole['id'];
                $permissionSlugs = ['cabinet.access', 'cabinet.profile.edit', 'cabinet.settings.view'];
                $placeholders = implode(',', array_fill(0, count($permissionSlugs), '?'));
                $permStmt = $this->db->prepare("SELECT id FROM permissions WHERE slug IN ($placeholders)");
                $permStmt->execute($permissionSlugs);
                $permissionIds = $permStmt->fetchAll(PDO::FETCH_COLUMN);
                $insert = $this->db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $permissionId) {
                    $insert->execute([$roleId, $permissionId]);
                }
            }
        } catch (Exception $e) {
            error_log('AdminPage ensureRolesAndPermissionsExist error: ' . $e->getMessage());
        }
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
