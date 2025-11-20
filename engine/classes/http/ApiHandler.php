<?php
/**
 * Обработчик API запросов
 * Аутентификация и обработка API запросов
 * 
 * @package Engine\Classes\Http
 * @version 1.0.0
 */

declare(strict_types=1);

class ApiHandler {
    private ApiManager $apiManager;
    private ?array $authenticatedKey = null;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->apiManager = new ApiManager();
    }
    
    /**
     * Проверка, является ли запрос API запросом
     * 
     * @return bool
     */
    public static function isApiRequest(): bool {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return strpos($path, '/api/') === 0 || strpos($path, '/api/v1/') === 0;
    }
    
    /**
     * Аутентификация по API ключу
     * 
     * @return bool
     */
    public function authenticate(): bool {
        $request = Request::getInstance();
        
        // Получаем API ключ из заголовка или параметра
        $apiKey = null;
        
        // Проверяем заголовок Authorization (Bearer token)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $apiKey = $matches[1];
        }
        
        // Если не найден в заголовке, проверяем параметр
        if (empty($apiKey)) {
            $apiKey = $request->query('api_key', $request->post('api_key', null));
        }
        
        if (empty($apiKey)) {
            $this->sendError('API ключ не указан', 401);
            return false;
        }
        
        // Валидируем ключ
        $keyData = $this->apiManager->validateKey($apiKey);
        if ($keyData === null) {
            $this->sendError('Невалидный или истекший API ключ', 401);
            return false;
        }
        
        $this->authenticatedKey = $keyData;
        return true;
    }
    
    /**
     * Получение данных аутентифицированного ключа
     * 
     * @return array|null
     */
    public function getAuthenticatedKey(): ?array {
        return $this->authenticatedKey;
    }
    
    /**
     * Проверка разрешения
     * 
     * @param string $permission Разрешение
     * @return bool
     */
    public function hasPermission(string $permission): bool {
        if ($this->authenticatedKey === null) {
            return false;
        }
        
        return $this->apiManager->hasPermission($this->authenticatedKey, $permission);
    }
    
    /**
     * Отправка ошибки
     * 
     * @param string $message Сообщение
     * @param int $statusCode Код статуса
     */
    public function sendError(string $message, int $statusCode = 400): void {
        Response::jsonResponse([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ]
        ], $statusCode);
    }
    
    /**
     * Отправка успешного ответа
     * 
     * @param mixed $data Данные
     * @param int $statusCode Код статуса
     */
    public function sendSuccess($data, int $statusCode = 200): void {
        Response::jsonResponse([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Middleware для проверки API ключа
     * 
     * @param array $params Параметры маршрута
     * @return bool
     */
    public static function requireAuth(array $params = []): bool {
        $handler = new self();
        return $handler->authenticate();
    }
    
    /**
     * Middleware для проверки разрешения
     * 
     * @param string $permission Разрешение
     * @return callable
     */
    public static function requirePermission(string $permission): callable {
        return function(array $params = []) use ($permission) {
            $handler = new self();
            if (!$handler->authenticate()) {
                return false;
            }
            
            if (!$handler->hasPermission($permission)) {
                $handler->sendError('Недостаточно прав доступа', 403);
                return false;
            }
            
            return true;
        };
    }
}

