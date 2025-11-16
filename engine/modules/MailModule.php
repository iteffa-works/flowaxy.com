<?php
/**
 * Модуль керування поштою
 * Підтримка SMTP, POP3, IMAP
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

class MailModule extends BaseModule {
    private $settings = [];
    
    /**
     * Ініціалізація модуля
     */
    protected function init(): void {
        $this->loadSettings();
    }
    
    /**
     * Реєстрація хуків модуля
     */
    public function registerHooks(): void {
        // Реєстрація пункту меню в адмінці
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        
        // Реєстрація сторінки адмінки
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Додавання пункту меню в адмінку
     * 
     * @param array $menu Поточне меню
     * @return array Оновлене меню
     */
    public function addAdminMenuItem(array $menu): array {
        // Знаходимо пункт "Налаштування"
        foreach ($menu as $key => $item) {
            if (isset($item['page']) && $item['page'] === 'settings' && isset($item['submenu'])) {
                // Додаємо підпункт "Пошта"
                $menu[$key]['submenu'][] = [
                    'href' => adminUrl('mail-settings'),
                    'text' => 'Налаштування пошти',
                    'icon' => 'fas fa-envelope',
                    'page' => 'mail-settings',
                    'order' => 2
                ];
                break;
            }
        }
        
        return $menu;
    }
    
    /**
     * Реєстрація маршруту адмінки
     * 
     * @param Router|null $router Роутер адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return; // Роутер ще не створено
        }
        
        require_once dirname(__DIR__) . '/skins/pages/MailSettingsPage.php';
        $router->add(['GET', 'POST'], 'mail-settings', 'MailSettingsPage');
    }
    
    /**
     * Отримання інформації про модуль
     * 
     * @return array
     */
    public function getInfo(): array {
        return [
            'name' => 'MailModule',
            'title' => 'Пошта',
            'description' => 'Керування поштовою системою',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Отримання API методів модуля
     * 
     * @return array
     */
    public function getApiMethods(): array {
        return [
            'sendEmail' => 'Відправка email через SMTP',
            'receiveEmails' => 'Отримання email через POP3/IMAP',
            'testSmtpConnection' => 'Тестування SMTP з\'єднання',
            'testPop3Connection' => 'Тестування POP3 з\'єднання',
            'testImapConnection' => 'Тестування IMAP з\'єднання',
            'getSettings' => 'Отримання налаштувань пошти',
            'saveSettings' => 'Збереження налаштувань пошти'
        ];
    }
    
    /**
     * Завантаження налаштувань
     */
    private function loadSettings(): void {
        $db = $this->getDB();
        if (!$db) {
            return;
        }
        
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'mail_%'");
            $settingsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($settingsData as $setting) {
                $key = str_replace('mail_', '', $setting['setting_key']);
                $this->settings[$key] = $setting['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Mail module: Failed to load settings: " . $e->getMessage());
        }
        
        // Значення за замовчуванням
        $defaults = [
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'pop3_host' => '',
            'pop3_port' => '995',
            'pop3_encryption' => 'ssl',
            'pop3_username' => '',
            'pop3_password' => '',
            'imap_host' => '',
            'imap_port' => '993',
            'imap_encryption' => 'ssl',
            'imap_username' => '',
            'imap_password' => '',
            'from_email' => '',
            'from_name' => '',
            'domain_mx' => 'mx.services'
        ];
        
        $this->settings = array_merge($defaults, $this->settings);
    }
    
    /**
     * Отримання налаштувань
     * 
     * @return array
     */
    public function getSettings(): array {
        return $this->settings;
    }
    
    /**
     * Збереження налаштувань
     * 
     * @param array $settings Налаштування
     * @return bool
     */
    public function saveSettings(array $settings): bool {
        $db = $this->getDB();
        if (!$db) {
            return false;
        }
        
        try {
            $db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $settingKey = 'mail_' . $key;
                $stmt = $db->prepare("
                    INSERT INTO site_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$settingKey, $value]);
            }
            
            $db->commit();
            $this->loadSettings(); // Перезавантажуємо налаштування
            cache_forget('site_settings');
            
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Mail module: Failed to save settings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Відправка email через SMTP
     * 
     * @param string $to Отримувач
     * @param string $subject Тема
     * @param string $body Тіло листа
     * @param array $options Додаткові опції
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): bool {
        // Використовуємо налаштування модуля
        $smtpHost = $this->settings['smtp_host'] ?? '';
        $smtpPort = (int)($this->settings['smtp_port'] ?? 587);
        $smtpEncryption = $this->settings['smtp_encryption'] ?? 'tls';
        $smtpUsername = $this->settings['smtp_username'] ?? '';
        $smtpPassword = $this->settings['smtp_password'] ?? '';
        $fromEmail = $options['from_email'] ?? $this->settings['from_email'] ?? '';
        $fromName = $options['from_name'] ?? $this->settings['from_name'] ?? '';
        
        // Якщо SMTP не налаштовано, використовуємо стандартний Mail клас
        if (empty($smtpHost)) {
            $mail = new \Mail();
            $mail->to($to)
                 ->subject($subject)
                 ->body($body, $options['is_html'] ?? true);
            
            if ($fromEmail) {
                $mail->from($fromEmail, $fromName);
            }
            
            return $mail->send();
        }
        
        // Використовуємо SMTP
        return $this->sendViaSmtp(
            $to,
            $subject,
            $body,
            $smtpHost,
            $smtpPort,
            $smtpEncryption,
            $smtpUsername,
            $smtpPassword,
            $fromEmail,
            $fromName,
            $options
        );
    }
    
    /**
     * Відправка через SMTP
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $host
     * @param int $port
     * @param string $encryption
     * @param string $username
     * @param string $password
     * @param string $fromEmail
     * @param string $fromName
     * @param array $options
     * @return bool
     */
    private function sendViaSmtp(
        string $to,
        string $subject,
        string $body,
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName,
        array $options = []
    ): bool {
        try {
            $socket = $this->connectSmtp($host, $port, $encryption);
            if (!$socket) {
                return false;
            }
            
            // EHLO
            fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $ehloResponse = $this->readAllSmtpResponses($socket);
            
            // STARTTLS якщо потрібно (тільки для TLS, не для SSL на порту 465)
            if ($encryption === 'tls' && $port !== 465) {
                $response = $this->sendSmtpCommand($socket, "STARTTLS");
                if (strpos($response, '220') !== false) {
                    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    if (!$crypto) {
                        fclose($socket);
                        error_log("SMTP: Failed to enable TLS encryption");
                        return false;
                    }
                    // Після STARTTLS потрібно відправити EHLO знову
                    fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
                    $ehloResponse = $this->readAllSmtpResponses($socket);
                }
            }
            
            // Аутентифікація
            if (!empty($username)) {
                $response = $this->sendSmtpCommand($socket, "AUTH LOGIN");
                // Очікуємо 334 для username
                if (strpos($response, '334') === false && strpos($response, '250') === false && strpos($response, '3') === false) {
                    // Читаємо наступну строку якщо потрібно
                    $response = fgets($socket, 515);
                }
                
                $response = $this->sendSmtpCommand($socket, base64_encode($username));
                // Очікуємо 334 для password
                if (strpos($response, '334') === false && strpos($response, '3') === false) {
                    $response = fgets($socket, 515);
                }
                
                $response = $this->sendSmtpCommand($socket, base64_encode($password));
                // Перевіряємо всі можливі успішні відповіді
                if (strpos($response, '235') === false && strpos($response, '250') === false && strpos($response, '2') === false) {
                    fclose($socket);
                    error_log("SMTP authentication failed. Response: " . trim($response));
                    return false;
                }
            }
            
            // MAIL FROM
            $this->sendSmtpCommand($socket, "MAIL FROM:<{$fromEmail}>");
            
            // RCPT TO
            $this->sendSmtpCommand($socket, "RCPT TO:<{$to}>");
            
            // DATA
            $this->sendSmtpCommand($socket, "DATA");
            
            // Headers
            $headers = [];
            $headers[] = "From: " . ($fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail);
            $headers[] = "To: <{$to}>";
            $headers[] = "Subject: " . mb_encode_mimeheader($subject, 'UTF-8', 'Q');
            $headers[] = "MIME-Version: 1.0";
            $isHtml = $options['is_html'] ?? true;
            $headers[] = "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8";
            
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
            fwrite($socket, $message);
            
            $response = fgets($socket, 515);
            
            // QUIT
            $this->sendSmtpCommand($socket, "QUIT");
            fclose($socket);
            
            return strpos($response, '250') !== false;
        } catch (Exception $e) {
            error_log("SMTP send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Підключення до SMTP сервера
     * 
     * @param string $host
     * @param int $port
     * @param string $encryption
     * @return resource|false
     */
    private function connectSmtp(string $host, int $port, string $encryption) {
        $context = stream_context_create();
        
        if ($port === 465 || $encryption === 'ssl') {
            // Для порту 465 використовуємо SSL одразу
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                ]
            ]);
            $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        } else {
            // Для інших портів - звичайне TCP з'єднання
            $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        }
        
        if (!$socket) {
            error_log("SMTP connection error: {$errstr} ({$errno})");
            return false;
        }
        
        // Встановлюємо таймаут
        stream_set_timeout($socket, 30);
        
        // Для SSL на 465 - з'єднання вже зашифроване, одразу читаємо привітання
        // Для інших - читаємо привітання сервера
        $response = fgets($socket, 515);
        if (!$response || strpos($response, '220') === false) {
            fclose($socket);
            error_log("SMTP: Invalid greeting. Response: " . trim($response));
            return false;
        }
        
        return $socket;
    }
    
    /**
     * Відправка команди SMTP
     * 
     * @param resource $socket
     * @param string $command
     * @return string
     */
    private function sendSmtpCommand($socket, string $command): string {
        fwrite($socket, $command . "\r\n");
        $response = fgets($socket, 515);
        // Читаємо мультилайнові відповіді
        while (strlen(trim($response)) > 3 && $response[3] === '-') {
            $line = fgets($socket, 515);
            if ($line === false) break;
            $response .= $line;
        }
        return $response;
    }
    
    /**
     * Читання всіх відповідей SMTP (для EHLO)
     * 
     * @param resource $socket
     * @return string
     */
    private function readAllSmtpResponses($socket): string {
        $response = '';
        $line = fgets($socket, 515);
        while ($line !== false) {
            $response .= $line;
            // Якщо третій символ не '-', це останній рядок
            if (strlen(trim($line)) <= 3 || $line[3] !== '-') {
                break;
            }
            $line = fgets($socket, 515);
        }
        return $response;
    }
    
    /**
     * Тестування SMTP з'єднання
     * 
     * @return array
     */
    public function testSmtpConnection(): array {
        $host = $this->settings['smtp_host'] ?? '';
        $port = (int)($this->settings['smtp_port'] ?? 587);
        $encryption = $this->settings['smtp_encryption'] ?? 'tls';
        $username = $this->settings['smtp_username'] ?? '';
        $password = $this->settings['smtp_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'message' => 'SMTP сервер не налаштовано'];
        }
        
        try {
            $socket = $this->connectSmtp($host, $port, $encryption);
            if (!$socket) {
                return ['success' => false, 'message' => 'Не вдалося підключитися до SMTP сервера'];
            }
            
            // Відправляємо EHLO та читаємо всі рядки відповіді
            fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $ehloResponse = $this->readAllSmtpResponses($socket);
            
            if ($encryption === 'tls' && $port !== 465) {
                $response = $this->sendSmtpCommand($socket, "STARTTLS");
                if (strpos($response, '220') !== false) {
                    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    if (!$crypto) {
                        fclose($socket);
                        return ['success' => false, 'message' => 'Помилка встановлення TLS з\'єднання'];
                    }
                    // Після STARTTLS потрібно відправити EHLO знову
                    fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
                    $ehloResponse = $this->readAllSmtpResponses($socket);
                }
            }
            
            if (!empty($username)) {
                // Перевіряємо, чи підтримує сервер AUTH (використовуємо вже прочитану відповідь EHLO)
                if (stripos($ehloResponse, 'AUTH') === false) {
                    fclose($socket);
                    return ['success' => false, 'message' => 'SMTP сервер не підтримує аутентифікацію'];
                }
                
                $response = $this->sendSmtpCommand($socket, "AUTH LOGIN");
                // Читаємо відповідь (може бути 334 або вже містити запит username)
                if (strpos($response, '334') === false && strpos($response, '250') === false && strpos($response, '3') === false) {
                    $response = fgets($socket, 515);
                }
                
                $response = $this->sendSmtpCommand($socket, base64_encode($username));
                // Очікуємо 334 для password
                if (strpos($response, '334') === false && strpos($response, '3') === false) {
                    $response = fgets($socket, 515);
                }
                
                $response = $this->sendSmtpCommand($socket, base64_encode($password));
                // Перевіряємо успішну аутентифікацію (235 = Authentication successful)
                $authSuccess = strpos($response, '235') !== false || strpos($response, '250') !== false || strpos($response, '2') === 0;
                if (!$authSuccess) {
                    $errorMsg = 'Помилка аутентифікації. Відповідь сервера: ' . trim($response);
                    fclose($socket);
                    return ['success' => false, 'message' => $errorMsg];
                }
            }
            
            $this->sendSmtpCommand($socket, "QUIT");
            fclose($socket);
            
            return ['success' => true, 'message' => 'SMTP з\'єднання успішне'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отримання email через POP3
     * 
     * @param int $limit Кількість листів
     * @return array
     */
    public function receiveEmails(int $limit = 10): array {
        $host = $this->settings['pop3_host'] ?? '';
        $port = (int)($this->settings['pop3_port'] ?? 995);
        $encryption = $this->settings['pop3_encryption'] ?? 'ssl';
        $username = $this->settings['pop3_username'] ?? '';
        $password = $this->settings['pop3_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'emails' => [], 'message' => 'POP3 сервер не налаштовано'];
        }
        
        try {
            $emails = [];
            
            if ($encryption === 'ssl') {
                $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 10);
            } else {
                $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
            }
            
            if (!$socket) {
                return ['success' => false, 'emails' => [], 'message' => "Помилка підключення: {$errstr}"];
            }
            
            $response = fgets($socket, 515);
            
            // USER
            fwrite($socket, "USER {$username}\r\n");
            $response = fgets($socket, 515);
            
            // PASS
            fwrite($socket, "PASS {$password}\r\n");
            $response = fgets($socket, 515);
            if (strpos($response, '+OK') === false) {
                fclose($socket);
                return ['success' => false, 'emails' => [], 'message' => 'Помилка аутентифікації'];
            }
            
            // LIST
            fwrite($socket, "LIST\r\n");
            $response = fgets($socket, 515);
            $messages = [];
            while (($line = fgets($socket, 515)) !== false && trim($line) !== '.') {
                if (preg_match('/^(\d+)\s+(\d+)/', $line, $matches)) {
                    $messages[] = ['num' => $matches[1], 'size' => $matches[2]];
                }
            }
            
            // Отримуємо останні листи
            $messages = array_slice(array_reverse($messages), 0, $limit);
            
            foreach ($messages as $msg) {
                fwrite($socket, "RETR {$msg['num']}\r\n");
                $emailContent = '';
                while (($line = fgets($socket, 515)) !== false && trim($line) !== '.') {
                    $emailContent .= $line;
                }
                $emails[] = $this->parseEmail($emailContent);
            }
            
            // QUIT
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return ['success' => true, 'emails' => $emails];
        } catch (Exception $e) {
            return ['success' => false, 'emails' => [], 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Парсинг email
     * 
     * @param string $emailContent
     * @return array
     */
    private function parseEmail(string $emailContent): array {
        $email = [
            'from' => '',
            'subject' => '',
            'date' => '',
            'body' => '',
            'headers' => []
        ];
        
        $parts = explode("\r\n\r\n", $emailContent, 2);
        $headers = $parts[0] ?? '';
        $body = $parts[1] ?? '';
        
        $lines = explode("\r\n", $headers);
        foreach ($lines as $line) {
            if (preg_match('/^From:\s*(.+)$/i', $line, $matches)) {
                $email['from'] = trim($matches[1]);
            } elseif (preg_match('/^Subject:\s*(.+)$/i', $line, $matches)) {
                $email['subject'] = mb_decode_mimeheader(trim($matches[1]));
            } elseif (preg_match('/^Date:\s*(.+)$/i', $line, $matches)) {
                $email['date'] = trim($matches[1]);
            }
        }
        
        $email['body'] = $body;
        
        return $email;
    }
    
    /**
     * Тестування POP3 з'єднання
     * 
     * @return array
     */
    public function testPop3Connection(): array {
        $host = $this->settings['pop3_host'] ?? '';
        $port = (int)($this->settings['pop3_port'] ?? 995);
        $encryption = $this->settings['pop3_encryption'] ?? 'ssl';
        $username = $this->settings['pop3_username'] ?? '';
        $password = $this->settings['pop3_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'message' => 'POP3 сервер не налаштовано'];
        }
        
        try {
            if ($encryption === 'ssl') {
                $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 10);
            } else {
                $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
            }
            
            if (!$socket) {
                return ['success' => false, 'message' => "Помилка підключення: {$errstr}"];
            }
            
            $response = fgets($socket, 515);
            
            fwrite($socket, "USER {$username}\r\n");
            $response = fgets($socket, 515);
            
            fwrite($socket, "PASS {$password}\r\n");
            $response = fgets($socket, 515);
            
            if (strpos($response, '+OK') === false) {
                fclose($socket);
                return ['success' => false, 'message' => 'Помилка аутентифікації'];
            }
            
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return ['success' => true, 'message' => 'POP3 з\'єднання успішне'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Тестування IMAP з'єднання
     * 
     * @return array
     */
    public function testImapConnection(): array {
        $host = $this->settings['imap_host'] ?? '';
        $port = (int)($this->settings['imap_port'] ?? 993);
        $encryption = $this->settings['imap_encryption'] ?? 'ssl';
        $username = $this->settings['imap_username'] ?? '';
        $password = $this->settings['imap_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'message' => 'IMAP сервер не налаштовано'];
        }
        
        // IMAP потребує розширення php-imap
        if (!function_exists('imap_open')) {
            return ['success' => false, 'message' => 'Розширення IMAP не встановлено'];
        }
        
        try {
            $connectionString = "{{$host}:{$port}/" . ($encryption === 'ssl' ? 'ssl' : 'notls') . "}INBOX";
            $mailbox = @imap_open($connectionString, $username, $password);
            
            if (!$mailbox) {
                return ['success' => false, 'message' => 'Помилка підключення: ' . imap_last_error()];
            }
            
            imap_close($mailbox);
            
            return ['success' => true, 'message' => 'IMAP з\'єднання успішне'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
}

/**
 * Глобальна функція для отримання екземпляра модуля Mail
 * 
 * @return MailModule
 */
// Функція оголошується тільки якщо вона ще не існує
// Це запобігає помилці повторного оголошення при кешуванні PHP
if (!function_exists('mailModule')) {
    /**
     * Глобальна функція для отримання екземпляра модуля Mail
     * 
     * @return MailModule|null
     */
    function mailModule() {
        // Переконуємося, що модуль завантажено через ModuleLoader
        if (class_exists('ModuleLoader') && method_exists('ModuleLoader', 'isModuleLoaded')) {
            if (!ModuleLoader::isModuleLoaded('MailModule')) {
                ModuleLoader::loadModule('MailModule');
            }
        }
        
        if (!class_exists('MailModule')) {
            return null;
        }
        
        return MailModule::getInstance();
    }
}

