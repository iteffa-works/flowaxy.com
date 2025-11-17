<?php
/**
 * Шаблон главной страницы админки
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Статистические карточки -->
<div class="row mb-4">
    <div class="col-xl-6 col-md-6 mb-3">
        <div class="card border-left-primary h-100 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Плагіни
                        </div>
                        <div class="text-xs text-muted">Активних плагінів</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-puzzle-piece fa-2x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 col-md-6 mb-3">
        <div class="card border-left-warning h-100 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Медіа
                        </div>
                        <div class="text-xs text-muted">Файлів у бібліотеці</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-images fa-2x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
</style>
