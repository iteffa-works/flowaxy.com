<?php
/**
 * Хелпер для работы с безопасностью
 * Обертка над Security классом
 * 
 * @package Engine\Classes\Helpers
 * @version 1.0.0
 */

declare(strict_types=1);

class SecurityHelper {
    /**
     * Генерация CSRF токена
     * 
     * @return string
     */
    public static function csrfToken(): string {
        if (!class_exists('Security')) {
            return '';
        }
        // Убеждаемся, что Session инициализирован
        if (session_status() !== PHP_SESSION_ACTIVE && class_exists('Session')) {
            Session::start();
        }
        return Security::csrfToken();
    }
    
    /**
     * Проверка CSRF токена
     * 
     * @param string|null $token Токен для проверки
     * @return bool
     */
    public static function verifyCsrfToken(?string $token = null): bool {
        if (!class_exists('Security')) {
            return false;
        }
        // Убеждаемся, что Session инициализирован
        if (session_status() !== PHP_SESSION_ACTIVE && class_exists('Session')) {
            Session::start();
        }
        return Security::verifyCsrfToken($token);
    }
    
    /**
     * Генерация CSRF поля для формы
     * 
     * @return string
     */
    public static function csrfField(): string {
        if (!class_exists('Security')) {
            return '';
        }
        // Убеждаемся, что Session инициализирован
        if (session_status() !== PHP_SESSION_ACTIVE && class_exists('Session')) {
            Session::start();
        }
        return Security::csrfField();
    }
    
    /**
     * Проверка, залогинен ли админ
     * 
     * @return bool
     */
    public static function isAdminLoggedIn(): bool {
        // Убеждаемся, что Session инициализирован
        if (!class_exists('Session')) {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
        return Session::has(ADMIN_SESSION_NAME) && Session::get(ADMIN_SESSION_NAME) === true;
    }
    
    /**
     * Требует авторизации админа
     * 
     * @return void
     */
    public static function requireAdmin(): void {
        if (!self::isAdminLoggedIn()) {
            if (class_exists('Response')) {
                Response::redirectStatic(ADMIN_URL . '/login');
            } else {
                header('Location: ' . ADMIN_URL . '/login');
                exit;
            }
        }
    }
    
    /**
     * Безопасный вывод HTML
     * 
     * @param mixed $value Значение для вывода
     * @param string $default Значение по умолчанию
     * @return string
     */
    public static function safeHtml($value, string $default = ''): string {
        if (is_array($value) || is_object($value)) {
            return Security::clean(Json::stringify($value));
        }
        return Security::clean((string)($value ?: $default));
    }
    
    /**
     * Санитизация входных данных
     * 
     * @param mixed $input Входные данные
     * @return string
     */
    public static function sanitizeInput($input): string {
        if (is_string($input)) {
            return Security::clean(trim($input), true);
        }
        
        if (is_numeric($input)) {
            return (string)$input;
        }
        
        if (is_array($input)) {
            try {
                return Json::stringify($input);
            } catch (Exception $e) {
                if (function_exists('logger')) {
                    logger()->logError('JSON encoding error', ['error' => $e->getMessage()]);
                } else {
                    error_log("JSON encoding error: " . $e->getMessage());
                }
                return '';
            }
        }
        
        if (is_bool($input)) {
            return $input ? '1' : '0';
        }
        
        return '';
    }
}

