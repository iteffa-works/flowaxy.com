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

<style>
.empty-state {
    text-align: center;
    padding: 100px 30px;
    max-width: 600px;
    margin: 0 auto;
}


.empty-state-icon {
    width: auto;
    height: auto;
    margin: 0 auto 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon i {
    font-size: 4rem;
    color: #adb5bd;
}

.empty-state-title {
    color: #212529;
    font-weight: 700;
    margin-bottom: 16px;
    font-size: 1.75rem;
    letter-spacing: -0.02em;
}

.empty-state-message {
    color: #6c757d;
    font-size: 1rem;
    line-height: 1.7;
    margin-bottom: 0;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
}

.empty-state-actions {
    margin-top: 32px;
}
</style>

