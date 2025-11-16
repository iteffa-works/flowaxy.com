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
        
        // НЕ завантажуємо модуль Mail тут - тільки коли потрібно (lazy loading)
        
        // Додаємо CSS та JS з версією плагіна (для кешування)
        $pluginUrl = $this->getPluginUrl();
        $version = '1.0.0'; // Версія плагіна
        $this->additionalCSS[] = $pluginUrl . '/assets/css/style.css?v=' . $version;
        $this->additionalJS[] = $pluginUrl . '/assets/js/mail-client.js?v=' . $version;
    }
    
    /**
     * Lazy loading модуля Mail
     */
    private function getMailModule() {
        if ($this->mailModule === null) {
            if (function_exists('mailModule')) {
                $this->mailModule = mailModule();
            }
        }
        return $this->mailModule;
    }
    
    /**
     * Отримання шляху до шаблону плагіна
     */
    protected function getTemplatePath() {
        return dirname(__DIR__) . '/templates/';
    }
    
    public function handle() {
        // Обробка скачивання вложений (не AJAX)
        if (isset($_GET['action']) && $_GET['action'] === 'download_attachment') {
            $this->ajaxDownloadAttachment();
            return;
        }
        
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Автоматично отримуємо нові листи при завантаженні сторінки (в фоновому режимі)
        // Не блокуємо завантаження сторінки
        $this->autoReceiveEmails();
        
        // Отримуємо тільки папки (легкий запит)
        $folders = $this->getFolders();
        
        // Статистику завантажуємо через AJAX (не блокуємо завантаження сторінки)
        // Рендеримо сторінку
        $this->render([
            'folders' => $folders,
            'stats' => [] // Пуста статистика, завантажиться через AJAX
        ]);
    }
    
    /**
     * Автоматичне отримання нових листів (викликається при завантаженні сторінки)
     */
    private function autoReceiveEmails() {
        // Запускаємо в фоновому режимі через AJAX, щоб не блокувати завантаження сторінки
        // Це буде викликано через JavaScript після завантаження сторінки
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
                    
                case 'get_folder_stats':
                    $this->ajaxGetFolderStats();
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
     * Отримання статистики по папках (з кешуванням)
     */
    private function getFolderStats(): array {
        if (!$this->db) {
            return [];
        }
        
        // Кешуємо статистику на 30 секунд
        $cacheKey = 'mail_client_folder_stats';
        if (function_exists('cache_remember')) {
            $stats = cache_remember($cacheKey, 30, function() {
                return $this->fetchFolderStats();
            });
            return $stats ?: [];
        }
        
        return $this->fetchFolderStats();
    }
    
    /**
     * Очищення кешу статистики
     */
    private function clearFolderStatsCache(): void {
        if (function_exists('cache_forget')) {
            cache_forget('mail_client_folder_stats');
        }
    }
    
    /**
     * Отримання статистики з БД
     */
    private function fetchFolderStats(): array {
        if (!$this->db) {
            return [];
        }
        
        try {
            // Оптимізований запит з індексами
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
                    try {
                        $updateStmt = $this->db->prepare("UPDATE mail_client_emails SET is_read = 1 WHERE id = ?");
                        $updateStmt->execute([$id]);
                        $email['is_read'] = 1;
                    } catch (Exception $e) {
                        error_log("Error marking email as read: " . $e->getMessage());
                        // Продовжуємо навіть якщо не вдалося позначити як прочитане
                    }
                }
                
                // Отримуємо вкладення (якщо таблиця існує)
                $email['attachments'] = [];
                try {
                    // Перевіряємо чи існує таблиця вкладень
                    $tableCheck = $this->db->query("SHOW TABLES LIKE 'mail_client_attachments'");
                    if ($tableCheck && $tableCheck->rowCount() > 0) {
                        $attachmentsStmt = $this->db->prepare("
                            SELECT id, filename, original_filename, file_path, mime_type, file_size 
                            FROM mail_client_attachments 
                            WHERE email_id = ?
                        ");
                        $attachmentsStmt->execute([$id]);
                        $email['attachments'] = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }
                } catch (Exception $e) {
                    error_log("Error getting attachments: " . $e->getMessage());
                    // Якщо помилка з вкладеннями, просто встановлюємо порожній масив
                    $email['attachments'] = [];
                }
                
                // Переконуємося, що всі поля є рядками для JSON
                $email['from'] = (string)($email['from'] ?? '');
                $email['to'] = (string)($email['to'] ?? '');
                $email['cc'] = (string)($email['cc'] ?? '');
                $email['bcc'] = (string)($email['bcc'] ?? '');
                $email['subject'] = (string)($email['subject'] ?? '');
                $email['body'] = (string)($email['body'] ?? '');
                $email['body_html'] = (string)($email['body_html'] ?? '');
                
                echo json_encode(['success' => true, 'email' => $email], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Лист не знайдено'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error getting email: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => 'Помилка отримання листа: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } catch (Error $e) {
            error_log("Fatal error getting email: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => 'Критична помилка отримання листа'
            ], JSON_UNESCAPED_UNICODE);
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
        
        $mailModule = $this->getMailModule();
        if (!$mailModule) {
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
        
        // Зберігаємо оригінальний формат для БД (з іменами)
        $toForDb = $to;
        
        // Витягуємо тільки email адреси для відправки (якщо формат "Ім'я <email>")
        $toForSend = $this->extractEmailsFromString($to);
        
        if (empty($toForSend)) {
            echo json_encode(['success' => false, 'error' => 'Невірний формат адреси отримувача'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $result = $mailModule->sendEmail($toForSend, $subject, $body, ['is_html' => $isHtml]);
            
            if ($result) {
                // Зберігаємо копію в надіслані
                if ($this->db) {
                    $stmt = $this->db->prepare("
                        INSERT INTO mail_client_emails 
                        (message_id, folder, `from`, `to`, subject, body, body_html, date_sent, date_received, is_read)
                        VALUES (?, 'sent', ?, ?, ?, ?, ?, NOW(), NOW(), 1)
                    ");
                    $settings = $mailModule->getSettings();
                    $from = $settings['from_email'] ?? '';
                    $messageId = md5($to . $subject . time());
                    $stmt->execute([
                        $messageId,
                        $from,
                        $toForDb, // Зберігаємо з іменами для відображення
                        $subject,
                        strip_tags($body),
                        $isHtml ? $body : null
                    ]);
                    
                    // Очищаємо кеш статистики
                    $this->clearFolderStatsCache();
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
            
            // Очищаємо кеш статистики
            $this->clearFolderStatsCache();
            
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
            
            // Очищаємо кеш статистики
            $this->clearFolderStatsCache();
            
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
            
            // Очищаємо кеш статистики
            $this->clearFolderStatsCache();
            
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
            
            // Очищаємо кеш статистики
            $this->clearFolderStatsCache();
            
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
        
        $mailModule = $this->getMailModule();
        if (!$mailModule || !$this->db) {
            echo json_encode(['success' => false, 'error' => 'Модуль пошти не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Отримуємо листи через POP3
            $result = $mailModule->receiveEmails(50);
            
            if ($result['success'] && !empty($result['emails'])) {
                $imported = 0;
                
                foreach ($result['emails'] as $emailData) {
                    try {
                        // Нормалізуємо message-id (видаляємо углові дужки якщо є)
                        $messageId = $emailData['message_id'] ?? '';
                        if (!empty($messageId)) {
                            $messageId = trim($messageId, '<>');
                        }
                        
                        // Якщо message-id порожній або невалідний, генеруємо на основі даних листа
                        if (empty($messageId)) {
                            // Використовуємо комбінацію from + subject + date для унікальності
                            $from = $emailData['from'] ?? '';
                            $subject = $emailData['subject'] ?? '';
                            $date = $emailData['date'] ?? '';
                            
                            // Витягуємо email з from (якщо формат "Ім'я <email>")
                            if (preg_match('/<(.+?)>/', $from, $matches)) {
                                $from = $matches[1];
                            }
                            
                            $messageId = md5(strtolower(trim($from)) . '|' . trim($subject) . '|' . trim($date));
                        }
                        
                        // Перевіряємо чи вже є такий лист по message_id
                        $checkStmt = $this->db->prepare("SELECT id FROM mail_client_emails WHERE message_id = ? LIMIT 1");
                        $checkStmt->execute([$messageId]);
                        
                        if ($checkStmt->fetch()) {
                            // Лист вже існує, пропускаємо
                            continue;
                        }
                        
                        // Додаткова перевірка: чи немає дубліката за комбінацією from + subject + date
                        // (на випадок якщо message-id змінився)
                        $from = $emailData['from'] ?? '';
                        $subject = $emailData['subject'] ?? '';
                        $date = $emailData['date'] ?? '';
                        
                        // Нормалізуємо дату для порівняння
                        $normalizedDate = null;
                        if (!empty($date)) {
                            try {
                                $normalizedDate = date('Y-m-d H:i:s', strtotime($date));
                            } catch (Exception $e) {
                                $normalizedDate = null;
                            }
                        }
                        
                        // Перевіряємо дублікат за комбінацією полів (якщо дата в межах 1 хвилини)
                        if ($normalizedDate) {
                            $dateFrom = date('Y-m-d H:i:s', strtotime($normalizedDate . ' -1 minute'));
                            $dateTo = date('Y-m-d H:i:s', strtotime($normalizedDate . ' +1 minute'));
                            
                            $duplicateStmt = $this->db->prepare("
                                SELECT id FROM mail_client_emails 
                                WHERE `from` = ? AND subject = ? 
                                AND date_received BETWEEN ? AND ?
                                LIMIT 1
                            ");
                            $duplicateStmt->execute([$from, $subject, $dateFrom, $dateTo]);
                            
                            if ($duplicateStmt->fetch()) {
                                // Знайдено дублікат, пропускаємо
                                continue;
                            }
                        }
                        // Зберігаємо лист
                        $stmt = $this->db->prepare("
                            INSERT INTO mail_client_emails 
                            (message_id, folder, `from`, `to`, cc, bcc, subject, body, body_html, date_received, is_read, headers)
                            VALUES (?, 'inbox', ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
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
                        
                        $headersJson = !empty($emailData['headers']) ? json_encode($emailData['headers'], JSON_UNESCAPED_UNICODE) : null;
                        
                        $stmt->execute([
                            $messageId,
                            $emailData['from'] ?? '',
                            $emailData['to'] ?? '',
                            $emailData['cc'] ?? '',
                            $emailData['bcc'] ?? '',
                            $emailData['subject'] ?? '',
                            $bodyText,
                            $bodyHtml ?: null,
                            $dateReceived,
                            $headersJson
                        ]);
                        
                        $emailId = $this->db->lastInsertId();
                        
                        // Зберігаємо вкладення
                        if (!empty($emailData['attachments']) && is_array($emailData['attachments'])) {
                            try {
                                $this->saveAttachments($emailId, $emailData['attachments']);
                            } catch (Exception $e) {
                                error_log("Error saving attachments for email {$emailId}: " . $e->getMessage());
                                // Продовжуємо навіть якщо вкладення не збереглися
                            }
                        }
                        
                        $imported++;
                    } catch (Exception $e) {
                        // Логуємо помилку для конкретного листа, але продовжуємо обробку інших
                        error_log("Error processing email: " . $e->getMessage());
                        error_log("Email data: " . json_encode([
                            'from' => $emailData['from'] ?? '',
                            'subject' => $emailData['subject'] ?? '',
                            'date' => $emailData['date'] ?? ''
                        ]));
                        continue; // Пропускаємо цей лист і продовжуємо з наступним
                    }
                }
                
                // Очищаємо кеш статистики після імпорту
                if ($imported > 0) {
                    $this->clearFolderStatsCache();
                }
                
                $message = $imported > 0 
                    ? "Отримано {$imported} нових листів" 
                    : "Нових листів не знайдено";
                
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'imported' => $imported
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // Якщо немає помилки, але і листів немає - це нормально
                $errorMessage = $result['message'] ?? 'Нових листів не знайдено';
                
                echo json_encode([
                    'success' => true, // Вважаємо успіхом, якщо просто немає нових листів
                    'message' => $errorMessage,
                    'imported' => 0
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
    
    /**
     * AJAX: Отримати статистику папок
     */
    private function ajaxGetFolderStats() {
        $stats = $this->getFolderStats();
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * AJAX: Скачивание вложения
     */
    private function ajaxDownloadAttachment() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id || !$this->db) {
            http_response_code(404);
            exit;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, e.id as email_id 
                FROM mail_client_attachments a
                JOIN mail_client_emails e ON a.email_id = e.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attachment || empty($attachment['file_path'])) {
                http_response_code(404);
                exit;
            }
            
            // Получаем полный путь к файлу
            $filePath = dirname(__DIR__, 3) . '/' . $attachment['file_path'];
            
            if (!file_exists($filePath) || !is_readable($filePath)) {
                http_response_code(404);
                exit;
            }
            
            // Отправляем файл
            $filename = $attachment['original_filename'] ?: $attachment['filename'];
            $mimeType = $attachment['mime_type'] ?: 'application/octet-stream';
            
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            readfile($filePath);
            exit;
        } catch (Exception $e) {
            error_log("Error downloading attachment: " . $e->getMessage());
            http_response_code(500);
            exit;
        }
    }
    
    /**
     * Витягування email адрес з рядка (формат "Ім'я <email>" або просто "email")
     * 
     * @param string $emailString
     * @return string
     */
    private function extractEmailsFromString(string $emailString): string {
        if (empty($emailString)) {
            return '';
        }
        
        // Розділяємо кілька адрес через кому
        $addresses = array_map('trim', explode(',', $emailString));
        $emails = [];
        
        foreach ($addresses as $address) {
            $address = trim($address);
            if (empty($address)) {
                continue;
            }
            
            // Якщо формат "Ім'я <email@example.com>"
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $address, $matches)) {
                $email = trim($matches[2]);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $email;
                }
            } elseif (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                // Якщо просто email
                $emails[] = $address;
            }
        }
        
        return implode(', ', $emails);
    }
}

