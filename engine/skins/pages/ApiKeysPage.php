<?php
/**
 * Страница управления API ключами
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class ApiKeysPage extends AdminPage {
    private ApiManager $apiManager;
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'API Ключи - Flowaxy CMS';
        $this->templateName = 'api-keys';
        $this->apiManager = new ApiManager();
        
        // Регистрируем модальное окно создания API ключа
        $this->registerModal('createApiKeyModal', [
            'title' => 'Создать API ключ',
            'type' => 'form',
            'action' => 'create_api_key',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Название',
                    'placeholder' => 'Например: Мобильное приложение',
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'name' => 'permissions',
                    'label' => 'Разрешения (по одному на строку)',
                    'placeholder' => 'system.read\ncontent.write\n*',
                    'help' => 'Оставьте пустым для полного доступа. Доступные разрешения: system.read, system.write, content.read, content.write, content.delete, users.read, users.write, plugins.read, plugins.write, themes.read, themes.write, *'
                ],
                [
                    'type' => 'datetime-local',
                    'name' => 'expires_at',
                    'label' => 'Срок действия (опционально)',
                    'help' => 'Оставьте пустым для бессрочного ключа'
                ]
            ],
            'buttons' => [
                [
                    'text' => 'Отменить',
                    'type' => 'secondary',
                    'action' => 'close'
                ],
                [
                    'text' => 'Создать',
                    'type' => 'primary',
                    'icon' => 'plus',
                    'action' => 'submit'
                ]
            ]
        ]);
        
        // Регистрируем обработчик создания API ключа
        $this->registerModalHandler('createApiKeyModal', 'create_api_key', [$this, 'handleCreateApiKey']);
        
        // Кнопка создания API ключа
        $headerButtons = $this->createButtonGroup([
            [
                'text' => 'Создать API ключ',
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
            'API Ключи',
            'Управление API ключами для внешних приложений',
            'fas fa-key',
            $headerButtons
        );
    }
    
    public function handle() {
        // Обработка AJAX запросов
        if ($this->isAjaxRequest()) {
            $this->handleAjax();
            return;
        }
        
        // Обработка действий
        if ($_POST) {
            $this->handleAction();
        }
        
        // Рендерим страницу с модальным окном
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
        $action = $this->post('action', '');
        
        if ($action === 'delete' && $this->post('id')) {
            $this->handleDeleteApiKey();
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

