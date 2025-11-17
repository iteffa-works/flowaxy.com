<?php
/**
 * Шаблон страницы настройки дизайна (Customizer)
 * Вертикальное меню слева как в WordPress
 */
?>

<!-- Уведомления -->
<div id="customizerAlert" class="alert alert-dismissible fade" role="alert" style="display: none;">
    <span id="customizerAlertMessage"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$activeTheme): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Тему не активовано!</strong> Спочатку активуйте тему в розділі <a href="<?= UrlHelper::admin('themes') ?>">Теми</a>.
    </div>
<?php else: ?>

<div class="customizer-wrapper">
    <!-- Вертикальное меню слева -->
    <div class="customizer-sidebar">
        <ul class="customizer-menu">
            <?php
            $firstItem = true;
            foreach ($categories as $category => $categoryInfo):
                $hasSettings = isset($availableSettings[$category]) && !empty($availableSettings[$category]);
                // Показываем все категории, даже если нет настроек (для будущего расширения)
                $icon = $categoryInfo['icon'] ?? 'fa-cog';
                $label = $categoryInfo['label'] ?? ucfirst($category);
            ?>
                <li class="customizer-menu-item <?= $firstItem ? 'active' : '' ?>">
                    <a href="#panel-<?= $category ?>" 
                       class="customizer-menu-link" 
                       data-panel="<?= $category ?>">
                        <i class="fas <?= $icon ?>"></i>
                        <span><?= htmlspecialchars($label) ?></span>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>
                </li>
            <?php
                $firstItem = false;
            endforeach;
            ?>
        </ul>
    </div>
    
    <!-- Контент справа -->
    <div class="customizer-content">
        <?php
        $firstPanel = true;
        foreach ($categories as $category => $categoryInfo):
            if (!isset($availableSettings[$category]) || empty($availableSettings[$category])) {
                continue;
            }
        ?>
            <div class="customizer-panel <?= $firstPanel ? 'active' : '' ?>" id="panel-<?= $category ?>">
                <div class="content-section">
                    <div class="content-section-header">
                        <h5>
                            <i class="fas <?= $categoryInfo['icon'] ?? 'fa-cog' ?> me-2"></i>
                            <?= htmlspecialchars($categoryInfo['label'] ?? ucfirst($category)) ?>
                        </h5>
                        <?php if (!empty($categoryInfo['description'])): ?>
                            <p class="text-muted mb-0"><?= htmlspecialchars($categoryInfo['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="content-section-body">
                        <div class="row">
                            <?php foreach ($availableSettings[$category] as $key => $config): ?>
                                <?php
                                $type = $config['type'] ?? 'text';
                                $label = $config['label'] ?? $key;
                                $description = $config['description'] ?? '';
                                $value = $settings[$key] ?? '';
                                $colClass = ($type === 'color') ? 'col-md-4 col-lg-3' : 'col-md-6';
                                if ($type === 'checkbox') {
                                    $colClass = 'col-md-6';
                                }
                                if ($type === 'media' || $type === 'textarea') {
                                    $colClass = 'col-12';
                                }
                                ?>
                                
                                <div class="<?= $colClass ?> mb-3">
                                    <?php if ($type === 'checkbox'): ?>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input setting-input" 
                                                   type="checkbox" 
                                                   id="setting_<?= htmlspecialchars($key) ?>" 
                                                   data-key="<?= htmlspecialchars($key) ?>" 
                                                   value="1"
                                                   <?= ($value == '1') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="setting_<?= htmlspecialchars($key) ?>">
                                                <?= htmlspecialchars($label) ?>
                                            </label>
                                        </div>
                                        <?php if (!empty($description)): ?>
                                            <div class="form-text"><?= htmlspecialchars($description) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="form-group">
                                            <label for="setting_<?= htmlspecialchars($key) ?>" class="form-label">
                                                <?= htmlspecialchars($label) ?>
                                            </label>
                                            
                                            <?php if ($type === 'color'): ?>
                                                <div class="input-group">
                                                    <input type="color" 
                                                           class="form-control form-control-color setting-input" 
                                                           id="setting_<?= htmlspecialchars($key) ?>" 
                                                           data-key="<?= htmlspecialchars($key) ?>"
                                                           value="<?= htmlspecialchars($value) ?>" 
                                                           style="width: 60px; height: 38px;">
                                                    <input type="text" 
                                                           class="form-control color-text-input" 
                                                           value="<?= htmlspecialchars($value) ?>"
                                                           data-key="<?= htmlspecialchars($key) ?>"
                                                           placeholder="#000000"
                                                           maxlength="7">
                                                </div>
                                            <?php elseif ($type === 'select'): ?>
                                                <select class="form-select setting-input" 
                                                        id="setting_<?= htmlspecialchars($key) ?>" 
                                                        data-key="<?= htmlspecialchars($key) ?>">
                                                    <?php foreach ($config['options'] ?? [] as $optionValue => $optionLabel): ?>
                                                        <option value="<?= htmlspecialchars($optionValue) ?>" 
                                                                <?= ($value == $optionValue) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($optionLabel) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($type === 'media'): ?>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control media-url-input setting-input" 
                                                           id="setting_<?= htmlspecialchars($key) ?>" 
                                                           data-key="<?= htmlspecialchars($key) ?>"
                                                           value="<?= htmlspecialchars($value) ?>"
                                                           placeholder="<?= htmlspecialchars($description) ?>"
                                                           readonly>
                                                    <?php if (!empty($mediaAvailable)): ?>
                                                        <button type="button" 
                                                                class="btn btn-outline-primary media-select-btn" 
                                                                data-target="#setting_<?= htmlspecialchars($key) ?>"
                                                                data-preview="preview_<?= htmlspecialchars($key) ?>">
                                                            <i class="fas fa-images"></i> Вибрати
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-outline-secondary" 
                                                                disabled
                                                                title="Плагін для роботи з медіафайлами не встановлено">
                                                            <i class="fas fa-images"></i> Вибрати
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($value)): ?>
                                                    <div class="mt-2" id="preview_<?= htmlspecialchars($key) ?>">
                                                        <img src="<?= htmlspecialchars($value) ?>" 
                                                             alt="Preview" 
                                                             class="img-thumbnail" 
                                                             style="max-width: 200px; max-height: 100px;">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mt-2" id="preview_<?= htmlspecialchars($key) ?>" style="display: none;"></div>
                                                <?php endif; ?>
                                            <?php elseif ($type === 'textarea'): ?>
                                                <textarea class="form-control setting-input <?= ($key === 'custom_css') ? 'font-monospace' : '' ?>" 
                                                          id="setting_<?= htmlspecialchars($key) ?>" 
                                                          data-key="<?= htmlspecialchars($key) ?>" 
                                                          rows="<?= ($key === 'custom_css') ? '15' : '5' ?>" 
                                                          placeholder="<?= htmlspecialchars($description) ?>"
                                                          style="<?= ($key === 'custom_css') ? 'font-size: 0.875rem; line-height: 1.6;' : '' ?>"><?= htmlspecialchars($value) ?></textarea>
                                            <?php else: ?>
                                                <input type="text" 
                                                       class="form-control setting-input" 
                                                       id="setting_<?= htmlspecialchars($key) ?>" 
                                                       data-key="<?= htmlspecialchars($key) ?>"
                                                       value="<?= htmlspecialchars($value) ?>"
                                                       placeholder="<?= htmlspecialchars($description) ?>">
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($description) && $type !== 'checkbox'): ?>
                                                <div class="form-text"><?= htmlspecialchars($description) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            $firstPanel = false;
        endforeach;
        ?>
    </div>
