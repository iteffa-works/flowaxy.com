<?php
/**
 * Сторінка управління ролями та правами доступу
 * 
 * @package Engine\Skins\Pages
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class RolesPage extends AdminPage {
    protected string $templateName = 'roles';
    
    private ?RoleManager $roleManager = null;
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.view')) {
            Response::redirectStatic(UrlHelper::admin('dashboard'));
            exit;
        }
        
        $this->pageTitle = 'Ролі та права доступу - Flowaxy CMS';
        
        // Підключаємо зовнішні CSS та JS файли
        $this->additionalCSS[] = UrlHelper::admin('assets/styles/roles.css') . '?v=' . time();
        $this->additionalJS[] = UrlHelper::admin('assets/scripts/roles.js') . '?v=' . time();
        
        // Кнопка створення ролі (тільки якщо є право на створення)
        $headerButtons = '';
        if (function_exists('current_user_can') && current_user_can('admin.roles.create')) {
            $headerButtons = $this->createButtonGroup([
                [
                    'text' => 'Створити роль',
                    'type' => 'outline-secondary',
                    'options' => [
                        'url' => UrlHelper::admin('roles?action=create'),
                        'attributes' => [
                            'class' => 'btn-sm'
                        ]
                    ]
                ]
            ]);
        }
        
        $this->setPageHeader(
            'Ролі та права доступу',
            'Управління ролями та правами доступу користувачів',
            'fas fa-user-shield',
            $headerButtons
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
                case 'bulk_delete_roles':
                    $this->handleBulkDeleteRoles();
                    break;
            }
        }
        
        // Перевіряємо, чи потрібна сторінка редагування або створення
        $editId = (int)$request->query('edit', 0);
        $action = $request->query('action', '');
        
        if ($editId > 0 || $action === 'create') {
            $this->templateName = 'role-edit';
            
            // Обновляем заголовок страницы в зависимости от режима
            if ($action === 'create') {
                $this->setPageHeader(
                    'Створити роль',
                    'Додавання нової ролі до системи',
                    'fas fa-user-shield',
                    $this->pageHeaderButtons
                );
            }
        }
        
        $this->render();
    }
    
    private function handleCreateRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.create')) {
            $this->setMessage('У вас немає прав на створення ролей', 'danger');
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
            
            // Если указан save_and_exit, перенаправляем на список ролей
            $saveAndExit = $request->post('save_and_exit', 0);
            if ($saveAndExit) {
                Response::redirectStatic(UrlHelper::admin('roles'));
            } else {
                // Иначе перенаправляем на редактирование созданной роли
                Response::redirectStatic(UrlHelper::admin('roles?edit=' . $roleId));
            }
        } else {
            $this->setMessage('Помилка при створенні ролі', 'danger');
        }
    }
    
    private function handleUpdateRole(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.edit')) {
            $this->setMessage('У вас немає прав на редагування ролей', 'danger');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.delete')) {
            $this->setMessage('У вас немає прав на видалення ролей', 'danger');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.permissions')) {
            $this->setMessage('У вас немає прав на управління дозволами', 'danger');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.permissions')) {
            $this->setMessage('У вас немає прав на управління дозволами', 'danger');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.roles')) {
            $this->setMessage('У вас немає прав на призначення ролей користувачам', 'danger');
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
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.users.roles')) {
            $this->setMessage('У вас немає прав на видалення ролей у користувачів', 'danger');
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
    
    private function handleBulkDeleteRoles(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.roles.delete')) {
            $this->setMessage('У вас немає прав на видалення ролей', 'danger');
            return;
        }
        
        if (!$this->roleManager) {
            $this->setMessage('Помилка: RoleManager не доступний', 'danger');
            return;
        }
        
        $request = Request::getInstance();
        $roleIds = $request->post('role_ids', []);
        
        if (empty($roleIds) || !is_array($roleIds)) {
            $this->setMessage('Не вибрано ролей для видалення', 'danger');
            return;
        }
        
        $deletedCount = 0;
        $errorCount = 0;
        
        foreach ($roleIds as $roleId) {
            $roleId = (int)$roleId;
            if ($roleId <= 0) {
                continue;
            }
            
            // Перевіряємо, чи це не системна роль
            try {
                $stmt = $this->db->prepare("SELECT is_system, slug FROM roles WHERE id = ?");
                $stmt->execute([$roleId]);
                $role = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($role && (empty($role['is_system']) && $role['slug'] !== 'developer')) {
                    $result = $this->roleManager->deleteRole($roleId);
                    if ($result) {
                        $deletedCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        if ($deletedCount > 0) {
            $this->setMessage("Успішно видалено {$deletedCount} ролей" . ($errorCount > 0 ? ". Помилок: {$errorCount}" : ''), 'success');
        } else {
            $this->setMessage('Не вдалося видалити ролі. Можливо, це системні ролі', 'danger');
        }
    }
    
    protected function getTemplateData(): array {
        $data = parent::getTemplateData();
        
        // Проверяем, нужна ли страница редактирования или создания
        $request = Request::getInstance();
        $editId = (int)$request->query('edit', 0);
        $action = $request->query('action', '');
        
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
                    
                    // Обновляем заголовок страницы для редактирования
                    $this->setPageHeader(
                        'Редагувати роль: ' . htmlspecialchars($data['role']['name']),
                        'Зміна параметрів ролі та прав доступу',
                        'fas fa-user-shield',
                        $this->pageHeaderButtons
                    );
                    
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
            } elseif ($action === 'create') {
                // Страница создания новой роли
                $data['role'] = [
                    'id' => null,
                    'name' => '',
                    'slug' => '',
                    'description' => '',
                    'is_system' => 0,
                    'is_default' => 0,
                    'permission_ids' => []
                ];
                
                // Получаем все разрешения сгруппированные по категориям
                $data['permissions'] = $this->roleManager->getAllPermissions();
                $data['permissionsByCategory'] = $this->groupPermissionsByCategory($data['permissions']);
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

