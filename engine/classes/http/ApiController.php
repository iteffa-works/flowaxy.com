<?php
/**
 * Контроллер API
 * Базовые endpoints для API
 * 
 * @package Engine\Classes\Http
 * @version 1.0.0
 */

declare(strict_types=1);

class ApiController {
    private ApiHandler $handler;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->handler = new ApiHandler();
    }
    
    /**
     * Информация о системе
     * 
     * @return void
     */
    public function info(): void {
        // Не требует аутентификации
        $info = [
            'name' => 'Flowaxy CMS',
            'version' => '6.0.0',
            'api_version' => '1.0',
            'timestamp' => time()
        ];
        
        Response::jsonResponse($info);
    }
    
    /**
     * Статус системы
     * 
     * @return void
     */
    public function status(): void {
        // Не требует аутентификации
        try {
            $db = Database::getInstance();
            $db->getConnection();
            $dbStatus = 'connected';
        } catch (Exception $e) {
            $dbStatus = 'error';
        }
        
        $status = [
            'status' => 'ok',
            'database' => $dbStatus,
            'timestamp' => time()
        ];
        
        Response::jsonResponse($status);
    }
    
    /**
     * Информация об аутентифицированном ключе
     * 
     * @return void
     */
    public function me(): void {
        if (!$this->handler->authenticate()) {
            return;
        }
        
        $keyData = $this->handler->getAuthenticatedKey();
        if ($keyData === null) {
            $this->handler->sendError('Не удалось получить данные ключа', 500);
            return;
        }
        
        // Не возвращаем чувствительные данные
        unset($keyData['key_hash']);
        
        $this->handler->sendSuccess($keyData);
    }
    
    /**
     * Проверка разрешения
     * 
     * @param array $params Параметры маршрута
     * @return void
     */
    public function checkPermission(array $params = []): void {
        if (!$this->handler->authenticate()) {
            return;
        }
        
        $request = Request::getInstance();
        $permission = $request->query('permission', $params['permission'] ?? null);
        
        if (empty($permission)) {
            $this->handler->sendError('Разрешение не указано', 400);
            return;
        }
        
        $hasPermission = $this->handler->hasPermission($permission);
        
        $this->handler->sendSuccess([
            'permission' => $permission,
            'granted' => $hasPermission
        ]);
    }
    
    /**
     * Список доступных разрешений
     * 
     * @return void
     */
    public function permissions(): void {
        if (!$this->handler->authenticate()) {
            return;
        }
        
        $permissions = [
            'system.read' => 'Чтение информации о системе',
            'system.write' => 'Изменение настроек системы',
            'content.read' => 'Чтение контента',
            'content.write' => 'Создание и редактирование контента',
            'content.delete' => 'Удаление контента',
            'users.read' => 'Чтение информации о пользователях',
            'users.write' => 'Управление пользователями',
            'plugins.read' => 'Чтение информации о плагинах',
            'plugins.write' => 'Управление плагинами',
            'themes.read' => 'Чтение информации о темах',
            'themes.write' => 'Управление темами',
            '*' => 'Полный доступ'
        ];
        
        $this->handler->sendSuccess([
            'permissions' => $permissions,
            'description' => 'Список доступных разрешений для API ключей'
        ]);
    }
}

