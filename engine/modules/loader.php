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
     */
    public static function init(): void {
        if (self::$initialized) {
            return; // Уже инициализирован
        }
        
        self::$modulesDir = __DIR__;
        self::loadModules();
        self::$initialized = true;
    }
    
    /**
     * Загрузка всех модулей
     */
    private static function loadModules(): void {
        $modules = glob(self::$modulesDir . '/*.php');
        $priorityModules = ['PluginManager']; // Модули, которые нужно загрузить первыми
        
        // Сначала загружаем приоритетные модули
        foreach ($priorityModules as $priorityModule) {
            $moduleFile = self::$modulesDir . '/' . $priorityModule . '.php';
            if (file_exists($moduleFile)) {
                self::loadModuleFile($moduleFile, $priorityModule);
            }
        }
        
        // Затем загружаем остальные модули
        if ($modules !== false) {
            foreach ($modules as $moduleFile) {
                $moduleName = basename($moduleFile, '.php');
                
                // Пропускаем loader.php, compatibility.php и уже загруженные модули
                if ($moduleName === 'loader' || $moduleName === 'compatibility' || in_array($moduleName, $priorityModules)) {
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
            } else {
                error_log("Module class {$moduleName} not found after loading file: {$moduleFile}");
            }
        } catch (Exception $e) {
            error_log("Error loading module {$moduleName}: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error loading module {$moduleName}: " . $e->getMessage());
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
     * @return array
     */
    public static function getLoadedModules(): array {
        // Если модули не загружены, но инициализация выполнена, попробуем загрузить их снова
        if (empty(self::$loadedModules) && self::$initialized) {
            // Это может произойти, если модули были загружены до инициализации
            // или если произошла ошибка при загрузке
            self::loadModules();
        }
        
        // Если модули все еще не загружены, но директория определена, попробуем загрузить напрямую
        if (empty(self::$loadedModules) && !empty(self::$modulesDir)) {
            self::loadModules();
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

