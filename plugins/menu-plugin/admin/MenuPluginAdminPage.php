<?php
/**
 * Административная страница плагина Menu Plugin
 * Автоматически регистрируется системой для маршрута /admin/menu-plugin
 * Основной функционал находится в MenusPage
 * 
 * Также регистрирует маршрут /admin/menus через хук admin_register_routes
 */

require_once __DIR__ . '/MenusPage.php';

// Используем MenusPage как основной класс
class MenuPluginAdminPage extends MenusPage {
    
    public function __construct() {
        parent::__construct();
    }
}


