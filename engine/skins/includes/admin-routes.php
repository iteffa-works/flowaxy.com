<?php
/**
 * Реєстрація маршрутів адмінки
 * Використовується в index.php для автоматичної реєстрації маршрутів адмінки
 * 
 * @param Router $router Роутер для адмінки
 * @return void
 */

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
require_once __DIR__ . '/menu-items.php';
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
            $router->add($slug, $className);
        }
    } elseif (file_exists($adminPageFile)) {
        require_once $adminPageFile;
        $router->add($slug, $className);
    }
}

