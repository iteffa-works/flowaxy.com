<?php
/**
 * Страница настроек модуля логирования
 * 
 * @package Engine\Skins\Pages
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/AdminPage.php';

// Убеждаемся, что Logger доступен
if (!class_exists('Logger')) {
    require_once dirname(__DIR__, 2) . '/modules/Logger.php';
}

class LoggerSettingsPage extends AdminPage {
    private $logger;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Настройки логирования - Landing CMS';
        $this->templateName = 'logger-settings';
        
        $this->setPageHeader(
            'Настройки логирования',
            'Управление настройками системы логирования',
            'fas fa-cog'
        );
        
        $this->logger = logger();
    }
    
    public function handle() {
        // Обработка сохранения настроек
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verifyCsrf()) {
            $this->saveSettings();
        }
        
        // Получаем текущие настройки
        $settings = $this->logger->getSettings();
        
        // Рендеринг страницы
        $this->render([
            'settings' => $settings
        ]);
    }
    
    /**
     * Сохранение настроек
     */
    private function saveSettings(): void {
        $settings = [
            'max_file_size' => (int)($_POST['max_file_size'] ?? 10485760),
            'retention_days' => (int)($_POST['retention_days'] ?? 30),
            'log_errors' => isset($_POST['log_errors']) ? '1' : '0',
            'log_warnings' => isset($_POST['log_warnings']) ? '1' : '0',
            'log_info' => isset($_POST['log_info']) ? '1' : '0',
            'log_success' => isset($_POST['log_success']) ? '1' : '0',
            'log_debug' => isset($_POST['log_debug']) ? '1' : '0',
            'log_db_queries' => isset($_POST['log_db_queries']) ? '1' : '0',
            'log_file_operations' => isset($_POST['log_file_operations']) ? '1' : '0',
            'log_plugin_events' => isset($_POST['log_plugin_events']) ? '1' : '0',
            'log_module_events' => isset($_POST['log_module_events']) ? '1' : '0',
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            if (!$this->logger->setSetting($key, $value)) {
                $success = false;
            }
        }
        
        if ($success) {
            $this->setMessage('Настройки успешно сохранены', 'success');
        } else {
            $this->setMessage('Ошибка при сохранении настроек', 'danger');
        }
    }
}

