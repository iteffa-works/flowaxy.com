<?php
/**
 * Шаблон страницы управления плагинами
 */
?>

<!-- Уведомления -->
<?php
if (!empty($message)) {
    include __DIR__ . '/../components/alert.php';
    $type = $messageType ?? 'info';
    $dismissible = true;
}
?>

<?php
// Формируем содержимое секции
ob_start();
?>
        <?php if (!empty($installedPlugins)): ?>
            <div class="plugins-list">
                <div class="row">
                    <?php foreach ($installedPlugins as $plugin): ?>
                <?php
                include __DIR__ . '/../components/plugin-card.php';
                ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
    <?php
    // Пустое состояние без кнопок
    unset($actions);
    include __DIR__ . '/../components/empty-state.php';
    $icon = 'puzzle-piece';
    $title = 'Плагіни відсутні';
    $message = 'Встановіть плагін за замовчуванням або завантажте новий плагін з маркетплейсу.';
    $classes = ['plugins-empty-state'];
    ?>
        <?php endif; ?>
<?php
$sectionContent = ob_get_clean();

// Используем компонент секции контента
$title = 'Встановлені плагіни';
$icon = 'puzzle-piece';
$content = $sectionContent;
$classes = ['plugins-page'];
include __DIR__ . '/../components/content-section.php';
?>

<script>
function togglePlugin(slug, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
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
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
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
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
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
/* Плоский строгий дизайн */
.plugins-page {
    background: transparent;
}

.content-section-header {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-bottom: none;
    padding: 16px 20px;
    font-weight: 600;
    color: #212529;
    font-size: 0.95rem;
}

.content-section-body {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-top: none;
    padding: 24px;
}

.content-section-body:has(.plugins-empty-state) {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    background: #f8f9fa;
    padding: 60px 24px !important;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.content-section-body:has(.plugins-empty-state .empty-state) {
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    background: #f8f9fa;
    padding: 60px 24px !important;
    min-height: calc(100vh - 300px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.plugins-list {
    padding: 0;
}

.plugins-list .row {
    display: flex;
    flex-wrap: wrap;
}

.plugins-list .plugin-item {
    display: flex;
    flex-direction: column;
}

.plugin-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 20px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.plugin-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.plugin-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.plugin-badges {
    display: flex;
    gap: 8px;
    align-items: center;
}

.badge {
    padding: 4px 10px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0;
    text-transform: uppercase;
}

.badge-active {
    background: #28a745;
    color: #fff;
}

.badge-installed {
    background: #6c757d;
    color: #fff;
}

.badge-available {
    background: #17a2b8;
    color: #fff;
}

.plugin-version {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.plugin-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0 0 16px 0;
    line-height: 1.5;
    flex-grow: 1;
}

.plugin-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: auto;
}

.plugin-actions .btn {
    border-radius: 0;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
    font-weight: 500;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    height: 38px;
    min-height: 38px;
    box-sizing: border-box;
}

.plugin-actions .btn i {
    display: inline-flex;
    align-items: center;
    line-height: 1;
}

.plugin-actions .btn-primary {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.plugin-actions .btn-primary:hover {
    background: #0b5ed7;
    border-color: #0b5ed7;
}

.plugin-actions .btn-success {
    background: #28a745;
    border-color: #28a745;
    color: #fff;
}

.plugin-actions .btn-success:hover {
    background: #218838;
    border-color: #218838;
}

.plugin-actions .btn-warning {
    background: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.plugin-actions .btn-warning:hover {
    background: #e0a800;
    border-color: #e0a800;
}

.plugin-actions .btn-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.plugin-actions .btn-danger:hover:not(:disabled) {
    background: #c82333;
    border-color: #c82333;
}

.plugin-actions .btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.plugin-actions .btn-secondary {
    background: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

.plugin-actions .btn-secondary:hover {
    background: #5a6268;
    border-color: #5a6268;
}

</style>

<!-- Модальне вікно завантаження плагіна через ModalHandler -->
<?php if (!empty($uploadModalHtml)): ?>
    <?= $uploadModalHtml ?>
<?php endif; ?>
