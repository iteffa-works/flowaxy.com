<?php
/**
 * Сторінка API методів модулів
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class ApiPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'API методів модулів - Landing CMS';
        $this->templateName = 'api';
        
        $this->setPageHeader(
            'API методів модулів',
            'Документація доступних методів всіх модулів системи',
            'fas fa-code'
        );
    }
    
    public function handle() {
        // Отримуємо список всіх модулів та їх API методів
        $modulesApi = $this->getModulesApi();
        
        // Рендеримо сторінку
        $this->render([
            'modulesApi' => $modulesApi
        ]);
    }
    
    /**
     * Отримання API методів всіх модулів
     */
    private function getModulesApi() {
        $modulesApi = [];
        
        // Отримуємо всі завантажені модулі
        if (class_exists('ModuleLoader')) {
            $loadedModules = ModuleLoader::getLoadedModules();
            
            foreach ($loadedModules as $moduleName => $module) {
                if (!is_object($module)) {
                    continue;
                }
                
                // Отримуємо інформацію про модуль
                $moduleInfo = [];
                if (method_exists($module, 'getInfo')) {
                    $moduleInfo = $module->getInfo();
                }
                
                // Отримуємо API методи модуля
                $apiMethods = [];
                if (method_exists($module, 'getApiMethods')) {
                    $apiMethods = $module->getApiMethods();
                }
                
                // Отримуємо список публічних методів класу
                $publicMethods = $this->getPublicMethods($module);
                
                if (!empty($apiMethods) || !empty($publicMethods)) {
                    $modulesApi[$moduleName] = [
                        'info' => $moduleInfo,
                        'api_methods' => $apiMethods,
                        'public_methods' => $publicMethods
                    ];
                }
            }
        }
        
        // Сортуємо модулі за іменем
        ksort($modulesApi);
        
        return $modulesApi;
    }
    
    /**
     * Отримання публічних методів класу
     */
    private function getPublicMethods($object) {
        $methods = [];
        $reflection = new ReflectionClass($object);
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Пропускаємо методи базових класів та магічні методи
            if ($method->getDeclaringClass()->getName() === 'BaseModule' || 
                $method->getName() === '__construct' || 
                $method->getName() === '__call' ||
                $method->getName() === '__get' ||
                $method->getName() === '__set' ||
                strpos($method->getName(), '__') === 0) {
                continue;
            }
            
            $methodName = $method->getName();
            $parameters = [];
            
            foreach ($method->getParameters() as $param) {
                $paramInfo = [
                    'name' => $param->getName(),
                    'type' => $param->getType() ? (string)$param->getType() : 'mixed',
                    'optional' => $param->isOptional(),
                    'default' => $param->isOptional() ? $param->getDefaultValue() : null
                ];
                $parameters[] = $paramInfo;
            }
            
            $docComment = $method->getDocComment();
            $description = '';
            if ($docComment) {
                // Витягуємо опис з DocComment
                $lines = explode("\n", $docComment);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, '@') === false && 
                        strpos($line, '/**') === false && 
                        strpos($line, '*/') === false &&
                        !empty($line)) {
                        $description = trim($line, '* ');
                        break;
                    }
                }
            }
            
            $methods[$methodName] = [
                'description' => $description ?: 'Немає опису',
                'parameters' => $parameters,
                'return_type' => $method->getReturnType() ? (string)$method->getReturnType() : 'mixed'
            ];
        }
        
        return $methods;
    }
}

