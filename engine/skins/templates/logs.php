<?php
/**
 * Шаблон страницы логов
 */

// Вспомогательная функция для форматирования размера
if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes): string {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

$logs = $logs ?? [];
$stats = $stats ?? [];
$currentType = $currentType ?? '';
$limit = $limit ?? 100;
$offset = $offset ?? 0;
$types = $types ?? [];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <!-- Статистика -->
        <div class="row">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?= number_format($stats['total'] ?? 0) ?></h5>
                        <p class="card-text text-muted mb-0">Всего логов</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h5 class="card-title text-danger"><?= number_format($stats['by_type']['error'] ?? 0) ?></h5>
                        <p class="card-text text-muted mb-0">Ошибки</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><?= number_format($stats['by_type']['warning'] ?? 0) ?></h5>
                        <p class="card-text text-muted mb-0">Предупреждения</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <h5 class="card-title text-info"><?= number_format($stats['by_type']['info'] ?? 0) ?></h5>
                        <p class="card-text text-muted mb-0">Информация</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?= number_format($stats['by_type']['success'] ?? 0) ?></h5>
                        <p class="card-text text-muted mb-0">Успехи</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?= formatBytes($stats['total_size'] ?? 0) ?></h5>
                        <p class="card-text text-muted mb-0">Размер логов</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt"></i> Логи системы
                </h5>
                <div>
                    <!-- Фильтр по типу -->
                    <div class="btn-group me-2" role="group">
                        <a href="<?= adminUrl('logs') ?>" 
                           class="btn btn-sm btn-<?= empty($currentType) ? 'primary' : 'outline-secondary' ?>">
                            Все
                        </a>
                        <?php foreach ($types as $typeKey => $typeName): ?>
                            <a href="<?= adminUrl('logs?type=' . $typeKey) ?>" 
                               class="btn btn-sm btn-<?= $currentType === $typeKey ? 'primary' : 'outline-secondary' ?>">
                                <?= htmlspecialchars($typeName) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Кнопка очистки -->
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                        <i class="fas fa-trash"></i> Очистить
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Логи не найдены
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Время</th>
                                    <th style="width: 100px;">Тип</th>
                                    <th>Сообщение</th>
                                    <th style="width: 200px;">Контекст</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <small><?= htmlspecialchars($log['timestamp'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $typeClass = 'secondary';
                                            $typeIcon = 'circle';
                                            switch ($log['type'] ?? '') {
                                                case 'error':
                                                    $typeClass = 'danger';
                                                    $typeIcon = 'exclamation-circle';
                                                    break;
                                                case 'warning':
                                                    $typeClass = 'warning';
                                                    $typeIcon = 'exclamation-triangle';
                                                    break;
                                                case 'info':
                                                    $typeClass = 'info';
                                                    $typeIcon = 'info-circle';
                                                    break;
                                                case 'success':
                                                    $typeClass = 'success';
                                                    $typeIcon = 'check-circle';
                                                    break;
                                                case 'debug':
                                                    $typeClass = 'secondary';
                                                    $typeIcon = 'bug';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?= $typeClass ?>">
                                                <i class="fas fa-<?= $typeIcon ?>"></i> <?= htmlspecialchars(ucfirst($log['type'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($log['message'] ?? '') ?></code>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['context'])): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary" 
                                                        onclick="showContext(<?= htmlspecialchars(json_encode($log['context'], JSON_UNESCAPED_UNICODE)) ?>)">
                                                    <i class="fas fa-eye"></i> Показать
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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

<!-- Модальное окно очистки логов -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= adminUrl('logs') ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Очистка логов</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="clear_logs">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Тип логов для очистки:</label>
                        <select name="type" class="form-select">
                            <option value="">Все типы</option>
                            <?php foreach ($types as $typeKey => $typeName): ?>
                                <option value="<?= htmlspecialchars($typeKey) ?>"><?= htmlspecialchars($typeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Удалить логи старше (дней):</label>
                        <input type="number" name="days" class="form-control" min="1" placeholder="Оставить пустым для удаления всех">
                        <small class="form-text text-muted">Оставьте пустым, чтобы удалить все логи выбранного типа</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Это действие нельзя отменить!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Очистить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showContext(context) {
    alert('Контекст:\n\n' + JSON.stringify(context, null, 2));
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
</script>

