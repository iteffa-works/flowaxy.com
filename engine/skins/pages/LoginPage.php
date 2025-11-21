<?php
/**
 * Сторінка входу в адмінку
 */

class LoginPage {
    private $db;
    private $error = '';
    
    public function __construct() {
        // Убеждаемся, что сессия инициализирована для CSRF токена (используем наши классы)
        // Session::start() теперь автоматически проверяет настройки протокола из базы данных
        if (!Session::isStarted()) {
            // Получаем настройки протокола из базы данных для правильной инициализации сессии
            $isSecure = false;
            if (class_exists('SettingsManager') && file_exists(__DIR__ . '/../../../data/database.ini')) {
                try {
                    $settingsManager = settingsManager();
                    $protocolSetting = $settingsManager->get('site_protocol', 'auto');
                    if ($protocolSetting === 'https') {
                        $isSecure = true;
                    } elseif ($protocolSetting === 'http') {
                        $isSecure = false;
                    }
                } catch (Exception $e) {
                    // Игнорируем ошибки
                }
            }
            
            Session::start([
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        $this->db = DatabaseHelper::getConnection();
    }
    
    public function handle() {
        // Якщо вже авторизований (не через POST), перенаправляємо
        // Но только если это не POST запрос (чтобы обработать форму входа)
        if ((Request::getMethod() !== 'POST' && empty($_POST)) && SecurityHelper::isAdminLoggedIn()) {
            Response::redirectStatic(UrlHelper::admin('dashboard'));
            return;
        }
        
        // Обробка форми входу (используем Request напрямую из engine/classes)
        if (Request::getMethod() === 'POST' || !empty($_POST)) {
            $this->processLogin();
        }
        
        // Всегда рендерим страницу (с ошибкой, если она есть)
        $this->render();
    }
    
    /**
     * Обробка входу
     */
    private function processLogin() {
        // Убеждаемся, что сессия инициализирована
        // НЕ переинициализируем сессию, если она уже запущена - это может сбросить cookies
        if (!Session::isStarted()) {
            // Получаем настройки протокола из базы данных для правильной инициализации сессии
            $isSecure = false;
            if (class_exists('SettingsManager') && file_exists(__DIR__ . '/../../../data/database.ini')) {
                try {
                    $settingsManager = settingsManager();
                    $protocolSetting = $settingsManager->get('site_protocol', 'auto');
                    if ($protocolSetting === 'https') {
                        $isSecure = true;
                    } elseif ($protocolSetting === 'http') {
                        $isSecure = false;
                    }
                } catch (Exception $e) {
                    // Игнорируем ошибки
                }
            }
            
            Session::start([
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        $request = Request::getInstance();
        $csrfToken = $request->post('csrf_token', '');
        
        // Проверка CSRF токена
        if (!SecurityHelper::verifyCsrfToken($csrfToken)) {
            $this->error = 'Помилка безпеки. Спробуйте ще раз.';
            SecurityHelper::csrfToken(); // Генерируем новый токен для следующей попытки
            return;
        }
        
        $username = SecurityHelper::sanitizeInput($request->post('username', ''));
        $password = $request->post('password', '');
        
        if (empty($username) || empty($password)) {
            $this->error = 'Заповніть всі поля';
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, password, session_token, last_activity, is_active FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Проверяем, активен ли пользователь
                $isActive = isset($user['is_active']) ? (int)$user['is_active'] : 1;
                if ($isActive === 0) {
                    // Пользователь неактивен - проверяем, не истекла ли сессия
                    $sessionLifetime = 7200; // По умолчанию 2 часа
                    if (class_exists('SystemConfig')) {
                        $systemConfig = SystemConfig::getInstance();
                        $sessionLifetime = $systemConfig->getSessionLifetime();
                    }
                    
                    // Если есть last_activity, проверяем, не истекла ли сессия
                    if (!empty($user['last_activity'])) {
                        $lastActivity = strtotime($user['last_activity']);
                        $currentTime = time();
                        $timeDiff = $currentTime - $lastActivity;
                        
                        if ($timeDiff <= $sessionLifetime) {
                            // Сессия еще валидна - блокируем вход
                            $this->error = 'Ваш аккаунт вже використовується з іншого пристрою або браузера. Будь ласка, спочатку вийдіть з системи або дочекайтеся закінчення сесії.';
                            return;
                        }
                    } else if (!empty($user['session_token'])) {
                        // Если есть токен, но нет last_activity - блокируем вход
                        $this->error = 'Ваш аккаунт вже використовується з іншого пристрою або браузера. Будь ласка, спочатку вийдіть з системи або дочекайтеся закінчення сесії.';
                        return;
                    }
                } else if (!empty($user['session_token'])) {
                    // Пользователь активен, но есть токен - проверяем валидность сессии
                    $sessionLifetime = 7200;
                    if (class_exists('SystemConfig')) {
                        $systemConfig = SystemConfig::getInstance();
                        $sessionLifetime = $systemConfig->getSessionLifetime();
                    }
                    
                    if (!empty($user['last_activity'])) {
                        $lastActivity = strtotime($user['last_activity']);
                        $currentTime = time();
                        $timeDiff = $currentTime - $lastActivity;
                        
                        if ($timeDiff <= $sessionLifetime) {
                            // Сессия еще валидна - блокируем вход
                            $this->error = 'Ваш аккаунт вже використовується з іншого пристрою або браузера. Будь ласка, спочатку вийдіть з системи або дочекайтеся закінчення сесії.';
                            return;
                        }
                    } else {
                        // Есть токен, но нет last_activity - блокируем вход
                        $this->error = 'Ваш аккаунт вже використовується з іншого пристрою або браузера. Будь ласка, спочатку вийдіть з системи або дочекайтеся закінчення сесії.';
                        return;
                    }
                }
                
                // Успішний вхід
                $session = sessionManager();
                
                // Генерируем уникальный токен сессии
                $sessionToken = bin2hex(random_bytes(32)); // 64 символа
                $now = date('Y-m-d H:i:s');
                
                // Сохраняем токен, время активности и помечаем как активного в БД
                $stmt = $this->db->prepare("UPDATE users SET session_token = ?, last_activity = ?, is_active = 1 WHERE id = ?");
                $stmt->execute([$sessionToken, $now, $user['id']]);
                
                // Сохраняем данные авторизации в сессии
                $session->set(ADMIN_SESSION_NAME, true);
                $session->set('admin_user_id', $user['id']);
                $session->set('admin_username', $user['username']);
                
                // Регенеруємо ID сесії для безпеки
                Session::regenerate(true);
                
                // Редирект на dashboard
                Response::redirectStatic(UrlHelper::admin('dashboard'));
                exit;
            } else {
                $this->error = 'Невірний логін або пароль';
            }
        } catch (Exception $e) {
            $this->error = 'Помилка входу. Спробуйте пізніше.';
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Login error', ['error' => $e->getMessage()]);
            }
        }
    }
    
    /**
     * Рендеринг сторінки
     */
    private function render() {
        // Убеждаемся, что сессия инициализирована перед генерацией токена
        if (!Session::isStarted()) {
            // Получаем настройки протокола из базы данных
            $isSecure = false;
            if (class_exists('SettingsManager') && file_exists(__DIR__ . '/../../../data/database.ini')) {
                try {
                    $settingsManager = settingsManager();
                    $protocolSetting = $settingsManager->get('site_protocol', 'auto');
                    if ($protocolSetting === 'https') {
                        $isSecure = true;
                    } elseif ($protocolSetting === 'http') {
                        $isSecure = false;
                    }
                } catch (Exception $e) {
                    // Игнорируем ошибки
                }
            }
            
            Session::start([
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        // Определяем, используется ли HTTPS для отображения правильного сообщения
        $isHttps = false;
        if (class_exists('UrlHelper')) {
            $isHttps = UrlHelper::isHttps();
        } elseif (function_exists('detectProtocol')) {
            $protocol = detectProtocol();
            $isHttps = ($protocol === 'https://');
        } else {
            $isHttps = (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            );
        }
        
        $error = $this->error;
        $csrfToken = SecurityHelper::csrfToken();
        $isSecureConnection = $isHttps;
        
        include __DIR__ . '/../templates/login.php';
    }
}
