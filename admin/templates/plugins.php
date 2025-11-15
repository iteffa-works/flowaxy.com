<?php
/**
 * Шаблон страницы управления плагинами
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Всего плагинов
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['total'] ?? 0 ?></div>
                        <div class="text-xs text-muted">В системе</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-puzzle-piece fa-2x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Активные
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['active'] ?? 0 ?></div>
                        <div class="text-xs text-muted">Работающие</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Неактивные
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['inactive'] ?? 0 ?></div>
                        <div class="text-xs text-muted">Отключенные</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-pause-circle fa-2x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Встановлені
                        </div>
                        <div class="h4 mb-0 font-weight-bold"><?= $stats['installed'] ?? 0 ?></div>
                        <div class="text-xs text-muted">Всього</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-download fa-2x text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Фильтры -->
<div class="content-section mb-4">
    <div class="content-section-body">
        <div class="row g-3">
            <div class="col-md-3">
                <select class="form-select" id="statusFilter" onchange="filterPlugins()">
                    <option value="all">Все плагины</option>
                    <option value="active">Активные</option>
                    <option value="inactive">Неактивные</option>
                    <option value="available">Доступные для установки</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" id="searchFilter" 
                       placeholder="Поиск плагинов..." onkeyup="filterPlugins()">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-times me-1"></i>Сброс
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Все плагины -->
<?php if (!empty($installedPlugins)): ?>
<div class="content-section mb-4">
    <div class="content-section-header">
        <span><i class="fas fa-puzzle-piece me-2"></i>Плагіни</span>
    </div>
    <div class="content-section-body">
        <div class="row">
            <?php foreach ($installedPlugins as $plugin): ?>
                <div class="col-lg-6 mb-3 plugin-item" data-status="<?= $plugin['is_active'] ? 'active' : ($plugin['is_installed'] ? 'inactive' : 'available') ?>" data-name="<?= strtolower($plugin['name'] ?? '') ?>">
                    <div class="card h-100 <?= $plugin['is_active'] ? 'border-success' : ($plugin['is_installed'] ? 'border-secondary' : 'border-info') ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <?= htmlspecialchars($plugin['name'] ?? 'Неизвестный плагин') ?>
                                    <?php if ($plugin['is_active']): ?>
                                        <span class="badge bg-success ms-2">Активний</span>
                                    <?php elseif ($plugin['is_installed']): ?>
                                        <span class="badge bg-secondary ms-2">Встановлений</span>
                                    <?php else: ?>
                                        <span class="badge bg-info ms-2">Доступний</span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted">v<?= htmlspecialchars($plugin['version'] ?? '1.0.0') ?></small>
                            </div>
                            
                            <p class="card-text text-muted small mb-3">
                                <?= htmlspecialchars($plugin['description'] ?? 'Описание отсутствует') ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$plugin['is_installed']): ?>
                                        <!-- Не установлен - показываем кнопку установки -->
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="installPlugin('<?= htmlspecialchars($plugin['slug'] ?? '') ?>')"
                                                style="padding: 0.5rem 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center;">
                                            <i class="fas fa-download me-2"></i><span>Встановити</span>
                                        </button>
                                    <?php elseif ($plugin['is_active']): ?>
                                        <!-- Активен - показываем деактивацию -->
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="togglePlugin('<?= htmlspecialchars($plugin['slug'] ?? '') ?>', false)"
                                                style="padding: 0.5rem 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center;">
                                            <i class="fas fa-pause me-2"></i><span>Деактивувати</span>
                                        </button>
                                    <?php else: ?>
                                        <!-- Установлен но не активен - показываем активацию -->
                                        <button class="btn btn-success btn-sm" 
                                                onclick="togglePlugin('<?= htmlspecialchars($plugin['slug'] ?? '') ?>', true)"
                                                style="padding: 0.5rem 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center;">
                                            <i class="fas fa-play me-2"></i><span>Активувати</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($plugin['is_installed']): ?>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="uninstallPlugin('<?= htmlspecialchars($plugin['slug'] ?? '') ?>')"
                                            title="Видалити плагін">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
        <p class="text-muted">Плагіни відсутні</p>
    </div>
<?php endif; ?>

<script>
function togglePlugin(slug, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="plugin_slug" value="${slug}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function installPlugin(slug) {
    if (confirm('Установить этот плагин?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="install">
            <input type="hidden" name="plugin_slug" value="${slug}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function uninstallPlugin(slug) {
    if (confirm('Вы уверены, что хотите удалить этот плагин? Все данные плагина будут потеряны.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="uninstall">
            <input type="hidden" name="plugin_slug" value="${slug}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterPlugins() {
    const statusFilter = document.getElementById('statusFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    const plugins = document.querySelectorAll('.plugin-item');
    
    plugins.forEach(plugin => {
        const status = plugin.dataset.status;
        const name = plugin.dataset.name;
        
        let showStatus = statusFilter === 'all' || status === statusFilter;
        let showSearch = searchFilter === '' || name.includes(searchFilter);
        
        plugin.style.display = showStatus && showSearch ? 'block' : 'none';
    });
}

function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('searchFilter').value = '';
    filterPlugins();
}
</script>
