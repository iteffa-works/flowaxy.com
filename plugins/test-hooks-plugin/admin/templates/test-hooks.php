<?php
/**
 * Шаблон страницы Test Hooks Plugin
 */
?>

<!-- Уведомления -->
<?php
if (!empty($message)) {
    include __DIR__ . '/../../../engine/skins/components/alert.php';
    $type = $messageType ?? 'info';
    $dismissible = true;
}
?>

<?php
// Формируем содержимое секции
ob_start();
?>

<div class="test-hooks-page">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> О системе хуков</h5>
                </div>
                <div class="card-body">
                    <p>Этот плагин демонстрирует работу системы хуков и событий Flowaxy CMS.</p>
                    
                    <h6>Типы хуков:</h6>
                    <ul>
                        <li><strong>Фильтры (Filters)</strong> - модифицируют данные и возвращают результат</li>
                        <li><strong>События (Actions)</strong> - выполняют действия без возврата данных</li>
                    </ul>
                    
                    <h6>Возможности:</h6>
                    <ul>
                        <li>Приоритизация хуков</li>
                        <li>Условное выполнение</li>
                        <li>Удаление и модификация хуков</li>
                        <li>Статистика вызовов</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Все зарегистрированные хуки</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($allHooks)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Имя хука</th>
                                        <th>Тип</th>
                                        <th>Обработчиков</th>
                                        <th>Вызовов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allHooks as $hookName => $hooks): ?>
                                        <?php
                                        $hookType = 'filter';
                                        if (!empty($hooks)) {
                                            $firstHook = $hooks[0];
                                            $hookType = $firstHook['type'] ?? 'filter';
                                        }
                                        $callCount = $hookCalls[$hookName] ?? 0;
                                        ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($hookName) ?></code></td>
                                            <td>
                                                <span class="badge bg-<?= $hookType === 'action' ? 'success' : 'info' ?>">
                                                    <?= $hookType === 'action' ? 'Action' : 'Filter' ?>
                                                </span>
                                            </td>
                                            <td><span class="badge bg-primary"><?= count($hooks) ?></span></td>
                                            <td><span class="badge bg-secondary"><?= $callCount ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Хуки не зарегистрированы.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Статистика</h5>
                </div>
                <div class="card-body">
                    <p><strong>Всего хуков:</strong> <?= $totalHooks ?></p>
                    
                    <?php if (!empty($hookCalls)): ?>
                        <h6>Вызовы хуков:</h6>
                        <ul class="list-unstyled">
                            <?php foreach ($hookCalls as $hookName => $count): ?>
                                <li>
                                    <code><?= htmlspecialchars($hookName) ?></code>: 
                                    <strong><?= $count ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Хуки еще не вызывались.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-code"></i> Примеры использования</h5>
                </div>
                <div class="card-body">
                    <h6>Фильтр:</h6>
                    <pre class="bg-light p-2 rounded"><code>addFilter('hook_name', function($data) {
    return $data . ' modified';
}, 10);</code></pre>
                    
                    <h6>Событие:</h6>
                    <pre class="bg-light p-2 rounded"><code>addAction('hook_name', function($arg) {
    // Выполнить действие
}, 10);</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$sectionContent = ob_get_clean();

// Используем компонент секции контента
$title = 'Test Hooks';
$icon = 'code';
$content = $sectionContent;
$classes = ['test-hooks-page'];
include __DIR__ . '/../../../engine/skins/components/content-section.php';
?>

<style>
.test-hooks-page {
    background: transparent;
}

.test-hooks-page .card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.test-hooks-page pre {
    font-size: 0.85em;
    margin: 0;
}

.test-hooks-page code {
    font-size: 0.9em;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>

