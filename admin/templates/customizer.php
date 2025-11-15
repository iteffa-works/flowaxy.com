<?php
/**
 * Шаблон страницы настройки дизайна (Customizer)
 * С табами и AJAX поддержкой
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

<!-- Табы -->
<ul class="nav nav-tabs" id="customizerTabs" role="tablist" style="margin-top: 0.5rem; margin-bottom: 0.5rem;">
    <?php
    $categoryLabels = [
        'colors' => ['icon' => 'fa-palette', 'label' => 'Кольори'],
        'fonts' => ['icon' => 'fa-font', 'label' => 'Шрифти'],
        'sizes' => ['icon' => 'fa-ruler', 'label' => 'Розміри'],
        'other' => ['icon' => 'fa-cog', 'label' => 'Логотип та інше'],
    ];
    
    $firstTab = true;
    foreach ($categoryLabels as $category => $categoryInfo):
        if (!isset($availableSettings[$category]) || empty($availableSettings[$category])) {
            continue;
        }
    ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $firstTab ? 'active' : '' ?>" 
                    id="tab-<?= $category ?>" 
                    data-bs-toggle="tab" 
                    data-bs-target="#panel-<?= $category ?>" 
                    type="button" 
                    role="tab">
                <i class="fas <?= $categoryInfo['icon'] ?> me-2"></i><?= $categoryInfo['label'] ?>
            </button>
        </li>
    <?php
        $firstTab = false;
    endforeach;
    ?>
</ul>

<!-- Контент табов -->
<div class="tab-content" id="customizerTabContent">
    <?php
    $firstTab = true;
    foreach ($categoryLabels as $category => $categoryInfo):
        if (!isset($availableSettings[$category]) || empty($availableSettings[$category])) {
            continue;
        }
    ?>
        <div class="tab-pane <?= $firstTab ? 'show active' : '' ?>" 
             id="panel-<?= $category ?>" 
             role="tabpanel">
            
            <div class="content-section">
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
                            if ($type === 'media') {
                                $colClass = 'col-12';
                            }
                            ?>
                            
                            <div class="<?= $colClass ?> mb-3 <?= ($key === 'logo_type' || $key === 'logo_url' || $key === 'logo_icon_url' || $key === 'logo_text') ? 'logo-field' : '' ?> <?= ($key === 'logo_type') ? 'logo-type-field' : '' ?> <?= ($key === 'logo_url') ? 'logo-url-field' : '' ?> <?= ($key === 'logo_icon_url') ? 'logo-icon-field' : '' ?> <?= ($key === 'logo_text') ? 'logo-text-field' : '' ?>" 
                                 <?= ($key === 'logo_url') ? 'style="display: none;"' : '' ?>
                                 <?= ($key === 'logo_icon_url') ? 'style="display: none;"' : '' ?>
                                 <?= ($key === 'logo_text') ? 'style="display: none;"' : '' ?>>
                                <?php if ($type === 'checkbox'): ?>
                                    <div class="form-check form-switch">
                                        <label for="setting_<?= htmlspecialchars($key) ?>" class="form-check-label form-label-checkbox">
                                            <?= htmlspecialchars($label) ?>
                                        </label>
                                        <div class="form-check-wrapper">
                                            <input class="form-check-input setting-input" 
                                                   type="checkbox" 
                                                   id="setting_<?= htmlspecialchars($key) ?>" 
                                                   data-key="<?= htmlspecialchars($key) ?>"
                                                   value="1"
                                                   <?= ($value == '1') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="setting_<?= htmlspecialchars($key) ?>">
                                                <?= htmlspecialchars($description ?: $label) ?>
                                            </label>
                                        </div>
                                    </div>
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
                    
                    <!-- Кнопка сохранения для вкладки -->
                    <div class="mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-primary save-tab-btn" data-category="<?= $category ?>">
                            <i class="fas fa-save me-2"></i>Зберегти зміни
                        </button>
                        <span class="ms-3 text-muted save-status" id="saveStatus-<?= $category ?>" style="display: none;"></span>
                    </div>
                </div>
            </div>
        </div>
    <?php
        $firstTab = false;
    endforeach;
    ?>
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
                <!-- Кнопка загрузки и поиск -->
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
            
                <!-- Прогресс загрузки -->
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
                
                <!-- Сетка изображений -->
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
    
    // Глобальные переменные
    const csrfToken = document.getElementById('csrfToken').value;
    let currentMediaTarget = null;
    let currentMediaPreview = null;
    let saveTimeout = null;
    const autoSaveDelay = 2000; // 2 секунды задержки для автосохранения
    
    // Инициализация
document.addEventListener('DOMContentLoaded', function() {
        initColorInputs();
        initSettingInputs();
        initLogoTypeToggle();
        initMediaGallery();
        initResetButton();
        initSaveButtons();
    });
    
    // Инициализация цветовых полей
    function initColorInputs() {
        document.querySelectorAll('.form-control-color').forEach(function(colorInput) {
            const key = colorInput.dataset.key;
            const textInput = document.querySelector('.color-text-input[data-key="' + key + '"]');
            
            if (textInput) {
                // Синхронизация color -> text
                colorInput.addEventListener('input', function() {
                    textInput.value = this.value;
                    saveSetting(key, this.value);
                });
                
                // Синхронизация text -> color
                textInput.addEventListener('input', function() {
                    const color = this.value;
                    if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color)) {
                        colorInput.value = color;
                        saveSetting(key, color);
                    }
                });
                
                // Валидация при потере фокуса
                textInput.addEventListener('blur', function() {
                    if (!/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(this.value)) {
                        this.value = colorInput.value;
                    }
                });
            }
        });
    }
    
    // Инициализация полей настроек
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
                // Сохранение сразу для select
                input.addEventListener('change', function() {
                    saveSetting(this.dataset.key, this.value);
                });
            } else if (type !== 'color' && type !== 'file' && !input.readOnly) {
                // Автосохранение с задержкой для текстовых полей
                input.addEventListener('input', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(function() {
                        saveSetting(input.dataset.key, input.value);
                    }, autoSaveDelay);
                });
                
                // Сохранение при потере фокуса
                input.addEventListener('blur', function() {
                    clearTimeout(saveTimeout);
                    saveSetting(this.dataset.key, this.value);
                });
            }
        });
    }
    
    // Переключение полей логотипа
    function initLogoTypeToggle() {
        const logoTypeSelect = document.querySelector('#setting_logo_type');
        const logoUrlField = document.querySelector('.logo-url-field');
        const logoIconField = document.querySelector('.logo-icon-field');
        const logoTextField = document.querySelector('.logo-text-field');
        
        function toggleLogoFields() {
            if (!logoTypeSelect) return;
            
            const logoType = logoTypeSelect.value;
            
            if (logoType === 'icon_text') {
                if (logoTextField) logoTextField.style.display = '';
                if (logoIconField) logoIconField.style.display = '';
                if (logoUrlField) logoUrlField.style.display = 'none';
            } else if (logoType === 'image') {
                if (logoTextField) logoTextField.style.display = 'none';
                if (logoIconField) logoIconField.style.display = 'none';
                if (logoUrlField) logoUrlField.style.display = '';
            } else if (logoType === 'text') {
                if (logoTextField) logoTextField.style.display = '';
                if (logoIconField) logoIconField.style.display = 'none';
                if (logoUrlField) logoUrlField.style.display = 'none';
            }
        }
        
        if (logoTypeSelect) {
            toggleLogoFields();
            logoTypeSelect.addEventListener('change', function() {
                toggleLogoFields();
                saveSetting(this.dataset.key, this.value);
            });
        }
    }
    
    // Сохранение одной настройки
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
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Сервер повернув не JSON відповідь. Статус: ' + response.status);
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error('HTTP error! status: ' + response.status + ', error: ' + (errorData.error || ''));
            }
            
            return response.json();
        })
        .then(data => {
            if (!data) {
                throw new Error('Порожня відповідь від сервера');
            }
            
            if (data.success) {
                // Не показываем уведомление для каждого автосохранения, только для ручного сохранения
                // showAlert('Налаштування збережено', 'success');
                updateColorPreview();
            } else {
                console.error('Save error:', data);
                const errorMsg = data.error || 'Невідома помилка';
                showAlert('Помилка: ' + errorMsg, 'danger');
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            console.error('Error stack:', error.stack);
            showAlert('Помилка збереження: ' + (error.message || error), 'danger');
        });
    }
    
    // Сохранение всех настроек вкладки
    function saveCategorySettings(category) {
        const settings = {};
        const tabPanel = document.querySelector('#panel-' + category);
        if (!tabPanel) return;
        
        tabPanel.querySelectorAll('.setting-input').forEach(function(input) {
            const key = input.dataset.key;
            if (!key) return;
            
            if (input.type === 'checkbox') {
                settings[key] = input.checked ? '1' : '0';
            } else if (input.type === 'color') {
                settings[key] = input.value;
            } else {
                settings[key] = input.value || '';
            }
        });
        
        // Также сохраняем текстовые поля для цветов
        tabPanel.querySelectorAll('.color-text-input').forEach(function(input) {
            const key = input.dataset.key;
            if (key && !settings[key]) {
                settings[key] = input.value;
            }
        });
        
        const formData = new FormData();
        formData.append('action', 'save_settings');
        formData.append('settings', JSON.stringify(settings));
        formData.append('csrf_token', csrfToken);
        
        const saveBtn = document.querySelector('.save-tab-btn[data-category="' + category + '"]');
        const saveStatus = document.getElementById('saveStatus-' + category);
        
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Збереження...';
        }
        
        if (saveStatus) {
            saveStatus.style.display = 'inline';
            saveStatus.textContent = 'Збереження...';
            saveStatus.className = 'ms-3 text-primary save-status';
        }
        
        console.log('Saving settings for category:', category);
        console.log('Settings to save:', settings);
        console.log('CSRF Token:', csrfToken ? 'Present' : 'Missing');
        
        fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Сервер повернув не JSON відповідь. Статус: ' + response.status);
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error('HTTP error! status: ' + response.status + ', error: ' + (errorData.error || ''));
            }
            
            return response.json();
        })
        .then(data => {
            if (!data) {
                throw new Error('Порожня відповідь від сервера');
            }
            
            console.log('Save response:', data);
            
            if (data.success) {
                // Не показываем уведомление, так как статус отображается рядом с кнопкой
                // showAlert('Налаштування успішно збережено', 'success');
                if (saveStatus) {
                    saveStatus.textContent = 'Збережено!';
                    saveStatus.className = 'ms-3 text-success save-status';
                    setTimeout(function() {
                        saveStatus.style.display = 'none';
                    }, 2000);
                }
                updateColorPreview();
            } else {
                console.error('Save error:', data);
                const errorMsg = data.error || 'Невідома помилка';
                showAlert('Помилка: ' + errorMsg, 'danger');
                if (saveStatus) {
                    saveStatus.textContent = 'Помилка!';
                    saveStatus.className = 'ms-3 text-danger save-status';
                }
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            console.error('Error stack:', error.stack);
            const errorMsg = error.message || 'Невідома помилка';
            showAlert('Помилка збереження: ' + errorMsg, 'danger');
            if (saveStatus) {
                saveStatus.textContent = 'Помилка!';
                saveStatus.className = 'ms-3 text-danger save-status';
            }
        })
        .finally(function() {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Зберегти зміни';
            }
        });
    }
    
    // Инициализация кнопок сохранения
    function initSaveButtons() {
        document.querySelectorAll('.save-tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const category = this.dataset.category;
                saveCategorySettings(category);
            });
        });
    }
    
    // Инициализация кнопки сброса
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
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Скидання...';
                
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
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-undo me-2"></i>Скинути до значень за замовчуванням';
                    }
                })
                .catch(error => {
                    console.error('Reset error:', error);
                    showAlert('Помилка скидання', 'danger');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-undo me-2"></i>Скинути до значень за замовчуванням';
                });
            });
        }
    }
    
    // Инициализация табов (мгновенное переключение без анимаций)
    (function() {
        function switchTab(button) {
            const targetId = button.getAttribute('data-bs-target');
            if (!targetId) return;
            
            const targetPane = document.querySelector(targetId);
            if (!targetPane) return;
            
            // Убираем активный класс со всех кнопок
            document.querySelectorAll('#customizerTabs .nav-link').forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            
            // Скрываем все панели мгновенно
            document.querySelectorAll('#customizerTabContent .tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active', 'fade');
                pane.style.display = 'none';
                pane.style.opacity = '1';
                pane.style.transition = 'none';
                pane.setAttribute('aria-hidden', 'true');
            });
            
            // Активируем выбранную кнопку и панель мгновенно
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');
            targetPane.classList.add('show', 'active');
            targetPane.style.display = 'block';
            targetPane.style.opacity = '1';
            targetPane.style.transition = 'none';
            targetPane.setAttribute('aria-hidden', 'false');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('#customizerTabs .nav-link[data-bs-toggle="tab"]');
            
            // Перехватываем клики до Bootstrap
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    switchTab(this);
                    return false;
                }, true);
            });
            
            // Инициализируем первый активный таб
            const firstTab = document.querySelector('#customizerTabs .nav-link.active');
            if (firstTab) {
                switchTab(firstTab);
            }
        });
    })();
    
    // Показ уведомлений
    function showAlert(message, type) {
        const alert = document.getElementById('customizerAlert');
        const alertMessage = document.getElementById('customizerAlertMessage');
        
        if (alert && alertMessage) {
            alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
            alertMessage.textContent = message;
            alert.style.display = 'block';
            
            // Автоматически скрываем через 3 секунды
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 3000);
        }
    }
    
    // Обновление предпросмотра цветов
    function updateColorPreview() {
        // Можно добавить логику обновления предпросмотра, если нужно
    }
    
    // Медиагалерея (код из предыдущей версии)
    function initMediaGallery() {
        // Обработка выбора изображения из медиагалереи
        document.querySelectorAll('.media-select-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentMediaTarget = document.querySelector(this.dataset.target);
                currentMediaPreview = document.querySelector('#' + this.dataset.preview);
                const modal = new bootstrap.Modal(document.getElementById('mediaManagerModal'));
                modal.show();
                loadMediaImages();
            });
        });
        
        // Обработка загрузки файлов
        const uploadImageBtn = document.getElementById('uploadImageBtn');
        const uploadImageInput = document.getElementById('uploadImageInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadStatus = document.getElementById('uploadStatus');
        const uploadProgressBar = uploadProgress ? uploadProgress.querySelector('.progress-bar') : null;
        
        if (uploadImageBtn && uploadImageInput) {
            uploadImageBtn.addEventListener('click', function() {
                uploadImageInput.click();
            });
            
            uploadImageInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files.length > 0) {
                    uploadFiles(e.target.files);
                }
            });
        }
        
        // Обработка поиска в медиагалерее
        const mediaSearch = document.getElementById('mediaSearch');
        if (mediaSearch) {
            let searchTimeout;
            mediaSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    loadMediaImages(1);
                }, 500);
            });
        }
    }
    
    // Функции для работы с медиагалереей
    function loadMediaImages(page = 1) {
        const container = document.getElementById('mediaImagesGrid');
        const search = document.getElementById('mediaSearch') ? document.getElementById('mediaSearch').value : '';
        const url = '?action=get_media_images&page=' + page + '&per_page=24' + (search ? '&search=' + encodeURIComponent(search) : '');
        
        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Завантаження...</span></div></div>';
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMediaImages(data.files);
                renderMediaPagination(data.page, data.pages);
            } else {
                container.innerHTML = '<div class="col-12 text-center text-danger py-4">Помилка завантаження зображень</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="col-12 text-center text-danger py-4">Помилка завантаження зображень</div>';
        });
    }
    
    function renderMediaImages(files) {
        const container = document.getElementById('mediaImagesGrid');
        container.innerHTML = '';
        
        if (files.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-muted py-4">Зображення не знайдено</div>';
            return;
        }
        
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
            img.alt = file.title || file.original_name || '';
            img.className = 'img-thumbnail w-100';
            
            const overlay = document.createElement('div');
            overlay.className = 'media-image-overlay';
            overlay.innerHTML = '<i class="fas fa-check"></i>';
            
            imageItem.appendChild(img);
            imageItem.appendChild(overlay);
            colDiv.appendChild(imageItem);
            container.appendChild(colDiv);
        });
    }
    
    function renderMediaPagination(currentPage, totalPages) {
        const container = document.getElementById('mediaPagination');
        if (!container) return;
        container.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        let pagination = '<ul class="pagination justify-content-center">';
        if (currentPage > 1) {
            pagination += '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '">Попередня</a></li>';
        }
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                pagination += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
            } else {
                pagination += '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
            }
        }
        if (currentPage < totalPages) {
            pagination += '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '">Наступна</a></li>';
        }
        pagination += '</ul>';
        
        container.innerHTML = pagination;
        
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page) {
                    loadMediaImages(page);
        }
    });
});
    }
    
    function selectMediaImage(url) {
        if (currentMediaTarget && url) {
            currentMediaTarget.value = url;
            // Автоматически сохраняем при выборе изображения
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
    
    // Функция загрузки файлов
    async function uploadFiles(files) {
        if (!files || files.length === 0) return;
        
        const fileArray = Array.from(files);
        const total = fileArray.length;
        let uploaded = 0;
        let lastUploadedUrl = null;
        let hasError = false;
        
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadProgressBar = uploadProgress ? uploadProgress.querySelector('.progress-bar') : null;
        const uploadStatus = document.getElementById('uploadStatus');
        const uploadImageBtn = document.getElementById('uploadImageBtn');
        const uploadImageInput = document.getElementById('uploadImageInput');
        
        // Показываем прогресс
        if (uploadProgress) {
            uploadProgress.style.display = 'block';
            if (uploadProgressBar) {
                uploadProgressBar.style.width = '0%';
            }
        }
        if (uploadStatus) {
            uploadStatus.textContent = 'Завантаження... 0 з ' + total;
        }
        
        // Отключаем кнопку загрузки
        if (uploadImageBtn) {
            uploadImageBtn.disabled = true;
        }
        
        // Загружаем файлы последовательно
        for (let i = 0; i < fileArray.length; i++) {
            const file = fileArray[i];
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'upload_image');
                
                const response = await fetch('?action=upload_image', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    uploaded++;
                    lastUploadedUrl = data.file_url;
                    
                    if (uploadProgressBar) {
                        uploadProgressBar.style.width = ((uploaded / total) * 100) + '%';
                    }
                    if (uploadStatus) {
                        uploadStatus.textContent = 'Завантажено: ' + uploaded + ' з ' + total;
                    }
                } else {
                    hasError = true;
                    alert('Помилка завантаження файлу "' + file.name + '": ' + (data.error || 'Невідома помилка'));
                }
            } catch (error) {
                console.error('Upload error:', error);
                hasError = true;
                alert('Помилка завантаження файлу "' + file.name + '"');
            }
        }
        
        // Включаем кнопку загрузки
        if (uploadImageBtn) {
            uploadImageBtn.disabled = false;
        }
        
        // Обработка после загрузки
        if (uploaded > 0 && !hasError) {
            // Обновляем галерею
            loadMediaImages(1);
            
            // Если загружен один файл и есть целевое поле, автоматически выбираем его
            if (uploaded === 1 && currentMediaTarget && lastUploadedUrl) {
                setTimeout(function() {
                    selectMediaImage(lastUploadedUrl);
                }, 1000);
            }
            
            // Скрываем прогресс через 2 секунды
            setTimeout(function() {
                if (uploadProgress) {
                    uploadProgress.style.display = 'none';
                }
            }, 2000);
        } else if (hasError && uploaded === 0) {
            // Если все файлы не загрузились, скрываем прогресс сразу
            if (uploadProgress) {
                uploadProgress.style.display = 'none';
            }
        }
        
        // Очищаем input
        if (uploadImageInput) {
            uploadImageInput.value = '';
        }
    }
})();
</script>

<style>
.media-image-item {
    position: relative;
    overflow: hidden;
    border-radius: 4px;
}
.media-image-item:hover .media-image-overlay {
    opacity: 1;
}
.media-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 123, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    color: white;
    font-size: 2rem;
}
#uploadImageBtn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    white-space: nowrap;
}
#uploadImageBtn i {
    line-height: 1;
    vertical-align: middle;
}
#uploadImageBtn span {
    line-height: 1.5;
    vertical-align: middle;
}
/* Строгий плоский дизайн без анимаций */
.nav-tabs {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 0;
    margin-top: 0;
}
.nav-tabs .nav-link {
    cursor: pointer;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    padding: 0.75rem 1.25rem;
    color: #6c757d;
    background: transparent !important;
    font-weight: 400;
    transition: none !important;
    transform: none !important;
    scale: 1 !important;
    box-shadow: none !important;
}
.nav-tabs .nav-link:hover {
    border-bottom-color: #adb5bd !important;
    color: #495057;
    background: transparent !important;
    transform: none !important;
    scale: 1 !important;
    box-shadow: none !important;
}
.nav-tabs .nav-link.active {
    color: #212529 !important;
    border-bottom-color: #212529 !important;
    background: transparent !important;
    font-weight: 500;
    transform: none !important;
    scale: 1 !important;
    box-shadow: none !important;
}
.nav-tabs .nav-link i {
    margin-right: 0.5rem;
}
.tab-content {
    margin-top: 0;
    padding-top: 0.25rem;
}
.tab-content .tab-pane {
    display: none !important;
    opacity: 1 !important;
    transition: none !important;
    animation: none !important;
    transform: none !important;
}
.tab-content .tab-pane.show,
.tab-content .tab-pane.active {
    display: block !important;
    opacity: 1 !important;
    transition: none !important;
    animation: none !important;
    transform: none !important;
}
.tab-content .tab-pane.fade {
    opacity: 1 !important;
    transition: none !important;
}
.tab-content .tab-pane.fade.show {
    opacity: 1 !important;
    transition: none !important;
}

