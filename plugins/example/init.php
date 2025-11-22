<?php
/**
 * Example Plugin
 * 
 * Опис плагіна
 * 
 * @package ExamplePlugin
 * @version 1.0.0
 * @author Your Name
 */

declare(strict_types=1);

require_once __DIR__ . '/../../engine/classes/base/BasePlugin.php';

// Убеждаемся, что необходимые функции доступны
if (!function_exists('addHook')) {
    require_once __DIR__ . '/../../engine/includes/functions.php';
}
if (!class_exists('UrlHelper')) {
    require_once __DIR__ . '/../../engine/classes/helpers/UrlHelper.php';
}
if (!function_exists('themeSupportsCustomization')) {
    require_once __DIR__ . '/../../engine/skins/includes/menu-items.php';
}

class ExamplePlugin extends BasePlugin {
    
    /**
     * Ініціалізація плагіна
     * Викликається при завантаженні плагіна
     */
    public function init(): void {
        // Реєстрація пункту меню в адмін-панелі
        // Використовуємо addFilter для admin_menu, оскільки це фільтр, який повертає дані
        if (function_exists('addFilter')) {
            addFilter('admin_menu', [$this, 'registerAdminMenu'], 10);
        } else {
            addHook('admin_menu', [$this, 'registerAdminMenu']);
        }
        
        // Реєстрація маршруту для адмін-сторінки
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Реєстрація пункту меню в адмін-панелі
     */
    public function registerAdminMenu(array $menu): array {
        // Структура меню з категориями та підкатегоріями
        $adminMenuItems = [
            // Категорія: CMS (Основні функції системи)
            [
                'text' => 'Платформа',
                'icon' => 'fas fa-cube',
                'href' => '#',
                'page' => 'cms',
                'order' => 1,
                'permission' => null,
                'submenu' => [
                    [
                        'text' => 'Панель управління',
                        'icon' => 'fas fa-tachometer-alt',
                        'href' => UrlHelper::admin('dashboard'),
                        'page' => 'dashboard',
                        'order' => 1,
                        'permission' => null
                    ],
                    [
                        'text' => 'Плагіни',
                        'icon' => 'fas fa-plug',
                        'href' => UrlHelper::admin('plugins'),
                        'page' => 'plugins',
                        'order' => 2,
                        'permission' => 'admin.plugins.view'
                    ],
                    [
                        'text' => 'Теми',
                        'icon' => 'fas fa-paint-brush',
                        'href' => UrlHelper::admin('themes'),
                        'page' => 'themes',
                        'order' => 3,
                        'permission' => 'admin.themes.view'
                    ],
                    [
                        'text' => 'Редактор теми',
                        'icon' => 'fas fa-code',
                        'href' => UrlHelper::admin('theme-editor?theme=example'),
                        'page' => 'theme-editor',
                        'order' => 4,
                        'permission' => 'admin.themes.edit'
                    ]
                ]
            ],
            
            // Категорія: Система (Користувачі та ролі)
            [
                'text' => 'Система',
                'icon' => 'fas fa-users-cog',
                'href' => '#',
                'page' => 'system',
                'order' => 2,
                'permission' => null,
                'submenu' => [
                    [
                        'text' => 'Користувачі',
                        'icon' => 'fas fa-users',
                        'href' => UrlHelper::admin('users'),
                        'page' => 'users',
                        'order' => 1,
                        'permission' => 'admin.users.view'
                    ],
                    [
                        'text' => 'Ролі та права',
                        'icon' => 'fas fa-user-shield',
                        'href' => UrlHelper::admin('roles'),
                        'page' => 'roles',
                        'order' => 2,
                        'permission' => 'admin.roles.view'
                    ],
                    [
                        'text' => 'Мій профіль',
                        'icon' => 'fas fa-user',
                        'href' => UrlHelper::admin('profile'),
                        'page' => 'profile',
                        'order' => 3,
                        'permission' => null
                    ]
                ]
            ],
            
            // Категорія: Налаштування
            [
                'text' => 'Налаштування',
                'icon' => 'fas fa-cog',
                'href' => '#',
                'page' => 'settings',
                'order' => 3,
                'permission' => null,
                'submenu' => [
                    [
                        'text' => 'Налаштування',
                        'icon' => 'fas fa-cog',
                        'href' => UrlHelper::admin('settings'),
                        'page' => 'settings',
                        'order' => 1,
                        'permission' => 'admin.settings.view'
                    ],
                    [
                        'text' => 'Налаштування сайту',
                        'icon' => 'fas fa-globe',
                        'href' => UrlHelper::admin('site-settings'),
                        'page' => 'site-settings',
                        'order' => 2,
                        'permission' => 'admin.settings.view'
                    ]
                ]
            ],
            
            // Категорія: Інтеграції
            [
                'text' => 'Інтеграції',
                'icon' => 'fas fa-link',
                'href' => '#',
                'page' => 'integrations',
                'order' => 4,
                'permission' => null,
                'submenu' => [
                    [
                        'text' => 'API ключі',
                        'icon' => 'fas fa-key',
                        'href' => UrlHelper::admin('api-keys'),
                        'page' => 'api-keys',
                        'order' => 1,
                        'permission' => 'admin.api.keys.view'
                    ],
                    [
                        'text' => 'Webhooks',
                        'icon' => 'fas fa-link',
                        'href' => UrlHelper::admin('webhooks'),
                        'page' => 'webhooks',
                        'order' => 2,
                        'permission' => 'admin.webhooks.view'
                    ]
                ]
            ],
            
            // Категорія: Інструменти
            [
                'text' => 'Інструменти',
                'icon' => 'fas fa-tools',
                'href' => '#',
                'page' => 'tools',
                'order' => 5,
                'permission' => null,
                'submenu' => [
                    [
                        'text' => 'Логи',
                        'icon' => 'fas fa-file-alt',
                        'href' => UrlHelper::admin('logs-view'),
                        'page' => 'logs-view',
                        'order' => 1,
                        'permission' => 'admin.logs.view'
                    ],
                    [
                        'text' => 'Кеш',
                        'icon' => 'fas fa-database',
                        'href' => UrlHelper::admin('cache-view'),
                        'page' => 'cache-view',
                        'order' => 2,
                        'permission' => 'admin.cache.view'
                    ]
                ]
            ],
            
            // Пункт меню плагіна (якщо потрібно)
            [
                'text' => 'Example Plugin',
                'icon' => 'fas fa-puzzle-piece',
                'href' => UrlHelper::admin('example'),
                'page' => 'example',
                'order' => 100,
                'permission' => null
            ]
        ];
        
        // Додаємо кастомізатор якщо підтримується
        if (function_exists('themeSupportsCustomization') && themeSupportsCustomization()) {
            // Додаємо в категорію CMS
            foreach ($adminMenuItems as $key => $item) {
                if ($item['page'] === 'cms' && isset($item['submenu'])) {
                    $adminMenuItems[$key]['submenu'][] = [
                        'text' => 'Кастомізатор',
                        'icon' => 'fas fa-palette',
                        'href' => UrlHelper::admin('customizer'),
                        'page' => 'customizer',
                        'order' => 5,
                        'permission' => 'admin.themes.customize'
                    ];
                    break;
                }
            }
        }
        
        // Об'єднуємо з існуючим меню
        return array_merge($menu, $adminMenuItems);
    }
    
    /**
     * Реєстрація маршруту для адмін-сторінки
     */
    public function registerAdminRoute($router): void {
        if (method_exists($router, 'add')) {
            // Реєструємо маршрут для адмін-сторінки плагіна
            // Використовуємо правильне ім'я класу з файлу ExampleAdminPage.php
            $router->add(['GET', 'POST'], 'example', 'ExampleAdminPage');
        }
    }
    
    /**
     * Активування плагіна
     * Викликається при активації плагіна
     */
    public function activate(): void {
        // Очищаємо кеш меню для оновлення пунктів меню
        // PluginManager вже очищає active_plugins_hash, але для надійності очищаємо тут теж
        if (function_exists('cache_forget')) {
            cache_forget('active_plugins_hash');
        }
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

