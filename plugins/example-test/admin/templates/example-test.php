<?php
/**
 * Шаблон страницы Example Test
 * 
 * После extract($data) в renderCustomTemplate() переменные доступны напрямую:
 * - $currentTab (из $data['currentTab'])
 * - $tabs (из $data['tabs'])
 * - $data (из $data['data'])
 * - $tabData (из $data['tabData'])
 */

// Используем переменные напрямую (они уже извлечены через extract())
$currentTab = $currentTab ?? $data['currentTab'] ?? 'modules';
$tabs = $tabs ?? $data['tabs'] ?? [];
$tabData = $tabData ?? $data['data'] ?? $data['tabData'] ?? [];

// Временная отладка - всегда показываем информацию
echo '<div class="alert alert-info mb-3">';
echo '<strong>Отладка данных:</strong><br>';
echo 'currentTab = ' . htmlspecialchars(var_export($currentTab, true)) . '<br>';
echo 'tabs count = ' . count($tabs) . '<br>';
echo 'tabData keys = ' . implode(', ', array_keys($tabData ?? [])) . '<br>';
echo 'tabData modules count = ' . (isset($tabData['modules']) ? count($tabData['modules']) : 'N/A') . '<br>';
echo 'tabData total = ' . ($tabData['total'] ?? 'N/A') . '<br>';
if (isset($data) && is_array($data)) {
    echo 'data keys = ' . implode(', ', array_keys($data)) . '<br>';
}
echo '</div>';
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
                <?php 
                // Явно передаем данные в подшаблон
                $modulesTabData = $tabData;
                include __DIR__ . '/tabs/modules.php'; 
                ?>
            </div>
            
            <!-- Вкладка: Плагины -->
            <div class="tab-pane fade <?= $currentTab === 'plugins' ? 'show active' : '' ?>" 
                 id="plugins" 
                 role="tabpanel">
                <?php 
                $pluginsTabData = $tabData;
                include __DIR__ . '/tabs/plugins.php'; 
                ?>
            </div>
            
            <!-- Вкладка: Компоненты -->
            <div class="tab-pane fade <?= $currentTab === 'components' ? 'show active' : '' ?>" 
                 id="components" 
                 role="tabpanel">
                <?php 
                $componentsTabData = $tabData;
                include __DIR__ . '/tabs/components.php'; 
                ?>
            </div>
            
            <!-- Вкладка: Визуальное тестирование -->
            <div class="tab-pane fade <?= $currentTab === 'visual' ? 'show active' : '' ?>" 
                 id="visual" 
                 role="tabpanel">
                <?php 
                $visualTabData = $tabData;
                include __DIR__ . '/tabs/visual.php'; 
                ?>
            </div>
            
            <!-- Вкладка: Теоретическое тестирование -->
            <div class="tab-pane fade <?= $currentTab === 'theoretical' ? 'show active' : '' ?>" 
                 id="theoretical" 
                 role="tabpanel">
                <?php 
                $theoreticalTabData = $tabData;
                include __DIR__ . '/tabs/theoretical.php'; 
                ?>
            </div>
            
            <!-- Вкладка: API тестирование -->
            <div class="tab-pane fade <?= $currentTab === 'api' ? 'show active' : '' ?>" 
                 id="api" 
                 role="tabpanel">
                <?php 
                $apiTabData = $tabData;
                include __DIR__ . '/tabs/api.php'; 
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function loadTab(tab) {
    window.location.href = '<?= adminUrl('example-test') ?>?tab=' + tab;
}
</script>

