<?php
/**
 * Шаблон поштового клієнта
 * Дизайн як в модулі меню
 */
?>

<style>
.mail-client-container {
    display: grid;
    grid-template-columns: 280px 380px 1fr;
    gap: 20px;
    height: calc(100vh - 220px);
    min-height: 600px;
}

/* Левая панель: Папки */
.mail-sidebar {
    background: #fff;
    border-radius: 8px;
    padding: 0;
    overflow-y: auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.mail-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #e0e0e0;
}

.mail-sidebar-header .btn {
    width: 100%;
    padding: 10px;
    font-weight: 500;
    border-radius: 6px;
}

.mail-folders {
    padding: 12px;
    flex: 1;
    overflow-y: auto;
}

.mail-folders-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e0e0e0;
}

.mail-folders-header h6 {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.folder-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.folder-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 2px;
    color: #495057;
    font-size: 0.875rem;
    transition: all 0.15s ease;
    position: relative;
}

.folder-item:hover {
    background: #f8f9fa;
}

.folder-item.active {
    background: #e7f3ff;
    color: #0d6efd;
    font-weight: 500;
}

.folder-item i {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.folder-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.folder-count {
    font-size: 0.75rem;
    color: #6c757d;
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 24px;
    text-align: center;
}

.folder-item.active .folder-count {
    background: #0d6efd;
    color: #fff;
}

/* Средняя панель: Список листов */
.mail-list-panel {
    background: #fff;
    border-radius: 8px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.mail-list-header {
    padding: 16px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    background: #f8f9fa;
}

.mail-list-header .btn {
    flex: 1;
    min-width: 80px;
    font-size: 0.875rem;
    padding: 8px 12px;
}

.mail-list {
    flex: 1;
    overflow-y: auto;
    background: #fff;
}

.mail-item {
    padding: 14px 16px;
    border-bottom: 1px solid #e0e0e0;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    position: relative;
}

.mail-item:hover {
    background: #f8f9fa;
}

.mail-item.active {
    background: #e7f3ff;
    border-left: 3px solid #0d6efd;
}

.mail-item.unread {
    background: #fff;
    font-weight: 600;
}

.mail-item.unread.active {
    background: #e7f3ff;
}

.mail-item-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-right: 8px;
    flex-shrink: 0;
}

.mail-item-star,
.mail-item-important {
    cursor: pointer;
    font-size: 16px;
    opacity: 0.3;
    transition: opacity 0.15s, transform 0.15s;
    padding: 4px;
    border-radius: 4px;
}

.mail-item-star:hover,
.mail-item-important:hover {
    opacity: 1;
    transform: scale(1.1);
}

.mail-item-star {
    color: #ffc107;
}

.mail-item-star.active {
    opacity: 1;
    color: #ffc107;
}

.mail-item-important {
    color: #dc3545;
}

.mail-item-important.active {
    opacity: 1;
    color: #dc3545;
}

.mail-item-content {
    flex: 1;
    min-width: 0;
}

.mail-item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 6px;
    gap: 12px;
}

.mail-item-from {
    font-weight: 600;
    color: #212529;
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}

.mail-item-date {
    font-size: 0.75rem;
    color: #6c757d;
    white-space: nowrap;
    flex-shrink: 0;
}

.mail-item-subject {
    font-weight: 600;
    color: #212529;
    font-size: 0.875rem;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mail-item-preview {
    font-size: 0.8125rem;
    color: #6c757d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 4px;
    line-height: 1.4;
}

/* Правая панель: Содержимое листа */
.mail-view-panel {
    background: #fff;
    border-radius: 8px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.mail-view-header {
    padding: 24px;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.mail-view-subject {
    font-size: 1.5rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 16px;
    line-height: 1.3;
}

.mail-view-meta {
    display: flex;
    justify-content: space-between;
    align-items: start;
    flex-wrap: wrap;
    gap: 16px;
}

.mail-view-info {
    flex: 1;
    min-width: 200px;
}

.mail-view-from {
    font-weight: 600;
    color: #212529;
    margin-bottom: 6px;
    font-size: 0.95rem;
}

.mail-view-to,
.mail-view-cc {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 4px;
}

.mail-view-date {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-top: 8px;
}

.mail-view-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.mail-view-actions .btn {
    font-size: 0.875rem;
    padding: 8px 16px;
    border-radius: 6px;
}

.mail-view-body {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    line-height: 1.6;
    color: #212529;
    background: #fff;
}

.mail-view-body pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 0.9375rem;
    line-height: 1.6;
}

.mail-view-body iframe {
    width: 100%;
    border: none;
    min-height: 400px;
}

/* Пустые состояния */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #6c757d;
    height: 100%;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
    color: #6c757d;
}

.empty-state h4 {
    margin-bottom: 8px;
    color: #495057;
    font-size: 1.1rem;
}

.empty-state p {
    margin-bottom: 0;
    font-size: 0.9rem;
}

.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6c757d;
}

