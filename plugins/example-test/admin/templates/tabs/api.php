<?php
$tabData = $apiTabData ?? $tabData ?? [];
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
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto flex-grow-1">
                                        <div class="fw-bold">
                                            <code><?= htmlspecialchars($methodName) ?></code>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars(is_string($methodDescription) ? $methodDescription : json_encode($methodDescription, JSON_UNESCAPED_UNICODE)) ?></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary ms-2" 
                                            onclick="testApiMethod('<?= htmlspecialchars($moduleName) ?>', '<?= htmlspecialchars($methodName) ?>')"
                                            title="Тестировать API метод">
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
    fetch('<?= adminUrl('example-test') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=test_api_method&module=' + encodeURIComponent(moduleName) + 
              '&method=' + encodeURIComponent(methodName) + 
              '&csrf_token=<?= generateCSRFToken() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API метод протестирован успешно!\n\n' + 
                  'Модуль: ' + data.api_method.module + '\n' +
                  'Метод: ' + data.api_method.method + '\n' +
                  'Описание: ' + data.api_method.description);
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при тестировании API метода');
    });
}
</script>

