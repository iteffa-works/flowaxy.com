<?php
/**
 * Pages Plugin
 * Плагін для управління сторінками сайту
 * 
 * @package Plugins\Pages
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/engine/classes/base/BasePlugin.php';
require_once __DIR__ . '/Pages.php';

class PagesPlugin extends BasePlugin {
    
    private ?Pages $pages = null;
    
    /**
     * Ініціалізація плагіна
     */
    public function init(): void {
        $this->pages = new Pages();
        
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Активація плагіна
     */
    public function activate(): void {
        $this->install();
    }
    
    /**
     * Деактивація плагіна
     */
    public function deactivate(): void {
        // Очищаємо кеш при деактивації
        if (function_exists('cache_forget')) {
            cache_forget('pages_list');
            cache_forget('pages_categories');
        }
    }
    
    /**
     * Встановлення плагіна (створення таблиць)
     */
    public function install(): void {
        $db = DatabaseHelper::getConnection();
        if (!$db) {
            return;
        }
        
        try {
            // Створення таблиці категорій сторінок
            $db->exec("
                CREATE TABLE IF NOT EXISTS `page_categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `description` text COLLATE utf8mb4_unicode_ci,
                    `parent_id` int(11) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`),
                    KEY `idx_parent_id` (`parent_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Створення таблиці сторінок
            $db->exec("
                CREATE TABLE IF NOT EXISTS `pages` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `content` longtext COLLATE utf8mb4_unicode_ci,
                    `excerpt` text COLLATE utf8mb4_unicode_ci,
                    `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
                    `category_id` int(11) DEFAULT NULL,
                    `author_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`),
                    KEY `idx_status` (`status`),
                    KEY `idx_category_id` (`category_id`),
                    KEY `idx_author_id` (`author_id`),
                    KEY `idx_created_at` (`created_at`),
                    CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `page_categories` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `pages_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("PagesPlugin: Failed to install tables: " . $e->getMessage());
        }
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
            // Видаляємо таблиці (опціонально, можна залишити дані)
            // $db->exec("DROP TABLE IF EXISTS `pages`");
            // $db->exec("DROP TABLE IF EXISTS `page_categories`");
            
            // Видаляємо налаштування плагіна
            $stmt = $db->prepare("DELETE FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute(['pages']);
            
            // Очищаємо кеш
            if (function_exists('cache_forget')) {
                cache_forget('pages_list');
                cache_forget('pages_categories');
            }
        } catch (Exception $e) {
            error_log("PagesPlugin: Failed to uninstall: " . $e->getMessage());
        }
    }
    
    /**
     * Додавання пункту меню в адмінку
     */
    public function addAdminMenuItem(array $menu): array {
        // Додаємо пункт меню "Сторінки" після "Медіа-бібліотека"
        $newMenu = [];
        $inserted = false;
        
        foreach ($menu as $item) {
            $newMenu[] = $item;
            
            // Вставляємо після "Медіа-бібліотека"
            if (isset($item['page']) && $item['page'] === 'media' && !$inserted) {
                $newMenu[] = [
                    'href' => '#',
                    'icon' => 'fas fa-file-alt',
                    'text' => 'Сторінки',
                    'page' => 'pages',
                    'order' => 25,
                    'submenu' => [
                        [
                            'href' => UrlHelper::admin('pages'),
                            'text' => 'Всі сторінки',
                            'icon' => 'fas fa-list',
                            'page' => 'pages',
                            'order' => 1
                        ],
                        [
                            'href' => UrlHelper::admin('pages-categories'),
                            'text' => 'Категорії',
                            'icon' => 'fas fa-folder',
                            'page' => 'pages-categories',
                            'order' => 2
                        ],
                        [
                            'href' => UrlHelper::admin('pages-add'),
                            'text' => 'Додати сторінку',
                            'icon' => 'fas fa-plus',
                            'page' => 'pages-add',
                            'order' => 3
                        ]
                    ]
                ];
                $inserted = true;
            }
        }
        
        // Якщо не знайшли "Медіа-бібліотека", додаємо в кінець
        if (!$inserted) {
            $newMenu[] = [
                'href' => '#',
                'icon' => 'fas fa-file-alt',
                'text' => 'Сторінки',
                'page' => 'pages',
                'order' => 25,
                'submenu' => [
                    [
                        'href' => UrlHelper::admin('pages'),
                        'text' => 'Всі сторінки',
                        'icon' => 'fas fa-list',
                        'page' => 'pages',
                        'order' => 1
                    ],
                    [
                        'href' => UrlHelper::admin('pages-categories'),
                        'text' => 'Категорії',
                        'icon' => 'fas fa-folder',
                        'page' => 'pages-categories',
                        'order' => 2
                    ],
                    [
                        'href' => UrlHelper::admin('pages-add'),
                        'text' => 'Додати сторінку',
                        'icon' => 'fas fa-plus',
                        'page' => 'pages-add',
                        'order' => 3
                    ]
                ]
            ];
        }
        
        return $newMenu;
    }
    
    /**
     * Реєстрація маршрутів адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        require_once __DIR__ . '/admin/PagesAdminPage.php';
        require_once __DIR__ . '/admin/PagesAddPage.php';
        require_once __DIR__ . '/admin/PagesCategoriesPage.php';
        
        $router->add(['GET', 'POST'], 'pages', 'PagesAdminPage');
        $router->add(['GET', 'POST'], 'pages-add', 'PagesAddPage');
        $router->add(['GET', 'POST'], 'pages-edit', 'PagesAddPage');
        $router->add(['GET', 'POST'], 'pages-categories', 'PagesCategoriesPage');
    }
    
    /**
     * Отримання екземпляра Pages
     */
    public function getPages(): ?Pages {
        return $this->pages;
    }
}

