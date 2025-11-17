<?php
/**
 * Сторінка налаштувань Mailer
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';

class MailerSettingsPage extends AdminPage {
    
    private ?Mailer $mailer = null;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування пошти - Flowaxy CMS';
        $this->templateName = 'mailer-settings';
        
        $this->setPageHeader(
            'Налаштування пошти',
            'Налаштування SMTP, POP3, IMAP серверів',
            'fas fa-envelope'
        );
        
        if (function_exists('mailer')) {
            $this->mailer = mailer();
        }
        
        if (!$this->mailer) {
            $this->setMessage('Помилка завантаження Mailer. Переконайтеся, що плагін "Mailer" активований', 'danger');
        }
    }
    
    /**
     * Отримання шляху до шаблону плагіна
     */
    protected function getTemplatePath(): string {
        return dirname(__DIR__) . '/templates/';
    }
    
    public function handle(): void {
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обробка збереження
        if ($_POST && isset($_POST['save_mailer_settings'])) {
            $this->saveSettings();
        }
        
        $settings = $this->mailer ? $this->mailer->getSettings() : [];
        
        $this->render(['settings' => $settings]);
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax(): void {
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        $action = SecurityHelper::sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
        
        if (!$this->mailer) {
            echo json_encode(['success' => false, 'message' => 'Mailer не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        switch ($action) {
            case 'test_smtp':
                echo json_encode($this->mailer->testSmtpConnection(), JSON_UNESCAPED_UNICODE);
                exit;
                
            case 'test_pop3':
                echo json_encode($this->mailer->testPop3Connection(), JSON_UNESCAPED_UNICODE);
                exit;
                
            case 'test_imap':
                echo json_encode($this->mailer->testImapConnection(), JSON_UNESCAPED_UNICODE);
                exit;
                
            case 'send_test_email':
                $to = SecurityHelper::sanitizeInput($_POST['to'] ?? '');
                if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Невірний формат email'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                $result = $this->mailer->sendEmail(
                    $to,
                    'Тестове повідомлення',
                    '<h1>Тестове повідомлення</h1><p>Це тестове повідомлення від Flowaxy CMS.</p>',
                    ['is_html' => true]
                );
                
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Повідомлення відправлено успішно' : 'Помилка відправки повідомлення'
                ], JSON_UNESCAPED_UNICODE);
                exit;
                
            case 'receive_emails':
                $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
                echo json_encode($this->mailer->receiveEmails($limit), JSON_UNESCAPED_UNICODE);
                exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Невідома дія'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Збереження налаштувань
     */
    private function saveSettings(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->mailer) {
            $this->setMessage('Помилка завантаження Mailer', 'danger');
            return;
        }
        
        $settings = $_POST['settings'] ?? [];
        $mailSettings = [];
        
        // Фільтруємо та санітизуємо налаштування
        foreach ($settings as $key => $value) {
            $mailSettings[$key] = SecurityHelper::sanitizeInput($value);
        }
        
        if ($this->mailer->saveSettings($mailSettings)) {
            $this->setMessage('Налаштування пошти успішно збережено', 'success');
        } else {
            $this->setMessage('Помилка при збереженні налаштувань пошти', 'danger');
        }
    }
}

