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
     * Вихід з системи (використовуємо Session та Response класи)
     */
    private function logout() {
        // Видаляємо дані сесії (використовуємо Session клас)
        Session::remove(ADMIN_SESSION_NAME);
        Session::remove('admin_user_id');
        Session::remove('admin_username');
        
        // Знищуємо сесію
        Session::destroy();
        
        // Перенаправляємо на сторінку входу (використовуємо Response клас)
        Response::redirectStatic(UrlHelper::admin('login'));
    }
}
