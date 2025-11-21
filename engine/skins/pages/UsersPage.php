<?php
/**
 * Страница управления пользователями
 * 
 * @package Engine\Skins\Pages
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class UsersPage extends AdminPage {
    protected $templateName = 'users';
    
    private ?RoleManager $roleManager = null;
    
    public function __construct() {
        parent::__construct();
        $this->pageTitle = 'Користувачі - Flowaxy CMS';
        $this->setPageHeader(
            'Користувачі',
            'Управління користувачами системи',
            'fas fa-users'
        );
        
        if (class_exists('RoleManager')) {
            $this->roleManager = RoleManager::getInstance();
        }
    }
    
    public function handle(): void {
        if (Request::getMethod() === 'POST') {
            $action = Request::post('action', '');
            
            switch ($action) {
                case 'create_user':
                    $this->handleCreateUser();
                    break;
                case 'update_user':
                    $this->handleUpdateUser();
                    break;
                case 'delete_user':
                    $this->handleDeleteUser();
                    break;
                case 'change_password':
                    $this->handleChangePassword();
                    break;
            }
        }
        
        $this->render();
    }
    
    private function handleCreateUser(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $username = SecurityHelper::sanitizeInput(Request::post('username', ''));
        $email = SecurityHelper::sanitizeInput(Request::post('email', ''));
        $password = Request::post('password', '');
        $passwordConfirm = Request::post('password_confirm', '');
        
        // Валидация
        if (!Validator::validateString($username, 3, 50)) {
            $this->setMessage('Логін має містити від 3 до 50 символів', 'danger');
            return;
        }
        
        if (!empty($email) && !Validator::validateEmail($email)) {
            $this->setMessage('Невірний формат email', 'danger');
            return;
        }
        
        if (empty($password) || strlen($password) < 6) {
            $this->setMessage('Пароль має містити мінімум 6 символів', 'danger');
            return;
        }
        
        if ($password !== $passwordConfirm) {
            $this->setMessage('Паролі не співпадають', 'danger');
            return;
        }
        
        // Проверка уникальности username
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $this->setMessage('Користувач з таким логіном вже існує', 'danger');
                return;
            }
        } catch (Exception $e) {
            error_log("Error checking username: " . $e->getMessage());
            $this->setMessage('Помилка перевірки логіну', 'danger');
            return;
        }
        
        // Создание пользователя
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$username, $email, $hashedPassword]);
            $userId = (int)$this->db->lastInsertId();
            
            $this->setMessage('Користувач успішно створений', 'success');
            $this->redirect('users');
            exit;
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            $this->setMessage('Помилка при створенні користувача', 'danger');
        }
    }
    
    private function handleUpdateUser(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $userId = (int)Request::post('user_id', 0);
        $username = SecurityHelper::sanitizeInput(Request::post('username', ''));
        $email = SecurityHelper::sanitizeInput(Request::post('email', ''));
        
        if ($userId <= 0) {
            $this->setMessage('Невірний ID користувача', 'danger');
            return;
        }
        
        // Защита от удаления первого пользователя
        if ($userId === 1) {
            $this->setMessage('Неможливо редагувати першого користувача', 'danger');
            return;
        }
        
        // Валидация
        if (!Validator::validateString($username, 3, 50)) {
            $this->setMessage('Логін має містити від 3 до 50 символів', 'danger');
            return;
        }
        
        if (!empty($email) && !Validator::validateEmail($email)) {
            $this->setMessage('Невірний формат email', 'danger');
            return;
        }
        
        // Проверка уникальности username
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                $this->setMessage('Користувач з таким логіном вже існує', 'danger');
                return;
            }
        } catch (Exception $e) {
            error_log("Error checking username: " . $e->getMessage());
            $this->setMessage('Помилка перевірки логіну', 'danger');
            return;
        }
        
        // Обновление пользователя
        try {
            $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$username, $email, $userId]);
            
            $this->setMessage('Користувач успішно оновлений', 'success');
            $this->redirect('users');
            exit;
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            $this->setMessage('Помилка при оновленні користувача', 'danger');
        }
    }
    
    private function handleDeleteUser(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $userId = (int)Request::post('user_id', 0);
        
        if ($userId <= 0) {
            $this->setMessage('Невірний ID користувача', 'danger');
            return;
        }
        
        // Защита от удаления первого пользователя
        if ($userId === 1) {
            $this->setMessage('Неможливо видалити першого користувача', 'danger');
            return;
        }
        
        // Нельзя удалять самого себя
        $currentUserId = (int)Session::get('admin_user_id');
        if ($userId === $currentUserId) {
            $this->setMessage('Неможливо видалити себе', 'danger');
            return;
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->setMessage('Користувач успішно видалений', 'success');
            $this->redirect('users');
            exit;
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            $this->setMessage('Помилка при видаленні користувача', 'danger');
        }
    }
    
    private function handleChangePassword(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $userId = (int)Request::post('user_id', 0);
        $newPassword = Request::post('new_password', '');
        $passwordConfirm = Request::post('password_confirm', '');
        
        if ($userId <= 0) {
            $this->setMessage('Невірний ID користувача', 'danger');
            return;
        }
        
        if (empty($newPassword) || strlen($newPassword) < 6) {
            $this->setMessage('Пароль має містити мінімум 6 символів', 'danger');
            return;
        }
        
        if ($newPassword !== $passwordConfirm) {
            $this->setMessage('Паролі не співпадають', 'danger');
            return;
        }
        
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $this->setMessage('Пароль успішно змінено', 'success');
            $this->redirect('users');
            exit;
        } catch (Exception $e) {
            error_log("Error changing password: " . $e->getMessage());
            $this->setMessage('Помилка при зміні пароля', 'danger');
        }
    }
    
    protected function getTemplateData(): array {
        $data = parent::getTemplateData();
        
        // Получаем всех пользователей
        try {
            $stmt = $this->db->query("SELECT id, username, email, created_at, updated_at FROM users ORDER BY username");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Получаем роли для каждого пользователя
            if ($this->roleManager) {
                foreach ($users as &$user) {
                    $user['roles'] = $this->roleManager->getUserRoles($user['id']);
                }
            } else {
                foreach ($users as &$user) {
                    $user['roles'] = [];
                }
            }
            
            $data['users'] = $users;
        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            $data['users'] = [];
        }
        
        // Получаем все роли для назначения
        if ($this->roleManager) {
            $data['roles'] = $this->roleManager->getAllRoles();
        } else {
            $data['roles'] = [];
        }
        
        return $data;
    }
}

