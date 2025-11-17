<?php
/**
 * Шаблон страницы просмотра логов
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- Список файлов логов -->
    <div class="col-md-4">
        <div class="card border-0 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-folder me-2 text-primary"></i>Файли логів
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logFiles)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0 small">Файлів логів не знайдено</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($logFiles as $logFile): ?>
                            <a href="?file=<?= urlencode($logFile['name']) ?>" 
                               class="list-group-item list-group-item-action <?= ($selectedFile === $logFile['name']) ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="fw-medium small"><?= htmlspecialchars($logFile['name']) ?></div>
                                        <div class="text-muted small mt-1">
                                            <?= $logFile['size_formatted'] ?> • <?= $logFile['modified'] ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Дії з логами -->
        <?php if (!empty($logFiles)): ?>
            <div class="card border-0 mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-tools me-2 text-primary"></i>Дії
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                        <input type="hidden" name="clear_logs" value="1">
                        <input type="hidden" name="file" value="all">
                        <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Ви впевнені, що хочете очистити всі логи?')">
                            <i class="fas fa-trash me-1"></i>Очистити всі логи
                        </button>
                    </form>
                    <?php if (!empty($selectedFile)): ?>
                        <form method="POST" class="d-inline mt-2">
                            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                            <input type="hidden" name="clear_logs" value="1">
                            <input type="hidden" name="file" value="<?= htmlspecialchars($selectedFile) ?>">
                            <button type="submit" class="btn btn-warning btn-sm w-100" onclick="return confirm('Ви впевнені, що хочете очистити цей файл?')">
                                <i class="fas fa-broom me-1"></i>Очистити поточний файл
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Содержимое лога -->
    <div class="col-md-8">
        <div class="card border-0 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-file-alt me-2 text-primary"></i>
                    <?php if (!empty($logContent['file'])): ?>
                        <?= htmlspecialchars($logContent['file']) ?>
                        <span class="text-muted small ms-2">(показано останні <?= count($logContent['lines']) ?> з <?= $logContent['total_lines'] ?> рядків)</span>
                    <?php else: ?>
                        Виберіть файл для перегляду
                    <?php endif; ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logContent['file'])): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p class="mb-0">Виберіть файл логу зі списку для перегляду</p>
                    </div>
                <?php elseif (isset($logContent['error'])): ?>
                    <div class="p-3 alert alert-danger mb-0">
                        <?= htmlspecialchars($logContent['error']) ?>
                    </div>
                <?php elseif (empty($logContent['lines'])): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">Файл порожній</p>
                    </div>
                <?php else: ?>
                    <div class="log-content">
                        <pre class="mb-0 p-3 bg-dark text-light small" style="max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; line-height: 1.5;"><?php
                            foreach ($logContent['lines'] as $line) {
                                echo htmlspecialchars($line['content']) . "\n";
                            }
                        ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.logs-view .list-group-item {
    border-left: none;
    border-right: none;
    padding: 0.75rem 1rem;
}

.logs-view .list-group-item:first-child {
    border-top: none;
}

.logs-view .list-group-item:last-child {
    border-bottom: none;
}

.logs-view .list-group-item.active {
    background-color: #0073aa;
    border-color: #0073aa;
    color: #ffffff;
}

.logs-view .list-group-item.active .text-muted {
    color: rgba(255, 255, 255, 0.75) !important;
}

.logs-view pre {
    border-radius: 0;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.logs-view .card-body {
    max-height: none;
}
</style>

