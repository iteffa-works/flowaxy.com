<?php
/**
 * Сторінка налаштувань
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class SettingsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування сайту - Flowaxy CMS';
        $this->templateName = 'settings';
        
        $headerButtons = '<div class="d-flex gap-2">' .
            '<a href="' . UrlHelper::admin('cache-view') . '" class="btn btn-outline-secondary btn-sm">' .
            '<i class="fas fa-database me-1"></i>Переглянути кеш</a>' .
            '<a href="' . UrlHelper::admin('logs-view') . '" class="btn btn-outline-secondary btn-sm">' .
            '<i class="fas fa-file-alt me-1"></i>Переглянути логи</a>' .
            '</div>';
        
        $this->setPageHeader(
            'Налаштування сайту',
            'Основні налаштування та конфігурація',
            'fas fa-cog',
            $headerButtons
        );
    }
    
    public function handle() {
        // Обробка збереження
        if ($_POST && isset($_POST['save_settings'])) {
            $this->saveSettings();
        }
        
        // Отримання налаштувань
        $settings = $this->getSettings();
        
        // Рендеримо сторінку
        $this->render([
            'settings' => $settings
        ]);
    }
    
    /**
     * Збереження налаштувань
     */
    private function saveSettings() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $settings = $_POST['settings'] ?? [];
        
        // Санитизация значений
        $sanitizedSettings = [];
        foreach ($settings as $key => $value) {
            $sanitizedSettings[$key] = SecurityHelper::sanitizeInput($value);
        }
        
        try {
            // Используем SettingsManager для сохранения настроек
            if (class_exists('SettingsManager')) {
                $settingsManager = settingsManager();
                $result = $settingsManager->setMultiple($sanitizedSettings);
                
                if ($result) {
                    $this->setMessage('Налаштування успішно збережено', 'success');
                } else {
                    $this->setMessage('Помилка при збереженні налаштувань', 'danger');
                }
            } else {
                throw new Exception('SettingsManager не доступний');
            }
        } catch (Exception $e) {
            $this->setMessage('Помилка при збереженні налаштувань: ' . $e->getMessage(), 'danger');
            error_log("Settings save error: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання налаштувань
     */
    private function getSettings() {
        // Значення за замовчуванням
        $defaultSettings = [
            'admin_email' => 'admin@example.com',
            'timezone' => 'Europe/Kiev',
            // Настройки кеша
            'cache_enabled' => '1',
            'cache_default_ttl' => '3600',
            'cache_auto_cleanup' => '1',
            // Настройки логирования
            'logging_enabled' => '1',
            'logging_level' => 'INFO',
            'logging_max_file_size' => '10485760', // 10 MB
            'logging_retention_days' => '30'
        ];
        
        // Используем SettingsManager для получения настроек
        if (class_exists('SettingsManager')) {
            try {
                $settingsManager = settingsManager();
                $settings = $settingsManager->all();
                return array_merge($defaultSettings, $settings);
            } catch (Exception $e) {
                error_log("Settings load error: " . $e->getMessage());
                return $defaultSettings;
            }
        }
        
        return $defaultSettings;
    }
}
