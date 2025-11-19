<?php
/**
 * Шаблон страницы управления темами
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

<!-- Скрытое поле с CSRF токеном для JavaScript -->
<input type="hidden" id="csrf_token" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">

<?php
// Формируем содержимое секции
ob_start();
?>
        <?php if (empty($themes)): ?>
            <?php
            // Пустое состояние без кнопок
            unset($actions);
            include __DIR__ . '/../components/empty-state.php';
            $icon = 'palette';
            $title = 'Теми не знайдено';
            $message = 'Встановіть тему за замовчуванням через міграцію бази даних або завантажте нову тему з маркетплейсу.';
            $classes = ['themes-empty-state'];
            ?>
        <?php else: ?>
            <div class="themes-list">
                <div class="row">
                    <?php foreach ($themes as $theme): ?>
                        <?php
                        // Подготавливаем данные для компонента
                        $isActive = ($theme['is_active'] == 1);
                        $supportsCustomization = isset($themesWithCustomization[$theme['slug']]) && $themesWithCustomization[$theme['slug']];
                        $supportsNavigation = isset($themesWithNavigation[$theme['slug']]) && $themesWithNavigation[$theme['slug']];
                        $hasSettings = isset($themesWithSettings[$theme['slug']]) && $themesWithSettings[$theme['slug']];
                        $features = isset($themesFeatures[$theme['slug']]) ? $themesFeatures[$theme['slug']] : [];
                        
                        include __DIR__ . '/../components/theme-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
<?php
$sectionContent = ob_get_clean();

// Используем компонент секции контента
$title = 'Встановлені теми';
$icon = 'palette';
$content = $sectionContent;
$classes = ['themes-page'];
include __DIR__ . '/../components/content-section.php';
?>

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

.content-section-body:has(.themes-empty-state) {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    background: #f8f9fa;
    padding: 60px 24px !important;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.content-section-body:has(.themes-empty-state .empty-state) {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    background: #f8f9fa;
    padding: 60px 24px !important;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
    justify-content: center;
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
    margin: 0 0 12px 0;
    line-height: 1.5;
}

.theme-features {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.theme-feature-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    font-size: 0.75rem;
    color: #6c757d;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    font-weight: 500;
}

.theme-feature-badge i {
    font-size: 0.7rem;
    color: #adb5bd;
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
    cursor: pointer !important;
    pointer-events: auto !important;
}

.theme-actions .btn-danger:hover:not(:disabled) {
    background: #c82333;
    border-color: #c82333;
}

.theme-actions .btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

.theme-actions .btn-danger.delete-theme-btn:not(:disabled) {
    cursor: pointer !important;
    pointer-events: auto !important;
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
        initThemeDeletion();
    });
    
    function initThemeDeletion() {
        document.querySelectorAll('.delete-theme-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const themeSlug = this.dataset.themeSlug;
                if (!themeSlug) {
                    alert('Помилка: не вказано slug теми');
                    return false;
                }
                
                deleteTheme(themeSlug);
                return false;
            });
        });
    }
    
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
        if (!slug) {
            alert('Помилка: не вказано slug теми');
            return false;
        }
        
        if (confirm('Ви впевнені, що хочете видалити цю тему?\n\nБудуть видалені:\n- Всі файли теми\n- Всі налаштування теми з бази даних (theme_settings)\n\nЦю дію неможливо скасувати!')) {
            // Создаем форму для отправки POST запроса
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            // Получаем CSRF токен из скрытого поля на странице
            const csrfInput = document.getElementById('csrf_token') || document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : '';
            
            if (!csrfToken) {
                alert('Помилка: не знайдено CSRF токен');
                console.error('CSRF token not found');
                return false;
            }
            
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="action" value="delete_theme">
                <input type="hidden" name="theme_slug" value="${slug}">
            `;
            
            document.body.appendChild(form);
            console.log('Submitting delete theme form for:', slug);
            form.submit();
        }
        
        return false;
    }
})();
</script>

<!-- Модальне вікно завантаження теми через ModalHandler -->
<?php if (!empty($uploadModalHtml)): ?>
    <?= $uploadModalHtml ?>
<?php endif; ?>