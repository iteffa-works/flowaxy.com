<?php
/**
 * Административная страница плагина Test Hooks Plugin
 * Демонстрирует работу системы хуков
 */

require_once __DIR__ . '/../../../engine/skins/includes/AdminPage.php';

class TestHooksPluginAdminPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Test Hooks - Flowaxy CMS';
        $this->templateName = 'test-hooks';
        
        $this->setPageHeader(
            'Test Hooks Plugin',
            'Демонстрация работы системы хуков и событий',
            'fas fa-code',
            ''
        );
    }
    
    /**
     * Переопределяем путь к шаблону для использования шаблонов плагина
     */
    protected function getTemplatePath() {
        return __DIR__ . '/templates/';
    }
    
    public function handle() {
        // Получаем активную вкладку из GET параметра
        $request = Request::getInstance();
        $activeTab = SecurityHelper::sanitizeInput($request->query('tab', ''));
        
        // Валидация вкладки
        $allowedTabs = ['', 'filters', 'actions', 'stats'];
        if (!in_array($activeTab, $allowedTabs)) {
            $activeTab = '';
        }
        
        // Получаем статистику хуков
        $hookManager = hookManager();
        $hookStats = $hookManager->getHookStats();
        $allHooks = $hookManager->getAllHooks();
        
        // Группируем хуки по типам
        $hooksByType = [
            'filters' => [],
            'actions' => []
        ];
        
        foreach ($allHooks as $hookName => $hooks) {
            foreach ($hooks as $hook) {
                $type = $hook['type'] ?? 'filter';
                if (!isset($hooksByType[$type . 's'])) {
                    $hooksByType[$type . 's'] = [];
                }
                if (!isset($hooksByType[$type . 's'][$hookName])) {
                    $hooksByType[$type . 's'][$hookName] = [];
                }
                $hooksByType[$type . 's'][$hookName][] = $hook;
            }
        }
        
        // Определяем контент в зависимости от вкладки
        $tabContent = $this->getTabContent($activeTab, $allHooks, $hooksByType, $hookStats);
        
        // Рендерим страницу
        $this->render([
            'activeTab' => $activeTab,
            'tabContent' => $tabContent,
            'hookStats' => $hookStats,
            'allHooks' => $allHooks,
            'hooksByType' => $hooksByType,
            'totalHooks' => $hookStats['total_hooks'] ?? 0,
            'hookCalls' => $hookStats['hook_calls'] ?? []
        ]);
    }
    
    /**
     * Получение контента для вкладки
     */
    private function getTabContent(string $tab, array $allHooks, array $hooksByType, array $hookStats): string {
        switch ($tab) {
            case 'filters':
                return $this->getFiltersContent($hooksByType['filters'] ?? []);
                
            case 'actions':
                return $this->getActionsContent($hooksByType['actions'] ?? []);
                
            case 'stats':
                return $this->getStatsContent($hookStats);
                
            default:
                return $this->getMainContent($allHooks, $hookStats);
        }
    }
    
    /**
     * Контент главной страницы
     */
    private function getMainContent(array $allHooks, array $hookStats): string {
        ob_start();
        ?>
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
                                    $callCount = ($hookStats['hook_calls'][$hookName] ?? 0);
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
        <?php
        return ob_get_clean();
    }
    
    /**
     * Контент вкладки "Фильтры"
     */
    private function getFiltersContent(array $filters): string {
        ob_start();
        ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Фильтры (Filters)</h5>
            </div>
            <div class="card-body">
                <p>Фильтры модифицируют данные и возвращают результат. Каждый фильтр получает данные, может их изменить и должен вернуть результат.</p>
                
                <h6>Пример использования:</h6>
                <pre class="bg-light p-3 rounded"><code>// Добавление фильтра
addFilter('post_title', function($title) {
    return strtoupper($title);
}, 10);

// Применение фильтра
$title = applyFilter('post_title', 'Hello World');
// Результат: "HELLO WORLD"</code></pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Зарегистрированные фильтры</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($filters)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Имя хука</th>
                                    <th>Обработчиков</th>
                                    <th>Приоритет</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filters as $hookName => $hooks): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($hookName) ?></code></td>
                                        <td><span class="badge bg-primary"><?= count($hooks) ?></span></td>
                                        <td>
                                            <?php
                                            $priorities = array_map(function($h) { return $h['priority'] ?? 10; }, $hooks);
                                            echo '<span class="badge bg-secondary">' . min($priorities) . ' - ' . max($priorities) . '</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Фильтры не зарегистрированы.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Контент вкладки "События"
     */
    private function getActionsContent(array $actions): string {
        ob_start();
        ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> События (Actions)</h5>
            </div>
            <div class="card-body">
                <p>События выполняют действия без возврата данных. Они используются для выполнения действий в определенные моменты.</p>
                
                <h6>Пример использования:</h6>
                <pre class="bg-light p-3 rounded"><code>// Добавление события
addAction('user_registered', function($userId) {
    // Отправить email приветствия
    sendWelcomeEmail($userId);
}, 10);

// Выполнение события
doAction('user_registered', 123);</code></pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Зарегистрированные события</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($actions)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Имя хука</th>
                                    <th>Обработчиков</th>
                                    <th>Приоритет</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actions as $hookName => $hooks): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($hookName) ?></code></td>
                                        <td><span class="badge bg-success"><?= count($hooks) ?></span></td>
                                        <td>
                                            <?php
                                            $priorities = array_map(function($h) { return $h['priority'] ?? 10; }, $hooks);
                                            echo '<span class="badge bg-secondary">' . min($priorities) . ' - ' . max($priorities) . '</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">События не зарегистрированы.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Контент вкладки "Статистика"
     */
    private function getStatsContent(array $hookStats): string {
        ob_start();
        $totalHooks = $hookStats['total_hooks'] ?? 0;
        $hookCalls = $hookStats['hook_calls'] ?? [];
        $totalCalls = array_sum($hookCalls);
        ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Статистика хуков</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $totalHooks ?></h3>
                                <p class="text-muted mb-0">Всего хуков</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $totalCalls ?></h3>
                                <p class="text-muted mb-0">Всего вызовов</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $totalCalls > 0 ? round($totalCalls / max($totalHooks, 1), 2) : 0 ?></h3>
                                <p class="text-muted mb-0">Среднее вызовов</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Детальная статистика вызовов</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($hookCalls)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Имя хука</th>
                                    <th>Количество вызовов</th>
                                    <th>Процент</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                arsort($hookCalls);
                                foreach ($hookCalls as $hookName => $count): 
                                    $percent = $totalCalls > 0 ? round(($count / $totalCalls) * 100, 2) : 0;
                                ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($hookName) ?></code></td>
                                        <td><span class="badge bg-primary"><?= $count ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%">
                                                    <?= $percent ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Хуки еще не вызывались.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

