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
     * Получение настроек плагина с кешированием
     */
    public function getSettings(): array {
        if (!$this->db) {
            return [];
        }
        
        $slug = $this->getSlug();
        $cacheKey = 'plugin_settings_' . $slug;
        
        return cache_remember($cacheKey, function() use ($slug) {
            $db = getDB();
            if (!$db) {
                return [];
            }
            
            try {
                $stmt = $db->prepare("SELECT setting_key, setting_value FROM plugin_settings WHERE plugin_slug = ?");
                $stmt->execute([$slug]);
                
                $settings = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                
                return $settings;
            } catch (Exception $e) {
                error_log("BasePlugin getSettings error: " . $e->getMessage());
                return [];
            }
        }, 1800); // Кешируем на 30 минут
    }
    
    /**
     * Сохранение настройки плагина
     */
    public function setSetting(string $key, $value): bool {
        if (!$this->db || empty($key)) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO plugin_settings (plugin_slug, setting_key, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            $result = $stmt->execute([$this->getSlug(), $key, $value]);
            
            if ($result) {
                // Очищаем кеш настроек
                cache_forget('plugin_settings_' . $this->getSlug());
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("BasePlugin setSetting error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение настройки плагина
     */
    public function getSetting(string $key, $default = null) {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }
    
    /**
     * Получение слага плагина
     */
    public function getSlug(): string {
        return $this->config['slug'] ?? strtolower(get_class($this));
    }
    
    /**
     * Получение имени плагина
     */
    public function getName(): string {
        return $this->config['name'] ?? get_class($this);
    }
    
    /**
     * Получение версии плагина
     */
    public function getVersion(): string {
        return $this->config['version'] ?? '1.0.0';
    }
    
    /**
     * Получение описания плагина
     */
    public function getDescription(): string {
        return $this->config['description'] ?? '';
    }
    
    /**
     * Получение автора плагина
     */
    public function getAuthor(): string {
        return $this->config['author'] ?? '';
    }
    
    /**
     * Получение URL плагина
     */
    public function getPluginUrl(): string {
        $pluginDir = basename(dirname((new ReflectionClass($this))->getFileName()));
        return SITE_URL . '/plugins/' . $pluginDir . '/';
    }
    
    /**
     * Получение пути к плагину
     */
    public function getPluginPath(): string {
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
    protected function createNonce(string $action): string {
        if (!isset($_SESSION['plugin_nonces'])) {
            $_SESSION['plugin_nonces'] = [];
        }
        
        $nonce = bin2hex(random_bytes(32));
        $_SESSION['plugin_nonces'][$action] = [
            'nonce' => $nonce,
            'expires' => time() + 3600 // 1 час
        ];
        
        return $nonce;
    }
    
    /**
     * Проверка nonce (аналог wp_verify_nonce)
     */
    protected function verifyNonce(?string $nonce, string $action): bool {
        if (empty($nonce) || !isset($_SESSION['plugin_nonces'][$action])) {
            return false;
        }
        
        $stored = $_SESSION['plugin_nonces'][$action];
        
        // Проверяем срок действия
        if (isset($stored['expires']) && $stored['expires'] < time()) {
            unset($_SESSION['plugin_nonces'][$action]);
            return false;
        }
        
        // Проверяем nonce
        if (isset($stored['nonce']) && hash_equals($stored['nonce'], $nonce)) {
            // Удаляем использованный nonce (одноразовое использование)
            unset($_SESSION['plugin_nonces'][$action]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Отправка JSON успеха (аналог wp_send_json_success)
     */
    protected function sendJsonSuccess($data): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    
    /**
     * Отправка JSON ошибки (аналог wp_send_json_error)
     */
    protected function sendJsonError($data): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['success' => false, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    
    /**
     * Логирование действий плагина
     */
    public function log(string $message, string $level = 'info'): void {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] [{$this->getName()}] [{$level}] {$message}";
        error_log($logMessage);
    }
    
    /**
     * Проверка зависимостей плагина
     */
    public function checkDependencies(): bool {
        if (!isset($this->config['dependencies']) || !is_array($this->config['dependencies'])) {
            return true;
        }
        
        try {
            if (!function_exists('pluginManager')) {
                return false;
            }
            
            $pluginManager = pluginManager();
            foreach ($this->config['dependencies'] as $dependency) {
                if (!is_string($dependency) || !$pluginManager->isPluginActive($dependency)) {
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
    public function getConfig(): array {
        return $this->config ?? [];
    }
}
?>
