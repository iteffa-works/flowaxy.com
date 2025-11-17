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
        
        $settings = $_POST['settings'] ?? [];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare("
                    INSERT INTO site_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, SecurityHelper::sanitizeInput($value)]);
            }
            
            $this->db->commit();
            // Очищаємо кеш налаштувань сайту
            cache_forget('site_settings');
            $this->setMessage('Налаштування успішно збережено', 'success');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->setMessage('Помилка при збереженні налаштувань', 'danger');
            error_log("Settings save error: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання налаштувань
     */
    private function getSettings() {
        $settings = [];
        
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'site_settings'");
            if ($stmt->rowCount() > 0) {
                $stmt = $this->db->query("SELECT setting_key, setting_value FROM site_settings");
                $settingsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($settingsData as $setting) {
                    $settings[$setting['setting_key']] = $setting['setting_value'];
                }
            }
        } catch (Exception $e) {
            error_log("Settings load error: " . $e->getMessage());
        }
        
        // Значення за замовчуванням
        $defaultSettings = [
            'site_name' => 'Flowaxy CMS',
            'site_tagline' => 'Сучасна система керування контентом',
            'site_description' => 'Створюйте красиві лендінги легко і швидко',
            'admin_email' => 'admin@example.com',
            'timezone' => 'Europe/Kiev',
            'copyright' => '© 2025 Spokinoki - Усі права захищені'
        ];
        
        return array_merge($defaultSettings, $settings);
    }
}
