<?php
/**
 * Адмін-сторінка плагіна
 * 
 * @package PluginName
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../engine/skins/includes/AdminPage.php';

class PluginNameAdminPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу (якщо потрібно)
        // if (!function_exists('current_user_can') || !current_user_can('admin.plugins.view')) {
        //     Response::redirectStatic(UrlHelper::admin('dashboard'));
        //     exit;
        // }
        
        $this->pageTitle = 'Plugin Name - Flowaxy CMS';
        $this->templateName = 'plugin-name-admin';
        
        $this->setPageHeader(
            'Plugin Name',
            'Управління плагіном',
            'fas fa-cog'
        );
    }
    
    public function handle() {
        // Обробка POST запитів
        if ($_POST && isset($_POST['save_settings'])) {
            $this->saveSettings();
        }
        
        // Отримання даних для шаблону
        $data = $this->getTemplateData();
        $data['settings'] = $this->getSettings();
        
        $this->render($data);
    }
    
    private function saveSettings(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Збереження налаштувань
        $settings = $this->post('settings') ?? [];
        
        // Ваша логіка збереження
        
        $this->setMessage('Налаштування збережено', 'success');
    }
    
    private function getSettings(): array {
        // Отримання налаштувань плагіна
        return [];
    }
}

