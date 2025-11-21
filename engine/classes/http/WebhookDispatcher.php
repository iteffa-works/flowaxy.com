<?php
/**
 * Відправник Webhooks
 * Асинхронна відправка webhooks зовнішнім сервісам
 * 
 * @package Engine\Classes\Http
 * @version 1.0.0
 */

declare(strict_types=1);

class WebhookDispatcher {
    private WebhookManager $webhookManager;
    private const TIMEOUT = 10; // Таймаут запиту в секундах
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->webhookManager = new WebhookManager();
    }
    
    /**
     * Відправка webhook для події
     * 
     * @param string $event Назва події
     * @param array $payload Дані для відправки
     * @param bool $async Асинхронна відправка (за замовчуванням true)
     * @return void
     */
    public function dispatch(string $event, array $payload = [], bool $async = true): void {
        $webhooks = $this->webhookManager->getForEvent($event);
        
        if (empty($webhooks)) {
            return;
        }
        
        foreach ($webhooks as $webhook) {
            if ($async) {
                // Асинхронна відправка (не блокує виконання)
                $this->sendAsync($webhook, $event, $payload);
            } else {
                // Синхронна відправка
                $this->send($webhook, $event, $payload);
            }
        }
    }
    
    /**
     * Відправка webhook конкретному отримувачу
     * 
     * @param array $webhook Дані webhook
     * @param string $event Назва події
     * @param array $payload Дані
     * @return bool Чи успішна відправка
     */
    public function send(array $webhook, string $event, array $payload = []): bool {
        try {
            $url = $webhook['url'];
            $secretHash = $this->getSecretHashForWebhook($webhook['id']);
            
            // Формуємо дані для відправки
            $data = [
                'event' => $event,
                'timestamp' => time(),
                'data' => $payload
            ];
            
            // Додаємо підпис, якщо є секрет
            if (!empty($secretHash)) {
                $signature = $this->generateSignature($data, $secretHash);
                $data['signature'] = $signature;
            }
            
            // Відправляємо запит
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => Json::stringify($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: Flowaxy-CMS/6.0',
                    'X-Webhook-Event: ' . $event
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $success = ($httpCode >= 200 && $httpCode < 300) && empty($error);
            
            // Оновлюємо статистику
            $this->webhookManager->updateStats($webhook['id'], $success);
            
            // Логируем ошибки
            if (!$success) {
                $logger = logger();
                if ($logger) {
                    $logger->warning("Webhook failed: {$webhook['name']}", [
                        'url' => $url,
                        'event' => $event,
                        'http_code' => $httpCode,
                        'error' => $error
                    ]);
                }
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("WebhookDispatcher::send() error: " . $e->getMessage());
            $this->webhookManager->updateStats($webhook['id'], false);
            return false;
        }
    }
    
    /**
     * Асинхронная отправка webhook
     * 
     * @param array $webhook Данные webhook
     * @param string $event Название события
     * @param array $payload Данные
     * @return void
     */
    private function sendAsync(array $webhook, string $event, array $payload = []): void {
        // Используем фоновый процесс через exec (если доступен) или просто вызываем синхронно
        // Для лучшей производительности можно использовать очереди (Redis, RabbitMQ и т.д.)
        
        // В простой реализации просто вызываем синхронно, но не ждем результата
        // В production рекомендуется использовать очередь задач
        if (function_exists('exec') && !ini_get('safe_mode')) {
            $script = __FILE__;
            $data = base64_encode(Json::stringify([
                'webhook_id' => $webhook['id'],
                'event' => $event,
                'payload' => $payload
            ]));
            
            $command = sprintf(
                'php -r "require \'%s\'; \\$data = json_decode(base64_decode(\'%s\'), true); ' .
                '\\$dispatcher = new WebhookDispatcher(); ' .
                '\\$webhook = \\$dispatcher->getWebhookManager()->get(\\$data[\'webhook_id\']); ' .
                'if (\\$webhook) { \\$dispatcher->send(\\$webhook, \\$data[\'event\'], \\$data[\'payload\']); }" > /dev/null 2>&1 &',
                $script,
                $data
            );
            
            @exec($command);
        } else {
            // Fallback: синхронная отправка
            $this->send($webhook, $event, $payload);
        }
    }
    
    /**
     * Получение хеша секрета для webhook
     * 
     * @param int $webhookId ID webhook
     * @return string|null
     */
    private function getSecretHashForWebhook(int $webhookId): ?string {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT secret FROM webhooks WHERE id = ?");
            $stmt->execute([$webhookId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['secret'] ?? null;
        } catch (Exception $e) {
            error_log("WebhookDispatcher::getSecretHashForWebhook() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Генерация подписи для webhook
     * 
     * @param array $data Данные
     * @param string $secretHash Хеш секрета
     * @return string
     */
    private function generateSignature(array $data, string $secretHash): string {
        // Используем HMAC для подписи
        $payload = Json::stringify($data);
        return 'sha256=' . hash_hmac('sha256', $payload, $secretHash);
    }
    
    /**
     * Получение WebhookManager (для внутреннего использования)
     * 
     * @return WebhookManager
     */
    public function getWebhookManager(): WebhookManager {
        return $this->webhookManager;
    }
    
    /**
     * Проверка подписи webhook
     * 
     * @param string $signature Подпись
     * @param array $data Данные
     * @param string $secretHash Хеш секрета
     * @return bool
     */
    public static function verifySignature(string $signature, array $data, string $secretHash): bool {
        $payload = Json::stringify($data);
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secretHash);
        return Hash::equals($expectedSignature, $signature);
    }
}

