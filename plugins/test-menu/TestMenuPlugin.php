<?php
/**
 * Тестовий плагін з боковим меню
 * 
 * @package Plugins
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/engine/classes/base/BasePlugin.php';

class TestMenuPlugin extends BasePlugin {
    
    /**
     * Ініціалізація плагіна
     */
    public function init() {
        // Реєстрація хуків
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Активація плагіна
     */
    public function activate() {
        // Нічого не потрібно
    }
    
    /**
     * Деактивація плагіна
     */
    public function deactivate() {
        // Нічого не потрібно
    }
    
    /**
     * Встановлення плагіна
     */
    public function install() {
        // Нічого не потрібно
    }
    
    /**
     * Видалення плагіна
     */
    public function uninstall() {
        // Нічого не потрібно
    }
    
    /**
     * Додавання пункту меню в адмінку
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            'href' => adminUrl('test-menu'),
            'icon' => 'fas fa-flask',
            'text' => 'Тестове меню',
            'page' => 'test-menu',
            'order' => 20
        ];
        return $menu;
    }
    
    /**
     * Реєстрація маршруту адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        require_once __DIR__ . '/admin/TestMenuAdminPage.php';
        $router->add(['GET', 'POST'], 'test-menu', 'TestMenuAdminPage');
    }
}

