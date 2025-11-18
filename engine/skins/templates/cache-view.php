<?php
/**
 * Шаблон страницы просмотра кеша
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

<div class="row g-3">
    <!-- Статистика кеша -->
    <div class="col-12">
        <div class="card border-0 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>Статистика кешу
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="fw-semibold text-muted small">Всього файлів</div>
                            <div class="h4 mb-0 text-primary"><?= $cacheStats['total_files'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="fw-semibold text-muted small">Дійсних файлів</div>
                            <div class="h4 mb-0 text-success"><?= $cacheStats['valid_files'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="fw-semibold text-muted small">Прострочених</div>
                            <div class="h4 mb-0 text-warning"><?= $cacheStats['expired_files'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="fw-semibold text-muted small">Загальний розмір</div>
                            <div class="h5 mb-0 text-info"><?= $cacheStats['total_size_formatted'] ?? '0 B' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Дії з кешем -->
    <div class="col-12">
        <div class="card border-0 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-tools me-2 text-primary"></i>Дії з кешем
                </h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                    <input type="hidden" name="clear_cache" value="1">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені, що хочете очистити весь кеш?')">
                        <i class="fas fa-trash me-1"></i>Очистити весь кеш
                    </button>
                </form>
                <form method="POST" class="d-inline ms-2">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                    <input type="hidden" name="clear_cache" value="1">
                    <input type="hidden" name="action" value="clear_expired">
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-broom me-1"></i>Очистити прострочений кеш
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Список файлів кешу -->
    <div class="col-12">
        <div class="card border-0 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-list me-2 text-primary"></i>Файли кешу
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($cacheFiles)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">Файлів кешу не знайдено</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">Ключ</th>
                                    <th class="small">Розмір</th>
                                    <th class="small">Створено</th>
                                    <th class="small">Термін дії</th>
                                    <th class="small">TTL</th>
                                    <th class="small">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cacheFiles as $file): ?>
                                    <tr class="<?= $file['is_expired'] ? 'table-warning' : '' ?>">
                                        <td class="small">
                                            <code class="text-muted"><?= htmlspecialchars(substr($file['key'], 0, 40)) ?><?= strlen($file['key']) > 40 ? '...' : '' ?></code>
                                        </td>
                                        <td class="small"><?= $file['size_formatted'] ?></td>
                                        <td class="small"><?= $file['created'] ?></td>
                                        <td class="small"><?= $file['expires'] ?></td>
                                        <td class="small"><?= $file['ttl_formatted'] ?></td>
                                        <td class="small">
                                            <?php if ($file['is_expired']): ?>
                                                <span class="badge bg-warning">Прострочено</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Дійсний</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.cache-view .table {
    font-size: 0.875rem;
}

.cache-view .table th {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.cache-view .table td {
    vertical-align: middle;
}

.cache-view code {
    font-size: 0.75rem;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
}

.cache-view .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

