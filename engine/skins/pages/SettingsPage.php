<?php
/**
 * Сторінка налаштувань (головна сторінка зі списком посилань)
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class SettingsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування - Flowaxy CMS';
        $this->templateName = 'settings';
        
        $this->setPageHeader(
            'Налаштування',
            'Управління системою та конфігурацією',
            'fas fa-cog'
        );
    }
    
    public function handle() {
        // Отримуємо список налаштувань через хук
        $settingsCategories = applyFilter('settings_categories', $this->getDefaultSettingsCategories());
        
        // Рендеримо сторінку
        $this->render([
            'settingsCategories' => $settingsCategories
        ]);
    }
    
    /**
     * Отримання категорій налаштувань за замовчуванням
     */
    private function getDefaultSettingsCategories(): array {
        $categories = [];
        
        // Основні налаштування
        $categories['general'] = [
            'title' => 'Основні налаштування',
            'icon' => 'fas fa-cog',
            'items' => [
                [
                    'title' => 'Налаштування сайту',
                    'description' => 'Email, часовий пояс, кеш, логування',
                    'url' => UrlHelper::admin('site-settings'),
                    'icon' => 'fas fa-globe',
                    'permission' => 'admin.settings'
                ]
            ]
        ];
        
        // Користувачі та права
        $categories['users'] = [
            'title' => 'Користувачі та права',
            'icon' => 'fas fa-users',
            'items' => []
        ];
        
        // Ролі та права
        if (function_exists('current_user_can')) {
            $session = sessionManager();
            $userId = $session->get('admin_user_id');
            $hasRolesAccess = ($userId == 1) || current_user_can('admin.roles');
            
            if ($hasRolesAccess) {
                $categories['users']['items'][] = [
                    'title' => 'Ролі та права',
                    'description' => 'Управління ролями та правами доступу',
                    'url' => UrlHelper::admin('roles'),
                    'icon' => 'fas fa-user-shield',
                    'permission' => 'admin.roles'
                ];
            }
        }
        
        // Користувачі (якщо є сторінка)
        if (class_exists('UsersPage') || file_exists(__DIR__ . '/UsersPage.php')) {
            $session = sessionManager();
            $userId = $session->get('admin_user_id');
            $hasUsersAccess = ($userId == 1) || (function_exists('current_user_can') && current_user_can('admin.users'));
            
            if ($hasUsersAccess) {
                $categories['users']['items'][] = [
                    'title' => 'Користувачі',
                    'description' => 'Управління користувачами системи',
                    'url' => UrlHelper::admin('users'),
                    'icon' => 'fas fa-users',
                    'permission' => 'admin.users'
                ];
            }
        }
        
        // Профіль
        $categories['users']['items'][] = [
            'title' => 'Мій профіль',
            'description' => 'Особисті налаштування та дані',
            'url' => UrlHelper::admin('profile'),
            'icon' => 'fas fa-user',
            'permission' => null // Доступен всем авторизованным
        ];
        
        // Расширения
        $categories['extensions'] = [
            'title' => 'Розширення',
            'icon' => 'fas fa-puzzle-piece',
            'items' => [
                [
                    'title' => 'Плагіни',
                    'description' => 'Управління плагінами',
                    'url' => UrlHelper::admin('plugins'),
                    'icon' => 'fas fa-plug',
                    'permission' => 'admin.plugins'
                ],
                [
                    'title' => 'Теми',
                    'description' => 'Управління темами',
                    'url' => UrlHelper::admin('themes'),
                    'icon' => 'fas fa-paint-brush',
                    'permission' => 'admin.themes'
                ]
            ]
        ];
        
        // API и интеграции
        $categories['api'] = [
            'title' => 'API та інтеграції',
            'icon' => 'fas fa-code',
            'items' => [
                [
                    'title' => 'API ключі',
                    'description' => 'Управління API ключами',
                    'url' => UrlHelper::admin('api-keys'),
                    'icon' => 'fas fa-key',
                    'permission' => 'admin.settings'
                ],
                [
                    'title' => 'Webhooks',
                    'description' => 'Управління webhooks',
                    'url' => UrlHelper::admin('webhooks'),
                    'icon' => 'fas fa-code-branch',
                    'permission' => 'admin.settings'
                ]
            ]
        ];
        
        // Система
        $categories['system'] = [
            'title' => 'Система',
            'icon' => 'fas fa-server',
            'items' => [
                [
                    'title' => 'Логи',
                    'description' => 'Перегляд системних логів',
                    'url' => UrlHelper::admin('logs-view'),
                    'icon' => 'fas fa-file-alt',
                    'permission' => 'admin.logs.view'
                ],
                [
                    'title' => 'Кеш',
                    'description' => 'Управління кешем',
                    'url' => UrlHelper::admin('cache-view'),
                    'icon' => 'fas fa-database',
                    'permission' => 'admin.settings'
                ],
                [
                    'title' => 'Сховища',
                    'description' => 'Управління сесіями, куками та клієнтським сховищем',
                    'url' => UrlHelper::admin('storage-management'),
                    'icon' => 'fas fa-boxes',
                    'permission' => 'admin.settings'
                ]
            ]
        ];
        
        // Фильтруем элементы по правам доступа
        foreach ($categories as $key => $category) {
            $categories[$key]['items'] = array_filter($category['items'], function($item) {
                if (isset($item['permission']) && $item['permission'] !== null) {
                    if (function_exists('current_user_can')) {
                        $session = sessionManager();
            $userId = $session->get('admin_user_id');
                        // Для первого пользователя всегда разрешаем доступ
                        if ($userId == 1) {
                            return true;
                        }
                        return current_user_can($item['permission']);
                    }
                    return false;
                }
                return true; // Если permission не указан, доступен всем
            });
        }
        
        // Удаляем пустые категории
        $categories = array_filter($categories, function($category) {
            return !empty($category['items']);
        });
        
        return $categories;
    }
}

