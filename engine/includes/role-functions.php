<?php
/**
 * Глобальные функции для работы с ролями и правами
 * 
 * @package Engine\Includes
 */

/**
 * Получение экземпляра RoleManager
 */
if (!function_exists('roleManager')) {
    function roleManager(): RoleManager {
        return RoleManager::getInstance();
    }
}

/**
 * Проверка разрешения у пользователя
 */
if (!function_exists('user_can')) {
    function user_can(int $userId, string $permission): bool {
        return roleManager()->hasPermission($userId, $permission);
    }
}

/**
 * Проверка роли у пользователя
 */
if (!function_exists('user_has_role')) {
    function user_has_role(int $userId, string $roleSlug): bool {
        return roleManager()->hasRole($userId, $roleSlug);
    }
}

/**
 * Проверка разрешения у текущего пользователя (для админки)
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
 * Проверка разрешения у текущего авторизованного пользователя (для публичной части)
 */
if (!function_exists('auth_user_can')) {
    function auth_user_can(string $permission): bool {
        if (!function_exists('auth_get_current_user_id')) {
            return false;
        }
        
        $userId = auth_get_current_user_id();
        if (!$userId) {
            return false;
        }
        
        return user_can($userId, $permission);
    }
}

/**
 * Получение всех разрешений пользователя
 */
if (!function_exists('user_permissions')) {
    function user_permissions(int $userId): array {
        return roleManager()->getUserPermissions($userId);
    }
}

/**
 * Получение всех ролей пользователя
 */
if (!function_exists('user_roles')) {
    function user_roles(int $userId): array {
        return roleManager()->getUserRoles($userId);
    }
}

/**
 * Назначение роли пользователю
 */
if (!function_exists('assign_role')) {
    function assign_role(int $userId, int $roleId): bool {
        return roleManager()->assignRole($userId, $roleId);
    }
}

/**
 * Удаление роли у пользователя
 */
if (!function_exists('remove_role')) {
    function remove_role(int $userId, int $roleId): bool {
        return roleManager()->removeRole($userId, $roleId);
    }
}

