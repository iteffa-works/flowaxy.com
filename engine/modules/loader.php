<?php
/**
 * Загрузчик системных модулей
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
     * Инициализация загрузчика
     * Загружает только критически важные модули, остальные загружаются по требованию
     */
    public static function init(): void {
        if (self::$initialized) {
            return; // Уже инициализирован
        }
        
        self::$modulesDir = __DIR__;
        
        // Загружаем только критически важные модули, которые нужны для работы системы
        $criticalModules = ['PluginManager']; // Модули, которые нужно загрузить сразу
        
        foreach ($criticalModules as $moduleName) {
            self::loadModule($moduleName);
        }
        
        self::$initialized = true;
    }
    
    /**
     * Ленивая загрузка модуля по требованию
     * 
     * @param string $moduleName Имя модуля
     * @return BaseModule|null
     */
    public static function loadModule(string $moduleName): ?BaseModule {
        // Если модуль уже загружен, возвращаем его
        if (isset(self::$loadedModules[$moduleName])) {
            return self::$loadedModules[$moduleName];
        }
        
        // Проверяем, что директория модулей определена
        if (empty(self::$modulesDir)) {
            self::$modulesDir = __DIR__;
        }
        
        $moduleFile = self::$modulesDir . '/' . $moduleName . '.php';
        
        // Проверяем существование файла модуля
        if (!file_exists($moduleFile)) {
            error_log("Module file not found: {$moduleFile}");
            return null;
        }
        
        // Пропускаем служебные файлы
        if ($moduleName === 'loader' || $moduleName === 'compatibility') {
            return null;
        }
        
        // Загружаем модуль
        return self::loadModuleFile($moduleFile, $moduleName);
    }
    
    /**
     * Загрузка всех модулей (для совместимости и отладки)
     */
    private static function loadAllModules(): void {
        $modules = glob(self::$modulesDir . '/*.php');
        
        if ($modules !== false) {
            foreach ($modules as $moduleFile) {
                $moduleName = basename($moduleFile, '.php');
                
                // Пропускаем служебные файлы и уже загруженные модули
                if ($moduleName === 'loader' || $moduleName === 'compatibility' || isset(self::$loadedModules[$moduleName])) {
                    continue;
                }
                
                self::loadModuleFile($moduleFile, $moduleName);
            }
        }
    }
    
    /**
     * Загрузка файла модуля
     */
    private static function loadModuleFile(string $moduleFile, string $moduleName): void {
        try {
            // Убеждаемся, что BaseModule загружен
            if (!class_exists('BaseModule')) {
                $baseModuleFile = dirname(self::$modulesDir) . '/classes/BaseModule.php';
                if (file_exists($baseModuleFile)) {
                    require_once $baseModuleFile;
                }
            }
            
            require_once $moduleFile;
            
            // Проверяем, что класс существует
            if (class_exists($moduleName)) {
                $module = $moduleName::getInstance();
                
                // Регистрируем хуки модуля
                if (method_exists($module, 'registerHooks')) {
                    $module->registerHooks();
                }
                
                self::$loadedModules[$moduleName] = $module;
                
                // Убеждаемся, что Logger загружен для логирования загрузки модулей
                // (если он еще не загружен, но нужен для логирования)
                if ($moduleName !== 'Logger' && function_exists('doHook')) {
                    // Проверяем, нужно ли логировать события модулей
                    // Если Logger еще не загружен, но может быть нужен, загружаем его
                    if (!self::isModuleLoaded('Logger')) {
                        $loggerFile = self::$modulesDir . '/Logger.php';
                        if (file_exists($loggerFile)) {
                            // Пытаемся загрузить Logger, если он существует
                            try {
                                self::loadModule('Logger');
                            } catch (Exception $e) {
                                // Игнорируем ошибки загрузки Logger
                            } catch (Error $e) {
                                // Игнорируем фатальные ошибки загрузки Logger
                            }
                        }
                    }
                    
                    // Логируем загрузку модуля
                    doHook('module_loaded', $moduleName);
                } elseif ($moduleName === 'Logger') {
                    // Для Logger просто вызываем хук без дополнительной загрузки
                    if (function_exists('doHook')) {
                        doHook('module_loaded', $moduleName);
                    }
                }
            } else {
                error_log("Module class {$moduleName} not found after loading file: {$moduleFile}");
            }
        } catch (Exception $e) {
            error_log("Error loading module {$moduleName}: " . $e->getMessage());
            // Логируем ошибку модуля
            if (function_exists('doHook')) {
                doHook('module_error', [
                    'module' => $moduleName,
                    'message' => $e->getMessage(),
                    'file' => $moduleFile
                ]);
            }
        } catch (Error $e) {
            error_log("Fatal error loading module {$moduleName}: " . $e->getMessage());
            // Логируем ошибку модуля
            if (function_exists('doHook')) {
                doHook('module_error', [
                    'module' => $moduleName,
                    'message' => $e->getMessage(),
                    'file' => $moduleFile
                ]);
            }
        }
    }
    
    /**
     * Получение загруженного модуля
     * 
     * @param string $moduleName Имя модуля
     * @return BaseModule|null
     */
    public static function getModule(string $moduleName): ?BaseModule {
        return self::$loadedModules[$moduleName] ?? null;
    }
    
    /**
     * Получение списка всех загруженных модулей
     * 
     * @param bool $loadAll Если true, загружает все модули (для отладки)
     * @return array
     */
    public static function getLoadedModules(bool $loadAll = false): array {
        // Если запрошена загрузка всех модулей (для отладки/админки)
        if ($loadAll && self::$initialized) {
            self::loadAllModules();
        }
        
        return self::$loadedModules;
    }
    
    /**
     * Проверка, загружен ли модуль
     * 
     * @param string $moduleName Имя модуля
     * @return bool
     */
    public static function isModuleLoaded(string $moduleName): bool {
        return isset(self::$loadedModules[$moduleName]);
    }
}

