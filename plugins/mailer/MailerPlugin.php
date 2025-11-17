<?php
/**
 * Mailer Plugin
 * Professional email management plugin
 * 
 * @package Plugins\Mailer
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/engine/classes/base/BasePlugin.php';
require_once __DIR__ . '/Mailer.php';

class MailerPlugin extends BasePlugin {
    
    private ?Mailer $mailer = null;
    
    /**
     * Ініціалізація плагіна
     */
    public function init(): void {
        $this->mailer = Mailer::getInstance();
        
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Активація плагіна
     */
    public function activate(): void {
        // Очищаємо кеш при активації
        if (function_exists('cache_forget')) {
            cache_forget('mailer_settings');
        }
    }
    
    /**
     * Деактивація плагіна
     */
    public function deactivate(): void {
        // Очищаємо кеш при деактивації
        if (function_exists('cache_forget')) {
            cache_forget('mailer_settings');
        }
    }
    
    /**
     * Встановлення плагіна
     */
    public function install(): void {
        // Нічого не потрібно
    }
    
    /**
     * Видалення плагіна
     */
    public function uninstall(): void {
        $db = DatabaseHelper::getConnection();
        if (!$db) {
            return;
        }
        
        try {
            // Видаляємо всі налаштування плагіна з plugin_settings
            $stmt = $db->prepare("DELETE FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute(['mailer']);
            
            // Очищаємо кеш
            if (function_exists('cache_forget')) {
                cache_forget('mailer_settings');
            }
        } catch (Exception $e) {
            error_log("MailerPlugin: Failed to uninstall settings: " . $e->getMessage());
        }
    }
    
    /**
     * Додавання пункту меню в адмінку
     */
    public function addAdminMenuItem(array $menu): array {
        foreach ($menu as $key => $item) {
            // Добавляем в подменю "Налаштування плагінів"
            if (isset($item['page']) && $item['page'] === 'plugin-settings' && isset($item['submenu'])) {
                $menu[$key]['submenu'][] = [
                    'href' => UrlHelper::admin('mailer-settings'),
                    'text' => 'Налаштування пошти',
                    'icon' => 'fas fa-envelope',
                    'page' => 'mailer-settings',
                    'order' => 1
                ];
                break;
            }
        }
        return $menu;
    }
    
    /**
     * Реєстрація маршруту адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        require_once __DIR__ . '/admin/SettingsPage.php';
        $router->add(['GET', 'POST'], 'mailer-settings', 'MailerSettingsPage');
    }
    
    /**
     * Отримання екземпляра Mailer
     */
    public function getMailer(): ?Mailer {
        return $this->mailer;
    }
}

