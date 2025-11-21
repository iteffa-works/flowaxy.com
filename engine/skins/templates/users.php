<?php
/**
 * Шаблон сторінки управління користувачами
 * 
 * @var array $users Список користувачів
 * @var array $roles Список ролей
 */
?>

<style>
.users-list-wrapper {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.users-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    padding: 20px;
}

.user-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.2s ease;
}

.user-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.user-card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.user-card-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    font-weight: 600;
    flex-shrink: 0;
}

.user-card-info {
    flex: 1;
}

.user-card-title {
    margin: 0 0 5px 0;
    font-size: 18px;
    font-weight: 600;
    color: #111827;
}

.user-card-id {
    font-size: 12px;
    color: #6b7280;
}

.user-card-email {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 15px;
    word-break: break-word;
}

.user-card-roles {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.user-card-role {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: #3b82f6;
    color: #fff;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.user-card-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 15px;
}

.user-card-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #f3f4f6;
}

.user-card-actions .btn {
    width: 38px;
    height: 38px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0;
    border-width: 1.5px;
}

@media (max-width: 768px) {
    .users-list {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 15px;
    }
    
    .user-card {
        padding: 15px;
    }
    
    .user-card-roles {
        flex-direction: column;
    }
}
</style>

<div class="users-list-wrapper">
    <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <p class="text-muted">Користувачі не знайдені</p>
        </div>
    <?php else: ?>
        <div class="users-list">
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <div class="user-card-header">
                        <div class="user-card-avatar">
                            <?= strtoupper(mb_substr($user['username'], 0, 1)) ?>
                        </div>
                        <div class="user-card-info">
                            <h3 class="user-card-title"><?= htmlspecialchars($user['username']) ?></h3>
                            <div class="user-card-id">ID: <?= $user['id'] ?></div>
                        </div>
                    </div>
                    <?php if (!empty($user['email'])): ?>
                    <div class="user-card-email">
                        <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="user-card-roles">
                        <?php
                        $userRoles = $user['roles'] ?? [];
                        if (empty($userRoles)):
                        ?>
                            <span class="text-muted" style="font-size: 13px;">
                                <i class="fas fa-user-shield me-1"></i>Немає ролей
                            </span>
                        <?php else: ?>
                            <?php foreach ($userRoles as $userRole): ?>
                                <span class="user-card-role">
                                    <i class="fas fa-user-shield"></i>
                                    <?= htmlspecialchars($userRole['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-card-meta">
                        <i class="fas fa-calendar"></i>
                        <span>
                            <?= !empty($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '-' ?>
                        </span>
                    </div>
                    <div class="user-card-actions">
                        <button type="button" class="btn btn-outline-primary" 
                                onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?>')"
                                <?= $user['id'] === 1 ? 'disabled title="Неможливо редагувати першого користувача"' : '' ?>
                                title="Редагувати">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning" 
                                onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')"
                                title="Змінити пароль">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php if ($user['id'] !== 1): ?>
                            <?php
                            $session = sessionManager();
                            $currentUserId = (int)$session->get('admin_user_id');
                            $isCurrentUser = $user['id'] === $currentUserId;
                            ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете видалити цього користувача?')">
                                <?= SecurityHelper::csrfField() ?>
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger"
                                        <?= $isCurrentUser ? 'disabled title="Неможливо видалити себе"' : '' ?>
                                        title="Видалити">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

