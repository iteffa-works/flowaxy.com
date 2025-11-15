<?php
$tests = $tabData['tests'] ?? [];
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-book"></i> Теоретическое тестирование
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($tests as $test): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">
                                <?= htmlspecialchars($test['name']) ?>
                                <span class="badge bg-<?= $test['status'] === 'pending' ? 'warning' : 'success' ?> ms-2">
                                    <?= $test['status'] === 'pending' ? 'Ожидает' : 'Пройден' ?>
                                </span>
                            </h6>
                            <p class="card-text"><?= htmlspecialchars($test['description']) ?></p>
                            <button class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i> Запустить тест
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

