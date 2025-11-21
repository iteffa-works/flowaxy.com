<?php
/**
 * Страница управления ролями и правами доступа
 * 
 * @package Engine\Skins\Pages
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class RolesPage extends AdminPage {
    protected string $pageTitle = 'Ролі та права доступу - Flowaxy CMS';
    protected string $templateName = 'roles';
    
    private ?RoleManager $roleManager = null;
    
    public function __construct() {
        parent::__construct();
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
        if (Request::getMethod() === 'POST') {
            $action = Request::post('action', '');
            
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
        
        $name = Request::post('name', '');
        $slug = Request::post('slug', '');
        $description = Request::post('description', '');
        
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
        
        // TODO: Реализовать обновление роли
        $this->setMessage('Оновлення ролі буде реалізовано в наступній версії', 'info');
    }
    
    private function handleDeleteRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $roleId = (int)Request::post('role_id', 0);
        
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
        
        $roleId = (int)Request::post('role_id', 0);
        $permissionId = (int)Request::post('permission_id', 0);
        
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
        
        $roleId = (int)Request::post('role_id', 0);
        $permissionId = (int)Request::post('permission_id', 0);
        
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
        
        $userId = (int)Request::post('user_id', 0);
        $roleId = (int)Request::post('role_id', 0);
        
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
        
        $userId = (int)Request::post('user_id', 0);
        $roleId = (int)Request::post('role_id', 0);
        
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
        
        if ($this->roleManager) {
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
            
            // Для каждой роли получаем разрешения
            foreach ($data['roles'] as &$role) {
                $role['permissions'] = $this->roleManager->getRolePermissions($role['id']);
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

