<?php
/**
 * Сторінка "В розробці"
 * Шаблон для нових модулів
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class DevelopmentPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу (базова перевірка авторизації вже виконана в AdminPage::__construct)
        // Можна додати специфічну перевірку прав, якщо потрібно
        
        $this->pageTitle = 'В розробці - Flowaxy CMS';
        $this->templateName = 'development';
        
        $this->setPageHeader(
            'В розробці',
            'Ця сторінка знаходиться в стадії розробки',
            'fas fa-tools'
        );
    }
    
    public function handle() {
        // Рендеримо сторінку
        $this->render([]);
    }
}

