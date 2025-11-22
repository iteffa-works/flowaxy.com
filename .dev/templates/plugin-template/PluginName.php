<?php
/**
 * Plugin Name
 * 
 * Опис плагіна
 * 
 * @package PluginName
 * @version 1.0.0
 * @author Your Name
 */

declare(strict_types=1);

require_once __DIR__ . '/../../engine/classes/base/BasePlugin.php';

class PluginName extends BasePlugin {
    
    /**
     * Ініціалізація плагіна
     * Викликається при завантаженні плагіна
     */
    public function init(): void {
        // Реєстрація хуків
        // add_action('hook_name', [$this, 'method_name']);
        // add_filter('filter_name', [$this, 'method_name']);
    }
    
    /**
     * Активування плагіна
     * Викликається при активації плагіна
     */
    public function activate(): void {
        // Створення таблиць, налаштувань тощо
    }
    
    /**
     * Деактивування плагіна
     * Викликається при деактивації плагіна
     */
    public function deactivate(): void {
        // Очищення тимчасових даних
    }
    
    /**
     * Встановлення плагіна
     * Викликається при встановленні плагіна
     */
    public function install(): void {
        // Створення таблиць БД, налаштувань тощо
    }
    
    /**
     * Видалення плагіна
     * Викликається при видаленні плагіна
     */
    public function uninstall(): void {
        // Видалення таблиць БД, налаштувань тощо
    }
    
    /**
     * Отримання slug плагіна
     */
    public function getSlug(): string {
        return $this->config['slug'] ?? 'plugin-name';
    }
}

