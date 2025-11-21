<?php
/**
 * Шаблон страницы управления пользователями
 * 
 * @var array $users Список пользователей
 * @var array $roles Список ролей
 */
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Користувачі</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-plus"></i> Створити користувача
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-muted">Користувачі не знайдені</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Логін</th>
                                    <th>Email</th>
                                    <th>Ролі</th>
                                    <th>Створено</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            $userRoles = $user['roles'] ?? [];
                                            if (empty($userRoles)):
                                            ?>
                                                <span class="text-muted">Немає ролей</span>
                                            <?php else: ?>
                                                <?php foreach ($userRoles as $userRole): ?>
                                                    <span class="badge bg-primary me-1">
                                                        <?= htmlspecialchars($userRole['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '-' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?>')"
                                                    <?= $user['id'] === 1 ? 'disabled title="Неможливо редагувати першого користувача"' : '' ?>>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] !== 1): ?>
                                                <?php
                                                $currentUserId = (int)Session::get('admin_user_id');
                                                $isCurrentUser = $user['id'] === $currentUserId;
                                                ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете видалити цього користувача?')">
                                                    <?= SecurityHelper::csrfField() ?>
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            <?= $isCurrentUser ? 'disabled title="Неможливо видалити себе"' : '' ?>>
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

<!-- Modal для создания пользователя -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= SecurityHelper::csrfField() ?>
                <input type="hidden" name="action" value="create_user">
                <div class="modal-header">
                    <h5 class="modal-title">Створити користувача</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Логін *</label>
                        <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Підтвердження пароля *</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
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

<!-- Modal для редактирования пользователя -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= SecurityHelper::csrfField() ?>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                    <h5 class="modal-title">Редагувати користувача</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Логін *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required minlength="3" maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Зберегти</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal для изменения пароля -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= SecurityHelper::csrfField() ?>
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="password_user_id">
                <div class="modal-header">
                    <h5 class="modal-title">Змінити пароль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Зміна пароля для користувача: <strong id="password_username"></strong></p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Новий пароль *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Підтвердження пароля *</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Змінити</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(id, username, email) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function changePassword(id, username) {
    document.getElementById('password_user_id').value = id;
    document.getElementById('password_username').textContent = username;
    document.getElementById('new_password').value = '';
    document.getElementById('password_confirm').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}
</script>

