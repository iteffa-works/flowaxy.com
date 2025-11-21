<?php
/**
 * Шаблон страницы настроек (список ссылок по категориям)
 * 
 * @var array $settingsCategories Категории настроек
 */
?>

<div class="row g-4">
    <?php foreach ($settingsCategories as $categoryKey => $category): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="<?= htmlspecialchars($category['icon']) ?> me-2 text-primary"></i>
                        <?= htmlspecialchars($category['title']) ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($category['items'] as $item): ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>" 
                               class="list-group-item list-group-item-action border-0 px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="settings-icon me-3">
                                        <i class="<?= htmlspecialchars($item['icon']) ?> text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($item['title']) ?></h6>
                                        <p class="mb-0 text-muted small"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                                    </div>
                                    <div class="ms-3">
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.settings-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
    flex-shrink: 0;
}

.settings-icon i {
    font-size: 1.1rem;
}

.list-group-item {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0 !important;
}

.list-group-item:last-child {
    border-bottom: none !important;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    transform: translateX(4px);
}

.list-group-item:hover .settings-icon {
    background: #e9ecef;
}

.list-group-item:hover .fa-chevron-right {
    transform: translateX(4px);
    transition: transform 0.2s ease;
}

.card {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
}

.card-header h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #23282d;
}

.card-header .text-primary {
    color: #0073aa !important;
}

@media (max-width: 768px) {
    .settings-icon {
        width: 36px;
        height: 36px;
    }
    
    .settings-icon i {
        font-size: 1rem;
    }
    
    .list-group-item {
        padding: 0.75rem 1rem !important;
    }
}
</style>
