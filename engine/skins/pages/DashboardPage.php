<?php
/**
 * Главная страница админки
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class DashboardPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Панель управління - Flowaxy CMS';
        $this->templateName = 'dashboard';
        
        $headerButtons = $this->createButton('Налаштування', 'outline-secondary', [
            'url' => UrlHelper::admin('settings'),
            'icon' => 'cog',
            'attributes' => ['class' => 'btn-sm']
        ]);
        
        $this->setPageHeader(
            'Панель управління',
            'Ласкаво просимо до Flowaxy CMS',
            'fas fa-tachometer-alt',
            $headerButtons
        );
    }
    
    public function handle() {
        // Виджеты добавляются через хук dashboard_widgets
        // Плагины могут использовать хук для добавления своих виджетов
        // Рендерим страницу без данных статистики
        $this->render([]);
    }
}
