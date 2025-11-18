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
                        <?php
                        include __DIR__ . '/../components/stats-card.php';
                        $label = 'Всього файлів';
                        $value = $cacheStats['total_files'] ?? 0;
                        $color = 'primary';
                        $size = 'lg';
                        unset($icon, $classes);
                        ?>
                    </div>
                    <div class="col-md-3">
                        <?php
                        include __DIR__ . '/../components/stats-card.php';
                        $label = 'Дійсних файлів';
                        $value = $cacheStats['valid_files'] ?? 0;
                        $color = 'success';
                        $size = 'lg';
                        unset($icon, $classes);
                        ?>
                    </div>
                    <div class="col-md-3">
                        <?php
                        include __DIR__ . '/../components/stats-card.php';
                        $label = 'Прострочених';
                        $value = $cacheStats['expired_files'] ?? 0;
                        $color = 'warning';
                        $size = 'lg';
                        unset($icon, $classes);
                        ?>
                    </div>
                    <div class="col-md-3">
                        <?php
                        include __DIR__ . '/../components/stats-card.php';
                        $label = 'Загальний розмір';
                        $value = $cacheStats['total_size_formatted'] ?? '0 B';
                        $color = 'info';
                        $size = 'md';
                        unset($icon, $classes);
                        ?>
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
                <?php
                // Кнопка очистки всего кеша
                ob_start();
                $text = 'Очистити весь кеш';
                $type = 'danger';
                $icon = 'trash';
                $attributes = ['type' => 'submit', 'class' => 'btn-sm', 'onclick' => "return confirm('Ви впевнені, що хочете очистити весь кеш?')"];
                unset($url);
                include __DIR__ . '/../components/button.php';
                $clearAllBtn = ob_get_clean();
                ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                    <input type="hidden" name="clear_cache" value="1">
                    <input type="hidden" name="action" value="clear_all">
                    <?= $clearAllBtn ?>
                </form>
                <?php
                // Кнопка очистки простроченного кеша
                ob_start();
                $text = 'Очистити прострочений кеш';
                $type = 'warning';
                $icon = 'broom';
                $attributes = ['type' => 'submit', 'class' => 'btn-sm'];
                unset($url);
                include __DIR__ . '/../components/button.php';
                $clearExpiredBtn = ob_get_clean();
                ?>
                <form method="POST" class="d-inline ms-2">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                    <input type="hidden" name="clear_cache" value="1">
                    <input type="hidden" name="action" value="clear_expired">
                    <?= $clearExpiredBtn ?>
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
                    <?php
                    include __DIR__ . '/../components/empty-state.php';
                    $icon = 'inbox';
                    $title = 'Файлів кешу не знайдено';
                    $message = '';
                    $actions = '';
                    $classes = ['p-3'];
                    ?>
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

