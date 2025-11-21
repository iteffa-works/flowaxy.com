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

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ролі</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    <i class="fas fa-plus"></i> Створити роль
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($roles)): ?>
                    <p class="text-muted">Ролі не знайдені</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Slug</th>
                                    <th>Опис</th>
                                    <th>Системна</th>
                                    <th>Дозволи</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($role['name']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($role['slug']) ?></code></td>
                                        <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($role['is_system'])): ?>
                                                <span class="badge bg-warning">Так</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ні</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $rolePermissions = $role['permissions'] ?? [];
                                            $permissionCount = count($rolePermissions);
                                            ?>
                                            <span class="badge bg-info"><?= $permissionCount ?> дозволів</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editRole(<?= $role['id'] ?>, '<?= htmlspecialchars($role['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($role['slug'], ENT_QUOTES) ?>', '<?= htmlspecialchars($role['description'] ?? '', ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (empty($role['is_system']) && $role['slug'] !== 'developer'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете видалити цю роль?')">
                                                    <?= SecurityHelper::csrfToken() ?>
                                                    <input type="hidden" name="action" value="delete_role">
                                                    <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal для создания роли -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= SecurityHelper::csrfToken() ?>
                <input type="hidden" name="action" value="create_role">
                <div class="modal-header">
                    <h5 class="modal-title">Створити роль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Назва ролі *</label>
                        <input type="text" class="form-control" id="role_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="role_slug" class="form-label">Slug *</label>
                        <input type="text" class="form-control" id="role_slug" name="slug" required pattern="[a-z0-9-]+">
                        <small class="form-text text-muted">Тільки малі літери, цифри та дефіси</small>
                    </div>
                    <div class="mb-3">
                        <label for="role_description" class="form-label">Опис</label>
                        <textarea class="form-control" id="role_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Створити</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Секция разрешений -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Дозволи</h5>
            </div>
            <div class="card-body">
                <?php if (empty($permissionsByCategory)): ?>
                    <p class="text-muted">Дозволи не знайдені</p>
                <?php else: ?>
                    <?php foreach ($permissionsByCategory as $category => $permissions): ?>
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted mb-3"><?= htmlspecialchars($category) ?></h6>
                            <div class="row">
                                <?php foreach ($permissions as $permission): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="perm_<?= $permission['id'] ?>" 
                                                   disabled>
                                            <label class="form-check-label" for="perm_<?= $permission['id'] ?>">
                                                <strong><?= htmlspecialchars($permission['name']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <code><?= htmlspecialchars($permission['slug']) ?></code>
                                                    <?php if (!empty($permission['description'])): ?>
                                                        - <?= htmlspecialchars($permission['description']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Секция назначения ролей пользователям -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Призначення ролей користувачам</h5>
            </div>
            <div class="card-body">
                <?php if (empty($users) || empty($roles)): ?>
                    <p class="text-muted">Немає користувачів або ролей</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Користувач</th>
                                    <th>Email</th>
                                    <th>Ролі</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userRoles = user_roles($user['id']);
                                    $userRoleIds = array_column($userRoles, 'id');
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                        <td>
                                            <?php if (empty($userRoles)): ?>
                                                <span class="text-muted">Немає ролей</span>
                                            <?php else: ?>
                                                <?php foreach ($userRoles as $userRole): ?>
                                                    <span class="badge bg-primary me-1">
                                                        <?= htmlspecialchars($userRole['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#assignRoleModal<?= $user['id'] ?>">
                                                <i class="fas fa-user-plus"></i> Призначити роль
                                            </button>
                                            
                                            <!-- Modal для назначения роли -->
                                            <div class="modal fade" id="assignRoleModal<?= $user['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <?= SecurityHelper::csrfToken() ?>
                                                            <input type="hidden" name="action" value="assign_user_role">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Призначити роль користувачу: <?= htmlspecialchars($user['username']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="role_id_<?= $user['id'] ?>" class="form-label">Роль</label>
                                                                    <select class="form-select" id="role_id_<?= $user['id'] ?>" name="role_id" required>
                                                                        <option value="">Виберіть роль</option>
                                                                        <?php foreach ($roles as $role): ?>
                                                                            <?php if (!in_array($role['id'], $userRoleIds)): ?>
                                                                                <option value="<?= $role['id'] ?>">
                                                                                    <?= htmlspecialchars($role['name']) ?>
                                                                                </option>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                                                                <button type="submit" class="btn btn-primary">Призначити</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function editRole(id, name, slug, description) {
    // TODO: Реализовать редактирование роли
    alert('Редагування ролі буде реалізовано в наступній версії');
}
</script>

