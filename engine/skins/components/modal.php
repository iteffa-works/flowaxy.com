<?php
/**
 * Компонент модального окна
 * 
 * @param string $id ID модального окна (обязательно)
 * @param string $title Заголовок модального окна
 * @param string $content Содержимое модального окна (HTML)
 * @param string $footer Футер модального окна (HTML кнопок)
 * @param string $size Размер модального окна (sm, lg, xl, или пусто для обычного)
 * @param bool $centered Вертикальное центрирование
 */
if (!isset($id) || empty($id)) {
    return; // ID обязателен
}
if (!isset($title)) {
    $title = '';
}
if (!isset($content)) {
    $content = '';
}
if (!isset($footer)) {
    $footer = '';
}
if (!isset($size)) {
    $size = '';
}
if (!isset($centered)) {
    $centered = false;
}

$dialogClass = 'modal-dialog';
if (!empty($size)) {
    $dialogClass .= ' modal-' . $size;
}
if ($centered) {
    $dialogClass .= ' modal-dialog-centered';
}
?>
<div class="modal fade" id="<?= htmlspecialchars($id) ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($id) ?>Label" aria-hidden="true">
    <div class="<?= $dialogClass ?>">
        <div class="modal-content">
            <?php if (!empty($title)): ?>
            <div class="modal-header">
                <h5 class="modal-title" id="<?= htmlspecialchars($id) ?>Label">
                    <?= htmlspecialchars($title) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($content)): ?>
            <div class="modal-body">
                <?= $content ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($footer)): ?>
            <div class="modal-footer">
                <?= $footer ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

