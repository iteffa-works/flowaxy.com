<?php
/**
 * Сторінка управління користувачами
 * 
 * @package Engine\Skins\Pages
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class UsersPage extends AdminPage {
    protected string $templateName = 'users';
    
    private ?RoleManager $roleManager = null;
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.view')) {
            Response::redirectStatic(UrlHelper::admin('dashboard'));
            exit;
        }
        
        $this->pageTitle = 'Користувачі - Flowaxy CMS';
        
        // Кнопка створення користувача (тільки якщо є право на створення)
        $headerButtons = '';
        if (function_exists('current_user_can') && current_user_can('admin.users.create')) {
            $headerButtons = $this->createButtonGroup([
                [
                    'text' => 'Створити користувача',
                    'type' => 'outline-secondary',
                    'options' => [
                        'attributes' => [
                            'class' => 'btn-sm',
                            'data-bs-toggle' => 'modal',
                            'data-bs-target' => '#createUserModal'
                        ]
                    ]
                ]
            ]);
        }
        
        $this->setPageHeader(
            'Користувачі',
            'Управління користувачами системи',
            'fas fa-users',
            $headerButtons
        );
        
        if (class_exists('RoleManager')) {
            $this->roleManager = RoleManager::getInstance();
        }
    }
    
    public function handle(): void {
        $request = Request::getInstance();
        
        // Обработка AJAX запроса для получения ролей пользователя
        if ($request->getMethod() === 'GET' && $request->query('action') === 'get_user_roles') {
            $this->handleGetUserRoles();
            return;
        }
        
        if ($request->getMethod() === 'POST') {
            $action = $request->post('action', '');
            
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
                case 'manage_user_roles':
                    $this->handleManageUserRoles();
                    break;
            }
        }
        
        $this->render();
    }
    
    private function handleCreateUser(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.create')) {
            $this->setMessage('У вас немає прав на створення користувачів', 'danger');
            return;
        }
        
        $username = SecurityHelper::sanitizeInput(Request::post('username', ''));
        $email = SecurityHelper::sanitizeInput(Request::post('email', ''));
        $password = Request::post('password', '');
        $passwordConfirm = Request::post('password_confirm', '');
        
        // Валідація
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
        
        // Перевірка унікальності username
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
        
        // Створення користувача
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$username, $email, $hashedPassword]);
            $userId = (int)$this->db->lastInsertId();
            
            // Автоматически назначаем роль "Гость" новому пользователю
            if ($this->roleManager) {
                try {
                    // Ищем роль "Гость" по slug 'guest'
                    $stmt = $this->db->prepare("SELECT id FROM roles WHERE slug = 'guest' LIMIT 1");
                    $stmt->execute();
                    $guestRole = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($guestRole && isset($guestRole['id'])) {
                        $this->roleManager->assignRole($userId, (int)$guestRole['id']);
                    }
                } catch (Exception $e) {
                    // Логируем ошибку, но не прерываем создание пользователя
                    error_log("Error assigning guest role to user: " . $e->getMessage());
                }
            }
            
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.edit')) {
            $this->setMessage('У вас немає прав на редагування користувачів', 'danger');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.delete')) {
            $this->setMessage('У вас немає прав на видалення користувачів', 'danger');
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
        $session = sessionManager();
        $currentUserId = (int)$session->get('admin_user_id');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.password')) {
            $this->setMessage('У вас немає прав на зміну паролів користувачів', 'danger');
            return;
        }
        
        $userId = (int)Request::post('user_id', 0);
        $newPassword = Request::post('new_password', '');
        $passwordConfirm = Request::post('password_confirm', '');
        
        if ($userId <= 0) {
            $this->setMessage('Невірний ID користувача', 'danger');
            return;
        }
        
        // Защита: только сам разработчик может менять свой пароль
        $session = sessionManager();
        $currentUserId = (int)$session->get('admin_user_id');
        if ($userId === 1 && $currentUserId !== 1) {
            $this->setMessage('Неможливо змінити пароль розробника', 'danger');
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
    
    private function handleGetUserRoles(): void {
        $request = Request::getInstance();
        $userId = (int)$request->query('user_id', 0);
        
        if ($userId <= 0) {
            Response::jsonResponse(['success' => false, 'error' => 'Невірний ID користувача'], 400);
            return;
        }
        
        if (!$this->roleManager) {
            Response::jsonResponse(['success' => false, 'error' => 'RoleManager не доступний'], 500);
            return;
        }
        
        try {
            $userRoles = $this->roleManager->getUserRoles($userId);
            $roleIds = array_column($userRoles, 'id');
            
            Response::jsonResponse([
                'success' => true,
                'roles' => $roleIds
            ]);
        } catch (Exception $e) {
            error_log("Error getting user roles: " . $e->getMessage());
            Response::jsonResponse(['success' => false, 'error' => 'Помилка отримання ролей'], 500);
        }
    }
    
    private function handleManageUserRoles(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.roles')) {
            $this->setMessage('У вас немає прав на управління ролями користувачів', 'danger');
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $userId = (int)$request->post('user_id', 0);
        $roleIds = $request->post('role_ids', []);
        
        if ($userId <= 0) {
            $this->setMessage('Невірний ID користувача', 'danger');
            return;
        }
        
        // Защита от изменения ролей первого пользователя
        if ($userId === 1) {
            $this->setMessage('Неможливо змінити ролі першого користувача', 'danger');
            return;
        }
        
        try {
            // Получаем текущие роли пользователя
            $currentRoles = $this->roleManager->getUserRoles($userId);
            $currentRoleIds = array_column($currentRoles, 'id');
            
            // Преобразуем role_ids в массив целых чисел
            $newRoleIds = [];
            if (!empty($roleIds) && is_array($roleIds)) {
                foreach ($roleIds as $roleId) {
                    $roleId = (int)$roleId;
                    if ($roleId > 0) {
                        $newRoleIds[] = $roleId;
                    }
                }
            }
            
            // Удаляем роли, которые не выбраны
            foreach ($currentRoleIds as $currentRoleId) {
                if (!in_array($currentRoleId, $newRoleIds)) {
                    $this->roleManager->removeRole($userId, $currentRoleId);
                }
            }
            
            // Добавляем новые роли
            foreach ($newRoleIds as $newRoleId) {
                if (!in_array($newRoleId, $currentRoleIds)) {
                    $this->roleManager->assignRole($userId, $newRoleId);
                }
            }
            
            $this->setMessage('Ролі користувача успішно оновлені', 'success');
            $this->redirect('users');
            exit;
        } catch (Exception $e) {
            error_log("Error managing user roles: " . $e->getMessage());
            $this->setMessage('Помилка при оновленні ролей користувача', 'danger');
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

