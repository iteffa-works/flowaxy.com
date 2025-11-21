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

if (!$isInstaller && file_exists($databaseIniFile)) {
    ModuleLoader::init();
    
    if (class_exists('SettingsManager')) {
        try {
            $tz = settingsManager()->get('timezone', 'Europe/Kiev');
            if (!empty($tz) && in_array($tz, timezone_identifiers_list())) {
                date_default_timezone_set($tz);
            }
        } catch (Exception $e) {
            @date_default_timezone_set('Europe/Kiev');
        }
    } else {
        @date_default_timezone_set('Europe/Kiev');
    }
} else {
    @date_default_timezone_set('Europe/Kiev');
}

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
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            http_response_code(500);
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

Session::start([
    'name' => 'PHPSESSID',
    'lifetime' => 7200,
    'domain' => '',
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if ($isInstaller) {
    require_once __DIR__ . '/includes/installer-handler.php';
    exit;
}

initializeSystem();

// Инициализация системы ролей (проверка и создание таблиц при необходимости)
if (file_exists(__DIR__ . '/includes/roles-init.php')) {
    require_once __DIR__ . '/includes/roles-init.php';
    if (function_exists('initializeRolesSystem')) {
        initializeRolesSystem();
    }
}

// Регистрация системного пункта меню для управления ролями
if (function_exists('addHook')) {
    addHook('admin_menu', function($menu) {
        // Проверяем право доступа или разрешаем для первого пользователя
        $hasAccess = false;
        
        if (function_exists('Session')) {
            $userId = Session::get('admin_user_id');
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
}

if (function_exists('doHook')) {
    $handled = doHook('handle_early_request', false);
    if ($handled === true) exit;
}

require_once __DIR__ . '/includes/router-handler.php';
