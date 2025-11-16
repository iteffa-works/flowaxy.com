<?php
/**
 * Завантажувач системних модулів
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

class ModuleLoader {
    private static $loadedModules = [];
    private static $modulesDir;
    private static $initialized = false;
    
    /**
     * Ініціалізація завантажувача
     * Завантажує тільки критично важливі модулі, інші завантажуються за вимогою
     */
    public static function init(): void {
        if (self::$initialized) {
            return; // Вже ініціалізовано
        }
        
        self::$modulesDir = __DIR__;
        
        // Завантажуємо тільки критично важливі модулі, які потрібні для роботи системи
        $criticalModules = ['PluginManager']; // Модулі, які потрібно завантажити одразу
        
        foreach ($criticalModules as $moduleName) {
            self::loadModule($moduleName);
        }
        
        self::$initialized = true;
    }
    
    /**
     * Ліниве завантаження модуля за вимогою
     * 
     * @param string $moduleName Ім'я модуля
     * @return BaseModule|null
     */
    public static function loadModule(string $moduleName): ?BaseModule {
        // Якщо модуль вже завантажено, повертаємо його
        if (isset(self::$loadedModules[$moduleName])) {
            return self::$loadedModules[$moduleName];
        }
        
        // Пропускаємо службові файли
        if ($moduleName === 'loader' || $moduleName === 'compatibility') {
            return null;
        }
        
        // Перевіряємо, що директорія модулів визначена
        if (empty(self::$modulesDir)) {
            self::$modulesDir = __DIR__;
        }
        
        $moduleFile = self::$modulesDir . '/' . $moduleName . '.php';
        
        // Перевіряємо існування файлу модуля
        if (!file_exists($moduleFile)) {
            error_log("Module file not found: {$moduleFile}");
            return null;
        }
        
        // Завантажуємо модуль
        return self::loadModuleFile($moduleFile, $moduleName);
    }
    
    /**
     * Завантаження всіх модулів (для сумісності та відлагодження)
     */
    private static function loadAllModules(): void {
        $modules = glob(self::$modulesDir . '/*.php');
        
        if ($modules !== false) {
            foreach ($modules as $moduleFile) {
                $moduleName = basename($moduleFile, '.php');
                
                // Пропускаємо службові файли та вже завантажені модулі
                if ($moduleName === 'loader' || $moduleName === 'compatibility' || isset(self::$loadedModules[$moduleName])) {
                    continue;
                }
                
                self::loadModuleFile($moduleFile, $moduleName);
            }
        }
    }
    
    /**
     * Завантаження файлу модуля
     */
    private static function loadModuleFile(string $moduleFile, string $moduleName): ?BaseModule {
        try {
            // Переконуємося, що BaseModule завантажено (автозавантажувач має завантажити)
            if (!class_exists('BaseModule')) {
                // Пробуємо завантажити з нової структури
                $baseModuleFile = dirname(self::$modulesDir) . '/classes/base/BaseModule.php';
                if (file_exists($baseModuleFile)) {
                    require_once $baseModuleFile;
                } else {
                    // Зворотна сумісність - стара структура
                    $baseModuleFile = dirname(self::$modulesDir) . '/classes/BaseModule.php';
                    if (file_exists($baseModuleFile)) {
                        require_once $baseModuleFile;
                    }
                }
            }
            
            require_once $moduleFile;
            
            // Перевіряємо, що клас існує
            if (!class_exists($moduleName)) {
                error_log("Module class {$moduleName} not found after loading file: {$moduleFile}");
                return null;
            }
            
            $module = $moduleName::getInstance();
            
            // Реєструємо хуки модуля
            if (method_exists($module, 'registerHooks')) {
                $module->registerHooks();
            }
            
            self::$loadedModules[$moduleName] = $module;
            
            // Логуємо завантаження модуля через хук (якщо доступний)
            if (function_exists('doHook')) {
                doHook('module_loaded', $moduleName);
            }
            
            return $module;
        } catch (Exception | Error $e) {
            error_log("Error loading module {$moduleName}: " . $e->getMessage());
            if (function_exists('doHook')) {
                doHook('module_error', [
                    'module' => $moduleName,
                    'message' => $e->getMessage(),
                    'file' => $moduleFile
                ]);
            }
            return null;
        }
    }
    
    /**
     * Отримання завантаженого модуля
     * 
     * @param string $moduleName Ім'я модуля
     * @return BaseModule|null
     */
    public static function getModule(string $moduleName): ?BaseModule {
        return self::$loadedModules[$moduleName] ?? null;
    }
    
    /**
     * Отримання списку всіх завантажених модулів
     * 
     * @param bool $loadAll Якщо true, завантажує всі модулі (для відлагодження)
     * @return array
     */
    public static function getLoadedModules(bool $loadAll = false): array {
        // Якщо запрошено завантаження всіх модулів (для відлагодження/адмінки)
        if ($loadAll && self::$initialized) {
            self::loadAllModules();
        }
        
        return self::$loadedModules;
    }
    
    /**
     * Перевірка, чи завантажено модуль
     * 
     * @param string $moduleName Ім'я модуля
     * @return bool
     */
    public static function isModuleLoaded(string $moduleName): bool {
        return isset(self::$loadedModules[$moduleName]);
    }
}

