<?php
/**
 * Страница просмотра логов системы
 * 
 * @package Engine\Skins\Pages
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/AdminPage.php';

// Убеждаемся, что Logger доступен
if (!class_exists('Logger')) {
    require_once dirname(__DIR__, 2) . '/modules/Logger.php';
}

class LogsPage extends AdminPage {
    private $logger;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Логи системы - Landing CMS';
        $this->templateName = 'logs';
        
        $this->setPageHeader(
            'Логи системы',
            'Просмотр логов ошибок и событий системы',
            'fas fa-file-alt'
        );
        
        $this->logger = logger();
    }
    
    public function handle() {
        // Обработка AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обработка действий
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = sanitizeInput($_POST['action'] ?? '');
            
            if ($action === 'clear_logs' && $this->verifyCsrf()) {
                $type = sanitizeInput($_POST['type'] ?? '');
                $days = isset($_POST['days']) ? (int)$_POST['days'] : null;
                
                if ($this->logger->clearLogs($type ?: null, $days)) {
                    $this->setMessage('Логи успешно очищены', 'success');
                } else {
                    $this->setMessage('Ошибка при очистке логов', 'danger');
                }
            }
        }
        
        // Получаем данные для отображения
        $type = sanitizeInput($_GET['type'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $logs = $this->logger->getLogs($type ?: null, $limit, $offset);
        $stats = $this->logger->getLogStats();
        
        // Рендеринг страницы
        $this->render([
            'logs' => $logs,
            'stats' => $stats,
            'currentType' => $type,
            'limit' => $limit,
            'offset' => $offset,
            'types' => [
                Logger::TYPE_ERROR => 'Ошибки',
                Logger::TYPE_WARNING => 'Предупреждения',
                Logger::TYPE_INFO => 'Информация',
                Logger::TYPE_SUCCESS => 'Успехи',
                Logger::TYPE_DEBUG => 'Отладка'
            ]
        ]);
    }
    
    private function handleAjax() {
        header('Content-Type: application/json; charset=utf-8');
        
        $action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
        
        switch ($action) {
            case 'get_logs':
                $type = sanitizeInput($_POST['type'] ?? '');
                $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 100;
                $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
                
                $logs = $this->logger->getLogs($type ?: null, $limit, $offset);
                
                echo json_encode([
                    'success' => true,
                    'logs' => $logs
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            case 'get_stats':
                $stats = $this->logger->getLogStats();
                
                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}

