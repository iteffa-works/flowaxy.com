<?php
$modules = $tabData['modules'] ?? [];
$total = $tabData['total'] ?? 0;
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-cubes"></i> Системные модули
            <span class="badge bg-primary ms-2"><?= $total ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($modules)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Модули не найдены
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Версия</th>
                            <th>Автор</th>
                            <th>API методы</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($module['title']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($module['name']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($module['description']) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($module['version']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($module['author']) ?></td>
                                <td>
                                    <?php if (!empty($module['api_methods'])): ?>
                                        <span class="badge bg-success"><?= count($module['api_methods']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $module['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $module['status'] === 'active' ? 'Активен' : 'Неактивен' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="testModule('<?= htmlspecialchars($module['name']) ?>')">
                                        <i class="fas fa-vial"></i> Тест
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function testModule(moduleName) {
    fetch('<?= adminUrl('example-test') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=test_module&module=' + encodeURIComponent(moduleName) + '&csrf_token=<?= generateCSRFToken() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Модуль протестирован успешно!\n\n' + JSON.stringify(data.module, null, 2));
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при тестировании модуля');
    });
}
</script>

