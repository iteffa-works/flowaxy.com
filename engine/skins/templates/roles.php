<?php
/**
 * Шаблон сторінки управління ролями та правами доступу
 * 
 * @var array $roles Список ролей
 * @var array $permissions Список дозволів
 * @var array $permissionsByCategory Дозволи згруповані за категоріями
 * @var array $users Список користувачів
 */
?>

<div class="roles-list-wrapper">
    <div class="roles-controls">
        <div class="bulk-actions-dropdown hidden" id="bulkActionsDropdown">
            <button class="btn btn-outline-secondary btn-sm" id="bulkActionsBtn">
                Масові дії <i class="fas fa-chevron-down"></i>
            </button>
            <div class="bulk-actions-menu" id="bulkActionsMenu">
                <a href="#" id="bulkDeleteBtn" class="danger">Видалити вибрані</a>
            </div>
        </div>
        <div class="filters-dropdown">
            <button class="btn btn-outline-secondary btn-sm" id="filtersBtn">
                Фільтри
            </button>
            <div class="filters-menu" id="filtersMenu">
                <div class="filter-group">
                    <label>Тип ролі</label>
                    <select id="filterType">
                        <option value="">Всі</option>
                        <option value="system">Системні</option>
                        <option value="custom">Користувацькі</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Дата створення</label>
                    <input type="date" id="filterDateFrom" placeholder="Від">
                    <input type="date" id="filterDateTo" placeholder="До">
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="applyFiltersBtn">Застосувати</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFiltersBtn">Скинути</button>
                </div>
            </div>
        </div>
        <div class="roles-controls-spacer"></div>
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Оновити
        </button>
    </div>
    
    <div class="roles-select-all">
        <label>
            <input type="checkbox" id="selectAll">
            <span>Вибрати всі</span>
        </label>
    </div>
    
    <?php if (empty($roles)): ?>
        <div class="text-center py-5">
            <p class="text-muted">Ролі не знайдені</p>
            <a href="<?= UrlHelper::admin('roles?action=create') ?>" class="btn btn-outline-secondary btn-sm">
                Створити роль
            </a>
        </div>
    <?php else: ?>
        <div class="roles-list">
            <?php 
            // Сортируем роли по ID по возрастанию
            usort($roles, function($a, $b) {
                return (int)$a['id'] - (int)$b['id'];
            });
            foreach ($roles as $role): ?>
                <div class="role-card" data-is-system="<?= !empty($role['is_system']) ? '1' : '0' ?>">
                    <input type="checkbox" class="role-checkbox role-card-checkbox" value="<?= $role['id'] ?>">
                    <div class="role-card-content">
                        <div class="role-card-header">
                            <h3 class="role-card-title">
                                <a href="<?= UrlHelper::admin('roles?edit=' . $role['id']) ?>">
                                    <?= htmlspecialchars($role['name']) ?>
                                </a>
                            </h3>
                            <div class="role-card-id">ID: <?= $role['id'] ?></div>
                        </div>
                        <div class="role-card-description">
                            <?= htmlspecialchars($role['description'] ?? 'Опис відсутній') ?>
                        </div>
                        <div class="role-card-meta">
                            <div class="role-card-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>
                                    <?php if (!empty($role['created_at'])): ?>
                                        <?= date('d.m.Y', strtotime($role['created_at'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if (!empty($role['created_by_username'])): ?>
                            <div class="role-card-meta-item">
                                <i class="fas fa-user"></i>
                                <a href="<?= UrlHelper::admin('users?edit=' . ($role['created_by'] ?? '')) ?>" class="text-primary">
                                    <?= htmlspecialchars($role['created_by_username']) ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($role['is_system'])): ?>
                            <div class="role-card-meta-item">
                                <i class="fas fa-shield-alt"></i>
                                <span>Системна роль</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="role-card-actions">
                        <a href="<?= UrlHelper::admin('roles?edit=' . $role['id']) ?>" class="btn btn-outline-primary" title="Редагувати">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if (empty($role['is_system']) && $role['slug'] !== 'developer'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Ви впевнені, що хочете видалити цю роль?')">
                                <?= SecurityHelper::csrfField() ?>
                                <input type="hidden" name="action" value="delete_role">
                                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Видалити">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="roles-footer">
            <div class="records-info">
                <i class="fas fa-globe"></i>
                <span>Показано від <strong>1</strong> до <strong><?= count($roles) ?></strong> з <span class="records-count"><?= count($roles) ?></span> записів</span>
            </div>
        </div>
    <?php endif; ?>
</div>
