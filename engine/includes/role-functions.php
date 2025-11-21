<?php
/**
 * Глобальні функції для роботи з ролями та правами
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

/**
 * Отримання екземпляра RoleManager
 */
if (!function_exists('roleManager')) {
    function roleManager(): RoleManager {
        return RoleManager::getInstance();
    }
}

/**
 * Перевірка дозволу у користувача
 */
if (!function_exists('user_can')) {
    function user_can(int $userId, string $permission): bool {
        return roleManager()->hasPermission($userId, $permission);
    }
}

/**
 * Перевірка ролі у користувача
 */
if (!function_exists('user_has_role')) {
    function user_has_role(int $userId, string $roleSlug): bool {
        return roleManager()->hasRole($userId, $roleSlug);
    }
}

/**
 * Перевірка дозволу у поточного користувача (для адмінки)
 */
if (!function_exists('current_user_can')) {
    function current_user_can(string $permission): bool {
        if (!class_exists('Session')) {
            return false;
        }

        $session = sessionManager();
        $userId = $session->get('admin_user_id');
        if (!$userId) {
            return false;
        }
        
        return user_can((int)$userId, $permission);
    }
}

/**
 * Отримання ID поточного авторизованого користувача (для публічної частини)
 */
if (!function_exists('auth_get_current_user_id')) {
    function auth_get_current_user_id(): ?int {
        if (!class_exists('Session')) {
            return null;
        }

        $session = sessionManager();
        $userId = $session->get('user_id');
        if (!$userId) {
            return null;
        }
        
        return (int)$userId;
    }
}

/**
 * Перевірка дозволу у поточного авторизованого користувача (для публічної частини)
 */
if (!function_exists('auth_user_can')) {
    function auth_user_can(string $permission): bool {
        $userId = auth_get_current_user_id();
        if (!$userId) {
            return false;
        }
        
        return user_can($userId, $permission);
    }
}

/**
 * Отримання всіх дозволів користувача
 */
if (!function_exists('user_permissions')) {
    function user_permissions(int $userId): array {
        return roleManager()->getUserPermissions($userId);
    }
}

/**
 * Отримання всіх ролей користувача
 */
if (!function_exists('user_roles')) {
    function user_roles(int $userId): array {
        return roleManager()->getUserRoles($userId);
    }
}

/**
 * Призначення ролі користувачу
 */
if (!function_exists('assign_role')) {
    function assign_role(int $userId, int $roleId): bool {
        return roleManager()->assignRole($userId, $roleId);
    }
}

/**
 * Видалення ролі у користувача
 */
if (!function_exists('remove_role')) {
    function remove_role(int $userId, int $roleId): bool {
        return roleManager()->removeRole($userId, $roleId);
    }
}

