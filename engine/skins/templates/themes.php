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
                <a href="https://flowaxy.com/marketplace/themes" target="_blank" class="btn btn-primary">
                    <i class="fas fa-store me-1"></i>Перейти до маркетплейсу
                </a>
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
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="theme_slug" value="<?= htmlspecialchars($theme['slug']) ?>">
                                    <input type="hidden" name="activate_theme" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check me-1"></i>Активувати
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
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0;
    padding: 16px 20px;
    font-weight: 600;
    color: #2d3748;
    font-size: 0.95rem;
}

.content-section-body {
    background: #fff;
    border: 1px solid #e3e6f0;
    border-top: none;
    border-radius: 0 0 12px 12px;
    padding: 24px;
}

.themes-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2.5rem;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
}

.themes-empty-state h4 {
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 12px;
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
    padding: 24px;
    background: #fff;
    border: 2px solid #e3e6f0;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.theme-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.theme-item:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border-color: #cbd5e0;
    transform: translateY(-4px);
}

.theme-item:hover::before {
    opacity: 1;
}

.theme-item.theme-active {
    border: 2px solid #0d6efd;
    background: linear-gradient(to right, rgba(13, 110, 253, 0.04) 0%, #fff 8%);
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.08), 0 8px 32px rgba(13, 110, 253, 0.15);
}

.theme-item.theme-active::before {
    opacity: 1;
    background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%);
}

.theme-item-preview {
    flex: 0 0 140px;
    height: 90px;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.theme-item:hover .theme-item-preview {
    transform: scale(1.05);
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
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.theme-preview-placeholder i {
    font-size: 3rem;
    opacity: 0.5;
}

.theme-active-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.875rem;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
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
    font-size: 1.35rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.theme-active-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

.theme-active-badge i {
    font-size: 0.7rem;
}

.theme-item-description {
    font-size: 0.95rem;
    color: #718096;
    margin: 0;
    line-height: 1.6;
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
    color: #718096;
    font-weight: 500;
    padding: 6px 12px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.theme-meta-item:hover {
    background: #e9ecef;
    color: #4a5568;
}

.theme-meta-item i {
    color: #adb5bd;
    font-size: 0.8rem;
}

.theme-customization-badge {
    color: #0d6efd;
    font-weight: 600;
    background: rgba(13, 110, 253, 0.1) !important;
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
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
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
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.theme-actions-group .btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
}

.theme-actions-group .btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
    background: #6c757d !important;
    border-color: #6c757d !important;
    box-shadow: none;
}


.theme-item .btn-primary:not(.theme-actions-group .btn-primary) {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1.5;
    vertical-align: middle;
}

.theme-item .btn-primary:not(.theme-actions-group .btn-primary) i {
    display: inline-flex;
    align-items: center;
    line-height: 1;
}

.theme-item .btn-primary:not(.theme-actions-group .btn-primary):hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
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
</style>
