<?php
/**
 * Шаблон страницы редактора темы
 */

// Функция для рекурсивного отображения дерева файлов
function renderFileTree($tree, $theme, $selectedFile, $level = 0) {
    foreach ($tree as $item) {
        if ($item['type'] === 'folder') {
            $hasChildren = !empty($item['children']);
            $isExpanded = $hasChildren && $level === 0; // По умолчанию раскрываем первый уровень
            ?>
            <div class="file-tree-folder">
                <div class="file-tree-folder-header <?= $isExpanded ? 'expanded' : '' ?>" data-folder-path="<?= htmlspecialchars($item['path']) ?>">
                    <i class="fas fa-chevron-right folder-icon"></i>
                    <i class="fas fa-folder file-icon"></i>
                    <span class="file-name"><?= htmlspecialchars($item['name']) ?></span>
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
            <div class="file-tree-item-wrapper <?= $isActive ? 'active' : '' ?>">
                <a href="<?= $fileUrl ?>" 
                   class="file-tree-item"
                   data-file="<?= htmlspecialchars($item['path']) ?>">
                    <i class="fas fa-file-code file-icon"></i>
                    <span class="file-name"><?= htmlspecialchars($item['name']) ?></span>
                </a>
                <?php if ($isActive): ?>
                    <button type="button" 
                            class="file-delete-btn" 
                            onclick="deleteCurrentFile(event, '<?= htmlspecialchars($item['path']) ?>')"
                            title="Видалити файл">
                        <i class="fas fa-trash"></i>
                    </button>
                <?php endif; ?>
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
                <div class="file-tree-actions mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="createNewFile()">
                        <i class="fas fa-file-plus me-1"></i>Створити файл
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="createNewDirectory()">
                        <i class="fas fa-folder-plus me-1"></i>Створити папку
                    </button>
                </div>
                <?php if (empty($fileTree)): ?>
                    <div class="p-3 text-muted text-center small">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                        Файли не знайдено
                    </div>
                <?php else: ?>
                    <div class="file-tree">
                        <?php renderFileTree($fileTree, $theme, $selectedFile); ?>
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
