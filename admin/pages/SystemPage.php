<?php
/**
 * Страница системной информации
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class SystemPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Система - Landing CMS';
        $this->templateName = 'system';
        
        $this->setPageHeader(
            'Система',
            'Інформація про систему та сервер',
            'fas fa-server'
        );
    }
    
    public function handle() {
        // Получение системной информации
        $systemInfo = $this->getSystemInfo();
        
        // Рендерим страницу
        $this->render([
            'systemInfo' => $systemInfo
        ]);
    }
    
    /**
     * Получение системной информации
     */
    private function getSystemInfo() {
        $info = [];
        
        // PHP версия
        $info['php_version'] = PHP_VERSION;
        $info['php_sapi'] = php_sapi_name();
        
        // MySQL версия
        try {
            $db = getDB();
            $stmt = $db->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $info['mysql_version'] = $result['version'] ?? 'Unknown';
        } catch (Exception $e) {
            $info['mysql_version'] = 'Error: ' . $e->getMessage();
        }
        
        // Сервер
        $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info['server_name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
        
        // Версия CMS
        $info['cms_version'] = '1.0.0';
        
        // Память
        $info['memory_limit'] = ini_get('memory_limit');
        $info['memory_usage'] = round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
        $info['memory_peak'] = round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';
        
        // Время сервера
        $info['server_time'] = date('d.m.Y H:i:s');
        $info['timezone'] = date_default_timezone_get();
        
        // Права папок
        $info['folders'] = $this->checkFolderPermissions();
        
        // Расширения PHP
        $info['extensions'] = $this->getImportantExtensions();
        
        return $info;
    }
    
    /**
     * Проверка прав доступа к папкам
     */
    private function checkFolderPermissions() {
        $folders = [
            'uploads' => UPLOADS_DIR,
            'cache' => __DIR__ . '/../../cache',
            'plugins' => __DIR__ . '/../../plugins',
            'themes' => __DIR__ . '/../../themes'
        ];
        
        $permissions = [];
        foreach ($folders as $name => $path) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $writable = is_writable($path);
                $readable = is_readable($path);
                
                $permissions[$name] = [
                    'path' => $path,
                    'permissions' => $perms,
                    'writable' => $writable,
                    'readable' => $readable,
                    'status' => $writable && $readable ? 'ok' : 'warning'
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
        
        return $permissions;
    }
    
    /**
     * Получение важных расширений PHP
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

