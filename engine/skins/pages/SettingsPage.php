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
        
        // Используем вспомогательные методы для создания кнопок
        $headerButtons = $this->createButtonGroup([
            [
                'text' => 'Переглянути кеш',
                'type' => 'outline-secondary',
                'options' => [
                    'url' => UrlHelper::admin('cache-view'),
                    'icon' => 'database',
                    'attributes' => ['class' => 'btn-sm']
                ]
            ],
            [
                'text' => 'Переглянути логи',
                'type' => 'outline-secondary',
                'options' => [
                    'url' => UrlHelper::admin('logs-view'),
                    'icon' => 'file-alt',
                    'attributes' => ['class' => 'btn-sm']
                ]
            ]
        ]);
        
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
        
        $settings = $this->post('settings', []);
        
        // Обработка checkbox полей (если не отмечены, они не приходят в POST)
        $checkboxFields = ['cache_enabled', 'cache_auto_cleanup', 'logging_enabled'];
        foreach ($checkboxFields as $field) {
            if (!isset($settings[$field])) {
                $settings[$field] = '0';
            }
        }
        
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
                    // Обновляем настройки в Cache и Logger
                    if (class_exists('Cache')) {
                        cache()->reloadSettings();
                    }
                    if (class_exists('Logger')) {
                        logger()->reloadSettings();
                    }
                    
                    // Применяем timezone, если он был изменен
                    if (isset($sanitizedSettings['timezone'])) {
                        $timezone = $sanitizedSettings['timezone'];
                        if (!empty($timezone) && in_array($timezone, timezone_identifiers_list())) {
                            date_default_timezone_set($timezone);
                        }
                    }
                    
                    $this->setMessage('Налаштування успішно збережено', 'success');
                } else {
                    $this->setMessage('Помилка при збереженні налаштувань', 'danger');
                }
            } else {
                throw new Exception('SettingsManager не доступний');
            }
        } catch (Exception $e) {
            $this->setMessage('Помилка при збереженні налаштувань: ' . $e->getMessage(), 'danger');
            if (function_exists('logger')) {
                logger()->logError('Settings save error: ' . $e->getMessage());
            } else {
                error_log("Settings save error: " . $e->getMessage());
            }
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
