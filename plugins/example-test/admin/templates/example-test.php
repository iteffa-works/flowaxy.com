<?php
/**
 * Шаблон страницы Example Test
 */

$currentTab = $data['currentTab'] ?? 'modules';
$tabs = $data['tabs'] ?? [];
$tabData = $data['data'] ?? [];
?>

<div class="row">
    <div class="col-12">
        <!-- Вкладки -->
        <ul class="nav nav-tabs mb-4" id="testTabs" role="tablist">
            <?php foreach ($tabs as $tabKey => $tab): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $currentTab === $tabKey ? 'active' : '' ?>" 
                            id="<?= $tabKey ?>-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#<?= $tabKey ?>" 
                            type="button" 
                            role="tab"
                            onclick="loadTab('<?= $tabKey ?>')">
                        <i class="<?= $tab['icon'] ?? '' ?>"></i>
                        <?= htmlspecialchars($tab['title']) ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <!-- Содержимое вкладок -->
        <div class="tab-content" id="testTabContent">
            <!-- Вкладка: Системные модули -->
            <div class="tab-pane fade <?= $currentTab === 'modules' ? 'show active' : '' ?>" 
                 id="modules" 
                 role="tabpanel">
                <?php include __DIR__ . '/tabs/modules.php'; ?>
            </div>
            
            <!-- Вкладка: Плагины -->
            <div class="tab-pane fade <?= $currentTab === 'plugins' ? 'show active' : '' ?>" 
                 id="plugins" 
                 role="tabpanel">
                <?php include __DIR__ . '/tabs/plugins.php'; ?>
            </div>
            
            <!-- Вкладка: Компоненты -->
            <div class="tab-pane fade <?= $currentTab === 'components' ? 'show active' : '' ?>" 
                 id="components" 
                 role="tabpanel">
                <?php include __DIR__ . '/tabs/components.php'; ?>
            </div>
            
            <!-- Вкладка: Визуальное тестирование -->
            <div class="tab-pane fade <?= $currentTab === 'visual' ? 'show active' : '' ?>" 
                 id="visual" 
                 role="tabpanel">
                <?php include __DIR__ . '/tabs/visual.php'; ?>
            </div>
            
            <!-- Вкладка: Теоретическое тестирование -->
            <div class="tab-pane fade <?= $currentTab === 'theoretical' ? 'show active' : '' ?>" 
                 id="theoretical" 
                 role="tabpanel">
                <?php include __DIR__ . '/tabs/theoretical.php'; ?>
            </div>
            
            <!-- Вкладка: API тестирование -->
            <div class="tab-pane fade <?= $currentTab === 'api' ? 'show active' : '' ?>" 
                 id="api" 
                 role="tabpanel">
                <?php include __DIR__ . '/tabs/api.php'; ?>
            </div>
        </div>
    </div>
</div>

<script>
function loadTab(tab) {
    window.location.href = '<?= adminUrl('example-test') ?>?tab=' + tab;
}
</script>

