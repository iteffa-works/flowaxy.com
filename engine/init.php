<?php
/**
 * Flowaxy CMS - Ініціалізація системи
 * 
 * @package Engine
 * @version 7.0.0
 */

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstaller = str_starts_with($requestUri, '/install');
$databaseIniFile = __DIR__ . '/data/database.ini';
$defaultTimezone = 'Europe/Kyiv';

// Встановлення часового поясу
$timezone = $defaultTimezone;
if (!$isInstaller && file_exists($databaseIniFile)) {
    ModuleLoader::init();
    
    if (class_exists('SettingsManager')) {
        try {
            $tz = settingsManager()->get('timezone', $defaultTimezone);
            
            // Автоматичне оновлення старого часового поясу на новий
            if ($tz === 'Europe/Kiev') {
                $tz = 'Europe/Kyiv';
                // Оновлюємо в налаштуваннях
                try {
                    settingsManager()->set('timezone', 'Europe/Kyiv');
                } catch (Exception $e) {
                    if (class_exists('Logger')) {
                        Logger::getInstance()->logWarning('Не вдалося оновити налаштування часового поясу', ['error' => $e->getMessage()]);
                    }
                }
            }
            
            if (!empty($tz) && in_array($tz, timezone_identifiers_list(), true)) {
                $timezone = $tz;
            } else {
                // Якщо часовий пояс невалідний, використовуємо значення за замовчуванням
                $timezone = $defaultTimezone;
            }
        } catch (Exception $e) {
            // Використовуємо значення за замовчуванням
            if (class_exists('Logger')) {
                Logger::getInstance()->logWarning('Помилка завантаження часового поясу', ['error' => $e->getMessage()]);
            }
        }
    }
}
date_default_timezone_set($timezone);

// Налаштування обробників помилок та винятків
if (!$isInstaller && file_exists($databaseIniFile) && class_exists('Logger')) {
    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
        Logger::getInstance()->log(match($errno) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR => Logger::LEVEL_ERROR,
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => Logger::LEVEL_WARNING,
            default => Logger::LEVEL_INFO
        }, $errstr, ['file' => $errfile, 'line' => $errline, 'errno' => $errno]);
        return false;
    });
    
    set_exception_handler(function(\Throwable $e): void {
        Logger::getInstance()->logException($e);
        if (defined('DEBUG_MODE') && constant('DEBUG_MODE')) {
            echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        } else {
            // Встановлюємо код відповіді тільки якщо заголовки ще не відправлені
            if (!headers_sent() && class_exists('Response')) {
                Response::setHeader('Status', '500 Internal Server Error');
            } elseif (!headers_sent()) {
                http_response_code(500);
            }
            echo '<h1>Внутрішня помилка сервера</h1>';
        }
    });
    
    register_shutdown_function(function(): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            Logger::getInstance()->logCritical('Критична помилка: ' . $error['message'], ['file' => $error['file'], 'line' => $error['line']]);
        }
    });
}

// Визначаємо secure на основі протоколу з налаштувань
// Пріоритет: налаштування з бази даних > реальне з'єднання
$isSecure = false;

// Перевіряємо налаштування протоколу з бази даних (якщо доступні)
$protocolFromSettings = null;
if (class_exists('SettingsManager') && file_exists(__DIR__ . '/data/database.ini')) {
    try {
        $settingsManager = settingsManager();
        $protocolSetting = $settingsManager->get('site_protocol', 'auto');
        if ($protocolSetting === 'https') {
            $protocolFromSettings = 'https://';
        } elseif ($protocolSetting === 'http') {
            $protocolFromSettings = 'http://';
        }
    } catch (Exception $e) {
        // Ігноруємо помилки при завантаженні налаштувань на етапі ініціалізації
    }
}

// Якщо в налаштуваннях явно вказано протокол, використовуємо його
if ($protocolFromSettings === 'https://') {
    $isSecure = true;
} elseif ($protocolFromSettings === 'http://') {
    $isSecure = false;
} else {
    // Якщо налаштування 'auto' або недоступні, визначаємо автоматично
    $realHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
    
    if ($realHttps) {
        $isSecure = true;
    } elseif (class_exists('UrlHelper')) {
        $isSecure = UrlHelper::isHttps();
    } elseif (function_exists('detectProtocol')) {
        $protocol = detectProtocol();
        $isSecure = ($protocol === 'https://');
    }
}

// Session::start() тепер сам перевіряє налаштування з бази даних
// Передаємо початкове значення, але воно може бути перевизначено всередині Session::start()
// Параметри сесії будуть завантажені з налаштувань в Session::start()
Session::start([
    'domain' => '',
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax' // Lax працює краще в Edge, ніж None
]);

if ($isInstaller) {
    require_once __DIR__ . '/includes/installer-handler.php';
    exit;
}

// Встановлення security headers для захисту від атак
if (!headers_sent() && class_exists('Response')) {
    Response::setSecurityHeaders();
}

initializeSystem();

