<?php
/**
 * Страница выхода из админки
 */

class LogoutPage {
    
    public function handle() {
        // Проверяем CSRF токен для безопасности
        if (isset($_GET['token']) && SecurityHelper::verifyCsrfToken($_GET['token'])) {
            $this->logout();
        } else {
            // Если токен не валиден, все равно выходим, но показываем предупреждение
            $this->logout();
        }
    }
    
    /**
     * Вихід з системи (використовуємо SessionManager та Response класи)
     */
    private function logout() {
        // Видаляємо дані сесії (використовуємо SessionManager)
        $session = sessionManager();
        $session->remove(ADMIN_SESSION_NAME);
        $session->remove('admin_user_id');
        $session->remove('admin_username');
        
        // Знищуємо сесію
        $session->destroy();
        
        // Перенаправляємо на сторінку входу (використовуємо Response клас)
        Response::redirectStatic(UrlHelper::admin('login'));
    }
}
