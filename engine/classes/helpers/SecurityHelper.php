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
     * Проверка только через базу данных (без проверки сессии)
     * 
     * @return bool
     */
    public static function isAdminLoggedIn(): bool {
        // Проверяем авторизацию только через базу данных
        // Сессия нужна только для получения ID пользователя
        if (!class_exists('Session') || !Session::isStarted()) {
            return false;
        }
        
        $session = sessionManager();
        $userId = (int)$session->get('admin_user_id');
        
        if ($userId <= 0) {
            return false;
        }
        
        // Проверяем авторизацию только через базу данных
        try {
            $db = DatabaseHelper::getConnection();
            if ($db) {
                // Получаем время жизни сессии из настроек
                $sessionLifetime = 7200; // По умолчанию 2 часа
                if (class_exists('SystemConfig')) {
                    $systemConfig = SystemConfig::getInstance();
                    $sessionLifetime = $systemConfig->getSessionLifetime();
                }
                
                // Проверяем пользователя в БД: активен ли он и не истекла ли сессия
                $stmt = $db->prepare("
                    SELECT id, session_token, last_activity, is_active 
                    FROM users 
                    WHERE id = ? 
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    // Пользователь не найден
                    self::logout();
                    return false;
                }
                
                // Проверяем, активен ли пользователь
                if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                    // Пользователь неактивен
                    self::logout();
                    return false;
                }
                
                // Проверяем наличие токена сессии
                if (empty($user['session_token'])) {
                    // Токен отсутствует - пользователь не авторизован
                    self::logout();
                    return false;
                }
                
                // Проверяем валидность сессии по времени последней активности
                if (!empty($user['last_activity'])) {
                    $lastActivity = strtotime($user['last_activity']);
                    $currentTime = time();
                    $timeDiff = $currentTime - $lastActivity;
                    
                    // Если прошло больше времени жизни сессии - сессия истекла
                    if ($timeDiff > $sessionLifetime) {
                        // Сессия истекла - помечаем пользователя как неактивного
                        $stmt = $db->prepare("UPDATE users SET is_active = 0, session_token = NULL, last_activity = NULL WHERE id = ?");
                        $stmt->execute([$userId]);
                        self::logout();
                        return false;
                    }
                } else if (!empty($user['session_token'])) {
                    // Если last_activity отсутствует, но есть токен - устанавливаем время активности
                    $now = date('Y-m-d H:i:s');
                    $stmt = $db->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
                    $stmt->execute([$now, $userId]);
                }
                
                return true;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Error checking admin login', ['error' => $e->getMessage()]);
            }
            // В случае ошибки БД разрешаем доступ (чтобы не блокировать пользователя)
            return true;
        }
        
        return false;
    }
    
    /**
     * Выход из системы (очистка сессии и пометка пользователя как неактивного)
     * 
     * @return void
     */
    public static function logout(): void {
        $session = sessionManager();
        $userId = (int)$session->get('admin_user_id');
        
        // Помечаем пользователя как неактивного и очищаем токен в базе данных
        if ($userId > 0) {
            try {
                $db = DatabaseHelper::getConnection();
                if ($db) {
                    $stmt = $db->prepare("UPDATE users SET is_active = 0, session_token = NULL, last_activity = NULL WHERE id = ?");
                    $stmt->execute([$userId]);
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('Error clearing session', ['error' => $e->getMessage()]);
                }
            }
        }
        
        // Очищаем сессию
        $session->remove(ADMIN_SESSION_NAME);
        $session->remove('admin_user_id');
        $session->remove('admin_username');
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
                    if (class_exists('Logger')) {
                        Logger::getInstance()->logError('JSON encoding error', ['error' => $e->getMessage()]);
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

