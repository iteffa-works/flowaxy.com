<?php
/**
 * Менеджер маршрутизації системи
 * Визначає контекст (адмінка / API / публічна частина) та управляє маршрутами
 *
 * @package Engine\Classes\Managers
 * @version 2.0.0
 */

declare(strict_types=1);

class RouterManager {
    public const CONTEXT_ADMIN = 'admin';
    public const CONTEXT_API = 'api';
    public const CONTEXT_PUBLIC = 'public';

    private static ?self $instance = null;

    private Router $router;
    private string $context = self::CONTEXT_PUBLIC;
    private ?string $basePath = null;
    private array $routeCache = [];
    private bool $routesLoaded = false;

    /**
     * @throws Exception
     */
    private function __construct() {
        $this->detectContext();
        $this->bootRouter();
        $this->registerCoreRoutes();
    }

    /**
     * Отримання екземпляра (Singleton)
     * 
     * @return self
     * @throws Exception
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Отримання об'єкта Router
     * 
     * @return Router
     */
    public function getRouter(): Router {
        return $this->router;
    }

    /**
     * Отримання поточного контексту
     * 
     * @return string
     */
    public function getContext(): string {
        return $this->context;
    }

    /**
     * Перевірка, чи є поточний контекст адмінкою
     * 
     * @return bool
     */
    public function isAdmin(): bool {
        return $this->context === self::CONTEXT_ADMIN;
    }

    /**
     * Перевірка, чи є поточний контекст API
     * 
     * @return bool
     */
    public function isApi(): bool {
        return $this->context === self::CONTEXT_API;
    }

    /**
     * Перевірка, чи є поточний контекст публічною частиною
     * 
     * @return bool
     */
    public function isPublic(): bool {
        return $this->context === self::CONTEXT_PUBLIC;
    }

    /**
     * Отримання базового шляху для поточного контексту
     * 
     * @return string
     */
    public function getBasePath(): string {
        return $this->basePath ?? '/';
    }

    /**
     * Виконання диспетчеризації маршрутів
     * 
     * @return void
     */
    public function dispatch(): void {
        // Блокування доступу до /install, якщо система вже встановлена
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        
        if (str_starts_with($path, '/install')) {
            $this->blockInstallerIfInstalled();
        }

        // Обробка AJAX запитів
        if (class_exists('AjaxHandler') && AjaxHandler::isAjax()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('display_errors', '0');
        }

        // Завантажуємо додаткові маршрути з плагінів, якщо ще не завантажені
        if (!$this->routesLoaded) {
            $this->loadPluginRoutes();
            $this->routesLoaded = true;
        }

        $this->router->dispatch();
    }

    /**
     * Додавання маршруту в поточний контекст
     * 
     * @param string|array $methods HTTP методи (GET, POST, PUT, DELETE тощо)
     * @param string $path Шлях маршруту
     * @param callable|string $handler Обробник (функція, замикання, клас)
     * @param array $options Додаткові опції (middleware, name, тощо)
     * @return self
     */
    public function addRoute($methods, string $path, $handler, array $options = []): self {
        $this->router->add($methods, $path, $handler, $options);
        return $this;
    }

    /**
     * Реєстрація сторінки адмінки у вигляді класу
     * 
     * @param string $slug Slug сторінки
     * @param string $className Ім'я класу сторінки
     * @param array $options Додаткові опції
     * @return void
     */
    public function registerAdminPage(string $slug, string $className, array $options = []): void {
        if ($this->context !== self::CONTEXT_ADMIN) {
            return;
        }
        $methods = $options['methods'] ?? ['GET', 'POST'];
        $this->router->add($methods, $slug, $className, $options);
    }

    /**
     * Виконання callback тільки в певному контексті
     * 
     * @param string $context Контекст (CONTEXT_ADMIN, CONTEXT_API, CONTEXT_PUBLIC)
     * @param callable $callback Callback функція
     * @return void
     */
    public function when(string $context, callable $callback): void {
        if ($this->context === $context) {
            $callback($this->router, $this);
        }
    }

