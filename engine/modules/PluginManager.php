<?php
/**
 * Модуль керування плагінами
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

// BaseModule тепер завантажується через автозавантажувач з base/BaseModule.php

class PluginManager extends BaseModule {
    private $plugins = [];
    private $hooks = []; // Для обратной совместимости
    private HookManager $hookManager; // Новый менеджер хуков
    private $pluginsDir;
    
    /**
     * Ініціалізація модуля
     */
    protected function init(): void {
        $this->pluginsDir = dirname(__DIR__, 2) . '/plugins/';
        // Инициализируем HookManager
        $this->hookManager = HookManager::getInstance();
        // НЕ завантажуємо плагіни при ініціалізації - тільки коли потрібно (lazy loading)
    }
    
    /**
     * Реєстрація хуків модуля
     */
    public function registerHooks(): void {
        // Модуль PluginManager не реєструє хуки, він сам керує хуками
    }
    
    /**
     * Отримання інформації про модуль
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
    
    /**
     * Ручна ініціалізація плагінів (викликається тільки один раз)
     */
    public function initializePlugins(): void {
        static $initialized = false;
        
        if ($initialized) {
            return; // Вже ініціалізовано
        }
        
        // Завантажуємо плагіни якщо ще не завантажені
        $this->loadPlugins('handle_early_request');
        
        // Ініціалізуємо тільки ще не ініціалізовані плагіни
        foreach ($this->plugins as $slug => $plugin) {
            // Перевіряємо, чи плагін вже ініціалізований (через статичну змінну)
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
    
    /**
     * Завантаження активних плагінів (lazy loading)
     * Завантажує тільки для конкретного хука, якщо потрібно
     */
    private function loadPlugins(?string $forHook = null): void {
        static $pluginsLoaded = false;
        static $hooksChecked = [];
        
        // Якщо плагіни вже завантажені, не завантажуємо знову
        if ($pluginsLoaded) {
            return;
        }
        
        // Якщо це не admin хуки, не завантажуємо плагіни
        if ($forHook && !in_array($forHook, ['admin_menu', 'admin_register_routes', 'handle_early_request'])) {
            return;
        }
        
        // Перевіряємо, чи вже перевіряли цей хук
        if ($forHook && isset($hooksChecked[$forHook])) {
            return;
        }
        
        $db = $this->getDB();
        if (!$db) {
            return;
        }
        
        try {
            // Кешуємо список активних плагінів
            $cacheKey = 'active_plugins_list';
            $pluginData = null;
            
            if (function_exists('cache_remember')) {
                $pluginData = cache_remember($cacheKey, function() use ($db) {
                    $stmt = $db->query("SELECT slug FROM plugins WHERE is_active = 1");
                    return $stmt->fetchAll(PDO::FETCH_COLUMN);
                }, 300); // Кеш на 5 хвилин
            } else {
                $stmt = $db->query("SELECT slug FROM plugins WHERE is_active = 1");
                $pluginData = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // Завантажуємо плагіни тільки якщо вони ще не завантажені
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
    
    /**
     * Завантаження повних даних плагіна (з кешуванням метаданих)
     */
    private function loadPluginFull(string $slug): void {
        // Перевіряємо, чи плагін вже завантажений
        if (isset($this->plugins[$slug])) {
            return;
        }
        
        $db = $this->getDB();
        if (!$db) {
            return;
        }
        
        try {
            // Кешуємо метадані плагіна
            $cacheKey = 'plugin_data_' . $slug;
            $pluginData = null;
            
            if (function_exists('cache_remember')) {
                $pluginData = cache_remember($cacheKey, function() use ($db, $slug) {
                    $stmt = $db->prepare("SELECT * FROM plugins WHERE slug = ? AND is_active = 1");
                    $stmt->execute([$slug]);
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }, 300); // Кеш на 5 хвилин
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
    
    /**
     * Завантаження конкретного плагіна (без виклику init - він викликається окремо)
     */
    private function loadPlugin(array $pluginData): void {
        $slug = $pluginData['slug'] ?? '';
        if (empty($slug)) {
            return;
        }
        
        // Перевіряємо, чи плагін вже завантажений
        if (isset($this->plugins[$slug])) {
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
                
                // НЕ викликаємо init() тут - він викликається окремо через initializePlugins()
                // Це дозволяє контролювати, коли саме ініціалізувати плагіни
            } else {
                error_log("Plugin class {$className} not found for plugin: {$slug}");
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
    
    /**
     * Отримання екземпляра плагіна (завантажує якщо потрібно)
     */
    private function getPluginInstance(string $pluginSlug) {
        // Якщо плагін вже завантажено, повертаємо його
        if (isset($this->plugins[$pluginSlug])) {
            return $this->plugins[$pluginSlug];
        }
        
        // Намагаємося завантажити плагін з БД
        $db = $this->getDB();
        if (!$db) {
            return null;
        }
        
        try {
            // При активации плагин уже активен в БД, но кеш может быть старым
            // Поэтому не используем кеш и загружаем напрямую из БД
            $stmt = $db->prepare("SELECT * FROM plugins WHERE slug = ?");
            $stmt->execute([$pluginSlug]);
            $pluginData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pluginData) {
                // Очищаем кеш метаданных плагина перед загрузкой
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
    
    /**
     * Додавання хука (использует HookManager)
     */
    public function addHook(string $hookName, callable $callback, int $priority = 10): void {
        // Используем HookManager для добавления хука
        // По умолчанию добавляем как фильтр (для обратной совместимости)
        $this->hookManager->addFilter($hookName, $callback, $priority);
        
        // Сохраняем для обратной совместимости
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] - $b['priority']);
    }
    
    /**
     * Виконання хука (использует HookManager с сохранением логики загрузки плагинов)
     */
    public function doHook(string $hookName, $data = null) {
        if (empty($hookName)) {
            return $data;
        }
        
        // Для admin_menu та admin_register_routes завантажуємо модулі тільки якщо потрібно
        // НЕ завантажуємо всі модулі при кожному виклику
        if (($hookName === 'admin_menu' || $hookName === 'admin_register_routes') && class_exists('ModuleLoader')) {
            static $adminModulesChecked = false;
            if (!$adminModulesChecked) {
                $this->loadAdminModules(false); // Не форсуємо завантаження всіх
                $adminModulesChecked = true;
            }
        }
        
        // Завантажуємо плагіни тільки для admin хуків і тільки один раз
        // Для theme хуків завантажуємо модулі, щоб вони могли зареєструвати хуки
        if (!$this->hookManager->hasHook($hookName) && !isset($this->hooks[$hookName])) {
            // Якщо це admin хуки, завантажуємо плагіни щоб вони могли зареєструвати хуки
            if ($hookName === 'admin_menu' || $hookName === 'admin_register_routes' || $hookName === 'handle_early_request') {
                $this->loadPlugins($hookName);
                // Ініціалізуємо плагіни тільки один раз
                $this->initializePlugins();
            } else {
                return $data;
            }
        }
        
        // Используем HookManager для выполнения хука
        // Для admin_register_routes передаем объект напрямую
        if ($hookName === 'admin_register_routes') {
            // Для этого хука используем старую логику, так как он передает объект
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
            return $data;
        }
        
        // Для остальных хуков используем HookManager
        return $this->hookManager->doHook($hookName, $data);
    }
    
    /**
     * Завантаження модулів, які реєструють хуки для адмінки
     * 
     * @param bool $forceLoadAll Якщо true, завантажує всі модулі незалежно від уже завантажених
     */
    private function loadAdminModules(bool $forceLoadAll = false): void {
        static $adminModulesLoaded = false;
        static $allModulesLoaded = false;
        
        // Для admin_menu завжди завантажуємо всі модулі (але тільки один раз в рамках одного запиту)
        if ($forceLoadAll) {
            if ($allModulesLoaded) {
                // Модулі вже завантажені в цьому запиті, але перевіряємо, що хуки зареєстровані
                // Це важливо на випадок, якщо модулі були завантажені до ініціалізації PluginManager
                $this->ensureModulesHooksRegistered();
                return;
            }
        } else {
            // Для admin_register_routes завантажуємо тільки якщо потрібно
            if ($adminModulesLoaded) {
                return; // Вже завантажені
            }
            
            // Визначаємо, чи потрібне завантаження всіх модулів
            // Якщо вже є завантажені модулі (окрім PluginManager), значить вони завантажені за вимогою
            $loadedModules = ModuleLoader::getLoadedModules();
            $hasOtherModules = count($loadedModules) > 1; // Більше ніж тільки PluginManager
            
            // Якщо модулі вже завантажені за вимогою, не завантажуємо всі інші
            // Це дозволяє завантажувати тільки потрібні модулі для конкретної сторінки
            if ($hasOtherModules) {
                $adminModulesLoaded = true;
                return;
            }
        }
        
        // Завантажуємо тільки модулі, які реєструють хуки для адмінки
        // Кешуємо список модулів щоб не виконувати glob при кожному виклику
        static $modulesList = null;
        
        if ($modulesList === null) {
            $modulesDir = dirname(__DIR__) . '/modules';
            $modules = glob($modulesDir . '/*.php');
            $modulesList = [];
            
            if ($modules !== false) {
                foreach ($modules as $moduleFile) {
                    $moduleName = basename($moduleFile, '.php');
                    
                    // Пропускаємо службові файли
                    if ($moduleName === 'loader' || 
                        $moduleName === 'compatibility' || 
                        $moduleName === 'PluginManager') {
                        continue;
                    }
                    
                    $modulesList[] = $moduleName;
                }
            }
        }
        
        // Завантажуємо модулі з кешованого списку
        foreach ($modulesList as $moduleName) {
            // Завантажуємо модуль, якщо він ще не завантажений
            if (!ModuleLoader::isModuleLoaded($moduleName)) {
                ModuleLoader::loadModule($moduleName);
            }
        }
        
        // Переконуємося, що хуки всіх модулів зареєстровані
        $this->ensureModulesHooksRegistered();
        
        if ($forceLoadAll) {
            $allModulesLoaded = true;
        } else {
            $adminModulesLoaded = true;
        }
    }
    
    /**
     * Переконується, що хуки всіх завантажених модулів зареєстровані
     */
    private function ensureModulesHooksRegistered(): void {
        if (!class_exists('ModuleLoader')) {
            return;
        }
        
        $loadedModules = ModuleLoader::getLoadedModules();
        foreach ($loadedModules as $moduleName => $module) {
            if (is_object($module) && method_exists($module, 'registerHooks')) {
                // Перевіряємо, чи зареєстровані хуки для admin_menu або admin_register_routes
                // Якщо ні, викликаємо registerHooks() ще раз
                // Це безпечно, оскільки addHook() просто додає хуки, а не замінює їх
                $needsRegistration = false;
                
                // Перевіряємо admin_menu
                if (!isset($this->hooks['admin_menu']) || !$this->hasModuleHook($moduleName, 'admin_menu')) {
                    $needsRegistration = true;
                }
                
                // Перевіряємо admin_register_routes
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
    
    /**
     * Перевіряє, чи зареєстрований хук для конкретного модуля
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
     * Перевірка існування хука
     */
    public function hasHook(string $hookName): bool {
        return $this->hookManager->hasHook($hookName) || 
               (!empty($hookName) && isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]));
    }
    
    /**
     * Получить экземпляр HookManager
     */
    public function getHookManager(): HookManager {
        return $this->hookManager;
    }
    
    /**
     * Отримання всіх плагінів (з файлової системи)
     * Автоматично виявляє плагіни за наявністю plugin.json
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
            
            // Використовуємо Json клас для читання конфігурації
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
    
    /**
     * Автоматичне виявлення та встановлення нових плагінів
     */
    public function autoDiscoverPlugins(): int {
        $allPlugins = $this->getAllPlugins();
        $installedCount = 0;
        
        $db = $this->getDB();
        if (!$db) {
            return 0;
        }
        
        // Видаляємо стовпець is_deleted якщо він існує (більше не потрібен)
        try {
            $checkStmt = $db->query("SHOW COLUMNS FROM plugins LIKE 'is_deleted'");
            if ($checkStmt && $checkStmt->rowCount() > 0) {
                $db->exec("ALTER TABLE plugins DROP COLUMN is_deleted");
            }
        } catch (Exception $e) {
            // Ігноруємо помилку якщо стовпець не існує або вже видалений
        }
        
        foreach ($allPlugins as $slug => $config) {
            try {
                // Проверяем, установлен ли плагин
                $stmt = $db->prepare("SELECT id FROM plugins WHERE slug = ?");
                $stmt->execute([$slug]);
                
                if (!$stmt->fetch()) {
                    // Плагин не установлен - устанавливаем
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
     * Отримання активних плагінів
     * Возвращает только активные плагины из БД (не из памяти)
     */
    public function getActivePlugins(): array {
        $db = $this->getDB();
        if (!$db) {
            return [];
        }
        
        try {
            // Получаем активные плагины напрямую из БД, чтобы избежать проблем с кешированием
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
    
    /**
     * Встановлення плагіна
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
            
            // Використовуємо Json клас для читання конфігурації
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
            
            // Выполняем SQL файлы из папки db
            $this->executeDatabaseFiles($pluginDir . '/db');
            
            // Використовуємо slug з конфігу або з імені директорії
            $pluginSlug = $config['slug'] ?? $pluginSlug;
            
            // Додаємо плагін до бази даних
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
                // Очищаємо кеш
                if (function_exists('cache_forget')) {
                    cache_forget('active_plugins');
                    cache_forget('active_plugins_hash');
                }
                $this->clearMenuCache();
                
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
     * Видалення плагіна (тільки записи з БД, файли не видаляються)
     * Можна видалити тільки якщо плагін деактивований
     */
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
            
            // Перевіряємо, чи плагін деактивований
            if (!empty($pluginData['is_active']) && $pluginData['is_active'] == 1) {
                // Плагін активний, спочатку потрібно деактивувати
                return false;
            }
            
            // Викликаємо метод видалення плагіна (якщо він завантажений)
            $plugin = $this->getPlugin($pluginSlug);
            if ($plugin && method_exists($plugin, 'uninstall')) {
                $plugin->uninstall();
            }
            
            // Видаляємо всі налаштування плагіна з plugin_settings
            $stmt = $db->prepare("DELETE FROM plugin_settings WHERE plugin_slug = ?");
            $stmt->execute([$pluginSlug]);
            
            // Видаляємо плагін з бази даних (повністю видаляємо запис)
            $stmt = $db->prepare("DELETE FROM plugins WHERE slug = ?");
            if ($stmt->execute([$pluginSlug])) {
                // Видаляємо файли плагіна
                $pluginDir = $this->pluginsDir . $pluginSlug;
                if (is_dir($pluginDir)) {
                    $this->deletePluginDirectory($pluginDir);
                }
                
                // Очищаємо кеш ПЕРЕД clearMenuCache, чтобы хеш пересчитался правильно
                if (function_exists('cache_forget')) {
                    cache_forget('active_plugins');
                    cache_forget('active_plugins_hash');
                    cache_forget('active_plugins_list');
                    cache_forget('plugin_data_' . $pluginSlug);
                }
                
                // Очищаємо ВСЕ варианты кеша меню (включая файловый)
                $this->clearAllMenuCache();
                
                // Логуємо видалення плагіна
                doHook('plugin_uninstalled', $pluginSlug);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Plugin uninstallation error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Рекурсивне видалення директорії плагіна
     * 
     * @param string $dir Шлях до директорії
     * @return bool
     */
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
    
    /**
     * Активація плагіна
     */
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
            
            // Очищаємо кеш перед активацией
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins');
                cache_forget('active_plugins_hash');
                cache_forget('active_plugins_list');
                cache_forget('plugin_data_' . $pluginSlug);
            }
            
            // Очищаємо кеш конкретного плагина ПЕРЕД загрузкой
            $this->clearPluginCache($pluginSlug);
            
            // Завантажуємо та активуємо плагін
            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin) {
                // Викликаємо activate() для логіки активації
                if (method_exists($plugin, 'activate')) {
                    $plugin->activate();
                }
                
                // ВАЖЛИВО: Явно вызываем init() после активации, чтобы зарегистрировать хуки
                // Это нужно, потому что initializePlugins() может не вызваться для только что активированного плагина
                if (method_exists($plugin, 'init')) {
                    try {
                        $plugin->init();
                    } catch (Exception $e) {
                        error_log("Plugin init error for {$pluginSlug} after activation: " . $e->getMessage());
                    }
                }
            }
            
            // Очищаємо кеш конкретного плагина
            $this->clearPluginCache($pluginSlug);
            
            // Очищаємо кеш меню після активації плагіна (включая файловый кеш)
            // ВАЖЛИВО: очищаем хеш ПЕРЕД clearAllMenuCache, чтобы он пересчитался
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins_hash');
            }
            $this->clearAllMenuCache();
            
            // Принудительно очищаем хеш еще раз после очистки кеша меню
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
    
    /**
     * Деактивація плагіна
     */
    public function deactivatePlugin(string $pluginSlug): bool {
        try {
            // Викликаємо метод деактивації плагіна
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
            
            // Очищаємо кеш перед деактивацией
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins');
                cache_forget('active_plugins_hash');
                cache_forget('active_plugins_list');
                cache_forget('plugin_data_' . $pluginSlug);
            }
            
            // Видаляємо хуки плагина перед удалением из памяти
            $this->removePluginHooks($pluginSlug);
            
            // Видаляємо плагін з пам'яті
            if (isset($this->plugins[$pluginSlug])) {
                unset($this->plugins[$pluginSlug]);
            }
            
            // Очищаємо кеш конкретного плагина
            $this->clearPluginCache($pluginSlug);
            
            // Очищаємо кеш меню після деактивації плагіна (включая файловый кеш)
            // ВАЖЛИВО: очищаем хеш ПЕРЕД clearAllMenuCache, чтобы он пересчитался
            if (function_exists('cache_forget')) {
                cache_forget('active_plugins_hash');
            }
            
            // Очищаем все варианты кеша меню
            $this->clearAllMenuCache();
            
            // Принудительно очищаем хеш еще раз после очистки кеша меню
            // Это гарантирует, что хеш будет пересчитан при следующем запросе
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
    
    /**
     * Отримання налаштування плагіна
     */
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
    
    /**
     * Збереження налаштування плагіна
     */
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
    
    /**
     * Виконання SQL файлів з директорії db плагіна
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
     * Виконання SQL файлу
     */
    private function executeSqlFile(string $sqlFile): bool {
        $db = $this->getDB();
        if (!$db) {
            return false;
        }
        
        // Використовуємо File клас для читання SQL файлу
        $file = new File($sqlFile);
        if (!$file->exists()) {
            return false;
        }
        
        try {
            $sql = $file->read();
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $db->exec($query);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SQL file execution error ({$sqlFile}): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Очищення кешу меню адміністратора
     * Очищає всі можливі комбінації ключів кешу меню
     */
    private function clearMenuCache(): void {
        // Сначала очищаем хеш плагинов, чтобы он пересчитался
        if (function_exists('cache_forget')) {
            cache_forget('active_plugins_hash');
        }
        
        $activePlugins = $this->getActivePlugins();
        $pluginsHash = md5(implode(',', array_keys($activePlugins)));
        
        // Очищаємо всі можливі комбінації підтримки теми з новим хешем
        for ($custom = 0; $custom <= 1; $custom++) {
            for ($nav = 0; $nav <= 1; $nav++) {
                $key = 'admin_menu_items_' . $custom . '_' . $nav . '_' . $pluginsHash;
                if (function_exists('cache_forget')) {
                    cache_forget($key);
                }
            }
        }
        
        // Очищаємо старі ключі кешу меню без хешу плагінів (для зворотної сумісності)
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
        
        // Очищаємо файловый кеш меню (на случай если используется файловый кеш)
        $this->clearMenuFileCache();
    }
    
    /**
     * Очищення файлового кешу меню
     */
    private function clearMenuFileCache(): void {
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        
        if (is_dir($cacheDir)) {
            // Удаляем все файлы кеша меню
            $files = glob($cacheDir . 'admin_menu_items_*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            
            // Также удаляем файлы с хешем плагинов
            $hashFiles = glob($cacheDir . 'active_plugins_hash*.cache');
            if ($hashFiles !== false) {
                foreach ($hashFiles as $file) {
                    @unlink($file);
                }
            }
        }
    }
    
    /**
     * Очищення всього кешу меню адмінки
     * Використовується при видаленні модулів або зміні структури меню
     */
    public function clearAllMenuCache(): void {
        // Очищаємо хеш плагинов сначала
        if (function_exists('cache_forget')) {
            cache_forget('active_plugins_hash');
        }
        
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        
        // Видаляємо всі файли кешу, що починаються з admin_menu_items_
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . 'admin_menu_items_*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            
            // Также удаляем файлы с хешем плагинов
            $hashFiles = glob($cacheDir . 'active_plugins_hash*.cache');
            if ($hashFiles !== false) {
                foreach ($hashFiles as $file) {
                    @unlink($file);
                }
            }
        }
        
        // Очищаємо все возможные варианты кеша меню через cache_forget
        // Очищаем старые ключи без хеша
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
        
        // Также вызываем clearMenuFileCache для полной очистки
        $this->clearMenuFileCache();
    }
    
    /**
     * Видалення хуків плагіна
     * 
     * @param string $pluginSlug Slug плагіна
     */
    private function removePluginHooks(string $pluginSlug): void {
        if (empty($pluginSlug)) {
            return;
        }
        
        // Получаем имя класса плагина
        $className = $this->getPluginClassName($pluginSlug);
        
        // Удаляем хуки плагина из HookManager
        $allHooks = $this->hookManager->getAllHooks();
        foreach ($allHooks as $hookName => $hooks) {
            foreach ($hooks as $hook) {
                $callback = $hook['callback'] ?? null;
                if ($callback === null) {
                    continue;
                }
                
                // Проверяем, является ли callback методом этого плагина
                if (is_array($callback)) {
                    if (isset($callback[0]) && is_object($callback[0])) {
                        $objectClass = get_class($callback[0]);
                        // Если это экземпляр класса плагина, удаляем хук
                        if ($objectClass === $className || strpos($objectClass, $pluginSlug) !== false) {
                            $this->hookManager->removeHook($hookName, $callback);
                        }
                    }
                } elseif (is_string($callback) && (strpos($callback, $className) !== false || strpos($callback, $pluginSlug) !== false)) {
                    // Для строковых callback (функций)
                    $this->hookManager->removeHook($hookName, $callback);
                }
            }
        }
        
        // Удаляем хуки плагина из старого хранилища (для обратной совместимости)
        foreach ($this->hooks as $hookName => $hooks) {
            $filteredHooks = array_filter($hooks, function($hook) use ($className, $pluginSlug) {
                // Проверяем, является ли callback методом этого плагина
                if (is_array($hook['callback'])) {
                    if (isset($hook['callback'][0])) {
                        $object = $hook['callback'][0];
                        if (is_object($object)) {
                            $objectClass = get_class($object);
                            // Если это экземпляр класса плагина, удаляем хук
                            if ($objectClass === $className || strpos($objectClass, $pluginSlug) !== false) {
                                return false; // Удаляем этот хук
                            }
                        }
                    }
                }
                return true; // Оставляем хук
            });
            
            // Пересоздаем массив с правильными индексами
            $this->hooks[$hookName] = array_values($filteredHooks);
            
            // Если хуков не осталось, удаляем ключ
            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
    }
    
    /**
     * Очищення кешу конкретного плагіна
     * 
     * @param string $pluginSlug Slug плагіна
     */
    private function clearPluginCache(string $pluginSlug): void {
        if (empty($pluginSlug)) {
            return;
        }
        
        // Очищаємо кеш даних плагіна
        if (function_exists('cache_forget')) {
            cache_forget('plugin_data_' . $pluginSlug);
        }
        
        // Очищаємо файловый кеш плагіна (если есть)
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        if (is_dir($cacheDir)) {
            // Удаляем файлы кеша плагина
            $pluginCacheFiles = glob($cacheDir . 'plugin_' . $pluginSlug . '_*.cache');
            if ($pluginCacheFiles !== false) {
                foreach ($pluginCacheFiles as $file) {
                    @unlink($file);
                }
            }
            
            // Также удаляем файлы с префиксом плагина
            $pluginCacheFiles2 = glob($cacheDir . $pluginSlug . '_*.cache');
            if ($pluginCacheFiles2 !== false) {
                foreach ($pluginCacheFiles2 as $file) {
                    @unlink($file);
                }
            }
        }
    }
}

/**
 * Глобальна функція для отримання екземпляра модуля PluginManager
 */
function pluginManager() {
    return PluginManager::getInstance();
}

/**
 * Глобальні функції для роботи з хуками
 */

/**
 * Добавить хук (обратная совместимость, по умолчанию фильтр)
 */
function addHook(string $hookName, callable $callback, int $priority = 10): void {
    pluginManager()->addHook($hookName, $callback, $priority);
}

/**
 * Выполнить хук (обратная совместимость)
 */
function doHook(string $hookName, $data = null) {
    return pluginManager()->doHook($hookName, $data);
}

/**
 * Проверить существование хука
 */
function hasHook(string $hookName): bool {
    return pluginManager()->hasHook($hookName);
}

/**
 * Добавить фильтр (filter)
 * Фильтры модифицируют данные и возвращают результат
 */
function addFilter(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
    pluginManager()->getHookManager()->addFilter($hookName, $callback, $priority, $condition);
}

/**
 * Применить фильтр (filter)
 * Проходит через все зарегистрированные фильтры и модифицирует данные
 */
function applyFilter(string $hookName, $data = null, ...$args) {
    return pluginManager()->getHookManager()->applyFilter($hookName, $data, ...$args);
}

/**
 * Добавить событие (action)
 * События выполняют действия без возврата данных
 */
function addAction(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
    pluginManager()->getHookManager()->addAction($hookName, $callback, $priority, $condition);
}

/**
 * Выполнить событие (action)
 * Вызывает все зарегистрированные обработчики события
 */
function doAction(string $hookName, ...$args): void {
    pluginManager()->getHookManager()->doAction($hookName, ...$args);
}

/**
 * Удалить хук
 */
function removeHook(string $hookName, ?callable $callback = null): bool {
    return pluginManager()->getHookManager()->removeHook($hookName, $callback);
}

/**
 * Получить экземпляр HookManager
 */
function hookManager(): HookManager {
    return HookManager::getInstance();
}

