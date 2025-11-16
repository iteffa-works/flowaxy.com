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
        <strong>Тему не активовано!</strong> Спочатку активуйте тему в розділі <a href="<?= adminUrl('themes') ?>">Теми</a>.
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
                                                    <button type="button" 
                                                            class="btn btn-outline-primary media-select-btn" 
                                                            data-target="#setting_<?= htmlspecialchars($key) ?>"
                                                            data-preview="preview_<?= htmlspecialchars($key) ?>">
                                                        <i class="fas fa-images"></i> Вибрати
                                                    </button>
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

<!-- Модальное окно медиагалереи -->
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
                
                <div class="row" id="mediaImagesGrid"></div>
                <div id="mediaPagination" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrfToken" value="<?= generateCSRFToken() ?>">

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
        document.querySelectorAll('.media-select-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentMediaTarget = document.querySelector(this.dataset.target);
                currentMediaPreview = document.querySelector('#' + this.dataset.preview);
                const modal = new bootstrap.Modal(document.getElementById('mediaManagerModal'));
                modal.show();
                loadMediaImages();
            });
        });
    }
    
    function loadMediaImages(page = 1) {
        const container = document.getElementById('mediaImagesGrid');
        const url = '?action=get_media_images&page=' + page;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMediaImages(data.files);
            }
        })
        .catch(() => {});
    }
    
    function renderMediaImages(files) {
        const container = document.getElementById('mediaImagesGrid');
        container.innerHTML = '';
        
        files.forEach(file => {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-2 col-sm-3 col-4 mb-3';
            
            const imageItem = document.createElement('div');
            imageItem.className = 'media-image-item';
            imageItem.style.cursor = 'pointer';
            imageItem.dataset.url = file.file_url || '';
            imageItem.addEventListener('click', function() {
                selectMediaImage(this.dataset.url);
            });
            
            const img = document.createElement('img');
            img.src = file.file_url || '';
            img.alt = file.title || '';
            img.className = 'img-thumbnail w-100';
            
            imageItem.appendChild(img);
            colDiv.appendChild(imageItem);
            container.appendChild(colDiv);
        });
    }
    
    function selectMediaImage(url) {
        if (currentMediaTarget && url) {
            currentMediaTarget.value = url;
            saveSetting(currentMediaTarget.dataset.key, url);
            
            if (currentMediaPreview) {
                currentMediaPreview.innerHTML = '<img src="' + url + '" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 100px;">';
                currentMediaPreview.style.display = 'block';
            }
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('mediaManagerModal'));
        if (modal) {
            modal.hide();
        }
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
