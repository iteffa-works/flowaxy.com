<?php
/**
 * Главная страница админки
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class DashboardPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Панель управління - Landing CMS';
        $this->templateName = 'dashboard';
        
        $this->setPageHeader(
            'Панель управління',
            'Ласкаво просимо до Landing CMS',
            'fas fa-tachometer-alt',
            '<a href="' . adminUrl('settings') . '" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog me-1"></i>Налаштування
            </a>'
        );
    }
    
    public function handle() {
        // Получение статистики
        $stats = $this->getStats();
        
        // Рендерим страницу
        $this->render([
            'stats' => $stats
        ]);
    }
    
    /**
     * Получение статистики
     */
    private function getStats() {
        $stats = [
            'plugins' => 0,
            'media' => 0
        ];
        
        if ($this->db && isDatabaseAvailable()) {
            try {
                // Плагины
                $stmt = $this->db->query("SHOW TABLES LIKE 'plugins'");
                if ($stmt->rowCount() > 0) {
                    $stats['plugins'] = $this->db->query("SELECT COUNT(*) FROM plugins WHERE is_active = 1")->fetchColumn();
                }
                
                // Медиа файлы
                $stmt = $this->db->query("SHOW TABLES LIKE 'media_files'");
                if ($stmt->rowCount() > 0) {
                    $stats['media'] = $this->db->query("SELECT COUNT(*) FROM media_files")->fetchColumn();
                }
            } catch (Exception $e) {
                error_log("Dashboard stats error: " . $e->getMessage());
            }
        }
        
        return $stats;
    }
}
