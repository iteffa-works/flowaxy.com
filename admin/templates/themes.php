<?php
/**
 * Шаблон страницы управления темами
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Информация об активной теме -->
<?php if (isset($activeTheme) && $activeTheme): ?>
    <div class="content-section mb-4">
        <div class="content-section-header">
            <span><i class="fas fa-star me-2"></i>Активна тема</span>
        </div>
        <div class="content-section-body">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <?php if ($activeTheme['screenshot']): ?>
                        <img src="<?= htmlspecialchars($activeTheme['screenshot']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($activeTheme['name']) ?>">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 120px;">
                            <i class="fas fa-palette fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <h5 class="mb-1"><?= htmlspecialchars($activeTheme['name']) ?></h5>
                    <p class="text-muted mb-2"><?= htmlspecialchars($activeTheme['description'] ?? '') ?></p>
                    <div class="small text-muted">
                        <strong>Версія:</strong> <?= htmlspecialchars($activeTheme['version'] ?? '1.0.0') ?>
                        <?php if ($activeTheme['author']): ?>
                            | <strong>Автор:</strong> <?= htmlspecialchars($activeTheme['author']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <a href="<?= adminUrl('customizer') ?>" class="btn btn-primary">
                        <i class="fas fa-paint-brush me-1"></i>Налаштувати
                    </a>
                    <a href="<?= SITE_URL ?>" class="btn btn-outline-secondary mt-2" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>Переглянути сайт
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Список тем -->
<div class="content-section">
    <div class="content-section-header">
        <span><i class="fas fa-palette me-2"></i>Доступні теми</span>
    </div>
    <div class="content-section-body">
        <?php if (empty($themes)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Темы не найдены. Установите тему по умолчанию через миграцию базы данных.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($themes as $theme): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 <?= ($theme['is_active'] == 1) ? 'border-primary shadow-sm' : '' ?>">
                            <?php if ($theme['screenshot']): ?>
                                <img src="<?= htmlspecialchars($theme['screenshot']) ?>" class="card-img-top" alt="<?= htmlspecialchars($theme['name']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-palette fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($theme['name']) ?>
                                    <?php if ($theme['is_active'] == 1): ?>
                                        <span class="badge bg-primary">Активна</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($theme['description'] ?? '') ?>
                                </p>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>Версія:</strong> <?= htmlspecialchars($theme['version'] ?? '1.0.0') ?><br>
                                        <?php if ($theme['author']): ?>
                                            <strong>Автор:</strong> <?= htmlspecialchars($theme['author']) ?><br>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <?php if ($theme['is_active'] != 1): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="theme_slug" value="<?= htmlspecialchars($theme['slug']) ?>">
                                        <input type="hidden" name="activate_theme" value="1">
                                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                            <i class="fas fa-check me-1"></i>Активувати
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm w-100 mb-2" disabled>
                                        <i class="fas fa-check-circle me-1"></i>Активна
                                    </button>
                                <?php endif; ?>
                                <a href="<?= adminUrl('customizer') ?>" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-paint-brush me-1"></i>Налаштувати
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

