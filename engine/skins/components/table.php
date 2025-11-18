<?php
/**
 * Компонент таблицы
 * 
 * @param array $headers Заголовки таблицы (массив строк или ['text' => '...', 'class' => '...'])
 * @param array $rows Строки таблицы (массив массивов)
 * @param bool $striped Чередование строк
 * @param bool $hover Эффект при наведении
 * @param bool $bordered Рамки
 * @param array $classes Дополнительные CSS классы
 */
if (!isset($headers)) {
    $headers = [];
}
if (!isset($rows)) {
    $rows = [];
}
if (!isset($striped)) {
    $striped = true;
}
if (!isset($hover)) {
    $hover = true;
}
if (!isset($bordered)) {
    $bordered = false;
}
if (!isset($classes)) {
    $classes = [];
}

$tableClasses = ['table'];
if ($striped) {
    $tableClasses[] = 'table-striped';
}
if ($hover) {
    $tableClasses[] = 'table-hover';
}
if ($bordered) {
    $tableClasses[] = 'table-bordered';
}
if (!empty($classes)) {
    $tableClasses = array_merge($tableClasses, $classes);
}
$tableClass = implode(' ', array_map('htmlspecialchars', $tableClasses));
?>
<table class="<?= $tableClass ?>">
    <?php if (!empty($headers)): ?>
    <thead>
        <tr>
            <?php foreach ($headers as $header): ?>
                <?php
                if (is_array($header)) {
                    $headerText = $header['text'] ?? '';
                    $headerClass = isset($header['class']) ? ' class="' . htmlspecialchars($header['class']) . '"' : '';
                } else {
                    $headerText = $header;
                    $headerClass = '';
                }
                ?>
                <th<?= $headerClass ?>><?= htmlspecialchars($headerText) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <?php endif; ?>
    
    <?php if (!empty($rows)): ?>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <?php
                    if (is_array($cell)) {
                        $cellContent = $cell['content'] ?? '';
                        $cellClass = isset($cell['class']) ? ' class="' . htmlspecialchars($cell['class']) . '"' : '';
                    } else {
                        $cellContent = $cell;
                        $cellClass = '';
                    }
                    ?>
                    <td<?= $cellClass ?>><?= $cellContent ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <?php endif; ?>
</table>

