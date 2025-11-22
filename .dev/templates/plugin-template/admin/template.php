<?php
/**
 * Шаблон адмін-сторінки плагіна
 */
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Налаштування плагіна</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= SecurityHelper::csrfField() ?>
            <input type="hidden" name="save_settings" value="1">
            
            <div class="mb-3">
                <label for="setting1" class="form-label">Налаштування 1</label>
                <input type="text" class="form-control" id="setting1" name="settings[setting1]" 
                       value="<?= htmlspecialchars($settings['setting1'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Зберегти</button>
        </form>
    </div>
</div>

