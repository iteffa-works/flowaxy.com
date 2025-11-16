<?php
/**
 * Сторінка системної інформації
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class SystemPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Системна інформація - Landing CMS';
        $this->templateName = 'system';
        
        $this->setPageHeader(
            'Системна інформація',
            'Інформація про систему та сервер',
            'fas fa-server'
        );
    }
    
    public function handle() {
        // Обробка дій з кешем
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cache_action'])) {
            $this->handleCacheAction();
        }
        
        // Отримання системної інформації
        $systemInfo = $this->getSystemInfo();
        
        // Отримання інформації про кеш
        $cacheInfo = $this->getCacheInfo();
        
        // Рендеримо сторінку
        $this->render([
            'systemInfo' => $systemInfo,
            'cacheInfo' => $cacheInfo
        ]);
    }
    
    /**
     * Обробка дій з кешем
     */
    private function handleCacheAction(): void {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Помилка безпеки', 'danger');
            return;
        }
        
        $action = sanitizeInput($_POST['cache_action'] ?? '');
        
        try {
            $cache = cache();
            
            switch ($action) {
                case 'clear_all':
                    if ($cache->clear()) {
                        $this->setMessage('Весь кеш успішно очищено', 'success');
                    } else {
                        $this->setMessage('Помилка при очищенні кешу', 'danger');
                    }
                    break;
                    
                case 'clear_expired':
                    $cleared = $cache->cleanup();
                    $this->setMessage("Прострочений кеш успішно очищено ({$cleared} файлів)", 'success');
                    break;
                    
                case 'clear_stats':
                    cache_forget('cache_stats');
                    $this->setMessage('Статистика кешу очищена', 'success');
                    break;
                    
                default:
                    $this->setMessage('Невідома дія', 'danger');
            }
        } catch (Exception $e) {
            $this->setMessage('Помилка при обробці кешу: ' . $e->getMessage(), 'danger');
            error_log("Cache action error: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання інформації про кеш
     */
    private function getCacheInfo(): array {
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        
        $info = [
            'enabled' => true,
            'directory' => $cacheDir,
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0,
            'expired_size' => 0,
            'writable' => is_writable($cacheDir)
        ];
        
        if (!is_dir($cacheDir)) {
            return $info;
        }
        
        $files = glob($cacheDir . '*.cache');
        if ($files === false) {
            return $info;
        }
        
        $now = time();
        $info['total_files'] = count($files);
        
        foreach ($files as $file) {
            $size = @filesize($file);
            if ($size !== false) {
                $info['total_size'] += $size;
            }
            
            $data = @file_get_contents($file);
            if ($data !== false) {
                try {
                    $cached = @unserialize($data, ['allowed_classes' => false]);
                    if (is_array($cached) && isset($cached['expires'])) {
                        if ($cached['expires'] < $now) {
                            $info['expired_files']++;
                            if ($size !== false) {
                                $info['expired_size'] += $size;
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Игнорируем ошибки
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Отримання системної інформації
     */
    private function getSystemInfo() {
        $info = [];
        
        // Версія PHP
        $info['php_version'] = PHP_VERSION;
        $info['php_sapi'] = php_sapi_name();
        
        // Версія MySQL (кешуємо на 1 годину)
        $info['mysql_version'] = cache_remember('mysql_version', function() {
            try {
                $db = getDB(false);
                if (!$db) {
                    return 'Unknown';
                }
                
                $stmt = $db->query("SELECT VERSION() as version");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['version'] ?? 'Unknown';
            } catch (Exception $e) {
                error_log("MySQL version error: " . $e->getMessage());
                return 'Error: ' . $e->getMessage();
            }
        }, 3600);
        
        // Сервер
        $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info['server_name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
        
        // Версія CMS
        $info['cms_version'] = '1.0.0';
        
        // Пам'ять
        $info['memory_limit'] = ini_get('memory_limit');
        $info['memory_usage'] = round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
        $info['memory_peak'] = round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';
        
        // Час сервера
        $info['server_time'] = date('d.m.Y H:i:s');
        $info['timezone'] = date_default_timezone_get();
        
        // Права доступу до папок
        $info['folders'] = $this->checkFolderPermissions();
        
        // Розширення PHP
        $info['extensions'] = $this->getImportantExtensions();
        
        return $info;
    }
    
    /**
     * Перевірка прав доступу до папок
     */
    private function checkFolderPermissions() {
        $baseDir = dirname(__DIR__, 2);
        
        $folders = [
            'uploads' => defined('UPLOADS_DIR') ? UPLOADS_DIR : $baseDir . '/uploads',
            'cache' => defined('CACHE_DIR') ? CACHE_DIR : $baseDir . '/storage/cache',
            'plugins' => $baseDir . '/plugins',
            'themes' => $baseDir . '/themes'
        ];
        
        $permissions = [];
        foreach ($folders as $name => $path) {
            // Нормалізуємо шлях (прибираємо зайві слеші)
            $path = str_replace(['\\', '//'], ['/', '/'], $path);
            $path = rtrim($path, '/');
            
            if (file_exists($path) && is_dir($path)) {
                // Отримуємо права доступу
                $perms = @fileperms($path);
                if ($perms !== false) {
                    $permsStr = substr(sprintf('%o', $perms), -4);
                } else {
                    $permsStr = 'N/A';
                }
                
                $writable = is_writable($path);
                $readable = is_readable($path);
                
                $permissions[$name] = [
                    'path' => $path,
                    'permissions' => $permsStr,
                    'writable' => $writable,
                    'readable' => $readable,
                    'status' => $writable && $readable ? 'ok' : ($readable ? 'warning' : 'error')
                    ];
                } else {
                    // Намагаємося створити директорію, якщо її немає
                    if (!file_exists($path)) {
                        @mkdir($path, 0755, true);
                    }
                    
                    // Перевіряємо знову після спроби створення
                    if (file_exists($path) && is_dir($path)) {
                    $perms = @fileperms($path);
                    $permsStr = $perms !== false ? substr(sprintf('%o', $perms), -4) : 'N/A';
                    $writable = is_writable($path);
                    $readable = is_readable($path);
                    
                    $permissions[$name] = [
                        'path' => $path,
                        'permissions' => $permsStr,
                        'writable' => $writable,
                        'readable' => $readable,
                        'status' => $writable && $readable ? 'ok' : ($readable ? 'warning' : 'error')
                    ];
                } else {
                    $permissions[$name] = [
                        'path' => $path,
                        'permissions' => 'N/A',
                        'writable' => false,
                        'readable' => false,
                        'status' => 'error'
                    ];
                }
            }
        }
        
        return $permissions;
    }
    
    /**
     * Отримання важливих розширень PHP
     */
    private function getImportantExtensions() {
        $important = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'gd', 'zip', 'curl', 'openssl', 'fileinfo'];
        $extensions = [];
        
        foreach ($important as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }
        
        return $extensions;
    }
}

