<?php
/**
 * Модуль управления плагинами
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

// BaseModule теперь загружается через автозагрузчик из base/BaseModule.php

class PluginManager extends BaseModule {
    private $plugins = [];
    private $hooks = [];
    private $pluginsDir;
    
    /**
     * Инициализация модуля
     */
    protected function init(): void {
        $this->pluginsDir = dirname(__DIR__, 2) . '/plugins/';
        $this->loadPlugins();
    }
    
    /**
     * Регистрация хуков модуля
     */
    public function registerHooks(): void {
        // Модуль PluginManager не регистрирует хуки, он сам управляет хуками
    }
    
    /**
     * Получение информации о модуле
     */
    public function getInfo(): array {
        return [
            'name' => 'PluginManager',
            'title' => 'Менеджер плагинов',
            'description' => 'Управление плагинами системы',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Получение API методов модуля
     */
    public function getApiMethods(): array {
        return [
            'getAllPlugins' => 'Получение всех плагинов',
            'getActivePlugins' => 'Получение активных плагинов',
            'getPlugin' => 'Получение плагина по slug',
            'installPlugin' => 'Установка плагина',
            'activatePlugin' => 'Активация плагина',
            'deactivatePlugin' => 'Деактивация плагина',
            'uninstallPlugin' => 'Удаление плагина',
            'addHook' => 'Добавление хука',
            'doHook' => 'Выполнение хука',
            'hasHook' => 'Проверка существования хука',
            'autoDiscoverPlugins' => 'Автоматическое обнаружение плагинов'
        ];
    }
    
    /**
     * Ручная инициализация плагинов
     */
    public function initializePlugins(): void {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'init')) {
                try {
                    $plugin->init();
                } catch (Exception $e) {
                    error_log("Plugin init error: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Загрузка активных плагинов
     */
    private function loadPlugins(): void {
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
     * Получение имени класса плагина из slug
     */
    private function getPluginClassName(string $pluginSlug): string {
        $parts = explode('-', $pluginSlug);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        return $className . 'Plugin';
    }
    
    /**
     * Загрузка конкретного плагина
     */
    private function loadPlugin(array $pluginData): void {
        $slug = $pluginData['slug'] ?? '';
        if (empty($slug)) {
            return;
        }
        
        $pluginPath = $this->getPluginPath($slug);
        if (!file_exists($pluginPath) || !is_readable($pluginPath)) {
            error_log("Plugin file not found or not readable: {$pluginPath}");
            return;
        }
        
        try {
            require_once $pluginPath;
            
            $className = $this->getPluginClassName($slug);
            if (class_exists($className)) {
                $plugin = new $className();
                $this->plugins[$slug] = $plugin;
                
                // Инициализируем плагин, чтобы зарегистрировать хуки
                if (method_exists($plugin, 'init')) {
                    try {
                        $plugin->init();
                    } catch (Exception $e) {
                        error_log("Plugin init error for {$slug}: " . $e->getMessage());
                    }
                }
            } else {
                error_log("Plugin class {$className} not found for plugin: {$slug}");
            }
        } catch (Exception $e) {
            error_log("Error loading plugin {$slug}: " . $e->getMessage());
        }
    }
    
    /**
     * Получение пути к файлу плагина
     */
    private function getPluginPath(string $pluginSlug): string {
        $className = $this->getPluginClassName($pluginSlug);
        return $this->pluginsDir . $pluginSlug . '/' . $className . '.php';
    }
    
    /**
     * Получение экземпляра плагина (загружает если нужно)
     */
    private function getPluginInstance(string $pluginSlug) {
        // Если плагин уже загружен, возвращаем его
        if (isset($this->plugins[$pluginSlug])) {
            return $this->plugins[$pluginSlug];
        }
        
        // Пытаемся загрузить плагин из БД
        try {
            $stmt = $this->db->prepare("SELECT * FROM plugins WHERE slug = ?");
            $stmt->execute([$pluginSlug]);
            $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pluginData) {
                $this->loadPlugin($pluginData);
                return $this->plugins[$pluginSlug] ?? null;
            }
        } catch (Exception $e) {
            error_log("Error loading plugin instance {$pluginSlug}: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Добавление хука
     */
    public function addHook(string $hookName, callable $callback, int $priority = 10): void {
        if (empty($hookName)) {
            return;
        }
        
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        // Сортируем по приоритету
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] - $b['priority']);
    }
    
    /**
     * Выполнение хука
     */
    public function doHook(string $hookName, $data = null) {
        if (empty($hookName)) {
            return $data;
        }
        
        // Для admin_menu всегда загружаем модули ДО проверки хуков
        // Это гарантирует, что модули зарегистрируют свои хуки независимо от порядка загрузки плагинов
        if ($hookName === 'admin_menu' && class_exists('ModuleLoader')) {
            $this->loadAdminModules(true);
        }
        
        // Проверяем наличие хуков после загрузки модулей
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }
        
        // Определяем тип хука: объектные хуки (admin_register_routes) vs данные (admin_menu)
        $isObjectHook = ($hookName === 'admin_register_routes');
        
        foreach ($this->hooks[$hookName] as $hook) {
            if (!is_callable($hook['callback'])) {
                continue;
            }
            
            try {
                $result = call_user_func($hook['callback'], $data);
                
                // Для не-объектных хуков используем результат как обновленные данные
                if (!$isObjectHook && $result !== null) {
                    $data = $result;
                }
            } catch (Exception $e) {
                error_log("Hook execution error for '{$hookName}': " . $e->getMessage());
            } catch (Error $e) {
                error_log("Fatal error in hook '{$hookName}': " . $e->getMessage());
            }
        }
        
        return $data;
    }
    
    /**
     * Загрузка модулей, которые регистрируют хуки для админки
     * 
     * @param bool $forceLoadAll Если true, загружает все модули независимо от уже загруженных
     */
    private function loadAdminModules(bool $forceLoadAll = false): void {
        static $adminModulesLoaded = false;
        static $allModulesLoaded = false;
        
        // Для admin_menu всегда загружаем все модули (но только один раз в рамках одного запроса)
        if ($forceLoadAll) {
            if ($allModulesLoaded) {
                // Модули уже загружены в этом запросе, но проверяем, что хуки зарегистрированы
                // Это важно на случай, если модули были загружены до инициализации PluginManager
                $this->ensureModulesHooksRegistered();
                return;
            }
        } else {
            // Для admin_register_routes загружаем только если нужно
            if ($adminModulesLoaded) {
                return; // Уже загружены
            }
            
            // Определяем, нужна ли загрузка всех модулей
            // Если уже есть загруженные модули (кроме PluginManager), значит они загружены по требованию
            $loadedModules = ModuleLoader::getLoadedModules();
            $hasOtherModules = count($loadedModules) > 1; // Больше чем только PluginManager
            
            // Если модули уже загружены по требованию, не загружаем все остальные
            // Это позволяет загружать только нужные модули для конкретной страницы
            if ($hasOtherModules) {
                $adminModulesLoaded = true;
                return;
            }
        }
        
        // Загружаем только модули, которые регистрируют хуки для админки
        $modulesDir = dirname(__DIR__) . '/modules';
        $modules = glob($modulesDir . '/*.php');
        
        if ($modules !== false) {
            foreach ($modules as $moduleFile) {
                $moduleName = basename($moduleFile, '.php');
                
                // Пропускаем служебные файлы
                if ($moduleName === 'loader' || 
                    $moduleName === 'compatibility' || 
                    $moduleName === 'PluginManager') {
                    continue;
                }
                
                // Загружаем модуль, если он еще не загружен
                if (!ModuleLoader::isModuleLoaded($moduleName)) {
                    ModuleLoader::loadModule($moduleName);
                }
            }
        }
        
        // Убеждаемся, что хуки всех модулей зарегистрированы
        $this->ensureModulesHooksRegistered();
        
        if ($forceLoadAll) {
            $allModulesLoaded = true;
        } else {
            $adminModulesLoaded = true;
        }
    }
    
    /**
     * Убеждается, что хуки всех загруженных модулей зарегистрированы
     */
    private function ensureModulesHooksRegistered(): void {
        if (!class_exists('ModuleLoader')) {
            return;
        }
        
        $loadedModules = ModuleLoader::getLoadedModules();
        foreach ($loadedModules as $moduleName => $module) {
            if (is_object($module) && method_exists($module, 'registerHooks')) {
                // Проверяем, зарегистрированы ли хуки для admin_menu
                // Если нет, вызываем registerHooks() еще раз
                // Это безопасно, так как addHook() просто добавляет хуки, а не заменяет их
                if (!isset($this->hooks['admin_menu']) || 
                    !$this->hasModuleHook($moduleName, 'admin_menu')) {
                    try {
                        $module->registerHooks();
                    } catch (Exception $e) {
                        error_log("Error registering hooks for module {$moduleName}: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Проверяет, зарегистрирован ли хук для конкретного модуля
     */
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
    
    /**
     * Проверка существования хука
     */
    public function hasHook(string $hookName): bool {
        return !empty($hookName) && isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }
    
    /**
     * Получение всех плагинов (из файловой системы)
     * Автоматически обнаруживает плагины по наличию plugin.json
     */
    public function getAllPlugins(): array {
        $allPlugins = [];
        
        if (!is_dir($this->pluginsDir)) {
            return $allPlugins;
        }
        
        $directories = glob($this->pluginsDir . '*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $pluginSlug = basename($dir);
            $configFile = $dir . '/plugin.json';
            
            if (file_exists($configFile) && is_readable($configFile)) {
                $configContent = @file_get_contents($configFile);
                if ($configContent === false) {
                    error_log("Cannot read plugin.json for plugin: {$pluginSlug}");
                    continue;
                }
                
                $config = json_decode($configContent, true);
                if ($config && is_array($config)) {
                    if (empty($config['slug'])) {
                        $config['slug'] = $pluginSlug;
                    }
                    
                    $config['has_plugin_file'] = file_exists($this->getPluginPath($pluginSlug));
                    $allPlugins[$pluginSlug] = $config;
                } else {
                    error_log("Invalid JSON in plugin.json for plugin: {$pluginSlug}");
                }
            }
        }
        
        return $allPlugins;
    }
    
    /**
     * Автоматическое обнаружение и установка новых плагинов
     */
    public function autoDiscoverPlugins(): int {
        $allPlugins = $this->getAllPlugins();
        $installedCount = 0;
        
        foreach ($allPlugins as $slug => $config) {
            try {
                $stmt = $this->db->prepare("SELECT id FROM plugins WHERE slug = ?");
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
    
    /**
     * Получение активных плагинов
     */
    public function getActivePlugins(): array {
        return $this->plugins;
    }
    
    /**
     * Установка плагина
     */
    public function installPlugin(string $pluginSlug): bool {
        try {
            $pluginDir = $this->pluginsDir . $pluginSlug;
            if (!is_dir($pluginDir)) {
                error_log("Plugin directory not found: {$pluginDir}");
                return false;
            }
            
            $configFile = $pluginDir . '/plugin.json';
            if (!file_exists($configFile) || !is_readable($configFile)) {
                error_log("plugin.json not found or not readable: {$configFile}");
                return false;
            }
            
            $configContent = file_get_contents($configFile);
            if ($configContent === false) {
                error_log("Cannot read plugin.json: {$configFile}");
                return false;
            }
            
            $config = json_decode($configContent, true);
            if (!$config || !is_array($config)) {
                error_log("Invalid JSON in plugin.json: {$configFile}");
                return false;
            }
            
            // Выполняем SQL файлы из папки db
            $this->executeDatabaseFiles($pluginDir . '/db');
            
            // Используем slug из конфига или из имени директории
            $pluginSlug = $config['slug'] ?? $pluginSlug;
            
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
            
            if ($stmt->execute([
                $config['name'] ?? $pluginSlug,
                $pluginSlug,
                $config['description'] ?? '',
                $config['version'] ?? '1.0.0',
                $config['author'] ?? ''
            ])) {
                cache_forget('active_plugins');
                // Логируем установку плагина
                doHook('plugin_installed', $pluginSlug);
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
    public function uninstallPlugin(string $pluginSlug): bool {
        try {
            // Получаем данные плагина из БД
            $stmt = $this->db->prepare("SELECT * FROM plugins WHERE slug = ?");
            $stmt->execute([$pluginSlug]);
            $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pluginData) {
                return false;
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
            if ($stmt->execute([$pluginSlug])) {
                cache_forget('active_plugins');
                // Логируем удаление плагина
                doHook('plugin_uninstalled', $pluginSlug);
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
    public function activatePlugin(string $pluginSlug): bool {
        try {
            $stmt = $this->db->prepare("UPDATE plugins SET is_active = 1 WHERE slug = ?");
            if (!$stmt->execute([$pluginSlug])) {
                return false;
            }
            
            cache_forget('active_plugins');
            cache_forget('active_plugins_hash'); // Очищаем хеш плагинов при активации
            
            // Загружаем и активируем плагин
            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin) {
                // Вызываем activate() для логики активации
                if (method_exists($plugin, 'activate')) {
                    $plugin->activate();
                }
                // init() уже вызывается в loadPlugin(), не нужно вызывать повторно
            }
            
            // Очищаем кеш меню после активации плагина
            $this->clearMenuCache();
            
            // Логируем активацию плагина
            doHook('plugin_activated', $pluginSlug);
            
            return true;
        } catch (Exception $e) {
            error_log("Plugin activation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivatePlugin(string $pluginSlug): bool {
        try {
            // Вызываем метод деактивации плагина
            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin && method_exists($plugin, 'deactivate')) {
                $plugin->deactivate();
            }
            
            $stmt = $this->db->prepare("UPDATE plugins SET is_active = 0 WHERE slug = ?");
            if (!$stmt->execute([$pluginSlug])) {
                return false;
            }
            
            cache_forget('active_plugins');
            cache_forget('active_plugins_hash'); // Очищаем хеш плагинов при деактивации
            unset($this->plugins[$pluginSlug]);
            
            // Очищаем кеш меню после изменения плагинов
            $this->clearMenuCache();
            
            // Логируем деактивацию плагина
            doHook('plugin_deactivated', $pluginSlug);
            
            return true;
        } catch (Exception $e) {
            error_log("Plugin deactivation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка активности плагина
     */
    public function isPluginActive(string $pluginSlug): bool {
        return !empty($pluginSlug) && isset($this->plugins[$pluginSlug]);
    }
    
    /**
     * Получение конкретного плагина
     */
    public function getPlugin(string $pluginSlug) {
        return $this->plugins[$pluginSlug] ?? $this->getPluginInstance($pluginSlug);
    }
    
    /**
     * Получение настройки плагина
     */
    public function getPluginSetting(string $pluginSlug, string $settingKey, $default = null) {
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
    public function setPluginSetting(string $pluginSlug, string $settingKey, $value): bool {
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
     * Выполнение SQL файлов из директории db плагина
     */
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
    
    /**
     * Выполнение SQL файла
     */
    private function executeSqlFile(string $sqlFile): bool {
        if (!file_exists($sqlFile) || !$this->db) {
            return false;
        }
        
        try {
            $sql = file_get_contents($sqlFile);
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
     * Очистка кеша меню администратора
     * Очищает все возможные комбинации ключей кеша меню
     */
    private function clearMenuCache(): void {
        $activePlugins = $this->getActivePlugins();
        $pluginsHash = md5(implode(',', array_keys($activePlugins)));
        
        // Очищаем все возможные комбинации поддержки темы
        for ($custom = 0; $custom <= 1; $custom++) {
            for ($nav = 0; $nav <= 1; $nav++) {
                $key = 'admin_menu_items_' . $custom . '_' . $nav . '_' . $pluginsHash;
                cache_forget($key);
            }
        }
    }
}

/**
 * Глобальная функция для получения экземпляра модуля PluginManager
 */
function pluginManager() {
    return PluginManager::getInstance();
}

/**
 * Глобальные функции для работы с хуками
 */
function addHook(string $hookName, callable $callback, int $priority = 10): void {
    pluginManager()->addHook($hookName, $callback, $priority);
}

function doHook(string $hookName, $data = null) {
    return pluginManager()->doHook($hookName, $data);
}

function hasHook(string $hookName): bool {
    return pluginManager()->hasHook($hookName);
}