    /**
     * Визначення контексту на основі URI
     * Оптимізована версія з покращеною обробкою шляхів
     * 
     * @return void
     */
    private function detectContext(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Нормалізація шляху
        $path = $this->normalizePath($path);

        // Перевіряємо контексти в порядку пріоритету
        // 1. API (найвищий пріоритет)
        if ($this->isApiPath($path)) {
            $this->context = self::CONTEXT_API;
            $this->basePath = $this->detectApiBasePath($path);
            return;
        }

        // 2. Адмінка
        if ($this->isAdminPath($path)) {
            $this->context = self::CONTEXT_ADMIN;
            $this->basePath = '/admin';
            return;
        }

        // 3. Публічна частина (за замовчуванням)
        $this->context = self::CONTEXT_PUBLIC;
        $this->basePath = '/';
    }

    /**
     * Нормалізація шляху (видалення дублюючих слэшів, очищення)
     * 
     * @param string $path Шлях для нормалізації
     * @return string Нормалізований шлях
     */
    private function normalizePath(string $path): string {
        // Видалення дублюючих слэшів (оптимізовано)
        $path = preg_replace('#/+#', '/', $path);
        
        // Видалення кінцевого слэша (крім кореня)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        
        // Переконуємося, що шлях починається зі слэша
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        
        return $path;
    }

    /**
     * Перевірка, чи є шлях API шляхом
     * 
     * @param string $path Шлях для перевірки
     * @return bool
     */
    private function isApiPath(string $path): bool {
        // Перевіряємо різні варіанти API шляхів
        // /api, /api/, /api/v1, /api/v2, /api-v1 тощо
        return preg_match('#^/api(/v\d+)?(/|$)#', $path) === 1 ||
               preg_match('#^/api-v?\d+#', $path) === 1;
    }

    /**
     * Визначення базового шляху для API
     * 
     * @param string $path Шлях запиту
     * @return string Базовий шлях API
     */
    private function detectApiBasePath(string $path): string {
        // Перевіряємо версію API в шляху /api/v1, /api/v2 тощо
        if (preg_match('#^/api/v(\d+)#', $path, $matches)) {
            return '/api/v' . $matches[1];
        }
        
        // Перевіряємо старий формат api-v1, api-v2 тощо
        if (preg_match('#^/api-v(\d+)#', $path, $matches)) {
            return '/api/v' . $matches[1];
        }
        
        // За замовчуванням використовуємо v1
        return '/api/v1';
    }

    /**
     * Перевірка, чи є шлях адмінським шляхом
     * 
     * @param string $path Шлях для перевірки
     * @return bool
     */
    private function isAdminPath(string $path): bool {
        // Точне співпадіння /admin або шлях починається з /admin/
        return $path === '/admin' || str_starts_with($path, '/admin/');
    }

    /**
     * Ініціалізація Router з потрібними параметрами
     * 
     * @return void
     * @throws RuntimeException
     */
    private function bootRouter(): void {
        if (!class_exists('Router')) {
            throw new RuntimeException('Клас Router не знайдено');
        }

        // Визначаємо маршрут за замовчуванням для адмінки
        $defaultRoute = $this->context === self::CONTEXT_ADMIN ? 'dashboard' : null;

        $this->router = new Router($this->basePath, $defaultRoute);
    }

    /**
     * Реєстрація основних маршрутів залежно від контексту
     * 
     * @return void
     */
    private function registerCoreRoutes(): void {
        switch ($this->context) {
            case self::CONTEXT_API:
                $this->loadApiRoutes();
                break;
            case self::CONTEXT_ADMIN:
                $this->loadAdminRoutes();
                break;
            default:
                $this->registerPublicRoutes();
                break;
        }
    }

    /**
     * Завантаження маршрутів API
     * 
     * @return void
     */
    private function loadApiRoutes(): void {
        $routesFile = __DIR__ . '/../../includes/api-routes.php';
        if (file_exists($routesFile)) {
            $router = $this->router;
            require $routesFile;
        }
    }

