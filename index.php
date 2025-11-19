<?php
/**
 * Головна точка входу системи
 * Універсальна обробка як фронтенду, так і адмінки
 * 
 * @version 6.0.0
 */

declare(strict_types=1);

// Підключаємо ініціалізацію системи
require_once __DIR__ . '/engine/init.php';

/**
 * Перевірка та ініціалізація системи
 */
function initializeSystem(): void {
    // Перевірка доступності БД
    if (!DatabaseHelper::isAvailable()) {
        showDatabaseError([
            'host' => DB_HOST,
            'database' => DB_NAME,
            'error' => 'Не вдалося підключитися до бази даних. Перевірте налаштування підключення.'
        ]);
        exit;
    }
    
    // Перевірка доступності PluginManager (плагіни завантажуються лениво через хуки)
    try {
        if (!function_exists('pluginManager')) {
            throw new Exception('PluginManager не доступний');
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'database') !== false || strpos($e->getMessage(), 'PDO') !== false) {
            showDatabaseError([
                'host' => DB_HOST,
                'database' => DB_NAME,
                'error' => $e->getMessage()
            ]);
            exit;
        }
        throw $e;
    }
}

/**
 * Завантаження необхідних класів для роботи системи
 * 
 * @return void
 */
function loadRequiredClasses(): void {
    // Cache завантажується автоматично через autoloader
    // Але перевіряємо для впевненості
    if (!class_exists('Cache')) {
        $cacheFile = __DIR__ . '/engine/classes/data/Cache.php';
        if (file_exists($cacheFile)) {
            require_once $cacheFile;
        }
    }
}

/**
 * Рендеринг fallback сторінки коли тема не встановлена
 * 
 * @return bool
 */
function renderThemeFallback(): bool {
    http_response_code(200);
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Встановіть тему</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <h1>Встановіть тему</h1>
                    <p class="text-muted">Для відображення сайту необхідно встановити та активувати тему.</p>
                    <a href="/admin/themes" class="btn btn-primary">Перейти до тем</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return true;
}

// Ініціалізація системи
initializeSystem();

// Хук для обробки ранніх запитів (AJAX, API тощо)
$handled = doHook('handle_early_request', false);
if ($handled === true) {
    exit;
}

// Завантаження необхідних класів
loadRequiredClasses();

// Визначаємо тип запиту та створюємо роутер
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminRequest = strpos($requestUri, '/admin') === 0;

if ($isAdminRequest) {
    // Підключаємо шаблонизатор для адмінки
    require_once __DIR__ . '/engine/skins/includes/SimpleTemplate.php';
    
    // Створюємо роутер для адмінки з базовим шляхом /admin
    $router = new Router('/admin', 'dashboard');
    
    // Реєструємо маршрути адмінки з окремого файлу
    require_once __DIR__ . '/engine/skins/includes/admin-routes.php';
} else {
    // Створюємо роутер для фронтенду
    $router = new Router('/', null);
    
    // Додаємо дефолтний маршрут для фронтенду
    // Це гарантує, що маршрут буде доступний, якщо тема не зареєструє свій
    $router->add(['GET', 'POST'], '', function() {
        // Завантажуємо активну тему
        if (function_exists('themeManager')) {
            $themeManager = themeManager();
            $activeTheme = $themeManager->getActiveTheme();
            
            if ($activeTheme !== null && isset($activeTheme['slug'])) {
                $themePath = $themeManager->getThemePath($activeTheme['slug']);
                
                if (!empty($themePath)) {
                    $themeIndexFile = $themePath . 'index.php';
                    if (file_exists($themeIndexFile)) {
                        // Передаємо дані теми в шаблон
                        $themeData = [
                            'theme_path' => $themePath,
                            'theme_url' => '/themes/' . $activeTheme['slug'],
                            'theme_slug' => $activeTheme['slug']
                        ];
                        
                        // Включаємо шаблон теми
                        extract($themeData);
                        include $themeIndexFile;
                        return true;
                    }
                }
            }
        }
        
        // Якщо тема не завантажена, показуємо повідомлення
        return renderThemeFallback();
    });
}

// Для AJAX запросов очищаем буфер вывода перед обработкой
if (AjaxHandler::isAjax()) {
    // Очищаем все уровни буфера вывода
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Отключаем вывод ошибок на экран для AJAX
    ini_set('display_errors', '0');
}

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$router->dispatch();

