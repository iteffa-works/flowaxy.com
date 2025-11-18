<?php
/**
 * Страница профиля пользователя
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class ProfilePage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Профіль користувача - Flowaxy CMS';
        $this->templateName = 'profile';
        
        $this->setPageHeader(
            'Профіль користувача',
            'Зміна логіну, email та пароля',
            'fas fa-user'
        );
    }
    
    public function handle() {
        // Обработка сохранения
        if ($_POST && isset($_POST['save_profile'])) {
            $this->saveProfile();
        }
        
        // Получение данных пользователя
        $user = $this->getCurrentUser();
        
        // Рендерим страницу
        $this->render([
            'user' => $user
        ]);
    }
    
    /**
     * Получение текущего пользователя
     */
    private function getCurrentUser() {
        // Використовуємо Session клас
        $userId = Session::get('admin_user_id');
        
        if (!$userId) {
            $this->setMessage('Користувач не знайдено', 'danger');
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->setMessage('Користувач не знайдено', 'danger');
                return null;
            }
            
            return $user;
        } catch (Exception $e) {
            error_log("Error getting user: " . $e->getMessage());
            $this->setMessage('Помилка завантаження даних користувача', 'danger');
            return null;
        }
    }
    
    /**
     * Сохранение профиля
     */
    private function saveProfile() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Використовуємо Session клас
        $userId = Session::get('admin_user_id');
        if (!$userId) {
            $this->setMessage('Користувач не знайдено', 'danger');
            return;
        }
        
        $username = SecurityHelper::sanitizeInput($_POST['username'] ?? '');
        $email = SecurityHelper::sanitizeInput($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Валидация
        $validation = AdminValidator::validate([
            'username' => $username,
            'email' => $email
        ], [
            'username' => 'required|string|min:3|max:50',
            'email' => 'email|max:255'
        ]);
        
        if (!$validation['valid']) {
            $firstError = reset($validation['errors']);
            $this->setMessage($firstError, 'danger');
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
        
        // Если меняется пароль, проверяем старый
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $this->setMessage('Введіть поточний пароль для зміни', 'danger');
                return;
            }
            
            // Проверяем текущий пароль
            try {
                $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    $this->setMessage('Невірний поточний пароль', 'danger');
                    return;
                }
            } catch (Exception $e) {
                error_log("Error verifying password: " . $e->getMessage());
                $this->setMessage('Помилка перевірки пароля', 'danger');
                return;
            }
            
            // Проверяем длину нового пароля
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                $this->setMessage('Пароль повинен містити мінімум ' . PASSWORD_MIN_LENGTH . ' символів', 'danger');
                return;
            }
            
            // Проверяем совпадение паролей
            if ($newPassword !== $confirmPassword) {
                $this->setMessage('Нові паролі не співпадають', 'danger');
                return;
            }
        }
        
        // Сохраняем изменения
        try {
            $this->db->beginTransaction();
            
            if (!empty($newPassword)) {
                // Обновляем username, email и password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$username, $email, $hashedPassword, $userId]);
                
                // Оновлюємо сесію (використовуємо Session клас)
                Session::set('admin_username', $username);
            } else {
                // Оновлюємо тільки username і email
                $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$username, $email, $userId]);
                
                // Оновлюємо сесію (використовуємо Session клас)
                Session::set('admin_username', $username);
            }
            
            $this->db->commit();
            $this->setMessage('Профіль успішно оновлено', 'success');
            
            // Перезагружаем данные пользователя
            $user = $this->getCurrentUser();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error saving profile: " . $e->getMessage());
            $this->setMessage('Помилка при збереженні профілю', 'danger');
        }
    }
}

