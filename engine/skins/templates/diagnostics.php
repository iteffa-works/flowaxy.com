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

<!-- Навігація -->
<div class="doc-nav mb-4">
    <ul class="nav nav-tabs border-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-system" data-bs-toggle="tab" data-bs-target="#content-system" type="button" role="tab">
                <i class="fas fa-server me-2"></i>Система
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-php" data-bs-toggle="tab" data-bs-target="#content-php" type="button" role="tab">
                <i class="fas fa-code me-2"></i>PHP
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-database" data-bs-toggle="tab" data-bs-target="#content-database" type="button" role="tab">
                <i class="fas fa-database me-2"></i>База даних
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-cache" data-bs-toggle="tab" data-bs-target="#content-cache" type="button" role="tab">
                <i class="fas fa-database me-2"></i>Кеш
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-permissions" data-bs-toggle="tab" data-bs-target="#content-permissions" type="button" role="tab">
                <i class="fas fa-key me-2"></i>Права доступу
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-extensions" data-bs-toggle="tab" data-bs-target="#content-extensions" type="button" role="tab">
                <i class="fas fa-puzzle-piece me-2"></i>Розширення
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-configuration" data-bs-toggle="tab" data-bs-target="#content-configuration" type="button" role="tab">
                <i class="fas fa-cog me-2"></i>Конфігурація
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-modules" data-bs-toggle="tab" data-bs-target="#content-modules" type="button" role="tab">
                <i class="fas fa-cube me-2"></i>Модулі
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-plugins" data-bs-toggle="tab" data-bs-target="#content-plugins" type="button" role="tab">
                <i class="fas fa-plug me-2"></i>Плагіни
            </button>
        </li>
    </ul>
</div>

<!-- Контент -->
<div class="tab-content">
    <!-- Система -->
    <div class="tab-pane fade show active" id="content-system" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-server text-primary me-2"></i>Системна інформація
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['system'])): ?>
                                <?php foreach ($diagnostics['system'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PHP -->
    <div class="tab-pane fade" id="content-php" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-code text-primary me-2"></i>Налаштування PHP
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['php'])): ?>
                                <?php foreach ($diagnostics['php'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- База даних -->
    <div class="tab-pane fade" id="content-database" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-database text-primary me-2"></i>База даних
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['database'])): ?>
                                <?php foreach ($diagnostics['database'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Кеш -->
    <div class="tab-pane fade" id="content-cache" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-database text-primary me-2"></i>Інформація про кеш
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['cache'])): ?>
                                <?php foreach ($diagnostics['cache'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($cacheInfo)): ?>
                <div class="border-top pt-3 mt-3">
                    <h6 class="mb-3">Управління кешем</h6>
                    <div class="d-flex gap-2">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="cache_action" value="clear_all">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені, що хочете очистити весь кеш?')">
                                <i class="fas fa-trash me-1"></i>Очистити весь кеш
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="cache_action" value="clear_expired">
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="fas fa-clock me-1"></i>Очистити прострочений кеш
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Права доступу -->
    <div class="tab-pane fade" id="content-permissions" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-key text-primary me-2"></i>Права доступу до директорій
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['permissions'])): ?>
                                <?php foreach ($diagnostics['permissions'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Розширення PHP -->
    <div class="tab-pane fade" id="content-extensions" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-puzzle-piece text-primary me-2"></i>Розширення PHP
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['extensions'])): ?>
                                <?php foreach ($diagnostics['extensions'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Конфігурація -->
    <div class="tab-pane fade" id="content-configuration" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog text-primary me-2"></i>Конфігурація системи
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['configuration'])): ?>
                                <?php foreach ($diagnostics['configuration'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модулі -->
    <div class="tab-pane fade" id="content-modules" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-cube text-primary me-2"></i>Системні модулі
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['modules'])): ?>
                                <?php foreach ($diagnostics['modules'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Плагіни -->
    <div class="tab-pane fade" id="content-plugins" role="tabpanel">
        <div class="doc-section-item">
            <div class="doc-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-plug text-primary me-2"></i>Плагіни
                </h5>
            </div>
            <div class="doc-section-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Параметр</th>
                                <th style="width: 35%;">Значення</th>
                                <th style="width: 25%;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diagnostics['plugins'])): ?>
                                <?php foreach ($diagnostics['plugins'] as $check): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($check['name']) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($check['value']) ?></code></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Flat дизайн */
.doc-nav {
    border-bottom: 2px solid #e9ecef;
}

.doc-nav .nav-tabs {
    border-bottom: none;
    margin-bottom: 0;
}

.doc-nav .nav-link {
    border: none;
    border-radius: 0;
    padding: 0.875rem 1.25rem;
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9375rem;
    border-bottom: 3px solid transparent;
    transition: all 0.15s ease;
    background: transparent;
}

.doc-nav .nav-link:hover {
    color: #0d6efd;
    background: #f8f9fa;
    border-bottom-color: #0d6efd;
}

.doc-nav .nav-link.active {
    color: #0d6efd;
    background: transparent;
    border-bottom-color: #0d6efd;
    font-weight: 600;
}

.doc-nav .nav-link i {
    font-size: 0.875rem;
}

/* Секції */
.doc-section-item {
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: white;
}

.doc-section-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}

.doc-section-header h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
}

.doc-section-body {
    padding: 1.25rem;
}

/* Таблиці */
.doc-section-body table {
    font-size: 0.875rem;
}

.doc-section-body th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.doc-section-body td code {
    font-size: 0.8125rem;
    color: #495057;
    background: #f8f9fa;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
}

/* Бейджі */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.375rem 0.625rem;
}

.bg-success { background-color: #28a745 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-secondary { background-color: #6c757d !important; }

/* Адаптивність */
@media (max-width: 991.98px) {
    .doc-nav .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .doc-nav .nav-link {
        white-space: nowrap;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 767.98px) {
    .doc-section-body {
        padding: 0.75rem;
    }
    
    .doc-section-body .table-responsive {
        font-size: 0.8125rem;
    }
}
</style>
