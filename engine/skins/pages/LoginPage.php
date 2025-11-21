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
        
        // Якщо вже авторизований, перенаправляємо (використовуємо Response клас)
        if (SecurityHelper::isAdminLoggedIn()) {
            Response::redirectStatic(UrlHelper::admin('dashboard'));
        }
        
        $this->db = DatabaseHelper::getConnection();
    }
    
    public function handle() {
        // Обробка форми входу (используем Request напрямую из engine/classes)
        if (Request::getMethod() === 'POST' || !empty($_POST)) {
            $this->processLogin();
            // Если после processLogin() произошел редирект, не вызываем render()
            // Проверяем, был ли установлен редирект
            if (headers_sent() || ob_get_level() > 0) {
                // Если заголовки уже отправлены или есть буфер, значит редирект уже выполнен
                return;
            }
        }
        
        // Рендеримо сторінку только если не было редиректа
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
        
        // Получаем токен из сессии для диагностики (используем sessionManager)
        $session = sessionManager();
        $sessionToken = $session->get('csrf_token');
        
        // Логируем для диагностики
        error_log('Login CSRF check - Session ID: ' . Session::getId());
        error_log('Login CSRF check - Session token: ' . ($sessionToken ? 'exists (' . substr($sessionToken, 0, 10) . '...)' : 'missing'));
        error_log('Login CSRF check - POST token: ' . ($csrfToken ? 'exists (' . substr($csrfToken, 0, 10) . '...)' : 'missing'));
        error_log('Login CSRF check - Session keys: ' . implode(', ', array_keys($session->all(false))));
        error_log('Login CSRF check - Cookie PHPSESSID: ' . (isset($_COOKIE['PHPSESSID']) ? 'exists' : 'missing'));
        
        // Если токен отсутствует в сессии, генерируем новый
        if (empty($sessionToken)) {
            error_log('Login CSRF: Token missing in session, generating new one');
            $sessionToken = SecurityHelper::csrfToken();
        }
        
        if (!SecurityHelper::verifyCsrfToken($csrfToken)) {
            error_log('Login CSRF: Token verification failed');
            $this->error = 'Помилка безпеки. Спробуйте ще раз.';
            // Генерируем новый токен для следующей попытки
            SecurityHelper::csrfToken();
            return;
        }
        
        error_log('Login CSRF: Token verification successful');
        
        $username = SecurityHelper::sanitizeInput($request->post('username', ''));
        $password = $request->post('password', '');
        
        if (empty($username) || empty($password)) {
            $this->error = 'Заповніть всі поля';
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, password, session_token FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Проверяем, есть ли активная сессия (токен в БД)
                if (!empty($user['session_token'])) {
                    // Активная сессия существует - блокируем вход
                    $this->error = 'Ваш аккаунт вже використовується з іншого пристрою або браузера. Будь ласка, спочатку вийдіть з системи або дочекайтеся закінчення сесії.';
                    error_log('Login blocked - User ID: ' . $user['id'] . ' already has active session token');
                    return;
                }
                
                // Успішний вхід (використовуємо SessionManager)
                $session = sessionManager();
                
                // Генерируем уникальный токен сессии для защиты от одновременного входа
                $sessionToken = bin2hex(random_bytes(32)); // 64 символа
                
                // Сохраняем токен в БД
                $stmt = $this->db->prepare("UPDATE users SET session_token = ? WHERE id = ?");
                $stmt->execute([$sessionToken, $user['id']]);
                
                // Сохраняем данные авторизации в сессии
                $session->set(ADMIN_SESSION_NAME, true);
                $session->set('admin_user_id', $user['id']);
                $session->set('admin_username', $user['username']);
                $session->set('admin_session_token', $sessionToken);
                
                // Регенеруємо ID сесії для безпеки
                Session::regenerate(true);
                
                // Редирект на dashboard
                Response::redirectStatic(UrlHelper::admin('dashboard'));
                exit;
            } else {
                $this->error = 'Невірний логін або пароль';
                error_log('Login failed - Invalid username or password for: ' . $username);
            }
        } catch (Exception $e) {
            $this->error = 'Помилка входу. Спробуйте пізніше.';
            if (function_exists('logger')) {
                logger()->logError('Login error', ['error' => $e->getMessage()]);
            } else {
                error_log("Login error: " . $e->getMessage());
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
        $isSecureConnection = $isHttps; // Передаем в шаблон
        
        // Логируем для диагностики
        error_log('Login render - Session ID: ' . Session::getId());
        error_log('Login render - CSRF token generated: ' . ($csrfToken ? 'yes (' . substr($csrfToken, 0, 10) . '...)' : 'no'));
        error_log('Login render - Cookie PHPSESSID: ' . (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : 'missing'));
        
        include __DIR__ . '/../templates/login.php';
    }
}
