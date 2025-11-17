<?php
/**
 * Шаблон сторінки медіафайлів
 * 
 * @var array $files Список файлів
 * @var array $stats Статистика
 * @var array $filters Фільтри
 * @var int $page Поточна сторінка
 * @var int $pages Загальна кількість сторінок
 * @var string $message Повідомлення
 * @var string $messageType Тип повідомлення
 */

/**
 * Функція форматування розміру файлу
 * 
 * @param int $bytes Розмір в байтах
 * @return string Відформатований розмір
 */
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max(0, (int)$bytes);
        
        if ($bytes === 0) {
            return '0 B';
        }
        
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>

<!-- Повідомлення -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
    </div>
<?php endif; ?>

<!-- Панель інструментів - строгий плоский дизайн -->
<div class="media-toolbar">
    <div class="media-toolbar-left">
        <div class="media-view-toggle">
            <button type="button" class="media-view-btn active" data-view="grid" title="Сітка">
                <i class="fas fa-th"></i>
            </button>
            <button type="button" class="media-view-btn" data-view="list" title="Список">
                <i class="fas fa-list"></i>
            </button>
        </div>
        <div class="media-toolbar-divider"></div>
        <div class="media-bulk-actions" id="bulkActions" style="display: none;">
            <button type="button" class="media-toolbar-btn" id="selectAllBtn" title="Вибрати все">
                <i class="fas fa-check-square"></i>
                <span class="d-none d-md-inline ms-1">Вибрати все</span>
            </button>
            <button type="button" class="media-toolbar-btn" id="deselectAllBtn" title="Скасувати вибір">
                <i class="fas fa-square"></i>
                <span class="d-none d-md-inline ms-1">Скасувати</span>
            </button>
            <button type="button" class="media-toolbar-btn media-toolbar-btn-danger" id="bulkDeleteBtn" title="Видалити вибрані">
                <i class="fas fa-trash"></i>
                <span class="d-none d-md-inline ms-1">Видалити</span>
            </button>
        </div>
    </div>
    <div class="media-toolbar-right">
        <div class="media-toolbar-actions">
            <button type="button" class="media-toolbar-btn" id="refreshBtn" title="Оновити">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="media-toolbar-divider"></div>
            <select name="sort" class="media-sort-select" id="sortSelect">
                <option value="uploaded_at_desc" <?= ($filters['order_by'] ?? 'uploaded_at') === 'uploaded_at' && ($filters['order_dir'] ?? 'DESC') === 'DESC' ? 'selected' : '' ?>>Новіші спочатку</option>
                <option value="uploaded_at_asc" <?= ($filters['order_by'] ?? 'uploaded_at') === 'uploaded_at' && ($filters['order_dir'] ?? 'DESC') === 'ASC' ? 'selected' : '' ?>>Старіші спочатку</option>
                <option value="title_asc" <?= ($filters['order_by'] ?? '') === 'title' && ($filters['order_dir'] ?? '') === 'ASC' ? 'selected' : '' ?>>Назва А-Я</option>
                <option value="title_desc" <?= ($filters['order_by'] ?? '') === 'title' && ($filters['order_dir'] ?? '') === 'DESC' ? 'selected' : '' ?>>Назва Я-А</option>
                <option value="file_size_desc" <?= ($filters['order_by'] ?? '') === 'file_size' && ($filters['order_dir'] ?? '') === 'DESC' ? 'selected' : '' ?>>Розмір (більші)</option>
                <option value="file_size_asc" <?= ($filters['order_by'] ?? '') === 'file_size' && ($filters['order_dir'] ?? '') === 'ASC' ? 'selected' : '' ?>>Розмір (менші)</option>
            </select>
        </div>
        <form method="GET" action="" class="media-filters">
            <select name="type" class="media-filter-select">
                <option value="">Всі типи</option>
                <option value="image" <?= ($filters['media_type'] ?? '') === 'image' ? 'selected' : '' ?>>Зображення</option>
                <option value="video" <?= ($filters['media_type'] ?? '') === 'video' ? 'selected' : '' ?>>Відео</option>
                <option value="audio" <?= ($filters['media_type'] ?? '') === 'audio' ? 'selected' : '' ?>>Аудіо</option>
                <option value="document" <?= ($filters['media_type'] ?? '') === 'document' ? 'selected' : '' ?>>Документи</option>
            </select>
            <div class="media-search-group">
                <input type="text" name="search" class="media-search-input" placeholder="Пошук..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" aria-label="Пошук файлів">
                <button type="submit" class="media-search-btn" aria-label="Виконати пошук">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Сетка медиафайлов -->