/* Плоский дизайн для заголовка страницы */
.page-header {
    background: #ffffff !important;
    box-shadow: none !important;
}
.page-icon {
    background: #667eea !important;
    border-radius: 4px !important;
    box-shadow: none !important;
}
.page-actions .btn {
    transition: none !important;
    transform: none !important;
}
.page-actions .btn:hover {
    transform: none !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
}
.setting-input:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.color-text-input {
    font-family: monospace;
}
.save-status {
    font-size: 0.875rem;
}
.content-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 0;
    box-shadow: none;
}
.content-section-header {
    display: none;
}
.content-section-body {
    padding: 1rem;
}

/* Улучшенный компактный дизайн для вкладки "Логотип та інше" */
#panel-other .content-section {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
#panel-other .content-section-body {
    padding: 1.5rem;
    box-sizing: border-box;
}
#panel-other .logo-field {
    margin-bottom: 1.25rem;
}
/* Секция типа логотипа - выделенная */
#panel-other .logo-type-field {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 8px;
    border: 1px solid #e9ecef;
    border-left: 4px solid #0d6efd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}
#panel-other .logo-type-field .form-label {
    font-weight: 600;
    font-size: 0.95rem;
    color: #212529;
    margin-bottom: 0.65rem;
    display: flex;
    align-items: center;
}
#panel-other .logo-type-field .form-label::before {
    content: "🎨";
    margin-right: 0.5rem;
    font-size: 1rem;
}
#panel-other .logo-type-field .form-select {
    font-size: 0.9rem;
    padding: 0.65rem 0.85rem;
    border: 1px solid #ced4da;
    background-color: #ffffff;
    border-radius: 6px;
    transition: all 0.2s ease;
}
#panel-other .logo-type-field .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}
#panel-other .logo-type-field .form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.5rem;
    font-style: italic;
}
/* Поля логотипа - аккуратные карточки */
#panel-other .logo-url-field,
#panel-other .logo-icon-field,
#panel-other .logo-text-field {
    padding: 1.25rem;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    margin-bottom: 1.25rem;
    border-left: 4px solid #28a745;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}
