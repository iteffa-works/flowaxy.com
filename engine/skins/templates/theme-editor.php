<?php
/**
 * Шаблон страницы редактора темы
 */

// Функція для перевірки, чи потрібно розкривати папку (чи містить вона вибраний файл)
function shouldExpandFolder($folder, $selectedFile) {
    if (empty($selectedFile)) {
        return false;
    }
    
    // Перевіряємо, чи починається шлях до вибраного файлу зі шляху папки
    $folderPath = rtrim($folder['path'], '/') . '/';
    return str_starts_with($selectedFile, $folderPath);
}

// Функція для рекурсивного відображення дерева файлів
function renderFileTree($tree, $theme, $selectedFile, $level = 0) {
    foreach ($tree as $item) {
        if ($item['type'] === 'folder') {
            $hasChildren = !empty($item['children']);
            // Розкриваємо папку, якщо вона містить вибраний файл або це перший рівень
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
                        <?php if (!empty($item['path'])): ?>
                        <button type="button" class="context-menu-btn" onclick="renameFileOrFolder(event, '<?= htmlspecialchars($item['path']) ?>', true)" title="Перейменувати папку">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="context-menu-btn context-menu-btn-danger" onclick="deleteCurrentFolder(event, '<?= htmlspecialchars($item['path']) ?>')" title="Видалити папку">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
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
                            class="context-menu-btn" 
                            onclick="renameFileOrFolder(event, '<?= htmlspecialchars($item['path']) ?>', false)"
                            title="Перейменувати файл">
                        <i class="fas fa-edit"></i>
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
                            <i class="fas fa-edit me-2"></i><?= $selectedFile ? htmlspecialchars($selectedFile) : '' ?>
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
            <!-- Placeholder (показывается если нет файла) -->
            <div class="card-body editor-placeholder-wrapper" style="<?= ($selectedFile && $fileContent !== null) ? 'display: none;' : '' ?>">
                <div class="editor-placeholder">
                    <i class="fas fa-file-code"></i>
                    <h5>Оберіть файл для редагування</h5>
                    <p>Виберіть файл зі списку зліва, щоб почати редагування</p>
                </div>
            </div>
            
            <!-- Режим: Завантаження файлів (вбудовується замість редактора) -->
            <div class="card-body editor-embedded-mode" id="upload-mode-content" style="display: none;">
                <div class="upload-files-container">
                    <div class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-dropzone-content">
                            <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-4"></i>
                            <h4 class="mb-3">Перетягніть файли або папки сюди</h4>
                            <p class="text-muted mb-4 fs-5">або</p>
                            <button type="button" class="btn btn-primary btn-lg px-4 py-2" onclick="document.getElementById('uploadFilesInput').click()">
                                <i class="fas fa-folder-open me-2"></i>Вибрати файли
                            </button>
                            <input type="file" id="uploadFilesInput" multiple webkitdirectory directory style="display: none;" onchange="handleFileSelection(this.files)">
                            <p class="text-muted mt-4 mb-0">Підтримується завантаження множинних файлів та папок</p>
                        </div>
                    </div>
                    
                    <!-- Список вибраних файлів -->
                    <div id="upload-files-list" class="upload-files-list" style="display: none;">
                        <h6 class="mb-3">Вибрані файли для завантаження:</h6>
                        <div id="upload-files-items" style="flex: 1; overflow-y: auto; min-height: 0; margin-bottom: 16px;"></div>
                        <!-- Прихований селект для цільової папки (використовується тільки програмно) -->
                        <select class="form-select d-none" id="upload-target-folder">
                            <option value="">Коренева папка теми</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Режим: Налаштування (вбудовується замість редактора) -->
            <div class="card-body editor-embedded-mode" id="settings-mode-content" style="display: none;">
                <div class="editor-settings-container">
                    <form id="editorSettingsFormInline">
                        <?= SecurityHelper::csrfField() ?>
                        
                        <!-- Налаштування відображення -->
                        <div class="settings-section">
                            <h6 class="settings-section-title">Відображення</h6>
                            <div class="settings-section-grid">
                                <div class="form-check form-switch">
                                    <label class="form-check-label" for="showEmptyFoldersInline">
                                        <strong>Показувати пусті папки</strong>
                                        <p class="text-muted small mb-0">Відображати порожні папки в дереві файлів</p>
                                    </label>
                                    <input class="form-check-input" type="checkbox" id="showEmptyFoldersInline" name="show_empty_folders" <?= (($editorSettings['show_empty_folders'] ?? '1') === '1') ? 'checked' : '' ?>>
                                </div>
                                <div class="form-check form-switch">
                                    <label class="form-check-label" for="enableSyntaxHighlightingInline">
                                        <strong>Увімкнути підсвітку коду</strong>
                                        <p class="text-muted small mb-0">Підсвітка синтаксису коду в редакторі</p>
                                    </label>
                                    <input class="form-check-input" type="checkbox" id="enableSyntaxHighlightingInline" name="enable_syntax_highlighting" <?= (($editorSettings['enable_syntax_highlighting'] ?? '1') === '1') ? 'checked' : '' ?>>
                                </div>
                                <div class="form-check form-switch">
                                    <label class="form-check-label" for="showLineNumbersInline">
                                        <strong>Показувати номери рядків</strong>
                                        <p class="text-muted small mb-0">Відображати номери рядків у редакторі</p>
                                    </label>
                                    <input class="form-check-input" type="checkbox" id="showLineNumbersInline" name="show_line_numbers" <?= (($editorSettings['show_line_numbers'] ?? '1') === '1') ? 'checked' : '' ?>>
                                </div>
                                <div class="form-check form-switch">
                                    <label class="form-check-label" for="wordWrapInline">
                                        <strong>Перенос рядків</strong>
                                        <p class="text-muted small mb-0">Автоматичний перенос довгих рядків</p>
                                    </label>
                                    <input class="form-check-input" type="checkbox" id="wordWrapInline" name="word_wrap" <?= (($editorSettings['word_wrap'] ?? '1') === '1') ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Налаштування редактора -->
                        <div class="settings-section">
                            <h6 class="settings-section-title">Редактор</h6>
                            <div class="settings-section-grid">
                                <div>
                                    <label class="form-label" for="editorFontFamilyInline">
                                        <strong>Шрифт</strong>
                                        <p class="text-muted small mb-0">Виберіть шрифт для редактора</p>
                                    </label>
                                    <select class="form-select" id="editorFontFamilyInline" name="font_family">
                                        <?php
                                        $fontFamily = $editorSettings['font_family'] ?? "'Courier New', monospace";
                                        $fontOptions = [
                                            "'Courier New', monospace" => 'Courier New',
                                            "'Menlo', monospace" => 'Menlo',
                                            "'JetBrains Mono', monospace" => 'JetBrains Mono'
                                        ];
                                        foreach ($fontOptions as $value => $label):
                                        ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= ($fontFamily === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="editorFontSizeInline">
                                        <strong>Розмір шрифту</strong>
                                        <p class="text-muted small mb-0">Розмір шрифту в пікселях (12-24px)</p>
                                    </label>
                                    <input type="number" class="form-control" id="editorFontSizeInline" name="font_size" min="12" max="24" value="<?= htmlspecialchars($editorSettings['font_size'] ?? '14') ?>" step="1">
                                </div>
                                <div>
                                    <label class="form-label" for="editorThemeInline">
                                        <strong>Тема редактора</strong>
                                        <p class="text-muted small mb-0">Кольорова схема редактора</p>
                                    </label>
                                    <select class="form-select" id="editorThemeInline" name="editor_theme">
                                        <?php
                                        $editorTheme = $editorSettings['editor_theme'] ?? 'monokai';
                                        // Тільки теми, які доступні на CDN для CodeMirror 5.65.2
                                        $themeOptions = [
                                            'monokai' => 'Monokai (темна)',
                                            'default' => 'Default (світла)'
                                        ];
                                        foreach ($themeOptions as $value => $label):
                                        ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= ($editorTheme === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="editorIndentSizeInline">
                                        <strong>Розмір відступу</strong>
                                        <p class="text-muted small mb-0">Кількість пробілів для відступу (2-8)</p>
                                    </label>
                                    <input type="number" class="form-control" id="editorIndentSizeInline" name="indent_size" min="2" max="8" value="<?= htmlspecialchars($editorSettings['indent_size'] ?? '4') ?>" step="1">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Додаткові налаштування -->
                        <div class="settings-section">
                            <h6 class="settings-section-title">Додатково</h6>
                            <div class="settings-section-grid">
                                <div class="form-check form-switch">
                                    <label class="form-check-label" for="autoSaveInline">
                                        <strong>Автозбереження</strong>
                                        <p class="text-muted small mb-0">Автоматично зберігати зміни через певний час</p>
                                    </label>
                                    <input class="form-check-input" type="checkbox" id="autoSaveInline" name="auto_save" <?= (($editorSettings['auto_save'] ?? '0') === '1') ? 'checked' : '' ?>>
                                </div>
                                <div>
                                    <label class="form-label" for="autoSaveIntervalInline">
                                        <strong>Інтервал автозбереження</strong>
                                        <p class="text-muted small mb-0">Час у секундах між автозбереженнями (30-300)</p>
                                    </label>
                                    <input type="number" class="form-control" id="autoSaveIntervalInline" name="auto_save_interval" min="30" max="300" value="<?= htmlspecialchars($editorSettings['auto_save_interval'] ?? '60') ?>" step="10" <?= (($editorSettings['auto_save'] ?? '0') === '1') ? '' : 'disabled' ?>>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Футер (завжди останній елемент) -->
            <div class="card-footer" id="editor-footer" style="<?= ($selectedFile && $fileContent !== null) ? 'display: flex;' : 'display: none;' ?>">
                <div class="d-flex justify-content-between align-items-center" style="width: 100%;">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                        <span id="editor-status-icon" class="editor-status-dot text-success me-2"></span>
                        <span class="text-muted small" id="editor-status">Готово до редагування</span>
                        <!-- Прогрес бар завантаження файлів (приховано за замовчуванням) -->
                        <div id="footer-upload-progress" class="ms-3 flex-grow-1" style="display: none; max-width: 300px;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     id="footer-upload-progress-bar" 
                                     role="progressbar" 
                                     style="width: 0%">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-shrink-0">
                        <!-- Кнопки для режиму редагування -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancel-btn" onclick="resetEditor()" style="display: none;">
                            <i class="fas fa-undo me-1"></i>Скасувати
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="editor-save-btn" onclick="saveFile()">
                            <i class="fas fa-save me-1"></i>Зберегти
                        </button>
                        
                        <!-- Кнопки для режиму завантаження (показуються коли є файли в списку) -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="upload-clear-btn" onclick="clearUploadList()" style="display: none;">
                            <i class="fas fa-times me-1"></i>Очистити список
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="upload-submit-btn" onclick="startFilesUpload()" style="display: none;">
                            <i class="fas fa-upload me-1"></i>Завантажити
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно налаштувань редактора -->
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

<!-- Модальне вікно підтвердження -->
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

