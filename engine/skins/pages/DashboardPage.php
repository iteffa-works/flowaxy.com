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
     * Получение статистики с кешированием
     */
    private function getStats() {
        if (!$this->db || !isDatabaseAvailable()) {
            return [
                'plugins' => 0,
                'media' => 0
            ];
        }
        
        // Кешируем статистику на 5 минут
        return cache_remember('dashboard_stats', function() {
            $stats = [
                'plugins' => 0,
                'media' => 0
            ];
            
            $db = getDB();
            if (!$db) {
                return $stats;
            }
            
            try {
                // Проверяем существование таблиц и получаем статистику одним запросом
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM plugins WHERE is_active = 1");
                if ($stmt->execute()) {
                    $stats['plugins'] = (int)$stmt->fetchColumn();
                }
                
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM media_files");
                if ($stmt->execute()) {
                    $stats['media'] = (int)$stmt->fetchColumn();
                }
            } catch (Exception $e) {
                error_log("Dashboard stats error: " . $e->getMessage());
            }
            
            return $stats;
        }, 300); // Кеш на 5 минут
    }
}
