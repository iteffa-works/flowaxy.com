<?php
/**
 * Mailer Class
 * Professional email handler with SMTP, POP3, IMAP support
 * 
 * @package Plugins\Mailer
 * @version 1.0.0
 */

declare(strict_types=1);

class Mailer {
    private static ?self $instance = null;
    private array $settings = [];
    private bool $settingsLoaded = false;
    private ?PDO $db = null;
    
    // Константи для кешування
    private const CACHE_KEY = 'mailer_settings';
    private const CACHE_TTL = 3600; // 1 година
    
    // Константи для таймаутів
    private const SMTP_TIMEOUT = 30;
    private const POP3_TIMEOUT = 10;
    
    /**
     * Приватний конструктор (Singleton)
     */
    private function __construct() {
        // Убеждаемся, что DatabaseHelper загружен
        if (!class_exists('DatabaseHelper')) {
            $helperFile = dirname(__DIR__, 2) . '/engine/classes/helpers/DatabaseHelper.php';
            if (file_exists($helperFile)) {
                require_once $helperFile;
            }
        }
        $this->db = DatabaseHelper::getConnection();
    }
    
    /**
     * Отримання екземпляра (Singleton)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Отримання підключення до БД
     */
    private function getDB(): ?PDO {
        if ($this->db === null) {
            // Убеждаемся, что DatabaseHelper загружен
            if (!class_exists('DatabaseHelper')) {
                $helperFile = dirname(__DIR__, 2) . '/engine/classes/helpers/DatabaseHelper.php';
                if (file_exists($helperFile)) {
                    require_once $helperFile;
                }
            }
            $this->db = DatabaseHelper::getConnection();
        }
        return $this->db;
    }
    
    /**
     * Завантаження налаштувань (lazy loading з кешуванням)
     */
    private function loadSettings(): void {
        if ($this->settingsLoaded) {
            return;
        }
        
        if (function_exists('cache_remember')) {
            $this->settings = cache_remember(self::CACHE_KEY, function() {
                return $this->fetchSettings();
            }, self::CACHE_TTL);
        } else {
            $this->settings = $this->fetchSettings();
        }
        
        $this->settingsLoaded = true;
    }
    
