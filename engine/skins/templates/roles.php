<?php
/**
 * Шаблон страницы управления ролями и правами доступа
 * 
 * @var array $roles Список ролей
 * @var array $permissions Список разрешений
 * @var array $permissionsByCategory Разрешения сгруппированные по категориям
 * @var array $users Список пользователей
 */
?>

<style>
.roles-table-wrapper {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.roles-controls {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.roles-controls .search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.roles-controls .search-box input {
    width: 100%;
    padding: 8px 40px 8px 15px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.roles-controls .search-box i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.roles-table {
    margin: 0;
}

.roles-table thead th {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: #6b7280;
    cursor: pointer;
    user-select: none;
}

.roles-table thead th:hover {
    background: #f3f4f6;
}

.roles-table thead th i {
    margin-left: 5px;
    opacity: 0.5;
}

.roles-table tbody td {
    padding: 15px;
    vertical-align: middle;
    border-bottom: 1px solid #e5e7eb;
}

.roles-table tbody tr:hover {
    background: #f9fafb;
}

.role-name-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.role-name-link:hover {
    text-decoration: underline;
}

.roles-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 14px;
    color: #6b7280;
}

.roles-footer .records-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.roles-footer .records-count {
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.bulk-actions-btn, .filters-btn {
    padding: 8px 16px;
    border: 1px solid #d1d5db;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.bulk-actions-btn:hover, .filters-btn:hover {
    background: #f9fafb;
}
</style>

<div class="roles-table-wrapper">
    <div class="roles-controls">
        <button class="bulk-actions-btn">
            Bulk Actions <i class="fas fa-chevron-down"></i>
        </button>
        <button class="filters-btn">
            Filters
        </button>
        <div class="search-box">
            <input type="text" placeholder="Search..." id="rolesSearch">
            <i class="fas fa-search"></i>
        </div>
        <a href="<?= UrlHelper::admin('roles?action=create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create
        </a>
        <button class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Reload
        </button>
    </div>
    
    <?php if (empty($roles)): ?>
        <div class="text-center py-5">
            <p class="text-muted">Ролі не знайдені</p>
            <a href="<?= UrlHelper::admin('roles?action=create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Створити роль
            </a>
        </div>
    <?php else: ?>
        <table class="table roles-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>
                        ID <i class="fas fa-sort"></i>
                    </th>
                    <th>NAME</th>
                    <th>
                        DESCRIPTION <i class="fas fa-sort"></i>
                    </th>
                    <th>
                        CREATED AT <i class="fas fa-sort"></i>
                    </th>
                    <th>
                        CREATED BY <i class="fas fa-sort"></i>
                    </th>
                    <th>OPERATIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="role-checkbox" value="<?= $role['id'] ?>">
                        </td>
                        <td><?= $role['id'] ?></td>
                        <td>
                            <a href="<?= UrlHelper::admin('roles?edit=' . $role['id']) ?>" class="role-name-link">
                                <?= htmlspecialchars($role['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($role['created_at'])): ?>
                                <?= date('Y-m-d', strtotime($role['created_at'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($role['created_by_username'])): ?>
                                <a href="<?= UrlHelper::admin('users?edit=' . ($role['created_by'] ?? '')) ?>" class="text-primary">
                                    <?= htmlspecialchars($role['created_by_username']) ?>
                                    <i class="fas fa-external-link-alt ms-1" style="font-size: 11px;"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= UrlHelper::admin('roles?edit=' . $role['id']) ?>" class="btn btn-outline-primary" title="Редагувати">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (empty($role['is_system']) && $role['slug'] !== 'developer'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете видалити цю роль?')">
                                        <?= SecurityHelper::csrfField() ?>
                                        <input type="hidden" name="action" value="delete_role">
                                        <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Видалити">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="roles-footer">
            <div class="records-info">
                <i class="fas fa-globe"></i>
                <span>Show from <strong>1</strong> to <strong><?= count($roles) ?></strong> in <span class="records-count"><?= count($roles) ?></span> records</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            roleCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('rolesSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.roles-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