#panel-other .logo-url-field:hover,
#panel-other .logo-icon-field:hover,
#panel-other .logo-text-field:hover {
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    border-color: #d1ecf1;
}
#panel-other .logo-url-field .form-label,
#panel-other .logo-icon-field .form-label,
#panel-other .logo-text-field .form-label {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.65rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
}
#panel-other .logo-url-field .form-label::before {
    content: "🖼️";
    margin-right: 0.5rem;
}
#panel-other .logo-icon-field .form-label::before {
    content: "📎";
    margin-right: 0.5rem;
}
#panel-other .logo-text-field .form-label::before {
    content: "✏️";
    margin-right: 0.5rem;
}
#panel-other .logo-url-field .form-text,
#panel-other .logo-icon-field .form-text,
#panel-other .logo-text-field .form-text {
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.5rem;
    display: block;
    line-height: 1.4;
}
#panel-other .logo-url-field .input-group,
#panel-other .logo-icon-field .input-group {
    margin-bottom: 0.75rem;
}
#panel-other .logo-url-field .form-control,
#panel-other .logo-icon-field .form-control,
#panel-other .logo-text-field .form-control {
    background-color: #ffffff;
    border: 1px solid #ced4da;
    font-size: 0.9rem;
    padding: 0.65rem 0.85rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}
