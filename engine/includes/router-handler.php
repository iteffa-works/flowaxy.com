<?php
/**
 * Обработчик роутинга
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

// Получаем переменную из flowaxy.php
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminRequest = strpos($requestUri, '/admin') === 0;
$isApiRequest = strpos($requestUri, '/api/') === 0 || strpos($requestUri, '/api/v1/') === 0;

// API запросы
if ($isApiRequest) {
    $router = new Router('/api/v1');
    require_once __DIR__ . '/api-routes.php';
} elseif ($isAdminRequest) {
    require_once __DIR__ . '/../skins/includes/SimpleTemplate.php';
    $router = new Router('/admin', 'dashboard');
    require_once __DIR__ . '/../skins/includes/admin-routes.php';
} else {
    $router = new Router('/', null);
    $router->add(['GET', 'POST'], '', function() {
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

// AJAX запросы
if (AjaxHandler::isAjax()) {
    while (ob_get_level() > 0) ob_end_clean();
    ini_set('display_errors', '0');
}

// Обработка запроса
$router->dispatch();

