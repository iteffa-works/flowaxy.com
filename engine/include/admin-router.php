<?php
/**
 * Роутер админки
 * Обрабатывает все запросы к /admin/*
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once __DIR__ . '/../skins/includes/SimpleTemplate.php';
require_once __DIR__ . '/../skins/includes/Router.php';

// Модули загружаются лениво - только когда они нужны для конкретной страницы

// Определяем текущий путь ДО вызова хуков, чтобы загрузить только нужный модуль
$currentPath = Router::getCurrentPage();
if (empty($currentPath)) {
    $currentPath = 'dashboard';
}

// Маппинг путей к модулям
$pathToModule = [
    'logs' => 'Logger',
    'logs-settings' => 'Logger',
    'media' => 'Media',
    'menus' => 'Menu',
];

// Загружаем модуль для текущей страницы, если он нужен
if (isset($pathToModule[$currentPath]) && class_exists('ModuleLoader')) {
    ModuleLoader::loadModule($pathToModule[$currentPath]);
}

// Подключаем классы страниц
require_once __DIR__ . '/../skins/pages/LoginPage.php';
require_once __DIR__ . '/../skins/pages/LogoutPage.php';
require_once __DIR__ . '/../skins/pages/DashboardPage.php';
require_once __DIR__ . '/../skins/pages/SettingsPage.php';
require_once __DIR__ . '/../skins/pages/ProfilePage.php';
require_once __DIR__ . '/../skins/pages/PluginsPage.php';
require_once __DIR__ . '/../skins/pages/ThemesPage.php';
require_once __DIR__ . '/../skins/pages/CustomizerPage.php';
require_once __DIR__ . '/../skins/pages/MenusPage.php';
require_once __DIR__ . '/../skins/pages/SystemPage.php';
require_once __DIR__ . '/../skins/pages/ThemeEditorPage.php';

// Создаем роутер
$router = new Router();

// Регистрируем маршруты
$router->add('', 'DashboardPage'); // Главная страница

$router->add('dashboard', 'DashboardPage');
$router->add('settings', 'SettingsPage');
$router->add('profile', 'ProfilePage');
$router->add('plugins', 'PluginsPage');
$router->add('themes', 'ThemesPage');

// Регистрируем маршрут кастомайзера только если активная тема поддерживает кастоматизацию
require_once __DIR__ . '/../skins/includes/menu-items.php';
if (themeSupportsCustomization()) {
    $router->add('customizer', 'CustomizerPage');
}

// Регистрируем маршрут меню только если активная тема поддерживает навигацию
if (themeSupportsNavigation()) {
    $router->add('menus', 'MenusPage');
}

$router->add('system', 'SystemPage');
$router->add('theme-editor', 'ThemeEditorPage');

$router->add('login', 'LoginPage');
$router->add('logout', 'LogoutPage');

// Регистрация маршрутов модулей
// Хуки загрузят только те модули, которые еще не загружены
doHook('admin_register_routes', $router);

// Регистрируем маршруты плагинов
$activePlugins = pluginManager()->getActivePlugins();
foreach ($activePlugins as $slug => $plugin) {
    $pluginDir = dirname(__DIR__, 2) . '/plugins/' . $slug;
    
    // Преобразуем slug в имя класса админ-страницы
    $parts = explode('-', $slug);
    $className = '';
    foreach ($parts as $part) {
        $className .= ucfirst($part);
    }
    $className .= 'AdminPage';
    
    $adminPageFile = $pluginDir . '/admin/' . $className . '.php';
    
    if (file_exists($adminPageFile)) {
        require_once $adminPageFile;
        $router->add($slug, $className);
    }
}

// Обрабатываем запрос
$router->dispatch();