// Оновлення протоколу на основі налаштування з бази даних (якщо доступна)
// detectProtocol() вже перевіряє налаштування з бази, але тут ми оновлюємо глобальну змінну
// для забезпечення консистентності
if (class_exists('SettingsManager') && file_exists(__DIR__ . '/data/database.ini')) {
    try {
        $settingsManager = settingsManager();
        $protocolSetting = $settingsManager->get('site_protocol', 'auto');
        
        if ($protocolSetting !== 'auto') {
            $newProtocol = $protocolSetting === 'https' ? 'https://' : 'http://';
            // Оновлюємо глобальну змінну для використання в detectProtocol()
            $GLOBALS['_SITE_PROTOCOL'] = $newProtocol;
        }
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::getInstance()->logWarning('Не вдалося оновити протокол з налаштувань', ['error' => $e->getMessage()]);
        }
    }
}

// Виконання міграцій
if (file_exists(__DIR__ . '/data/database.ini')) {
    try {
        $db = DatabaseHelper::getConnection();
        if ($db) {
            // Міграція для додавання session_token
            if (file_exists(__DIR__ . '/includes/migrations/add_session_token_to_users.php')) {
                require_once __DIR__ . '/includes/migrations/add_session_token_to_users.php';
                if (function_exists('migration_add_session_token_to_users')) {
                    migration_add_session_token_to_users($db);
                }
            }
            
            // Міграція для додавання полів активності сесії
            if (file_exists(__DIR__ . '/includes/migrations/add_session_activity_to_users.php')) {
                require_once __DIR__ . '/includes/migrations/add_session_activity_to_users.php';
                if (function_exists('migration_add_session_activity_to_users')) {
                    migration_add_session_activity_to_users($db);
                }
            }
        }
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::getInstance()->logError('Не вдалося виконати міграції', ['error' => $e->getMessage()]);
        }
    }
}

// Ініціалізація системи ролей (перевірка та створення таблиць при необхідності)
if (file_exists(__DIR__ . '/includes/roles-init.php')) {
    require_once __DIR__ . '/includes/roles-init.php';
    if (function_exists('initializeRolesSystem')) {
        initializeRolesSystem();
    }
}

