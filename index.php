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

// Перевірка доступності БД
if (!isDatabaseAvailable()) {
    showDatabaseError([
        'host' => DB_HOST,
        'database' => DB_NAME,
        'error' => 'Не вдалося підключитися до бази даних. Перевірте налаштування підключення.'
    ]);
    exit;
}

// НЕ ініціалізуємо плагіни тут - вони завантажуються лениво через хуки
// Ініціалізація плагінів відбувається автоматично при виклику хуків
try {
    // Тільки перевіряємо доступність PluginManager
    if (!function_exists('pluginManager')) {
        throw new Exception('PluginManager не доступний');
    }
    
    // НЕ викликаємо initializePlugins() тут - це робиться автоматично в doHook()
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

// Хук для обробки ранніх запитів (AJAX, API тощо)
$handled = doHook('handle_early_request', false);
if ($handled === true) {
    exit;
}

// Визначаємо, чи це запит до адмінки
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminRequest = strpos($requestUri, '/admin') === 0;

if ($isAdminRequest) {
    // Підключаємо шаблонизатор для адмінки
    require_once __DIR__ . '/engine/skins/includes/SimpleTemplate.php';
    
    // Завантажуємо Cache перед використанням функції cache_remember()
    if (!class_exists('Cache')) {
        $cacheFile = __DIR__ . '/engine/classes/data/Cache.php';
        if (file_exists($cacheFile)) {
            require_once $cacheFile;
        }
    }
    
    // Завантажуємо ThemeManager перед використанням функції themeManager()
    if (!class_exists('ThemeManager')) {
        $themeManagerFile = __DIR__ . '/engine/classes/managers/ThemeManager.php';
        if (file_exists($themeManagerFile)) {
            require_once $themeManagerFile;
        }
    }
    
    // Створюємо роутер для адмінки з базовим шляхом /admin
    $router = new Router('/admin', 'dashboard');
    
    // Реєструємо маршрути адмінки з окремого файлу
    require_once __DIR__ . '/engine/skins/includes/admin-routes.php';
} else {
    // Створюємо роутер для фронтенду
    $router = new Router('/', null);
    
    // Завантажуємо Cache перед використанням функції cache_remember()
    if (!class_exists('Cache')) {
        $cacheFile = __DIR__ . '/engine/classes/data/Cache.php';
        if (file_exists($cacheFile)) {
            require_once $cacheFile;
        }
    }
    
    // Завантажуємо ThemeManager перед використанням функції themeManager()
    if (!class_exists('ThemeManager')) {
        $themeManagerFile = __DIR__ . '/engine/classes/managers/ThemeManager.php';
        if (file_exists($themeManagerFile)) {
            require_once $themeManagerFile;
        }
    }
    
    // Додаємо дефолтний маршрут для фронтенду ДО автоматичної загрузки маршрутів
    // Це гарантує, що маршрут буде доступний, якщо тема не зареєструє свій
    $router->add(['GET', 'POST'], '', function() {
        // Завантажуємо активну тему
        if (function_exists('themeManager')) {
            $themeManager = themeManager();
            $activeTheme = $themeManager->getActiveTheme();
            
            if ($activeTheme !== null && isset($activeTheme['slug'])) {
                $themePath = $themeManager->getThemePath($activeTheme['slug']);
                
                if (!empty($themePath)) {
                    // Спробуємо завантажити index.php теми
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
        http_response_code(200);
        echo '<!DOCTYPE html>
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
</html>';
        return true;
    });
    
    // Хук для реєстрації маршрутів НЕ викликаємо тут, він буде викликаний в autoLoad()
    // Це уникне подвійного виклику хука
}

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$router->dispatch();

