<?php
/**
 * Плагин Example Test
 * Для тестирования всех компонентов CMS
 * 
 * @package Plugins
 * @version 1.0.0
 */

require_once dirname(__DIR__, 2) . '/engine/classes/BasePlugin.php';

class ExampleTestPlugin extends BasePlugin {
    
    /**
     * Инициализация плагина
     */
    public function init() {
        // Регистрация хуков
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
    }
    
    /**
     * Активация плагина
     */
    public function activate() {
        // Логика активации
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate() {
        // Логика деактивации
    }
    
    /**
     * Установка плагина
     */
    public function install() {
        // Логика установки
    }
    
    /**
     * Удаление плагина
     */
    public function uninstall() {
        // Логика удаления
    }
    
    /**
     * Добавление пункта меню в админку
     * 
     * @param array $menu Текущее меню
     * @return array Обновленное меню
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            'href' => adminUrl('example-test'),
            'icon' => 'fas fa-vial',
            'text' => 'Example Test',
            'page' => 'example-test',
            'order' => 95
        ];
        return $menu;
    }
    
    /**
     * Получение информации о плагине
     */
    public function getSlug(): string {
        return 'example-test';
    }
    
    public function getName(): string {
        return $this->config['name'] ?? 'Example Test';
    }
    
    public function getVersion(): string {
        return $this->config['version'] ?? '1.0.0';
    }
    
    public function getDescription(): string {
        return $this->config['description'] ?? '';
    }
    
    public function getAuthor(): string {
        return $this->config['author'] ?? '';
    }
}

