<?php
/**
 * Адмін-сторінка поштового клієнта
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';

class MailClientAdminPage extends AdminPage {
    
    private $mailModule;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Поштовий клієнт - Flowaxy CMS';
        $this->templateName = 'mail-client';
        
        $this->setPageHeader(
            'Поштовий клієнт',
            'Управління поштою',
            'fas fa-envelope'
        );
        
        // Завантажуємо модуль Mail
        if (function_exists('mailModule')) {
            $this->mailModule = mailModule();
        }
        
        // Додаємо CSS та JS
        $pluginUrl = $this->getPluginUrl();
        $this->additionalCSS[] = $pluginUrl . '/assets/css/style.css?v=' . time();
        $this->additionalJS[] = $pluginUrl . '/assets/js/mail-client.js?v=' . time();
    }
    
    /**
     * Отримання шляху до шаблону плагіна
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
    
    public function handle() {
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Отримуємо папки
        $folders = $this->getFolders();
        
        // Отримуємо статистику по папках
        $stats = $this->getFolderStats();
        
        // Рендеримо сторінку
        $this->render([
            'folders' => $folders,
            'stats' => $stats
        ]);
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax() {
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        $action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
        
        try {
            switch ($action) {
                case 'get_emails':
                    $this->ajaxGetEmails();
                    break;
                    
                case 'get_email':
                    $this->ajaxGetEmail();
                    break;
                    
                case 'send_email':
                    $this->ajaxSendEmail();
                    break;
                    
                case 'delete_email':
                    $this->ajaxDeleteEmail();
                    break;
                    
                case 'mark_as_read':
                    $this->ajaxMarkAsRead();
                    break;
                    
                case 'mark_as_unread':
                    $this->ajaxMarkAsUnread();
                    break;
                    
                case 'toggle_star':
                    $this->ajaxToggleStar();
                    break;
                    
                case 'toggle_important':
                    $this->ajaxToggleImportant();
                    break;
                    
                case 'move_to_folder':
                    $this->ajaxMoveToFolder();
                    break;
                    
                case 'receive_emails':
                    $this->ajaxReceiveEmails();
                    break;
                    
                case 'get_folders':
                    $this->ajaxGetFolders();
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Невідома дія: ' . $action], JSON_UNESCAPED_UNICODE);
                    exit;
            }
        } catch (Exception $e) {
            error_log("MailClientAdminPage AJAX error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    /**
     * Отримання URL плагіна
     */
    protected function getPluginUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/plugins/mail-client';
    }
    
    /**
     * Отримання папок
     */
    private function getFolders(): array {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->query("SELECT * FROM mail_client_folders ORDER BY order_num ASC, name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting folders: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Отримання статистики по папках
     */
    private function getFolderStats(): array {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    folder,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
                FROM mail_client_emails
                WHERE is_deleted = 0
                GROUP BY folder
            ");
            $stats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['folder']] = [
                    'total' => (int)$row['total'],
                    'unread' => (int)$row['unread']
                ];
            }
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting folder stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * AJAX: Отримання листів
     */
    private function ajaxGetEmails() {
        $folder = sanitizeInput($_GET['folder'] ?? 'inbox');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        if (!$this->db) {
            echo json_encode(['success' => false, 'error' => 'БД недоступна'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $where = "folder = ? AND is_deleted = 0";
            $params = [$folder];
            
            // Додаткові фільтри для спеціальних папок
            if ($folder === 'starred') {
                $where = "is_starred = 1 AND is_deleted = 0";
                $params = [];
            } elseif ($folder === 'important') {
                $where = "is_important = 1 AND is_deleted = 0";
                $params = [];
            } elseif ($folder === 'all') {
                $where = "is_deleted = 0";
                $params = [];
            } elseif ($folder === 'trash') {
                $where = "is_deleted = 1";
                $params = [];
            }
            
            // Підрахунок загальної кількості
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM mail_client_emails WHERE {$where}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Отримання листів
            $stmt = $this->db->prepare("
                SELECT 
                    id, message_id, folder, `from`, `to`, subject, 
                    date_received, date_sent, is_read, is_starred, 
                    is_important, LEFT(body, 200) as preview
                FROM mail_client_emails
                WHERE {$where}
                ORDER BY date_received DESC, date_sent DESC
                LIMIT ? OFFSET ?
            ");
            
            $allParams = array_merge($params, [$limit, $offset]);
            $stmt->execute($allParams);
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'emails' => $emails,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error getting emails: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка отримання листів'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Отримання одного листа
     */
    private function ajaxGetEmail() {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if (!$id || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'ID не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM mail_client_emails WHERE id = ?");
            $stmt->execute([$id]);
            $email = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($email) {
                // Позначаємо як прочитане
                if (!$email['is_read']) {
                    $updateStmt = $this->db->prepare("UPDATE mail_client_emails SET is_read = 1 WHERE id = ?");
                    $updateStmt->execute([$id]);
                    $email['is_read'] = 1;
                }
                
                echo json_encode(['success' => true, 'email' => $email], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Лист не знайдено'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error getting email: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка отримання листа'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Відправка листа
     */
    private function ajaxSendEmail() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!$this->mailModule) {
            echo json_encode(['success' => false, 'error' => 'Модуль пошти не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $to = sanitizeInput($_POST['to'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $body = $_POST['body'] ?? '';
        $isHtml = isset($_POST['is_html']) ? (bool)$_POST['is_html'] : true;
        
        if (empty($to) || empty($subject)) {
            echo json_encode(['success' => false, 'error' => 'Отримувач та тема обов\'язкові'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $result = $this->mailModule->sendEmail($to, $subject, $body, ['is_html' => $isHtml]);
            
            if ($result) {
                // Зберігаємо копію в надіслані
                if ($this->db) {
                    $stmt = $this->db->prepare("
                        INSERT INTO mail_client_emails 
                        (message_id, folder, `from`, `to`, subject, body, body_html, date_sent, date_received, is_read)
                        VALUES (?, 'sent', ?, ?, ?, ?, ?, NOW(), NOW(), 1)
                    ");
                    $settings = $this->mailModule->getSettings();
                    $from = $settings['from_email'] ?? '';
                    $messageId = md5($to . $subject . time());
                    $stmt->execute([
                        $messageId,
                        $from,
                        $to,
                        $subject,
                        strip_tags($body),
                        $isHtml ? $body : null
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Лист успішно відправлено'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Помилка відправки листа'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Видалення листа
     */
    private function ajaxDeleteEmail() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'ID не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Переміщуємо в кошик або видаляємо назавжди
            $permanent = isset($_POST['permanent']) && $_POST['permanent'];
            
            if ($permanent) {
                $stmt = $this->db->prepare("DELETE FROM mail_client_emails WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $this->db->prepare("UPDATE mail_client_emails SET is_deleted = 1, folder = 'trash' WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Лист видалено'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error deleting email: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка видалення'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Позначити як прочитане (один або кілька)
     */
    private function ajaxMarkAsRead() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!$this->db) {
            echo json_encode(['success' => false, 'error' => 'БД недоступна'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Перевіряємо чи це масив ID
            $ids = [];
            if (isset($_POST['ids'])) {
                $idsJson = json_decode($_POST['ids'], true);
                if (is_array($idsJson)) {
                    $ids = array_map('intval', $idsJson);
                }
            }
            
            // Якщо немає масиву, перевіряємо одиночний ID
            if (empty($ids)) {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $ids = [$id];
                }
            }
            
            if (empty($ids)) {
                echo json_encode(['success' => false, 'error' => 'ID не вказано'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Оновлюємо всі листи
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("UPDATE mail_client_emails SET is_read = 1 WHERE id IN ({$placeholders})");
            $stmt->execute($ids);
            
            echo json_encode(['success' => true, 'updated' => count($ids)], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error marking as read: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Позначити як непрочитане
     */
    private function ajaxMarkAsUnread() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'ID не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE mail_client_emails SET is_read = 0 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Помилка'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Перемкнути зірочку
     */
    private function ajaxToggleStar() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'ID не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Отримуємо поточний стан
            $stmt = $this->db->prepare("SELECT is_starred FROM mail_client_emails WHERE id = ?");
            $stmt->execute([$id]);
            $email = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($email) {
                $newState = $email['is_starred'] ? 0 : 1;
                $updateStmt = $this->db->prepare("UPDATE mail_client_emails SET is_starred = ? WHERE id = ?");
                $updateStmt->execute([$newState, $id]);
                echo json_encode(['success' => true, 'is_starred' => $newState], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Лист не знайдено'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Помилка'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Перемкнути важливе
     */
    private function ajaxToggleImportant() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'ID не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT is_important FROM mail_client_emails WHERE id = ?");
            $stmt->execute([$id]);
            $email = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($email) {
                $newState = $email['is_important'] ? 0 : 1;
                $updateStmt = $this->db->prepare("UPDATE mail_client_emails SET is_important = ? WHERE id = ?");
                $updateStmt->execute([$newState, $id]);
                echo json_encode(['success' => true, 'is_important' => $newState], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Лист не знайдено'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Помилка'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Перемістити в папку
     */
    private function ajaxMoveToFolder() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $folder = sanitizeInput($_POST['folder'] ?? '');
        
        if (!$id || !$folder || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'Параметри не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE mail_client_emails SET folder = ? WHERE id = ?");
            $stmt->execute([$folder, $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Помилка'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Отримати пошту через POP3/IMAP
     */
    private function ajaxReceiveEmails() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!$this->mailModule || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'Модуль пошти не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Отримуємо листи через POP3
            $result = $this->mailModule->receiveEmails(50);
            
            if ($result['success'] && !empty($result['emails'])) {
                $imported = 0;
                
                foreach ($result['emails'] as $emailData) {
                    // Перевіряємо чи вже є такий лист
                    $messageId = md5($emailData['from'] . $emailData['subject'] . ($emailData['date'] ?? ''));
                    
                    $checkStmt = $this->db->prepare("SELECT id FROM mail_client_emails WHERE message_id = ?");
                    $checkStmt->execute([$messageId]);
                    
                    if (!$checkStmt->fetch()) {
                        // Зберігаємо лист
                        $stmt = $this->db->prepare("
                            INSERT INTO mail_client_emails 
                            (message_id, folder, `from`, `to`, subject, body, body_html, date_received, is_read)
                            VALUES (?, 'inbox', ?, ?, ?, ?, ?, ?, 0)
                        ");
                        
                        $dateReceived = null;
                        if (!empty($emailData['date'])) {
                            try {
                                $dateReceived = date('Y-m-d H:i:s', strtotime($emailData['date']));
                            } catch (Exception $e) {
                                $dateReceived = null;
                            }
                        }
                        
                        $dateReceived = $dateReceived ?: date('Y-m-d H:i:s');
                        
                        // Витягуємо тіло листа
                        $bodyText = $emailData['body'] ?? '';
                        $bodyHtml = $emailData['body_html'] ?? '';
                        
                        // Якщо є HTML, використовуємо його, інакше текст
                        if (empty($bodyText) && !empty($bodyHtml)) {
                            $bodyText = strip_tags($bodyHtml);
                        }
                        
                        $stmt->execute([
                            $messageId,
                            $emailData['from'] ?? '',
                            $emailData['to'] ?? '',
                            $emailData['subject'] ?? '',
                            $bodyText,
                            $bodyHtml ?: null, // HTML версія
                            $dateReceived
                        ]);
                        
                        $imported++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "Отримано {$imported} нових листів",
                    'imported' => $imported
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['message'] ?? 'Помилка отримання пошти'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error receiving emails: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX: Отримати папки
     */
    private function ajaxGetFolders() {
        $folders = $this->getFolders();
        $stats = $this->getFolderStats();
        
        echo json_encode([
            'success' => true,
            'folders' => $folders,
            'stats' => $stats
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

