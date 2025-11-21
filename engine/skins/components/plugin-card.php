<?php
/**
 * Компонент карточки плагина
 * 
 * @param array $plugin Данные плагина
 * @param string $colClass CSS класс колонки (по умолчанию col-lg-6)
 */
if (!isset($plugin) || !is_array($plugin)) {
    return;
}
if (!isset($colClass)) {
    $colClass = 'col-lg-6';
}

$pluginName = htmlspecialchars($plugin['name'] ?? 'Неизвестный плагин');
$pluginSlug = htmlspecialchars($plugin['slug'] ?? '');
$pluginVersion = htmlspecialchars($plugin['version'] ?? '1.0.0');
$pluginDescription = htmlspecialchars($plugin['description'] ?? 'Описание отсутствует');
$isActive = $plugin['is_active'] ?? false;
$isInstalled = $plugin['is_installed'] ?? false;
$hasSettings = $plugin['has_settings'] ?? false;

$status = $isActive ? 'active' : ($isInstalled ? 'inactive' : 'available');
?>
<div class="<?= htmlspecialchars($colClass) ?> mb-3 plugin-item" data-status="<?= $status ?>" data-name="<?= strtolower($pluginName) ?>">
    <div class="plugin-card">
        <div class="plugin-header">
            <h6 class="plugin-name"><?= $pluginName ?></h6>
            <div class="plugin-badges">
                <?php
                if ($isActive) {
                    include __DIR__ . '/badge.php';
                    $text = 'Активний';
                    $type = 'active';
                    unset($icon);
                } elseif ($isInstalled) {
                    include __DIR__ . '/badge.php';
                    $text = 'Встановлений';
                    $type = 'installed';
                    unset($icon);
                } else {
                    include __DIR__ . '/badge.php';
                    $text = 'Доступний';
                    $type = 'available';
                    unset($icon);
                }
                ?>
                <span class="plugin-version">v<?= $pluginVersion ?></span>
            </div>
        </div>
        
        <p class="plugin-description"><?= $pluginDescription ?></p>
        
        <div class="plugin-actions">
            <?php
            // Перевірка прав доступу
            $session = sessionManager();
            $userId = (int)$session->get('admin_user_id');
            $hasInstallAccess = ($userId === 1) || (function_exists('current_user_can') && current_user_can('admin.plugins.install'));
            $hasActivateAccess = ($userId === 1) || (function_exists('current_user_can') && current_user_can('admin.plugins.activate'));
            $hasDeactivateAccess = ($userId === 1) || (function_exists('current_user_can') && current_user_can('admin.plugins.deactivate'));
            $hasDeleteAccess = ($userId === 1) || (function_exists('current_user_can') && current_user_can('admin.plugins.delete'));
            $hasSettingsAccess = ($userId === 1) || (function_exists('current_user_can') && current_user_can('admin.plugins.settings'));
            ?>
            
            <?php if (!$isInstalled): ?>
                <?php if ($hasInstallAccess): ?>
                    <?php
                    $text = 'Встановити';
                    $type = 'primary';
                    $icon = 'download';
                    $attributes = ['onclick' => "installPlugin('{$pluginSlug}')"];
                    unset($url);
                    include __DIR__ . '/button.php';
                    ?>
                <?php endif; ?>
            <?php elseif ($isActive): ?>
                <?php if ($hasDeactivateAccess): ?>
                    <?php
                    $text = 'Деактивувати';
                    $type = 'warning';
                    $icon = 'pause';
                    $attributes = ['onclick' => "togglePlugin('{$pluginSlug}', false)"];
                    unset($url);
                    include __DIR__ . '/button.php';
                    ?>
                <?php endif; ?>
                
                <?php if ($hasSettings && $hasSettingsAccess): ?>
                    <?php
                    $text = '';
                    $type = 'secondary';
                    $icon = 'cog';
                    $url = UrlHelper::admin($pluginSlug . '-settings');
                    $attributes = ['title' => 'Налаштування плагіна'];
                    include __DIR__ . '/button.php';
                    ?>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($hasActivateAccess): ?>
                    <?php
                    $text = 'Активувати';
                    $type = 'success';
                    $icon = 'play';
                    $attributes = ['onclick' => "togglePlugin('{$pluginSlug}', true)"];
                    unset($url);
                    include __DIR__ . '/button.php';
                    ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($isInstalled && $hasDeleteAccess): ?>
                <?php if ($isActive): ?>
                    <?php
                    $text = '';
                    $type = 'danger';
                    $icon = 'trash';
                    $attributes = ['disabled' => true, 'title' => 'Спочатку деактивуйте плагін перед видаленням'];
                    unset($url);
                    include __DIR__ . '/button.php';
                    ?>
                <?php else: ?>
                    <?php
                    $text = '';
                    $type = 'danger';
                    $icon = 'trash';
                    $attributes = ['onclick' => "uninstallPlugin('{$pluginSlug}')", 'title' => 'Видалити плагін'];
                    unset($url);
                    include __DIR__ . '/button.php';
                    ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

