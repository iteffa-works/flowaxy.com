<?php
/**
 * Універсальний роутер для адмінки та фронтенду
 * Підтримує автоматичну завантаження маршрутів, API для плагінів, middleware та параметри
 * 
 * @package Engine\Classes\Http
 * @version 2.0.0
 */

declare(strict_types=1);

class Router {
    private array $routes = [];
    private array $middlewares = [];
    private array $groups = [];
    private ?string $basePath = null;
    private ?string $defaultRoute = null;
    private bool $autoLoadEnabled = true;
    
    /**
     * Конструктор
     * 
     * @param string|null $basePath Базовий шлях (наприклад '/admin' або '/')
     * @param string|null $defaultRoute Маршрут за замовчуванням
     */
    public function __construct(?string $basePath = null, ?string $defaultRoute = null) {
        $this->basePath = $basePath;
        $this->defaultRoute = $defaultRoute;
    }
    
    /**
     * Додавання маршруту
     * Підтримує два формати:
     * 1. add('GET', $path, $handler, $options) - новий формат
     * 2. add($path, $handler) - старий формат (для зворотної сумісності)
     * 
     * @param string|array $methods HTTP методи або шлях (для зворотної сумісності)
     * @param string|callable $path Шлях маршруту або обробник
     * @param string|callable|null $handler Обробник або опції
     * @param array $options Додаткові опції
     * @return self
     */
    public function add($methods, $path = null, $handler = null, array $options = []): self {
        // Перевірка на старий формат: add($path, $handler)
        // Якщо другий параметр - це callable або string (назва класу), а $methods - це шлях (не HTTP метод)
        if ($path !== null && 
            (is_callable($path) || is_string($path)) && 
            is_string($methods) && 
            !in_array(strtoupper($methods), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'])) {
            // Старий формат: add($path, $handler)
            $options = is_array($handler) ? $handler : [];
            $handler = $path;
            $path = $methods;
            $methods = ['GET'];
        }
        
        $methods = is_array($methods) ? $methods : [$methods];
        $methods = array_map('strtoupper', $methods);
        
        // Переконуємося, що $path та $handler встановлені
        if ($path === null || $handler === null) {
            throw new InvalidArgumentException("Router::add() requires path and handler parameters");
        }
        
        $route = [
            'methods' => $methods,
            'path' => trim($path, '/'), // Сохраняем оригинальный путь без слэшей для точного сравнения
            'normalizedPath' => $this->normalizePath($path), // Нормализованный путь для паттерна
            'handler' => $handler,
            'middleware' => $options['middleware'] ?? [],
            'name' => $options['name'] ?? null,
            'params' => $this->extractParams($path),
            'pattern' => $this->pathToPattern($path),
        ];
        
        $this->routes[] = $route;
        
        return $this;
    }
    
    /**
     * Додавання GET маршруту
     */
    public function get(string $path, $handler, array $options = []): self {
        return $this->add('GET', $path, $handler, $options);
    }
    
    /**
     * Додавання POST маршруту
     */
    public function post(string $path, $handler, array $options = []): self {
        return $this->add('POST', $path, $handler, $options);
    }
    
    /**
     * Додавання PUT маршруту
     */
    public function put(string $path, $handler, array $options = []): self {
        return $this->add('PUT', $path, $handler, $options);
    }
    
    /**
     * Додавання DELETE маршруту
     */
    public function delete(string $path, $handler, array $options = []): self {
        return $this->add('DELETE', $path, $handler, $options);
    }
    
    /**
     * Додавання маршруту для будь-якого методу
     */
    public function any(string $path, $handler, array $options = []): self {
        return $this->add(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $path, $handler, $options);
    }
    
    /**
     * Групування маршрутів з префіксом та middleware
     */
    public function group(string $prefix, callable $callback, array $options = []): self {
        $oldPrefix = $this->basePath;
        $oldMiddlewares = $this->middlewares;
        
        $this->basePath = $this->normalizePath(($oldPrefix ?? '') . '/' . $prefix);
        if (!empty($options['middleware'])) {
            $this->middlewares = array_merge($this->middlewares, (array)$options['middleware']);
        }
        
        $callback($this);
        
        $this->basePath = $oldPrefix;
        $this->middlewares = $oldMiddlewares;
        
        return $this;
    }
    
    /**
     * Реєстрація middleware
     */
    public function middleware(string $name, callable $handler): self {
        $this->middlewares[$name] = $handler;
        return $this;
    }
    
    /**
     * Автоматична завантаження маршрутів з модулів, плагінів та теми
     */
    public function autoLoad(): void {
        if (!$this->autoLoadEnabled) {
            return;
        }
        
        // Завантаження маршрутів з модулів
        $this->loadModuleRoutes();
        
        // Завантаження маршрутів з плагінів через хук
        doHook('register_routes', $this);
        
        // Завантаження маршрутів з теми
        $this->loadThemeRoutes();
    }
    
    /**
     * Завантаження маршрутів з модулів
     */
    private function loadModuleRoutes(): void {
        // Модулі реєструють свої маршрути через хук admin_register_routes
        // Цей метод викликається, але маршрути реєструються через хуки
        // для зворотної сумісності з існуючим кодом
    }
    
    /**
     * Завантаження маршрутів з теми
     */
    private function loadThemeRoutes(): void {
        if (!function_exists('themeManager')) {
            return;
        }
        
        $themeManager = themeManager();
        $activeTheme = $themeManager->getActiveTheme();
        
        if ($activeTheme === null || !isset($activeTheme['slug'])) {
            return;
        }
        
        // Передаем slug темы, а не весь массив
        $themePath = $themeManager->getThemePath($activeTheme['slug']);
        if (empty($themePath)) {
            return;
        }
        
        $routesFile = $themePath . 'routes.php';
        $file = new File($routesFile);
        
        if ($file->exists() && $file->isReadable()) {
            $router = $this;
            require_once $routesFile;
        }
    }
    
    /**
     * Отримання поточного URI
     */
    private function getCurrentUri(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Видаляємо query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Видаляємо базовий шлях, якщо встановлено
        if ($this->basePath !== null && $this->basePath !== '/') {
            $basePath = rtrim($this->basePath, '/');
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
                // Нормализуем URI: убираем лишние слэши
                $uri = '/' . ltrim($uri, '/');
                // Если остался только слэш, возвращаем пустую строку
                if ($uri === '/') {
                    $uri = '';
                }
            }
        }
        
        // Видаляємо index.php (включая случаи с путями типа /index.php/path)
        $uri = preg_replace('#/index\.php(/.*)?$#', '$1', $uri);
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Видаляємо розширення .php якщо є
        if (preg_match('/^(.+)\.php$/', $uri, $matches)) {
            $uri = $matches[1];
        }
        
        // Возвращаем путь без начальных и конечных слэшей для точного сравнения
        $result = trim($uri, '/');
        
        return $result;
    }
    
