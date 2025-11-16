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
                <div class="row">
                    <?php foreach ($themes as $theme): ?>
                        <?php 
                        $isActive = ($theme['is_active'] == 1);
                        $supportsCustomization = isset($themesWithCustomization[$theme['slug']]) && $themesWithCustomization[$theme['slug']];
                        $hasSettings = isset($themesWithSettings[$theme['slug']]) && $themesWithSettings[$theme['slug']];
                        ?>
                        <div class="col-lg-6 mb-3 theme-item-wrapper" data-status="<?= $isActive ? 'active' : 'inactive' ?>" data-name="<?= strtolower($theme['name'] ?? '') ?>">
                            <div class="theme-card <?= $isActive ? 'theme-active' : '' ?>">
                                <div class="theme-header">
                                    <h6 class="theme-name">
                                        <?= htmlspecialchars($theme['name']) ?>
                                    </h6>
                                    <div class="theme-badges">
                                        <?php if ($isActive): ?>
                                            <span class="badge badge-active">Активна</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Неактивна</span>
                                        <?php endif; ?>
                                        <span class="theme-version">v<?= htmlspecialchars($theme['version'] ?? '1.0.0') ?></span>
                                    </div>
                                </div>
                                
                                <p class="theme-description">
                                    <?= htmlspecialchars($theme['description'] ?? 'Опис відсутній') ?>
                                </p>
                                
                                <div class="theme-actions">
                                    <?php if (!$isActive): ?>
                                        <?php
                                        $hasScssSupport = themeManager()->hasScssSupport($theme['slug']);
                                        ?>
                                        <form method="POST" class="d-inline theme-activate-form" data-theme-slug="<?= htmlspecialchars($theme['slug']) ?>" data-has-scss="<?= $hasScssSupport ? '1' : '0' ?>">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="theme_slug" value="<?= htmlspecialchars($theme['slug']) ?>">
                                            <input type="hidden" name="activate_theme" value="1">
                                            <button type="submit" class="btn btn-primary theme-activate-btn">
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
                                        <?php if ($supportsCustomization): ?>
                                            <a href="<?= adminUrl('customizer') ?>" class="btn btn-primary">
                                                <i class="fas fa-paint-brush me-1"></i>Налаштувати
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary" disabled title="Ця тема не підтримує кастомізацію">
                                                <i class="fas fa-paint-brush me-1"></i>Налаштувати
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($hasSettings): ?>
                                            <a href="<?= adminUrl($theme['slug'] . '-theme-settings') ?>" class="btn btn-secondary" title="Налаштування теми">
                                                <i class="fas fa-cog"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-danger" disabled title="Спочатку деактивуйте тему перед видаленням">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isActive): ?>
                                        <button type="button" class="btn btn-danger" onclick="deleteTheme('<?= htmlspecialchars($theme['slug'] ?? '') ?>')" title="Видалити тему">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
    padding: 0;
}

.themes-list .row {
    display: flex;
    flex-wrap: wrap;
}

.themes-list .theme-item-wrapper {
    display: flex;
    flex-direction: column;
}

.theme-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 20px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.theme-card.theme-active {
    border-left: 4px solid #0d6efd;
}

.theme-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.theme-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.theme-badges {
    display: flex;
    gap: 8px;
    align-items: center;
}

.badge {
    padding: 4px 10px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0;
    text-transform: uppercase;
}

.badge-active {
    background: #28a745;
    color: #fff;
}

.badge-inactive {
    background: #6c757d;
    color: #fff;
}

.theme-version {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.theme-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0 0 16px 0;
    line-height: 1.5;
    flex-grow: 1;
}

.theme-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: auto;
}

.theme-actions .btn {
    border-radius: 0;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
    font-weight: 500;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    height: 38px;
    min-height: 38px;
    box-sizing: border-box;
}

.theme-actions .btn i {
    display: inline-flex;
    align-items: center;
    line-height: 1;
}

.theme-actions .btn-primary {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.theme-actions .btn-primary:hover:not(:disabled) {
    background: #0b5ed7;
    border-color: #0b5ed7;
}

.theme-actions .btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
    background: #6c757d !important;
    border-color: #6c757d !important;
}

.theme-actions .btn-secondary {
    background: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

.theme-actions .btn-secondary:hover {
    background: #5a6268;
    border-color: #5a6268;
}

.theme-actions .btn-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.theme-actions .btn-danger:hover:not(:disabled) {
    background: #c82333;
    border-color: #c82333;
}

.theme-actions .btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .themes-list .theme-item-wrapper {
        width: 100%;
    }
    
    .theme-card {
        margin-bottom: 16px;
    }
    
    .theme-actions {
        flex-wrap: wrap;
    }
    
    .theme-actions .btn {
        flex: 1;
        min-width: 120px;
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
    
    function deleteTheme(slug) {
        if (confirm('Ви впевнені, що хочете видалити цю тему? Всі файли теми будуть видалені.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete_theme">
                <input type="hidden" name="theme_slug" value="${slug}">
            `;
            document.body.appendChild(form);
            form.submit();
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