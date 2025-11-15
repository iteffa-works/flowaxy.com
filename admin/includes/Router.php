<?php
/**
 * Простой роутер для админки
 * Обрабатывает ЧПУ и загружает нужные страницы
 */

class Router {
    private $routes = [];
    private $defaultRoute = 'dashboard';
    
    /**
     * Регистрация маршрута
     */
    public function add($path, $pageClass) {
        $this->routes[$path] = $pageClass;
    }
    
    /**
     * Получение текущего пути
     */
    private function getCurrentPath() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Убираем базовый путь админки
        $adminPath = '/admin/';
        if (strpos($uri, $adminPath) === 0) {
            $uri = substr($uri, strlen($adminPath));
        }
        
        // Убираем query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Убираем index.php
        $uri = str_replace('index.php', '', $uri);
        
        // Убираем расширение .php если есть
        if (preg_match('/^(.+)\.php$/', $uri, $matches)) {
            $uri = $matches[1];
        }
        
        // Убираем слеши
        $uri = trim($uri, '/');
        
        // Возвращаем путь (пустая строка для корня)
        return $uri;
    }
    
    /**
     * Получение текущего пути (публичный метод)
     */
    public function getCurrentRoute() {
        return $this->getCurrentPath();
    }
    
    /**
     * Статический метод для получения текущего маршрута
     */
    public static function getCurrentPage() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Убираем базовый путь админки
        $adminPath = '/admin/';
        if (strpos($uri, $adminPath) === 0) {
            $uri = substr($uri, strlen($adminPath));
        }
        
        // Убираем query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Убираем index.php
        $uri = str_replace('index.php', '', $uri);
        
        // Убираем расширение .php если есть
        if (preg_match('/^(.+)\.php$/', $uri, $matches)) {
            $uri = $matches[1];
        }
        
        // Убираем слеши
        $uri = trim($uri, '/');
        
        // Возвращаем путь (пустая строка для корня)
        return $uri;
    }
    
    /**
     * Обработка запроса
     */
    public function dispatch() {
        $path = $this->getCurrentPath();
        
        // Если путь пустой, используем defaultRoute
        if (empty($path)) {
            $path = $this->defaultRoute;
        }
        
        // Проверяем, есть ли маршрут для текущего пути
        if (isset($this->routes[$path])) {
            $pageClass = $this->routes[$path];
            
            // Проверяем, существует ли класс
            if (class_exists($pageClass)) {
                $page = new $pageClass();
                $page->handle();
                return true;
            }
        }
        
        // Если маршрут не найден, показываем 404
        $this->show404();
        return false;
    }
    
    /**
     * Показать 404
     */
    private function show404() {
        http_response_code(404);
        echo '<!DOCTYPE html>
        <html lang="uk">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Сторінка не знайдена</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <h1 class="display-1">404</h1>
                        <h2>Сторінка не знайдена</h2>
                        <p class="text-muted">Запитувана сторінка не існує</p>
                        <a href="/admin/" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>На головну
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Генерация URL
     */
    public static function url($path) {
        return '/admin/' . ltrim($path, '/');
    }
}

/**
 * Глобальная функция для генерации URL
 */
function adminUrl($path = '') {
    return Router::url($path);
}
