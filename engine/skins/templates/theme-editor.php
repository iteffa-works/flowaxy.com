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

<div class="theme-editor-wrapper">
    <div class="theme-editor-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-code me-2"></i>Редактор теми: <strong><?= htmlspecialchars($theme['name']) ?></strong>
            </h5>
            <small class="text-muted"><?= htmlspecialchars($theme['slug']) ?></small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="createNewFile()">
                <i class="fas fa-file-plus me-1"></i>Створити файл
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="createNewDirectory()">
                <i class="fas fa-folder-plus me-1"></i>Створити папку
            </button>
            <a href="<?= UrlHelper::admin('themes') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Назад
            </a>
        </div>
    </div>
    
    <div class="row g-3">
        <!-- Боковая панель с файлами -->
        <div class="col-md-3">
            <div class="card theme-editor-sidebar">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-folder-open me-2"></i>Файли теми
                    </h6>
                </div>
                <div class="card-body p-0" style="max-height: calc(100vh - 300px); overflow-y: auto;">
                    <?php if (empty($themeFiles)): ?>
                        <div class="p-3 text-muted text-center small">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                            Файли не знайдено
                        </div>
                    <?php else: ?>
                        <div class="file-tree">
                            <?php
                            $currentDir = '';
                            foreach ($themeFiles as $file):
                                $fileDir = $file['directory'] ?? dirname($file['path']) ?: '.';
                                if ($fileDir !== $currentDir):
                                    $currentDir = $fileDir;
                                    if ($currentDir !== '.'):
                            ?>
                                <div class="file-tree-directory">
                                    <i class="fas fa-folder me-1"></i>
                                    <span class="text-muted small"><?= htmlspecialchars($currentDir) ?></span>
                                </div>
                            <?php
                                    endif;
                                endif;
                            ?>
                                <a href="<?= UrlHelper::admin('theme-editor?theme=' . urlencode($theme['slug']) . '&file=' . urlencode($file['path'])) ?>" 
                                   class="file-tree-item <?= ($selectedFile === $file['path']) ? 'active' : '' ?>"
                                   data-file="<?= htmlspecialchars($file['path']) ?>">
                                    <i class="fas fa-file-code me-2"></i>
                                    <span class="file-name"><?= htmlspecialchars($file['name']) ?></span>
                                    <?php if ($selectedFile === $file['path']): ?>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-auto" 
                                                onclick="deleteCurrentFile(event, '<?= htmlspecialchars($file['path']) ?>')"
                                                title="Видалити файл">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Область редактирования -->
        <div class="col-md-9">
            <div class="card theme-editor-content">
                <?php if ($selectedFile && $fileContent !== null): ?>
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-file-code me-2"></i><?= htmlspecialchars(basename($selectedFile)) ?>
                            </h6>
                            <small class="text-muted"><?= htmlspecialchars($selectedFile) ?></small>
                        </div>
                        <div>
                            <span class="badge bg-secondary me-2"><?= strtoupper($fileExtension) ?></span>
                            <span class="text-muted small">
                                <?= formatBytes($themeFiles[array_search($selectedFile, array_column($themeFiles, 'path'), true)]['size'] ?? 0) ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <textarea id="theme-file-editor" 
                                  data-theme="<?= htmlspecialchars($theme['slug']) ?>"
                                  data-file="<?= htmlspecialchars($selectedFile) ?>"
                                  data-extension="<?= htmlspecialchars($fileExtension) ?>"><?= htmlspecialchars($fileContent) ?></textarea>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small" id="editor-status">Готово до редагування</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetEditor()">
                                    <i class="fas fa-undo me-1"></i>Скасувати
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="saveFile()">
                                    <i class="fas fa-save me-1"></i>Зберегти
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-body">
                        <div class="editor-placeholder text-center py-5">
                            <i class="fas fa-file-code fa-4x text-muted mb-3"></i>
                            <h5>Оберіть файл для редагування</h5>
                            <p class="text-muted">Виберіть файл зі списку зліва, щоб почати редагування</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания файла -->
<div class="modal fade" id="createFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Створити новий файл</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createFileForm">
                    <?= SecurityHelper::csrfField() ?>
                    <input type="hidden" name="theme" value="<?= htmlspecialchars($theme['slug']) ?>">
                    <div class="mb-3">
                        <label for="newFilePath" class="form-label">Шлях до файлу</label>
                        <input type="text" class="form-control" id="newFilePath" name="file" 
                               placeholder="наприклад: templates/header.php" required>
                        <small class="form-text text-muted">Вкажіть відносний шлях від кореня теми</small>
                    </div>
                    <div class="mb-3">
                        <label for="newFileContent" class="form-label">Вміст файлу (опціонально)</label>
                        <textarea class="form-control font-monospace" id="newFileContent" name="content" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" onclick="submitCreateFile()">Створити</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания папки -->
<div class="modal fade" id="createDirectoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Створити нову папку</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createDirectoryForm">
                    <?= SecurityHelper::csrfField() ?>
                    <input type="hidden" name="theme" value="<?= htmlspecialchars($theme['slug']) ?>">
                    <div class="mb-3">
                        <label for="newDirectoryPath" class="form-label">Шлях до папки</label>
                        <input type="text" class="form-control" id="newDirectoryPath" name="dir" 
                               placeholder="наприклад: templates/layouts" required>
                        <small class="form-text text-muted">Вкажіть відносний шлях від кореня теми</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" onclick="submitCreateDirectory()">Створити</button>
            </div>
        </div>
    </div>
</div>

