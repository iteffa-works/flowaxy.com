<?php
/**
 * Шаблон страницы просмотра логов
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

<!-- Модальное окно подтверждения удаления всех логов -->
<?php if (!empty($logFiles)): ?>
<div class="modal fade" id="clearAllLogsModal" tabindex="-1" aria-labelledby="clearAllLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearAllLogsModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Підтвердження видалення
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <p>Ви впевнені, що хочете видалити всі файли логів?</p>
                <p class="text-muted small mb-0">Цю дію неможливо скасувати. Всі файли логів будуть безповоротно видалені.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                    <input type="hidden" name="clear_logs" value="1">
                    <input type="hidden" name="file" value="all">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Видалити всі логи
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Формируем содержимое секции
ob_start();
?>
    <?php if (empty($logFiles)): ?>
        <?php
        // Пустое состояние без кнопок
        unset($actions);
        include __DIR__ . '/../components/empty-state.php';
        $icon = 'inbox';
        $title = 'Файлів логів не знайдено';
        $message = 'Системні логи будуть автоматично створюватися при виникненні подій.';
        $classes = ['logs-empty-state'];
        ?>
    <?php else: ?>
        <div class="logs-view-content">
            <!-- Выпадающее меню выбора файла лога -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-file-alt me-2 text-primary"></i>Виберіть файл логу
                </label>
                <select class="form-select" id="logFileSelect" onchange="window.location.href='?file=' + encodeURIComponent(this.value)">
                    <option value="">-- Виберіть файл --</option>
                    <?php foreach ($logFiles as $logFile): ?>
                        <option value="<?= htmlspecialchars($logFile['name']) ?>" 
                                <?= ($selectedFile === $logFile['name']) ? 'selected' : '' ?>
                                data-size="<?= htmlspecialchars($logFile['size_formatted']) ?>"
                                data-modified="<?= htmlspecialchars($logFile['modified']) ?>">
                            <?= htmlspecialchars($logFile['name']) ?> 
                            (<?= $logFile['size_formatted'] ?> • <?= $logFile['modified'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Содержимое лога -->
            <?php if (empty($logContent['file'])): ?>
                <?php
                include __DIR__ . '/../components/empty-state.php';
                $icon = 'file-alt';
                $title = 'Виберіть файл для перегляду';
                $message = 'Виберіть файл логу з випадаючого меню для перегляду його вмісту';
                $actions = '';
                $classes = ['logs-content-empty'];
                ?>
            <?php elseif (isset($logContent['error'])): ?>
                <?php
                include __DIR__ . '/../components/alert.php';
                $message = htmlspecialchars($logContent['error']);
                $type = 'danger';
                $dismissible = false;
                $classes = ['mb-0'];
                ?>
            <?php elseif (empty($logContent['lines'])): ?>
                <?php
                include __DIR__ . '/../components/empty-state.php';
                $icon = 'inbox';
                $title = 'Файл порожній';
                $message = 'Вибраний файл логу не містить записів';
                $actions = '';
                $classes = ['logs-content-empty'];
                ?>
            <?php else: ?>
                <div class="log-content-wrapper">
                    <div class="log-content-header">
                        <?php
                        // Подсчитываем количество записей каждого типа
                        $levelCounts = [];
                        foreach ($logContent['lines'] as $logEntry) {
                            $level = strtoupper($logEntry['level'] ?? 'INFO');
                            $levelCounts[$level] = ($levelCounts[$level] ?? 0) + 1;
                        }
                        ?>
                        <?php if (!empty($levelCounts)): ?>
                            <div class="log-filters">
                                <div class="log-filter-buttons">
                                    <i class="fas fa-filter log-filter-icon"></i>
                                    <button type="button" class="log-filter-btn active" data-level="all">Всі (<?= count($logContent['lines']) ?>)</button>
                                    <?php
                                    $levelLabels = [
                                        'ERROR' => ['label' => 'Помилки', 'color' => '#dc3545'],
                                        'WARNING' => ['label' => 'Попередження', 'color' => '#ffc107'],
                                        'INFO' => ['label' => 'Інформація', 'color' => '#0dcaf0'],
                                        'DEBUG' => ['label' => 'Відлагодження', 'color' => '#6c757d'],
                                        'NOTICE' => ['label' => 'Повідомлення', 'color' => '#0d6efd']
                                    ];
                                    foreach ($levelLabels as $level => $info):
                                        if (isset($levelCounts[$level])):
                                    ?>
                                        <button type="button" class="log-filter-btn" data-level="<?= strtolower($level) ?>" style="--filter-color: <?= $info['color'] ?>">
                                            <?= htmlspecialchars($info['label']) ?> (<?= $levelCounts[$level] ?>)
                                        </button>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                                <div class="log-header-actions">
                                    <select class="log-limit-select" id="logLimitSelect">
                                        <option value="50" <?= (($_GET['limit'] ?? 50) == 50) ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= (($_GET['limit'] ?? 50) == 100) ? 'selected' : '' ?>>100</option>
                                        <option value="200" <?= (($_GET['limit'] ?? 50) == 200) ? 'selected' : '' ?>>200</option>
                                        <option value="500" <?= (($_GET['limit'] ?? 50) == 500) ? 'selected' : '' ?>>500</option>
                                        <option value="0" <?= (($_GET['limit'] ?? 50) == 0) ? 'selected' : '' ?>>Всі</option>
                                    </select>
                                    <?php if (!empty($selectedFile)): ?>
                                        <form method="POST" class="log-delete-form">
                                            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                                            <input type="hidden" name="clear_logs" value="1">
                                            <input type="hidden" name="file" value="<?= htmlspecialchars($selectedFile) ?>">
                                            <button type="submit" class="log-delete-btn" onclick="return confirm('Видалити файл?')" title="Видалити файл">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="log-entries" id="logEntries">
                        <?php foreach ($logContent['lines'] as $index => $logEntry): ?>
                            <?php
                            $level = $logEntry['level'] ?? 'INFO';
                            $timestamp = $logEntry['timestamp'] ?? '';
                            $message = $logEntry['message'] ?? '';
                            $ip = $logEntry['ip'] ?? null;
                            $url = $logEntry['url'] ?? null;
                            $context = $logEntry['context'] ?? null;
                            
                            // Определяем цвет и иконку для уровня
                            $levelClasses = [
                                'ERROR' => 'danger',
                                'WARNING' => 'warning',
                                'INFO' => 'info',
                                'DEBUG' => 'secondary',
                                'NOTICE' => 'primary'
                            ];
                            
                            $levelIcons = [
                                'ERROR' => 'exclamation-circle',
                                'WARNING' => 'exclamation-triangle',
                                'INFO' => 'info-circle',
                                'DEBUG' => 'bug',
                                'NOTICE' => 'bell'
                            ];
                            
                            $levelClass = $levelClasses[$level] ?? 'secondary';
                            $levelIcon = $levelIcons[$level] ?? 'info-circle';
                            ?>
                            <div class="log-entry log-entry-<?= strtolower($level) ?>" data-level="<?= strtolower($level) ?>">
                                <div class="log-entry-row">
                                    <div class="log-level-indicator"></div>
                                    <div class="log-content-cell">
                                        <div class="log-row-main">
                                            <span class="log-level-badge"><?= htmlspecialchars($level) ?></span>
                                            <?php if ($timestamp): ?>
                                                <span class="log-separator">•</span>
                                                <span class="log-timestamp"><?= htmlspecialchars($timestamp) ?></span>
                                            <?php endif; ?>
                                            <?php if ($ip): ?>
                                                <span class="log-separator">•</span>
                                                <span class="log-ip"><?= htmlspecialchars($ip) ?></span>
                                            <?php endif; ?>
                                            <?php if ($url): ?>
                                                <span class="log-separator">•</span>
                                                <span class="log-url"><?= htmlspecialchars($url) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="log-message">
                                            <?= htmlspecialchars($message) ?>
                                        </div>
                                        <?php if ($context): ?>
                                            <details class="log-context">
                                                <summary class="log-context-toggle">
                                                    <i class="fas fa-chevron-right"></i>Контекст
                                                </summary>
                                                <div class="log-context-content">
                                                    <?php
                                                    // Если контекст - массив, кодируем в JSON, иначе используем как есть
                                                    if (is_array($context)) {
                                                        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                        // Исправляем пути - заменяем обратные слеши на прямые
                                                        $contextJson = str_replace('\\', '/', $contextJson);
                                                        
                                                        // Добавляем подсветку синтаксиса
                                                        $highlighted = preg_replace(
                                                            '/(?:")([^"]+)(?:"\s*:)/', 
                                                            '<span class="json-key">"$1"</span>:', 
                                                            htmlspecialchars($contextJson, ENT_QUOTES, 'UTF-8')
                                                        );
                                                        
                                                        $highlighted = preg_replace(
                                                            '/:\s*"([^"]*)"([,\]\}])/', 
                                                            ': <span class="json-string">"$1"</span>$2', 
                                                            $highlighted
                                                        );
                                                        
                                                        $highlighted = preg_replace(
                                                            '/:\s*(-?\d+\.?\d*)([,\]\}])/', 
                                                            ': <span class="json-number">$1</span>$2', 
                                                            $highlighted
                                                        );
                                                        
                                                        $highlighted = preg_replace(
                                                            '/:\s*(true|false|null)([,\]\}])/', 
                                                            ': <span class="json-literal">$1</span>$2', 
                                                            $highlighted
                                                        );
                                                        
                                                        echo '<pre>' . $highlighted . '</pre>';
                                                    } else {
                                                        echo '<pre>' . htmlspecialchars($context, ENT_QUOTES, 'UTF-8') . '</pre>';
                                                    }
                                                    ?>
                                                </div>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php
$sectionContent = ob_get_clean();

// Используем компонент секции контента (без заголовка, так как он уже в page-header)
$title = '';
$icon = '';
$content = $sectionContent;
$classes = ['logs-page'];
include __DIR__ . '/../components/content-section.php';
?>
