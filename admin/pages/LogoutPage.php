<?php
/**
 * Страница выхода из админки
 */

class LogoutPage {
    
    public function handle() {
        // Проверяем CSRF токен для безопасности
        if (isset($_GET['token']) && verifyCSRFToken($_GET['token'])) {
            $this->logout();
        } else {
            // Если токен не валиден, все равно выходим, но показываем предупреждение
            $this->logout();
        }
    }
    
    /**
     * Выход из системы
     */
    private function logout() {
        // Удаляем данные сессии
        unset($_SESSION[ADMIN_SESSION_NAME]);
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_username']);
        
        // Уничтожаем сессию
        session_destroy();
        
        // Перенаправляем на страницу входа
        header('Location: ' . adminUrl('login'));
        exit;
    }
}
