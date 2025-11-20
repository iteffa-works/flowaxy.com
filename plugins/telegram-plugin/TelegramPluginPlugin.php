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
    private static bool $initialized = false;
    
    /**
     * Инициализация плагина
     */
    public function init(): void {
        // Предотвращаем двойную инициализацию
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;
        
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
        // Используем статическую переменную, чтобы не регистрировать дважды
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        
        addHook('admin_menu', function($menu) {
            // Проверяем, не добавлено ли уже меню Telegram
            foreach ($menu as $item) {
                if (isset($item['page']) && $item['page'] === 'telegram-plugin') {
                    return $menu; // Меню уже добавлено
                }
            }
            
            $menu[] = [
                'text' => 'Telegram',
                'icon' => 'fab fa-telegram',
                'href' => UrlHelper::admin('telegram-plugin'),
                'page' => 'telegram-plugin',
                'order' => 50,
                'submenu' => [
                    [
                        'text' => 'Настройки',
                        'href' => UrlHelper::admin('telegram-plugin'),
                        'page' => 'telegram-plugin'
                    ],
                    [
                        'text' => 'История',
                        'href' => UrlHelper::admin('telegram-history'),
                        'page' => 'telegram-history'
                    ]
                ]
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
            
            // Сохраняем входящее обновление в историю
            $this->saveHistory($update, 'incoming');
            
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
     * Сохранение истории взаимодействий с Telegram
     * 
     * @param array $data Данные обновления или сообщения
     * @param string $direction Направление: 'incoming' или 'outgoing'
     * @param string $type Тип события
     * @param string|null $status Статус
     * @param string|null $errorMessage Сообщение об ошибке
     */
    private function saveHistory(array $data, string $direction = 'incoming', string $type = 'message', ?string $status = null, ?string $errorMessage = null): void {
        try {
            $db = DatabaseHelper::getConnection(false);
            if (!$db) {
                return;
            }
            
            // Определяем тип события
            if (isset($data['message'])) {
                $type = 'message';
                $message = $data['message'];
            } elseif (isset($data['callback_query'])) {
                $type = 'callback_query';
                $message = $data['callback_query'];
            } elseif (isset($data['text'])) {
                // Исходящее сообщение
                $type = 'message';
                $message = $data;
            } else {
                $type = 'unknown';
                $message = $data;
            }
            
            $updateId = $data['update_id'] ?? null;
            $chatId = $message['chat']['id'] ?? $message['from']['id'] ?? $data['chat_id'] ?? null;
            $userId = $message['from']['id'] ?? $data['user_id'] ?? null;
            $username = $message['from']['username'] ?? $data['username'] ?? null;
            $firstName = $message['from']['first_name'] ?? $data['first_name'] ?? null;
            $lastName = $message['from']['last_name'] ?? $data['last_name'] ?? null;
            $text = $message['text'] ?? $data['text'] ?? null;
            $callbackData = $message['data'] ?? $data['callback_data'] ?? null;
            $rawData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            $stmt = $db->prepare("
                INSERT INTO telegram_history (
                    update_id, type, chat_id, user_id, username, first_name, last_name,
                    text, callback_data, raw_data, direction, status, error_message, processed_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $updateId,
                $type,
                $chatId,
                $userId,
                $username,
                $firstName,
                $lastName,
                $text,
                $callbackData,
                $rawData,
                $direction,
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            error_log("TelegramPlugin saveHistory error: " . $e->getMessage());
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
            // Создаем таблицу истории при активации
            $this->createHistoryTable();
            
            // Инициализируем, если еще не инициализирован
            if (!self::$initialized) {
                $this->init();
            }
            
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
     * Создание таблицы истории
     */
    private function createHistoryTable(): void {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                error_log("TelegramPlugin: Не удалось подключиться к БД для создания таблицы");
                return;
            }
            
            // Проверяем, существует ли таблица
            $stmt = $db->query("SHOW TABLES LIKE 'telegram_history'");
            if ($stmt->rowCount() > 0) {
                error_log("TelegramPlugin: Таблица telegram_history уже существует");
                return;
            }
            
            // Читаем SQL файл
            $sqlFile = __DIR__ . '/db/telegram_history.sql';
            if (!file_exists($sqlFile)) {
                // Пробуем альтернативный путь
                $sqlFile = __DIR__ . '/config/telegram_history.sql';
            }
            
            if (!file_exists($sqlFile)) {
                error_log("TelegramPlugin: SQL файл не найден: {$sqlFile}");
                return;
            }
            
            $sql = file_get_contents($sqlFile);
            if (empty($sql)) {
                error_log("TelegramPlugin: SQL файл пуст: {$sqlFile}");
                return;
            }
            
            // Выполняем SQL
            $db->exec($sql);
            error_log("TelegramPlugin: Таблица telegram_history успешно создана");
        } catch (Exception $e) {
            error_log("TelegramPlugin: Ошибка создания таблицы telegram_history: " . $e->getMessage());
            error_log("TelegramPlugin: Trace: " . $e->getTraceAsString());
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
            
            // НЕ удаляем таблицу при деактивации - данные могут понадобиться
            // Таблица будет удалена только при полном удалении плагина
        } catch (Exception $e) {
            error_log("TelegramPlugin deactivate error: " . $e->getMessage());
        }
    }
    
    /**
     * Удаление плагина (вызывается перед удалением)
     */
    public function uninstall(): void {
        try {
            // Удаляем таблицу истории при удалении плагина
            $this->dropHistoryTable();
        } catch (Exception $e) {
            error_log("TelegramPlugin uninstall error: " . $e->getMessage());
        }
    }
    
    /**
     * Удаление таблицы истории
     */
    private function dropHistoryTable(): void {
        try {
            $db = DatabaseHelper::getConnection();
            if (!$db) {
                error_log("TelegramPlugin: Не удалось подключиться к БД для удаления таблицы");
                return;
            }
            
            // Проверяем, существует ли таблица
            $stmt = $db->query("SHOW TABLES LIKE 'telegram_history'");
            if ($stmt->rowCount() === 0) {
                error_log("TelegramPlugin: Таблица telegram_history не существует");
                return;
            }
            
            // Удаляем таблицу
            $db->exec("DROP TABLE IF EXISTS `telegram_history`");
            error_log("TelegramPlugin: Таблица telegram_history успешно удалена");
        } catch (Exception $e) {
            error_log("TelegramPlugin: Ошибка удаления таблицы telegram_history: " . $e->getMessage());
            error_log("TelegramPlugin: Trace: " . $e->getTraceAsString());
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

