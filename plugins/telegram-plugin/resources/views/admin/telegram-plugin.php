<?php
/**
 * Шаблон страницы настройки Telegram плагина
 */

// Данные уже доступны через extract() в SimpleTemplate
$settings = $settings ?? [];
$notifyEvents = $notifyEvents ?? [];
?>

<div class="telegram-plugin-settings">
    <form method="POST" action="<?= UrlHelper::admin('telegram-plugin') ?>">
        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
        
        <!-- Основные настройки -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog"></i> Основные настройки
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
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
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="chat_id" class="form-label">
                                Chat ID <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="chat_id" 
                                   name="chat_id" 
                                   value="<?= htmlspecialchars($settings['chat_id'] ?? '') ?>"
                                   placeholder="123456789"
                                   required>
                            <small class="form-text text-muted">
                                ID чата для отправки уведомлений
                            </small>
                        </div>
                    </div>
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

        <!-- События для уведомлений -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bell"></i> События для уведомлений
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="user.login"
                                   id="event_user_login"
                                   <?= in_array('user.login', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_user_login">
                                Вход пользователя
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="user.logout"
                                   id="event_user_logout"
                                   <?= in_array('user.logout', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_user_logout">
                                Выход пользователя
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="plugin.installed"
                                   id="event_plugin_installed"
                                   <?= in_array('plugin.installed', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_plugin_installed">
                                Установка плагина
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="plugin.activated"
                                   id="event_plugin_activated"
                                   <?= in_array('plugin.activated', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_plugin_activated">
                                Активация плагина
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="plugin.deactivated"
                                   id="event_plugin_deactivated"
                                   <?= in_array('plugin.deactivated', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_plugin_deactivated">
                                Деактивация плагина
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="theme.activated"
                                   id="event_theme_activated"
                                   <?= in_array('theme.activated', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_theme_activated">
                                Активация темы
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="notify_events[]" 
                                   value="system.error"
                                   id="event_system_error"
                                   <?= in_array('system.error', $notifyEvents) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_system_error">
                                Ошибки системы
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопки действий -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tools"></i> Действия
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить настройки
                    </button>
                    
                    <button type="button" 
                            class="btn btn-success" 
                            onclick="sendTestMessage()"
                            <?= empty($settings['bot_token']) ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane"></i> Отправить тестовое сообщение
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
                            <i class="fas fa-link"></i> Установить webhook
                        </button>
                        
                        <button type="button" 
                                class="btn btn-danger" 
                                onclick="deleteWebhook()"
                                <?= empty($settings['bot_token']) ? 'disabled' : '' ?>>
                            <i class="fas fa-unlink"></i> Удалить webhook
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function sendTestMessage() {
    if (!confirm('Отправить тестовое сообщение в Telegram?')) {
        return;
    }
    
    fetch('<?= UrlHelper::admin('telegram-plugin') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'test_message',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Telegram Plugin] Тестовое сообщение отправлено успешно');
            alert('✅ Сообщение отправлено!');
        } else {
            const errorMsg = data.message || 'Неизвестная ошибка';
            const errorCode = data.error_code || null;
            const errorDesc = data.error_description || null;
            
            console.warn('[Telegram Plugin] Ошибка отправки сообщения:', {
                message: errorMsg,
                code: errorCode,
                description: errorDesc
            });
            
            let alertMsg = '❌ Ошибка: ' + errorMsg;
            if (errorCode) {
                alertMsg += `\n\nКод ошибки: ${errorCode}`;
            }
            if (errorDesc) {
                alertMsg += `\nОписание: ${errorDesc}`;
            }
            alert(alertMsg);
        }
    })
    .catch(error => {
        console.error('[Telegram Plugin] Ошибка отправки сообщения:', error);
        alert('❌ Ошибка отправки сообщения');
    });
}

function getBotInfo() {
    fetch('<?= UrlHelper::admin('telegram-plugin') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_bot_info',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data) {
            const bot = data.data;
            console.log('[Telegram Plugin] Информация о боте получена:', {
                id: bot.id,
                username: bot.username,
                firstName: bot.first_name
            });
            let info = 'Информация о боте:\n\n';
            info += 'ID: ' + (bot.id || 'N/A') + '\n';
            info += 'Имя: ' + (bot.first_name || 'N/A') + '\n';
            info += 'Username: @' + (bot.username || 'N/A') + '\n';
            info += 'Поддерживает группы: ' + (bot.can_join_groups ? 'Да' : 'Нет') + '\n';
            info += 'Поддерживает каналы: ' + (bot.can_read_all_group_messages ? 'Да' : 'Нет') + '\n';
            alert(info);
        } else {
            console.warn('[Telegram Plugin] Ошибка получения информации о боте:', data.message || 'Неизвестная ошибка');
            alert('❌ Ошибка: ' + (data.message || 'Не удалось получить информацию'));
        }
    })
    .catch(error => {
        console.error('[Telegram Plugin] Ошибка получения информации о боте:', error);
        alert('❌ Ошибка получения информации');
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
        },
        body: new URLSearchParams({
            action: 'set_webhook',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Telegram Plugin] Webhook установлен успешно');
            alert('✅ Webhook установлен!');
        } else {
            console.warn('[Telegram Plugin] Ошибка установки webhook:', data.message || 'Неизвестная ошибка');
            alert('❌ Ошибка установки webhook');
        }
    })
    .catch(error => {
        console.error('[Telegram Plugin] Ошибка установки webhook:', error);
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
        },
        body: new URLSearchParams({
            action: 'delete_webhook',
            csrf_token: '<?= Security::csrfToken() ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Telegram Plugin] Webhook удален успешно');
            alert('✅ Webhook удален!');
        } else {
            console.warn('[Telegram Plugin] Ошибка удаления webhook:', data.message || 'Неизвестная ошибка');
            alert('❌ Ошибка удаления webhook');
        }
    })
    .catch(error => {
        console.error('[Telegram Plugin] Ошибка удаления webhook:', error);
        alert('❌ Ошибка удаления webhook');
    });
}
</script>

