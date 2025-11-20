<?php
/**
 * Административная страница для просмотра истории взаимодействий с Telegram
 */

require_once dirname(__DIR__, 5) . '/engine/skins/includes/AdminPage.php';

class TelegramHistoryPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'История Telegram - Flowaxy CMS';
        $this->templateName = 'telegram-history';
        
        $this->setPageHeader(
            'История взаимодействий',
            'Просмотр истории входящих и исходящих сообщений с Telegram',
            'fas fa-history',
            ''
        );
    }
    
    public function handle() {
        // Обработка AJAX запросов
        if ($this->isAjaxRequest()) {
            $this->handleAjax();
            return;
        }
        
        // Обработка удаления записи
        if ($_POST && $this->post('action') === 'delete') {
            $this->handleDelete();
        }
        
        // Рендерим страницу
        $this->render();
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax(): void {
        $action = $this->post('action', '');
        
        switch ($action) {
            case 'get_history':
                $this->handleGetHistory();
                break;
            case 'clear_history':
                $this->handleClearHistory();
                break;
            default:
                Response::jsonResponse(['success' => false, 'message' => 'Неизвестное действие'], 400);
        }
    }
    
    /**
     * Получение истории через AJAX
     */
    private function handleGetHistory(): void {
        try {
            $page = (int)($this->post('page', 1));
            $limit = (int)($this->post('limit', 50));
            $direction = $this->post('direction', '');
            $type = $this->post('type', '');
            
            $history = $this->getHistory($page, $limit, $direction, $type);
            $total = $this->getHistoryCount($direction, $type);
            
            Response::jsonResponse([
                'success' => true,
                'data' => $history,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]);
        } catch (Exception $e) {
            Response::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Очистка истории
     */
    private function handleClearHistory(): void {
        if (!$this->verifyCsrf()) {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка безопасности'], 403);
            return;
        }
        
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                Response::jsonResponse(['success' => false, 'message' => 'Ошибка подключения к БД'], 500);
                return;
            }
            
            $stmt = $db->prepare("DELETE FROM telegram_history");
            $stmt->execute();
            
            Response::jsonResponse(['success' => true, 'message' => 'История очищена']);
        } catch (Exception $e) {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Удаление записи
     */
    private function handleDelete(): void {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Ошибка безопасности', 'danger');
            return;
        }
        
        $id = (int)($this->post('id', 0));
        if (!$id) {
            $this->setMessage('Не указан ID записи', 'danger');
            return;
        }
        
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                $this->setMessage('Ошибка подключения к БД', 'danger');
                return;
            }
            
            $stmt = $db->prepare("DELETE FROM telegram_history WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->setMessage('Запись удалена', 'success');
            $this->redirect('telegram-history');
        } catch (Exception $e) {
            $this->setMessage('Ошибка удаления: ' . $e->getMessage(), 'danger');
        }
    }
    
    /**
     * Получение истории взаимодействий
     * 
     * @param int $page Номер страницы
     * @param int $limit Лимит записей
     * @param string $direction Фильтр по направлению (incoming/outgoing)
     * @param string $type Фильтр по типу
     * @return array
     */
    private function getHistory(int $page = 1, int $limit = 50, string $direction = '', string $type = ''): array {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            if ($direction) {
                $where[] = "direction = ?";
                $params[] = $direction;
            }
            
            if ($type) {
                $where[] = "type = ?";
                $params[] = $type;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "
                SELECT * FROM telegram_history 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("TelegramHistoryPage getHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение общего количества записей
     * 
     * @param string $direction Фильтр по направлению
     * @param string $type Фильтр по типу
     * @return int
     */
    private function getHistoryCount(string $direction = '', string $type = ''): int {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                return 0;
            }
            
            $where = [];
            $params = [];
            
            if ($direction) {
                $where[] = "direction = ?";
                $params[] = $direction;
            }
            
            if ($type) {
                $where[] = "type = ?";
                $params[] = $type;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT COUNT(*) as total FROM telegram_history {$whereClause}";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("TelegramHistoryPage getHistoryCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Получение пути к шаблону
     */
    protected function getTemplatePath(): string {
        // Путь к шаблонам: plugins/telegram-plugin/resources/views/admin/
        $templateDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;
        $realPath = realpath($templateDir);
        if ($realPath !== false) {
            return $realPath . DIRECTORY_SEPARATOR;
        }
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $templateDir);
    }
    
    /**
     * Получение данных для шаблона
     */
    protected function getTemplateData(): array {
        $parentData = parent::getTemplateData();
        
        // Получаем историю для первой страницы
        $history = $this->getHistory(1, 50);
        $total = $this->getHistoryCount();
        $incomingCount = $this->getHistoryCount('incoming');
        $outgoingCount = $this->getHistoryCount('outgoing');
        
        // Подсчитываем ошибки
        try {
            $db = DatabaseHelper::getConnection();
            if ($db) {
                $stmt = $db->query("SELECT COUNT(*) as total FROM telegram_history WHERE status = 'error'");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $errorCount = (int)($result['total'] ?? 0);
            } else {
                $errorCount = 0;
            }
        } catch (Exception $e) {
            $errorCount = 0;
        }
        
        return array_merge($parentData, [
            'history' => $history,
            'total' => $total,
            'incomingCount' => $incomingCount,
            'outgoingCount' => $outgoingCount,
            'errorCount' => $errorCount,
            'page' => 1,
            'limit' => 50,
            'pages' => ceil($total / 50)
        ]);
    }
}
