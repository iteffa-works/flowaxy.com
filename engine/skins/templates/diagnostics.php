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

<!-- Системна інформація -->
<?php if (!empty($systemInfo)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-info-circle me-2"></i>Системна інформація</span>
            </div>
            <div class="content-section-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Версія CMS:</span>
                            <span class="text-muted"><strong><?= htmlspecialchars($systemInfo['cms_version']) ?></strong></span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Сервер:</span>
                            <span class="text-muted"><?= htmlspecialchars($systemInfo['server_software']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Ім'я сервера:</span>
                            <span class="text-muted"><?= htmlspecialchars($systemInfo['server_name']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Часова зона:</span>
                            <span class="text-muted"><?= htmlspecialchars($systemInfo['timezone']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between py-2">
                            <span>Час сервера:</span>
                            <span class="text-muted"><strong><?= htmlspecialchars($systemInfo['server_time']) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Інформація про кеш -->
<?php if (!empty($cacheInfo)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-database me-2"></i>Інформація про кеш</span>
            </div>
            <div class="content-section-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Статус:</span>
                            <span class="badge <?= $cacheInfo['enabled'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $cacheInfo['enabled'] ? 'Увімкнено' : 'Вимкнено' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Всього файлів:</span>
                            <span class="text-muted"><strong><?= $cacheInfo['total_files'] ?></strong></span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Загальний розмір:</span>
                            <span class="text-muted"><?= round($cacheInfo['total_size'] / 1024 / 1024, 2) ?> MB</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Прострочених:</span>
                            <span class="text-muted"><?= $cacheInfo['expired_files'] ?> (<?= round($cacheInfo['expired_size'] / 1024 / 1024, 2) ?> MB)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Директорія:</span>
                            <span class="text-muted small"><code><?= htmlspecialchars($cacheInfo['directory']) ?></code></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between py-2">
                            <span>Доступ до запису:</span>
                            <span class="badge <?= $cacheInfo['writable'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $cacheInfo['writable'] ? 'Доступно' : 'Недоступно' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="cache_action" value="clear_all">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені, що хочете очистити весь кеш?')">
                            <i class="fas fa-trash me-1"></i>Очистити весь кеш
                        </button>
                    </form>
                    <form method="POST" class="d-inline ms-2">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="cache_action" value="clear_expired">
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-clock me-1"></i>Очистити прострочений кеш
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <?php 
    $categories = [
        'system' => ['title' => 'Система', 'icon' => 'fas fa-server', 'color' => 'info'],
        'php' => ['title' => 'PHP', 'icon' => 'fas fa-code', 'color' => 'primary'],
        'database' => ['title' => 'База даних', 'icon' => 'fas fa-database', 'color' => 'info'],
        'cache' => ['title' => 'Кеш (деталі)', 'icon' => 'fas fa-database', 'color' => 'success'],
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

