<?php
/**
 * Менеджер маршрутизации системы
 * Определяет контекст (админка / API / публичная часть) и управляет маршрутами
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
     * Получение экземпляра (Singleton)
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
     * Получение объекта Router
     * 
     * @return Router
     */
    public function getRouter(): Router {
        return $this->router;
    }

    /**
     * Получение текущего контекста
     * 
     * @return string
     */
    public function getContext(): string {
        return $this->context;
    }

    /**
     * Проверка, является ли текущий контекст админкой
     * 
     * @return bool
     */
    public function isAdmin(): bool {
        return $this->context === self::CONTEXT_ADMIN;
    }

    /**
     * Проверка, является ли текущий контекст API
     * 
     * @return bool
     */
    public function isApi(): bool {
        return $this->context === self::CONTEXT_API;
    }

    /**
     * Проверка, является ли текущий контекст публичной частью
     * 
     * @return bool
     */
    public function isPublic(): bool {
        return $this->context === self::CONTEXT_PUBLIC;
    }

    /**
     * Получение базового пути для текущего контекста
     * 
     * @return string
     */
    public function getBasePath(): string {
        return $this->basePath ?? '/';
    }

    /**
     * Выполнение диспетчеризации маршрутов
     * 
     * @return void
     */
    public function dispatch(): void {
        // Блокировка доступа к /install, если система уже установлена
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        
        if (strpos($path, '/install') === 0) {
            $this->blockInstallerIfInstalled();
        }

        // Обработка AJAX запросов
        if (class_exists('AjaxHandler') && AjaxHandler::isAjax()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('display_errors', '0');
        }

        // Загружаем дополнительные маршруты из плагинов, если еще не загружены
        if (!$this->routesLoaded) {
            $this->loadPluginRoutes();
            $this->routesLoaded = true;
        }

        $this->router->dispatch();
    }

    /**
     * Добавление маршрута в текущий контекст
     * 
     * @param string|array $methods HTTP методы (GET, POST, PUT, DELETE и т.д.)
     * @param string $path Путь маршрута
     * @param callable|string $handler Обработчик (функция, замыкание, класс)
     * @param array $options Дополнительные опции (middleware, name, etc.)
     * @return self
     */
    public function addRoute($methods, string $path, $handler, array $options = []): self {
        $this->router->add($methods, $path, $handler, $options);
        return $this;
    }

    /**
     * Регистрация страницы админки в виде класса
     * 
     * @param string $slug Slug страницы
     * @param string $className Имя класса страницы
     * @param array $options Дополнительные опции
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
     * Выполнение callback только в определенном контексте
     * 
     * @param string $context Контекст (CONTEXT_ADMIN, CONTEXT_API, CONTEXT_PUBLIC)
     * @param callable $callback Callback функция
     * @return void
     */
    public function when(string $context, callable $callback): void {
        if ($this->context === $context) {
            $callback($this->router, $this);
        }
    }

    /**
     * Определение контекста на основе URI
     * Оптимизированная версия с улучшенной обработкой путей
     * 
     * @return void
     */
    private function detectContext(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Нормализация пути
        $path = $this->normalizePath($path);

        // Проверяем контексты в порядке приоритета
        // 1. API (высший приоритет)
        if ($this->isApiPath($path)) {
            $this->context = self::CONTEXT_API;
            $this->basePath = $this->detectApiBasePath($path);
            return;
        }

        // 2. Админка
        if ($this->isAdminPath($path)) {
            $this->context = self::CONTEXT_ADMIN;
            $this->basePath = '/admin';
            return;
        }

        // 3. Публичная часть (по умолчанию)
        $this->context = self::CONTEXT_PUBLIC;
        $this->basePath = '/';
    }

    /**
     * Нормализация пути (удаление дублирующихся слэшей, очистка)
     * 
     * @param string $path Путь для нормализации
     * @return string Нормализованный путь
     */
    private function normalizePath(string $path): string {
        // Удаление дублирующихся слэшей
        $path = preg_replace('#/+#', '/', $path);
        
        // Удаление конечного слэша (кроме корня)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        // Убеждаемся, что путь начинается со слэша
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        
        return $path;
    }

    /**
     * Проверка, является ли путь API путём
     * 
     * @param string $path Путь для проверки
     * @return bool
     */
    private function isApiPath(string $path): bool {
        // Проверяем различные варианты API путей
        // /api, /api/, /api/v1, /api/v2, /api-v1 и т.д.
        return preg_match('#^/api(/v\d+)?(/|$)#', $path) === 1 ||
               preg_match('#^/api-v?\d+#', $path) === 1;
    }

    /**
     * Определение базового пути для API
     * 
     * @param string $path Путь запроса
     * @return string Базовый путь API
     */
    private function detectApiBasePath(string $path): string {
        // Проверяем версию API в пути /api/v1, /api/v2 и т.д.
        if (preg_match('#^/api/v(\d+)#', $path, $matches)) {
            return '/api/v' . $matches[1];
        }
        
        // Проверяем старый формат api-v1, api-v2 и т.д.
        if (preg_match('#^/api-v(\d+)#', $path, $matches)) {
            return '/api/v' . $matches[1];
        }
        
        // По умолчанию используем v1
        return '/api/v1';
    }

    /**
     * Проверка, является ли путь админским путём
     * 
     * @param string $path Путь для проверки
     * @return bool
     */
    private function isAdminPath(string $path): bool {
        // Точное совпадение /admin или путь начинается с /admin/
        return $path === '/admin' || strpos($path, '/admin/') === 0;
    }

    /**
     * Инициализация Router с нужными параметрами
     * 
     * @return void
     * @throws RuntimeException
     */
    private function bootRouter(): void {
        if (!class_exists('Router')) {
            throw new RuntimeException('Router class not found');
        }

        // Определяем маршрут по умолчанию для админки
        $defaultRoute = $this->context === self::CONTEXT_ADMIN ? 'dashboard' : null;

        $this->router = new Router($this->basePath, $defaultRoute);
    }

    /**
     * Регистрация основных маршрутов в зависимости от контекста
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
     * Загрузка маршрутов API
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
     * Загрузка маршрутов админки
     * 
     * @return void
     */
    private function loadAdminRoutes(): void {
        // Загружаем SimpleTemplate если он есть
        $simpleTemplate = __DIR__ . '/../../skins/includes/SimpleTemplate.php';
        if (file_exists($simpleTemplate)) {
            require_once $simpleTemplate;
        }

        // Загружаем маршруты админки
        $routesFile = __DIR__ . '/../../skins/includes/admin-routes.php';
        if (file_exists($routesFile)) {
            $router = $this->router;
            require $routesFile;
        }

        // Позволяем плагинам добавлять свои маршруты через хук
        if (function_exists('doHook')) {
            doHook('admin_routes', [$this->router, $this]);
        }
    }

    /**
     * Загрузка маршрутов из плагинов
     * 
     * @return void
     */
    private function loadPluginRoutes(): void {
        if (!function_exists('doHook')) {
            return;
        }

        // Хук для добавления маршрутов плагинами
        doHook('register_routes', [$this->router, $this]);
    }

    /**
     * Регистрация публичных маршрутов
     * 
     * @return void
     */
    private function registerPublicRoutes(): void {
        // Основной маршрут для публичной части
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

            // Fallback если тема не найдена
            return renderThemeFallback();
        });

        // Позволяем плагинам добавлять свои публичные маршруты
        if (function_exists('doHook')) {
            doHook('public_routes', [$this->router, $this]);
        }
    }

    /**
     * Очистка кэша маршрутов
     * 
     * @return void
     */
    public function clearCache(): void {
        $this->routeCache = [];
        $this->routesLoaded = false;
    }

    /**
     * Получение списка всех зарегистрированных маршрутов (для отладки)
     * 
     * @return array
     */
    public function getRoutes(): array {
        // Если Router имеет метод для получения маршрутов
        if (method_exists($this->router, 'getRoutes')) {
            return $this->router->getRoutes();
        }
        return [];
    }

    /**
     * Блокировка доступа к установщику, если система уже установлена
     * 
     * @return void
     */
    private function blockInstallerIfInstalled(): void {
        $databaseIniFile = __DIR__ . '/../../data/database.ini';
        
        // Проверяем, идет ли процесс установки (есть настройки БД в сессии)
        $isInstallationInProgress = isset($_SESSION['install_db_config']) && is_array($_SESSION['install_db_config']);
        
        // Если система установлена И процесс установки не идет, блокируем доступ
        if (file_exists($databaseIniFile) && !$isInstallationInProgress) {
            // Проверяем, это AJAX запрос для тестирования БД (разрешен во время установки)
            $action = $_GET['action'] ?? '';
            $isAjaxAction = ($action === 'test_db' || $action === 'create_table') && $_SERVER['REQUEST_METHOD'] === 'POST';
            
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

