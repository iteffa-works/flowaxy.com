<?php
/**
 * Шаблон сторінки діагностики системи
 */
?>

<!-- Уведомлення -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php 
    $categories = [
        'php' => ['title' => 'PHP', 'icon' => 'fas fa-code', 'color' => 'primary'],
        'database' => ['title' => 'База даних', 'icon' => 'fas fa-database', 'color' => 'info'],
        'permissions' => ['title' => 'Права доступу', 'icon' => 'fas fa-key', 'color' => 'warning'],
        'extensions' => ['title' => 'Розширення PHP', 'icon' => 'fas fa-puzzle-piece', 'color' => 'success'],
        'configuration' => ['title' => 'Конфігурація', 'icon' => 'fas fa-cog', 'color' => 'secondary'],
        'modules' => ['title' => 'Модулі', 'icon' => 'fas fa-cube', 'color' => 'dark'],
        'plugins' => ['title' => 'Плагіни', 'icon' => 'fas fa-plug', 'color' => 'primary']
    ];
    
    foreach ($categories as $category => $catInfo):
        $checks = $diagnostics[$category] ?? [];
        if (empty($checks)) continue;
    ?>
        <div class="col-lg-6 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span>
                        <i class="<?= $catInfo['icon'] ?> me-2"></i>
                        <?= $catInfo['title'] ?>
                    </span>
                </div>
                <div class="content-section-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Параметр</th>
                                    <th style="width: 30%;">Значення</th>
                                    <th style="width: 30%;">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($checks as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td>
                                            <code class="small"><?= htmlspecialchars($check['value']) ?></code>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = match($check['status']) {
                                                'success' => 'bg-success',
                                                'error' => 'bg-danger',
                                                'warning' => 'bg-warning',
                                                'info' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?php
                                                $statusText = match($check['status']) {
                                                    'success' => 'OK',
                                                    'error' => 'Помилка',
                                                    'warning' => 'Попередження',
                                                    'info' => 'Інфо',
                                                    default => 'Невідомо'
                                                };
                                                echo $statusText;
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if (!empty($check['message'])): ?>
                                        <tr>
                                            <td colspan="3" class="small text-muted bg-light">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?= htmlspecialchars($check['message']) ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Порада:</strong> Якщо ви бачите помилки або попередження, перевірте налаштування системи та права доступу до директорій.
        </div>
    </div>
</div>

