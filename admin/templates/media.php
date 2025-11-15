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

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Всього файлів
                </div>
                <div class="h4 mb-0 font-weight-bold"><?= $stats['total_files'] ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-info">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                    Зображення
                </div>
                <div class="h4 mb-0 font-weight-bold"><?= $stats['by_type']['image'] ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    Відео
                </div>
                <div class="h4 mb-0 font-weight-bold"><?= $stats['by_type']['video'] ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    Документи
                </div>
                <div class="h4 mb-0 font-weight-bold"><?= ($stats['by_type']['document'] ?? 0) + ($stats['by_type']['audio'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Панель инструментов -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="btn-toolbar" role="toolbar">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload me-2"></i>Завантажити файли
                    </button>
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <form method="GET" action="" class="d-flex">
                    <select name="type" class="form-select me-2" style="max-width: 150px;">
                        <option value="">Всі типи</option>
                        <option value="image" <?= ($filters['media_type'] ?? '') === 'image' ? 'selected' : '' ?>>Зображення</option>
                        <option value="video" <?= ($filters['media_type'] ?? '') === 'video' ? 'selected' : '' ?>>Відео</option>
                        <option value="audio" <?= ($filters['media_type'] ?? '') === 'audio' ? 'selected' : '' ?>>Аудіо</option>
                        <option value="document" <?= ($filters['media_type'] ?? '') === 'document' ? 'selected' : '' ?>>Документи</option>
                    </select>
                    <input type="text" name="search" class="form-control me-2" placeholder="Пошук..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" aria-label="Пошук файлів">
                    <button type="submit" class="btn btn-outline-primary" aria-label="Виконати пошук">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Сетка медиафайлов -->
<div class="media-grid" id="mediaGrid">
    <?php if (empty($files)): ?>
        <div class="text-center py-5">
            <i class="fas fa-images fa-4x text-muted mb-3"></i>
            <h4>Медіафайлів не знайдено</h4>
            <p class="text-muted">Завантажте перший файл, щоб почати</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload me-2"></i>Завантажити файли
            </button>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($files as $file): ?>
                <div class="col-md-2 col-sm-3 col-6 media-item" data-id="<?= $file['id'] ?>">
                    <div class="card media-card">
                        <div class="media-thumbnail">
                            <?php if ($file['media_type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars(toProtocolRelativeUrl($file['file_url'])) ?>" 
                                     alt="<?= htmlspecialchars($file['alt_text'] ?? $file['title'] ?? '') ?>"
                                     class="img-fluid"
                                     loading="lazy">
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
                        </div>
                        <div class="card-body p-2">
                            <div class="media-title" title="<?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>">
                                <?= htmlspecialchars(mb_substr($file['title'] ?? $file['original_name'], 0, 20)) ?>
                                <?= mb_strlen($file['title'] ?? $file['original_name']) > 20 ? '...' : '' ?>
                            </div>
                            <div class="media-actions">
                                <button class="btn btn-sm btn-outline-primary view-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Переглянути">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info edit-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Редагувати">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-media" 
                                        data-id="<?= $file['id'] ?>"
                                        title="Видалити">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="media-info">
                            <small class="text-muted">
                                <?php if ($file['media_type'] === 'image' && $file['width'] && $file['height']): ?>
                                    <?= $file['width'] ?> × <?= $file['height'] ?>
                                <?php else: ?>
                                    <?= formatFileSize($file['file_size']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Пагінація -->
        <?php if ($pages > 1): ?>
            <nav aria-label="Навігація по сторінкам" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&type=<?= htmlspecialchars(urlencode($filters['media_type'] ?? '')) ?>&search=<?= htmlspecialchars(urlencode($filters['search'] ?? '')) ?>" aria-label="Попередня сторінка">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($pages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&type=<?= htmlspecialchars(urlencode($filters['media_type'] ?? '')) ?>&search=<?= htmlspecialchars(urlencode($filters['search'] ?? '')) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= htmlspecialchars(urlencode($filters['media_type'] ?? '')) ?>&search=<?= htmlspecialchars(urlencode($filters['search'] ?? '')) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $pages): ?>
                        <?php if ($endPage < $pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $pages ?>&type=<?= htmlspecialchars(urlencode($filters['media_type'] ?? '')) ?>&search=<?= htmlspecialchars(urlencode($filters['search'] ?? '')) ?>"><?= $pages ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&type=<?= htmlspecialchars(urlencode($filters['media_type'] ?? '')) ?>&search=<?= htmlspecialchars(urlencode($filters['search'] ?? '')) ?>" aria-label="Наступна сторінка">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Модальне вікно завантаження -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Завантажити файли</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="fileInput" class="form-label">Виберіть файли</label>
                        <input type="file" class="form-control" id="fileInput" name="file" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx" aria-describedby="fileHelp">
                        <div id="fileHelp" class="form-text">Максимальний розмір файлу: 10 MB</div>
                    </div>
                    <div id="filePreview" class="mb-3"></div>
                    <div class="mb-3">
                        <label for="fileTitle" class="form-label">Назва <span class="text-muted">(опціонально)</span></label>
                        <input type="text" class="form-control" id="fileTitle" name="title" placeholder="Автоматично з імені файлу">
                    </div>
                    <div class="mb-3">
                        <label for="fileDescription" class="form-label">Опис <span class="text-muted">(опціонально)</span></label>
                        <textarea class="form-control" id="fileDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="fileAlt" class="form-label">Alt текст <span class="text-muted">(для зображень)</span></label>
                        <input type="text" class="form-control" id="fileAlt" name="alt_text">
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
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
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Деталі файлу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Контент завантажується через AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
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

