<?php
/**
 * Административная страница для настройки Telegram плагина
 */

require_once dirname(__DIR__, 5) . '/engine/skins/includes/AdminPage.php';

class TelegramPluginAdminPage extends AdminPage {
    private TelegramService $telegramService;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Настройки Telegram - Flowaxy CMS';
        $this->templateName = 'telegram-plugin';
        
        // Инициализируем Telegram сервис, если есть токен
        $settings = $this->getPluginSettings();
        $botToken = $settings['bot_token'] ?? '';
        
        if (!empty($botToken)) {
            $telegramServicePath = dirname(__DIR__, 2) . '/services/TelegramService.php';
            if (file_exists($telegramServicePath)) {
                require_once $telegramServicePath;
                $this->telegramService = new TelegramService($botToken);
            }
        }
        
        $this->setPageHeader(
            'Настройки Telegram',
            'Настройка интеграции с Telegram Bot API',
            'fab fa-telegram',
            ''
        );
    }
    
    public function handle() {
        // Обработка AJAX запросов
        if ($this->isAjaxRequest()) {
            $this->handleAjax();
            return;
        }
        
        // Обработка сохранения настроек
        if ($_POST) {
            $this->handleSave();
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
            case 'test_message':
                $this->handleTestMessage();
                break;
            case 'get_bot_info':
                $this->handleGetBotInfo();
                break;
            case 'set_webhook':
                $this->handleSetWebhook();
                break;
            case 'delete_webhook':
                $this->handleDeleteWebhook();
                break;
            default:
                Response::jsonResponse(['success' => false, 'message' => 'Неизвестное действие'], 400);
        }
    }
    
    /**
     * Обработка сохранения настроек
     */
    private function handleSave(): void {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Ошибка безопасности', 'danger');
            return;
        }
        
        $settings = [
            'bot_token' => $this->post('bot_token', ''),
            'chat_id' => $this->post('chat_id', ''),
            'webhook_url' => $this->post('webhook_url', ''),
            'notify_events' => json_encode($this->post('notify_events', []))
        ];
        
        try {
            $this->savePluginSettings($settings);
            $this->setMessage('Настройки успешно сохранены', 'success');
            
            // Перенаправляем для обновления страницы
            $this->redirect('telegram-plugin');
        } catch (Exception $e) {
            $this->setMessage('Ошибка сохранения настроек: ' . $e->getMessage(), 'danger');
        }
    }
    
    /**
     * Отправка тестового сообщения
     */
    private function handleTestMessage(): void {
        // Проверка CSRF токена
        if (!$this->verifyCsrf()) {
            Response::jsonResponse([
                'success' => false,
                'message' => 'Ошибка безопасности (CSRF токен неверный)'
            ], 403);
            return;
        }
        
        $settings = $this->getPluginSettings();
        $botToken = $settings['bot_token'] ?? '';
        $chatId = $settings['chat_id'] ?? '';
        
        if (empty($botToken) || empty($chatId)) {
            Response::jsonResponse([
                'success' => false,
                'message' => 'Укажите Bot Token и Chat ID'
            ], 400);
            return;
        }
        
        try {
            if (!isset($this->telegramService)) {
                require_once dirname(__DIR__, 2) . '/services/TelegramService.php';
                $this->telegramService = new TelegramService($botToken);
            }
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Одобрить', 'callback_data' => 'approve_test'],
                        ['text' => '❌ Отклонить', 'callback_data' => 'decline_test']
                    ],
                    [
                        ['text' => '👁️ в Админку', 'url' => UrlHelper::admin('dashboard')],
                        ['text' => '👤 Профиль', 'url' => UrlHelper::admin('profile')]
                    ]
                ]
            ];
            
            error_log("TelegramPlugin: Отправка тестового сообщения. Chat ID: {$chatId}");
            
            $success = $this->telegramService->sendMessageWithKeyboard(
                $chatId,
                "🧪 *Тестовое сообщение из Flowaxy CMS*\n\nЭто тестовое сообщение для проверки интеграции с Telegram.",
                $keyboard,
                'Markdown'
            );
            
            if ($success) {
                error_log("TelegramPlugin: Тестовое сообщение отправлено успешно");
                Response::jsonResponse([
                    'success' => true, 
                    'message' => 'Сообщение отправлено успешно! Проверьте Telegram.'
                ]);
            } else {
                error_log("TelegramPlugin: Ошибка отправки тестового сообщения - метод вернул false");
                
                // Получаем детали ошибки из TelegramService
                $lastError = $this->telegramService->getLastError();
                $lastErrorCode = $this->telegramService->getLastErrorCode();
                
                // Формируем детальное сообщение об ошибке
                $errorMessage = 'Ошибка отправки сообщения. ';
                
                if ($lastErrorCode) {
                    $errorMessages = [
                        400 => 'Неверный запрос. Проверьте параметры сообщения.',
                        401 => 'Неверный Bot Token. Проверьте токен бота в настройках.',
                        403 => 'Доступ запрещен. Бот не может отправлять сообщения в этот чат. Начните диалог с ботом через /start в Telegram.',
                        404 => 'Чат не найден. Проверьте Chat ID.',
                        429 => 'Слишком много запросов. Попробуйте позже.',
                    ];
                    
                    if (isset($errorMessages[$lastErrorCode])) {
                        $errorMessage = $errorMessages[$lastErrorCode];
                    } else {
                        $errorMessage .= "Код ошибки: {$lastErrorCode}. ";
                    }
                    
                    if ($lastError) {
                        $errorMessage .= "Описание: {$lastError}";
                    }
                } else {
                    $errorMessage .= 'Возможные причины: ';
                    $errorMessage .= '1) Неверный Bot Token - проверьте токен в настройках бота; ';
                    $errorMessage .= '2) Неверный Chat ID - убедитесь, что указан правильный ID чата; ';
                    $errorMessage .= '3) Бот не может отправлять сообщения - начните диалог с ботом через /start в Telegram; ';
                    $errorMessage .= '4) Проблемы с интернет-соединением. Проверьте логи системы для подробностей.';
                }
                
                Response::jsonResponse([
                    'success' => false, 
                    'message' => $errorMessage,
                    'error_code' => $lastErrorCode,
                    'error_description' => $lastError
                ], 500);
            }
        } catch (Exception $e) {
            error_log("TelegramPlugin: Исключение при отправке тестового сообщения: " . $e->getMessage());
            error_log("TelegramPlugin: Trace: " . $e->getTraceAsString());
            Response::jsonResponse([
                'success' => false, 
                'message' => 'Ошибка: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * Получение информации о боте
     */
    private function handleGetBotInfo(): void {
        $settings = $this->getPluginSettings();
        $botToken = $settings['bot_token'] ?? '';
        
        if (empty($botToken)) {
            Response::jsonResponse([
                'success' => false,
                'message' => 'Укажите Bot Token'
            ], 400);
            return;
        }
        
        try {
            if (!isset($this->telegramService)) {
                require_once dirname(__DIR__, 2) . '/services/TelegramService.php';
                $this->telegramService = new TelegramService($botToken);
            }
            
            $botInfo = $this->telegramService->getMe();
            
            if ($botInfo) {
                Response::jsonResponse(['success' => true, 'data' => $botInfo]);
            } else {
                Response::jsonResponse(['success' => false, 'message' => 'Не удалось получить информацию о боте'], 500);
            }
        } catch (Exception $e) {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Установка webhook
     */
    private function handleSetWebhook(): void {
        $settings = $this->getPluginSettings();
        $botToken = $settings['bot_token'] ?? '';
        $webhookUrl = $settings['webhook_url'] ?? '';
        
        if (empty($botToken) || empty($webhookUrl)) {
            Response::jsonResponse([
                'success' => false,
                'message' => 'Укажите Bot Token и Webhook URL'
            ], 400);
            return;
        }
        
        try {
            if (!isset($this->telegramService)) {
                require_once dirname(__DIR__, 2) . '/services/TelegramService.php';
                $this->telegramService = new TelegramService($botToken);
            }
            
            $success = $this->telegramService->setWebhook($webhookUrl);
            
            if ($success) {
                Response::jsonResponse(['success' => true, 'message' => 'Webhook установлен']);
            } else {
                Response::jsonResponse(['success' => false, 'message' => 'Ошибка установки webhook'], 500);
            }
        } catch (Exception $e) {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Удаление webhook
     */
    private function handleDeleteWebhook(): void {
        $settings = $this->getPluginSettings();
        $botToken = $settings['bot_token'] ?? '';
        
        if (empty($botToken)) {
            Response::jsonResponse([
                'success' => false,
                'message' => 'Укажите Bot Token'
            ], 400);
            return;
        }
        
        try {
            if (!isset($this->telegramService)) {
                require_once dirname(__DIR__, 2) . '/services/TelegramService.php';
                $this->telegramService = new TelegramService($botToken);
            }
            
            $success = $this->telegramService->deleteWebhook();
            
            if ($success) {
                Response::jsonResponse(['success' => true, 'message' => 'Webhook удален']);
            } else {
                Response::jsonResponse(['success' => false, 'message' => 'Ошибка удаления webhook'], 500);
            }
        } catch (Exception $e) {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Получение настроек плагина
     */
    private function getPluginSettings(): array {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                return [];
            }
            
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute(['telegram-plugin']);
            
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log("TelegramPluginAdminPage getPluginSettings error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Сохранение настроек плагина
     */
    private function savePluginSettings(array $settings): void {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                throw new Exception('Database connection failed');
            }
            
            $db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO plugin_settings (plugin_slug, setting_key, setting_value)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute(['telegram-plugin', $key, $value, $value]);
            }
            
            $db->commit();
            
            // Очищаем кеш
            if (function_exists('cache_forget')) {
                cache_forget('plugin_settings_telegram-plugin');
            }
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
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
        $settings = $this->getPluginSettings();
        
        $notifyEvents = [];
        if (!empty($settings['notify_events'])) {
            $notifyEvents = json_decode($settings['notify_events'], true) ?? [];
        }
        
        return array_merge($parentData, [
            'settings' => $settings,
            'notifyEvents' => $notifyEvents,
            'botInfo' => null,
            'webhookInfo' => null
        ]);
    }
}