    /**
     * Завантаження маршрутів адмінки
     * 
     * @return void
     */
    private function loadAdminRoutes(): void {
        // Завантажуємо SimpleTemplate якщо він є
        $simpleTemplate = __DIR__ . '/../../skins/includes/SimpleTemplate.php';
        if (file_exists($simpleTemplate)) {
            require_once $simpleTemplate;
        }

        // Завантажуємо маршрути адмінки
        $routesFile = __DIR__ . '/../../skins/includes/admin-routes.php';
        if (file_exists($routesFile)) {
            $router = $this->router;
            require $routesFile;
        }

        // Дозволяємо плагінам додавати свої маршрути через хук
        if (function_exists('doHook')) {
            doHook('admin_routes', [$this->router, $this]);
        }
    }

    /**
     * Завантаження маршрутів з плагінів
     * 
     * @return void
     */
    private function loadPluginRoutes(): void {
        if (!function_exists('doHook')) {
            return;
        }

        // Хук для додавання маршрутів плагінами
        doHook('register_routes', [$this->router, $this]);
    }

    /**
     * Реєстрація публічних маршрутів
     * 
     * @return void
     */
    private function registerPublicRoutes(): void {
        // Основний маршрут для публічної частини
        $this->router->add(['GET', 'POST'], '', function () {
            if (function_exists('themeManager')) {
                $tm = themeManager();
                $active = $tm->getActiveTheme();

                if ($active && isset($active['slug'])) {
                    $path = $tm->getThemePath($active['slug']);

                    if ($path && file_exists($path . 'index.php')) {
                        extract([
                            'theme_path' => $path,
                            'theme_url' => '/themes/' . $active['slug'],
                            'theme_slug' => $active['slug']
                        ]);
                        include $path . 'index.php';
                        return true;
                    }
                }
            }

            // Fallback якщо тема не знайдена
            return renderThemeFallback();
        });

        // Дозволяємо плагінам додавати свої публічні маршрути
        if (function_exists('doHook')) {
            doHook('public_routes', [$this->router, $this]);
        }
    }

    /**
     * Очищення кешу маршрутів
     * 
     * @return void
     */
    public function clearCache(): void {
        $this->routeCache = [];
        $this->routesLoaded = false;
    }

    /**
     * Отримання списку всіх зареєстрованих маршрутів (для відлагодження)
     * 
     * @return array
     */
    public function getRoutes(): array {
        // Якщо Router має метод для отримання маршрутів
        if (method_exists($this->router, 'getRoutes')) {
            return $this->router->getRoutes();
        }
        return [];
    }

    /**
     * Блокування доступу до встановлювача, якщо система вже встановлена
     * 
     * @return void
     */
    private function blockInstallerIfInstalled(): void {
        $databaseIniFile = __DIR__ . '/../../data/database.ini';
        
        // Перевіряємо, чи йде процес встановлення (є налаштування БД в сесії)
        // Використовуємо Session напряму, оскільки sessionManager може бути ще не доступний
        if (function_exists('sessionManager')) {
            $session = sessionManager('installer');
            $isInstallationInProgress = $session->has('db_config') && is_array($session->get('db_config'));
        } else {
            // Fallback на прямий доступ до сесії для перевірки
            $isInstallationInProgress = isset($_SESSION['install_db_config']) && is_array($_SESSION['install_db_config']);
        }
        
        // Якщо система встановлена І процес встановлення не йде, блокуємо доступ
        if (file_exists($databaseIniFile) && !$isInstallationInProgress) {
            // Перевіряємо, чи це AJAX запит для тестування БД (дозволений під час встановлення)
            $action = $_GET['action'] ?? '';
            $isAjaxAction = ($action === 'test_db' || $action === 'create_table') && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
            
            if (!$isAjaxAction) {
                http_response_code(403);
                header('Content-Type: text/html; charset=UTF-8');
                echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ заборонено - Flowaxy CMS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 28px;
        }
        p {
            color: #666;
            margin: 0 0 30px 0;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #764ba2;
        }
        .btn-secondary:hover {
            background: #5d3a7a;
        }
    </style>
</head>
<body>
    <div class="container">
            <h1>⚠️ Доступ заборонено</h1>
            <p>Система вже встановлена. Доступ до сторінки установки блокується з метою безпеки.</p>
        <div style="margin-top: 30px;">
            <a href="/" class="btn">Перейти на головну</a>
            <a href="/admin" class="btn btn-secondary">Перейти в адмінку</a>
        </div>
    </div>
</body>
</html>';
                exit;
            }
        }
    }
}

