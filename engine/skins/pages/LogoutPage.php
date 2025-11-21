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
     * Вихід з системи (використовуємо SecurityHelper::logout())
     */
    private function logout() {
        // Используем централизованный метод logout из SecurityHelper
        SecurityHelper::logout();
        
        // Перенаправляємо на сторінку входу (використовуємо Response клас)
        Response::redirectStatic(UrlHelper::admin('login'));
    }
}
