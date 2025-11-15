<?php
/**
 * Шаблон страницы системной информации
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-info-circle me-2"></i>Основна інформація</span>
            </div>
            <div class="content-section-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Версія CMS:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['cms_version']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>PHP версія:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['php_version']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>PHP SAPI:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['php_sapi']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>MySQL версія:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['mysql_version']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Сервер:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['server_software']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Ім'я сервера:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['server_name']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span>Часова зона:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['timezone']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-memory me-2"></i>Пам'ять</span>
            </div>
            <div class="content-section-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Ліміт пам'яті:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['memory_limit']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Використання:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['memory_usage']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span>Пікове використання:</span>
                        <span class="text-muted"><?= htmlspecialchars($systemInfo['memory_peak']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="content-section mt-4">
            <div class="content-section-header">
                <span><i class="fas fa-clock me-2"></i>Час сервера</span>
            </div>
            <div class="content-section-body">
                <p class="mb-0"><?= htmlspecialchars($systemInfo['server_time']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-folder me-2"></i>Права доступу до папок</span>
            </div>
            <div class="content-section-body">
                <?php foreach ($systemInfo['folders'] as $name => $folder): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 <?= $name !== 'themes' ? 'border-bottom' : '' ?>">
                        <div>
                            <strong><?= htmlspecialchars(ucfirst($name)) ?></strong>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($folder['path']) ?></small>
                            <br>
                            <small class="text-muted">Права: <?= htmlspecialchars($folder['permissions']) ?></small>
                        </div>
                        <div>
                            <?php if ($folder['status'] === 'ok'): ?>
                                <span class="badge bg-success">OK</span>
                            <?php elseif ($folder['status'] === 'warning'): ?>
                                <span class="badge bg-warning">Увага</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Помилка</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-puzzle-piece me-2"></i>Розширення PHP</span>
            </div>
            <div class="content-section-body">
                <?php foreach ($systemInfo['extensions'] as $ext => $loaded): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 <?= $ext !== 'fileinfo' ? 'border-bottom' : '' ?>">
                        <span><?= htmlspecialchars($ext) ?></span>
                        <?php if ($loaded): ?>
                            <span class="badge bg-success">Встановлено</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Відсутнє</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

