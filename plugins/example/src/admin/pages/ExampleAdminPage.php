<?php
/**
 * Адмін-сторінка плагіна
 * 
 * @package ExamplePlugin
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../../engine/skins/includes/AdminPage.php';

class ExampleAdminPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу (якщо потрібно)
        // if (!function_exists('current_user_can') || !current_user_can('admin.plugins.view')) {
        //     Response::redirectStatic(UrlHelper::admin('dashboard'));
        //     exit;
        // }
        
        $this->pageTitle = 'Example Plugin - Flowaxy CMS';
        $this->templateName = 'template'; // Имя файла template.php
        
        $this->setPageHeader(
            'Example Plugin',
            'Управління плагіном Example Plugin',
            'fas fa-puzzle-piece'
        );
    }
    
    /**
     * Перевизначення шляху до шаблону для плагіна
     */
    protected function getTemplatePath() {
        return __DIR__ . '/../templates/'; // Папка templates плагіна
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

