<?php
/**
 * Шаблон страницы управления API ключами
 */

// Данные уже доступны через extract() в SimpleTemplate
$keys = $keys ?? [];
$apiBaseUrl = $apiBaseUrl ?? '';
?>

<div class="content-section">
    <?php if (empty($keys)): ?>
        <?php
        unset($actions);
        $icon = 'key';
        $title = 'Нет API ключей';
        $message = 'Создайте первый API ключ для интеграции внешних приложений';
        $classes = ['api-keys-empty-state'];
        include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Ключ</th>
                        <th>Разрешения</th>
                        <th>Последнее использование</th>
                        <th>Срок действия</th>
                        <th>Статус</th>
                        <th width="150">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keys as $key): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($key['name']) ?></strong>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($key['key_preview']) ?></code>
                            </td>
                            <td>
                                <?php if (empty($key['permissions'])): ?>
                                    <span class="badge bg-success">Все разрешения</span>
                                <?php else: ?>
                                    <?php foreach ($key['permissions'] as $permission): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($permission) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($key['last_used_at']): ?>
                                    <?= date('d.m.Y H:i', strtotime($key['last_used_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Никогда</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($key['expires_at']): ?>
                                    <?= date('d.m.Y H:i', strtotime($key['expires_at'])) ?>
                                    <?php if (strtotime($key['expires_at']) < time()): ?>
                                        <span class="badge bg-danger">Истек</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Бессрочный</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($key['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Неактивен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-sm <?= $key['is_active'] ? 'btn-warning' : 'btn-success' ?>"
                                            onclick="toggleApiKey(<?= $key['id'] ?>, <?= $key['is_active'] ? 'false' : 'true' ?>)"
                                            title="<?= $key['is_active'] ? 'Деактивировать' : 'Активировать' ?>">
                                        <i class="fas fa-<?= $key['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteApiKey(<?= $key['id'] ?>)"
                                            title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>API Base URL:</strong> <code><?= htmlspecialchars($apiBaseUrl) ?></code><br>
            <small>Используйте заголовок <code>Authorization: Bearer YOUR_API_KEY</code> или параметр <code>?api_key=YOUR_API_KEY</code> для аутентификации</small>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteApiKey(id) {
    if (!confirm('Вы уверены, что хотите удалить этот API ключ?')) {
        return;
    }
    
    fetch('<?= UrlHelper::admin('api-keys') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'delete_api_key',
            id: id,
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при удалении API ключа');
    });
}

function toggleApiKey(id, isActive) {
    fetch('<?= UrlHelper::admin('api-keys') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'toggle_api_key',
            id: id,
            is_active: isActive ? '1' : '0',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при изменении статуса API ключа');
    });
}

// Обработка успешного создания API ключа
document.addEventListener('DOMContentLoaded', function() {
    // Если есть данные нового ключа в сессии, показываем их
    // (можно расширить через AJAX ответ)
});
</script>

<!-- Модальное окно создания API ключа -->
<?php if (!empty($createModalHtml)): ?>
    <?= $createModalHtml ?>
<?php endif; ?>