// ТИМЧАСОВО: Реєстрація всіх сторінок у бічну панель на час розробки
if (function_exists('addHook')) {
    addHook('admin_menu', function($menu) {
        // Для тимчасової розробки - додаємо всі сторінки з перевіркою прав доступу
        $devMenuItems = [
            [
                'text' => 'Панель управління',
                'icon' => 'fas fa-tachometer-alt',
                'href' => UrlHelper::admin('dashboard'),
                'page' => 'dashboard',
                'order' => 1,
                'permission' => null // Доступно всім авторизованим
            ],
            [
                'text' => 'Налаштування',
                'icon' => 'fas fa-cog',
                'href' => UrlHelper::admin('settings'),
                'page' => 'settings',
                'order' => 10,
                'permission' => 'admin.settings.view'
            ],
            [
                'text' => 'Налаштування сайту',
                'icon' => 'fas fa-globe',
                'href' => UrlHelper::admin('site-settings'),
                'page' => 'site-settings',
                'order' => 11,
                'permission' => 'admin.settings.view'
            ],
            [
                'text' => 'Користувачі',
                'icon' => 'fas fa-users',
                'href' => UrlHelper::admin('users'),
                'page' => 'users',
                'order' => 20,
                'permission' => 'admin.users.view'
            ],
            [
                'text' => 'Ролі та права',
                'icon' => 'fas fa-user-shield',
                'href' => UrlHelper::admin('roles'),
                'page' => 'roles',
                'order' => 21,
                'permission' => 'admin.roles.view'
            ],
            [
                'text' => 'Мій профіль',
                'icon' => 'fas fa-user',
                'href' => UrlHelper::admin('profile'),
                'page' => 'profile',
                'order' => 22,
                'permission' => null // Доступно всім авторизованим
            ],
            [
                'text' => 'Плагіни',
                'icon' => 'fas fa-plug',
                'href' => UrlHelper::admin('plugins'),
                'page' => 'plugins',
                'order' => 30,
                'permission' => 'admin.plugins.view'
            ],
            [
                'text' => 'Теми',
                'icon' => 'fas fa-paint-brush',
                'href' => UrlHelper::admin('themes'),
                'page' => 'themes',
                'order' => 31,
                'permission' => 'admin.themes.view'
            ],
            [
                'text' => 'Редактор теми',
                'icon' => 'fas fa-code',
                'href' => UrlHelper::admin('theme-editor'),
                'page' => 'theme-editor',
                'order' => 32,
                'permission' => 'admin.themes.edit'
            ],
            [
                'text' => 'API ключі',
                'icon' => 'fas fa-key',
                'href' => UrlHelper::admin('api-keys'),
                'page' => 'api-keys',
                'order' => 40,
                'permission' => 'admin.api.keys.view'
            ],
            [
                'text' => 'Webhooks',
                'icon' => 'fas fa-link',
                'href' => UrlHelper::admin('webhooks'),
                'page' => 'webhooks',
                'order' => 41,
                'permission' => 'admin.webhooks.view'
            ],
            [
                'text' => 'Логи',
                'icon' => 'fas fa-file-alt',
                'href' => UrlHelper::admin('logs-view'),
                'page' => 'logs-view',
                'order' => 50,
                'permission' => 'admin.logs.view'
            ],
            [
                'text' => 'Кеш',
                'icon' => 'fas fa-database',
                'href' => UrlHelper::admin('cache-view'),
                'page' => 'cache-view',
                'order' => 51,
                'permission' => 'admin.cache.view'
            ]
        ];
        
        // Додаємо кастомізатор якщо підтримується
        if (function_exists('themeSupportsCustomization') && themeSupportsCustomization()) {
            $devMenuItems[] = [
                'text' => 'Кастомізатор',
                'icon' => 'fas fa-palette',
                'href' => UrlHelper::admin('customizer'),
                'page' => 'customizer',
                'order' => 33,
                'permission' => 'admin.themes.customize'
            ];
        }
        
        // Додаємо пункти меню з перевіркою прав доступу
        foreach ($devMenuItems as $item) {
            // Перевіряємо права доступу
            $hasAccess = true;
            $permission = $item['permission'] ?? null;
            if ($permission !== null && is_string($permission)) {
                // Для першого користувача завжди дозволяємо доступ
                $session = sessionManager();
                $userId = (int)$session->get('admin_user_id');
                if ($userId === 1) {
                    $hasAccess = true;
                } elseif (function_exists('current_user_can')) {
                    $hasAccess = current_user_can($permission);
                } else {
                    $hasAccess = false;
                }
            }
            
            // Якщо є підменю, перевіряємо права для кожного підпункту
            if ($hasAccess && isset($item['submenu']) && is_array($item['submenu'])) {
                $filteredSubmenu = [];
                foreach ($item['submenu'] as $subItem) {
                    $subHasAccess = true;
                    $subPermission = $subItem['permission'] ?? null;
                    if ($subPermission !== null && is_string($subPermission)) {
                        $session = sessionManager();
                        $userId = (int)$session->get('admin_user_id');
                        if ($userId === 1) {
                            $subHasAccess = true;
                        } elseif (function_exists('current_user_can')) {
                            $subHasAccess = current_user_can($subPermission);
                        } else {
                            $subHasAccess = false;
                        }
                    }
                    if ($subHasAccess) {
                        $filteredSubmenu[] = $subItem;
                    }
                }
                $item['submenu'] = $filteredSubmenu;
                
                // Якщо після фільтрації підменю порожнє, не додаємо пункт меню
                if (empty($filteredSubmenu) && ($item['href'] ?? '#') === '#') {
                    $hasAccess = false;
                }
            }
            
            if ($hasAccess) {
                $menu[] = $item;
            }
        }
        
        
        return $menu;
    }, 1);
    
    // ТИМЧАСОВО ВИМКНЕНО: Старі хуки для roles та users (тепер все в одному місці вище)
    /*
    // Регистрация системного пункта меню для управления ролями
    addHook('admin_menu', function($menu) {
        // Проверяем право доступа или разрешаем для первого пользователя
        $hasAccess = false;
        
        if (function_exists('sessionManager')) {
            $session = sessionManager();
            $userId = $session->get('admin_user_id');
            // Для первого пользователя всегда разрешаем доступ
            if ($userId == 1) {
                $hasAccess = true;
            } elseif (function_exists('current_user_can')) {
                $hasAccess = current_user_can('admin.roles');
            }
        } elseif (function_exists('current_user_can')) {
            $hasAccess = current_user_can('admin.roles');
        }
        
        if ($hasAccess) {
            $menu[] = [
                'text' => 'Ролі та права',
                'icon' => 'fas fa-user-shield',
                'href' => UrlHelper::admin('roles'),
                'page' => 'roles',
                'order' => 30
            ];
        }
        return $menu;
    }, 5);
    
    // Регистрация системного пункта меню для управления пользователями
    addHook('admin_menu', function($menu) {
        // Проверяем право доступа или разрешаем для первого пользователя
        $hasAccess = false;
        
        if (function_exists('sessionManager')) {
            $session = sessionManager();
            $userId = $session->get('admin_user_id');
            // Для первого пользователя всегда разрешаем доступ
            if ($userId == 1) {
                $hasAccess = true;
            } elseif (function_exists('current_user_can')) {
                $hasAccess = current_user_can('admin.users');
            }
        } elseif (function_exists('current_user_can')) {
            $hasAccess = current_user_can('admin.users');
        }
        
        if ($hasAccess) {
            $menu[] = [
                'text' => 'Користувачі',
                'icon' => 'fas fa-users',
                'href' => UrlHelper::admin('users'),
                'page' => 'users',
                'order' => 29
            ];
        }
        return $menu;
    }, 5);
    */
}

if (function_exists('doHook')) {
    $handled = doHook('handle_early_request', false);
    if ($handled === true) exit;
}

require_once __DIR__ . '/includes/router-handler.php';
