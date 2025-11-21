<?php
/**
 * Страница настроек сайта
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class SiteSettingsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування сайту - Flowaxy CMS';
        $this->templateName = 'site-settings';
        
        $this->setPageHeader(
            'Налаштування сайту',
            'Основні налаштування та конфігурація',
            'fas fa-cog'
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
        
        $settings = $this->post('settings') ?? [];
        
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
                
                // Очищаем кеш настроек перед сохранением, чтобы гарантировать свежие данные
                if (method_exists($settingsManager, 'clearCache')) {
                    $settingsManager->clearCache();
                }
                
                $result = $settingsManager->setMultiple($sanitizedSettings);
                
                if ($result) {
                    // Очищаем кеш настроек после сохранения
                    if (method_exists($settingsManager, 'clearCache')) {
                        $settingsManager->clearCache();
                    }
                    
                    // Перезагружаем настройки в SettingsManager
                    if (method_exists($settingsManager, 'reloadSettings')) {
                        $settingsManager->reloadSettings();
                    }
                    
                    // Обновляем настройки в Cache и Logger
                    if (class_exists('Cache')) {
                        $cacheInstance = cache();
                        if ($cacheInstance && method_exists($cacheInstance, 'reloadSettings')) {
                            $cacheInstance->reloadSettings();
                        }
                    }
                    if (class_exists('Logger')) {
                        $loggerInstance = logger();
                        if ($loggerInstance && method_exists($loggerInstance, 'reloadSettings')) {
                            $loggerInstance->reloadSettings();
                        }
                    }
                    
                    // Применяем timezone, если он был изменен
                    if (isset($sanitizedSettings['timezone'])) {
                        $timezone = $sanitizedSettings['timezone'];
                        if (!empty($timezone) && in_array($timezone, timezone_identifiers_list())) {
                            date_default_timezone_set($timezone);
                        }
                    }
                    
                    // Обновляем протокол в глобальной переменной, если он был изменен
                    if (isset($sanitizedSettings['site_protocol'])) {
                        $protocolSetting = $sanitizedSettings['site_protocol'];
                        if ($protocolSetting === 'https') {
                            $GLOBALS['_SITE_PROTOCOL'] = 'https://';
                        } elseif ($protocolSetting === 'http') {
                            $GLOBALS['_SITE_PROTOCOL'] = 'http://';
                        } else {
                            // Если 'auto', очищаем глобальную переменную для автоматического определения
                            unset($GLOBALS['_SITE_PROTOCOL']);
                        }
                    }
                    
                    $this->setMessage('Налаштування успішно збережено', 'success');
                    // Редирект после сохранения для предотвращения повторного выполнения
                    $this->redirect('site-settings');
                    exit;
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
        // Значення за замовчуванням (используются только если настройка отсутствует в БД)
        $defaultSettings = [
            'admin_email' => 'admin@example.com',
            'site_protocol' => 'auto', // Автоматическое определение протокола
            'timezone' => 'Europe/Kyiv',
            // Настройки кеша
            'cache_enabled' => '1',
            'cache_default_ttl' => '3600',
            'cache_auto_cleanup' => '1',
            // Настройки логирования
            'logging_enabled' => '1',
            'logging_level' => 'INFO',
            'logging_max_file_size' => '10485760', // 10 MB
            'logging_retention_days' => '30',
            // Настройки сессий
            'session_lifetime' => '7200', // 2 часа
            'session_name' => 'PHPSESSID',
            // Настройки базы данных
            'db_connection_timeout' => '3',
            'db_max_attempts' => '3',
            'db_host_check_timeout' => '1',
            'db_slow_query_threshold' => '1.0',
            // Настройки загрузки файлов
            'upload_max_file_size' => '10485760', // 10 MB
            'upload_allowed_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx,zip',
            'upload_allowed_mime_types' => 'image/jpeg,image/png,image/gif,application/pdf',
            // Настройки безопасности
            'password_min_length' => '8',
            'csrf_token_lifetime' => '3600', // 1 час
            // Настройки производительности
            'query_optimization_enabled' => '1',
            'max_queries_per_second' => '100'
        ];
        
        // Используем SettingsManager для получения настроек
        if (class_exists('SettingsManager')) {
            try {
                $settingsManager = settingsManager();
                
                // Очищаем кеш Cache перед загрузкой настроек, чтобы избежать использования устаревших данных
                if (function_exists('cache_forget')) {
                    cache_forget('site_settings');
                }
                
                // Очищаем кеш SettingsManager перед загрузкой
                if (method_exists($settingsManager, 'clearCache')) {
                    $settingsManager->clearCache();
                }
                
                // Загружаем настройки из БД напрямую (с force=true), минуя кеш
                if (method_exists($settingsManager, 'load')) {
                    $settingsManager->load(true); // force = true для принудительной перезагрузки
                } else if (method_exists($settingsManager, 'reloadSettings')) {
                    $settingsManager->reloadSettings();
                }
                
                // Получаем все настройки из БД
                $settings = $settingsManager->all();
                
                // Объединяем настройки: сначала дефолтные, затем из БД (БД имеет приоритет)
                // Это гарантирует, что если настройка есть в БД (даже со значением '0'), она будет использована
                $result = array_merge($defaultSettings, $settings);
                
                return $result;
            } catch (Exception $e) {
                error_log("Settings load error: " . $e->getMessage());
                return $defaultSettings;
            }
        }
        
        return $defaultSettings;
    }
}

