<?php
/**
 * Шаблон страницы управления темами
 */
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="content-section themes-page">
    <div class="content-section-header">
        <span><i class="fas fa-palette me-2"></i>Встановлені теми</span>
    </div>
    <div class="content-section-body">
        <?php if (empty($themes)): ?>
            <div class="themes-empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <h4>Теми не знайдено</h4>
                <p class="text-muted">Встановіть тему за замовчуванням через міграцію бази даних або завантажте нову тему з маркетплейсу.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadThemeModal">
                        <i class="fas fa-upload me-1"></i>Завантажити тему
                    </button>
                    <a href="https://flowaxy.com/marketplace/themes" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-store me-1"></i>Перейти до маркетплейсу
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="themes-list">
                <?php foreach ($themes as $theme): ?>
                    <?php 
                    $isActive = ($theme['is_active'] == 1);
                    $supportsCustomization = isset($themesWithCustomization[$theme['slug']]) && $themesWithCustomization[$theme['slug']];
                    ?>
                    <div class="theme-item <?= $isActive ? 'theme-active' : '' ?>">
                        <div class="theme-item-preview">
                            <?php if ($theme['screenshot']): ?>
                                <img src="<?= htmlspecialchars($theme['screenshot']) ?>" 
                                     class="theme-preview-img" 
                                     alt="<?= htmlspecialchars($theme['name']) ?>">
                            <?php else: ?>
                                <div class="theme-preview-placeholder">
                                    <i class="fas fa-palette"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($isActive): ?>
                                <div class="theme-active-indicator">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="theme-item-info">
                            <div class="theme-item-header">
                                <h5 class="theme-item-name">
                                    <?= htmlspecialchars($theme['name']) ?>
                                    <?php if ($isActive): ?>
                                        <span class="theme-active-badge">
                                            <i class="fas fa-check-circle me-1"></i>Активна
                                        </span>
                                    <?php endif; ?>
                                </h5>
                                <?php if (!empty($theme['description'])): ?>
                                    <p class="theme-item-description"><?= htmlspecialchars($theme['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="theme-item-meta">
                                <span class="theme-meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span>v<?= htmlspecialchars($theme['version'] ?? '1.0.0') ?></span>
                                </span>
                                <?php if (!empty($theme['author'])): ?>
                                    <span class="theme-meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($theme['author']) ?></span>
                                    </span>
                                <?php endif; ?>
                                <?php if ($supportsCustomization): ?>
                                    <span class="theme-meta-item theme-customization-badge">
                                        <i class="fas fa-paint-brush"></i>
                                        <span>Підтримка кастомізації</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="theme-item-actions">
                            <?php if (!$isActive): ?>
                                <?php
                                $hasScssSupport = themeManager()->hasScssSupport($theme['slug']);
                                $themePath = themeManager()->getThemePath($theme['slug']);
                                $cssFile = $themePath . 'assets/css/style.css';
                                $cssExists = file_exists($cssFile);
                                ?>
                                <form method="POST" class="d-inline theme-activate-form" data-theme-slug="<?= htmlspecialchars($theme['slug']) ?>" data-has-scss="<?= $hasScssSupport ? '1' : '0' ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="theme_slug" value="<?= htmlspecialchars($theme['slug']) ?>">
                                    <input type="hidden" name="activate_theme" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm theme-activate-btn">
                                        <i class="fas fa-check me-1"></i>
                                        <span class="btn-text">Активувати</span>
                                        <?php if ($hasScssSupport): ?>
                                            <span class="btn-spinner ms-2" style="display: none;">
                                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            </span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="theme-actions-group">
                                    <?php if ($supportsCustomization): ?>
                                        <a href="<?= adminUrl('customizer') ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-paint-brush me-1"></i>Налаштувати
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" disabled title="Ця тема не підтримує кастомізацію">
                                            <i class="fas fa-paint-brush me-1"></i>Налаштувати
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?= adminUrl('theme-editor?theme=' . urlencode($theme['slug'])) ?>" class="btn btn-primary btn-sm" title="Редактор теми">
                                        <i class="fas fa-code me-1"></i>Редактор
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.themes-page {
    background: transparent;
}

.content-section-header {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-bottom: none;
    padding: 16px 20px;
    font-weight: 600;
    color: #212529;
    font-size: 0.95rem;
}

.content-section-body {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-top: none;
    padding: 24px;
}

.themes-empty-state {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border: 1px solid #e0e0e0;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2.5rem;
}

.themes-empty-state h4 {
    color: #212529;
    font-weight: 600;
    margin-bottom: 12px;
    font-size: 1.5rem;
}

.themes-empty-state .text-muted {
    color: #6c757d;
    margin-bottom: 32px;
    font-size: 0.95rem;
    line-height: 1.6;
}

.themes-empty-state .btn {
    padding: 12px 24px;
    font-weight: 500;
    border-radius: 0;
    border: 1px solid;
    font-size: 0.9rem;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.themes-empty-state .btn-primary {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.themes-empty-state .btn-primary:hover {
    background: #0b5ed7;
    border-color: #0b5ed7;
}

.themes-empty-state .btn-outline-primary {
    border-color: #0d6efd;
    color: #0d6efd;
    background: transparent;
}

.themes-empty-state .btn-outline-primary:hover {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.themes-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.theme-item {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e0e0e0;
    position: relative;
}

.theme-item.theme-active {
    border-left: 4px solid #0d6efd;
}

.theme-item-preview {
    flex: 0 0 140px;
    height: 90px;
    overflow: hidden;
    background: #f8f9fa;
    position: relative;
    border: 1px solid #e0e0e0;
}

.theme-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.theme-preview-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    background: #f8f9fa;
}

.theme-preview-placeholder i {
    font-size: 3rem;
    opacity: 0.5;
}

.theme-active-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.875rem;
    border: 2px solid #fff;
}

.theme-item-info {
    flex: 1;
    min-width: 0;
}

.theme-item-header {
    margin-bottom: 12px;
}

.theme-item-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.theme-active-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    background: #0d6efd;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.theme-active-badge i {
    font-size: 0.7rem;
}

.theme-item-description {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

.theme-item-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.theme-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
    padding: 4px 10px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
}

.theme-meta-item i {
    color: #adb5bd;
    font-size: 0.8rem;
}

.theme-customization-badge {
    color: #0d6efd;
    font-weight: 600;
    background: rgba(13, 110, 253, 0.1) !important;
    border-color: rgba(13, 110, 253, 0.2) !important;
}

.theme-customization-badge i {
    color: #0d6efd;
}

.theme-item-actions {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
}

.theme-actions-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.theme-actions-group .btn {
    white-space: nowrap;
    font-size: 0.875rem;
    padding: 8px 16px;
    border-radius: 0;
    font-weight: 500;
    border: 1px solid #dee2e6;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1.5;
    vertical-align: middle;
}

.theme-actions-group .btn i {
    display: inline-flex;
    align-items: center;
    line-height: 1;
}

.theme-actions-group .btn-primary {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.theme-actions-group .btn-primary:hover:not(:disabled) {
    background: #0b5ed7;
    border-color: #0b5ed7;
}

.theme-actions-group .btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
    background: #6c757d !important;
    border-color: #6c757d !important;
}

.theme-item .btn-primary:not(.theme-actions-group .btn-primary) {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
    padding: 8px 16px;
    border-radius: 0;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1.5;
    vertical-align: middle;
    border: 1px solid;
}

.theme-item .btn-primary:not(.theme-actions-group .btn-primary) i {
    display: inline-flex;
    align-items: center;
    line-height: 1;
}

.theme-item .btn-primary:not(.theme-actions-group .btn-primary):hover {
    background: #0b5ed7;
    border-color: #0b5ed7;
}

@media (max-width: 768px) {
    .theme-item {
        flex-direction: column;
        align-items: stretch;
        padding: 20px;
    }
    
    .theme-item-preview {
        width: 100%;
        height: 180px;
    }
    
    .theme-item-actions {
        width: 100%;
        margin-top: 16px;
    }
    
    .theme-actions-group {
        width: 100%;
        flex-direction: column;
    }
    
    .theme-actions-group .btn {
        width: 100%;
    }
    
    .theme-item-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

.theme-activate-btn {
    position: relative;
    min-width: 120px;
}

.theme-activate-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.theme-activate-btn .btn-spinner {
    display: inline-flex;
    align-items: center;
}

.theme-activate-btn.compiling .btn-text::after {
    content: ' (компіляція...)';
    font-size: 0.875em;
}

.theme-activate-btn.activating .btn-text::after {
    content: ' (активація...)';
    font-size: 0.875em;
}
</style>

<script>
(function() {
    'use strict';
    
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    
    document.addEventListener('DOMContentLoaded', function() {
        initThemeActivation();
    });
    
    function initThemeActivation() {
        document.querySelectorAll('.theme-activate-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const themeSlug = this.dataset.themeSlug;
                const hasScss = this.dataset.hasScss === '1';
                const btn = this.querySelector('.theme-activate-btn');
                const btnText = btn.querySelector('.btn-text');
                const btnSpinner = btn.querySelector('.btn-spinner');
                
                // Отключаем кнопку и показываем спиннер
                btn.disabled = true;
                btn.classList.add(hasScss ? 'compiling' : 'activating');
                if (btnSpinner) {
                    btnSpinner.style.display = 'inline-flex';
                }
                
                // Если тема поддерживает SCSS, сначала компилируем
                if (hasScss) {
                    btnText.textContent = 'Компілюється...';
                    
                    // Компилируем SCSS
                    compileAndActivateTheme(themeSlug, btn, btnText, btnSpinner);
                } else {
                    // Просто активируем тему
                    activateTheme(themeSlug, btn, btnText, btnSpinner);
                }
            });
        });
    }
    
    function compileAndActivateTheme(themeSlug, btn, btnText, btnSpinner) {
        const formData = new FormData();
        formData.append('action', 'activate_theme');
        formData.append('theme_slug', themeSlug);
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
                btnText.textContent = 'Активовано!';
                btn.classList.remove('compiling');
                btn.classList.add('activating');
                
                // Перезагружаем страницу через небольшую задержку
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                btn.disabled = false;
                btn.classList.remove('compiling', 'activating');
                if (btnSpinner) {
                    btnSpinner.style.display = 'none';
                }
                btnText.textContent = 'Активувати';
                
                alert('Помилка: ' + (data.error || 'Невідома помилка'));
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.classList.remove('compiling', 'activating');
            if (btnSpinner) {
                btnSpinner.style.display = 'none';
            }
            btnText.textContent = 'Активувати';
            
            alert('Помилка підключення до сервера');
            console.error('Error:', error);
        });
    }
    
    function activateTheme(themeSlug, btn, btnText, btnSpinner) {
        // Для тем без SCSS используем обычную активацию через форму
        btnText.textContent = 'Активується...';
        
        // Отправляем форму обычным способом
        const form = btn.closest('form');
        if (form) {
            // Создаем скрытую форму для отправки
            const hiddenForm = document.createElement('form');
            hiddenForm.method = 'POST';
            hiddenForm.action = '';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = csrfToken;
            hiddenForm.appendChild(csrfInput);
            
            const themeSlugInput = document.createElement('input');
            themeSlugInput.type = 'hidden';
            themeSlugInput.name = 'theme_slug';
            themeSlugInput.value = themeSlug;
            hiddenForm.appendChild(themeSlugInput);
            
            const activateInput = document.createElement('input');
            activateInput.type = 'hidden';
            activateInput.name = 'activate_theme';
            activateInput.value = '1';
            hiddenForm.appendChild(activateInput);
            
            document.body.appendChild(hiddenForm);
            hiddenForm.submit();
        }
    }
})();
</script>

