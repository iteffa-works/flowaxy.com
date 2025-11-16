<?php
/**
 * Шаблон тестового меню
 */
?>

<div class="container-fluid">
    <div class="row">
        <!-- Бокове меню -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bars me-2"></i>Бокове меню</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home me-2"></i>Головна
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i>Профіль
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i>Налаштування
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i>Статистика
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i>Документи
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Основний контент -->
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title">
                        <i class="fas fa-flask text-primary me-2"></i>
                        Тестова сторінка
                    </h4>
                    <p class="card-text">
                        <?= htmlspecialchars($message ?? 'Ласкаво просимо!') ?>
                    </p>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Це простий тестовий плагін з боковим меню. 
                        Ви можете використовувати його як основу для своїх плагінів.
                    </div>
                    
                    <div class="mt-4">
                        <h5>Приклад контенту:</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Бокове меню працює
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Плагін завантажено успішно
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Маршрути працюють
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item-action:hover {
    background-color: #f8f9fa;
}
.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

