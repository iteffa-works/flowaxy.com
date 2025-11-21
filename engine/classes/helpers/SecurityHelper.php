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
     * Также проверяет токен сессии для защиты от одновременного входа
     * 
     * @return bool
     */
    public static function isAdminLoggedIn(): bool {
        // Убеждаемся, что Session инициализирован
        if (!class_exists('Session')) {
            return false;
        }
        
        $session = sessionManager();
        
        // Проверяем базовую авторизацию
        if (!$session->has(ADMIN_SESSION_NAME) || $session->get(ADMIN_SESSION_NAME) !== true) {
            return false;
        }
        
        $userId = (int)$session->get('admin_user_id');
        if ($userId <= 0) {
            return false;
        }
        
        // Проверяем токен сессии для защиты от одновременного входа
        $sessionToken = $session->get('admin_session_token');
        if (empty($sessionToken)) {
            // Если токен отсутствует в сессии, проверяем, может быть это старый вход
            // Разлогиниваем пользователя
            error_log('SecurityHelper::isAdminLoggedIn() - Session token missing, logging out user ID: ' . $userId);
            self::logout();
            return false;
        }
        
        // Проверяем токен в базе данных
        try {
            $db = DatabaseHelper::getConnection();
            if ($db) {
                $stmt = $db->prepare("SELECT session_token FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user || empty($user['session_token'])) {
                    // Токен отсутствует в БД - пользователь разлогинен с другого устройства
                    error_log('SecurityHelper::isAdminLoggedIn() - Session token not found in DB, user logged out from another device. User ID: ' . $userId);
                    self::logout();
                    return false;
                }
                
                // Сравниваем токены
                if (!hash_equals($user['session_token'], $sessionToken)) {
                    // Токены не совпадают - пользователь зашел с другого устройства
                    error_log('SecurityHelper::isAdminLoggedIn() - Session tokens do not match, user logged in from another device. User ID: ' . $userId);
                    self::logout();
                    return false;
                }
            }
        } catch (Exception $e) {
            error_log('SecurityHelper::isAdminLoggedIn() - Error checking session token: ' . $e->getMessage());
            // В случае ошибки БД разрешаем доступ (чтобы не блокировать пользователя)
            return true;
        }
        
        return true;
    }
    
    /**
     * Выход из системы (очистка сессии и токена)
     * 
     * @return void
     */
    private static function logout(): void {
        $session = sessionManager();
        $userId = (int)$session->get('admin_user_id');
        
        // Очищаем токен в базе данных
        if ($userId > 0) {
            try {
                $db = DatabaseHelper::getConnection();
                if ($db) {
                    $stmt = $db->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
                    $stmt->execute([$userId]);
                }
            } catch (Exception $e) {
                error_log('SecurityHelper::logout() - Error clearing session token: ' . $e->getMessage());
            }
        }
        
        // Очищаем сессию
        $session->remove(ADMIN_SESSION_NAME);
        $session->remove('admin_user_id');
        $session->remove('admin_username');
        $session->remove('admin_session_token');
    }
    
    /**
     * Требует авторизации админа
     * 
     * @return void
     */
    public static function requireAdmin(): void {
        if (!self::isAdminLoggedIn()) {
            // Для AJAX запросов возвращаем JSON ошибку
            if (class_exists('AjaxHandler') && AjaxHandler::isAjax()) {
                // Очищаем буфер вывода
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                if (class_exists('Response')) {
                    Response::jsonResponse([
                        'success' => false,
                        'error' => 'Потрібна авторизація',
                        'auth_required' => true
                    ], 401);
                } else {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Потрібна авторизація',
                        'auth_required' => true
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
            
            // Для обычных запросов делаем редирект
            if (class_exists('Response')) {
                $adminLoginUrl = class_exists('UrlHelper') ? UrlHelper::admin('login') : (defined('ADMIN_URL') ? ADMIN_URL . '/login' : '/admin/login');
                Response::redirectStatic($adminLoginUrl);
            } else {
                $adminLoginUrl = class_exists('UrlHelper') ? UrlHelper::admin('login') : (defined('ADMIN_URL') ? ADMIN_URL . '/login' : '/admin/login');
                header('Location: ' . $adminLoginUrl);
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