<div class="media-grid" id="mediaGrid" data-loading="false" data-current-page="<?= $page ?? 1 ?>" data-total-pages="<?= $pages ?? 1 ?>">
    <?php if (empty($files)): ?>
        <div class="media-empty-state">
            <div class="media-empty-icon">
                <i class="fas fa-images"></i>
            </div>
            <h4 class="media-empty-title">Медіафайлів не знайдено</h4>
            <p class="media-empty-description">Завантажте перший файл, щоб почати роботу з медіа-бібліотекою</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload me-2"></i>Завантажити файли
            </button>
        </div>
    <?php else: ?>
        <!-- Режим таблицы для списка -->
        <table class="media-table" style="display: none;">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th style="width: 60px;"></th>
                    <th>Назва</th>
                    <th style="width: 120px;">Інформація</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                    <tr class="media-item" data-id="<?= $file['id'] ?>">
                        <td>
                            <input type="checkbox" class="media-checkbox" data-id="<?= $file['id'] ?>" id="media-<?= $file['id'] ?>">
                            <label for="media-<?= $file['id'] ?>" class="media-checkbox-label"></label>
                        </td>
                        <td>
                            <div class="media-thumbnail">
                                <?php if ($file['media_type'] === 'image'): ?>
                                    <img src="<?= htmlspecialchars(UrlHelper::toProtocolRelative($file['file_url'])) ?>" 
                                         alt="<?= htmlspecialchars($file['alt_text'] ?? $file['title'] ?? '') ?>">
                                <?php elseif ($file['media_type'] === 'video'): ?>
                                    <div class="media-icon video-icon">
                                        <i class="fas fa-video fa-2x"></i>
                                    </div>
                                <?php elseif ($file['media_type'] === 'audio'): ?>
                                    <div class="media-icon audio-icon">
                                        <i class="fas fa-music fa-2x"></i>
                                    </div>
                                <?php elseif ($file['media_type'] === 'document'): ?>
                                    <div class="media-icon document-icon">
                                        <i class="fas fa-file fa-2x"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="media-icon other-icon">
                                        <i class="fas fa-file fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="media-title" title="<?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>">
                                <?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="media-info-content">
                                <?php 
                                $extension = strtoupper(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                if ($file['media_type'] === 'image' && $file['width'] && $file['height']): ?>
                                    <?= $extension ?> • <?= $file['width'] ?> × <?= $file['height'] ?>
                                <?php else: ?>
                                    <?= $extension ?> • <?= formatFileSize($file['file_size']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="media-actions-list">
                                <button class="media-action-btn-list view-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Переглянути">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="media-action-btn-list edit-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Редагувати">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="media-action-btn-list delete-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Видалити">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Режим сетки -->
        <div class="row media-grid-row">
            <?php foreach ($files as $file): ?>
                <div class="media-item" data-id="<?= $file['id'] ?>">
                    <div class="card media-card">
                        <div class="media-checkbox-wrapper">
                            <input type="checkbox" class="media-checkbox" data-id="<?= $file['id'] ?>" id="media-grid-<?= $file['id'] ?>">
                            <label for="media-grid-<?= $file['id'] ?>" class="media-checkbox-label"></label>
                        </div>
                        <div class="media-thumbnail">
                            <?php if ($file['media_type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars(UrlHelper::toProtocolRelative($file['file_url'])) ?>" 
                                     alt="<?= htmlspecialchars($file['alt_text'] ?? $file['title'] ?? '') ?>">
                            <?php elseif ($file['media_type'] === 'video'): ?>
                                <div class="media-icon video-icon">
                                    <i class="fas fa-video fa-3x"></i>
                                    <div class="media-overlay">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                            <?php elseif ($file['media_type'] === 'audio'): ?>
                                <div class="media-icon audio-icon">
                                    <i class="fas fa-music fa-3x"></i>
                                </div>
                            <?php elseif ($file['media_type'] === 'document'): ?>
                                <div class="media-icon document-icon">
                                    <i class="fas fa-file fa-3x"></i>
                                </div>
                            <?php else: ?>
                                <div class="media-icon other-icon">
                                    <i class="fas fa-file fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <div class="media-actions-overlay">
                                <button class="media-action-btn view-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Переглянути">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="media-action-btn edit-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Редагувати">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="media-action-btn delete-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Видалити">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="media-title" title="<?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>">
                                <?= htmlspecialchars(mb_substr($file['title'] ?? $file['original_name'], 0, 25)) ?>
                                <?= mb_strlen($file['title'] ?? $file['original_name']) > 25 ? '...' : '' ?>
                            </div>
                            <div class="media-info-content">
                                <small class="text-muted">
                                    <?php 
                                    $extension = strtoupper(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                    if ($file['media_type'] === 'image' && $file['width'] && $file['height']): ?>
                                        <?= $extension ?> • <?= $file['width'] ?> × <?= $file['height'] ?>
                                    <?php else: ?>
                                        <?= $extension ?> • <?= formatFileSize($file['file_size']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальне вікно завантаження -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Завантажити файли</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div id="fileInputContainer" class="mb-3">
                        <input type="file" class="d-none" id="fileInput" name="file" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx" aria-describedby="fileHelp">
                    </div>
                    <div id="filePreview" class="mb-4"></div>
                    <div class="upload-fields">
                        <div class="mb-3">
                            <label for="fileTitle" class="form-label">Назва</label>
                            <input type="text" class="form-control" id="fileTitle" name="title" placeholder="Автоматично з імені файлу">
                        </div>
                        <div class="mb-3">
                            <label for="fileDescription" class="form-label">Опис</label>
                            <textarea class="form-control" id="fileDescription" name="description" rows="3" placeholder="Додайте опис файлу"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="fileAlt" class="form-label">Alt текст</label>
                            <input type="text" class="form-control" id="fileAlt" name="alt_text" placeholder="Альтернативний текст для зображення">
                        </div>
                    </div>
                    <div id="fileHelp" class="form-text text-center mt-1 mb-2">Максимальний розмір файлу: 10 MB</div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SecurityHelper::csrfToken()) ?>">
                    <input type="hidden" name="action" value="upload">
                </form>
                <div id="uploadProgress" class="progress d-none mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="uploadBtn">
                    <i class="fas fa-upload me-2" aria-hidden="true"></i>Завантажити
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно перегляду -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Деталі файлу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body p-4" id="viewModalBody">
                <!-- Контент завантажується через AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Редагувати файл</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editMediaId" name="media_id">
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Назва</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Опис</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editAlt" class="form-label">Alt текст</label>
                        <input type="text" class="form-control" id="editAlt" name="alt_text">
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SecurityHelper::csrfToken()) ?>">
                    <input type="hidden" name="action" value="update">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">
                    <i class="fas fa-save me-2" aria-hidden="true"></i>Зберегти
                </button>
            </div>
        </div>
    </div>
</div>

