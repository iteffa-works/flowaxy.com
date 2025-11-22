<?php
/**
 * Шаблон сторінки "В розробці"
 */
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                </div>
                <h3 class="mb-3">Сторінка в розробці</h3>
                <p class="text-muted mb-4">
                    Ця сторінка знаходиться в стадії розробки.<br>
                    Функціонал буде доступний найближчим часом.
                </p>
                <a href="<?= UrlHelper::admin('dashboard') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Повернутися на панель
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.development-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.development-page .card {
    border-radius: 8px;
}

.development-page .fa-tools {
    opacity: 0.5;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 0.8;
    }
}
</style>

