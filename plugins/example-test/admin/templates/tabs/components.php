<?php
$tabData = $componentsTabData ?? $tabData ?? [];
$components = $tabData['components'] ?? [];
$total = $tabData['total'] ?? 0;
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-layer-group"></i> Компоненты системы
            <span class="badge bg-primary ms-2"><?= $total ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($components)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Компоненты не найдены
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($components as $component): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-cog"></i> <?= htmlspecialchars($component['name']) ?>
                                    <span class="badge bg-<?= $component['status'] === 'active' ? 'success' : 'secondary' ?> ms-2">
                                        <?= $component['status'] === 'active' ? 'Активен' : 'Неактивен' ?>
                                    </span>
                                </h6>
                                <p class="card-text text-muted"><?= htmlspecialchars($component['description']) ?></p>
                                <p class="card-text">
                                    <small class="text-muted">Тип: <?= htmlspecialchars($component['type']) ?></small>
                                </p>
                                <button class="btn btn-sm btn-primary" 
                                        onclick="testComponent('<?= htmlspecialchars($component['name']) ?>')">
                                    <i class="fas fa-vial"></i> Тест
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function testComponent(componentName) {
    fetch('<?= adminUrl('example-test') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=test_component&component=' + encodeURIComponent(componentName) + '&csrf_token=<?= generateCSRFToken() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Компонент протестирован успешно!\n\n' + JSON.stringify(data.component, null, 2));
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при тестировании компонента');
    });
}
</script>

