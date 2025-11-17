<?php
/**
 * Media Library Plugin
 * Плагін для керування медіафайлами системи
 * 
 * @package Plugins\MediaLibrary
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/engine/classes/base/BasePlugin.php';
require_once __DIR__ . '/Media.php';

class MediaLibraryPlugin extends BasePlugin {
    
    private ?Media $media = null;
    
    /**
     * Ініціалізація плагіна
     */
    public function init(): void {
        $this->media = new Media();
        
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
        
        // Реєстрація хуків для вбудовування медиагалереї в інші модулі
        addHook('media_render_selector', [$this->media, 'hookRenderMediaSelector']);
        addHook('media_get_files', [$this->media, 'hookGetMediaFiles']);
        addHook('media_upload_file', [$this->media, 'hookUploadMediaFile']);
    }
    
    /**
     * Активація плагіна
     */
    public function activate(): void {
        $this->install();
    }
    
    /**
     * Деактивація плагіна
     */
    public function deactivate(): void {
        // Очищаємо кеш при деактивації
        if (function_exists('cache_clear')) {
            cache_clear('media_files_');
            cache_clear('media_selector_');
        }
    }
    
    /**
     * Встановлення плагіна (створення таблиць)
     */
    public function install(): void {
        $db = DatabaseHelper::getConnection();
        if (!$db) {
            return;
        }
        
        try {
            // Читаємо SQL з файлу install.sql
            $sqlFile = __DIR__ . '/install.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $db->exec($sql);
            } else {
                // Fallback: створюємо таблицю напряму
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `media_files` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `file_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `file_size` bigint(20) NOT NULL,
                      `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `media_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `width` int(11) DEFAULT NULL,
                      `height` int(11) DEFAULT NULL,
                      `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                      `description` text COLLATE utf8mb4_unicode_ci,
                      `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                      `uploaded_by` int(11) NOT NULL,
                      `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `idx_media_type` (`media_type`),
                      KEY `idx_uploaded_at` (`uploaded_at`),
                      KEY `idx_uploaded_by` (`uploaded_by`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (Exception $e) {
            error_log("MediaLibraryPlugin: Failed to install tables: " . $e->getMessage());
        }
    }
    
    /**
     * Видалення плагіна
     */
    public function uninstall(): void {
        $db = DatabaseHelper::getConnection();
        if (!$db) {
            return;
        }
        
        try {
            // Видаляємо налаштування плагіна
            $stmt = $db->prepare("DELETE FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute(['media-library']);
            
            // Очищаємо кеш
            if (function_exists('cache_clear')) {
                cache_clear('media_files_');
                cache_clear('media_selector_');
            }
            
            // Опціонально: видаляємо таблицю (закоментовано для збереження даних)
            // $db->exec("DROP TABLE IF EXISTS `media_files`");
        } catch (Exception $e) {
            error_log("MediaLibraryPlugin: Failed to uninstall: " . $e->getMessage());
        }
    }
    
    /**
     * Додавання пункту меню в адмінку
     */
    public function addAdminMenuItem(array $menu): array {
        // Додаємо пункт меню "Медіа-бібліотека"
        $menu[] = [
            'href' => UrlHelper::admin('media-library'),
            'icon' => 'fas fa-images',
            'text' => 'Медіа-бібліотека',
            'page' => 'media-library',
            'order' => 20
        ];
        
        return $menu;
    }
    
    /**
     * Реєстрація маршрутів адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        require_once __DIR__ . '/admin/MediaLibraryAdminPage.php';
        
        $router->add(['GET', 'POST'], 'media-library', 'MediaLibraryAdminPage');
        // Також реєструємо старий роут 'media' для зворотної сумісності
        $router->add(['GET', 'POST'], 'media', 'MediaLibraryAdminPage');
    }
    
    /**
     * Отримання екземпляра Media
     */
    public function getMedia(): ?Media {
        return $this->media;
    }
}