<!-- Модальне вікно завантаження теми -->
<div class="modal fade" id="uploadThemeModal" tabindex="-1" aria-labelledby="uploadThemeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadThemeModalLabel">
                    <i class="fas fa-upload me-2"></i>Завантажити тему
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadThemeForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="upload_theme">
                    
                    <div class="mb-3">
                        <label for="themeFile" class="form-label">Виберіть ZIP архів з темою</label>
                        <input type="file" class="form-control" id="themeFile" name="theme_file" accept=".zip" required>
                        <div class="form-text">
                            Максимальний розмір: 50 MB. Архів повинен містити файл theme.json
                        </div>
                    </div>
                    
                    <div id="uploadProgress" class="progress d-none mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div id="uploadResult" class="alert d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary" id="uploadThemeBtn">
                        <i class="fas fa-upload me-1"></i>Завантажити
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadThemeForm');
    const uploadBtn = document.getElementById('uploadThemeBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const uploadResult = document.getElementById('uploadResult');
    const progressBar = uploadProgress?.querySelector('.progress-bar');
    
    if (uploadForm && uploadBtn && uploadProgress && uploadResult && progressBar) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const fileInput = document.getElementById('themeFile');
            
            if (!fileInput.files.length) {
                showUploadResult('Будь ласка, виберіть файл', 'danger');
                return;
            }
            
            // Показуємо прогрес
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Завантаження...';
            uploadProgress.classList.remove('d-none');
            uploadResult.classList.add('d-none');
            progressBar.style.width = '0%';
            
            // Симулюємо прогрес
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 10;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                }
            }, 200);
            
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                setTimeout(() => {
                    if (data.success) {
                        showUploadResult(data.message || 'Тему успішно завантажено', 'success');
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
                        
                        // Перезавантажуємо сторінку через 2 секунди
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showUploadResult(data.error || 'Помилка завантаження', 'danger');
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
                        progressBar.style.width = '0%';
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                showUploadResult('Помилка підключення до сервера: ' + error.message, 'danger');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
                progressBar.style.width = '0%';
            });
        });
    }
    
    function showUploadResult(message, type) {
        if (uploadResult) {
            uploadResult.textContent = message;
            uploadResult.className = 'alert alert-' + type;
            uploadResult.classList.remove('d-none');
        }
    }
    
    // Очищаємо форму при закритті модального вікна
    const modal = document.getElementById('uploadThemeModal');
    if (modal && uploadForm) {
        modal.addEventListener('hidden.bs.modal', function() {
            uploadForm.reset();
            if (uploadResult) uploadResult.classList.add('d-none');
            if (uploadProgress) uploadProgress.classList.add('d-none');
            if (progressBar) progressBar.style.width = '0%';
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
            }
        });
    }
});
</script>