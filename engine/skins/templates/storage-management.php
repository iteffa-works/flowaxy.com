<?php
/**
 * Шаблон страницы управления хранилищами
 * 
 * @var array $sessions_info Информация о сессиях
 * @var array $cookies_info Информация о cookies
 * @var array $storage_info Информация о клиентском хранилище
 */
?>

<div class="row">
    <div class="col-12">
        <!-- Уведомления -->
        <?php
        if (!empty($message)) {
            include __DIR__ . '/../components/alert.php';
            $type = $messageType ?? 'info';
            $dismissible = true;
        }
        ?>
        
        <!-- Сессии -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-server me-2"></i>Сесії
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshStorageInfo()">
                        <i class="fas fa-sync-alt"></i> Оновити
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете очистити всі сесії?')">
                        <?= SecurityHelper::csrfField() ?>
                        <input type="hidden" name="action" value="clear_sessions">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Очистити сесії
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-label">Кількість файлів</span>
                            <span class="info-box-value" id="sessions-count"><?= $sessions_info['count'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-label">Загальний розмір</span>
                            <span class="info-box-value" id="sessions-size"><?= function_exists('formatBytes') ? formatBytes($sessions_info['total_size'] ?? 0) : number_format($sessions_info['total_size'] ?? 0) . ' B' ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-label">Поточна сесія ID</span>
                            <span class="info-box-value small" id="session-id"><?= htmlspecialchars($sessions_info['current_session_id'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-label">Ключів у сесії</span>
                            <span class="info-box-value" id="session-keys-count"><?= count($sessions_info['current_session_keys'] ?? []) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Ключі поточної сесії:</h6>
                    <div class="session-keys-list">
                        <?php if (!empty($sessions_info['current_session_keys'])): ?>
                            <?php foreach ($sessions_info['current_session_keys'] as $key): ?>
                                <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($key) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">Немає ключів</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Шлях збереження:</h6>
                    <code><?= htmlspecialchars($sessions_info['session_path'] ?? '') ?></code>
                </div>
            </div>
        </div>
        
        <!-- Cookies -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-cookie me-2"></i>Cookies
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshStorageInfo()">
                        <i class="fas fa-sync-alt"></i> Оновити
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете очистити всі cookies? (Важливі cookies будуть збережені)')">
                        <?= SecurityHelper::csrfField() ?>
                        <input type="hidden" name="action" value="clear_cookies">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Очистити cookies
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="info-box">
                            <span class="info-box-label">Кількість cookies</span>
                            <span class="info-box-value" id="cookies-count"><?= $cookies_info['count'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Ключ</th>
                                <th>Значення</th>
                                <th>Розмір</th>
                                <th>Тип</th>
                            </tr>
                        </thead>
                        <tbody id="cookies-list">
                            <?php if (!empty($cookies_info['cookies'])): ?>
                                <?php foreach ($cookies_info['cookies'] as $cookie): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($cookie['key']) ?></code></td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 300px;" title="<?= htmlspecialchars($cookie['value']) ?>">
                                                <?= htmlspecialchars($cookie['value']) ?>
                                            </span>
                                        </td>
                                        <td><?= function_exists('formatBytes') ? formatBytes($cookie['value_length']) : number_format($cookie['value_length']) . ' B' ?></td>
                                        <td>
                                            <?php if ($cookie['is_json']): ?>
                                                <span class="badge bg-info">JSON</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Text</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Немає cookies</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Клиентское хранилище -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-boxes me-2"></i>Клієнтське сховище
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshStorageInfo()">
                        <i class="fas fa-sync-alt"></i> Оновити
                    </button>
                    <button type="button" class="btn btn-sm btn-success" onclick="syncStorage()">
                        <i class="fas fa-sync"></i> Синхронізувати
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені, що хочете очистити клієнтське сховище?')">
                        <?= SecurityHelper::csrfField() ?>
                        <input type="hidden" name="action" value="clear_storage">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Очистити сховище
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-label">Тип сховища</span>
                            <span class="info-box-value" id="storage-type"><?= htmlspecialchars($storage_info['type'] ?? 'localStorage') ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-label">Префікс</span>
                            <span class="info-box-value" id="storage-prefix"><?= htmlspecialchars($storage_info['prefix'] ?? '') ?: 'Немає' ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-label">Ключів (сервер)</span>
                            <span class="info-box-value" id="storage-server-count"><?= $storage_info['server_count'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Серверні дані:</h6>
                    <div id="storage-server-keys">
                        <?php if (!empty($storage_info['server_keys'])): ?>
                            <?php foreach ($storage_info['server_keys'] as $key): ?>
                                <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($key) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">Немає даних</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Клієнтські дані:</h6>
                    <div id="storage-client-keys">
                        <span class="text-muted">Завантаження...</span>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Синхронізація:</strong> Перевіряє та синхронізує дані між сервером та клієнтом.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Функция форматирования байтов
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Обновление информации о хранилищах
function refreshStorageInfo() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Оновлення...';
    
    fetch('<?= UrlHelper::admin("storage-management") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_storage_info',
            csrf_token: '<?= SecurityHelper::csrfToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            // Обновляем информацию о сессиях
            if (data.data.sessions) {
                document.getElementById('sessions-count').textContent = data.data.sessions.count || 0;
                document.getElementById('sessions-size').textContent = formatBytes(data.data.sessions.total_size || 0);
                document.getElementById('session-id').textContent = data.data.sessions.current_session_id || '';
                document.getElementById('session-keys-count').textContent = (data.data.sessions.current_session_keys || []).length;
                
                // Обновляем список ключей
                const keysList = document.querySelector('.session-keys-list');
                if (data.data.sessions.current_session_keys && data.data.sessions.current_session_keys.length > 0) {
                    keysList.innerHTML = data.data.sessions.current_session_keys.map(key => 
                        `<span class="badge bg-secondary me-1 mb-1">${escapeHtml(key)}</span>`
                    ).join('');
                } else {
                    keysList.innerHTML = '<span class="text-muted">Немає ключів</span>';
                }
            }
            
            // Обновляем информацию о cookies
            if (data.data.cookies) {
                document.getElementById('cookies-count').textContent = data.data.cookies.count || 0;
                
                const cookiesList = document.getElementById('cookies-list');
                if (data.data.cookies.cookies && data.data.cookies.cookies.length > 0) {
                    cookiesList.innerHTML = data.data.cookies.cookies.map(cookie => {
                        const value = cookie.value.length > 50 ? cookie.value.substring(0, 50) + '...' : cookie.value;
                        const typeBadge = cookie.is_json 
                            ? '<span class="badge bg-info">JSON</span>' 
                            : '<span class="badge bg-secondary">Text</span>';
                        return `
                            <tr>
                                <td><code>${escapeHtml(cookie.key)}</code></td>
                                <td><span class="text-truncate d-inline-block" style="max-width: 300px;" title="${escapeHtml(cookie.value)}">${escapeHtml(value)}</span></td>
                                <td>${formatBytes(cookie.value_length)}</td>
                                <td>${typeBadge}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    cookiesList.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Немає cookies</td></tr>';
                }
            }
            
            // Обновляем информацию о клиентском хранилище
            if (data.data.storage) {
                document.getElementById('storage-type').textContent = data.data.storage.type || 'localStorage';
                document.getElementById('storage-prefix').textContent = data.data.storage.prefix || 'Немає';
                document.getElementById('storage-server-count').textContent = data.data.storage.server_count || 0;
                
                const serverKeys = document.getElementById('storage-server-keys');
                if (data.data.storage.server_keys && data.data.storage.server_keys.length > 0) {
                    serverKeys.innerHTML = data.data.storage.server_keys.map(key => 
                        `<span class="badge bg-primary me-1 mb-1">${escapeHtml(key)}</span>`
                    ).join('');
                } else {
                    serverKeys.innerHTML = '<span class="text-muted">Немає даних</span>';
                }
            }
        }
        
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    })
    .catch(error => {
        console.error('Error refreshing storage info:', error);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Помилка при оновленні інформації');
    });
}

// Синхронизация хранилищ
function syncStorage() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Синхронізація...';
    
    // Получаем данные из клиентского хранилища
    let clientData = {};
    try {
        if (typeof Storage !== 'undefined') {
            const storage = window.localStorage;
            for (let i = 0; i < storage.length; i++) {
                const key = storage.key(i);
                clientData[key] = storage.getItem(key);
            }
        }
    } catch (e) {
        console.error('Error reading client storage:', e);
    }
    
    // Отправляем на сервер для синхронизации
    fetch('<?= UrlHelper::admin("storage-management") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'sync_storage',
            csrf_token: '<?= SecurityHelper::csrfToken() ?>',
            client_data: JSON.stringify(clientData)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем клиентские ключи
            updateClientStorageKeys();
            
            let message = 'Синхронізація виконана успішно';
            if (data.synced > 0) {
                message += `. Синхронізовано ключів: ${data.synced}`;
            }
            if (data.conflicts && data.conflicts.length > 0) {
                message += `. Знайдено конфліктів: ${data.conflicts.length}`;
            }
            
            alert(message);
            
            // Обновляем информацию о хранилищах
            refreshStorageInfo();
        } else {
            alert('Помилка при синхронізації: ' + (data.error || 'Невідома помилка'));
        }
        
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    })
    .catch(error => {
        console.error('Error syncing storage:', error);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Помилка при синхронізації');
    });
}

// Обновление списка клиентских ключей
function updateClientStorageKeys() {
    const clientKeysDiv = document.getElementById('storage-client-keys');
    let clientKeys = [];
    
    try {
        if (typeof Storage !== 'undefined') {
            const storage = window.localStorage;
            for (let i = 0; i < storage.length; i++) {
                clientKeys.push(storage.key(i));
            }
        }
    } catch (e) {
        console.error('Error reading client storage:', e);
    }
    
    if (clientKeys.length > 0) {
        clientKeysDiv.innerHTML = clientKeys.map(key => 
            `<span class="badge bg-success me-1 mb-1">${escapeHtml(key)}</span>`
        ).join('');
    } else {
        clientKeysDiv.innerHTML = '<span class="text-muted">Немає даних</span>';
    }
}

// Экранирование HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Загружаем клиентские ключи при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    updateClientStorageKeys();
});
</script>

<style>
.info-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 12px;
    text-align: center;
}

.info-box-label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 4px;
    text-transform: uppercase;
    font-weight: 600;
}

.info-box-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
}

.info-box-value.small {
    font-size: 0.875rem;
    word-break: break-all;
}

.session-keys-list,
#storage-server-keys,
#storage-client-keys {
    min-height: 40px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
}

.table code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.875rem;
}
</style>

