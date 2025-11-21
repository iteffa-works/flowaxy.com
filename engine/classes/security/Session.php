<?php
/**
 * Клас для роботи з сесіями
 * Управління сесіями користувачів з додатковою безпекою
 * 
 * @package Engine\Classes\Security
 * @version 1.1.0
 */

declare(strict_types=1);

class Session {
    private static bool $started = false;
    private static array $config = [
        'name' => 'PHPSESSID',
        'lifetime' => 7200, // Будет переопределено из настроек
        'domain' => '',
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    /**
     * Ініціалізація сесії
     * 
     * @param array $config Конфігурація сесії
     * @return void
     */
    public static function start(array $config = []): void {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        
        // Загружаем параметры из настроек, если доступны
        if (class_exists('SystemConfig')) {
            $systemConfig = SystemConfig::getInstance();
            $defaultConfig = [
                'name' => $systemConfig->getSessionName(),
                'lifetime' => $systemConfig->getSessionLifetime()
            ];
            self::$config = array_merge(self::$config, $defaultConfig);
        }
        
        self::$config = array_merge(self::$config, $config);
        
        // Определяем secure на основе настроек из базы данных (если доступны)
        $isSecure = self::$config['secure'];
        
        // Проверяем настройки протокола из базы данных
        $protocolFromSettings = null;
        if (class_exists('SettingsManager') && file_exists(__DIR__ . '/../../data/database.ini')) {
            try {
                $settingsManager = settingsManager();
                $protocolSetting = $settingsManager->get('site_protocol', 'auto');
                if ($protocolSetting === 'https') {
                    $protocolFromSettings = 'https://';
                } elseif ($protocolSetting === 'http') {
                    $protocolFromSettings = 'http://';
                }
            } catch (Exception $e) {
                // Игнорируем ошибки при загрузке настроек
            }
        }
        
        // Если в настройках явно указан протокол, используем его
        if ($protocolFromSettings === 'https://') {
            $isSecure = true;
        } elseif ($protocolFromSettings === 'http://') {
            $isSecure = false;
        } else {
            // Если настройки 'auto' или недоступны, проверяем реальное соединение
            if ($isSecure) {
                // Дополнительная проверка реального протокола
                $realHttps = (
                    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                    (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
                    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                );
                
                // Если реальное соединение HTTP, но secure=true, отключаем secure для совместимости
                if (!$realHttps) {
                    $isSecure = false;
                    error_log("Session: secure=true but connection is HTTP, disabling secure flag for compatibility");
                }
            }
        }
        
        // SameSite настройка (важно для Edge)
        $samesite = self::$config['samesite'] ?? 'Lax';
        // Если SameSite=None, но secure=false, Edge блокирует - меняем на Lax
        if ($samesite === 'None' && !$isSecure) {
            $samesite = 'Lax';
            error_log("Session: SameSite=None requires Secure flag, changing to Lax for compatibility");
        }
        
        session_name(self::$config['name']);
        
        // Определяем domain правильно для Edge
        // Edge очень строгий к domain - лучше использовать пустой domain для точного совпадения
        $cookieDomain = self::$config['domain'];
        if (empty($cookieDomain)) {
            // Для Edge лучше использовать пустой domain - браузер сам определит домен
            // Это гарантирует, что cookie будет работать для точного домена
            $cookieDomain = '';
            
            // Альтернатива: можно использовать текущий домен, но без точки в начале
            // $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // $host = preg_replace('/:\d+$/', '', $host);
            // if ($host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP)) {
            //     $cookieDomain = $host; // Без точки в начале!
            // }
        }
        
        // ВАЖНО: session_set_cookie_params() должен вызываться ДО session_start()
        // Это критично для Edge и других браузеров
        session_set_cookie_params([
            'lifetime' => self::$config['lifetime'],
            'path' => self::$config['path'],
            'domain' => $cookieDomain, // Используем правильно определенный domain
            'secure' => $isSecure,
            'httponly' => self::$config['httponly'],
            'samesite' => $samesite
        ]);
        
        // Налаштування параметрів сесії через ini_set (для совместимости)
        ini_set('session.cookie_lifetime', self::$config['lifetime']);
        ini_set('session.cookie_path', self::$config['path']);
        ini_set('session.cookie_domain', $cookieDomain); // Используем правильно определенный domain
        ini_set('session.cookie_secure', $isSecure ? '1' : '0');
        ini_set('session.cookie_httponly', self::$config['httponly'] ? '1' : '0');
        
        // Для PHP 7.3+ используем ini_set для SameSite
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            ini_set('session.cookie_samesite', $samesite);
        }
        
        if (!headers_sent()) {
            session_start();
            self::$started = true;
        } elseif (class_exists('Logger')) {
            // Логируем только критичные ошибки
            Logger::getInstance()->logWarning('Session::start() - Headers already sent, cannot start session');
        }
    }
    
    /**
     * Отримання значення з сесії
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Встановлення значення в сесію
     * 
     * @param string $key Ключ
     * @param mixed $value Значення
     * @return void
     */
    public static function set(string $key, $value): void {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Перевірка наявності ключа в сесії
     * 
     * @param string $key Ключ
     * @return bool
     */
    public static function has(string $key): bool {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Видалення значення з сесії
     * 
     * @param string $key Ключ
     * @return void
     */
    public static function remove(string $key): void {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }
    
    /**
     * Отримання всіх даних сесії
     * 
     * @return array
     */
    public static function all(): array {
        self::ensureStarted();
        return $_SESSION ?? [];
    }
    
    /**
     * Очищення всіх даних сесії
     * 
     * @return void
     */
    public static function clear(): void {
        self::ensureStarted();
        $_SESSION = [];
    }
    
    /**
     * Видалення сесії
     * 
     * @return void
     */
    public static function destroy(): void {
        if (!self::$started) {
            return;
        }
        
        $_SESSION = [];
        
        // Используем наш класс Cookie для удаления cookie
        if (isset($_COOKIE[session_name()])) {
            if (class_exists('Cookie')) {
                Cookie::set(session_name(), '', time() - 3600, self::$config['path'], self::$config['domain'], false, true);
            } else {
                setcookie(session_name(), '', time() - 3600, self::$config['path'], self::$config['domain']);
            }
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Регенерація ID сесії
     * 
     * @param bool $deleteOldSession Видаляти стару сесію
     * @return bool
     */
    public static function regenerate(bool $deleteOldSession = true): bool {
        self::ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Отримання ID сесії
     * 
     * @return string
     */
    public static function getId(): string {
        self::ensureStarted();
        return session_id();
    }
    
    /**
     * Встановлення ID сесії
     * 
     * @param string $id ID сесії
     * @return bool
     */
    public static function setId(string $id): bool {
        if (self::$started) {
            return false;
        }
        
        return session_id($id) !== '';
    }
    
    /**
     * Перевірка, чи запущена сесія
     * 
     * @return bool
     */
    public static function isStarted(): bool {
        return self::$started && session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Отримання Flash повідомлення (читається один раз)
     * 
     * @param string $key Ключ
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public static function flash(string $key, $default = null) {
        $value = self::get('_flash.' . $key, $default);
        self::remove('_flash.' . $key);
        return $value;
    }
    
    /**
     * Встановлення Flash повідомлення
     * 
     * @param string $key Ключ
     * @param mixed $value Значення
     * @return void
     */
    public static function setFlash(string $key, $value): void {
        self::set('_flash.' . $key, $value);
    }
    
    /**
     * Переконатися, що сесія запущена
     * 
     * @return void
     */
    private static function ensureStarted(): void {
        if (!self::$started) {
            self::start();
        }
    }
}
