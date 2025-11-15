<?php
$apiMethods = $tabData['api_methods'] ?? [];
$total = $tabData['total'] ?? 0;
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-code"></i> API методы модулей
            <span class="badge bg-primary ms-2"><?= $total ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($apiMethods)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> API методы не найдены
            </div>
        <?php else: ?>
            <?php foreach ($apiMethods as $moduleName => $methods): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-cube"></i> <?= htmlspecialchars($moduleName) ?>
                            <span class="badge bg-success ms-2"><?= count($methods) ?> методов</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($methods as $methodName => $methodDescription): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <code><?= htmlspecialchars($methodName) ?></code>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($methodDescription) ?></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="testApiMethod('<?= htmlspecialchars($moduleName) ?>', '<?= htmlspecialchars($methodName) ?>')">
                                        <i class="fas fa-vial"></i> Тест
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function testApiMethod(moduleName, methodName) {
    alert('Тестирование API метода:\n\nМодуль: ' + moduleName + '\nМетод: ' + methodName);
    // Здесь можно добавить реальное тестирование API метода
}
</script>

