<?php
/**
 * Центральний роутер системи
 * Визначає контекст (адмінка / API / публічна частина) та інкапсулює Router
 *
 * @package Engine\Classes\Http
 */

declare(strict_types=1);

class CentralRouter {
    public const CONTEXT_ADMIN = 'admin';
    public const CONTEXT_API = 'api';
    public const CONTEXT_PUBLIC = 'public';

    private static ?self $instance = null;

    private Router $router;
    private string $context = self::CONTEXT_PUBLIC;
    private ?string $basePath = null;

    /**
     * @throws Exception
     */
    private function __construct() {
        $this->detectContext();
        $this->bootRouter();
        $this->registerCoreRoutes();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRouter(): Router {
        return $this->router;
    }

    public function getContext(): string {
        return $this->context;
    }

    public function isAdmin(): bool {
        return $this->context === self::CONTEXT_ADMIN;
    }

    public function isApi(): bool {
        return $this->context === self::CONTEXT_API;
    }

    public function dispatch(): void {
        if (class_exists('AjaxHandler') && AjaxHandler::isAjax()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('display_errors', '0');
        }

        $this->router->dispatch();
    }

    /**
     * Додає маршрут у поточний контекст (спрощення для плагінів)
     */
    public function addRoute($methods, string $path, $handler, array $options = []): self {
        $this->router->add($methods, $path, $handler, $options);
        return $this;
    }

    /**
     * Реєстрація сторінки адмінки у вигляді класу
     */
    public function registerAdminPage(string $slug, string $className, array $options = []): void {
        if ($this->context !== self::CONTEXT_ADMIN) {
            return;
        }
        $methods = $options['methods'] ?? ['GET', 'POST'];
        $this->router->add($methods, $slug, $className, $options);
    }

    /**
     * Дозволяє виконати callback лише у певному контексті
     */
    public function when(string $context, callable $callback): void {
        if ($this->context === $context) {
            $callback($this->router, $this);
        }
    }

    private function detectContext(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (strpos($uri, '/api/') === 0 || strpos($uri, '/api-v1/') === 0 || strpos($uri, '/api/v1/') === 0) {
            $this->context = self::CONTEXT_API;
            $this->basePath = '/api/v1';
            return;
        }

        if (strpos($uri, '/admin') === 0) {
            $this->context = self::CONTEXT_ADMIN;
            $this->basePath = '/admin';
            return;
        }

        $this->context = self::CONTEXT_PUBLIC;
        $this->basePath = '/';
    }

    private function bootRouter(): void {
        $defaultRoute = $this->context === self::CONTEXT_ADMIN ? 'dashboard' : null;

        if (!class_exists('Router')) {
            throw new RuntimeException('Router class not found');
        }

        $this->router = new Router($this->basePath, $defaultRoute);
    }

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

    private function loadApiRoutes(): void {
        $router = $this->router;
        $routesFile = __DIR__ . '/../../includes/api-routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    private function loadAdminRoutes(): void {
        $simpleTemplate = __DIR__ . '/../../skins/includes/SimpleTemplate.php';
        if (file_exists($simpleTemplate)) {
            require_once $simpleTemplate;
        }

        $router = $this->router;
        $routesFile = __DIR__ . '/../../skins/includes/admin-routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    private function registerPublicRoutes(): void {
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

            return renderThemeFallback();
        });
    }
}


