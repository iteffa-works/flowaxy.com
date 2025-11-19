<?php
/**
 * Адмін-сторінка тестового меню
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';

class TestMenuAdminPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Тестове меню - Flowaxy CMS';
        $this->templateName = 'test-menu';
        
        $this->setPageHeader(
            'Тестове меню',
            'Простий тестовий плагін',
            'fas fa-flask'
        );
    }
    
    /**
     * Отримання шляху до шаблону плагіна
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
    
    public function handle() {
        // Просто рендеримо сторінку
        $this->render([
            'message' => 'Це тестова сторінка з боковим меню!'
        ]);
    }
}

