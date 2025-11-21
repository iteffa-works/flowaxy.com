<?php
/**
 * Менеджер ролей та прав доступу
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

class RoleManager {
    private static ?self $instance = null;
    private $db;
    private array $userRolesCache = [];
    private array $userPermissionsCache = [];
    private array $rolePermissionsCache = [];
    
    private function __construct() {
        $this->db = DatabaseHelper::getConnection();
    }
    
    /**
     * Отримання екземпляра (Singleton)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Перевірка наявності дозволу у користувача
     */
    public function hasPermission(int $userId, string $permission): bool {
        // Перевіряємо кеш
        if (isset($this->userPermissionsCache[$userId])) {
            return in_array($permission, $this->userPermissionsCache[$userId]);
        }
        
        // Завантажуємо всі дозволи користувача
        $permissions = $this->getUserPermissions($userId);
        $this->userPermissionsCache[$userId] = $permissions;
        
        return in_array($permission, $permissions);
    }
    
    /**
     * Перевірка наявності ролі у користувача
     */
    public function hasRole(int $userId, string $roleSlug): bool {
        $roles = $this->getUserRoles($userId);
        foreach ($roles as $role) {
            if ($role['slug'] === $roleSlug) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Отримання всіх дозволів користувача
     */
    public function getUserPermissions(int $userId): array {
        try {
            // Отримуємо role_ids з users
            $stmt = $this->db->prepare("SELECT role_ids FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['role_ids'])) {
                return [];
            }
            
            $roleIds = json_decode($user['role_ids'], true);
            if (!is_array($roleIds) || empty($roleIds)) {
                return [];
            }
            
            // Отримуємо дозволи для всіх ролей користувача
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $stmt = $this->db->prepare("
                SELECT DISTINCT p.slug
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id IN ($placeholders)
            ");
            $stmt->execute($roleIds);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result ?: [];
        } catch (Exception $e) {
            error_log("RoleManager getUserPermissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Отримання всіх ролей користувача
     */
    public function getUserRoles(int $userId): array {
        // Проверяем кеш
        if (isset($this->userRolesCache[$userId])) {
            return $this->userRolesCache[$userId];
        }
        
        try {
            // Получаем role_ids из users
            $stmt = $this->db->prepare("SELECT role_ids FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['role_ids'])) {
                $this->userRolesCache[$userId] = [];
                return [];
            }
            
            $roleIds = json_decode($user['role_ids'], true);
            if (!is_array($roleIds) || empty($roleIds)) {
                $this->userRolesCache[$userId] = [];
                return [];
            }
            
            // Получаем роли по ID
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $stmt = $this->db->prepare("
                SELECT id, name, slug, description, is_system
                FROM roles
                WHERE id IN ($placeholders)
                ORDER BY name
            ");
            $stmt->execute($roleIds);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->userRolesCache[$userId] = $roles ?: [];
            return $this->userRolesCache[$userId];
        } catch (Exception $e) {
            error_log("RoleManager getUserRoles error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Назначение роли пользователю
     */
    public function assignRole(int $userId, int $roleId): bool {
        try {
            // Получаем текущие role_ids
            $stmt = $this->db->prepare("SELECT role_ids FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $roleIds = [];
            if ($user && !empty($user['role_ids'])) {
                $roleIds = json_decode($user['role_ids'], true) ?: [];
            }
            
            // Добавляем новую роль, если её ещё нет
            if (!in_array($roleId, $roleIds)) {
                $roleIds[] = $roleId;
                $roleIdsJson = json_encode($roleIds);
                
                $stmt = $this->db->prepare("UPDATE users SET role_ids = ? WHERE id = ?");
                $stmt->execute([$roleIdsJson, $userId]);
            }
            
            // Очищаем кеш
            unset($this->userRolesCache[$userId]);
            unset($this->userPermissionsCache[$userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("RoleManager assignRole error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление роли у пользователя
     */
    public function removeRole(int $userId, int $roleId): bool {
        try {
            // Получаем текущие role_ids
            $stmt = $this->db->prepare("SELECT role_ids FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['role_ids'])) {
                return true; // Ролей нет, ничего делать не нужно
            }
            
            $roleIds = json_decode($user['role_ids'], true) ?: [];
            
            // Удаляем роль из массива
            $roleIds = array_filter($roleIds, function($id) use ($roleId) {
                return (int)$id !== $roleId;
            });
            $roleIds = array_values($roleIds); // Переиндексируем массив
            
            $roleIdsJson = json_encode($roleIds);
            $stmt = $this->db->prepare("UPDATE users SET role_ids = ? WHERE id = ?");
            $stmt->execute([$roleIdsJson, $userId]);
            
            // Очищаем кеш
            unset($this->userRolesCache[$userId]);
            unset($this->userPermissionsCache[$userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("RoleManager removeRole error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение всех ролей
     */
    public function getAllRoles(): array {
        try {
            $stmt = $this->db->query("
                SELECT id, name, slug, description, is_system, created_at, updated_at
                FROM roles
                ORDER BY name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("RoleManager getAllRoles error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение всех разрешений
     */
    public function getAllPermissions(?string $category = null): array {
        try {
            $sql = "
                SELECT id, name, slug, description, category, created_at, updated_at
                FROM permissions
            ";
            
            if ($category !== null) {
                $sql .= " WHERE category = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$category]);
            } else {
                $stmt = $this->db->query($sql);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("RoleManager getAllPermissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Создание новой роли
     */
    public function createRole(string $name, string $slug, ?string $description = null, bool $isSystem = false): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO roles (name, slug, description, is_system)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $description, $isSystem ? 1 : 0]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("RoleManager createRole error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание нового разрешения
     */
    public function createPermission(string $name, string $slug, ?string $description = null, ?string $category = null): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO permissions (name, slug, description, category)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $description, $category]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("RoleManager createPermission error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Назначение разрешения роли
     */
    public function assignPermissionToRole(int $roleId, int $permissionId): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO role_permissions (role_id, permission_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$roleId, $permissionId]);
            
            // Очищаем кеш разрешений роли
            unset($this->rolePermissionsCache[$roleId]);
            
            return true;
        } catch (Exception $e) {
            error_log("RoleManager assignPermissionToRole error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление разрешения у роли
     */
    public function removePermissionFromRole(int $roleId, int $permissionId): bool {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM role_permissions
                WHERE role_id = ? AND permission_id = ?
            ");
            $stmt->execute([$roleId, $permissionId]);
            
            // Очищаем кеш разрешений роли
            unset($this->rolePermissionsCache[$roleId]);
            
            return true;
        } catch (Exception $e) {
            error_log("RoleManager removePermissionFromRole error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение разрешений роли
     */
    public function getRolePermissions(int $roleId): array {
        // Проверяем кеш
        if (isset($this->rolePermissionsCache[$roleId])) {
            return $this->rolePermissionsCache[$roleId];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.slug, p.description, p.category
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.category, p.name
            ");
            $stmt->execute([$roleId]);
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->rolePermissionsCache[$roleId] = $permissions ?: [];
            return $this->rolePermissionsCache[$roleId];
        } catch (Exception $e) {
            error_log("RoleManager getRolePermissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Удаление роли
     */
    public function deleteRole(int $roleId): bool {
        try {
            // Проверяем, не системная ли роль
            $stmt = $this->db->prepare("SELECT is_system, slug FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($role && !empty($role['is_system'])) {
                return false; // Нельзя удалять системные роли
            }
            
            // Дополнительная проверка: роль developer нельзя удалить даже если is_system = 0
            if ($role && isset($role['slug']) && $role['slug'] === 'developer') {
                return false; // Роль разработчика нельзя удалить
            }
            
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            
            // Очищаем кеш
            $this->userRolesCache = [];
            $this->userPermissionsCache = [];
            $this->rolePermissionsCache = [];
            
            return true;
        } catch (Exception $e) {
            error_log("RoleManager deleteRole error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Очистка кеша пользователя
     */
    public function clearUserCache(?int $userId = null): void {
        if ($userId !== null) {
            unset($this->userRolesCache[$userId]);
            unset($this->userPermissionsCache[$userId]);
        } else {
            $this->userRolesCache = [];
            $this->userPermissionsCache = [];
        }
    }
    
    /**
     * Очистка кеша ролей
     */
    public function clearRoleCache(?int $roleId = null): void {
        if ($roleId !== null) {
            unset($this->rolePermissionsCache[$roleId]);
        } else {
            $this->rolePermissionsCache = [];
        }
    }
}