.loading i {
    margin-right: 8px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Адаптивность */
@media (max-width: 1400px) {
    .mail-client-container {
        grid-template-columns: 240px 320px 1fr;
    }
}

@media (max-width: 1200px) {
    .mail-client-container {
        grid-template-columns: 200px 280px 1fr;
    }
}

@media (max-width: 992px) {
    .mail-client-container {
        grid-template-columns: 1fr;
        height: auto;
        min-height: auto;
    }
    
    .mail-sidebar {
        order: 1;
        max-height: 300px;
        margin-bottom: 20px;
    }
    
    .mail-list-panel {
        order: 2;
        max-height: 500px;
        margin-bottom: 20px;
    }
    
    .mail-view-panel {
        order: 3;
        min-height: 500px;
    }
}

/* Стили для модального окна */
#composeModal .modal-body {
    padding: 24px;
}

#composeModal .form-label {
    font-weight: 500;
    margin-bottom: 8px;
    color: #495057;
}

#composeModal .form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
}

#composeModal textarea {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    resize: vertical;
}
</style>

<!-- Уведомления -->
<div id="alertContainer" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;"></div>

<div class="mail-client-container">
    <!-- Левая панель: Папки -->
    <div class="mail-sidebar">
        <div class="mail-sidebar-header">
            <button type="button" class="btn btn-primary" onclick="MailClient.showComposeModal()">
                <i class="fas fa-pen me-2"></i>Написати
            </button>
        </div>
        
        <div class="mail-folders">
            <div class="mail-folders-header">
                <h6>Папки</h6>
            </div>
            <ul class="folder-list" id="folderList">
                <?php foreach ($folders as $folder): ?>
                    <li class="folder-item" 
                        data-folder="<?= htmlspecialchars($folder['slug']) ?>"
                        onclick="MailClient.loadFolder('<?= htmlspecialchars($folder['slug']) ?>')">
                        <i class="<?= htmlspecialchars($folder['icon'] ?? 'fas fa-folder') ?>"></i>
                        <span class="folder-name"><?= htmlspecialchars($folder['name']) ?></span>
                        <?php if (isset($stats[$folder['slug']])): ?>
                            <?php 
                            $unread = $stats[$folder['slug']]['unread'] ?? 0;
                            $total = $stats[$folder['slug']]['total'] ?? 0;
                            $displayCount = $unread > 0 ? $unread : $total;
                            ?>
                            <?php if ($displayCount > 0): ?>
                                <span class="folder-count"><?= $displayCount ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <!-- Средняя панель: Список листов -->
    <div class="mail-list-panel">
        <div class="mail-list-header">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="MailClient.receiveEmails()" title="Отримати пошту">
                <i class="fas fa-sync-alt me-1"></i>Оновити
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="MailClient.deleteSelected()" title="Видалити вибрані">
                <i class="fas fa-trash me-1"></i>Видалити
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="MailClient.markSelectedAsRead()" title="Позначити як прочитане">
                <i class="fas fa-envelope-open me-1"></i>Прочитане
            </button>
        </div>
        <div class="mail-list" id="mailList">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h4>Виберіть папку</h4>
                <p>Виберіть папку для перегляду листів</p>
            </div>
        </div>
    </div>
    
    <!-- Правая панель: Содержимое листа -->
    <div class="mail-view-panel" id="mailView">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <h4>Виберіть лист</h4>
            <p>Виберіть лист зі списку для перегляду</p>
        </div>
    </div>
</div>

<!-- Модальное окно написания письма -->
<div class="modal fade" id="composeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="composeForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="send_email">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-pen me-2"></i>Написати листа
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="composeTo" class="form-label">Кому <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="composeTo" name="to" required 
                               placeholder="Ім'я <email@example.com> або email@example.com">
                        <div class="form-text">Можна вказати кілька адрес через кому. Формат: "Ім'я <email@example.com>" або просто email@example.com</div>
                        <div class="invalid-feedback" id="composeToError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="composeSubject" class="form-label">Тема <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="composeSubject" name="subject" required 
                               placeholder="Тема листа">
                    </div>
                    <div class="mb-3">
                        <label for="composeBody" class="form-label">Повідомлення</label>
                        <textarea class="form-control" id="composeBody" name="body" rows="12" 
                                  placeholder="Введіть текст повідомлення..."></textarea>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="composeIsHtml" name="is_html" value="1" checked>
                            <label class="form-check-label" for="composeIsHtml">
                                HTML формат (підтримка розмітки)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Скасувати
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="MailClient.saveDraft()">
                        <i class="fas fa-save me-1"></i>Зберегти чернетку
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Відправити
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
