<?php
/**
 * Компонент группы формы
 * 
 * @param string $label Метка поля
 * @param string $name Имя поля
 * @param string $type Тип поля (text, email, password, textarea, select, checkbox, etc.)
 * @param mixed $value Значение поля
 * @param string $placeholder Placeholder
 * @param string $helpText Подсказка под полем
 * @param array $options Опции для select/radio/checkbox
 * @param array $attributes Дополнительные атрибуты
 * @param string $id ID поля (если не указан, генерируется из name)
 */
if (!isset($type)) {
    $type = 'text';
}
if (!isset($value)) {
    $value = '';
}
if (!isset($placeholder)) {
    $placeholder = '';
}
if (!isset($helpText)) {
    $helpText = '';
}
if (!isset($options)) {
    $options = [];
}
if (!isset($attributes)) {
    $attributes = [];
}
if (!isset($id) && isset($name)) {
    $id = $name;
}
if (!isset($id)) {
    $id = '';
}

$fieldId = !empty($id) ? htmlspecialchars($id) : 'field_' . uniqid();
$fieldName = isset($name) ? htmlspecialchars($name) : '';
$fieldLabel = isset($label) ? htmlspecialchars($label) : '';
?>
<div class="form-group mb-3">
    <?php if (!empty($fieldLabel) && $type !== 'checkbox' && $type !== 'radio'): ?>
    <label for="<?= $fieldId ?>" class="form-label fw-medium small">
        <?= $fieldLabel ?>
    </label>
    <?php endif; ?>
    
    <?php
    // Формируем базовые атрибуты
    $baseAttributes = [
        'id' => $fieldId,
        'name' => $fieldName,
        'class' => 'form-control'
    ];
    
    if (!empty($placeholder)) {
        $baseAttributes['placeholder'] = $placeholder;
    }
    
    // Объединяем с дополнительными атрибутами
    $allAttributes = array_merge($baseAttributes, $attributes);
    
    // Формируем строку атрибутов
    $attrsString = '';
    foreach ($allAttributes as $key => $val) {
        if ($val !== null && $val !== '') {
            if ($key === 'class') {
                $attrsString .= ' class="' . htmlspecialchars($val) . '"';
            } else {
                $attrsString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
        }
    }
    ?>
    
    <?php if ($type === 'textarea'): ?>
        <textarea<?= $attrsString ?>><?= htmlspecialchars($value) ?></textarea>
    <?php elseif ($type === 'select'): ?>
        <select<?= $attrsString ?>>
            <?php foreach ($options as $optionValue => $optionLabel): ?>
                <option value="<?= htmlspecialchars($optionValue) ?>" <?= ($value == $optionValue) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($optionLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php elseif ($type === 'checkbox'): ?>
        <div class="form-check form-switch">
            <input type="checkbox"<?= $attrsString ?> class="form-check-input" value="1" <?= ($value == '1' || $value === true) ? 'checked' : '' ?>>
            <?php if (!empty($fieldLabel)): ?>
            <label class="form-check-label" for="<?= $fieldId ?>">
                <?= $fieldLabel ?>
            </label>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <input type="<?= htmlspecialchars($type) ?>" value="<?= htmlspecialchars($value) ?>"<?= $attrsString ?>>
    <?php endif; ?>
    
    <?php if (!empty($helpText)): ?>
    <div class="form-text small"><?= htmlspecialchars($helpText) ?></div>
    <?php endif; ?>
</div>

