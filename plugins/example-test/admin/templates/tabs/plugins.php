<?php
$plugins = $tabData['plugins'] ?? [];
$total = $tabData['total'] ?? 0;
$active = $tabData['active'] ?? 0;
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-puzzle-piece"></i> Плагины
            <span class="badge bg-primary ms-2">Всего: <?= $total ?></span>
            <span class="badge bg-success ms-2">Активных: <?= $active ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($plugins)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Плагины не найдены
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
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plugins as $plugin): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($plugin['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($plugin['slug']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($plugin['description']) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($plugin['version']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($plugin['author']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $plugin['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $plugin['is_active'] ? 'Активен' : 'Неактивен' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="testPlugin('<?= htmlspecialchars($plugin['slug']) ?>')">
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
function testPlugin(pluginSlug) {
    fetch('<?= adminUrl('example-test') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=test_plugin&plugin=' + encodeURIComponent(pluginSlug) + '&csrf_token=<?= generateCSRFToken() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Плагин протестирован успешно!\n\n' + JSON.stringify(data.plugin, null, 2));
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при тестировании плагина');
    });
}
</script>