    /**
     * Отримання налаштувань з БД (оптимізовано з prepared statement)
     */
    private function fetchSettings(): array {
        $db = $this->getDB();
        if (!$db) {
            return $this->getDefaultSettings();
        }
        
        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute(['mailer']);
            $settingsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($settingsData as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            return array_merge($this->getDefaultSettings(), $settings);
        } catch (Exception $e) {
            error_log("Mailer: Failed to load settings: " . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }
    
    /**
     * Значення за замовчуванням
     */
    private function getDefaultSettings(): array {
        return [
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
    }
    
    /**
     * Отримання налаштувань
     */
    public function getSettings(): array {
        $this->loadSettings();
        return $this->settings;
    }
    
    /**
     * Збереження налаштувань (оптимізовано з batch insert)
     */
    public function saveSettings(array $settings): bool {
        $db = $this->getDB();
        if (!$db) {
            return false;
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO plugin_settings (plugin_slug, setting_key, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            foreach ($settings as $key => $value) {
                $stmt->execute(['mailer', $key, $value]);
            }
            
            $db->commit();
            
            // Очищаємо кеш та перезавантажуємо налаштування
            if (function_exists('cache_forget')) {
                cache_forget(self::CACHE_KEY);
            }
            $this->settingsLoaded = false;
            $this->loadSettings();
            
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Mailer: Failed to save settings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Відправка email через SMTP
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): bool {
        $this->loadSettings();
        
        // Валідація email
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Mailer: Invalid recipient email: {$to}");
            return false;
        }
        
        $smtpHost = $this->settings['smtp_host'] ?? '';
        $smtpPort = (int)($this->settings['smtp_port'] ?? 587);
        $smtpEncryption = $this->settings['smtp_encryption'] ?? 'tls';
        $smtpUsername = $this->settings['smtp_username'] ?? '';
        $smtpPassword = $this->settings['smtp_password'] ?? '';
        $fromEmail = $options['from_email'] ?? $this->settings['from_email'] ?? '';
        $fromName = $options['from_name'] ?? $this->settings['from_name'] ?? '';
        
        // Валідація відправника
        if (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Mailer: Invalid sender email: {$fromEmail}");
            return false;
        }
        
        // Якщо SMTP не налаштовано, використовуємо стандартний Mail клас
        if (empty($smtpHost)) {
            return $this->sendViaDefaultMail($to, $subject, $body, $fromEmail, $fromName, $options);
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
     * Відправка через стандартний Mail клас
     */
    private function sendViaDefaultMail(
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName,
        array $options
    ): bool {
        try {
            $mail = new \Mail();
            $mail->to($to)
                 ->subject($subject)
                 ->body($body, $options['is_html'] ?? true);
            
            if ($fromEmail) {
                $mail->from($fromEmail, $fromName);
            }
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer: Default mail send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Відправка через SMTP (оптимізовано)
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
        $socket = null;
        
        try {
            $socket = $this->connectSmtp($host, $port, $encryption);
            if (!$socket) {
                return false;
            }
            
            // EHLO
            $this->sendSmtpCommand($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $ehloResponse = $this->readAllSmtpResponses($socket);
            
            // STARTTLS якщо потрібно
            if ($encryption === 'tls' && $port !== 465) {
                if (!$this->enableTls($socket)) {
                    return false;
                }
                $this->sendSmtpCommand($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $ehloResponse = $this->readAllSmtpResponses($socket);
            }
            
            // Аутентифікація
            if (!empty($username) && !$this->authenticateSmtp($socket, $username, $password, $ehloResponse)) {
                return false;
            }
            
            // Відправка листа
            return $this->sendEmailData($socket, $to, $subject, $body, $fromEmail, $fromName, $options);
            
        } catch (Exception $e) {
            error_log("Mailer SMTP error: " . $e->getMessage());
            return false;
        } finally {
            if ($socket && is_resource($socket)) {
                @fclose($socket);
            }
        }
    }
    
    /**
     * Підключення до SMTP сервера (оптимізовано)
     */
    private function connectSmtp(string $host, int $port, string $encryption) {
        $context = $this->createSslContext($port, $encryption);
        $protocol = ($port === 465 || $encryption === 'ssl') ? 'ssl' : 'tcp';
        
        $socket = @stream_socket_client(
            "{$protocol}://{$host}:{$port}",
            $errno,
            $errstr,
            self::SMTP_TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            error_log("Mailer SMTP connection error: {$errstr} ({$errno})");
            return false;
        }
        
        stream_set_timeout($socket, self::SMTP_TIMEOUT);
        
        $response = fgets($socket, 515);
        if (!$response || strpos($response, '220') === false) {
            @fclose($socket);
            error_log("Mailer SMTP: Invalid greeting. Response: " . trim($response));
            return false;
        }
        
        return $socket;
    }
    
    /**
     * Створення SSL контексту
     */
    private function createSslContext(int $port, string $encryption): array {
        if ($port === 465 || $encryption === 'ssl') {
            return stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                ]
            ]);
        }
        return stream_context_create([]);
    }
    
    /**
     * Увімкнення TLS
     */
    private function enableTls($socket): bool {
        $response = $this->sendSmtpCommand($socket, "STARTTLS");
        if (strpos($response, '220') === false) {
            return false;
        }
        
        $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) {
            error_log("Mailer: Failed to enable TLS encryption");
            return false;
        }
        
        return true;
    }
    
    /**
     * Аутентифікація SMTP
     */
    private function authenticateSmtp($socket, string $username, string $password, string $ehloResponse): bool {
        if (stripos($ehloResponse, 'AUTH') === false) {
            error_log("Mailer: SMTP server does not support authentication");
            return false;
        }
        
        $response = $this->sendSmtpCommand($socket, "AUTH LOGIN");
        if (strpos($response, '334') === false && strpos($response, '3') === false) {
            $response = fgets($socket, 515);
        }
        
        $response = $this->sendSmtpCommand($socket, base64_encode($username));
        if (strpos($response, '334') === false && strpos($response, '3') === false) {
            $response = fgets($socket, 515);
        }
        
        $response = $this->sendSmtpCommand($socket, base64_encode($password));
        $authSuccess = strpos($response, '235') !== false || 
                      strpos($response, '250') !== false || 
                      (strlen($response) > 0 && $response[0] === '2');
        
        if (!$authSuccess) {
            error_log("Mailer SMTP authentication failed. Response: " . trim($response));
            return false;
        }
        
        return true;
    }
    
    /**
     * Відправка даних email
     */
    private function sendEmailData(
        $socket,
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName,
        array $options
    ): bool {
        $this->sendSmtpCommand($socket, "MAIL FROM:<{$fromEmail}>");
        $this->sendSmtpCommand($socket, "RCPT TO:<{$to}>");
        $this->sendSmtpCommand($socket, "DATA");
        
        $headers = $this->buildEmailHeaders($to, $subject, $fromEmail, $fromName, $options);
        $message = $headers . "\r\n\r\n" . $body . "\r\n.\r\n";
        
        fwrite($socket, $message);
        $response = fgets($socket, 515);
        
        $this->sendSmtpCommand($socket, "QUIT");
        
        return strpos($response, '250') !== false;
    }
    
    /**
     * Формування заголовків email
     */
    private function buildEmailHeaders(
        string $to,
        string $subject,
        string $fromEmail,
        string $fromName,
        array $options
    ): string {
        $headers = [];
        $headers[] = "From: " . ($fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail);
        $headers[] = "To: <{$to}>";
        $headers[] = "Subject: " . mb_encode_mimeheader($subject, 'UTF-8', 'Q');
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: " . (($options['is_html'] ?? true) ? "text/html" : "text/plain") . "; charset=UTF-8";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . md5($to . $subject . time()) . "@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">";
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Відправка команди SMTP
     */
    private function sendSmtpCommand($socket, string $command): string {
        fwrite($socket, $command . "\r\n");
        $response = fgets($socket, 515);
        
        // Читаємо мультилайнові відповіді
        while ($response !== false && strlen(trim($response)) > 3 && $response[3] === '-') {
            $line = fgets($socket, 515);
            if ($line === false) break;
            $response .= $line;
        }
        
        return $response ?: '';
    }
    
    /**
     * Читання всіх відповідей SMTP
     */
    private function readAllSmtpResponses($socket): string {
        $response = '';
        $line = fgets($socket, 515);
        
        while ($line !== false) {
            $response .= $line;
            if (strlen(trim($line)) <= 3 || $line[3] !== '-') {
                break;
            }
            $line = fgets($socket, 515);
        }
        
        return $response;
    }
    
    /**
     * Тестування SMTP з'єднання
     */
    public function testSmtpConnection(): array {
        $this->loadSettings();
        
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
            
            $this->sendSmtpCommand($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $ehloResponse = $this->readAllSmtpResponses($socket);
            
            if ($encryption === 'tls' && $port !== 465) {
                if (!$this->enableTls($socket)) {
                    @fclose($socket);
                    return ['success' => false, 'message' => 'Помилка встановлення TLS з\'єднання'];
                }
                $this->sendSmtpCommand($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $ehloResponse = $this->readAllSmtpResponses($socket);
            }
            
            if (!empty($username)) {
                if (!$this->authenticateSmtp($socket, $username, $password, $ehloResponse)) {
                    @fclose($socket);
                    return ['success' => false, 'message' => 'Помилка аутентифікації'];
                }
            }
            
            $this->sendSmtpCommand($socket, "QUIT");
            @fclose($socket);
            
            return ['success' => true, 'message' => 'SMTP з\'єднання успішне'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отримання email через POP3 (оптимізовано)
     */
    public function receiveEmails(int $limit = 10): array {
        $this->loadSettings();
        
        $host = $this->settings['pop3_host'] ?? '';
        $port = (int)($this->settings['pop3_port'] ?? 995);
        $encryption = $this->settings['pop3_encryption'] ?? 'ssl';
        $username = $this->settings['pop3_username'] ?? '';
        $password = $this->settings['pop3_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'emails' => [], 'message' => 'POP3 сервер не налаштовано'];
        }
        
        $socket = null;
        
        try {
            $protocol = ($encryption === 'ssl') ? 'ssl' : 'tcp';
            $socket = @stream_socket_client(
                "{$protocol}://{$host}:{$port}",
                $errno,
                $errstr,
                self::POP3_TIMEOUT
            );
            
            if (!$socket) {
                return ['success' => false, 'emails' => [], 'message' => "Помилка підключення: {$errstr}"];
            }
            
            stream_set_timeout($socket, self::POP3_TIMEOUT);
            fgets($socket, 515); // Читаємо привітання
            
            // Аутентифікація
            fwrite($socket, "USER {$username}\r\n");
            fgets($socket, 515);
            
            fwrite($socket, "PASS {$password}\r\n");
            $response = fgets($socket, 515);
            
            if (strpos($response, '+OK') === false) {
                return ['success' => false, 'emails' => [], 'message' => 'Помилка аутентифікації'];
            }
            
            // Отримуємо список листів
            $messages = $this->getPop3MessageList($socket);
            $messages = array_slice(array_reverse($messages), 0, $limit);
            
            // Отримуємо листи
            $emails = [];
            foreach ($messages as $msg) {
                $emailContent = $this->retrievePop3Message($socket, $msg['num']);
                if ($emailContent) {
                    $emails[] = $this->parseEmail($emailContent);
                }
            }
            
            fwrite($socket, "QUIT\r\n");
            @fclose($socket);
            
            return ['success' => true, 'emails' => $emails];
        } catch (Exception $e) {
            if ($socket && is_resource($socket)) {
                @fclose($socket);
            }
            return ['success' => false, 'emails' => [], 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отримання списку листів POP3
     */
    private function getPop3MessageList($socket): array {
        fwrite($socket, "LIST\r\n");
        fgets($socket, 515); // Пропускаємо перший рядок
        
        $messages = [];
        while (($line = fgets($socket, 515)) !== false && trim($line) !== '.') {
            if (preg_match('/^(\d+)\s+(\d+)/', $line, $matches)) {
                $messages[] = ['num' => $matches[1], 'size' => (int)$matches[2]];
            }
        }
        
        return $messages;
    }
    
    /**
     * Отримання листа POP3
     */
    private function retrievePop3Message($socket, string $messageNum): string {
        fwrite($socket, "RETR {$messageNum}\r\n");
        fgets($socket, 515); // Пропускаємо перший рядок
        
        $content = '';
        while (($line = fgets($socket, 515)) !== false && trim($line) !== '.') {
            $content .= $line;
        }
        
        return $content;
    }
    
    /**
     * Парсинг email (оптимізовано)
     */
    private function parseEmail(string $emailContent): array {
        $email = [
            'from' => '',
            'to' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => '',
            'date' => '',
            'body' => '',
            'body_html' => '',
            'headers' => [],
            'attachments' => [],
            'message_id' => '',
            'in_reply_to' => '',
            'references' => ''
        ];
        
        // Розділяємо заголовки та тіло
        $parts = preg_split("/\r\n\r\n|\n\n/", $emailContent, 2);
        $headersText = $parts[0] ?? '';
        $body = $parts[1] ?? '';
        
        // Парсимо заголовки
        $headers = $this->parseHeaders($headersText);
        
        // Витягуємо дані з заголовків
        $email['from'] = $this->decodeHeader($headers['from'] ?? '');
        $email['to'] = $this->decodeHeader($headers['to'] ?? '');
        $email['cc'] = $this->decodeHeader($headers['cc'] ?? '');
        $email['bcc'] = $this->decodeHeader($headers['bcc'] ?? '');
        $email['subject'] = $this->decodeHeader($headers['subject'] ?? '');
        $email['date'] = $headers['date'] ?? '';
        $email['message_id'] = trim($headers['message-id'] ?? '', '<>');
        $email['in_reply_to'] = trim($headers['in-reply-to'] ?? '', '<>');
        $email['references'] = $headers['references'] ?? '';
        $email['headers'] = $headers;
        
        // Парсимо тіло листа
        $this->parseEmailBody($email, $body, $headers);
        
        return $email;
    }
    
    /**
     * Парсинг заголовків (оптимізовано)
     */
    private function parseHeaders(string $headersText): array {
        $headers = [];
        $lines = preg_split("/\r\n|\n/", $headersText);
        $currentHeader = null;
        
        foreach ($lines as $line) {
            if (preg_match('/^\s+/', $line) && $currentHeader !== null) {
                $headers[$currentHeader] .= ' ' . trim($line);
            } elseif (preg_match('/^([^:]+):\s*(.+)$/', $line, $matches)) {
                $currentHeader = strtolower(trim($matches[1]));
                $headers[$currentHeader] = trim($matches[2]);
            }
        }
        
        return $headers;
    }
    
    /**
     * Парсинг тіла листа
     */
    private function parseEmailBody(array &$email, string $body, array $headers): void {
        $contentType = strtolower($headers['content-type'] ?? '');
        
        if (stripos($contentType, 'multipart') !== false) {
            $this->parseMultipartBody($email, $body, $contentType);
        } else {
            $transferEncoding = strtolower($headers['content-transfer-encoding'] ?? '');
            $decodedBody = $this->decodeBody($body, $transferEncoding);
            
            if (stripos($contentType, 'text/html') !== false) {
                $email['body_html'] = $decodedBody;
                $email['body'] = strip_tags($decodedBody);
            } else {
                $email['body'] = $decodedBody;
            }
        }
        
        // Якщо тіло порожнє, використовуємо весь контент
        if (empty($email['body']) && empty($email['body_html'])) {
            $transferEncoding = strtolower($headers['content-transfer-encoding'] ?? '');
            $email['body'] = $this->decodeBody($body, $transferEncoding);
        }
    }
    
    /**
     * Парсинг multipart тіла
     */
    private function parseMultipartBody(array &$email, string $body, string $contentType): void {
        if (!preg_match('/boundary=["\']?([^"\';]+)["\']?/i', $contentType, $matches)) {
            return;
        }
        
        $boundary = trim($matches[1], '"\'');
        $parts = explode('--' . $boundary, $body);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--') {
                continue;
            }
            
            $partLines = preg_split("/\r\n\r\n|\n\n/", $part, 2);
            $partHeaders = $this->parseHeaders($partLines[0] ?? '');
            $partBody = $partLines[1] ?? '';
            
            $partContentType = strtolower($partHeaders['content-type'] ?? '');
            $partTransferEncoding = strtolower($partHeaders['content-transfer-encoding'] ?? '');
            $contentDisposition = strtolower($partHeaders['content-disposition'] ?? '');
            
            // Перевіряємо чи це вкладення
            $isAttachment = stripos($contentDisposition, 'attachment') !== false ||
                           stripos($contentDisposition, 'inline') !== false ||
                           stripos($partContentType, 'application/') !== false ||
                           stripos($partContentType, 'image/') !== false ||
                           stripos($partContentType, 'video/') !== false ||
                           stripos($partContentType, 'audio/') !== false;
            
            if ($isAttachment) {
                $attachment = $this->parseAttachment($partHeaders, $partBody, $partTransferEncoding);
                if ($attachment) {
                    $email['attachments'][] = $attachment;
                }
            } elseif (stripos($partContentType, 'text/html') !== false) {
                $email['body_html'] = $this->decodeBody($partBody, $partTransferEncoding);
            } elseif (stripos($partContentType, 'text/plain') !== false || empty($email['body'])) {
                $email['body'] = $this->decodeBody($partBody, $partTransferEncoding);
            }
        }
    }
    
    /**
     * Декодування заголовка (MIME)
     */
    private function decodeHeader(string $header): string {
        if (empty($header)) {
            return '';
        }
        
        // Декодуємо MIME encoded заголовки
        if (preg_match_all('/=\?([^?]+)\?([QB])\?([^?]+)\?=/i', $header, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $decoded = '';
            $lastPos = 0;
            
            foreach ($matches as $match) {
                $matchPos = $match[0][1];
                $matchStr = $match[0][0];
                
                $decoded .= substr($header, $lastPos, $matchPos - $lastPos);
                $charset = $match[1][0];
                $encoding = strtoupper($match[2][0]);
                $text = $match[3][0];
                
                if ($encoding === 'Q') {
                    $text = quoted_printable_decode($text);
                } elseif ($encoding === 'B') {
                    $text = base64_decode($text);
                }
                
                if (function_exists('mb_convert_encoding') && !empty($charset)) {
                    $text = @mb_convert_encoding($text, 'UTF-8', $charset);
                }
                
                $decoded .= $text;
                $lastPos = $matchPos + strlen($matchStr);
            }
            
            $decoded .= substr($header, $lastPos);
            return $decoded;
        }
        
        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($header);
        }
        
        return $header;
    }
    
    /**
     * Декодування тіла листа
     */
    private function decodeBody(string $body, string $transferEncoding = ''): string {
        if (empty($body)) {
            return '';
        }
        
        $body = trim($body);
        $encoding = strtolower(trim($transferEncoding));
        
        if ($encoding === 'base64') {
            $decoded = base64_decode($body, true);
            if ($decoded !== false) {
                return $decoded;
            }
        } elseif ($encoding === 'quoted-printable' || $encoding === 'qp') {
            return quoted_printable_decode($body);
        }
        
        // Автоматичне визначення Base64
        if (empty($transferEncoding)) {
            $trimmed = trim($body);
            if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $trimmed) && strlen($trimmed) % 4 === 0 && strlen($trimmed) > 50) {
                $decoded = base64_decode($trimmed, true);
                if ($decoded !== false && mb_check_encoding($decoded, 'UTF-8')) {
                    return $decoded;
                }
            }
        }
        
        return $body;
    }
    
    /**
     * Парсинг вкладення
     */
    private function parseAttachment(array $headers, string $body, string $transferEncoding): ?array {
        $contentType = $headers['content-type'] ?? '';
        $contentDisposition = $headers['content-disposition'] ?? '';
        $contentId = $headers['content-id'] ?? '';
        
        // Витягуємо ім'я файлу
        $filename = '';
        if (preg_match('/filename=["\']?([^"\';]+)["\']?/i', $contentDisposition, $matches)) {
            $filename = trim($matches[1], ' "\'');
        } elseif (preg_match('/name=["\']?([^"\';]+)["\']?/i', $contentType, $matches)) {
            $filename = trim($matches[1], ' "\'');
        }
        
        if (!empty($filename)) {
            $filename = $this->decodeHeader($filename);
        }
        
        if (empty($filename)) {
            $extension = $this->getExtensionFromMimeType($contentType);
            $filename = 'attachment_' . time() . '.' . $extension;
        }
        
        $decodedBody = $this->decodeBody($body, $transferEncoding);
        $mimeType = $this->extractMimeType($contentType);
        
        return [
            'filename' => $filename,
            'mime_type' => $mimeType,
            'content' => $decodedBody,
            'size' => strlen($decodedBody),
            'content_id' => trim($contentId, '<>'),
            'content_disposition' => $contentDisposition
        ];
    }
    
    /**
     * Отримання розширення з MIME типу
     */
    private function getExtensionFromMimeType(string $contentType): string {
        $extensions = [
            'pdf' => 'pdf',
            'jpeg' => 'jpg',
            'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'zip' => 'zip',
            'msword' => 'doc',
            'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'vnd.ms-excel' => 'xls',
            'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
        ];
        
        if (preg_match('/([^\/]+)$/', $contentType, $matches)) {
            $mimeType = strtolower(trim($matches[1]));
            return $extensions[$mimeType] ?? 'bin';
        }
        
        return 'bin';
    }
    
    /**
     * Витягування MIME типу
     */
    private function extractMimeType(string $contentType): string {
        if (preg_match('/^([^;]+)/', $contentType, $matches)) {
            return trim($matches[1]);
        }
        return 'application/octet-stream';
    }
    
    /**
     * Тестування POP3 з'єднання
     */
    public function testPop3Connection(): array {
        $this->loadSettings();
        
        $host = $this->settings['pop3_host'] ?? '';
        $port = (int)($this->settings['pop3_port'] ?? 995);
        $encryption = $this->settings['pop3_encryption'] ?? 'ssl';
        $username = $this->settings['pop3_username'] ?? '';
        $password = $this->settings['pop3_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'message' => 'POP3 сервер не налаштовано'];
        }
        
        $socket = null;
        
        try {
            $protocol = ($encryption === 'ssl') ? 'ssl' : 'tcp';
            $socket = @stream_socket_client(
                "{$protocol}://{$host}:{$port}",
                $errno,
                $errstr,
                self::POP3_TIMEOUT
            );
            
            if (!$socket) {
                return ['success' => false, 'message' => "Помилка підключення: {$errstr}"];
            }
            
            stream_set_timeout($socket, self::POP3_TIMEOUT);
            fgets($socket, 515);
            
            fwrite($socket, "USER {$username}\r\n");
            fgets($socket, 515);
            
            fwrite($socket, "PASS {$password}\r\n");
            $response = fgets($socket, 515);
            
            if (strpos($response, '+OK') === false) {
                return ['success' => false, 'message' => 'Помилка аутентифікації'];
            }
            
            fwrite($socket, "QUIT\r\n");
            @fclose($socket);
            
            return ['success' => true, 'message' => 'POP3 з\'єднання успішне'];
        } catch (Exception $e) {
            if ($socket && is_resource($socket)) {
                @fclose($socket);
            }
            return ['success' => false, 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Тестування IMAP з'єднання
     */
    public function testImapConnection(): array {
        $this->loadSettings();
        
        $host = $this->settings['imap_host'] ?? '';
        $port = (int)($this->settings['imap_port'] ?? 993);
        $encryption = $this->settings['imap_encryption'] ?? 'ssl';
        $username = $this->settings['imap_username'] ?? '';
        $password = $this->settings['imap_password'] ?? '';
        
        if (empty($host)) {
            return ['success' => false, 'message' => 'IMAP сервер не налаштовано'];
        }
        
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
 * Глобальна функція для отримання екземпляра Mailer
 * 
 * @return Mailer|null
 */
if (!function_exists('mailer')) {
    function mailer(): ?Mailer {
        if (!function_exists('pluginManager')) {
            return null;
        }
        
        $pluginManager = pluginManager();
        if (!$pluginManager) {
            return null;
        }
        
        $plugin = $pluginManager->getPlugin('mailer');
        if (!$plugin || !method_exists($plugin, 'getMailer')) {
            return null;
        }
        
        return $plugin->getMailer();
    }
}

/**
 * Зворотна сумісність з mailModule()
 */
if (!function_exists('mailModule')) {
    function mailModule() {
        return mailer();
    }
}

