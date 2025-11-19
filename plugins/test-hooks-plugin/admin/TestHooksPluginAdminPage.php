<?php
/**
 * Административная страница плагина Test Hooks Plugin
 * Демонстрирует работу системы хуков
 */

require_once __DIR__ . '/../../../engine/skins/includes/AdminPage.php';

class TestHooksPluginAdminPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Test Hooks - Flowaxy CMS';
        $this->templateName = 'test-hooks';
        
        $this->setPageHeader(
            'Test Hooks Plugin',
            'Демонстрация работы системы хуков и событий',
            'fas fa-code',
            ''
        );
    }
    
    /**
     * Переопределяем путь к шаблону для использования шаблонов плагина
     */
    protected function getTemplatePath() {
        return __DIR__ . '/templates/';
    }
    
    public function handle() {
        // Получаем статистику хуков
        $hookManager = hookManager();
        $hookStats = $hookManager->getHookStats();
        $allHooks = $hookManager->getAllHooks();
        
        // Группируем хуки по типам
        $hooksByType = [
            'filters' => [],
            'actions' => []
        ];
        
        foreach ($allHooks as $hookName => $hooks) {
            foreach ($hooks as $hook) {
                $type = $hook['type'] ?? 'filter';
                if (!isset($hooksByType[$type . 's'])) {
                    $hooksByType[$type . 's'] = [];
                }
                if (!isset($hooksByType[$type . 's'][$hookName])) {
                    $hooksByType[$type . 's'][$hookName] = [];
                }
                $hooksByType[$type . 's'][$hookName][] = $hook;
            }
        }
        
        // Рендерим страницу
        $this->render([
            'hookStats' => $hookStats,
            'allHooks' => $allHooks,
            'hooksByType' => $hooksByType,
            'totalHooks' => $hookStats['total_hooks'] ?? 0,
            'hookCalls' => $hookStats['hook_calls'] ?? []
        ]);
    }
}

