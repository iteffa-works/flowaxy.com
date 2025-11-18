<?php
/**
 * Компонент пустого состояния (empty state)
 * 
 * @param string $icon Иконка Font Awesome (без fa-, например: 'puzzle-piece')
 * @param string $title Заголовок
 * @param string $message Сообщение/описание
 * @param string $actions HTML кнопок/действий (необязательно)
 * @param array $classes Дополнительные CSS классы
 */
if (!isset($icon)) {
    $icon = 'folder-open';
}
if (!isset($title)) {
    $title = 'Немає елементів';
}
if (!isset($message)) {
    $message = '';
}
if (!isset($actions)) {
    $actions = '';
}
if (!isset($classes)) {
    $classes = [];
}

$containerClasses = ['empty-state'];
if (!empty($classes)) {
    $containerClasses = array_merge($containerClasses, $classes);
}
$containerClass = implode(' ', array_map('htmlspecialchars', $containerClasses));
?>
<div class="<?= $containerClass ?>">
    <?php if (!empty($icon)): ?>
    <div class="empty-state-icon">
        <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($title)): ?>
    <h4 class="empty-state-title"><?= htmlspecialchars($title) ?></h4>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
    <p class="empty-state-message text-muted"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($actions)): ?>
    <div class="empty-state-actions d-flex gap-2 justify-content-center">
        <?= $actions ?>
    </div>
    <?php endif; ?>
</div>

