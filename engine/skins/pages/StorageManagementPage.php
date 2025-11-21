<?php
/**
 * Сторінка управління сховищами (сесії, куки, сторейджи)
 * 
 * @package Engine\Skins\Pages
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class StorageManagementPage extends AdminPage {
    protected string $templateName = 'storage-management';
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Управління сховищами - Flowaxy CMS';
        $this->setPageHeader(
            'Управління сховищами',
            'Управління сесіями, куками та клієнтським сховищем',
            'fas fa-database'
        );
    }
    
    public function handle(): void {
        $request = Request::getInstance();
        
        if ($request->method() === 'POST') {
            $action = $request->post('action', '');
            
            switch ($action) {
                case 'clear_sessions':
                    $this->handleClearSessions();
                    break;
                case 'clear_cookies':
                    $this->handleClearCookies();
                    break;
                case 'clear_storage':
                    $this->handleClearStorage();
                    break;
                case 'sync_storage':
                    $this->handleSyncStorage();
                    break;
                case 'get_storage_info':
                    $this->handleGetStorageInfo();
                    break;
            }
        }
        
        $this->render();
    }
    
    /**
     * Очистка сесій
     */
    private function handleClearSessions(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        try {
            $sessionPath = __DIR__ . '/../../../storage/sessions';
            $cleared = 0;
            
            if (is_dir($sessionPath)) {
                $files = glob($sessionPath . '/sess_*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                        $cleared++;
                    }
                }
            }
            
            // Також очищаємо поточну сесію (крім адмінської авторизації)
            $session = sessionManager();
            $adminUserId = $session->get('admin_user_id');
            $csrfToken = $session->get('csrf_token');
            $session->clear();
            
            // Відновлюємо важливі дані
            if ($adminUserId) {
                $session->set('admin_user_id', $adminUserId);
            }
            if ($csrfToken) {
                $session->set('csrf_token', $csrfToken);
            }
            
            $this->setMessage("Очищено сесій: {$cleared}", 'success');
        } catch (Exception $e) {
            error_log("Error clearing sessions: " . $e->getMessage());
            $this->setMessage('Помилка при очищенні сесій', 'danger');
        }
        
        $this->redirect('storage-management');
    }
    
    /**
     * Очистка cookies
     */
    private function handleClearCookies(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        try {
            $cookieManager = cookieManager();
            $allCookies = $cookieManager->all();
            $cleared = 0;
            
            // Исключаем важные cookies (сессия, CSRF)
            $excludedCookies = ['PHPSESSID', 'csrf_token'];
            
            foreach ($allCookies as $key => $value) {
                if (!in_array($key, $excludedCookies, true)) {
                    if ($cookieManager->remove($key)) {
                        $cleared++;
                    }
                }
            }
            
            $this->setMessage("Очищено cookies: {$cleared}", 'success');
        } catch (Exception $e) {
            error_log("Error clearing cookies: " . $e->getMessage());
            $this->setMessage('Помилка при очищенні cookies', 'danger');
        }
        
        $this->redirect('storage-management');
    }
    
    /**
     * Очистка клиентского хранилища
     */
    private function handleClearStorage(): void {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        try {
            $storageManager = storageManager();
            $storageManager->clear();
            
            $this->setMessage('Клієнтське сховище очищено', 'success');
        } catch (Exception $e) {
            error_log("Error clearing storage: " . $e->getMessage());
            $this->setMessage('Помилка при очищенні сховища', 'danger');
        }
        
        $this->redirect('storage-management');
    }
    
    /**
     * Синхронизация хранилищ
     */
    private function handleSyncStorage(): void {
        if (!$this->verifyCsrf()) {
            Response::jsonResponse(['success' => false, 'error' => 'CSRF token invalid'], 403);
            return;
        }
        
        try {
            $request = Request::getInstance();
            $clientDataJson = $request->post('client_data', '');
            
            $clientData = [];
            if (!empty($clientDataJson)) {
                $decoded = json_decode($clientDataJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $clientData = $decoded;
                }
            }
            
            // Получаем серверные данные
            $storageManager = storageManager();
            $serverData = $storageManager->all();
            
            // Сравниваем и синхронизируем
            $synced = 0;
            $conflicts = [];
            
            // Проверяем, какие ключи есть на клиенте, но нет на сервере
            foreach ($clientData as $key => $value) {
                if (!isset($serverData[$key])) {
                    // Ключ есть только на клиенте - можно добавить на сервер (опционально)
                    // $storageManager->set($key, $value);
                }
            }
            
            // Проверяем, какие ключи есть на сервере, но нет на клиенте
            foreach ($serverData as $key => $value) {
                if (!isset($clientData[$key])) {
                    // Ключ есть только на сервере - нужно отправить на клиент
                    $synced++;
                } else {
                    // Ключ есть и на сервере, и на клиенте - проверяем на конфликты
                    if ($clientData[$key] !== $value) {
                        $conflicts[] = $key;
                    }
                }
            }
            
            Response::jsonResponse([
                'success' => true,
                'synced' => $synced,
                'conflicts' => $conflicts,
                'server_keys' => array_keys($serverData),
                'client_keys' => array_keys($clientData)
            ]);
        } catch (Exception $e) {
            error_log("Error syncing storage: " . $e->getMessage());
            Response::jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Получение информации о хранилищах (AJAX)
     */
    private function handleGetStorageInfo(): void {
        if (!$this->verifyCsrf()) {
            Response::jsonResponse(['success' => false, 'error' => 'CSRF token invalid'], 403);
            return;
        }
        
        try {
            $info = [
                'sessions' => $this->getSessionsInfo(),
                'cookies' => $this->getCookiesInfo(),
                'storage' => $this->getStorageInfo()
            ];
            
            Response::jsonResponse(['success' => true, 'data' => $info]);
        } catch (Exception $e) {
            error_log("Error getting storage info: " . $e->getMessage());
            Response::jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Получение информации о сессиях
     */
    private function getSessionsInfo(): array {
        $sessionPath = __DIR__ . '/../../../storage/sessions';
        $info = [
            'count' => 0,
            'total_size' => 0,
            'files' => [],
            'current_session_id' => Session::getId(),
            'session_path' => session_save_path()
        ];
        
        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/sess_*');
            $info['count'] = count($files);
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $size = filesize($file);
                    $info['total_size'] += $size;
                    $info['files'][] = [
                        'name' => basename($file),
                        'size' => $size,
                        'modified' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
        }
        
        // Получаем данные текущей сессии
        $session = sessionManager();
        $info['current_session_data'] = $session->all(false);
        $info['current_session_keys'] = array_keys($info['current_session_data']);
        
        return $info;
    }
    
    /**
     * Получение информации о cookies
     */
    private function getCookiesInfo(): array {
        $cookieManager = cookieManager();
        $allCookies = $cookieManager->all();
        
        $info = [
            'count' => count($allCookies),
            'cookies' => []
        ];
        
        foreach ($allCookies as $key => $value) {
            $cookieInfo = [
                'key' => $key,
                'value' => is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value,
                'value_length' => is_string($value) ? strlen($value) : 0,
                'is_json' => false
            ];
            
            // Проверяем, является ли значение JSON
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cookieInfo['is_json'] = true;
                    $cookieInfo['json_keys'] = is_array($decoded) ? array_keys($decoded) : [];
                }
            }
            
            $info['cookies'][] = $cookieInfo;
        }
        
        return $info;
    }
    
    /**
     * Получение информации о клиентском хранилище
     */
    private function getStorageInfo(): array {
        $storageManager = storageManager();
        
        $info = [
            'type' => $storageManager->getType(),
            'prefix' => $storageManager->getPrefix(),
            'server_data' => $storageManager->all(),
            'server_keys' => array_keys($storageManager->all()),
            'server_count' => count($storageManager->all())
        ];
        
        return $info;
    }
    
    /**
     * Форматирование размера в байтах
     */
    private function formatBytes(int $bytes): string {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    protected function getTemplateData(): array {
        $data = parent::getTemplateData();
        
        // Получаем информацию о хранилищах
        $data['sessions_info'] = $this->getSessionsInfo();
        $data['cookies_info'] = $this->getCookiesInfo();
        $data['storage_info'] = $this->getStorageInfo();
        
        // Добавляем метод форматирования для шаблона
        $data['formatBytes'] = [$this, 'formatBytes'];
        
        return $data;
    }
}

