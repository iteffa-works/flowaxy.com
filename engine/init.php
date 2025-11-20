<?php
/**
 * Flowaxy CMS - Initialization
 * Загрузка модулей, плагинов, тем и инициализация системы
 * 
 * @package Engine
 * @version 7.0.0
 */

declare(strict_types=1);

// Получаем переменные из flowaxy.php
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstaller = strpos($requestUri, '/install') === 0;
$databaseIniFile = __DIR__ . '/data/database.ini';

// Инициализация модулей (только если система установлена)
if (!$isInstaller && file_exists($databaseIniFile)) {
    ModuleLoader::init();
    
    // Установка часового пояса
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

// Инициализация логирования (только если система установлена)
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

// Инициализация сессии
Session::start([
    'name' => 'PHPSESSID',
    'lifetime' => 7200,
    'domain' => '',
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// ========== ОБРАБОТКА УСТАНОВЩИКА ==========
if ($isInstaller) {
    require_once __DIR__ . '/includes/installer-handler.php';
    exit;
}

// ========== ИНИЦИАЛИЗАЦИЯ СИСТЕМЫ ==========
initializeSystem();

// Хук для ранних запросов
if (function_exists('doHook')) {
    $handled = doHook('handle_early_request', false);
    if ($handled === true) exit;
    
    // Регистрируем пункты меню для API и Webhooks
    addHook('admin_menu', function($menu) {
        $menu[] = [
            'text' => 'Интеграции',
            'icon' => 'fas fa-plug',
            'href' => '#',
            'order' => 40,
            'submenu' => [
                [
                    'text' => 'API Ключи',
                    'icon' => 'fas fa-key',
                    'href' => UrlHelper::admin('api-keys'),
                    'page' => 'api-keys',
                    'order' => 10
                ],
                [
                    'text' => 'Webhooks',
                    'icon' => 'fas fa-paper-plane',
                    'href' => UrlHelper::admin('webhooks'),
                    'page' => 'webhooks',
                    'order' => 20
                ]
            ]
        ];
        return $menu;
    });
}

// ========== РОУТИНГ ==========
require_once __DIR__ . '/includes/router-handler.php';
