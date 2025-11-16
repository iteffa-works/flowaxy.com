<?php
/**
 * Точка входу для адмінки
 * Використовує універсальний роутер з engine/classes/http/Router.php
 * 
 * @version 6.0.0
 */

declare(strict_types=1);

// Підключаємо централізовану ініціалізацію системи
require_once __DIR__ . '/engine/init.php';

// Підключаємо шаблонизатор
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

// Реєструємо базові маршрути адмінки
$router->add('', 'DashboardPage');
$router->add('dashboard', 'DashboardPage');
$router->add('login', 'LoginPage');
$router->add('logout', 'LogoutPage');
$router->add('settings', 'SettingsPage');
$router->add('profile', 'ProfilePage');
$router->add('plugins', 'PluginsPage');
$router->add('themes', 'ThemesPage');
$router->add('system', 'SystemPage');
$router->add('theme-editor', 'ThemeEditorPage');

// Реєструємо маршрут кастомізатора тільки якщо активна тема підтримує кастомізацію
require_once __DIR__ . '/engine/skins/includes/menu-items.php';
if (themeSupportsCustomization()) {
    $router->add('customizer', 'CustomizerPage');
}

// Реєструємо маршрут меню тільки якщо активна тема підтримує навігацію
if (themeSupportsNavigation()) {
    $router->add('menus', 'MenusPage');
}

// Хук для реєстрації маршрутів модулів та плагінів
doHook('admin_register_routes', $router);

// Реєструємо маршрути плагінів
$activePlugins = pluginManager()->getActivePlugins();
foreach ($activePlugins as $slug => $plugin) {
    $pluginDir = __DIR__ . '/plugins/' . $slug;
    
    // Перетворюємо slug в ім'я класу адмін-сторінки
    $parts = explode('-', $slug);
    $className = '';
    foreach ($parts as $part) {
        $className .= ucfirst($part);
    }
    $className .= 'AdminPage';
    
    $adminPageFile = $pluginDir . '/admin/' . $className . '.php';
    
    // Використовуємо File клас для перевірки існування файлу
    $file = new File($adminPageFile);
    if ($file->exists()) {
        require_once $adminPageFile;
        $router->add($slug, $className);
    }
}

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$router->dispatch();


