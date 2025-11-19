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
                    <div class="log-content-header mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Показано останні <?= count($logContent['lines']) ?> з <?= $logContent['total_lines'] ?> рядків
                            </span>
                        </div>
                    </div>
                    <div class="log-content">
                        <pre class="mb-0 p-3 bg-dark text-light small"><?php
                            foreach ($logContent['lines'] as $line) {
                                echo htmlspecialchars($line['content']) . "\n";
                            }
                        ?></pre>
                    </div>
                </div>
                
                <!-- Кнопка удаления текущего файла -->
                <?php if (!empty($selectedFile)): ?>
                    <div class="mt-3 pt-3 border-top">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                            <input type="hidden" name="clear_logs" value="1">
                            <input type="hidden" name="file" value="<?= htmlspecialchars($selectedFile) ?>">
                            <?php
                            ob_start();
                            $text = 'Видалити поточний файл';
                            $type = 'danger';
                            $icon = 'trash';
                            $attributes = [
                                'type' => 'submit',
                                'class' => 'btn-sm',
                                'onclick' => "return confirm('Ви впевнені, що хочете видалити файл " . htmlspecialchars($selectedFile, ENT_QUOTES) . "? Цю дію неможливо скасувати.')"
                            ];
                            unset($url);
                            include __DIR__ . '/../components/button.php';
                            echo ob_get_clean();
                            ?>
                        </form>
                    </div>
                <?php endif; ?>
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

<style>
.logs-view-content {
    min-height: 400px;
}

.logs-content-empty {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.log-content-wrapper {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.log-content-header {
    padding: 12px 16px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.log-content pre {
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    line-height: 1.5;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.content-section-body:has(.logs-empty-state) {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    background: #f8f9fa;
    padding: 60px 24px !important;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.content-section-body:has(.logs-empty-state .empty-state) {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    background: #f8f9fa;
    padding: 60px 24px !important;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>