</div>

<!-- Модальное окно медиагалереи (только если есть плагин с медиа-функциональностью) -->
<?php if (!empty($mediaAvailable)): ?>
<div class="modal fade" id="mediaManagerModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-fullscreen-xl-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-images me-2"></i>Вибір зображення
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="flex-grow-1 me-3">
                        <input type="text" 
                               class="form-control" 
                               id="mediaSearch" 
                               placeholder="Пошук зображень...">
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center" id="uploadImageBtn" style="min-width: 140px;">
                            <i class="fas fa-upload me-2"></i>
                            <span>Завантажити</span>
                        </button>
                        <input type="file" 
                               id="uploadImageInput" 
                               accept="image/*" 
                               style="display: none;" 
                               multiple>
                    </div>
                </div>
                
                <div id="uploadProgress" class="mb-3" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 0%"></div>
                    </div>
                    <div class="text-center mt-2">
                        <small id="uploadStatus" class="text-muted"></small>
                    </div>
                </div>
                
                <div id="mediaImagesGrid">
                    <!-- Контент буде завантажено динамічно через JS при відкритті модального вікна -->
                </div>
                <div id="mediaPagination" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<input type="hidden" id="csrfToken" value="<?= SecurityHelper::csrfToken() ?>">

<script>
(function() {
    'use strict';
    
    const csrfToken = document.getElementById('csrfToken').value;
    let currentMediaTarget = null;
    let currentMediaPreview = null;
    let saveTimeout = null;
    const autoSaveDelay = 2000;
    
    document.addEventListener('DOMContentLoaded', function() {
        initMenu();
        initColorInputs();
        initSettingInputs();
        initMediaGallery();
        initResetButton();
    });
    
    function initMenu() {
        document.querySelectorAll('.customizer-menu-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const panelId = this.dataset.panel;
                
                // Убираем активный класс со всех элементов меню
                document.querySelectorAll('.customizer-menu-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                
                // Добавляем активный класс к текущему элементу
                this.closest('.customizer-menu-item').classList.add('active');
                
                // Скрываем все панели
                document.querySelectorAll('.customizer-panel').forEach(function(panel) {
                    panel.classList.remove('active');
                });
                
                // Показываем выбранную панель
                const panel = document.getElementById('panel-' + panelId);
                if (panel) {
                    panel.classList.add('active');
                }
            });
        });
    }
    
    function initColorInputs() {
        document.querySelectorAll('.form-control-color').forEach(function(colorInput) {
            const key = colorInput.dataset.key;
            const textInput = document.querySelector('.color-text-input[data-key="' + key + '"]');
            
            if (textInput) {
                colorInput.addEventListener('input', function() {
                    textInput.value = this.value;
                    saveSetting(key, this.value);
                });
                
                textInput.addEventListener('input', function() {
                    const color = this.value;
                    if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color)) {
                        colorInput.value = color;
                        saveSetting(key, color);
                    }
                });
            }
        });
    }
    
    function initSettingInputs() {
        document.querySelectorAll('.setting-input').forEach(function(input) {
            const type = input.type || input.tagName.toLowerCase();
            const tagName = input.tagName.toLowerCase();
            
            if (type === 'checkbox') {
                input.addEventListener('change', function() {
                    const value = this.checked ? '1' : '0';
                    saveSetting(this.dataset.key, value);
                });
            } else if (tagName === 'select') {
                input.addEventListener('change', function() {
                    saveSetting(this.dataset.key, this.value);
                });
            } else if (type !== 'color' && type !== 'file' && !input.readOnly) {
                input.addEventListener('input', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(function() {
                        saveSetting(input.dataset.key, input.value);
                    }, autoSaveDelay);
                });
                
                input.addEventListener('blur', function() {
                    clearTimeout(saveTimeout);
                    saveSetting(this.dataset.key, this.value);
                });
            }
        });
    }
    
    function saveSetting(key, value) {
        const formData = new FormData();
        formData.append('action', 'save_setting');
        formData.append('key', key);
        formData.append('value', value);
        formData.append('csrf_token', csrfToken);
        
        fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showAlert('Помилка: ' + (data.error || 'Невідома помилка'), 'danger');
            }
        })
        .catch(() => {
            showAlert('Помилка збереження', 'danger');
        });
    }
    
    function initMediaGallery() {
        // Перевіряємо, чи є модальне вікно (тобто плагін встановлено)
        const mediaModal = document.getElementById('mediaManagerModal');
        if (!mediaModal) {
            return; // Плагін не встановлено, виходимо
        }
        
        // Обробка кнопок вибору медіа
        document.querySelectorAll('.media-select-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentMediaTarget = document.querySelector(this.dataset.target);
                currentMediaPreview = document.querySelector('#' + this.dataset.preview);
                const modal = new bootstrap.Modal(mediaModal);
                
                // Завантажуємо медіа через AJAX тільки при відкритті модального вікна
                if (currentMediaTarget) {
                    loadMediaSelector(currentMediaTarget.id, currentMediaPreview ? currentMediaPreview.id : '');
                }
                
                modal.show();
            });
        });
        
        // Завантаження селектора медіа
        function loadMediaSelector(targetInputId, previewContainerId) {
            const container = document.getElementById('mediaImagesGrid');
            if (!container) return;
            
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
            
            fetch('?action=get_media_images&per_page=24', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.files) {
                    renderMediaSelector(data.files, targetInputId, previewContainerId);
                } else {
                    container.innerHTML = '<div class="alert alert-warning">Не вдалося завантажити медіафайли</div>';
                }
            })
            .catch(() => {
                container.innerHTML = '<div class="alert alert-danger">Помилка завантаження</div>';
            });
        }
        
        // Рендеринг селектора медіа
        function renderMediaSelector(files, targetInputId, previewContainerId) {
            const container = document.getElementById('mediaImagesGrid');
            if (!container) return;
            
            let html = '<div class="row">';
            files.forEach(function(file) {
                const fileUrl = file.file_url || '';
                html += '<div class="col-md-2 col-sm-3 col-4 mb-3">';
                html += '<div class="media-selector-item" style="cursor: pointer;" ';
                html += 'data-url="' + escapeHtml(fileUrl) + '" ';
                html += 'data-target="' + escapeHtml(targetInputId) + '" ';
                html += 'data-preview="' + escapeHtml(previewContainerId) + '">';
                
                if (file.media_type === 'image') {
                    html += '<img src="' + escapeHtml(fileUrl) + '" alt="' + escapeHtml(file.title || '') + '" class="img-thumbnail w-100">';
                } else {
                    const icon = file.media_type === 'video' ? 'video' : (file.media_type === 'audio' ? 'music' : 'file');
                    html += '<div class="media-selector-icon text-center p-3"><i class="fas fa-' + icon + ' fa-3x"></i></div>';
                }
                html += '</div></div>';
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Обробка вибору медіа з селектора (вже відрендереного через PHP)
        document.addEventListener('click', function(e) {
            const selectorItem = e.target.closest('.media-selector-item');
            if (selectorItem) {
                const url = selectorItem.dataset.url;
                const targetId = selectorItem.dataset.target;
                const previewId = selectorItem.dataset.preview;
                
                if (url && targetId) {
                    const targetInput = document.querySelector('#' + targetId);
                    if (targetInput) {
                        targetInput.value = url;
                        saveSetting(targetInput.dataset.key, url);
                        
                        if (previewId) {
                            const previewContainer = document.querySelector('#' + previewId);
                            if (previewContainer) {
                                previewContainer.innerHTML = '<img src="' + url + '" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 100px;">';
                                previewContainer.style.display = 'block';
                            }
                        }
                    }
                }
                
                const mediaModalEl = document.getElementById('mediaManagerModal');
                const modal = mediaModalEl ? bootstrap.Modal.getInstance(mediaModalEl) : null;
                if (modal) {
                    modal.hide();
                }
            }
        });
        
        // Обробка завантаження файлів
        const uploadBtn = document.querySelector('.media-selector-upload-btn');
        const fileInput = document.querySelector('.media-selector-file-input');
        
        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    uploadMediaFiles(e.target.files);
                }
            });
        }
        
        // Обробка пошуку (debounce)
        const searchInput = document.querySelector('.media-selector-search');
        let searchTimeout = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    // Можна додати AJAX пошук, але зараз використовуємо PHP рендеринг
                    // Для оптимізації можна завантажити через AJAX тільки при необхідності
                }, 500);
            });
        }
    }
    
    function uploadMediaFiles(files) {
        const formData = new FormData();
        Array.from(files).forEach((file, index) => {
            formData.append('file' + index, file);
        });
        formData.append('action', 'upload_image');
        formData.append('csrf_token', csrfToken);
        
        const progressDiv = document.getElementById('uploadProgress');
        if (progressDiv) {
            progressDiv.style.display = 'block';
        }
        
        fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Перезавантажуємо сторінку для оновлення селектора
                location.reload();
            } else {
                showAlert('Помилка завантаження: ' + (data.error || 'Невідома помилка'), 'danger');
            }
        })
        .catch(() => {
            showAlert('Помилка завантаження', 'danger');
        })
        .finally(() => {
            if (progressDiv) {
                progressDiv.style.display = 'none';
            }
        });
    }
    
    function initResetButton() {
        const resetBtn = document.getElementById('resetSettingsBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (!confirm('Ви впевнені, що хочете скинути всі налаштування до значень за замовчуванням?')) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'reset_settings');
                formData.append('csrf_token', csrfToken);
                
                fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Налаштування скинуто', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert('Помилка: ' + (data.error || 'Невідома помилка'), 'danger');
                    }
                });
            });
        }
    }
    
    function showAlert(message, type) {
        const alert = document.getElementById('customizerAlert');
        const alertMessage = document.getElementById('customizerAlertMessage');
        
        if (alert && alertMessage) {
            alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
            alertMessage.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 3000);
        }
    }
})();
</script>