    /**
     * Нормалізація шляху
     * Увага: НЕ додає basePath до path для паттерну, це робиться в getCurrentUri()
     */
    private function normalizePath(string $path): string {
        // Просто нормалізуємо шлях без додавання базового шляху
        // Базовий шлях видаляється з URI в getCurrentUri(), а не додається до паттерну
        $path = trim($path, '/');
        // Для пустого пути возвращаем пустую строку, для остальных - путь со слэшем
        return empty($path) ? '' : '/' . $path;
    }
    
    /**
     * Витягування параметрів з шляху
     */
    private function extractParams(string $path): array {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * Перетворення шляху на regex pattern
     */
    private function pathToPattern(string $path): string {
        // Нормалізуємо шлях для pattern
        $normalizedPath = $this->normalizePath($path);
        
        // Якщо шлях порожній, він має відповідати кореневому маршруту
        if (empty($normalizedPath) || $normalizedPath === '/') {
            return '/^\/?$/';
        }
        
        // Убираем начальный слэш для паттерна, так как он будет добавлен в uriPath
        $normalizedPath = ltrim($normalizedPath, '/');
        
        $pattern = preg_quote($normalizedPath, '/');
        $pattern = str_replace('\{', '(?P<', $pattern);
        $pattern = str_replace('\}', '>[^/]+)', $pattern);
        return '/^\/' . $pattern . '$/';
    }
    
    /**
     * Обробка запиту
     */
    public function dispatch(): bool {
        // Автоматична завантаження маршрутів
        $this->autoLoad();
        
        $uri = $this->getCurrentUri();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Сначала проверяем, есть ли маршрут для пустого пути
        $hasEmptyRoute = false;
        foreach ($this->routes as $route) {
            if (($route['path'] === '' || empty($route['path'])) && in_array($method, $route['methods'])) {
                $hasEmptyRoute = true;
                break;
            }
        }
        
        // Если пути пустой и нет маршрута для пустого пути, используем маршрут за замовчуванням
        if (empty($uri) && !$hasEmptyRoute && $this->defaultRoute !== null) {
            $uri = $this->defaultRoute;
        }
        
        // Пошук відповідного маршруту
        foreach ($this->routes as $route) {
            // Перевірка HTTP методу
            if (!in_array($method, $route['methods'])) {
                continue;
            }
            
            // Перевірка шляху
            $params = [];
            // Для порожнього URI використовуємо '/', для інших - '/path'
            $uriPath = empty($uri) ? '/' : '/' . $uri;
            
            // Сначала проверяем точное совпадение пути (быстрее чем regex)
            // route['path'] теперь хранится без слэшей, так же как и $uri
            if ($route['path'] === $uri) {
                // Виконання middleware
                if (!$this->runMiddlewares($route['middleware'], $params)) {
                    return false;
                }
                
                // Виконання обробника
                return $this->executeHandler($route['handler'], $params);
            }
            
            // Додатково перевіряємо, чи порожній URI відповідає порожньому маршруту
            if (empty($uri) && empty($route['path'])) {
                // Виконання middleware
                if (!$this->runMiddlewares($route['middleware'], $params)) {
                    return false;
                }
                
                // Виконання обробника
                return $this->executeHandler($route['handler'], $params);
            }
            
            // Используем regex pattern для маршрутов с параметрами
            if (preg_match($route['pattern'], $uriPath, $matches)) {
                // Витягування параметрів
                foreach ($route['params'] as $param) {
                    if (isset($matches[$param])) {
                        $params[$param] = $matches[$param];
                    }
                }
                
                // Виконання middleware
                if (!$this->runMiddlewares($route['middleware'], $params)) {
                    return false;
                }
                
                // Виконання обробника
                return $this->executeHandler($route['handler'], $params);
            }
        }
        
        // Маршрут не знайдено
        // Для отладки (можно убрать после тестирования)
        // error_log("Router::dispatch() - Route not found. URI: {$uri}, Method: {$method}, BasePath: {$this->basePath}, Routes count: " . count($this->routes));
        $this->show404();
        return false;
    }
    
    /**
     * Виконання middleware
     */
    private function runMiddlewares(array $middlewareNames, array $params): bool {
        foreach ($middlewareNames as $middlewareName) {
            if (isset($this->middlewares[$middlewareName])) {
                $result = call_user_func($this->middlewares[$middlewareName], $params);
                if ($result === false) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Виконання обробника маршруту
     */
    private function executeHandler($handler, array $params): bool {
        if (is_callable($handler)) {
            return call_user_func($handler, $params) !== false;
        }
        
        if (is_string($handler) && class_exists($handler)) {
            $page = new $handler();
            if (method_exists($page, 'handle')) {
                // Передаємо параметри як властивості об'єкту або через метод
                if (method_exists($page, 'setParams')) {
                    $page->setParams($params);
                }
                $page->handle();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Отримання поточного маршруту (статичний метод для зворотної сумісності)
     */
    public static function getCurrentPage(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Видаляємо query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Видаляємо базовий шлях адмінки
        $adminPath = '/admin';
        if (strpos($uri, $adminPath) === 0) {
            $uri = substr($uri, strlen($adminPath));
        }
        
        // Видаляємо index.php
        $uri = str_replace('index.php', '', $uri);
        
        // Видаляємо розширення .php якщо є
        if (preg_match('/^(.+)\.php$/', $uri, $matches)) {
            $uri = $matches[1];
        }
        
        $uri = trim($uri, '/');
        
        // Якщо шлях порожній, повертаємо 'dashboard' для адмінки
        if (empty($uri) && (strpos($_SERVER['REQUEST_URI'] ?? '/', '/admin') === 0)) {
            return 'dashboard';
        }
        
        return $uri;
    }
    
    /**
     * Генерація URL для маршруту за іменем
     */
    public function url(string $routeName, array $params = []): ?string {
        foreach ($this->routes as $route) {
            if ($route['name'] === $routeName) {
                // Используем normalizedPath для генерации URL, чтобы сохранить структуру с параметрами
                $url = isset($route['normalizedPath']) ? trim($route['normalizedPath'], '/') : $route['path'];
                foreach ($params as $key => $value) {
                    $url = str_replace('{' . $key . '}', $value, $url);
                }
                return $url;
            }
        }
        return null;
    }
    
    /**
     * Відображення сторінки 404
     */
    private function show404(): void {
        (new Response())->status(404)->send();
        
        // Перевіряємо, чи є кастомна сторінка 404 у темі
        if (function_exists('themeManager')) {
            $themeManager = themeManager();
            $themePath = $themeManager->getThemePath();
            $error404File = $themePath . '404.php';
            $file = new File($error404File);
            
            if ($file->exists()) {
                include $error404File;
                return;
            }
        }
        
        // Стандартна сторінка 404
        echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Сторінка не знайдена</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f7fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .container { max-width: 600px; padding: 40px 20px; }
        h1 { font-size: 120px; color: #667eea; margin-bottom: 20px; }
        h2 { font-size: 28px; color: #2d3748; margin-bottom: 16px; }
        p { font-size: 18px; color: #718096; margin-bottom: 32px; }
        a {
            display: inline-block;
            padding: 12px 32px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s;
        }
        a:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Сторінка не знайдена</h2>
        <p>Запитувана сторінка не існує</p>
        <a href="/">На головну</a>
    </div>
</body>
</html>';
    }
    
    /**
     * API для плагінів: Додавання маршруту
     */
    public function addRoute($methods, string $path, $handler, array $options = []): self {
        return $this->add($methods, $path, $handler, $options);
    }
    
    /**
     * API для плагінів: Отримання всіх маршрутів
     */
    public function getRoutes(): array {
        return $this->routes;
    }
    
    /**
     * API для плагінів: Додавання групи маршрутів
     */
    public function addGroup(string $prefix, callable $callback, array $options = []): self {
        return $this->group($prefix, $callback, $options);
    }
    
    /**
     * API для плагінів: Додавання middleware
     */
    public function addMiddleware(string $name, callable $handler): self {
        return $this->middleware($name, $handler);
    }
}

/**
 * Глобальна функція для генерації URL адмінки (для зворотної сумісності)
 * 
 * @param string $path Шлях
 * @return string URL
 */
function adminUrl(string $path = ''): string {
    return '/admin/' . ltrim($path, '/');
}


