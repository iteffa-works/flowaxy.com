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
        
        $this->setPageHeader(
            'Панель управління',
            'Ласкаво просимо до Flowaxy CMS',
            'fas fa-tachometer-alt',
            '<a href="' . UrlHelper::admin('settings') . '" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog me-1"></i>Налаштування
            </a>'
        );
    }
    
    public function handle() {
        // Виджеты добавляются через хук dashboard_widgets
        // Плагины могут использовать хук для добавления своих виджетов
        // Рендерим страницу без данных статистики
        $this->render([]);
    }
}