#panel-other .logo-url-field .form-control:focus,
#panel-other .logo-icon-field .form-control:focus,
#panel-other .logo-text-field .form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.1);
}
#panel-other .logo-url-field .media-url-input,
#panel-other .logo-icon-field .media-url-input {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
#panel-other .media-select-btn {
    white-space: nowrap;
    border-left: 0;
    font-size: 0.875rem;
    padding: 0.65rem 1rem;
    border-radius: 0 6px 6px 0;
    transition: all 0.2s ease;
}
#panel-other .media-select-btn:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
}
/* Улучшенное превью */
#panel-other #preview_logo_url,
#panel-other #preview_logo_icon_url {
    margin-top: 1rem;
    display: block;
}
#panel-other #preview_logo_url img,
#panel-other #preview_logo_icon_url img {
    max-width: 180px;
    max-height: 90px;
    object-fit: contain;
    border-radius: 6px;
    border: 2px solid #e9ecef;
    padding: 0.75rem;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
#panel-other #preview_logo_url img:hover,
#panel-other #preview_logo_icon_url img:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: scale(1.02);
}
#panel-other .logo-text-preview {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin-top: 0.75rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid #e9ecef;
    border-radius: 6px;
    display: inline-block;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    letter-spacing: 0.5px;
}
/* Компактная группировка элементов - оптимизировано */
#panel-other .row {
    margin-left: -0.5rem;
    margin-right: -0.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    width: calc(100% + 1rem);
    box-sizing: border-box;
}
#panel-other .row > [class*="col-"] {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}
#panel-other .col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    box-sizing: border-box;
}
@media (max-width: 767.98px) {
    #panel-other .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Компактные стили для чекбоксов в 2 колонки */
#panel-other .col-md-6.mb-3 {
    display: flex;
    flex-direction: column;
}
#panel-other .form-check {
    padding: 0;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 0;
    border-left: 3px solid #ffc107;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: all 0.2s ease;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 100%;
}
#panel-other .form-check:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    border-color: #ffeaa7;
}
#panel-other .form-label-checkbox {
    display: block;
    font-size: 0.875rem;
    color: #212529;
    font-weight: 600;
    margin-bottom: 0.375rem;
    padding: 0.75rem 1rem 0.375rem 1rem;
    width: 100%;
    box-sizing: border-box;
    line-height: 1.3;
}
#panel-other .form-check-wrapper {
    display: flex;
    align-items: center;
    padding: 0 1rem 0.75rem 1rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}
