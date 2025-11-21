<?php
/**
 * Сторінка управління Webhooks
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class WebhooksPage extends AdminPage {
    private WebhookManager $webhookManager;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Webhooks - Flowaxy CMS';
        $this->templateName = 'webhooks';
        $this->webhookManager = new WebhookManager();
        
        // Реєструємо модальне вікно створення webhook
        $this->registerModal('createWebhookModal', [
            'title' => 'Створити Webhook',
            'type' => 'form',
            'action' => 'create_webhook',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Назва',
                    'placeholder' => 'Наприклад: Сповіщення в Telegram',
                    'required' => true
                ],
                [
                    'type' => 'url',
                    'name' => 'url',
                    'label' => 'URL для відправки',
                    'placeholder' => 'https://example.com/webhook',
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'name' => 'events',
                    'label' => 'Події (по одному на рядок)',
                    'placeholder' => 'plugin.installed\nplugin.activated\ntheme.changed',
                    'help' => 'Залиште порожнім для відстеження всіх подій. Доступні події: plugin.installed, plugin.activated, plugin.deactivated, plugin.deleted, theme.activated, theme.deleted, user.created, user.updated, system.updated'
                ]
            ],
            'buttons' => [
                [
                    'text' => 'Скасувати',
                    'type' => 'secondary',
                    'action' => 'close'
                ],
                [
                    'text' => 'Створити',
                    'type' => 'primary',
                    'icon' => 'plus',
                    'action' => 'submit'
                ]
            ]
        ]);
        
        // Реєструємо обробник створення webhook
        $this->registerModalHandler('createWebhookModal', 'create_webhook', [$this, 'handleCreateWebhook']);
        
        // Кнопка створення webhook
        $headerButtons = $this->createButtonGroup([
            [
                'text' => 'Створити Webhook',
                'type' => 'primary',
                'options' => [
                    'icon' => 'plus',
                    'attributes' => [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#createWebhookModal',
                        'onclick' => 'window.ModalHandler && window.ModalHandler.show("createWebhookModal")'
                    ]
                ]
            ]
        ]);
        
        $this->setPageHeader(
            'Webhooks',
            'Управління webhooks для відправки сповіщень зовнішнім сервісам',
            'fas fa-paper-plane',
            $headerButtons
        );
    }
    
    public function handle() {
        // Обробка AJAX запитів
        if ($this->isAjaxRequest()) {
            $this->handleAjax();
            return;
        }
        
        // Обробка дій
        if ($_POST) {
            $this->handleAction();
        }
        
        // Рендерим страницу с модальным окном
        $this->render([
            'createModalHtml' => $this->renderModal('createWebhookModal')
        ]);
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax(): void {
        $action = $this->post('action', '');
        
        switch ($action) {
            case 'delete_webhook':
                $this->handleDeleteWebhook();
                break;
            case 'toggle_webhook':
                $this->handleToggleWebhook();
                break;
            case 'test_webhook':
                $this->handleTestWebhook();
                break;
            default:
                Response::jsonResponse(['success' => false, 'message' => 'Неизвестное действие'], 400);
        }
    }
    
    /**
     * Обработка действий
     */
    private function handleAction(): void {
        // Если это AJAX запрос, обрабатываем через handleAjax
        if ($this->isAjaxRequest()) {
            $this->handleAjax();
            return;
        }
        
        $action = $this->post('action', '');
        
        if ($action === 'delete' && $this->post('id')) {
            $id = (int)$this->post('id', 0);
            if ($id > 0 && $this->webhookManager->delete($id)) {
                $this->setMessage('Webhook успішно видалено', 'success');
            } else {
                $this->setMessage('Помилка видалення webhook', 'danger');
            }
            // Редирект после удаления для предотвращения повторного выполнения
            $this->redirect('webhooks');
            exit;
        }
    }
    
    /**
     * Обработчик создания webhook
     */
    public function handleCreateWebhook(): array {
        $request = Request::getInstance();
        
        if (!SecurityHelper::verifyCsrfToken($request->post('csrf_token', ''))) {
            return [
                'success' => false,
                'message' => 'Ошибка безопасности. Попробуйте еще раз.'
            ];
        }
        
        $name = SecurityHelper::sanitizeInput($request->post('name', ''));
        $url = $request->post('url', '');
        $eventsStr = $request->post('events', '');
        
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Укажите название webhook'
            ];
        }
        
        if (empty($url) || !Security::isValidUrl($url)) {
            return [
                'success' => false,
                'message' => 'Укажите корректный URL'
            ];
        }
        
        // Парсим события
        $events = [];
        if (!empty($eventsStr)) {
            $lines = explode("\n", $eventsStr);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $events[] = $line;
                }
            }
        }
        
        try {
            $result = $this->webhookManager->create($name, $url, $events);
            
            return [
                'success' => true,
                'message' => 'Webhook успешно создан',
                'data' => [
                    'id' => $result['id'],
                    'name' => $result['name'],
                    'url' => $result['url'],
                    'secret' => $result['secret'], // Показываем только один раз!
                    'events' => $result['events'],
                    'is_active' => $result['is_active']
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка создания webhook: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обработка удаления webhook
     */
    private function handleDeleteWebhook(): void {
        $request = Request::getInstance();
        $id = (int)$request->post('id', 0);
        
        if ($id <= 0) {
            Response::jsonResponse(['success' => false, 'message' => 'Неверный ID'], 400);
            return;
        }
        
        if ($this->webhookManager->delete($id)) {
            Response::jsonResponse(['success' => true, 'message' => 'Webhook удален']);
        } else {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка удаления webhook'], 500);
        }
    }
    
    /**
     * Обработка активации/деактивации webhook
     */
    private function handleToggleWebhook(): void {
        $request = Request::getInstance();
        $id = (int)$request->post('id', 0);
        $isActive = (bool)$request->post('is_active', false);
        
        if ($id <= 0) {
            Response::jsonResponse(['success' => false, 'message' => 'Неверный ID'], 400);
            return;
        }
        
        if ($this->webhookManager->update($id, ['is_active' => $isActive])) {
            Response::jsonResponse(['success' => true, 'message' => 'Статус обновлен']);
        } else {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка обновления статуса'], 500);
        }
    }
    
    /**
     * Тестирование webhook
     */
    private function handleTestWebhook(): void {
        $request = Request::getInstance();
        $id = (int)$request->post('id', 0);
        
        if ($id <= 0) {
            Response::jsonResponse(['success' => false, 'message' => 'Неверный ID'], 400);
            return;
        }
        
        $webhook = $this->webhookManager->get($id);
        if (!$webhook) {
            Response::jsonResponse(['success' => false, 'message' => 'Webhook не найден'], 404);
            return;
        }
        
        try {
            $dispatcher = new WebhookDispatcher();
            $success = $dispatcher->send($webhook, 'webhook.test', [
                'message' => 'Тестовое событие',
                'timestamp' => time()
            ]);
            
            if ($success) {
                Response::jsonResponse(['success' => true, 'message' => 'Webhook успешно отправлен']);
            } else {
                Response::jsonResponse(['success' => false, 'message' => 'Ошибка отправки webhook'], 500);
            }
        } catch (Exception $e) {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Получение данных для шаблона
     */
    protected function getTemplateData(): array {
        $parentData = parent::getTemplateData();
        $webhooks = $this->webhookManager->getAll();
        
        return array_merge($parentData, [
            'webhooks' => $webhooks
        ]);
    }
}

