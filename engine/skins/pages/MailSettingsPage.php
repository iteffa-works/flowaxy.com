<?php
/**
 * Сторінка налаштувань пошти
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class MailSettingsPage extends AdminPage {
    
    private $mailModule;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування пошти - Flowaxy CMS';
        $this->templateName = 'mail-settings';
        
        $this->setPageHeader(
            'Налаштування пошти',
            'Налаштування SMTP, POP3, IMAP серверів',
            'fas fa-envelope'
        );
        
        // Завантажуємо модуль Mail
        if (function_exists('mailModule')) {
            $this->mailModule = mailModule();
        } else {
            $this->mailModule = ModuleLoader::loadModule('MailModule');
        }
        
        if (!$this->mailModule) {
            $this->setMessage('Помилка завантаження модуля пошти', 'danger');
        }
    }
    
    public function handle() {
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обробка збереження
        if ($_POST && isset($_POST['save_mail_settings'])) {
            $this->saveSettings();
        }
        
        // Отримання налаштувань
        $settings = $this->mailModule ? $this->mailModule->getSettings() : [];
        
        // Рендеримо сторінку
        $this->render([
            'settings' => $settings
        ]);
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax() {
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        $action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
        
        if (!$this->mailModule) {
            echo json_encode(['success' => false, 'message' => 'Модуль пошти не завантажено']);
            exit;
        }
        
        switch ($action) {
            case 'test_smtp':
                $result = $this->mailModule->testSmtpConnection();
                echo json_encode($result);
                exit;
                
            case 'test_pop3':
                $result = $this->mailModule->testPop3Connection();
                echo json_encode($result);
                exit;
                
            case 'test_imap':
                $result = $this->mailModule->testImapConnection();
                echo json_encode($result);
                exit;
                
            case 'send_test_email':
                $to = sanitizeInput($_POST['to'] ?? '');
                if (empty($to)) {
                    echo json_encode(['success' => false, 'message' => 'Вкажіть email отримувача']);
                    exit;
                }
                
                $result = $this->mailModule->sendEmail(
                    $to,
                    'Тестове повідомлення',
                    '<h1>Тестове повідомлення</h1><p>Це тестове повідомлення від Flowaxy CMS.</p>',
                    ['is_html' => true]
                );
                
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Повідомлення відправлено успішно' : 'Помилка відправки повідомлення'
                ]);
                exit;
                
            case 'receive_emails':
                $result = $this->mailModule->receiveEmails(5);
                echo json_encode($result);
                exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Невідома дія']);
        exit;
    }
    
    /**
     * Збереження налаштувань
     */
    private function saveSettings() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        if (!$this->mailModule) {
            $this->setMessage('Помилка завантаження модуля пошти', 'danger');
            return;
        }
        
        $settings = $_POST['settings'] ?? [];
        
        // Фільтруємо тільки mail_ налаштування
        $mailSettings = [];
        foreach ($settings as $key => $value) {
            if (strpos($key, 'mail_') === 0) {
                $mailSettings[str_replace('mail_', '', $key)] = sanitizeInput($value);
            }
        }
        
        if ($this->mailModule->saveSettings($mailSettings)) {
            $this->setMessage('Налаштування пошти успішно збережено', 'success');
        } else {
            $this->setMessage('Помилка при збереженні налаштувань пошти', 'danger');
        }
    }
}

