<?php
/**
 * Сторінка управління API ключами
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class ApiKeysPage extends AdminPage {
    private ApiManager $apiManager;
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.api.keys.view')) {
            Response::redirectStatic(UrlHelper::admin('dashboard'));
            exit;
        }
        
        $this->pageTitle = 'API Ключі - Flowaxy CMS';
        $this->templateName = 'api-keys';
        $this->apiManager = new ApiManager();
        
        // Реєструємо модальне вікно створення API ключа
        $this->registerModal('createApiKeyModal', [
            'title' => 'Створити API ключ',
            'type' => 'form',
            'action' => 'create_api_key',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Назва',
                    'placeholder' => 'Наприклад: Мобільний додаток',
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'name' => 'permissions',
                    'label' => 'Дозволи (по одному на рядок)',
                    'placeholder' => 'system.read\ncontent.write\n*',
                    'help' => 'Залиште порожнім для повного доступу. Доступні дозволи: system.read, system.write, content.read, content.write, content.delete, users.read, users.write, plugins.read, plugins.write, themes.read, themes.write, *'
                ],
                [
                    'type' => 'datetime-local',
                    'name' => 'expires_at',
                    'label' => 'Термін дії (опціонально)',
                    'help' => 'Залиште порожнім для безстрокового ключа'
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
        
        // Реєструємо обробник створення API ключа
        $this->registerModalHandler('createApiKeyModal', 'create_api_key', [$this, 'handleCreateApiKey']);
        
        // Кнопка створення API ключа
        $headerButtons = $this->createButtonGroup([
            [
                'text' => 'Створити API ключ',
                'type' => 'primary',
                'options' => [
                    'icon' => 'plus',
                    'attributes' => [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#createApiKeyModal',
                        'onclick' => 'window.ModalHandler && window.ModalHandler.show("createApiKeyModal")'
                    ]
                ]
            ]
        ]);
        
        $this->setPageHeader(
            'API Ключі',
            'Управління API ключами для зовнішніх додатків',
            'fas fa-key',
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
        
        // Рендеримо сторінку з модальним вікном
        $this->render([
            'createModalHtml' => $this->renderModal('createApiKeyModal')
        ]);
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax(): void {
        $action = $this->post('action', '');
        
        switch ($action) {
            case 'delete_api_key':
                $this->handleDeleteApiKey();
                break;
            case 'toggle_api_key':
                $this->handleToggleApiKey();
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
        
        // Перевірка прав доступу для видалення
        if ($action === 'delete') {
            if (!function_exists('current_user_can') || !current_user_can('admin.api.keys.delete')) {
                $this->setMessage('У вас немає прав на видалення API ключів', 'danger');
                return;
            }
        }
        
        if ($action === 'delete' && $this->post('id')) {
            $id = (int)$this->post('id', 0);
            if ($id > 0 && $this->apiManager->deleteKey($id)) {
                $this->setMessage('API ключ успішно видалено', 'success');
            } else {
                $this->setMessage('Помилка видалення API ключа', 'danger');
            }
            // Редирект после удаления для предотвращения повторного выполнения
            $this->redirect('api-keys');
            exit;
        }
    }
    
    /**
     * Обработчик создания API ключа
     */
    public function handleCreateApiKey(): array {
        $request = Request::getInstance();
        
        if (!SecurityHelper::verifyCsrfToken($request->post('csrf_token', ''))) {
            return [
                'success' => false,
                'message' => 'Ошибка безопасности. Попробуйте еще раз.'
            ];
        }
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.api.keys.create')) {
            return [
                'success' => false,
                'message' => 'У вас немає прав на створення API ключів'
            ];
        }
        
        $name = SecurityHelper::sanitizeInput($request->post('name', ''));
        $permissionsStr = $request->post('permissions', '');
        $expiresAt = $request->post('expires_at', null);
        
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Укажите название API ключа'
            ];
        }
        
        // Парсим разрешения
        $permissions = [];
        if (!empty($permissionsStr)) {
            $lines = explode("\n", $permissionsStr);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $permissions[] = $line;
                }
            }
        }
        
        // Форматируем дату истечения
        $expiresAtFormatted = null;
        if (!empty($expiresAt)) {
            $timestamp = strtotime($expiresAt);
            if ($timestamp !== false) {
                $expiresAtFormatted = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        try {
            $result = $this->apiManager->createKey($name, $permissions, $expiresAtFormatted);
            
            return [
                'success' => true,
                'message' => 'API ключ успешно создан',
                'data' => [
                    'id' => $result['id'],
                    'name' => $result['name'],
                    'key' => $result['key'], // Показываем только один раз!
                    'key_preview' => $result['key_preview'],
                    'permissions' => $result['permissions'],
                    'expires_at' => $result['expires_at'],
                    'is_active' => $result['is_active']
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка создания API ключа: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обработка удаления API ключа
     */
    private function handleDeleteApiKey(): void {
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.api.keys.delete')) {
            Response::jsonResponse(['success' => false, 'message' => 'У вас немає прав на видалення API ключів'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $id = (int)$request->post('id', 0);
        
        if ($id <= 0) {
            Response::jsonResponse(['success' => false, 'message' => 'Неверный ID'], 400);
            return;
        }
        
        if ($this->apiManager->deleteKey($id)) {
            Response::jsonResponse(['success' => true, 'message' => 'API ключ удален']);
        } else {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка удаления API ключа'], 500);
        }
    }
    
    /**
     * Обработка активации/деактивации API ключа
     */
    private function handleToggleApiKey(): void {
        $request = Request::getInstance();
        $id = (int)$request->post('id', 0);
        $isActive = (bool)$request->post('is_active', false);
        
        if ($id <= 0) {
            Response::jsonResponse(['success' => false, 'message' => 'Неверный ID'], 400);
            return;
        }
        
        if ($this->apiManager->setActive($id, $isActive)) {
            Response::jsonResponse(['success' => true, 'message' => 'Статус обновлен']);
        } else {
            Response::jsonResponse(['success' => false, 'message' => 'Ошибка обновления статуса'], 500);
        }
    }
    
    /**
     * Получение данных для шаблона
     */
    protected function getTemplateData(): array {
        $parentData = parent::getTemplateData();
        $keys = $this->apiManager->getAllKeys();
        
        return array_merge($parentData, [
            'keys' => $keys,
            'apiBaseUrl' => rtrim(UrlHelper::base(), '/') . '/api/v1'
        ]);
    }
}

