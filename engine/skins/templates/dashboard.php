<?php
/**
 * Шаблон главной страницы админки
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Статистические карточки -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary h-100 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Плагіни
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['plugins'] ?? 0 ?></div>
                        <div class="text-xs text-muted">Активних плагінів</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-puzzle-piece fa-2x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success h-100 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Заявки
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['submissions'] ?? 0 ?></div>
                        <div class="text-xs text-muted">
                            Всього (сьогодні: <?= $stats['submissions_today'] ?? 0 ?>)
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope fa-2x text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info h-100 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Товари
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['products'] ?? 0 ?></div>
                        <div class="text-xs text-muted">Активних товарів</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning h-100 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Медіа
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['media'] ?? 0 ?></div>
                        <div class="text-xs text-muted">Файлів у бібліотеці</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-images fa-2x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Основной контент -->
<div class="row">
    <!-- Быстрые действия -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-primary">Швидкі дії</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= adminUrl('plugins') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-puzzle-piece me-2"></i>Керування плагінами
                    </a>
                    <a href="<?= adminUrl('settings') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>Налаштування
                    </a>
                    <?php if (isset($stats['forms']) && $stats['forms'] > 0): ?>
                        <a href="<?= adminUrl('pb-form-submissions') ?>" class="btn btn-outline-info">
                            <i class="fas fa-edit me-2"></i>Форми та заявки
                        </a>
                    <?php endif; ?>
                    <?php if (isset($stats['products']) && $stats['products'] > 0): ?>
                        <a href="<?= adminUrl('pb-catalog') ?>" class="btn btn-outline-success">
                            <i class="fas fa-box me-2"></i>Каталог товарів
                        </a>
                    <?php endif; ?>
                    <a href="<?= adminUrl('media') ?>" class="btn btn-outline-warning">
                        <i class="fas fa-images me-2"></i>Медіа-бібліотека
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Последние заявки -->
    <?php if (!empty($stats['recent_submissions'])): ?>
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Останні заявки</h6>
                <?php if (isset($stats['forms']) && $stats['forms'] > 0): ?>
                    <a href="<?= adminUrl('pb-form-submissions') ?>" class="btn btn-sm btn-outline-primary">
                        Всі заявки
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Дані</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_submissions'] as $submission): ?>
                                <?php
                                $data = json_decode($submission['data'], true);
                                $name = $data['name'] ?? $data['Ім\'я'] ?? $data['ім\'я'] ?? 'Без імені';
                                $phone = $data['phone'] ?? $data['Телефон'] ?? $data['телефон'] ?? '';
                                if (is_array($name)) $name = implode(', ', $name);
                                if (is_array($phone)) $phone = implode(', ', $phone);
                                ?>
                                <tr>
                                    <td>#<?= $submission['id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($name) ?></div>
                                        <?php if ($phone): ?>
                                            <small class="text-muted"><?= htmlspecialchars($phone) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d.m.Y H:i', strtotime($submission['created_at'])) ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Системная информация -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-primary">Система</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span>PHP версія:</span>
                        <span class="text-muted"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Час сервера:</span>
                        <span class="text-muted"><?= date('d.m.Y H:i') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Використання пам'яті:</span>
                        <span class="text-muted"><?= round(memory_get_usage()/1024/1024, 1) ?> MB</span>
                    </div>
                    <?php if (isset($stats['categories'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Категорії:</span>
                        <span class="text-muted"><?= $stats['categories'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($stats['forms'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Форми:</span>
                        <span class="text-muted"><?= $stats['forms'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Дополнительная статистика -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-primary">Статистика</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1 text-primary"><?= $stats['plugins'] ?? 0 ?></div>
                            <div class="small text-muted">Плагінів</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1 text-success"><?= $stats['submissions'] ?? 0 ?></div>
                            <div class="small text-muted">Заявок</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1 text-info"><?= $stats['products'] ?? 0 ?></div>
                            <div class="small text-muted">Товарів</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1 text-warning"><?= $stats['media'] ?? 0 ?></div>
                            <div class="small text-muted">Медіа файлів</div>
                        </div>
                    </div>
                    <?php if (isset($stats['categories'])): ?>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1 text-secondary"><?= $stats['categories'] ?></div>
                            <div class="small text-muted">Категорій</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($stats['forms'])): ?>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h3 mb-1 text-dark"><?= $stats['forms'] ?></div>
                            <div class="small text-muted">Форм</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
</style>