#panel-other .form-check-wrapper .form-check-label {
    font-size: 0.85rem;
    color: #495057;
    font-weight: 400;
    margin-left: 0.5rem;
    cursor: pointer;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.4;
}
#panel-other .form-check-input {
    margin-top: 0;
    cursor: pointer;
    width: 1.1rem;
    height: 1.1rem;
    flex-shrink: 0;
}
#panel-other .form-check-input:checked {
    background-color: #ffc107;
    border-color: #ffc107;
}

/* Компактные отступы */
#panel-other .mb-3 {
    margin-bottom: 0.75rem !important;
}
#panel-other .col-md-6.mb-3 {
    margin-bottom: 0.75rem !important;
}

/* Кнопка сохранения - оптимизировано */
#panel-other .content-section-body > .border-top {
    margin-top: 1rem !important;
    padding: 1rem 0 0 0 !important;
    border-top: 2px solid #e9ecef !important;
    clear: both;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
    margin-left: 0 !important;
    margin-right: 0 !important;
    float: none !important;
    display: block;
    text-align: left;
}
#panel-other .save-tab-btn {
    font-size: 0.9rem;
    padding: 0.55rem 1.25rem;
    font-weight: 500;
    border-radius: 5px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
    display: inline-block;
    margin: 0;
    vertical-align: middle;
}
#panel-other .save-tab-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

/* Дублирующиеся стили удалены */
</style>

<?php endif; ?>
