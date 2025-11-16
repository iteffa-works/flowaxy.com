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
                                    <?php if ($plugin['is_active']): ?>
                                        <!-- Плагин активен - кнопка неактивна -->
                                        <button class="btn btn-outline-danger btn-sm" 
                                                disabled
                                                title="Спочатку деактивуйте плагін перед видаленням">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Плагин неактивен - можно удалить -->
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="uninstallPlugin('<?= htmlspecialchars($plugin['slug'] ?? '') ?>')"
                                                title="Видалити плагін">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
    <div class="plugins-empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-puzzle-piece"></i>
        </div>
        <h4>Плагіни відсутні</h4>
        <p class="text-muted">Встановіть плагін за замовчуванням або завантажте новий плагін з маркетплейсу.</p>
        <div class="d-flex gap-2 justify-content-center">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadPluginModal">
                <i class="fas fa-upload me-1"></i>Завантажити плагін
            </button>
            <a href="https://flowaxy.com/marketplace/plugins" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-store me-1"></i>Перейти до маркетплейсу
            </a>
        </div>
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

<style>
.plugins-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.plugins-empty-state .empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2.5rem;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
}

.plugins-empty-state h4 {
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 12px;
}

.plugins-empty-state .text-muted {
    color: #718096;
    margin-bottom: 24px;
    font-size: 0.95rem;
    line-height: 1.6;
}

.plugins-empty-state .btn {
    padding: 12px 24px;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.plugins-empty-state .btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.plugins-empty-state .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
}

.plugins-empty-state .btn-outline-primary {
    border: 2px solid #0d6efd;
    color: #0d6efd;
    background: transparent;
}

.plugins-empty-state .btn-outline-primary:hover {
    background: #0d6efd;
    color: #fff;
    transform: translateY(-2px);
}
</style>

<!-- Модальне вікно завантаження плагіна -->
<div class="modal fade" id="uploadPluginModal" tabindex="-1" aria-labelledby="uploadPluginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPluginModalLabel">
                    <i class="fas fa-upload me-2"></i>Завантажити плагін
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadPluginForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="upload_plugin">
                    
                    <div class="mb-3">
                        <label for="pluginFile" class="form-label">Виберіть ZIP архів з плагіном</label>
                        <input type="file" class="form-control" id="pluginFile" name="plugin_file" accept=".zip" required>
                        <div class="form-text">
                            Максимальний розмір: 50 MB. Архів повинен містити файл plugin.json
                        </div>
                    </div>
                    
                    <div id="uploadProgress" class="progress d-none mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div id="uploadResult" class="alert d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary" id="uploadPluginBtn">
                        <i class="fas fa-upload me-1"></i>Завантажити
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadPluginForm');
    const uploadBtn = document.getElementById('uploadPluginBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const uploadResult = document.getElementById('uploadResult');
    const progressBar = uploadProgress.querySelector('.progress-bar');
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const fileInput = document.getElementById('pluginFile');
            
            if (!fileInput.files.length) {
                showUploadResult('Будь ласка, виберіть файл', 'danger');
                return;
            }
            
            // Показуємо прогрес
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Завантаження...';
            uploadProgress.classList.remove('d-none');
            uploadResult.classList.add('d-none');
            progressBar.style.width = '0%';
            
            // Симулюємо прогрес
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 10;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                }
            }, 200);
            
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                setTimeout(() => {
                    if (data.success) {
                        showUploadResult(data.message || 'Плагін успішно завантажено', 'success');
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
                        
                        // Перезавантажуємо сторінку через 2 секунди
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showUploadResult(data.error || 'Помилка завантаження', 'danger');
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
                        progressBar.style.width = '0%';
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                showUploadResult('Помилка підключення до сервера: ' + error.message, 'danger');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
                progressBar.style.width = '0%';
            });
        });
    }
    
    function showUploadResult(message, type) {
        uploadResult.textContent = message;
        uploadResult.className = 'alert alert-' + type;
        uploadResult.classList.remove('d-none');
    }
    
    // Очищаємо форму при закритті модального вікна
    const modal = document.getElementById('uploadPluginModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            uploadForm.reset();
            uploadResult.classList.add('d-none');
            uploadProgress.classList.add('d-none');
            progressBar.style.width = '0%';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
        });
    }
});
</script>
