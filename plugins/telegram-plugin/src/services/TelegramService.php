<?php
/**
 * Сервис для работы с Telegram Bot API
 * 
 * @package TelegramPlugin\Services
 * @version 1.0.0
 */

declare(strict_types=1);

class TelegramService {
    private string $botToken;
    private string $apiUrl = 'https://api.telegram.org/bot';
    private const TIMEOUT = 10;
    private ?string $lastError = null;
    private ?int $lastErrorCode = null;
    
    /**
     * Конструктор
     * 
     * @param string $botToken Токен бота
     */
    public function __construct(string $botToken) {
        $this->botToken = $botToken;
    }
    
    /**
     * Отправка запроса к Telegram API
     * 
     * @param string $method Метод API
     * @param array $params Параметры
     * @return array|null
     */
    private function sendRequest(string $method, array $params = []): ?array {
        $url = $this->apiUrl . $this->botToken . '/' . $method;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Telegram API error: {$error}");
            return null;
        }
        
        if ($httpCode !== 200) {
            $this->lastError = "HTTP error: {$httpCode}";
            $this->lastErrorCode = $httpCode;
            error_log("Telegram API HTTP error: {$httpCode}, Response: {$response}");
            error_log("Telegram API Method: {$method}, URL: {$url}");
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['ok']) || !$data['ok']) {
            $errorDescription = $data['description'] ?? 'Unknown error';
            $errorCode = isset($data['error_code']) ? (int)$data['error_code'] : null;
            $parameters = isset($data['parameters']) ? json_encode($data['parameters']) : 'N/A';
            error_log("Telegram API error response: {$response}");
            error_log("Telegram API error - Method: {$method}, Code: " . ($errorCode ?? 'N/A') . ", Description: {$errorDescription}, Parameters: {$parameters}");
            
            // Сохраняем информацию об ошибке для последующего использования
            $this->lastError = $errorDescription;
            $this->lastErrorCode = $errorCode;
            
            if (isset($data['error_code'])) {
                error_log("Telegram API Error Code Details: {$errorCode}");
                // Коды ошибок Telegram API для более понятных сообщений
                $errorMessages = [
                    400 => 'Неверный запрос. Проверьте параметры.',
                    401 => 'Неверный Bot Token. Проверьте токен бота.',
                    403 => 'Доступ запрещен. Бот не может отправлять сообщения в этот чат.',
                    404 => 'Чат не найден. Проверьте Chat ID.',
                    429 => 'Слишком много запросов. Попробуйте позже.',
                ];
                if (isset($errorMessages[$errorCode])) {
                    error_log("Telegram API Error Message: " . $errorMessages[$errorCode]);
                }
            }
            
            return null;
        }
        
        // Очищаем предыдущую ошибку при успешном ответе
        $this->lastError = null;
        $this->lastErrorCode = null;
        
        // Возвращаем result, если он есть, иначе пустой массив (для некоторых методов API возвращает true)
        if (isset($data['result'])) {
            return is_array($data['result']) ? $data['result'] : [];
        }
        
        // Для методов, которые возвращают ok: true без result, возвращаем пустой массив
        return [];
    }
    
    /**
     * Получение последней ошибки
     * 
     * @return string|null
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }
    
    /**
     * Получение кода последней ошибки
     * 
     * @return int|null
     */
    public function getLastErrorCode(): ?int {
        return $this->lastErrorCode;
    }
    
    /**
     * Отправка сообщения
     * 
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array|null $options Дополнительные опции (parse_mode, reply_markup и т.д.)
     * @return bool
     */
    public function sendMessage($chatId, string $text, ?array $options = null): bool {
        $params = [
            'chat_id' => $chatId,
            'text' => $text
        ];
        
        if ($options) {
            $params = array_merge($params, $options);
        }
        
        $result = $this->sendRequest('sendMessage', $params);
        $success = $result !== null;
        
        // Сохраняем исходящее сообщение в историю
        $this->saveOutgoingHistory($chatId, $text, $success, $options ?? []);
        
        return $success;
    }
    
    /**
     * Сохранение исходящего сообщения в историю
     * 
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param bool $success Успешно ли отправлено
     * @param array $options Дополнительные опции
     */
    private function saveOutgoingHistory($chatId, string $text, bool $success, array $options = []): void {
        try {
            if (!class_exists('DatabaseHelper')) {
                return;
            }
            
            $db = DatabaseHelper::getConnection(false);
            if (!$db) {
                return;
            }
            
            // Проверяем существование таблицы
            $stmt = $db->query("SHOW TABLES LIKE 'telegram_history'");
            if ($stmt->rowCount() === 0) {
                // Таблица не существует, пытаемся создать
                $sqlFile = dirname(__DIR__, 2) . '/config/telegram_history.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    $db->exec($sql);
                } else {
                    return; // Таблица не создана, пропускаем сохранение
                }
            }
            
            $data = [
                'chat_id' => $chatId,
                'text' => $text,
                'direction' => 'outgoing',
                'status' => $success ? 'sent' : 'error',
                'options' => $options
            ];
            
            $rawData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            $stmt = $db->prepare("
                INSERT INTO telegram_history (
                    type, chat_id, text, raw_data, direction, status, processed_at
                ) VALUES ('message', ?, ?, ?, 'outgoing', ?, NOW())
            ");
            
            $stmt->execute([
                $chatId,
                $text,
                $rawData,
                $success ? 'sent' : 'error'
            ]);
        } catch (Exception $e) {
            error_log("TelegramService saveOutgoingHistory error: " . $e->getMessage());
        }
    }
    
    /**
     * Отправка сообщения с inline-кнопками
     * 
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array|null $keyboard Клавиатура (inline_keyboard)
     * @param string $parseMode Режим парсинга (Markdown, HTML)
     * @return bool
     */
    public function sendMessageWithKeyboard($chatId, string $text, ?array $keyboard = null, string $parseMode = 'Markdown'): bool {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        
        error_log("TelegramService: Отправка сообщения с клавиатурой. Chat ID: {$chatId}, Text length: " . strlen($text));
        
        $result = $this->sendRequest('sendMessage', $params);
        $success = $result !== null;
        
        // Сохраняем исходящее сообщение в историю
        $this->saveOutgoingHistory($chatId, $text, $success, [
            'parse_mode' => $parseMode,
            'keyboard' => $keyboard
        ]);
        
        if (!$success) {
            error_log("TelegramService: Отправка сообщения не удалась. sendRequest вернул null");
        } else {
            error_log("TelegramService: Сообщение отправлено успешно. Result: " . json_encode($result));
        }
        
        return $success;
    }
    
    /**
     * Ответ на callback query
     * 
     * @param string $callbackQueryId ID callback query
     * @param string|null $text Текст ответа
     * @param bool $showAlert Показать alert
     * @return bool
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool {
        $params = [
            'callback_query_id' => $callbackQueryId
        ];
        
        if ($text !== null) {
            $params['text'] = $text;
        }
        
        if ($showAlert) {
            $params['show_alert'] = true;
        }
        
        $result = $this->sendRequest('answerCallbackQuery', $params);
        return $result !== null;
    }
    
    /**
     * Установка webhook
     * 
     * @param string $url URL для webhook
     * @return bool
     */
    public function setWebhook(string $url): bool {
        $params = [
            'url' => $url
        ];
        
        $result = $this->sendRequest('setWebhook', $params);
        return $result !== null;
    }
    
    /**
     * Удаление webhook
     * 
     * @return bool
     */
    public function deleteWebhook(): bool {
        $result = $this->sendRequest('deleteWebhook', []);
        return $result !== null;
    }
    
    /**
     * Получение информации о боте
     * 
     * @return array|null
     */
    public function getMe(): ?array {
        return $this->sendRequest('getMe');
    }
    
    /**
     * Получение информации о webhook
     * 
     * @return array|null
     */
    public function getWebhookInfo(): ?array {
        return $this->sendRequest('getWebhookInfo');
    }
    
    /**
     * Отправка файла
     * 
     * @param int|string $chatId ID чата
     * @param string $filePath Путь к файлу
     * @param string|null $caption Подпись
     * @return bool
     */
    public function sendDocument($chatId, string $filePath, ?string $caption = null): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $url = $this->apiUrl . $this->botToken . '/sendDocument';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'document' => new CURLFile($filePath),
                'caption' => $caption ?? ''
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Telegram sendDocument HTTP error: {$httpCode}");
            return false;
        }
        
        $data = json_decode($response, true);
        return $data !== null && isset($data['ok']) && $data['ok'];
    }
}

