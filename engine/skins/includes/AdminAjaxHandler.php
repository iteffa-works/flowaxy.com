<?php
/**
 * Обертка над AjaxHandler для админ-панели
 * Интегрирует функционал из engine/classes/http/AjaxHandler
 * 
 * @package Engine\Skins\Includes
 */

class AdminAjaxHandler {
    private static ?AjaxHandler $handler = null;
    
    /**
     * Получить экземпляр AjaxHandler
     */
    public static function handler(): AjaxHandler {
        if (self::$handler === null) {
            self::$handler = new AjaxHandler();
            
            // Настройка авторизации для админ-панели
            self::$handler->setAuthCallback(function() {
                return SecurityHelper::isAdmin();
            });
        }
        return self::$handler;
    }
    
    /**
     * Проверка на AJAX запрос
     */
    public static function isAjax(): bool {
        return AjaxHandler::isAjax();
    }
    
    /**
     * Регистрация обработчика действия
     */
    public static function register(string $action, callable $handler, array $options = []): AjaxHandler {
        return self::handler()->register($action, $handler, $options);
    }
    
    /**
     * Регистрация нескольких действий
     */
    public static function registerMultiple(array $actions): AjaxHandler {
        return self::handler()->registerMultiple($actions);
    }
    
    /**
     * Обработка AJAX запроса
     */
    public static function handle(?string $action = null): void {
        self::handler()->handle($action);
    }
    
    /**
     * Получение санитизированных данных из запроса
     */
    public static function getSanitizedData(array $keys = []): array {
        return AjaxHandler::getSanitizedData($keys);
    }
    
    /**
     * Получение файла из запроса
     */
    public static function getFile(string $key): ?array {
        return AjaxHandler::getFile($key);
    }
    
    /**
     * Быстрая проверка AJAX
     */
    public static function check(): bool {
        return AjaxHandler::check();
    }
}

