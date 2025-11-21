<?php
/**
 * Менеджер плагінів системи
 * 
 * @package Engine\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

class PluginManager extends BaseModule {
    private $plugins = [];
    private $hooks = [];
    private HookManager $hookManager;
    private $pluginsDir;
    
    protected function init(): void {
        $rootDir = dirname(__DIR__, 3);
        $this->pluginsDir = $rootDir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
        $this->hookManager = HookManager::getInstance();
    }
    
    public function registerHooks(): void {
    }
    
    /**
     * Отримання інформації про модуль
     */
    public function getInfo(): array {
        return [
            'name' => 'PluginManager',
            'title' => 'Менеджер плагінів',
            'description' => 'Управління плагінами системи',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Отримання API методів модуля
     */
    public function getApiMethods(): array {
        return [
            'getAllPlugins' => 'Отримання всіх плагінів',
            'getActivePlugins' => 'Отримання активних плагінів',
            'getPlugin' => 'Отримання плагіна за slug',
            'installPlugin' => 'Встановлення плагіна',
            'activatePlugin' => 'Активація плагіна',
            'deactivatePlugin' => 'Деактивація плагіна',
            'uninstallPlugin' => 'Видалення плагіна',
            'addHook' => 'Додавання хука',
            'doHook' => 'Виконання хука',
            'hasHook' => 'Перевірка існування хука',
            'autoDiscoverPlugins' => 'Автоматичне виявлення плагінів'
        ];
    }
    
    public function initializePlugins(): void {
        static $initialized = false;
        
        if ($initialized) {
            return;
        }
        
        $this->loadPlugins('handle_early_request');
        
        foreach ($this->plugins as $slug => $plugin) {
            static $initializedPlugins = [];
            if (isset($initializedPlugins[$slug])) {
                continue;
            }
            
            if (method_exists($plugin, 'init')) {
                try {
                    $plugin->init();
                    $initializedPlugins[$slug] = true;
                } catch (Exception $e) {
                    error_log("Plugin init error for {$slug}: " . $e->getMessage());
                }
            }
        }
        
        $initialized = true;
    }
    
    private function loadPlugins(?string $forHook = null): void {
        static $pluginsLoaded = false;
        static $hooksChecked = [];
        
        if ($pluginsLoaded) {
            return;
        }
        
        if ($forHook && !in_array($forHook, ['admin_menu', 'admin_register_routes', 'handle_early_request'])) {
            return;
        }
        
        if ($forHook && isset($hooksChecked[$forHook])) {
            return;
        }
        
        $db = $this->getDB();
        if (!$db) {
            return;
        }
        
        try {
            $cacheKey = 'active_plugins_list';
            
            if (function_exists('cache_remember')) {
                $pluginData = cache_remember($cacheKey, function() use ($db) {
                    $stmt = $db->query("SELECT slug FROM plugins WHERE is_active = 1");
                    return $stmt->fetchAll(PDO::FETCH_COLUMN);
                }, 300);
            } else {
                $stmt = $db->query("SELECT slug FROM plugins WHERE is_active = 1");
                $pluginData = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            foreach ($pluginData as $slug) {
                if (!isset($this->plugins[$slug])) {
                    $this->loadPluginFull($slug);
                }
            }
            
            $pluginsLoaded = true;
            if ($forHook) {
                $hooksChecked[$forHook] = true;
            }
        } catch (Exception $e) {
            error_log("Error loading plugins: " . $e->getMessage());
        }
    }
    
    private function loadPluginFull(string $slug): void {
        if (isset($this->plugins[$slug])) {
            return;
        }
        
        $db = $this->getDB();
        if (!$db) {
            return;
        }
        
        try {
            $cacheKey = 'plugin_data_' . $slug;
            
            if (function_exists('cache_remember')) {
                $pluginData = cache_remember($cacheKey, function() use ($db, $slug) {
                    $stmt = $db->prepare("SELECT * FROM plugins WHERE slug = ? AND is_active = 1");
                    $stmt->execute([$slug]);
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }, 300);
            } else {
                $stmt = $db->prepare("SELECT * FROM plugins WHERE slug = ? AND is_active = 1");
                $stmt->execute([$slug]);
                $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($pluginData) {
                $this->loadPlugin($pluginData);
            }
        } catch (Exception $e) {
            error_log("Error loading plugin full data for {$slug}: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання імені класу плагіна з slug
     */
    private function getPluginClassName(string $pluginSlug): string {
        $parts = explode('-', $pluginSlug);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        return $className . 'Plugin';
    }
    
    private function loadPlugin(array $pluginData): void {
        $slug = $pluginData['slug'] ?? '';
        if (empty($slug) || isset($this->plugins[$slug])) {
            return;
        }
        
        $pluginPath = $this->getPluginPath($slug);
        if (!file_exists($pluginPath) || !is_readable($pluginPath)) {
            return;
        }
        
        try {
            require_once $pluginPath;
            
            $className = $this->getPluginClassName($slug);
            if (class_exists($className)) {
                $this->plugins[$slug] = new $className();
            }
        } catch (Exception $e) {
            error_log("Error loading plugin {$slug}: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання шляху до файлу плагіна
     */
    private function getPluginPath(string $pluginSlug): string {
        $className = $this->getPluginClassName($pluginSlug);
        return $this->pluginsDir . $pluginSlug . '/' . $className . '.php';
    }
    
    private function getPluginInstance(string $pluginSlug) {
        if (isset($this->plugins[$pluginSlug])) {
            return $this->plugins[$pluginSlug];
        }
        
        $db = $this->getDB();
        if (!$db) {
            return null;
        }
        
        try {
            $stmt = $db->prepare("SELECT * FROM plugins WHERE slug = ?");
            $stmt->execute([$pluginSlug]);
            $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pluginData) {
                if (function_exists('cache_forget')) {
                    cache_forget('plugin_data_' . $pluginSlug);
                }
                
                $this->loadPlugin($pluginData);
                return $this->plugins[$pluginSlug] ?? null;
            }
        } catch (Exception $e) {
            error_log("Error loading plugin instance {$pluginSlug}: " . $e->getMessage());
        }
        
        return null;
    }
    
    public function addHook(string $hookName, callable $callback, int $priority = 10): void {
        $this->hookManager->addFilter($hookName, $callback, $priority);
        
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] - $b['priority']);
    }
    
    public function doHook(string $hookName, $data = null) {
        if (empty($hookName)) {
            return $data;
        }
        
        if (($hookName === 'admin_menu' || $hookName === 'admin_register_routes') && class_exists('ModuleLoader')) {
            static $adminModulesChecked = false;
            if (!$adminModulesChecked) {
                $this->loadAdminModules(false);
                $adminModulesChecked = true;
            }
        }
        
        if (!$this->hookManager->hasHook($hookName) && !isset($this->hooks[$hookName])) {
            if ($hookName === 'admin_menu' || $hookName === 'admin_register_routes' || $hookName === 'handle_early_request') {
                $this->loadPlugins($hookName);
                $this->initializePlugins();
            } else {
                return $data;
            }
        }
        
        if ($hookName === 'admin_register_routes') {
            if (isset($this->hooks[$hookName])) {
                foreach ($this->hooks[$hookName] as $hook) {
                    if (!is_callable($hook['callback'])) {
                        continue;
                    }
                    try {
                        call_user_func($hook['callback'], $data);
                    } catch (Exception $e) {
                        error_log("Hook execution error for '{$hookName}': " . $e->getMessage());
                    }
                }
            }
            $this->hookManager->doAction($hookName, $data);
            return $data;
        }
        
        return $this->hookManager->doHook($hookName, $data);
    }
    
    private function loadAdminModules(bool $forceLoadAll = false): void {
        static $adminModulesLoaded = false;
        static $allModulesLoaded = false;
        
        if ($forceLoadAll) {
            if ($allModulesLoaded) {
                $this->ensureModulesHooksRegistered();
                return;
            }
        } else {
            if ($adminModulesLoaded) {
                return;
            }
            
            $loadedModules = ModuleLoader::getLoadedModules();
            if (count($loadedModules) > 1) {
                $adminModulesLoaded = true;
                return;
            }
        }
        
        static $modulesList = null;
        
        if ($modulesList === null) {
            $managersDir = dirname(__DIR__) . '/managers';
            $modules = glob($managersDir . '/*.php');
            $modulesList = [];
            
            if ($modules !== false) {
                foreach ($modules as $moduleFile) {
                    $moduleName = basename($moduleFile, '.php');
                    
                    if ($moduleName === 'loader' || 
                        $moduleName === 'compatibility' || 
                        $moduleName === 'PluginManager') {
                        continue;
                    }
                    
                    $modulesList[] = $moduleName;
                }
            }
        }
        
        foreach ($modulesList as $moduleName) {
            if (!ModuleLoader::isModuleLoaded($moduleName)) {
                ModuleLoader::loadModule($moduleName);
            }
        }
        
        $this->ensureModulesHooksRegistered();
        
        if ($forceLoadAll) {
            $allModulesLoaded = true;
        } else {
            $adminModulesLoaded = true;
        }
    }
    
    private function ensureModulesHooksRegistered(): void {
        if (!class_exists('ModuleLoader')) {
            return;
        }
        
        $loadedModules = ModuleLoader::getLoadedModules();
        foreach ($loadedModules as $moduleName => $module) {
            if (is_object($module) && method_exists($module, 'registerHooks')) {
                $needsRegistration = false;
                
                if (!isset($this->hooks['admin_menu']) || !$this->hasModuleHook($moduleName, 'admin_menu')) {
                    $needsRegistration = true;
                }
                
                if (!isset($this->hooks['admin_register_routes']) || !$this->hasModuleHook($moduleName, 'admin_register_routes')) {
                    $needsRegistration = true;
                }
                
                if ($needsRegistration) {
                    try {
                        $module->registerHooks();
                    } catch (Exception $e) {
                        error_log("Error registering hooks for module {$moduleName}: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    private function hasModuleHook(string $moduleName, string $hookName): bool {
        if (!isset($this->hooks[$hookName])) {
            return false;
        }
        
        foreach ($this->hooks[$hookName] as $hook) {
            if (is_array($hook['callback']) && 
                isset($hook['callback'][0]) && 
                is_object($hook['callback'][0])) {
                $objectClass = get_class($hook['callback'][0]);
                if ($objectClass === $moduleName || strpos($objectClass, $moduleName) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function hasHook(string $hookName): bool {
        return $this->hookManager->hasHook($hookName) || 
               (!empty($hookName) && isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]));
    }
    
    public function getHookManager(): HookManager {
        return $this->hookManager;
    }
    
    public function getAllPlugins(): array {
        $allPlugins = [];
        
        if (!is_dir($this->pluginsDir)) {
            return $allPlugins;
        }
        
        $directories = glob($this->pluginsDir . '*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $pluginSlug = basename($dir);
            $configFile = $dir . '/plugin.json';
            $json = new Json($configFile);
            if ($json->getFilePath() && file_exists($json->getFilePath())) {
                try {
                    $json->load(true);
                    $config = $json->get([]);
                    
                    if (is_array($config) && !empty($config)) {
                        if (empty($config['slug'])) {
                            $config['slug'] = $pluginSlug;
                        }
                        
                        $pluginFile = new File($this->getPluginPath($pluginSlug));
                        $config['has_plugin_file'] = $pluginFile->exists();
                        $allPlugins[$pluginSlug] = $config;
                    } else {
                        error_log("Invalid JSON in plugin.json for plugin: {$pluginSlug}");
                    }
                } catch (Exception $e) {
                    error_log("Cannot read plugin.json for plugin {$pluginSlug}: " . $e->getMessage());
                }
            }
        }
        
        return $allPlugins;
    }
    
    public function autoDiscoverPlugins(): int {
        $allPlugins = $this->getAllPlugins();
        $installedCount = 0;
        
        $db = $this->getDB();
        if (!$db) {
            return 0;
        }
        
        try {
            $checkStmt = $db->query("SHOW COLUMNS FROM plugins LIKE 'is_deleted'");
            if ($checkStmt && $checkStmt->rowCount() > 0) {
                $db->exec("ALTER TABLE plugins DROP COLUMN is_deleted");
            }
        } catch (Exception $e) {
        }
        
        foreach ($allPlugins as $slug => $config) {
            try {
                $stmt = $db->prepare("SELECT id FROM plugins WHERE slug = ?");
                $stmt->execute([$slug]);
                
                if (!$stmt->fetch()) {
                    if ($this->installPlugin($slug)) {
                        $installedCount++;
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking plugin {$slug}: " . $e->getMessage());
            }
        }
        
        return $installedCount;
    }
    
    public function getActivePlugins(): array {
        $db = $this->getDB();
        if (!$db) {
            return [];
        }
        
        try {
            $stmt = $db->query("SELECT slug FROM plugins WHERE is_active = 1");
            $activeSlugs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $activePlugins = [];
            foreach ($activeSlugs as $slug) {
                if (isset($this->plugins[$slug])) {
                    $activePlugins[$slug] = $this->plugins[$slug];
                }
            }
            
            return $activePlugins;
        } catch (Exception $e) {
            if (function_exists('logger')) {
                logger()->logError('Error getting active plugins', ['error' => $e->getMessage()]);
            } else {
                error_log("Error getting active plugins: " . $e->getMessage());
            }
            return [];
        }
    }
    
    public function installPlugin(string $pluginSlug): bool {
        try {
            $pluginDir = $this->pluginsDir . $pluginSlug;
            
            if (!is_dir($pluginDir)) {
                return false;
            }
            
            $configFile = $pluginDir . '/plugin.json';
            if (!file_exists($configFile) || !is_readable($configFile)) {
                return false;
            }
            
            $json = new Json($configFile);
            try {
                $json->load(true);
                $config = $json->get([]);
                
                if (!is_array($config) || empty($config)) {
                    error_log("Invalid JSON in plugin.json: {$configFile}");
                    return false;
                }
            } catch (Exception $e) {
                error_log("Cannot read plugin.json: {$configFile} - " . $e->getMessage());
                return false;
            }
            
            $this->executeDatabaseFiles($pluginDir . '/db');
            $pluginSlug = $config['slug'] ?? $pluginSlug;
            $db = $this->getDB();
            if (!$db) {
                return false;
            }
            
            $stmt = $db->prepare("
                INSERT INTO plugins (name, slug, description, version, author, is_active) 
                VALUES (?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                description = VALUES(description), 
                version = VALUES(version),
                author = VALUES(author)
            ");
            
            if ($stmt->execute([
                $config['name'] ?? $pluginSlug,
                $pluginSlug,
                $config['description'] ?? '',
                $config['version'] ?? '1.0.0',
                    $config['author'] ?? ''
                ])) {
                if (function_exists('cache_forget')) {
                    cache_forget('active_plugins');
                    cache_forget('active_plugins_hash');
                }
                $this->clearMenuCache();
                doHook('plugin_installed', $pluginSlug);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Plugin installation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    public function uninstallPlugin(string $pluginSlug): bool {
        try {
            $db = $this->getDB();
            if (!$db) {
                return false;
            }
            
            // Отримуємо дані плагіна з БД
            $stmt = $db->prepare("SELECT * FROM plugins WHERE slug = ?");
            $stmt->execute([$pluginSlug]);
            $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pluginData) {
                return false;
            }
            
            if (!empty($pluginData['is_active']) && $pluginData['is_active'] == 1) {
                return false;
            }
            
            $plugin = $this->getPlugin($pluginSlug);
            if ($plugin && method_exists($plugin, 'uninstall')) {
                $plugin->uninstall();
            }
            
            $stmt = $db->prepare("DELETE FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute([$pluginSlug]);
            
            $stmt = $db->prepare("DELETE FROM plugins WHERE slug = ?");
            if ($stmt->execute([$pluginSlug])) {
                // Видаляємо файли плагіна
                $pluginDir = $this->pluginsDir . $pluginSlug;
                if (is_dir($pluginDir)) {
                    $this->deletePluginDirectory($pluginDir);
                }
                
                if (function_exists('cache_forget')) {
                    cache_forget('active_plugins');
                    cache_forget('active_plugins_hash');
                    cache_forget('active_plugins_list');
                    cache_forget('plugin_data_' . $pluginSlug);
                }
                
                $this->clearAllMenuCache();
                doHook('plugin_uninstalled', $pluginSlug);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Plugin uninstallation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function deletePluginDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deletePluginDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
    
    public function activatePlugin(string $pluginSlug): bool {
        try {
            $db = $this->getDB();
            if (!$db) {
                return false;
            }
            
            $stmt = $db->prepare("UPDATE plugins SET is_active = 1 WHERE slug = ?");
            if (!$stmt->execute([$pluginSlug])) {
                return false;
            }
            
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins');
                cache_forget('active_plugins_hash');
                cache_forget('active_plugins_list');
                cache_forget('plugin_data_' . $pluginSlug);
            }
            
            $this->clearPluginCache($pluginSlug);
            $plugin = $this->getPluginInstance($pluginSlug);
            
            if ($plugin) {
                if (method_exists($plugin, 'activate')) {
                    $plugin->activate();
                }
                
                if (method_exists($plugin, 'init')) {
                    try {
                        $plugin->init();
                    } catch (Exception $e) {
                        error_log("Plugin init error for {$pluginSlug} after activation: " . $e->getMessage());
                    }
                }
            }
            
            $this->clearPluginCache($pluginSlug);
            
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins_hash');
            }
            $this->clearAllMenuCache();
            
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins_hash');
            }
            
            doHook('plugin_activated', $pluginSlug);
            
            return true;
        } catch (Exception $e) {
            error_log("Plugin activation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deactivatePlugin(string $pluginSlug): bool {
        try {
            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin && method_exists($plugin, 'deactivate')) {
                $plugin->deactivate();
            }
            
            $db = $this->getDB();
            if (!$db) {
                return false;
            }
            
            $stmt = $db->prepare("UPDATE plugins SET is_active = 0 WHERE slug = ?");
            if (!$stmt->execute([$pluginSlug])) {
                return false;
            }
            
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins');
                cache_forget('active_plugins_hash');
                cache_forget('active_plugins_list');
                cache_forget('plugin_data_' . $pluginSlug);
            }
            
            $this->removePluginHooks($pluginSlug);
            
            if (isset($this->plugins[$pluginSlug])) {
                unset($this->plugins[$pluginSlug]);
            }
            
            $this->clearPluginCache($pluginSlug);
            
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins_hash');
            }
            
            $this->clearAllMenuCache();
            
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins_hash');
            }
            
            doHook('plugin_deactivated', $pluginSlug);
            
            return true;
        } catch (Exception $e) {
            error_log("Plugin deactivation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Перевірка активності плагіна
     */
    public function isPluginActive(string $pluginSlug): bool {
        return !empty($pluginSlug) && isset($this->plugins[$pluginSlug]);
    }
    
    /**
     * Отримання конкретного плагіна
     */
    public function getPlugin(string $pluginSlug) {
        return $this->plugins[$pluginSlug] ?? $this->getPluginInstance($pluginSlug);
    }
    
    public function getPluginSetting(string $pluginSlug, string $settingKey, $default = null) {
        $db = $this->getDB();
        if (!$db) {
            return $default;
        }
        
        try {
            $stmt = $db->prepare("SELECT setting_value FROM plugin_settings WHERE plugin_slug = ? AND setting_key = ?");
            $stmt->execute([$pluginSlug, $settingKey]);
            $result = $stmt->fetch();
            
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    public function setPluginSetting(string $pluginSlug, string $settingKey, $value): bool {
        $db = $this->getDB();
        if (!$db) {
            return false;
        }
        
        try {
            $stmt = $db->prepare("
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
    
    private function executeDatabaseFiles(string $dbDir): void {
        if (!is_dir($dbDir)) {
            return;
        }
        
        $sqlFiles = [];
        if (file_exists($dbDir . '/install.sql')) {
            $sqlFiles[] = $dbDir . '/install.sql';
        } else {
            $files = glob($dbDir . '/*.sql');
            $sqlFiles = array_merge($sqlFiles, $files ?: []);
        }
        
        foreach ($sqlFiles as $sqlFile) {
            $this->executeSqlFile($sqlFile);
        }
    }
    
    private function executeSqlFile(string $sqlFile): bool {
        $db = $this->getDB();
        if (!$db) {
            return false;
        }
        
        $file = new File($sqlFile);
        if (!$file->exists()) {
            return false;
        }
        
        try {
            $sql = $file->read();
            // Видаляємо коментарі
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $db->exec($query);
                    } catch (PDOException $e) {
                        // Ігноруємо помилки "Duplicate column" та "Duplicate key"
                        if (strpos($e->getMessage(), 'Duplicate column') === false && 
                            strpos($e->getMessage(), 'Duplicate key') === false &&
                            strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SQL file execution error ({$sqlFile}): " . $e->getMessage());
            return false;
        }
    }
    
    private function clearMenuCache(): void {
        if (function_exists('cache_forget')) {
            cache_forget('active_plugins_hash');
        }
        
        $activePlugins = $this->getActivePlugins();
        $pluginsHash = md5(implode(',', array_keys($activePlugins)));
        
        for ($custom = 0; $custom <= 1; $custom++) {
            for ($nav = 0; $nav <= 1; $nav++) {
                $key = 'admin_menu_items_' . $custom . '_' . $nav . '_' . $pluginsHash;
                if (function_exists('cache_forget')) {
                    cache_forget($key);
                }
            }
        }
        
        $oldPatterns = [
            'admin_menu_items_0',
            'admin_menu_items_1',
            'admin_menu_items_0_0',
            'admin_menu_items_0_1',
            'admin_menu_items_1_0',
            'admin_menu_items_1_1'
        ];
        foreach ($oldPatterns as $pattern) {
            if (function_exists('cache_forget')) {
                cache_forget($pattern);
            }
        }
        
        $this->clearMenuFileCache();
    }
    
    private function clearMenuFileCache(): void {
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . 'admin_menu_items_*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            
            $hashFiles = glob($cacheDir . 'active_plugins_hash*.cache');
            if ($hashFiles !== false) {
                foreach ($hashFiles as $file) {
                    @unlink($file);
                }
            }
        }
    }
    
    public function clearAllMenuCache(): void {
        if (function_exists('cache_forget')) {
            cache_forget('active_plugins_hash');
        }
        
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . 'admin_menu_items_*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            
            $hashFiles = glob($cacheDir . 'active_plugins_hash*.cache');
            if ($hashFiles !== false) {
                foreach ($hashFiles as $file) {
                    @unlink($file);
                }
            }
        }
        
        $oldPatterns = [
            'admin_menu_items_0',
            'admin_menu_items_1',
            'admin_menu_items_0_0',
            'admin_menu_items_0_1',
            'admin_menu_items_1_0',
            'admin_menu_items_1_1'
        ];
        foreach ($oldPatterns as $pattern) {
            if (function_exists('cache_forget')) {
                cache_forget($pattern);
            }
        }
        
        $this->clearMenuFileCache();
    }
    
    private function removePluginHooks(string $pluginSlug): void {
        if (empty($pluginSlug)) {
            return;
        }
        
        $className = $this->getPluginClassName($pluginSlug);
        $allHooks = $this->hookManager->getAllHooks();
        
        foreach ($allHooks as $hookName => $hooks) {
            foreach ($hooks as $hook) {
                $callback = $hook['callback'] ?? null;
                if ($callback === null) {
                    continue;
                }
                
                if (is_array($callback)) {
                    if (isset($callback[0]) && is_object($callback[0])) {
                        $objectClass = get_class($callback[0]);
                        if ($objectClass === $className || strpos($objectClass, $pluginSlug) !== false) {
                            $this->hookManager->removeHook($hookName, $callback);
                        }
                    }
                } elseif (is_string($callback) && (strpos($callback, $className) !== false || strpos($callback, $pluginSlug) !== false)) {
                    $this->hookManager->removeHook($hookName, $callback);
                }
            }
        }
        
        foreach ($this->hooks as $hookName => $hooks) {
            $filteredHooks = array_filter($hooks, function($hook) use ($className, $pluginSlug) {
                if (is_array($hook['callback'])) {
                    if (isset($hook['callback'][0])) {
                        $object = $hook['callback'][0];
                        if (is_object($object)) {
                            $objectClass = get_class($object);
                            if ($objectClass === $className || strpos($objectClass, $pluginSlug) !== false) {
                                return false;
                            }
                        }
                    }
                }
                return true;
            });
            
            $this->hooks[$hookName] = array_values($filteredHooks);
            
            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
    }
    
    private function clearPluginCache(string $pluginSlug): void {
        if (empty($pluginSlug)) {
            return;
        }
        
        if (function_exists('cache_forget')) {
            cache_forget('plugin_data_' . $pluginSlug);
        }
        
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        if (is_dir($cacheDir)) {
            $pluginCacheFiles = glob($cacheDir . 'plugin_' . $pluginSlug . '_*.cache');
            if ($pluginCacheFiles !== false) {
                foreach ($pluginCacheFiles as $file) {
                    @unlink($file);
                }
            }
            
            $pluginCacheFiles2 = glob($cacheDir . $pluginSlug . '_*.cache');
            if ($pluginCacheFiles2 !== false) {
                foreach ($pluginCacheFiles2 as $file) {
                    @unlink($file);
                }
            }
        }
    }
}

function addHook(string $hookName, callable $callback, int $priority = 10): void {
    pluginManager()->addHook($hookName, $callback, $priority);
}

function doHook(string $hookName, $data = null) {
    return pluginManager()->doHook($hookName, $data);
}

function hasHook(string $hookName): bool {
    return pluginManager()->hasHook($hookName);
}

function addFilter(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
    pluginManager()->getHookManager()->addFilter($hookName, $callback, $priority, $condition);
}

function applyFilter(string $hookName, $data = null, ...$args) {
    return pluginManager()->getHookManager()->applyFilter($hookName, $data, ...$args);
}

function addAction(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
    pluginManager()->getHookManager()->addAction($hookName, $callback, $priority, $condition);
}

function doAction(string $hookName, ...$args): void {
    pluginManager()->getHookManager()->doAction($hookName, ...$args);
}

function removeHook(string $hookName, ?callable $callback = null): bool {
    return pluginManager()->getHookManager()->removeHook($hookName, $callback);
}

function hookManager(): HookManager {
    return HookManager::getInstance();
}

