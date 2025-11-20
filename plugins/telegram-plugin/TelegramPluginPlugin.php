<?php
/**
 * Telegram Plugin
 * Интеграция с Telegram Bot API
 * 
 * @package TelegramPlugin
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/src/services/TelegramService.php';

class TelegramPluginPlugin extends BasePlugin {
    private TelegramService $telegramService;
    
    /**
     * Инициализация плагина
     */
    public function init(): void {
        try {
            // Всегда регистрируем меню, независимо от настроек
            $this->registerMenu();
            
            $settings = $this->getSettings();
            $botToken = $settings['bot_token'] ?? '';
            
            if (!empty($botToken)) {
                require_once __DIR__ . '/src/services/TelegramService.php';
                $this->telegramService = new TelegramService($botToken);
                
                // Регистрируем хуки для отправки уведомлений
                $this->registerEventHooks();
                
                // Регистрируем маршрут для webhook
                $this->registerWebhookRoute();
            }
        } catch (Exception $e) {
            error_log("TelegramPlugin init error: " . $e->getMessage());
        }
    }
    
    /**
     * Регистрация пункта меню
     */
    private function registerMenu(): void {
        addHook('admin_menu', function($menu) {
            $menu[] = [
                'text' => 'Telegram',
                'icon' => 'fab fa-telegram',
                'href' => UrlHelper::admin('telegram-plugin'),
                'page' => 'telegram-plugin',
                'order' => 50
            ];
            return $menu;
        }, 10);
    }
    
    /**
     * Регистрация хуков для событий
     */
    private function registerEventHooks(): void {
        $settings = $this->getSettings();
        $notifyEventsStr = $settings['notify_events'] ?? '[]';
        $notifyEvents = json_decode($notifyEventsStr, true);
        if (!is_array($notifyEvents)) {
            $notifyEvents = [];
        }
        
        // Регистрируем хуки для выбранных событий
        foreach ($notifyEvents as $event) {
            addHook($event, function($data) use ($event) {
                $this->sendNotification($event, $data);
            }, 10);
        }
    }
    
    /**
     * Регистрация маршрута для webhook
     */
    private function registerWebhookRoute(): void {
        addHook('admin_register_routes', function($router) {
            $plugin = new self();
            $router->post('telegram/webhook', function() use ($plugin) {
                $plugin->handleWebhook();
            });
        }, 10);
    }
    
    /**
     * Отправка уведомления в Telegram
     * 
     * @param string $event Событие
     * @param array $data Данные
     */
    private function sendNotification(string $event, array $data = []): void {
        try {
            $settings = $this->getSettings();
            $chatId = $settings['chat_id'] ?? '';
            
            if (empty($chatId) || !isset($this->telegramService)) {
                return;
            }
            
            $message = $this->formatNotification($event, $data);
            
            // Отправляем сообщение
            $this->telegramService->sendMessage($chatId, $message['text'], $message['keyboard'] ?? null);
        } catch (Exception $e) {
            error_log("TelegramPlugin sendNotification error: " . $e->getMessage());
        }
    }
    
    /**
     * Форматирование уведомления
     * 
     * @param string $event Событие
     * @param array $data Данные
     * @return array
     */
    private function formatNotification(string $event, array $data): array {
        $username = $data['username'] ?? 'Неизвестно';
        $plugin = $data['plugin'] ?? 'Неизвестно';
        $theme = $data['theme'] ?? 'Неизвестно';
        $message = $data['message'] ?? 'Неизвестная ошибка';
        
        $messages = [
            'user.login' => "🔐 *Пользователь вошел в систему*\n\nПользователь: {$username}",
            'user.logout' => "🚪 *Пользователь вышел из системы*\n\nПользователь: {$username}",
            'plugin.installed' => "📦 *Плагин установлен*\n\nПлагин: {$plugin}",
            'plugin.activated' => "✅ *Плагин активирован*\n\nПлагин: {$plugin}",
            'plugin.deactivated' => "❌ *Плагин деактивирован*\n\nПлагин: {$plugin}",
            'theme.activated' => "🎨 *Тема активирована*\n\nТема: {$theme}",
            'system.error' => "⚠️ *Ошибка системы*\n\n{$message}"
        ];
        
        $text = $messages[$event] ?? "📢 *Событие: {$event}*";
        
        // Добавляем кнопки для некоторых событий
        $keyboard = null;
        if (in_array($event, ['user.login', 'plugin.installed', 'theme.activated'])) {
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '👁️ в Админку', 'url' => UrlHelper::admin('dashboard')],
                        ['text' => '👤 Профиль', 'url' => UrlHelper::admin('profile')]
                    ]
                ]
            ];
        }
        
        return [
            'text' => $text,
            'keyboard' => $keyboard
        ];
    }
    
    /**
     * Обработка webhook от Telegram
     */
    public function handleWebhook(): void {
        try {
            $input = file_get_contents('php://input');
            $update = json_decode($input, true);
            
            if (!$update) {
                Response::jsonResponse(['success' => false, 'message' => 'Invalid update'], 400);
                return;
            }
            
            // Обрабатываем сообщение
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
            
            // Обрабатываем callback query (кнопки)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
            
            Response::jsonResponse(['success' => true]);
        } catch (Exception $e) {
            error_log("TelegramPlugin handleWebhook error: " . $e->getMessage());
            Response::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Обработка сообщения от пользователя
     * 
     * @param array $message Сообщение
     */
    private function handleMessage(array $message): void {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        
        if (!$chatId) {
            return;
        }
        
        // Обрабатываем команды
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $text);
        } else {
            // Простое эхо для теста
            $this->telegramService->sendMessage($chatId, "Вы написали: {$text}");
        }
    }
    
    /**
     * Обработка команды
     * 
     * @param int $chatId ID чата
     * @param string $command Команда
     */
    private function handleCommand(int $chatId, string $command): void {
        switch ($command) {
            case '/start':
                $this->telegramService->sendMessage($chatId, "👋 Добро пожаловать в Flowaxy CMS Bot!\n\nИспользуйте /help для получения списка команд.");
                break;
                
            case '/help':
                $help = "📋 *Доступные команды:*\n\n";
                $help .= "/start - Начать работу\n";
                $help .= "/help - Показать помощь\n";
                $help .= "/status - Статус системы\n";
                $help .= "/test - Тестовое сообщение";
                $this->telegramService->sendMessage($chatId, $help, ['parse_mode' => 'Markdown']);
                break;
                
            case '/status':
                $status = $this->getSystemStatus();
                $this->telegramService->sendMessage($chatId, $status, ['parse_mode' => 'Markdown']);
                break;
                
            case '/test':
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
                $this->telegramService->sendMessage(
                    $chatId, 
                    "🧪 *Тестовое сообщение*\n\nВыберите действие:", 
                    ['parse_mode' => 'Markdown', 'reply_markup' => json_encode($keyboard)]
                );
                break;
                
            default:
                $this->telegramService->sendMessage($chatId, "❓ Неизвестная команда. Используйте /help для получения списка команд.");
        }
    }
    
    /**
     * Обработка callback query (нажатие на кнопку)
     * 
     * @param array $callbackQuery Callback query
     */
    private function handleCallbackQuery(array $callbackQuery): void {
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $queryId = $callbackQuery['id'] ?? null;
        
        if (!$chatId || !$queryId) {
            return;
        }
        
        // Отвечаем на callback query
        $this->telegramService->answerCallbackQuery($queryId);
        
        // Обрабатываем действие
        switch ($data) {
            case 'approve_test':
                $this->telegramService->sendMessage($chatId, "✅ Действие одобрено!");
                break;
                
            case 'decline_test':
                $this->telegramService->sendMessage($chatId, "❌ Действие отклонено!");
                break;
                
            default:
                $this->telegramService->sendMessage($chatId, "ℹ️ Получен callback: {$data}");
        }
    }
    
    /**
     * Получение статуса системы
     * 
     * @return string
     */
    private function getSystemStatus(): string {
        try {
            $db = DatabaseHelper::getConnection();
            $status = "✅ *Статус системы*\n\n";
            
            // Статус БД
            if ($db) {
                $status .= "✅ База данных: Подключена\n";
            } else {
                $status .= "❌ База данных: Не подключена\n";
            }
            
            // Количество плагинов
            if ($db) {
                $stmt = $db->query("SELECT COUNT(*) FROM plugins WHERE is_active = 1");
                $activePlugins = $stmt->fetchColumn();
                $status .= "📦 Активных плагинов: {$activePlugins}\n";
            }
            
            // Статус Telegram бота
            $settings = $this->getSettings();
            if (!empty($settings['bot_token'])) {
                $status .= "✅ Telegram бот: Настроен\n";
            } else {
                $status .= "❌ Telegram бот: Не настроен\n";
            }
            
            return $status;
        } catch (Exception $e) {
            return "❌ Ошибка получения статуса: " . $e->getMessage();
        }
    }
    
    /**
     * Активация плагина
     */
    public function activate(): void {
        try {
            $this->init();
            
            // Устанавливаем webhook, если указан URL
            $settings = $this->getSettings();
            $webhookUrl = $settings['webhook_url'] ?? '';
            
            if (!empty($webhookUrl) && isset($this->telegramService)) {
                $this->telegramService->setWebhook($webhookUrl);
            }
        } catch (Exception $e) {
            error_log("TelegramPlugin activate error: " . $e->getMessage());
        }
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate(): void {
        try {
            if (isset($this->telegramService)) {
                $this->telegramService->deleteWebhook();
            }
        } catch (Exception $e) {
            error_log("TelegramPlugin deactivate error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение slug плагина
     * 
     * @return string
     */
    public function getSlug(): string {
        return 'telegram-plugin';
    }
}

