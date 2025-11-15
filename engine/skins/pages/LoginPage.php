<?php
/**
 * Страница входа в админку
 */

class LoginPage {
    private $db;
    private $error = '';
    
    public function __construct() {
        // Если уже авторизован, перенаправляем
        if (isAdminLoggedIn()) {
            header('Location: ' . adminUrl('dashboard'));
            exit;
        }
        
        $this->db = getDB();
    }
    
    public function handle() {
        // Обработка формы входа
        if ($_POST) {
            $this->processLogin();
        }
        
        // Рендерим страницу
        $this->render();
    }
    
    /**
     * Обработка входа
     */
    private function processLogin() {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->error = 'Помилка безпеки. Спробуйте ще раз.';
            return;
        }
        
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->error = 'Заповніть всі поля';
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Успешный вход
                $_SESSION[ADMIN_SESSION_NAME] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                header('Location: ' . adminUrl('dashboard'));
                exit;
            } else {
                $this->error = 'Невірний логін або пароль';
            }
        } catch (Exception $e) {
            $this->error = 'Помилка входу. Спробуйте пізніше.';
            error_log("Login error: " . $e->getMessage());
        }
    }
    
    /**
     * Рендеринг страницы
     */
    private function render() {
        $error = $this->error;
        $csrfToken = generateCSRFToken();
        
        include __DIR__ . '/../templates/login.php';
    }
}
