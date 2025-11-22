<?php
/**
 * Шаблон страницы редактора темы
 */

// Функция для проверки, нужно ли раскрывать папку (содержит ли она выбранный файл)
function shouldExpandFolder($folder, $selectedFile) {
    if (empty($selectedFile)) {
        return false;
    }
    
    // Проверяем, начинается ли путь к выбранному файлу с пути папки
    $folderPath = rtrim($folder['path'], '/') . '/';
    return str_starts_with($selectedFile, $folderPath);
}

// Функция для рекурсивного отображения дерева файлов
function renderFileTree($tree, $theme, $selectedFile, $level = 0) {
    foreach ($tree as $item) {
        if ($item['type'] === 'folder') {
            $hasChildren = !empty($item['children']);
            // Раскрываем папку, если она содержит выбранный файл или это первый уровень
            $isExpanded = $hasChildren && ($level === 0 || shouldExpandFolder($item, $selectedFile));
            ?>
            <div class="file-tree-folder" data-folder-path="<?= htmlspecialchars($item['path']) ?>">
                <div class="file-tree-folder-header <?= $isExpanded ? 'expanded' : '' ?>" data-folder-path="<?= htmlspecialchars($item['path']) ?>">
                    <i class="fas fa-chevron-right folder-icon"></i>
                    <i class="fas fa-folder file-icon"></i>
                    <span class="file-name"><?= htmlspecialchars($item['name']) ?></span>
                    <div class="file-tree-context-menu">
                        <button type="button" class="context-menu-btn" onclick="createNewFileInFolder(event, '<?= htmlspecialchars($item['path']) ?>')" title="Створити файл">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="context-menu-btn" onclick="createNewDirectoryInFolder(event, '<?= htmlspecialchars($item['path']) ?>')" title="Створити папку">
                            <i class="fas fa-folder"></i>
                        </button>
                        <button type="button" class="context-menu-btn" onclick="uploadFileToFolder(event, '<?= htmlspecialchars($item['path']) ?>')" title="Завантажити файл">
                            <i class="fas fa-upload"></i>
                        </button>
                        <button type="button" class="context-menu-btn" onclick="downloadFolder(event, '<?= htmlspecialchars($item['path']) ?>')" title="Скачати папку">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <?php if ($hasChildren): ?>
                    <div class="file-tree-folder-content <?= $isExpanded ? 'expanded' : '' ?>">
                        <?php renderFileTree($item['children'], $theme, $selectedFile, $level + 1); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        } else {
            $isActive = ($selectedFile === $item['path']);
            $fileUrl = UrlHelper::admin('theme-editor?theme=' . urlencode($theme['slug']) . '&file=' . urlencode($item['path']));
            ?>
            <div class="file-tree-item-wrapper <?= $isActive ? 'active' : '' ?>" data-file-path="<?= htmlspecialchars($item['path']) ?>">
                <a href="#" 
                   class="file-tree-item"
                   data-file="<?= htmlspecialchars($item['path']) ?>">
                    <i class="fas fa-file-code file-icon"></i>
                    <span class="file-name"><?= htmlspecialchars($item['name']) ?></span>
                </a>
                <div class="file-tree-context-menu">
                    <button type="button" 
                            class="context-menu-btn" 
                            onclick="downloadFile(event, '<?= htmlspecialchars($item['path']) ?>')"
                            title="Скачати файл">
                        <i class="fas fa-download"></i>
                    </button>
                    <button type="button" 
                            class="context-menu-btn context-menu-btn-danger" 
                            onclick="deleteCurrentFile(event, '<?= htmlspecialchars($item['path']) ?>')"
                            title="Видалити файл">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php
        }
    }
}
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
    <div class="theme-editor-content-wrapper">
        <!-- Боковая панель с файлами -->
        <div class="theme-editor-sidebar">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-folder me-2"></i>ФАЙЛИ ТЕМИ
                    </h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary theme-files-btn" onclick="refreshFileTree()" title="Оновити">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary theme-files-btn" onclick="openEditorSettings()" title="Налаштування">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($fileTree)): ?>
                    <div class="p-3 text-muted text-center small">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                        Файли не знайдено
                    </div>
                <?php else: ?>
                    <div class="file-tree">
                        <!-- Корневая папка темы (всегда открыта) -->
                        <div class="file-tree-folder file-tree-root" data-folder-path="">
                            <div class="file-tree-folder-header expanded" data-folder-path="">
                                <i class="fas fa-chevron-down folder-icon"></i>
                                <i class="fas fa-folder file-icon"></i>
                                <span class="file-name"><?= htmlspecialchars($theme['name']) ?></span>
                                <div class="file-tree-context-menu">
                                    <button type="button" class="context-menu-btn" onclick="createNewFileInFolder(event, '')" title="Створити файл">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="context-menu-btn" onclick="createNewDirectoryInFolder(event, '')" title="Створити папку">
                                        <i class="fas fa-folder"></i>
                                    </button>
                                    <button type="button" class="context-menu-btn" onclick="uploadFileToFolder(event, '')" title="Завантажити файл">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="file-tree-folder-content expanded">
                                <?php renderFileTree($fileTree, $theme, $selectedFile, 1); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Футер боковой панели -->
            <div class="card-footer theme-editor-sidebar-footer">
                <div class="sidebar-footer-content">
                    <span class="sidebar-footer-text">
                        <a href="#" class="sidebar-footer-link">Theme Editor</a> <span class="sidebar-footer-separator">•</span> v1.0.0 Dev
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Область редактирования -->
        <div class="theme-editor-main">
            <!-- Структура редактора (всегда присутствует, скрывается если нет файла) -->
            <div class="card-header" id="editor-header" style="<?= ($selectedFile && $fileContent !== null) ? '' : 'display: none;' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="editor-file-path-container">
                        <h6 class="mb-0 editor-file-title">
                            <i class="fas fa-file-code me-2"></i><?= $selectedFile ? htmlspecialchars($selectedFile) : '' ?>
                        </h6>
                    </div>
                    <div>
                        <span class="badge bg-secondary me-2" id="editor-extension"><?= $selectedFile ? strtoupper($fileExtension ?? '') : '' ?></span>
                        <span class="text-muted small" id="editor-size">
                            <?= ($selectedFile && isset($themeFiles)) ? formatBytes($themeFiles[array_search($selectedFile, array_column($themeFiles, 'path'), true)]['size'] ?? 0) : '' ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body" id="editor-body" style="<?= ($selectedFile && $fileContent !== null) ? '' : 'display: none;' ?>">
                <textarea id="theme-file-editor" 
                          data-theme="<?= htmlspecialchars($theme['slug']) ?>"
                          data-file="<?= $selectedFile ? htmlspecialchars($selectedFile) : '' ?>"
                          data-extension="<?= $selectedFile ? htmlspecialchars($fileExtension ?? '') : '' ?>"
                          data-syntax-highlighting="<?= ($enableSyntaxHighlighting ?? true) ? '1' : '0' ?>"><?= ($selectedFile && $fileContent !== null) ? htmlspecialchars($fileContent) : '' ?></textarea>
            </div>
            <div class="card-footer" id="editor-footer" style="<?= ($selectedFile && $fileContent !== null) ? '' : 'display: none;' ?>">
                <div class="d-flex justify-content-between align-items-center" style="width: 100%;">
                    <div class="d-flex align-items-center">
                        <span id="editor-status-icon" class="editor-status-dot text-success me-2"></span>
                        <span class="text-muted small" id="editor-status">Готово до редагування</span>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancel-btn" onclick="resetEditor()" style="display: none;">
                            <i class="fas fa-undo me-1"></i>Скасувати
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="saveFile()">
                            <i class="fas fa-save me-1"></i>Зберегти
                        </button>
                    </div>
                </div>
            </div>
            <!-- Placeholder (показывается если нет файла) -->
            <div class="card-body editor-placeholder-wrapper" style="<?= ($selectedFile && $fileContent !== null) ? 'display: none;' : '' ?>">
                <div class="editor-placeholder">
                    <i class="fas fa-file-code"></i>
                    <h5>Оберіть файл для редагування</h5>
                    <p>Виберіть файл зі списку зліва, щоб почати редагування</p>
                </div>
            </div>
            
            <!-- Режим: Загрузка файлов (встраивается вместо редактора) -->
            <div class="card-body editor-embedded-mode" id="upload-mode-content" style="display: none;">
                <div class="upload-files-container">
                    <div class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-dropzone-content">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>Перетягніть файли або папки сюди</h5>
                            <p class="text-muted mb-3">або</p>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadFilesInput').click()">
                                <i class="fas fa-folder-open me-2"></i>Вибрати файли
                            </button>
                            <input type="file" id="uploadFilesInput" multiple webkitdirectory directory style="display: none;" onchange="handleFileSelection(this.files)">
                            <p class="text-muted small mt-3 mb-0">Підтримується завантаження множинних файлів та папок</p>
                        </div>
                    </div>
                    
                    <!-- Список выбранных файлов -->
                    <div id="upload-files-list" class="upload-files-list mt-4" style="display: none;">
                        <h6 class="mb-3">Вибрані файли для завантаження:</h6>
                        <div id="upload-files-items"></div>
                        <div class="mt-3">
                            <label class="form-label">Виберіть цільову папку:</label>
                            <select class="form-select" id="upload-target-folder">
                                <option value="">Коренева папка теми</option>
                            </select>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="startFilesUpload()">
                                <i class="fas fa-upload me-2"></i>Завантажити файли
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearUploadList()">
                                <i class="fas fa-times me-2"></i>Очистити список
                            </button>
                        </div>
                        <div id="upload-progress-container" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar" id="upload-progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p class="text-muted small mt-2 mb-0" id="upload-progress-text">Готується до завантаження...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Режим: Настройки (встраивается вместо редактора) -->
            <div class="card-body editor-embedded-mode" id="settings-mode-content" style="display: none;">
                <div class="editor-settings-container">
                    <h5 class="mb-4">
                        <i class="fas fa-cog me-2"></i>Налаштування редактора
                    </h5>
                    <form id="editorSettingsFormInline">
                        <?= SecurityHelper::csrfField() ?>
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="showEmptyFoldersInline" name="show_empty_folders">
                                <label class="form-check-label" for="showEmptyFoldersInline">
                                    <strong>Показувати пусті папки</strong>
                                    <p class="text-muted small mb-0">Відображати порожні папки в дереві файлів</p>
                                </label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableSyntaxHighlightingInline" name="enable_syntax_highlighting" checked>
                                <label class="form-check-label" for="enableSyntaxHighlightingInline">
                                    <strong>Увімкнути підсвітку коду</strong>
                                    <p class="text-muted small mb-0">Підсвітка синтаксису коду в редакторі</p>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно настроек редактора -->
<div class="modal fade" id="editorSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog me-2"></i>Налаштування редактора
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editorSettingsForm">
                    <?= SecurityHelper::csrfField() ?>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showEmptyFolders" name="show_empty_folders">
                            <label class="form-check-label" for="showEmptyFolders">
                                Показувати пусті папки
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enableSyntaxHighlighting" name="enable_syntax_highlighting" checked>
                            <label class="form-check-label" for="enableSyntaxHighlighting">
                                Увімкнути підсвітку коду
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения -->
<div class="modal fade" id="confirmDialogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDialogTitle">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>Підтвердження
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmDialogMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-danger" id="confirmDialogButton">Підтвердити</button>
            </div>
        </div>
    </div>
</div>

