<?php
/**
 * Flowaxy CMS - Initialization
 * 
 * @package Engine
 * @version 7.0.0
 */

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstaller = strpos($requestUri, '/install') === 0;
$databaseIniFile = __DIR__ . '/data/database.ini';
$defaultTimezone = 'Europe/Kyiv';

// Установка часового пояса
$timezone = $defaultTimezone;
if (!$isInstaller && file_exists($databaseIniFile)) {
    ModuleLoader::init();
    
    if (class_exists('SettingsManager')) {
        try {
            $tz = settingsManager()->get('timezone', $defaultTimezone);
            
            // Автоматическое обновление старого часового пояса на новый
            if ($tz === 'Europe/Kiev') {
                $tz = 'Europe/Kyiv';
                // Обновляем в настройках
                try {
                    settingsManager()->set('timezone', 'Europe/Kyiv');
                } catch (Exception $e) {
                    error_log("Failed to update timezone setting: " . $e->getMessage());
                }
            }
            
            if (!empty($tz) && in_array($tz, timezone_identifiers_list())) {
                $timezone = $tz;
            } else {
                // Если часовой пояс невалидный, используем значение по умолчанию
                $timezone = $defaultTimezone;
            }
        } catch (Exception $e) {
            // Используем значение по умолчанию
            error_log("Error loading timezone: " . $e->getMessage());
        }
    }
}
date_default_timezone_set($timezone);

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
            echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            // Устанавливаем код ответа только если заголовки еще не отправлены
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo '<h1>Внутрішня помилка сервера</h1>';
        }
    });
    
    register_shutdown_function(function(): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            Logger::getInstance()->logCritical('Fatal error: ' . $error['message'], ['file' => $error['file'], 'line' => $error['line']]);
        }
    });
}

// Определяем secure на основе протокола из настроек
// Используем UrlHelper, если доступен, иначе detectProtocol()
$isSecure = false;
if (class_exists('UrlHelper')) {
    $isSecure = UrlHelper::isHttps();
} elseif (function_exists('detectProtocol')) {
    $protocol = detectProtocol();
    $isSecure = ($protocol === 'https://');
} else {
    // Fallback на автоматическое определение
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
}

Session::start([
    'name' => 'PHPSESSID',
    'lifetime' => 7200,
    'domain' => '',
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if ($isInstaller) {
    require_once __DIR__ . '/includes/installer-handler.php';
    exit;
}

// Установка security headers для защиты от атак
if (!headers_sent() && class_exists('Response')) {
    Response::setSecurityHeaders();
}

initializeSystem();

// Обновление протокола на основе настройки из базы данных (если доступна)
// detectProtocol() уже проверяет настройку из базы, но здесь мы обновляем глобальную переменную
// для обеспечения консистентности
if (class_exists('SettingsManager') && file_exists(__DIR__ . '/data/database.ini')) {
    try {
        $settingsManager = settingsManager();
        $protocolSetting = $settingsManager->get('site_protocol', 'auto');
        
        if ($protocolSetting !== 'auto') {
            $newProtocol = $protocolSetting === 'https' ? 'https://' : 'http://';
            // Обновляем глобальную переменную для использования в detectProtocol()
            $GLOBALS['_SITE_PROTOCOL'] = $newProtocol;
        }
    } catch (Exception $e) {
        error_log('init.php: Could not update protocol from settings: ' . $e->getMessage());
    }
}

// Инициализация системы ролей (проверка и создание таблиц при необходимости)
if (file_exists(__DIR__ . '/includes/roles-init.php')) {
    require_once __DIR__ . '/includes/roles-init.php';
    if (function_exists('initializeRolesSystem')) {
        initializeRolesSystem();
    }
}

// ВРЕМЕННО: Регистрация всех страниц в боковую панель на время разработки
if (function_exists('addHook')) {
    addHook('admin_menu', function($menu) {
        // Для временной разработки - добавляем все страницы
        $devMenuItems = [
            [
                'text' => 'Панель управління',
                'icon' => 'fas fa-tachometer-alt',
                'href' => UrlHelper::admin('dashboard'),
                'page' => 'dashboard',
                'order' => 1
            ],
            [
                'text' => 'Налаштування',
                'icon' => 'fas fa-cog',
                'href' => UrlHelper::admin('settings'),
                'page' => 'settings',
                'order' => 10
            ],
            [
                'text' => 'Налаштування сайту',
                'icon' => 'fas fa-globe',
                'href' => UrlHelper::admin('site-settings'),
                'page' => 'site-settings',
                'order' => 11
            ],
            [
                'text' => 'Користувачі',
                'icon' => 'fas fa-users',
                'href' => UrlHelper::admin('users'),
                'page' => 'users',
                'order' => 20
            ],
            [
                'text' => 'Ролі та права',
                'icon' => 'fas fa-user-shield',
                'href' => UrlHelper::admin('roles'),
                'page' => 'roles',
                'order' => 21
            ],
            [
                'text' => 'Мій профіль',
                'icon' => 'fas fa-user',
                'href' => UrlHelper::admin('profile'),
                'page' => 'profile',
                'order' => 22
            ],
            [
                'text' => 'Плагіни',
                'icon' => 'fas fa-plug',
                'href' => UrlHelper::admin('plugins'),
                'page' => 'plugins',
                'order' => 30
            ],
            [
                'text' => 'Теми',
                'icon' => 'fas fa-paint-brush',
                'href' => UrlHelper::admin('themes'),
                'page' => 'themes',
                'order' => 31
            ],
            [
                'text' => 'Редактор теми',
                'icon' => 'fas fa-code',
                'href' => UrlHelper::admin('theme-editor'),
                'page' => 'theme-editor',
                'order' => 32
            ],
            [
                'text' => 'API ключі',
                'icon' => 'fas fa-key',
                'href' => UrlHelper::admin('api-keys'),
                'page' => 'api-keys',
                'order' => 40
            ],
            [
                'text' => 'Webhooks',
                'icon' => 'fas fa-link',
                'href' => UrlHelper::admin('webhooks'),
                'page' => 'webhooks',
                'order' => 41
            ],
            [
                'text' => 'Логи',
                'icon' => 'fas fa-file-alt',
                'href' => UrlHelper::admin('logs-view'),
                'page' => 'logs-view',
                'order' => 50
            ],
            [
                'text' => 'Кеш',
                'icon' => 'fas fa-database',
                'href' => UrlHelper::admin('cache-view'),
                'page' => 'cache-view',
                'order' => 51
            ]
        ];
        
        // Добавляем кастомізатор если поддерживается
        if (function_exists('themeSupportsCustomization') && themeSupportsCustomization()) {
            $devMenuItems[] = [
                'text' => 'Кастомізатор',
                'icon' => 'fas fa-palette',
                'href' => UrlHelper::admin('customizer'),
                'page' => 'customizer',
                'order' => 33
            ];
        }
        
        // Добавляем все пункты меню
        foreach ($devMenuItems as $item) {
            $menu[] = $item;
        }
        
        return $menu;
    }, 1); // Высокий приоритет чтобы добавить первым
    
    // ВРЕМЕННО ОТКЛЮЧЕНО: Старые хуки для roles и users (теперь все в одном месте выше)
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
