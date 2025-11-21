<?php
/**
 * Головна сторінка адмінки
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class DashboardPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        // Dashboard доступний всім авторизованим користувачам
        // (базова перевірка авторизації вже виконана в AdminPage::__construct)
        // Право admin.dashboard використовується для відображення статистики та віджетів
        
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
        // Віджети додаються через хук dashboard_widgets
        // Плагіни можуть використовувати хук для додавання своїх віджетів
        // Рендеримо сторінку без даних статистики
        $this->render([]);
    }
}
