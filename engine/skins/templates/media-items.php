<?php
/**
 * Шаблон для рендеринга списку медіафайлів (для AJAX)
 */

/**
 * Функція форматування розміру файлу
 * 
 * @param int $bytes Розмір в байтах
 * @return string Відформатований розмір
 */
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max(0, (int)$bytes);
        
        if ($bytes === 0) {
            return '0 B';
        }
        
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>

<?php if (empty($files)): ?>
    <div class="media-empty-state">
        <div class="media-empty-icon">
            <i class="fas fa-images"></i>
        </div>
        <h4 class="media-empty-title">Медіафайли відсутні</h4>
        <p class="media-empty-description">Завантажте перший файл, щоб почати роботу з медіа-бібліотекою.</p>
    </div>
<?php else: ?>
    <!-- Режим таблицы для списка -->
    <table class="media-table" style="display: none;">
        <tbody>
            <?php foreach ($files as $file): ?>
                <tr class="media-item" data-id="<?= $file['id'] ?>">
                    <td>
                        <input type="checkbox" class="media-checkbox" data-id="<?= $file['id'] ?>" id="media-<?= $file['id'] ?>">
                        <label for="media-<?= $file['id'] ?>" class="media-checkbox-label"></label>
                    </td>
                    <td>
                        <div class="media-thumbnail">
                            <?php if ($file['media_type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars(UrlHelper::toProtocolRelative($file['file_url'])) ?>" 
                                     alt="<?= htmlspecialchars($file['alt_text'] ?? $file['title'] ?? '') ?>">
                            <?php elseif ($file['media_type'] === 'video'): ?>
                                <div class="media-icon video-icon">
                                    <i class="fas fa-video fa-2x"></i>
                                </div>
                            <?php elseif ($file['media_type'] === 'audio'): ?>
                                <div class="media-icon audio-icon">
                                    <i class="fas fa-music fa-2x"></i>
                                </div>
                            <?php elseif ($file['media_type'] === 'document'): ?>
                                <div class="media-icon document-icon">
                                    <i class="fas fa-file fa-2x"></i>
                                </div>
                            <?php else: ?>
                                <div class="media-icon other-icon">
                                    <i class="fas fa-file fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="media-title" title="<?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>">
                            <?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>
                        </div>
                    </td>
                    <td>
                        <div class="media-info-content">
                            <?php 
                            $extension = strtoupper(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                            if ($file['media_type'] === 'image' && $file['width'] && $file['height']): ?>
                                <?= $extension ?> • <?= $file['width'] ?> × <?= $file['height'] ?>
                            <?php else: ?>
                                <?= $extension ?> • <?= formatFileSize($file['file_size']) ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="media-actions-list">
                            <button class="media-action-btn-list view-media" 
                                    data-id="<?= $file['id'] ?>"
                                    title="Переглянути">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="media-action-btn-list edit-media" 
                                    data-id="<?= $file['id'] ?>"
                                    title="Редагувати">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="media-action-btn-list delete-media" 
                                    data-id="<?= $file['id'] ?>"
                                    title="Видалити">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Режим сетки -->
    <div class="row media-grid-row">
        <?php foreach ($files as $file): ?>
            <div class="media-item" data-id="<?= $file['id'] ?>">
                <div class="card media-card">
                    <div class="media-checkbox-wrapper">
                        <input type="checkbox" class="media-checkbox" data-id="<?= $file['id'] ?>" id="media-grid-<?= $file['id'] ?>">
                        <label for="media-grid-<?= $file['id'] ?>" class="media-checkbox-label"></label>
                    </div>
                    <div class="media-thumbnail">
                        <?php if ($file['media_type'] === 'image'): ?>
                            <img src="<?= htmlspecialchars(UrlHelper::toProtocolRelative($file['file_url'])) ?>" 
                                 alt="<?= htmlspecialchars($file['alt_text'] ?? $file['title'] ?? '') ?>"
                                 class="img-fluid">
                        <?php elseif ($file['media_type'] === 'video'): ?>
                            <div class="media-icon video-icon">
                                <i class="fas fa-video fa-3x"></i>
                                <div class="media-overlay">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                        <?php elseif ($file['media_type'] === 'audio'): ?>
                            <div class="media-icon audio-icon">
                                <i class="fas fa-music fa-3x"></i>
                            </div>
                        <?php elseif ($file['media_type'] === 'document'): ?>
                            <div class="media-icon document-icon">
                                <i class="fas fa-file fa-3x"></i>
                            </div>
                        <?php else: ?>
                            <div class="media-icon other-icon">
                                <i class="fas fa-file fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <div class="media-actions-overlay">
                            <button class="media-action-btn view-media" 
                                    data-id="<?= $file['id'] ?>"
                                    title="Переглянути">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="media-action-btn edit-media" 
                                    data-id="<?= $file['id'] ?>"
                                    title="Редагувати">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="media-action-btn delete-media" 
                                    data-id="<?= $file['id'] ?>"
                                    title="Видалити">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="media-title" title="<?= htmlspecialchars($file['title'] ?? $file['original_name']) ?>">
                            <?= htmlspecialchars(mb_substr($file['title'] ?? $file['original_name'], 0, 25)) ?>
                            <?= mb_strlen($file['title'] ?? $file['original_name']) > 25 ? '...' : '' ?>
                        </div>
                        <div class="media-info-content">
                            <small class="text-muted">
                                <?php 
                                $extension = strtoupper(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                if ($file['media_type'] === 'image' && $file['width'] && $file['height']): ?>
                                    <?= $extension ?> • <?= $file['width'] ?> × <?= $file['height'] ?>
                                <?php else: ?>
                                    <?= $extension ?> • <?= formatFileSize($file['file_size']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