<style>
.customizer-wrapper {
    display: flex;
    gap: 0;
    margin-top: 1rem;
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
}

.customizer-sidebar {
    width: 280px;
    background: #f8f9fa;
    border-right: 1px solid #e1e5e9;
    flex-shrink: 0;
}

.customizer-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.customizer-menu-item {
    border-bottom: 1px solid #e1e5e9;
}

.customizer-menu-item:last-child {
    border-bottom: none;
}

.customizer-menu-link {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: #495057;
    text-decoration: none;
    transition: all 0.2s ease;
    gap: 12px;
}

.customizer-menu-link:hover {
    background: #e9ecef;
    color: #212529;
}

.customizer-menu-item.active .customizer-menu-link {
    background: #fff;
    color: #0d6efd;
    font-weight: 600;
    border-right: 3px solid #0d6efd;
}

.customizer-menu-link i:first-child {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.customizer-menu-link i:last-child {
    font-size: 0.75rem;
    opacity: 0.5;
}

.customizer-content {
    flex: 1;
    padding: 0;
    overflow-y: auto;
    max-height: calc(100vh - 300px);
}

.customizer-panel {
    display: none;
    padding: 24px;
}

.customizer-panel.active {
    display: block;
}

.media-image-item {
    position: relative;
    overflow: hidden;
    border-radius: 4px;
}

.media-image-item:hover {
    opacity: 0.8;
}

</style>

<?php endif; ?>
