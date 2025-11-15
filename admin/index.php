<?php
/**
 * Точка входа админки с роутингом
 */

require_once '../config/config.php';
require_once '../engine/skins/includes/SimpleTemplate.php';
require_once '../engine/skins/includes/Router.php';

// Подключаем классы страниц
require_once '../engine/skins/pages/LoginPage.php';
require_once '../engine/skins/pages/LogoutPage.php';
require_once '../engine/skins/pages/DashboardPage.php';
require_once '../engine/skins/pages/SettingsPage.php';
require_once '../engine/skins/pages/ProfilePage.php';
require_once '../engine/skins/pages/PluginsPage.php';
require_once '../engine/skins/pages/MediaPage.php';
require_once '../engine/skins/pages/ThemesPage.php';
require_once '../engine/skins/pages/CustomizerPage.php';
require_once '../engine/skins/pages/MenusPage.php';
require_once '../engine/skins/pages/SystemPage.php';

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
    $pluginDir = __DIR__ . '/../plugins/' . $slug;
    
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
