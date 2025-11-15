<?php
/**
 * Базовый класс для всех плагинов
 */

abstract class BasePlugin {
    protected $pluginData;
    protected $db;
    protected $config;
    
    public function __construct() {
        try {
            $this->db = getDB();
            $this->loadConfig();
        } catch (Exception $e) {
            error_log("BasePlugin constructor error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    /**
     * Загрузка конфигурации плагина
     */
    private function loadConfig() {
        $reflection = new ReflectionClass($this);
        $pluginDir = dirname($reflection->getFileName());
        $configFile = $pluginDir . '/plugin.json';
        
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        }
    }
    
    /**
     * Инициализация плагина (вызывается при загрузке)
     */
    public function init() {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Активация плагина
     */
    public function activate() {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate() {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Установка плагина (создание таблиц, настроек и т.д.)
     */
    public function install() {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Удаление плагина (очистка данных)
     */
    public function uninstall() {
        // Переопределяется в дочерних классах
    }
    
    /**
     * Получение настроек плагина
     */
    public function getSettings() {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute([$this->getSlug()]);
            
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Сохранение настройки плагина
     */
    public function setSetting($key, $value) {
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO plugin_settings (plugin_slug, setting_key, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            return $stmt->execute([$this->getSlug(), $key, $value]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Получение настройки плагина
     */
    public function getSetting($key, $default = null) {
        if (!$this->db) {
            return $default;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM plugin_settings WHERE plugin_slug = ? AND setting_key = ?");
            $stmt->execute([$this->getSlug(), $key]);
            
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Получение слага плагина
     */
    public function getSlug() {
        return $this->config['slug'] ?? strtolower(get_class($this));
    }
    
    /**
     * Получение имени плагина
     */
    public function getName() {
        return $this->config['name'] ?? get_class($this);
    }
    
    /**
     * Получение версии плагина
     */
    public function getVersion() {
        return $this->config['version'] ?? '1.0.0';
    }
    
    /**
     * Получение описания плагина
     */
    public function getDescription() {
        return $this->config['description'] ?? '';
    }
    
    /**
     * Получение автора плагина
     */
    public function getAuthor() {
        return $this->config['author'] ?? '';
    }
    
    /**
     * Получение URL плагина
     */
    public function getPluginUrl() {
        $pluginDir = basename(dirname((new ReflectionClass($this))->getFileName()));
        return SITE_URL . '/plugins/' . $pluginDir . '/';
    }
    
    /**
     * Получение пути к плагину
     */
    public function getPluginPath() {
        return dirname((new ReflectionClass($this))->getFileName()) . '/';
    }
    
    /**
     * Подключение CSS файла плагина
     */
    public function enqueueStyle($handle, $file, $dependencies = []) {
        $url = $this->getPluginUrl() . $file;
        
        addHook('wp_head', function() use ($url, $handle) {
            echo "<link rel='stylesheet' id='{$handle}-css' href='{$url}' type='text/css' media='all' />\n";
        });
    }
    
    /**
     * Подключение JS файла плагина
     */
    public function enqueueScript($handle, $file, $dependencies = [], $inFooter = true) {
        $url = $this->getPluginUrl() . $file;
        
        $hookName = $inFooter ? 'wp_footer' : 'wp_head';
        
        addHook($hookName, function() use ($url, $handle) {
            echo "<script id='{$handle}-js' src='{$url}'></script>\n";
        });
    }
    
    /**
     * Добавление пункта меню в админку
     */
    public function addAdminMenu($title, $capability, $menuSlug, $callback, $icon = '') {
        addHook('admin_menu', function() use ($title, $capability, $menuSlug, $callback, $icon) {
            // Логика добавления меню будет реализована в админке
        });
    }
    
    /**
     * Локализация скрипта (аналог wp_localize_script)
     */
    protected function localizeScript($handle, $objectName, $data) {
        // Добавляем JavaScript переменную
        addHook('wp_footer', function() use ($objectName, $data) {
            echo "<script>var {$objectName} = " . json_encode($data) . ";</script>\n";
        });
    }
    
    /**
     * Создание nonce (аналог wp_create_nonce)
     */
    protected function createNonce($action) {
        return hash('sha256', $action . session_id() . time());
    }
    
    /**
     * Проверка nonce (аналог wp_verify_nonce)
     */
    protected function verifyNonce($nonce, $action) {
        // Простая проверка - в реальном проекте нужна более сложная логика
        return !empty($nonce) && strlen($nonce) === 64;
    }
    
    /**
     * Отправка JSON успеха (аналог wp_send_json_success)
     */
    protected function sendJsonSuccess($data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    /**
     * Отправка JSON ошибки (аналог wp_send_json_error)
     */
    protected function sendJsonError($data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }
    
    /**
     * Логирование действий плагина
     */
    public function log($message, $level = 'info') {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] [{$this->getName()}] [{$level}] {$message}";
        error_log($logMessage);
    }
    
    /**
     * Проверка зависимостей плагина
     */
    public function checkDependencies() {
        if (!isset($this->config['dependencies'])) {
            return true;
        }
        
        try {
            foreach ($this->config['dependencies'] as $dependency) {
                if (!function_exists('pluginManager') || !pluginManager()->isPluginActive($dependency)) {
                    return false;
                }
            }
        } catch (Exception $e) {
            error_log("Plugin dependency check error: " . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * Получение конфигурации плагина
     */
    public function getConfig() {
        return $this->config;
    }
}
?>
