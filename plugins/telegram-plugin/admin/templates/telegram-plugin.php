<?php
/**
 * Шаблон страницы настройки Telegram плагина
 */

// Данные уже доступны через extract() в SimpleTemplate
$settings = $settings ?? [];
$notifyEvents = $notifyEvents ?? [];
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <?php
    $type = $messageType ?? 'info';
    $dismissible = true;
    include dirname(__DIR__, 3) . '/engine/skins/components/alert.php';
    ?>
<?php endif; ?>

<div class="content-section">
    <form method="POST" action="<?= UrlHelper::admin('telegram-plugin') ?>">
        <?= Security::csrfField() ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Основные настройки</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="bot_token" class="form-label">
                        Bot Token <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="bot_token" 
                           name="bot_token" 
                           value="<?= htmlspecialchars($settings['bot_token'] ?? '') ?>"
                           placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                           required>
                    <small class="form-text text-muted">
                        Токен бота от @BotFather в Telegram
                    </small>
                </div>
                
                <div class="mb-3">
                    <label for="chat_id" class="form-label">
                        Chat ID <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="chat_id" 
                           name="chat_id" 
                           value="<?= htmlspecialchars($settings['chat_id'] ?? '') ?>"
                           placeholder="-1001234567890 или 123456789"
                           required>
                    <small class="form-text text-muted">
                        ID чата или канала для отправки уведомлений
                    </small>
                </div>
                
                <div class="mb-3">
                    <label for="webhook_url" class="form-label">
                        Webhook URL
                    </label>
                    <input type="url" 
                           class="form-control" 
                           id="webhook_url" 
                           name="webhook_url" 
                           value="<?= htmlspecialchars($settings['webhook_url'] ?? '') ?>"
                           placeholder="https://example.com/admin/telegram/webhook">
                    <small class="form-text text-muted">
                        URL для получения обновлений от Telegram (опционально)
                    </small>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">События для уведомлений</h5>
            </div>
            <div class="card-body">
                <?php
                $availableEvents = [
                    'user.login' => 'Вход пользователя',
                    'user.logout' => 'Выход пользователя',
                    'plugin.installed' => 'Установка плагина',
                    'plugin.activated' => 'Активация плагина',
                    'plugin.deactivated' => 'Деактивация плагина',
                    'theme.activated' => 'Активация темы',
                    'system.error' => 'Ошибка системы'
                ];
                
                foreach ($availableEvents as $event => $label):
                ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="notify_events[]" 
                               value="<?= htmlspecialchars($event) ?>"
                               id="event_<?= htmlspecialchars($event) ?>"
                               <?= in_array($event, $notifyEvents) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="event_<?= htmlspecialchars($event) ?>">
                            <?= htmlspecialchars($label) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Сохранить настройки
            </button>
            
            <button type="button" 
                    class="btn btn-success" 
                    onclick="testMessage()"
                    <?= empty($settings['bot_token']) || empty($settings['chat_id']) ? 'disabled' : '' ?>>
                <i class="fas fa-paper-plane"></i> Отправить тест
            </button>
            
            <button type="button" 
                    class="btn btn-info" 
                    onclick="getBotInfo()"
                    <?= empty($settings['bot_token']) ? 'disabled' : '' ?>>
                <i class="fas fa-info-circle"></i> Информация о боте
            </button>
            
            <?php if (!empty($settings['webhook_url'])): ?>
                <button type="button" 
                        class="btn btn-warning" 
                        onclick="setWebhook()"
                        <?= empty($settings['bot_token']) ? 'disabled' : '' ?>>
                    <i class="fas fa-link"></i> Установить Webhook
                </button>
                
                <button type="button" 
                        class="btn btn-danger" 
                        onclick="deleteWebhook()"
                        <?= empty($settings['bot_token']) ? 'disabled' : '' ?>>
                    <i class="fas fa-unlink"></i> Удалить Webhook
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Модальное окно с информацией о боте -->
<div class="modal fade" id="botInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Информация о боте</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="botInfoContent">
                Загрузка...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
function testMessage() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
    
    fetch('<?= UrlHelper::admin('telegram-plugin') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'test_message',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ ' + (data.message || 'Сообщение отправлено!'));
        } else {
            alert('❌ Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Ошибка отправки сообщения');
    });
}

function getBotInfo() {
    const modal = new bootstrap.Modal(document.getElementById('botInfoModal'));
    const content = document.getElementById('botInfoContent');
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Загрузка...</p></div>';
    modal.show();
    
    fetch('<?= UrlHelper::admin('telegram-plugin') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'get_bot_info',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const bot = data.data;
            content.innerHTML = `
                <div class="mb-3">
                    <strong>Имя бота:</strong> ${bot.first_name || 'N/A'}<br>
                    <strong>Username:</strong> @${bot.username || 'N/A'}<br>
                    <strong>ID:</strong> ${bot.id || 'N/A'}<br>
                    ${bot.can_join_groups ? '<span class="badge bg-success">Может присоединяться к группам</span>' : ''}
                    ${bot.can_read_all_group_messages ? '<span class="badge bg-info">Может читать все сообщения</span>' : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<div class="alert alert-danger">❌ ' + (data.message || 'Не удалось получить информацию о боте') + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div class="alert alert-danger">❌ Ошибка получения информации о боте</div>';
    });
}

function setWebhook() {
    if (!confirm('Установить webhook? Это заменит текущий webhook, если он установлен.')) {
        return;
    }
    
    fetch('<?= UrlHelper::admin('telegram-plugin') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'set_webhook',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + (data.message || 'Webhook установлен!'));
        } else {
            alert('❌ Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Ошибка установки webhook');
    });
}

function deleteWebhook() {
    if (!confirm('Удалить webhook? Бот перестанет получать обновления через webhook.')) {
        return;
    }
    
    fetch('<?= UrlHelper::admin('telegram-plugin') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'delete_webhook',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + (data.message || 'Webhook удален!'));
        } else {
            alert('❌ Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Ошибка удаления webhook');
    });
}
</script>

