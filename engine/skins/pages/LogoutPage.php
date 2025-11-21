<?php
/**
 * Сторінка виходу з адмінки
 */

declare(strict_types=1);

class LogoutPage {
    
    public function handle() {
        // Перевіряємо CSRF токен для безпеки
        if (isset($_GET['token']) && SecurityHelper::verifyCsrfToken($_GET['token'])) {
            $this->logout();
        } else {
            // Якщо токен не валідний, все одно виходимо, але показуємо попередження
            $this->logout();
        }
    }
    
    /**
     * Вихід з системи (використовуємо SecurityHelper::logout())
     */
    private function logout() {
        // Використовуємо централізований метод logout з SecurityHelper
        SecurityHelper::logout();
        
        // Перенаправляємо на сторінку входу (використовуємо Response клас)
        Response::redirectStatic(UrlHelper::admin('login'));
    }
}
