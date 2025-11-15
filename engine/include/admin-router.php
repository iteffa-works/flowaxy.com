<?php
/**
 * Роутер админки
 * Обрабатывает все запросы к /admin/*
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once __DIR__ . '/../skins/includes/SimpleTemplate.php';
require_once __DIR__ . '/../skins/includes/Router.php';

// Подключаем классы страниц
require_once __DIR__ . '/../skins/pages/LoginPage.php';
require_once __DIR__ . '/../skins/pages/LogoutPage.php';
require_once __DIR__ . '/../skins/pages/DashboardPage.php';
require_once __DIR__ . '/../skins/pages/SettingsPage.php';
require_once __DIR__ . '/../skins/pages/ProfilePage.php';
require_once __DIR__ . '/../skins/pages/PluginsPage.php';
require_once __DIR__ . '/../skins/pages/MediaPage.php';
require_once __DIR__ . '/../skins/pages/ThemesPage.php';
require_once __DIR__ . '/../skins/pages/CustomizerPage.php';
require_once __DIR__ . '/../skins/pages/MenusPage.php';
require_once __DIR__ . '/../skins/pages/SystemPage.php';

// Создаем роутер
$router = new Router();

// Регистрируем маршруты
$router->add('login', 'LoginPage');
$router->add('logout', 'LogoutPage');
$router->add('dashboard', 'DashboardPage');
$router->add('', 'DashboardPage'); // Главная страница
$router->add('settings', 'SettingsPage');
$router->add('profile', 'ProfilePage');
$router->add('plugins', 'PluginsPage');
$router->add('media', 'MediaPage');
$router->add('themes', 'ThemesPage');
$router->add('customizer', 'CustomizerPage');
$router->add('menus', 'MenusPage');
$router->add('system', 'SystemPage');

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
    
    // Додаткові адмін-сторінки для плагінів
    // UTM Tracker - аналітика
    if ($slug === 'pb-utm-tracker') {
        $analyticsPageFile = $pluginDir . '/admin/PbUtmTrackerAnalyticsAdminPage.php';
        if (file_exists($analyticsPageFile)) {
            require_once $analyticsPageFile;
            $router->add('pb-utm-tracker-analytics', 'PbUtmTrackerAnalyticsAdminPage');
        }
    }
    
    // Каталог - категорії та секція
    if ($slug === 'pb-catalog') {
        $categoriesPageFile = $pluginDir . '/admin/PbCatalogCategoriesAdminPage.php';
        if (file_exists($categoriesPageFile)) {
            require_once $categoriesPageFile;
            $router->add('pb-catalog-categories', 'PbCatalogCategoriesAdminPage');
        }
        $sectionPageFile = $pluginDir . '/admin/PbCatalogSectionAdminPage.php';
        if (file_exists($sectionPageFile)) {
            require_once $sectionPageFile;
            $router->add('pb-catalog-section', 'PbCatalogSectionAdminPage');
        }
    }
    
    // Форми - відправки
    if ($slug === 'pb-form-builder') {
        $submissionsPageFile = $pluginDir . '/admin/PbFormSubmissionsAdminPage.php';
        if (file_exists($submissionsPageFile)) {
            require_once $submissionsPageFile;
            $router->add('pb-form-submissions', 'PbFormSubmissionsAdminPage');
        }
    }
}

// Обрабатываем запрос
$router->dispatch();

