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
                <a href="<?= $fileUrl ?>" 
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
                <h6 class="mb-0">
                    <i class="fas fa-folder-open me-2"></i>Файли теми
                </h6>
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
        </div>
        
        <!-- Область редактирования -->
        <div class="theme-editor-main">
            <?php if ($selectedFile && $fileContent !== null): ?>
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
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
                </div>
                <div class="card-body">
                    <textarea id="theme-file-editor" 
                              data-theme="<?= htmlspecialchars($theme['slug']) ?>"
                              data-file="<?= htmlspecialchars($selectedFile) ?>"
                              data-extension="<?= htmlspecialchars($fileExtension) ?>"><?= htmlspecialchars($fileContent) ?></textarea>
                </div>
                <div class="card-footer">
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
                    <div class="editor-placeholder">
                        <i class="fas fa-file-code"></i>
                        <h5>Оберіть файл для редагування</h5>
                        <p>Виберіть файл зі списку зліва, щоб почати редагування</p>
                    </div>
                </div>
            <?php endif; ?>
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
                    <input type="hidden" id="createFileFolder" name="folder" value="">
                    <div class="mb-3">
                        <label for="newFilePath" class="form-label">Назва файлу</label>
                        <input type="text" class="form-control" id="newFilePath" name="file" 
                               placeholder="наприклад: header.php" required>
                        <small class="form-text text-muted">Вкажіть назву файлу (шлях буде додано автоматично)</small>
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
                    <input type="hidden" id="createDirectoryFolder" name="folder" value="">
                    <div class="mb-3">
                        <label for="newDirectoryPath" class="form-label">Назва папки</label>
                        <input type="text" class="form-control" id="newDirectoryPath" name="directory" 
                               placeholder="наприклад: layouts" required>
                        <small class="form-text text-muted">Вкажіть назву папки (шлях буде додано автоматично)</small>
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

<!-- Модальное окно загрузки файла -->
<div class="modal fade" id="uploadFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Завантажити файл</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadFileForm" enctype="multipart/form-data">
                    <?= SecurityHelper::csrfField() ?>
                    <input type="hidden" name="theme" value="<?= htmlspecialchars($theme['slug']) ?>">
                    <input type="hidden" id="uploadFileFolder" name="folder" value="">
                    <div class="mb-3">
                        <label for="uploadFileInput" class="form-label">Виберіть файл</label>
                        <input type="file" class="form-control" id="uploadFileInput" name="file" required>
                        <small class="form-text text-muted">Можна завантажити зображення, відео, скрипти та інші файли</small>
                    </div>
                    <div id="uploadFileProgress" class="progress d-none mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" onclick="submitUploadFile()">Завантажити</button>
            </div>
        </div>
    </div>
</div>
