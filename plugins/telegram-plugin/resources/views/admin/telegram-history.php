<?php
/**
 * Шаблон страницы истории взаимодействий с Telegram
 */

// Данные уже доступны через extract() в SimpleTemplate
$history = $history ?? [];
$total = $total ?? 0;
$incomingCount = $incomingCount ?? 0;
$outgoingCount = $outgoingCount ?? 0;
$errorCount = $errorCount ?? 0;
$page = $page ?? 1;
$limit = $limit ?? 50;
$pages = $pages ?? 1;
?>

<div class="telegram-history-container">
    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="filterDirection" class="form-label">Направление</label>
                    <select class="form-select" id="filterDirection">
                        <option value="">Все</option>
                        <option value="incoming">Входящие</option>
                        <option value="outgoing">Исходящие</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterType" class="form-label">Тип</label>
                    <select class="form-select" id="filterType">
                        <option value="">Все</option>
                        <option value="message">Сообщение</option>
                        <option value="callback_query">Callback Query</option>
                        <option value="edited_message">Редактированное</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-primary me-2" onclick="loadHistory()">
                        <i class="fas fa-search"></i> Поиск
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearHistory()">
                        <i class="fas fa-trash"></i> Очистить историю
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $total ?></h5>
                    <p class="card-text text-muted">Всего записей</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= $incomingCount ?></h5>
                    <p class="card-text text-muted">Входящие</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?= $outgoingCount ?></h5>
                    <p class="card-text text-muted">Исходящие</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $errorCount ?></h5>
                    <p class="card-text text-muted">Ошибки</p>
                </div>
            </div>
        </div>
    </div>

    <!-- История -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">История взаимодействий</h5>
            <div class="d-flex align-items-center">
                <span class="badge bg-secondary me-2">Страница <?= $page ?> из <?= $pages ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshHistory()">
                    <i class="fas fa-sync"></i> Обновить
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="historyTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 120px;">Время</th>
                            <th style="width: 100px;">Направление</th>
                            <th style="width: 100px;">Тип</th>
                            <th>Пользователь</th>
                            <th>Сообщение</th>
                            <th style="width: 80px;">Статус</th>
                            <th style="width: 100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>История пуста</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <tr class="<?= $item['direction'] === 'incoming' ? 'table-info' : 'table-success' ?>" data-raw="<?= htmlspecialchars($item['raw_data'] ?? '{}') ?>">
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                    <td>
                                        <small><?= date('d.m.Y H:i:s', strtotime($item['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($item['direction'] === 'incoming'): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-arrow-down"></i> Входящее
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-arrow-up"></i> Исходящее
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($item['type']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($item['username']): ?>
                                            <strong>@<?= htmlspecialchars($item['username']) ?></strong><br>
                                        <?php endif; ?>
                                        <?php if ($item['first_name']): ?>
                                            <small><?= htmlspecialchars($item['first_name']) ?> <?= htmlspecialchars($item['last_name'] ?? '') ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['chat_id']): ?>
                                            <br><small class="text-muted">Chat ID: <?= htmlspecialchars($item['chat_id']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['text']): ?>
                                            <div class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($item['text']) ?>">
                                                <?= htmlspecialchars($item['text']) ?>
                                            </div>
                                        <?php elseif ($item['callback_data']): ?>
                                            <span class="badge bg-info">Callback: <?= htmlspecialchars($item['callback_data']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['status'] === 'error'): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle"></i> Ошибка
                                            </span>
                                        <?php elseif ($item['status'] === 'sent'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Отправлено
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-circle"></i> Обработано
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDetails(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['raw_data'] ?? '{}')) ?>')" title="Детали">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $item['id'] ?>)" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center" id="pagination">
                        <!-- Пагинация будет загружена через JS -->
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для деталей -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали записи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Загрузка деталей -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<style>
.telegram-history-container {
    padding: 20px 0;
}

.table-responsive {
    max-height: 600px;
    overflow-y: auto;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
let currentPage = <?= $page ?>;
let currentDirection = '';
let currentType = '';

// Загрузка истории
function loadHistory(page = 1) {
    currentPage = page;
    currentDirection = document.getElementById('filterDirection').value;
    currentType = document.getElementById('filterType').value;
    
    const formData = new FormData();
    formData.append('action', 'get_history');
    formData.append('page', page);
    formData.append('limit', 50);
    if (currentDirection) formData.append('direction', currentDirection);
    if (currentType) formData.append('type', currentType);
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');
    
    fetch('<?= UrlHelper::admin('telegram-history') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Telegram History] История загружена:', {
                total: data.total,
                page: data.page,
                pages: data.pages,
                records: data.data.length
            });
            renderHistory(data.data);
            renderPagination(data.page, data.pages);
            updateStatistics(data);
        } else {
            console.warn('[Telegram History] Ошибка загрузки:', data.message || 'Неизвестная ошибка');
            alert('Ошибка загрузки истории: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('[Telegram History] Ошибка загрузки истории:', error);
        alert('Ошибка загрузки истории');
    });
}

// Обновление истории
function refreshHistory() {
    loadHistory(currentPage);
}

// Рендеринг истории
function renderHistory(history) {
    const tbody = document.getElementById('historyTableBody');
    
    if (!history || history.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>История пуста</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = history.map(item => {
        const direction = item.direction === 'incoming' ? 'table-info' : 'table-success';
        const directionBadge = item.direction === 'incoming' 
            ? '<span class="badge bg-primary"><i class="fas fa-arrow-down"></i> Входящее</span>'
            : '<span class="badge bg-success"><i class="fas fa-arrow-up"></i> Исходящее</span>';
        
        const statusBadge = item.status === 'error' 
            ? '<span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> Ошибка</span>'
            : item.status === 'sent'
            ? '<span class="badge bg-success"><i class="fas fa-check"></i> Отправлено</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-circle"></i> Обработано</span>';
        
        const userInfo = item.username 
            ? `<strong>@${escapeHtml(item.username)}</strong><br><small>${escapeHtml(item.first_name || '')} ${escapeHtml(item.last_name || '')}</small>`
            : item.first_name 
            ? `<small>${escapeHtml(item.first_name)} ${escapeHtml(item.last_name || '')}</small>`
            : '';
        
        const chatId = item.chat_id ? `<br><small class="text-muted">Chat ID: ${escapeHtml(item.chat_id)}</small>` : '';
        
        const message = item.text 
            ? `<div class="text-truncate" style="max-width: 300px;" title="${escapeHtml(item.text)}">${escapeHtml(item.text)}</div>`
            : item.callback_data
            ? `<span class="badge bg-info">Callback: ${escapeHtml(item.callback_data)}</span>`
            : '<span class="text-muted">-</span>';
        
        const date = new Date(item.created_at);
        const dateStr = date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        const rawData = escapeHtml(item.raw_data || '{}');
        
        return `
            <tr class="${direction}" data-raw="${rawData}">
                <td>${item.id}</td>
                <td><small>${dateStr}</small></td>
                <td>${directionBadge}</td>
                <td><span class="badge bg-secondary">${escapeHtml(item.type)}</span></td>
                <td>${userInfo}${chatId}</td>
                <td>${message}</td>
                <td>${statusBadge}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDetails(${item.id}, '${rawData.replace(/'/g, "\\'")}')" title="Детали">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRecord(${item.id})" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Рендеринг пагинации
function renderPagination(page, pages) {
    const pagination = document.getElementById('pagination');
    if (!pagination || pages <= 1) {
        if (pagination) pagination.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Предыдущая страница
    html += `
        <li class="page-item ${page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadHistory(${page - 1}); return false;">Предыдущая</a>
        </li>
    `;
    
    // Страницы
    for (let i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i++) {
        html += `
            <li class="page-item ${i === page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadHistory(${i}); return false;">${i}</a>
            </li>
        `;
    }
    
    // Следующая страница
    html += `
        <li class="page-item ${page === pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadHistory(${page + 1}); return false;">Следующая</a>
        </li>
    `;
    
    pagination.innerHTML = html;
}

// Обновление статистики
function updateStatistics(data) {
    // Можно добавить отдельный AJAX запрос для статистики
    if (data.incomingCount !== undefined) {
        // Обновляем счетчики через отдельный запрос, если нужно
    }
}

// Просмотр деталей
function viewDetails(id, rawData) {
    try {
        const data = JSON.parse(rawData);
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        document.getElementById('detailsContent').innerHTML = `
            <pre style="max-height: 500px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">${JSON.stringify(data, null, 2)}</pre>
        `;
        modal.show();
    } catch (e) {
        alert('Ошибка парсинга данных');
    }
}

// Удаление записи
function deleteRecord(id) {
    if (!confirm('Удалить эту запись?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');
    
    fetch('<?= UrlHelper::admin('telegram-history') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Telegram History] Запись удалена:', id);
            loadHistory(currentPage);
        } else {
            console.warn('[Telegram History] Ошибка удаления записи:', data.message || 'Неизвестная ошибка');
            alert('Ошибка удаления: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('[Telegram History] Ошибка удаления записи:', error);
        alert('Ошибка удаления');
    });
}

// Очистка истории
function clearHistory() {
    if (!confirm('Очистить всю историю? Это действие нельзя отменить.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clear_history');
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');
    
    fetch('<?= UrlHelper::admin('telegram-history') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Telegram History] История очищена успешно');
            alert('История очищена');
            loadHistory(1);
        } else {
            console.warn('[Telegram History] Ошибка очистки истории:', data.message || 'Неизвестная ошибка');
            alert('Ошибка очистки: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('[Telegram History] Ошибка очистки истории:', error);
        alert('Ошибка очистки');
    });
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Автообновление каждые 30 секунд
setInterval(refreshHistory, 30000);
</script>
