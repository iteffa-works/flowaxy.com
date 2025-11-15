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
            'forms' => 0,
            'submissions' => 0,
            'submissions_today' => 0,
            'products' => 0,
            'categories' => 0,
            'media' => 0,
            'recent_submissions' => []
        ];
        
        if ($this->db && isDatabaseAvailable()) {
            try {
                // Плагины
                $stmt = $this->db->query("SHOW TABLES LIKE 'plugins'");
                if ($stmt->rowCount() > 0) {
                    $stats['plugins'] = $this->db->query("SELECT COUNT(*) FROM plugins WHERE is_active = 1")->fetchColumn();
                }
                
                // Формы
                $stmt = $this->db->query("SHOW TABLES LIKE 'forms'");
                if ($stmt->rowCount() > 0) {
                    $stats['forms'] = $this->db->query("SELECT COUNT(*) FROM forms WHERE is_active = 1")->fetchColumn();
                }
                
                // Заявки
                $stmt = $this->db->query("SHOW TABLES LIKE 'form_submissions'");
                if ($stmt->rowCount() > 0) {
                    $stats['submissions'] = $this->db->query("SELECT COUNT(*) FROM form_submissions")->fetchColumn();
                    $stats['submissions_today'] = $this->db->query("SELECT COUNT(*) FROM form_submissions WHERE DATE(created_at) = CURDATE()")->fetchColumn();
                    
                    // Последние 5 заявок
                    $stmt = $this->db->query("SELECT id, form_id, data, created_at FROM form_submissions ORDER BY created_at DESC LIMIT 5");
                    $stats['recent_submissions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                // Товары
                $stmt = $this->db->query("SHOW TABLES LIKE 'catalog_products'");
                if ($stmt->rowCount() > 0) {
                    $stats['products'] = $this->db->query("SELECT COUNT(*) FROM catalog_products WHERE is_active = 1")->fetchColumn();
                }
                
                // Категории
                $stmt = $this->db->query("SHOW TABLES LIKE 'catalog_categories'");
                if ($stmt->rowCount() > 0) {
                    $stats['categories'] = $this->db->query("SELECT COUNT(*) FROM catalog_categories WHERE is_active = 1")->fetchColumn();
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
