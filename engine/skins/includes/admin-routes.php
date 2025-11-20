<?php
/**
 * Реєстрація маршрутів адмінки
 * Використовується в index.php для автоматичної реєстрації маршрутів адмінки
 * 
 * @param Router $router Роутер для адмінки
 * @return void
 */

// Реєструємо базові маршрути адмінки
// Увага: всі сторінки адмінки повинні підтримувати GET та POST для AJAX запитів
$router->add(['GET', 'POST'], '', 'DashboardPage');
$router->add(['GET', 'POST'], 'dashboard', 'DashboardPage');
$router->add(['GET', 'POST'], 'login', 'LoginPage');
$router->add(['GET', 'POST'], 'logout', 'LogoutPage');
$router->add(['GET', 'POST'], 'settings', 'SettingsPage');
$router->add(['GET', 'POST'], 'cache-view', 'CacheViewPage');
$router->add(['GET', 'POST'], 'logs-view', 'LogsViewPage');
$router->add(['GET', 'POST'], 'profile', 'ProfilePage');
$router->add(['GET', 'POST'], 'plugins', 'PluginsPage');
$router->add(['GET', 'POST'], 'themes', 'ThemesPage');
$router->add(['GET', 'POST'], 'theme-editor', 'ThemeEditorPage');
$router->add(['GET', 'POST'], 'api-keys', 'ApiKeysPage');
$router->add(['GET', 'POST'], 'webhooks', 'WebhooksPage');

// Реєструємо маршрут кастомізатора тільки якщо активна тема підтримує кастомізацію
require_once __DIR__ . '/menu-items.php';
if (themeSupportsCustomization()) {
    $router->add(['GET', 'POST'], 'customizer', 'CustomizerPage');
}

// Маршрут меню тепер реєструється через плагін menu-plugin

// Инициализируем плагины перед вызовом хука, чтобы они могли зарегистрировать свои маршруты
if (function_exists('pluginManager')) {
    $pluginManager = pluginManager();
    if ($pluginManager && method_exists($pluginManager, 'initializePlugins')) {
        $pluginManager->initializePlugins();
    }
}

// Хук для реєстрації маршрутів модулів та плагінів
doHook('admin_register_routes', $router);

// Реєструємо маршрути плагінів
$activePlugins = pluginManager()->getActivePlugins();
foreach ($activePlugins as $slug => $plugin) {
    $pluginDir = dirname(__DIR__, 3) . '/plugins/' . $slug;
    
    // Перетворюємо slug в ім'я класу адмін-сторінки
    $parts = explode('-', $slug);
    $className = '';
    foreach ($parts as $part) {
        $className .= ucfirst($part);
    }
    $className .= 'AdminPage';
    
    $adminPageFile = $pluginDir . '/admin/' . $className . '.php';
    
    // Використовуємо File клас для перевірки існування файлу
    if (class_exists('File')) {
        $file = new File($adminPageFile);
        if ($file->exists()) {
            require_once $adminPageFile;
            $router->add(['GET', 'POST'], $slug, $className);
        }
    } elseif (file_exists($adminPageFile)) {
        require_once $adminPageFile;
        $router->add(['GET', 'POST'], $slug, $className);
    }
    
    // Специальная регистрация маршрута /admin/menus для плагина menu-plugin
    if ($slug === 'menu-plugin') {
        $menusPageFile = $pluginDir . '/admin/MenusPage.php';
        if (file_exists($menusPageFile)) {
            require_once $menusPageFile;
            if (class_exists('MenusPage')) {
                $router->add(['GET', 'POST'], 'menus', 'MenusPage');
            }
        }
    }
}

