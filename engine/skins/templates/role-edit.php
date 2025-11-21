<?php
/**
 * Шаблон страницы редактирования роли
 * 
 * @var array $role Данные роли
 * @var array $permissions Все разрешения
 * @var array $permissionsByCategory Разрешения сгруппированные по категориям
 */
?>

<style>
.role-edit-wrapper {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.role-edit-main {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 25px;
}

.role-edit-sidebar {
    width: 280px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 25px;
    height: fit-content;
}

.role-form-group {
    margin-bottom: 25px;
}

.role-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
    color: #374151;
}

.role-form-group input[type="text"],
.role-form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.role-form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #3b82f6;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.permission-flags-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.permission-flags-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.permission-flags-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.permission-category {
    margin-bottom: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
}

.permission-category-header {
    background: #f9fafb;
    padding: 12px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
}

.permission-category-header input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.permission-category-header span {
    font-weight: 600;
    font-size: 14px;
    flex: 1;
}

.permission-category-body {
    padding: 15px;
    display: none;
}

.permission-category-body.active {
    display: block;
}

.permission-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
}

.permission-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.permission-item label {
    margin: 0;
    font-weight: 400;
    font-size: 14px;
    cursor: pointer;
    flex: 1;
}

.publish-section h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
}

.publish-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.publish-actions .btn {
    width: 100%;
    justify-content: center;
    padding: 10px;
}

.all-permissions-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
}

.all-permissions-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>

<form method="POST" action="<?= UrlHelper::admin('roles') ?>">
    <?= SecurityHelper::csrfField() ?>
    <input type="hidden" name="action" value="update_role">
    <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
    
    <div class="role-edit-wrapper">
        <div class="role-edit-main">
            <!-- Role Details -->
            <div class="role-form-group">
                <label for="role_name">Name *</label>
                <input type="text" id="role_name" name="name" value="<?= htmlspecialchars($role['name']) ?>" required>
            </div>
            
            <div class="role-form-group">
                <label for="role_description">Description</label>
                <textarea id="role_description" name="description" rows="4"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
            </div>
            
            <div class="role-form-group">
                <label>
                    <span style="margin-right: 10px;">Is default?</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_default" value="1" <?= !empty($role['is_system']) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </label>
            </div>
            
            <!-- Permission Flags -->
            <div class="permission-flags-header">
                <h3>Permission Flags</h3>
                <div class="permission-flags-controls">
                    <label class="all-permissions-checkbox">
                        <input type="checkbox" id="allPermissions">
                        <span>All Permissions</span>
                    </label>
                    <span>|</span>
                    <a href="#" id="collapseAll" style="text-decoration: none; color: #3b82f6;">Collapse all</a>
                    <span>|</span>
                    <a href="#" id="expandAll" style="text-decoration: none; color: #3b82f6;">Expand all</a>
                </div>
            </div>
            
            <?php
            $rolePermissionIds = $role['permission_ids'] ?? [];
            $categoryMapping = [
                'admin' => 'CMS',
                'cabinet' => 'FOB Comments',
                'system' => 'System',
                'settings' => 'Settings',
                'tools' => 'Tools'
            ];
            
            // Группируем разрешения по категориям
            $categoryPermissions = [];
            foreach ($permissionsByCategory as $category => $perms) {
                $displayCategory = $categoryMapping[$category] ?? ucfirst($category);
                if (!isset($categoryPermissions[$displayCategory])) {
                    $categoryPermissions[$displayCategory] = [];
                }
                $categoryPermissions[$displayCategory] = array_merge($categoryPermissions[$displayCategory], $perms);
            }
            
            // Если категорий нет, используем исходные
            if (empty($categoryPermissions)) {
                foreach ($permissionsByCategory as $category => $perms) {
                    $categoryPermissions[ucfirst($category)] = $perms;
                }
            }
            ?>
            
            <?php foreach ($categoryPermissions as $categoryName => $permissions): ?>
                <div class="permission-category">
                    <div class="permission-category-header" onclick="toggleCategory(this)">
                        <input type="checkbox" class="category-checkbox" data-category="<?= htmlspecialchars($categoryName) ?>">
                        <span><?= htmlspecialchars($categoryName) ?></span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </div>
                    <div class="permission-category-body">
                        <?php foreach ($permissions as $permission): ?>
                            <div class="permission-item">
                                <input type="checkbox" 
                                       id="perm_<?= $permission['id'] ?>" 
                                       name="permissions[]" 
                                       value="<?= $permission['id'] ?>"
                                       class="permission-checkbox"
                                       data-category="<?= htmlspecialchars($categoryName) ?>"
                                       <?= in_array($permission['id'], $rolePermissionIds) ? 'checked' : '' ?>>
                                <label for="perm_<?= $permission['id'] ?>">
                                    <?= htmlspecialchars($permission['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="role-edit-sidebar">
            <div class="publish-section">
                <h3>Publish</h3>
                <div class="publish-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="submit" name="save_and_exit" value="1" class="btn btn-outline-primary">
                        <i class="fas fa-door-open"></i> Save & Exit
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="duplicateRole()">
                        <i class="fas fa-copy"></i> Duplicate
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Toggle category
function toggleCategory(header) {
    const body = header.nextElementSibling;
    const icon = header.querySelector('i');
    
    if (body.classList.contains('active')) {
        body.classList.remove('active');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        body.classList.add('active');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

// All Permissions checkbox
document.getElementById('allPermissions')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
    });
    
    // Обновляем категории
    document.querySelectorAll('.category-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Category checkbox
document.querySelectorAll('.category-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const category = this.dataset.category;
        const permissions = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
        permissions.forEach(cb => {
            cb.checked = this.checked;
        });
    });
});

// Permission checkbox - обновляем категорию
document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const category = this.dataset.category;
        const categoryPermissions = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
        const checkedPermissions = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]:checked`);
        const categoryCheckbox = document.querySelector(`.category-checkbox[data-category="${category}"]`);
        
        if (categoryCheckbox) {
            categoryCheckbox.checked = categoryPermissions.length === checkedPermissions.length;
        }
        
        // Обновляем "All Permissions"
        const allPermissions = document.querySelectorAll('.permission-checkbox');
        const allCheckedPermissions = document.querySelectorAll('.permission-checkbox:checked');
        const allPermissionsCheckbox = document.getElementById('allPermissions');
        
        if (allPermissionsCheckbox) {
            allPermissionsCheckbox.checked = allPermissions.length === allCheckedPermissions.length;
        }
    });
});

// Collapse all
document.getElementById('collapseAll')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.permission-category-body').forEach(body => {
        body.classList.remove('active');
    });
    document.querySelectorAll('.permission-category-header i').forEach(icon => {
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    });
});

// Expand all
document.getElementById('expandAll')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.permission-category-body').forEach(body => {
        body.classList.add('active');
    });
    document.querySelectorAll('.permission-category-header i').forEach(icon => {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    });
});

// Duplicate role
function duplicateRole() {
    if (confirm('Дублювати цю роль?')) {
        // TODO: Реализовать дублирование роли
        alert('Дублювання ролі буде реалізовано в наступній версії');
    }
}

// Инициализация - открываем все категории по умолчанию
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.permission-category-body').forEach(body => {
        body.classList.add('active');
    });
    document.querySelectorAll('.permission-category-header i').forEach(icon => {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    });
});
</script>

