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

<div class="content-section">
    <div class="content-section-header">
        <span><i class="fas fa-palette me-2"></i>Теми</span>
    </div>
    <div class="content-section-body">
        <?php if (empty($themes)): ?>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Темы не найдены. Установите тему по умолчанию через миграцию базы данных.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($themes as $theme): ?>
                    <?php $isActive = ($theme['is_active'] == 1); ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="theme-card card h-100 position-relative overflow-hidden <?= $isActive ? 'theme-active' : '' ?>">
                            <?php if ($isActive): ?>
                                <div class="theme-active-badge">
                                    <i class="fas fa-check-circle me-1"></i>Активна
                                </div>
                            <?php endif; ?>
                            
                            <div class="theme-screenshot">
                                <?php if ($theme['screenshot']): ?>
                                    <img src="<?= htmlspecialchars($theme['screenshot']) ?>" 
                                         class="theme-screenshot-img" 
                                         alt="<?= htmlspecialchars($theme['name']) ?>">
                                <?php else: ?>
                                    <div class="theme-screenshot-placeholder">
                                        <i class="fas fa-palette"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <div class="theme-header">
                                    <h6 class="theme-name">
                                        <?= htmlspecialchars($theme['name']) ?>
                                    </h6>
                                    <span class="theme-type">Тема</span>
                                </div>
                                
                                <div class="theme-meta">
                                    <span class="theme-version">v<?= htmlspecialchars($theme['version'] ?? '1.0.0') ?></span>
                                    <?php if ($theme['author']): ?>
                                        <span class="theme-author"><?= htmlspecialchars($theme['author']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <?php if (!$isActive): ?>
                                    <form method="POST" class="d-inline w-100">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="theme_slug" value="<?= htmlspecialchars($theme['slug']) ?>">
                                        <input type="hidden" name="activate_theme" value="1">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-check me-1"></i>Активувати
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="theme-actions">
                                        <?php if (isset($themesWithCustomization[$theme['slug']]) && $themesWithCustomization[$theme['slug']]): ?>
                                            <a href="<?= adminUrl('customizer') ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-paint-brush me-1"></i>Налаштувати
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= SITE_URL ?>" class="btn btn-outline-secondary btn-sm" target="_blank" title="Переглянути сайт">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.theme-card {
    border: 1px solid #e1e5e9;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.theme-card:hover {
    box-shadow: 0 12px 32px rgba(0,0,0,0.15);
    transform: translateY(-6px);
    border-color: #cbd5e0;
}

.theme-card.theme-active {
    border: 2px solid #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1), 0 4px 16px rgba(13, 110, 253, 0.15);
}

.theme-active-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
    display: flex;
    align-items: center;
    letter-spacing: 0.3px;
    backdrop-filter: blur(10px);
}

.theme-active-badge i {
    font-size: 0.7rem;
}

.theme-screenshot {
    height: 180px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    overflow: hidden;
    position: relative;
}

.theme-screenshot-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-card:hover .theme-screenshot-img {
    transform: scale(1.08);
}

.theme-screenshot-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.theme-screenshot-placeholder i {
    font-size: 3.5rem;
    opacity: 0.6;
}

.theme-card .card-body {
    padding: 18px;
}

.theme-header {
    margin-bottom: 14px;
}

.theme-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 6px 0;
    line-height: 1.2;
    letter-spacing: -0.3px;
}

.theme-type {
    font-size: 0.8rem;
    color: #718096;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.theme-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 14px;
    border-top: 1px solid #edf2f7;
    font-size: 0.8rem;
    color: #718096;
}

.theme-version {
    font-weight: 600;
    color: #4a5568;
}

.theme-author {
    font-weight: 500;
    color: #718096;
}

.theme-card .card-footer {
    padding: 14px 18px;
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
    border-top: 1px solid #edf2f7;
}

.theme-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.theme-actions .btn {
    flex: 1;
    min-width: 0;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 8px 16px;
    transition: all 0.2s ease;
    letter-spacing: 0.2px;
}

.theme-actions .btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

.theme-actions .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
}

.theme-actions .btn-outline-secondary {
    flex: 0 0 auto;
    width: 44px;
    height: 36px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid #e2e8f0;
    background: #fff;
}

.theme-actions .btn-outline-secondary:hover {
    background: #f8f9fa;
    border-color: #cbd5e0;
    transform: translateY(-1px);
}

.theme-card .btn:not(.btn-outline-secondary) {
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 8px 16px;
    transition: all 0.2s ease;
}

.theme-card .btn-primary:not(.theme-actions .btn-primary) {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

.theme-card .btn-primary:not(.theme-actions .btn-primary):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
}
</style>