<style>
.theme-editor-wrapper {
    padding: 0;
}

.theme-editor-sidebar {
    height: calc(100vh - 250px);
}

.file-tree {
    padding: 8px;
}

.file-tree-directory {
    padding: 6px 12px;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-top: 8px;
    margin-bottom: 4px;
}

.file-tree-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    color: #495057;
    text-decoration: none;
    border-radius: 4px;
    margin-bottom: 2px;
    transition: all 0.15s ease;
}

.file-tree-item:hover {
    background-color: #f8f9fa;
    color: #0073aa;
}

.file-tree-item.active {
    background-color: #e7f3ff;
    color: #0073aa;
    font-weight: 500;
}

.file-tree-item .file-name {
    flex: 1;
    font-size: 0.875rem;
}

.theme-editor-content {
    min-height: calc(100vh - 250px);
}

#theme-file-editor {
    width: 100%;
    min-height: 600px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.6;
    border: none;
    padding: 16px;
    resize: vertical;
}

.editor-placeholder {
    padding: 60px 20px;
}

.CodeMirror {
    height: 600px;
    font-size: 14px;
    border: none;
}
</style>

<script>
let codeEditor = null;
let originalContent = '';
let isModified = false;

document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('theme-file-editor');
    if (textarea) {
        const extension = textarea.getAttribute('data-extension');
        const mode = getCodeMirrorMode(extension);
        
        codeEditor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: mode,
            theme: 'monokai',
            indentUnit: 4,
            indentWithTabs: false,
            lineWrapping: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            foldGutter: true,
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
        });
        
        originalContent = codeEditor.getValue();
        
        codeEditor.on('change', function() {
            isModified = codeEditor.getValue() !== originalContent;
            updateEditorStatus();
        });
    }
});

function getCodeMirrorMode(extension) {
    const modes = {
        'php': 'application/x-httpd-php',
        'js': 'javascript',
        'json': {name: 'javascript', json: true},
        'css': 'css',
        'html': 'htmlmixed',
        'htm': 'htmlmixed',
        'xml': 'xml',
        'yaml': 'yaml',
        'yml': 'yaml',
        'md': 'markdown'
    };
    
    return modes[extension.toLowerCase()] || 'text/plain';
}

function updateEditorStatus() {
    const statusEl = document.getElementById('editor-status');
    if (statusEl) {
        if (isModified) {
            statusEl.textContent = 'Є незбережені зміни';
            statusEl.className = 'text-warning small';
        } else {
            statusEl.textContent = 'Готово до редагування';
            statusEl.className = 'text-muted small';
        }
    }
}

function saveFile() {
    if (!codeEditor) {
        alert('Редактор не ініціалізовано');
        return;
    }
    
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea.getAttribute('data-theme');
    const file = textarea.getAttribute('data-file');
    const content = codeEditor.getValue();
    
    const formData = new FormData();
    formData.append('action', 'save_file');
    formData.append('theme', theme);
    formData.append('file', file);
    formData.append('content', content);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            originalContent = content;
            isModified = false;
            updateEditorStatus();
            showNotification('Файл успішно збережено', 'success');
        } else {
            showNotification(data.error || 'Помилка збереження', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка збереження файлу', 'danger');
    });
}

function resetEditor() {
    if (!codeEditor) return;
    
    if (isModified && confirm('Скасувати всі зміни?')) {
        codeEditor.setValue(originalContent);
        isModified = false;
        updateEditorStatus();
    }
}

function createNewFile() {
    const modal = new bootstrap.Modal(document.getElementById('createFileModal'));
    modal.show();
}

function submitCreateFile() {
    const form = document.getElementById('createFileForm');
    const formData = new FormData(form);
    formData.append('action', 'create_file');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Файл успішно створено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createFileModal')).hide();
            setTimeout(() => {
                window.location.href = window.location.href.split('&file=')[0] + '&file=' + encodeURIComponent(data.path);
            }, 500);
        } else {
            showNotification(data.error || 'Помилка створення', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка створення файлу', 'danger');
    });
}

function createNewDirectory() {
    const modal = new bootstrap.Modal(document.getElementById('createDirectoryModal'));
    modal.show();
}

function submitCreateDirectory() {
    const form = document.getElementById('createDirectoryForm');
    const formData = new FormData(form);
    formData.append('action', 'create_directory');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Папку успішно створено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createDirectoryModal')).hide();
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showNotification(data.error || 'Помилка створення', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка створення папки', 'danger');
    });
}

function deleteCurrentFile(event, filePath) {
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('Ви впевнені, що хочете видалити цей файл? Цю дію неможливо скасувати.')) {
        return;
    }
    
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea.getAttribute('data-theme');
    
    const formData = new FormData();
    formData.append('action', 'delete_file');
    formData.append('theme', theme);
    formData.append('file', filePath);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Файл успішно видалено', 'success');
            setTimeout(() => {
                window.location.href = window.location.href.split('&file=')[0];
            }, 500);
        } else {
            showNotification(data.error || 'Помилка видалення', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка видалення файлу', 'danger');
    });
}

function showNotification(message, type) {
    // Используем существующую систему уведомлений
    if (typeof showAlert === 'function') {
        showAlert(message, type);
    } else {
        alert(message);
    }
}

// Предупреждение при уходе со страницы с несохраненными изменениями
window.addEventListener('beforeunload', function(e) {
    if (isModified) {
        e.preventDefault();
        e.returnValue = 'У вас є незбережені зміни. Ви впевнені, що хочете покинути сторінку?';
    }
});
</script>

