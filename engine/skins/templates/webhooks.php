<?php
/**
 * Шаблон страницы управления Webhooks
 */

// Данные уже доступны через extract() в SimpleTemplate
$webhooks = $webhooks ?? [];
?>

<div class="content-section">
    <?php if (empty($webhooks)): ?>
        <?php
        unset($actions);
        $icon = 'paper-plane';
        $title = 'Нет Webhooks';
        $message = 'Создайте первый webhook для отправки уведомлений внешним сервисам';
        $classes = ['webhooks-empty-state'];
        include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>URL</th>
                        <th>События</th>
                        <th>Последний вызов</th>
                        <th>Успешно</th>
                        <th>Ошибок</th>
                        <th>Статус</th>
                        <th width="200">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($webhooks as $webhook): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($webhook['name']) ?></strong>
                            </td>
                            <td>
                                <code class="small"><?= htmlspecialchars(substr($webhook['url'], 0, 50)) ?><?= strlen($webhook['url']) > 50 ? '...' : '' ?></code>
                            </td>
                            <td>
                                <?php if (empty($webhook['events'])): ?>
                                    <span class="badge bg-info">Все события</span>
                                <?php else: ?>
                                    <?php foreach (array_slice($webhook['events'], 0, 3) as $event): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($event) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($webhook['events']) > 3): ?>
                                        <span class="badge bg-secondary">+<?= count($webhook['events']) - 3 ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($webhook['last_triggered_at']): ?>
                                    <?= date('d.m.Y H:i', strtotime($webhook['last_triggered_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Никогда</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= (int)$webhook['success_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-danger"><?= (int)$webhook['failure_count'] ?></span>
                            </td>
                            <td>
                                <?php if ($webhook['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Неактивен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-sm btn-info"
                                            onclick="testWebhook(<?= $webhook['id'] ?>)"
                                            title="Тест">
                                        <i class="fas fa-vial"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm <?= $webhook['is_active'] ? 'btn-warning' : 'btn-success' ?>"
                                            onclick="toggleWebhook(<?= $webhook['id'] ?>, <?= $webhook['is_active'] ? 'false' : 'true' ?>)"
                                            title="<?= $webhook['is_active'] ? 'Деактивировать' : 'Активировать' ?>">
                                        <i class="fas fa-<?= $webhook['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteWebhook(<?= $webhook['id'] ?>)"
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
    <?php endif; ?>
</div>

<script>
function deleteWebhook(id) {
    if (!confirm('Вы уверены, что хотите удалить этот webhook?')) {
        return;
    }
    
    fetch('<?= UrlHelper::admin('webhooks') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'delete_webhook',
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
        alert('Ошибка при удалении webhook');
    });
}

function toggleWebhook(id, isActive) {
    fetch('<?= UrlHelper::admin('webhooks') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'toggle_webhook',
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
        alert('Ошибка при изменении статуса webhook');
    });
}

function testWebhook(id) {
    if (!confirm('Отправить тестовое событие на этот webhook?')) {
        return;
    }
    
    fetch('<?= UrlHelper::admin('webhooks') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'test_webhook',
            id: id,
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Webhook успешно отправлен!');
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при отправке тестового webhook');
    });
}
</script>

<!-- Модальное окно создания Webhook -->
<?php if (!empty($createModalHtml)): ?>
    <?= $createModalHtml ?>
<?php endif; ?>

