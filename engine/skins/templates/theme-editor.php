<?php
/**
 * Шаблон страницы редактора темы
 */
?>

<!-- Уведомления -->
<?php
if (!empty($message)) {
    include __DIR__ . '/../components/alert.php';
    $type = $messageType ?? 'info';
    $dismissible = true;
}
?>

<div class="content-section">
    <div class="content-section-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-code me-2"></i>Редактор теми: <strong><?= htmlspecialchars($theme['name']) ?></strong>
        </span>
        <a href="<?= UrlHelper::admin('themes') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Назад до тем
        </a>
    </div>
    <div class="content-section-body">
        <div class="row">
            <div class="col-md-3">
                <div class="theme-editor-sidebar">
                    <h6 class="sidebar-title">Файли теми</h6>
                    <div class="file-list">
                        <?php if (empty($themeFiles)): ?>
                            <div class="text-muted small">Файли не знайдено</div>
                        <?php else: ?>
                            <?php foreach ($themeFiles as $file): ?>
                                <a href="<?= UrlHelper::admin('theme-editor?theme=' . urlencode($theme['slug']) . '&file=' . urlencode($file['path'])) ?>" 
                                   class="file-item <?= ($selectedFile === $file['path']) ? 'active' : '' ?>">
                                    <i class="fas fa-file-code me-2"></i>
                                    <span class="file-name"><?= htmlspecialchars($file['name']) ?></span>
                                    <span class="file-path"><?= htmlspecialchars(dirname($file['path']) ?: '.') ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="theme-editor-content">
                    <?php if ($selectedFile): ?>
                        <?php
                        $filePath = $themePath . $selectedFile;
                        if (file_exists($filePath) && is_readable($filePath)) {
                            $fileContent = file_get_contents($filePath);
                            $fileExtension = pathinfo($selectedFile, PATHINFO_EXTENSION);
                        } else {
                            $fileContent = '';
                            $fileExtension = '';
                        }
                        ?>
                        <div class="editor-header">
                            <h6><?= htmlspecialchars(basename($selectedFile)) ?></h6>
                            <span class="badge bg-secondary"><?= strtoupper($fileExtension) ?></span>
                        </div>
                        <div class="editor-wrapper">
                            <textarea id="theme-file-content" class="form-control font-monospace" rows="20" style="font-size: 0.875rem;"><?= htmlspecialchars($fileContent) ?></textarea>
                        </div>
                        <div class="editor-actions mt-3">
                            <button type="button" class="btn btn-primary" onclick="saveThemeFile()">
                                <i class="fas fa-save me-1"></i>Зберегти
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetThemeFile()">
                                <i class="fas fa-undo me-1"></i>Скасувати зміни
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="editor-placeholder">
                            <i class="fas fa-file-code fa-3x text-muted mb-3"></i>
                            <h5>Оберіть файл для редагування</h5>
                            <p class="text-muted">Виберіть файл зі списку зліва, щоб почати редагування</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.theme-editor-sidebar {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 16px;
    height: calc(100vh - 250px);
    overflow-y: auto;
}

.sidebar-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.file-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.file-item {
    display: flex;
    flex-direction: column;
    padding: 10px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: #4a5568;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.file-item:hover {
    background: #f8f9fa;
    border-color: #e1e5e9;
    color: #1a202c;
}

.file-item.active {
    background: #e7f3ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.file-item .file-name {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 2px;
}

.file-item .file-path {
    font-size: 0.75rem;
    color: #718096;
    opacity: 0.8;
}

.theme-editor-content {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    min-height: 500px;
}

.editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e1e5e9;
}

.editor-header h6 {
    margin: 0;
    font-weight: 600;
    color: #1a202c;
}

.editor-wrapper {
    margin-bottom: 16px;
}

#theme-file-content {
    font-family: 'Courier New', monospace;
    line-height: 1.6;
    resize: vertical;
}

.editor-placeholder {
    text-align: center;
    padding: 60px 20px;
    color: #718096;
}

.editor-actions {
    display: flex;
    gap: 10px;
}
</style>

<script>
let originalContent = '';

document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('theme-file-content');
    if (textarea) {
        originalContent = textarea.value;
    }
});

function saveThemeFile() {
    // Заглушка для сохранения файла
    alert('Функція збереження файлу буде реалізована пізніше');
}

function resetThemeFile() {
    const textarea = document.getElementById('theme-file-content');
    if (textarea && confirm('Скасувати всі зміни?')) {
        textarea.value = originalContent;
    }
}
</script>

