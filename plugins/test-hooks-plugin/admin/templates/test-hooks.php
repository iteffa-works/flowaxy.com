<?php
/**
 * Шаблон страницы Test Hooks Plugin
 */

// Определяем активную вкладку
$activeTab = $activeTab ?? '';
$currentUrl = '/admin/test-hooks-plugin';
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <?php
    $type = $messageType ?? 'info';
    $dismissible = true;
    include __DIR__ . '/../../../../engine/skins/components/alert.php';
    ?>
<?php endif; ?>

<!-- Навигация по вкладкам -->
<div class="test-hooks-tabs mb-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === '' ? 'active' : '' ?>" href="<?= $currentUrl ?>">
                <i class="fas fa-home"></i> Главная
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'filters' ? 'active' : '' ?>" href="<?= $currentUrl ?>?tab=filters">
                <i class="fas fa-filter"></i> Фильтры
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'actions' ? 'active' : '' ?>" href="<?= $currentUrl ?>?tab=actions">
                <i class="fas fa-bolt"></i> События
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'stats' ? 'active' : '' ?>" href="<?= $currentUrl ?>?tab=stats">
                <i class="fas fa-chart-bar"></i> Статистика
            </a>
        </li>
    </ul>
</div>

<div class="test-hooks-page">
    <div class="row">
        <div class="col-md-12">
            <!-- Контент вкладки -->
            <?= $tabContent ?? '' ?>
        </div>
        
        <?php if ($activeTab === ''): ?>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Статистика</h5>
                </div>
                <div class="card-body">
                    <p><strong>Всего хуков:</strong> <?= $totalHooks ?? 0 ?></p>
                    
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
        <?php endif; ?>
    </div>
</div>

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

.test-hooks-tabs .nav-tabs {
    border-bottom: 2px solid #dee2e6;
}

.test-hooks-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 10px 20px;
}

.test-hooks-tabs .nav-link:hover {
    border-bottom-color: #dee2e6;
    color: #495057;
}

.test-hooks-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    font-weight: 500;
}

.test-hooks-tabs .nav-link i {
    margin-right: 5px;
}
</style>

