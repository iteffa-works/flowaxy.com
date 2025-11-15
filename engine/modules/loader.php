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
    
    /**
     * Инициализация загрузчика
     */
    public static function init(): void {
        self::$modulesDir = __DIR__;
        self::loadModules();
    }
    
    /**
     * Загрузка всех модулей
     */
    private static function loadModules(): void {
        $modules = glob(self::$modulesDir . '/*.php');
        
        foreach ($modules as $moduleFile) {
            $moduleName = basename($moduleFile, '.php');
            
            // Пропускаем loader.php
            if ($moduleName === 'loader') {
                continue;
            }
            
            try {
                require_once $moduleFile;
                
                // Проверяем, что класс существует
                if (class_exists($moduleName)) {
                    $module = $moduleName::getInstance();
                    
                    // Регистрируем хуки модуля
                    if (method_exists($module, 'registerHooks')) {
                        $module->registerHooks();
                    }
                    
                    self::$loadedModules[$moduleName] = $module;
                }
            } catch (Exception $e) {
                error_log("Error loading module {$moduleName}: " . $e->getMessage());
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
     * @return array
     */
    public static function getLoadedModules(): array {
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

