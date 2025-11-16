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

// Ініціалізація плагінів
try {
    pluginManager()->initializePlugins();
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
    
    // Хук для реєстрації маршрутів (плагіни та теми можуть реєструвати свої маршрути)
    doHook('register_routes', $router);
}

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$router->dispatch();

