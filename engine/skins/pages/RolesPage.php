<?php
/**
 * Страница управления ролями и правами доступа
 * 
 * @package Engine\Skins\Pages
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class RolesPage extends AdminPage {
    protected $templateName = 'roles';
    
    private ?RoleManager $roleManager = null;
    
    public function __construct() {
        parent::__construct();
        $this->pageTitle = 'Ролі та права доступу - Flowaxy CMS';
        $this->setPageHeader(
            'Ролі та права доступу',
            'Управління ролями та правами доступу користувачів',
            'fas fa-user-shield'
        );
        
        if (class_exists('RoleManager')) {
            $this->roleManager = RoleManager::getInstance();
        }
    }
    
    public function handle(): void {
        $request = Request::getInstance();
        
        if ($request->method() === 'POST') {
            $action = $request->post('action', '');
            
            switch ($action) {
                case 'create_role':
                    $this->handleCreateRole();
                    break;
                case 'update_role':
                    $this->handleUpdateRole();
                    break;
                case 'delete_role':
                    $this->handleDeleteRole();
                    break;
                case 'assign_permission':
                    $this->handleAssignPermission();
                    break;
                case 'remove_permission':
                    $this->handleRemovePermission();
                    break;
                case 'assign_user_role':
                    $this->handleAssignUserRole();
                    break;
                case 'remove_user_role':
                    $this->handleRemoveUserRole();
                    break;
            }
        }
        
        // Проверяем, нужна ли страница редактирования
        $editId = (int)$request->query('edit', 0);
        if ($editId > 0) {
            $this->templateName = 'role-edit';
        }
        
        $this->render();
    }
    
    private function handleCreateRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $name = $request->post('name', '');
        $slug = $request->post('slug', '');
        $description = $request->post('description', '');
        
        if (empty($name) || empty($slug)) {
            $this->setMessage('Назва та slug ролі обов\'язкові', 'danger');
            return;
        }
        
        $roleId = $this->roleManager->createRole($name, $slug, $description);
        
        if ($roleId) {
            $this->setMessage('Роль успішно створена', 'success');
        } else {
            $this->setMessage('Помилка при створенні ролі', 'danger');
        }
    }
    
    private function handleUpdateRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $roleId = (int)$request->post('role_id', 0);
        if ($roleId <= 0) {
            $this->setMessage('Невірний ID ролі', 'danger');
            return;
        }
        
        $name = $request->post('name', '');
        $description = $request->post('description', '');
        $isDefault = $request->post('is_default', 0) ? 1 : 0;
        $permissions = $request->post('permissions', []);
        
        if (empty($name)) {
            $this->setMessage('Назва ролі обов\'язкова', 'danger');
            return;
        }
        
        // Обновляем роль
        try {
            $stmt = $this->db->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $roleId]);
            
            // Удаляем все разрешения роли
            $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            
            // Добавляем новые разрешения
            if (!empty($permissions) && is_array($permissions)) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissions as $permissionId) {
                    $permissionId = (int)$permissionId;
                    if ($permissionId > 0) {
                        $stmt->execute([$roleId, $permissionId]);
                    }
                }
            }
            
            // Очищаем кеш роли через публичный метод RoleManager
            if ($this->roleManager) {
                $this->roleManager->clearRoleCache($roleId);
            }
            
            $this->setMessage('Роль успішно оновлена', 'success');
            Response::redirectStatic(UrlHelper::admin('roles'));
        } catch (Exception $e) {
            error_log("RolesPage handleUpdateRole error: " . $e->getMessage());
            $this->setMessage('Помилка при оновленні ролі', 'danger');
        }
    }
    
    private function handleDeleteRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $roleId = (int)$request->post('role_id', 0);
        
        if ($roleId <= 0) {
            $this->setMessage('Невірний ID ролі', 'danger');
            return;
        }
        
        $result = $this->roleManager->deleteRole($roleId);
        
        if ($result) {
            $this->setMessage('Роль успішно видалена', 'success');
        } else {
            $this->setMessage('Помилка при видаленні ролі. Можливо, це системна роль', 'danger');
        }
    }
    
    private function handleAssignPermission(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $roleId = (int)$request->post('role_id', 0);
        $permissionId = (int)$request->post('permission_id', 0);
        
        if ($roleId <= 0 || $permissionId <= 0) {
            $this->setMessage('Невірні параметри', 'danger');
            return;
        }
        
        $result = $this->roleManager->assignPermissionToRole($roleId, $permissionId);
        
        if ($result) {
            $this->setMessage('Дозвіл успішно призначено', 'success');
        } else {
            $this->setMessage('Помилка при призначенні дозволу', 'danger');
        }
    }
    
    private function handleRemovePermission(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $roleId = (int)$request->post('role_id', 0);
        $permissionId = (int)$request->post('permission_id', 0);
        
        if ($roleId <= 0 || $permissionId <= 0) {
            $this->setMessage('Невірні параметри', 'danger');
            return;
        }
        
        $result = $this->roleManager->removePermissionFromRole($roleId, $permissionId);
        
        if ($result) {
            $this->setMessage('Дозвіл успішно видалено', 'success');
        } else {
            $this->setMessage('Помилка при видаленні дозволу', 'danger');
        }
    }
    
    private function handleAssignUserRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $userId = (int)$request->post('user_id', 0);
        $roleId = (int)$request->post('role_id', 0);
        
        if ($userId <= 0 || $roleId <= 0) {
            $this->setMessage('Невірні параметри', 'danger');
            return;
        }
        
        $result = $this->roleManager->assignRole($userId, $roleId);
        
        if ($result) {
            $this->setMessage('Роль успішно призначено користувачу', 'success');
        } else {
            $this->setMessage('Помилка при призначенні ролі', 'danger');
        }
    }
    
    private function handleRemoveUserRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $userId = (int)$request->post('user_id', 0);
        $roleId = (int)$request->post('role_id', 0);
        
        if ($userId <= 0 || $roleId <= 0) {
            $this->setMessage('Невірні параметри', 'danger');
            return;
        }
        
        $result = $this->roleManager->removeRole($userId, $roleId);
        
        if ($result) {
            $this->setMessage('Роль успішно видалено у користувача', 'success');
        } else {
            $this->setMessage('Помилка при видаленні ролі', 'danger');
        }
    }
    
    protected function getTemplateData(): array {
        $data = parent::getTemplateData();
        
        // Проверяем, нужна ли страница редактирования
        $request = Request::getInstance();
        $editId = (int)$request->query('edit', 0);
        
        if ($this->roleManager) {
            if ($editId > 0) {
                // Страница редактирования роли
                try {
                    $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = ?");
                    $stmt->execute([$editId]);
                    $data['role'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$data['role']) {
                        $this->setMessage('Роль не знайдена', 'danger');
                        Response::redirectStatic(UrlHelper::admin('roles'));
                    }
                    
                    // Получаем создателя роли, если поле created_by существует
                    if (!empty($data['role']['created_by'])) {
                        try {
                            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
                            $stmt->execute([$data['role']['created_by']]);
                            $creator = $stmt->fetch(PDO::FETCH_ASSOC);
                            $data['role']['created_by_username'] = $creator['username'] ?? null;
                        } catch (Exception $e) {
                            $data['role']['created_by_username'] = null;
                        }
                    }
                    
                    // Получаем разрешения роли
                    $data['role']['permissions'] = $this->roleManager->getRolePermissions($editId);
                    $rolePermissionIds = array_column($data['role']['permissions'], 'id');
                    $data['role']['permission_ids'] = $rolePermissionIds;
                    
                    // Получаем все разрешения сгруппированные по категориям
                    $data['permissions'] = $this->roleManager->getAllPermissions();
                    $data['permissionsByCategory'] = $this->groupPermissionsByCategory($data['permissions']);
                } catch (Exception $e) {
                    error_log("RolesPage getTemplateData error: " . $e->getMessage());
                    $data['role'] = null;
                } catch (Exception $e) {
                    error_log("RolesPage getTemplateData error: " . $e->getMessage());
                    $data['role'] = null;
                }
            } else {
                // Список ролей
                $data['roles'] = $this->roleManager->getAllRoles();
                $data['permissions'] = $this->roleManager->getAllPermissions();
                $data['permissionsByCategory'] = $this->groupPermissionsByCategory($data['permissions']);
                
                // Получаем пользователей для назначения ролей
                try {
                    $stmt = $this->db->query("SELECT id, username, email FROM users ORDER BY username");
                    $data['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Exception $e) {
                    $data['users'] = [];
                }
                
                // Для каждой роли получаем разрешения и создателя
                foreach ($data['roles'] as &$role) {
                    $role['permissions'] = $this->roleManager->getRolePermissions($role['id']);
                    
                    // Получаем создателя роли, если поле created_by существует
                    if (isset($role['created_by']) && !empty($role['created_by'])) {
                        try {
                            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
                            $stmt->execute([$role['created_by']]);
                            $creator = $stmt->fetch(PDO::FETCH_ASSOC);
                            $role['created_by_username'] = $creator['username'] ?? null;
                        } catch (Exception $e) {
                            $role['created_by_username'] = null;
                        }
                    } else {
                        $role['created_by_username'] = null;
                    }
                }
            }
        } else {
            $data['roles'] = [];
            $data['permissions'] = [];
            $data['permissionsByCategory'] = [];
            $data['users'] = [];
        }
        
        return $data;
    }
    
    private function groupPermissionsByCategory(array $permissions): array {
        $grouped = [];
        foreach ($permissions as $permission) {
            $category = $permission['category'] ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        return $grouped;
    }
}

