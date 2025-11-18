<?php
/**
 * Компонент модального окна для загрузки файлов (плагинов/тем)
 * 
 * @param string $id ID модального окна
 * @param string $title Заголовок модального окна
 * @param string $fileInputName Имя поля файла (plugin_file, theme_file)
 * @param string $action Значение действия для формы (upload_plugin, upload_theme)
 * @param string $accept Тип файлов (по умолчанию .zip)
 * @param string $helpText Подсказка для поля файла
 * @param int $maxSize Максимальный размер файла в MB
 */
if (!isset($id) || empty($id)) {
    return;
}
if (!isset($title)) {
    $title = 'Завантажити файл';
}
if (!isset($fileInputName)) {
    $fileInputName = 'file';
}
if (!isset($action)) {
    $action = 'upload';
}
if (!isset($accept)) {
    $accept = '.zip';
}
if (!isset($helpText)) {
    $helpText = 'Виберіть файл для завантаження';
}
if (!isset($maxSize)) {
    $maxSize = 50;
}

// Генерируем уникальные ID для элементов
$baseName = str_replace(['_file', 'plugin_', 'theme_'], '', $fileInputName);
if (empty($baseName)) {
    $baseName = 'file';
}
$fileInputId = $baseName . 'File';
$formId = 'upload' . ucfirst($baseName) . 'Form';
$uploadBtnId = 'upload' . ucfirst($baseName) . 'Btn';
$progressId = 'uploadProgress';
$resultId = 'uploadResult';

// Для обратной совместимости с существующим JS
if ($fileInputName === 'plugin_file') {
    $fileInputId = 'pluginFile';
    $formId = 'uploadPluginForm';
    $uploadBtnId = 'uploadPluginBtn';
} elseif ($fileInputName === 'theme_file') {
    $fileInputId = 'themeFile';
    $formId = 'uploadThemeForm';
    $uploadBtnId = 'uploadThemeBtn';
}
?>
<div class="modal fade" id="<?= htmlspecialchars($id) ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($id) ?>Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= htmlspecialchars($id) ?>Label">
                    <i class="fas fa-upload me-2"></i><?= htmlspecialchars($title) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити" onclick="closeUploadModal('<?= htmlspecialchars($id) ?>')"></button>
            </div>
            <form id="<?= htmlspecialchars($formId) ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                    <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
                    
                    <div class="mb-3">
                        <label for="<?= htmlspecialchars($fileInputId) ?>" class="form-label"><?= htmlspecialchars($helpText) ?></label>
                        <input type="file" class="form-control" id="<?= htmlspecialchars($fileInputId) ?>" name="<?= htmlspecialchars($fileInputName) ?>" accept="<?= htmlspecialchars($accept) ?>" required>
                        <div class="form-text">
                            Максимальний розмір: <?= $maxSize ?> MB
                        </div>
                    </div>
                    
                    <div id="<?= htmlspecialchars($progressId) ?>" class="progress d-none mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div id="<?= htmlspecialchars($resultId) ?>" class="alert d-none"></div>
                </div>
                <div class="modal-footer">
                    <?php
                    // Кнопка отмены
                    ob_start();
                    $text = 'Скасувати';
                    $type = 'secondary';
                    $icon = '';
                    $attributes = ['data-bs-dismiss' => 'modal', 'type' => 'button', 'onclick' => "closeUploadModal('" . htmlspecialchars($id) . "')"];
                    unset($url);
                    include __DIR__ . '/button.php';
                    $cancelBtn = ob_get_clean();
                    
                    // Кнопка загрузки
                    ob_start();
                    $text = 'Завантажити';
                    $type = 'primary';
                    $icon = 'upload';
                    $attributes = ['type' => 'submit', 'id' => $uploadBtnId];
                    unset($url);
                    include __DIR__ . '/button.php';
                    $uploadBtn = ob_get_clean();
                    ?>
                    <?= $cancelBtn ?>
                    <?= $uploadBtn ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function closeUploadModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        return;
    }
    
    // Используем Bootstrap Modal API для закрытия
    const bsModal = bootstrap.Modal.getInstance(modalElement);
    if (bsModal) {
        bsModal.hide();
    } else {
        // Если modal instance не существует, создаем новый и закрываем
        const newModal = new bootstrap.Modal(modalElement);
        newModal.hide();
    }
    
    // Также пробуем через ModalHandler если доступен
    if (window.ModalHandler && typeof window.ModalHandler.hide === 'function') {
        window.ModalHandler.hide(modalId);
    }
    
    // Фолбэк: прячем модальное окно вручную
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    
    // Убираем backdrop
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
    
    // Убираем класс modal-open с body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// Добавляем обработчик на все кнопки закрытия
document.addEventListener('DOMContentLoaded', function() {
    const modalId = '<?= htmlspecialchars($id) ?>';
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        return;
    }
    
    // Обработка закрытия через ESC
    modalElement.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUploadModal(modalId);
        }
    });
    
    // Обработка клика на backdrop
    modalElement.addEventListener('click', function(e) {
        if (e.target === modalElement) {
            closeUploadModal(modalId);
        }
    });
});
</script>
