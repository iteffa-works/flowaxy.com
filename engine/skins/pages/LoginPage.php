<?php
/**
 * Сторінка входу в адмінку
 */

class LoginPage {
    private $db;
    private $error = '';
    
    public function __construct() {
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
        }
        
        // Рендеримо сторінку
        $this->render();
    }
    
    /**
     * Обробка входу
     */
    private function processLogin() {
        $request = Request::getInstance();
        $csrfToken = $request->post('csrf_token', '');
        
        if (!SecurityHelper::verifyCsrfToken($csrfToken)) {
            $this->error = 'Помилка безпеки. Спробуйте ще раз.';
            return;
        }
        
        $username = SecurityHelper::sanitizeInput($request->post('username', ''));
        $password = $request->post('password', '');
        
        if (empty($username) || empty($password)) {
            $this->error = 'Заповніть всі поля';
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Успішний вхід (використовуємо Session клас)
                Session::set(ADMIN_SESSION_NAME, true);
                Session::set('admin_user_id', $user['id']);
                Session::set('admin_username', $user['username']);
                
                // Регенеруємо ID сесії для безпеки
                Session::regenerate();
                
                Response::redirectStatic(UrlHelper::admin('dashboard'));
            } else {
                $this->error = 'Невірний логін або пароль';
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
        $error = $this->error;
        $csrfToken = SecurityHelper::csrfToken();
        
        include __DIR__ . '/../templates/login.php';
    }
}
