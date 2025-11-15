<?php
/**
 * Менеджер плагинов - основной класс для управления плагинами
 */

class PluginManager {
    private static $instance = null;
    private $plugins = [];
    private $hooks = [];
    private $db;
    
    private function __construct() {
        $this->db = getDB();
        $this->loadPlugins();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ручная инициализация плагинов
     */
    public function initializePlugins() {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'init')) {
                $plugin->init();
            }
        }
    }
    
    /**
     * Загрузка активных плагинов
     */
    private function loadPlugins() {
        if (!$this->db) {
            return;
        }
        
        try {
            $stmt = $this->db->query("SELECT * FROM plugins WHERE is_active = 1 ORDER BY name ASC");
            $pluginData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pluginData as $plugin) {
                $this->loadPlugin($plugin);
            }
        } catch (Exception $e) {
            error_log("Error loading plugins: " . $e->getMessage());
        }
    }
    
    /**
     * Загрузка конкретного плагина
     */
    private function loadPlugin($pluginData) {
        $slug = $pluginData['slug'];
        $pluginDir = __DIR__ . '/../plugins/' . $slug;
        
        // Преобразуем slug в имя класса (например: page-builder -> PageBuilderPlugin)
        $parts = explode('-', $slug);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        $className .= 'Plugin';
        
        $pluginPath = $pluginDir . '/' . $className . '.php';
        
        if (file_exists($pluginPath)) {
            try {
                require_once $pluginPath;
                
                if (class_exists($className)) {
                    $this->plugins[$slug] = new $className();
                }
            } catch (Exception $e) {
                error_log("Error loading plugin {$slug}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Добавление хука
     */
    public function addHook($hookName, $callback, $priority = 10) {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        // Сортируем по приоритету
        usort($this->hooks[$hookName], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Выполнение хука
     */
    public function doHook($hookName, $data = null) {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }
        
        foreach ($this->hooks[$hookName] as $hook) {
            if (is_callable($hook['callback'])) {
                $data = call_user_func($hook['callback'], $data);
            }
        }
        
        return $data;
    }
    
    /**
     * Проверка существования хука
     */
    public function hasHook($hookName) {
        return isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }
    
    /**
     * Получение всех плагинов (из файловой системы)
     */
    public function getAllPlugins() {
        $pluginsDir = __DIR__ . '/../plugins/';
        $allPlugins = [];
        
        if (is_dir($pluginsDir)) {
            $directories = glob($pluginsDir . '*', GLOB_ONLYDIR);
            
            foreach ($directories as $dir) {
                $pluginSlug = basename($dir);
                $configFile = $dir . '/plugin.json';
                
                if (file_exists($configFile)) {
                    $config = json_decode(file_get_contents($configFile), true);
                    if ($config) {
                        $config['slug'] = $pluginSlug;
                        $allPlugins[$pluginSlug] = $config;
                    }
                }
            }
        }
        
        return $allPlugins;
    }
    
    /**
     * Получение активных плагинов
     */
    public function getActivePlugins() {
        return $this->plugins;
    }
    
    /**
     * Установка плагина
     */
    public function installPlugin($pluginSlug) {
        try {
            // Проверяем, существует ли директория плагина
            $pluginDir = __DIR__ . '/../plugins/' . $pluginSlug;
            if (!is_dir($pluginDir)) {
                return false;
            }
            
            // Загружаем конфигурацию плагина
            $configFile = $pluginDir . '/plugin.json';
            if (!file_exists($configFile)) {
                return false;
            }
            
            $config = json_decode(file_get_contents($configFile), true);
            if (!$config) {
                return false;
            }
            
            // Выполняем SQL файлы из папки db
            $this->executeDatabaseFiles($pluginDir . '/db');
            
            // Добавляем плагин в базу данных
            $stmt = $this->db->prepare("
                INSERT INTO plugins (name, slug, description, version, author, is_active) 
                VALUES (?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                description = VALUES(description), 
                version = VALUES(version),
                author = VALUES(author)
            ");
            
            $result = $stmt->execute([
                $config['name'],
                $config['slug'],
                $config['description'] ?? '',
                $config['version'] ?? '1.0.0',
                $config['author'] ?? ''
            ]);
            
            if ($result) {
                // Очищаем кеш
                if (function_exists('cache_delete')) {
                    cache_delete('active_plugins');
                }
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Plugin installation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Удаление плагина
     */
    public function uninstallPlugin($pluginSlug) {
        try {
            // Получаем данные плагина из БД
            $stmt = $this->db->prepare("SELECT * FROM plugins WHERE slug = ?");
            $stmt->execute([$pluginSlug]);
            $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pluginData) {
                return false;
            }
            
            // Загружаем плагин если он не загружен
            if (!isset($this->plugins[$pluginSlug])) {
                $this->loadPlugin($pluginData);
            }
            
            // Вызываем метод удаления плагина
            $plugin = $this->getPlugin($pluginSlug);
            if ($plugin && method_exists($plugin, 'uninstall')) {
                $plugin->uninstall();
            }
            
            // Деактивируем плагин
            $this->deactivatePlugin($pluginSlug);
            
            // Удаляем плагин из базы данных
            $stmt = $this->db->prepare("DELETE FROM plugins WHERE slug = ?");
            $result = $stmt->execute([$pluginSlug]);
            
            if ($result) {
                // Очищаем кеш
                cache_forget('active_plugins');
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Plugin uninstallation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Активация плагина
     */
    public function activatePlugin($pluginSlug) {
        try {
            $stmt = $this->db->prepare("UPDATE plugins SET is_active = 1 WHERE slug = ?");
            $result = $stmt->execute([$pluginSlug]);
            
            if ($result) {
                // Очищаем кеш
                if (function_exists('cache_delete')) {
                    cache_delete('active_plugins');
                }
                
                // Загружаем и активируем плагин
                $plugin = $this->getPluginInstance($pluginSlug);
                if ($plugin) {
                    // Вызываем метод активации плагина
                    if (method_exists($plugin, 'activate')) {
                        $plugin->activate();
                    }
                    
                    // Вызываем init() чтобы зарегистрировать хуки плагина
                    if (method_exists($plugin, 'init')) {
                        $plugin->init();
                    }
                }
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Plugin activation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivatePlugin($pluginSlug) {
        try {
            // Вызываем метод деактивации плагина
            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin && method_exists($plugin, 'deactivate')) {
                $plugin->deactivate();
            }
            
            $stmt = $this->db->prepare("UPDATE plugins SET is_active = 0 WHERE slug = ?");
            $result = $stmt->execute([$pluginSlug]);
            
            if ($result) {
                // Очищаем кеш
                if (function_exists('cache_delete')) {
                    cache_delete('active_plugins');
                }
                
                // Удаляем из загруженных плагинов
                unset($this->plugins[$pluginSlug]);
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Plugin deactivation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Получение экземпляра плагина по slug
     */
    private function getPluginInstance($pluginSlug) {
        // Если плагин уже загружен, возвращаем его
        if (isset($this->plugins[$pluginSlug])) {
            return $this->plugins[$pluginSlug];
        }
        
        $pluginDir = __DIR__ . '/../plugins/' . $pluginSlug;
        
        // Преобразуем slug в имя класса (например: page-builder -> PageBuilderPlugin)
        $parts = explode('-', $pluginSlug);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        $className .= 'Plugin';
        
        $pluginPath = $pluginDir . '/' . $className . '.php';
        
        if (file_exists($pluginPath)) {
            try {
                require_once $pluginPath;
                
                if (class_exists($className)) {
                    $plugin = new $className();
                    // Сохраняем плагин в массиве загруженных плагинов
                    $this->plugins[$pluginSlug] = $plugin;
                    return $plugin;
                }
            } catch (Exception $e) {
                error_log("Error loading plugin instance {$pluginSlug}: " . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Получение имени класса плагина
     */
    private function getPluginClassName($pluginSlug) {
        // Преобразуем slug в CamelCase + Plugin
        $parts = explode('-', $pluginSlug);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        return $className . 'Plugin';
    }
    
    /**
     * Выполнение SQL файлов из директории db плагина
     */
    private function executeDatabaseFiles($dbDir) {
        if (!is_dir($dbDir)) {
            return;
        }
        
        // Ищем файлы install.sql или *.sql
        $sqlFiles = [];
        
        // Сначала ищем install.sql
        if (file_exists($dbDir . '/install.sql')) {
            $sqlFiles[] = $dbDir . '/install.sql';
        } else {
            // Если нет install.sql, берем все .sql файлы
            $files = glob($dbDir . '/*.sql');
            $sqlFiles = array_merge($sqlFiles, $files);
        }
        
        foreach ($sqlFiles as $sqlFile) {
            $this->executeSqlFile($sqlFile);
        }
    }
    
    /**
     * Выполнение SQL файлов удаления
     */
    private function executeUninstallFiles($dbDir) {
        if (!is_dir($dbDir)) {
            return;
        }
        
        // Ищем файл uninstall.sql
        $uninstallFile = $dbDir . '/uninstall.sql';
        if (file_exists($uninstallFile)) {
            $this->executeSqlFile($uninstallFile);
        }
    }
    
    /**
     * Выполнение SQL файла
     */
    private function executeSqlFile($sqlFile) {
        if (!file_exists($sqlFile) || !$this->db) {
            return false;
        }
        
        try {
            $sql = file_get_contents($sqlFile);
            
            // Разбиваем на отдельные запросы
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->db->exec($query);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SQL file execution error ({$sqlFile}): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение настройки плагина
     */
    public function getPluginSetting($pluginSlug, $settingKey, $default = null) {
        if (!$this->db) {
            return $default;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM plugin_settings WHERE plugin_slug = ? AND setting_key = ?");
            $stmt->execute([$pluginSlug, $settingKey]);
            $result = $stmt->fetch();
            
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Сохранение настройки плагина
     */
    public function setPluginSetting($pluginSlug, $settingKey, $value) {
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO plugin_settings (plugin_slug, setting_key, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            return $stmt->execute([$pluginSlug, $settingKey, $value]);
        } catch (Exception $e) {
            error_log("Error setting plugin setting: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка активности плагина
     */
    public function isPluginActive($pluginSlug) {
        return isset($this->plugins[$pluginSlug]);
    }
    
    /**
     * Получение конкретного плагина
     */
    public function getPlugin($pluginSlug) {
        return $this->plugins[$pluginSlug] ?? null;
    }
}

// Глобальные функции для удобства использования
function pluginManager() {
    return PluginManager::getInstance();
}

function addHook($hookName, $callback, $priority = 10) {
    return pluginManager()->addHook($hookName, $callback, $priority);
}

function doHook($hookName, $data = null) {
    return pluginManager()->doHook($hookName, $data);
}

function hasHook($hookName) {
    return pluginManager()->hasHook($hookName);
}
?>
