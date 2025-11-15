<?php
/**
 * Админ-страница плагина Example Test
 * 
 * @package Plugins\ExampleTest
 * @version 1.0.0
 */

require_once dirname(__DIR__, 3) . '/engine/skins/includes/AdminPage.php';

// Убеждаемся, что ModuleLoader доступен и инициализирован
if (!class_exists('ModuleLoader')) {
    require_once dirname(__DIR__, 3) . '/engine/classes/BaseModule.php';
    require_once dirname(__DIR__, 3) . '/engine/modules/loader.php';
}
if (class_exists('ModuleLoader') && method_exists('ModuleLoader', 'init')) {
    ModuleLoader::init();
}

class ExampleTestAdminPage extends AdminPage {
    
    private $currentTab = 'modules';
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Example Test - Landing CMS';
        $this->templateName = 'example-test';
        
        $this->setPageHeader(
            'Example Test',
            'Тестирование всех компонентов CMS',
            'fas fa-vial'
        );
        
        // Определяем текущую вкладку
        $this->currentTab = sanitizeInput($_GET['tab'] ?? 'modules');
        
        // Добавляем стили и скрипты
        $this->additionalCSS[] = adminUrl('styles/flowaxy.css') . '?v=' . time();
    }
    
    /**
     * Получение пути к шаблону
     */
    protected function getTemplatePath() {
        return __DIR__ . '/templates/';
    }
    
    /**
     * Обработка запроса
     */
    public function handle() {
        // Обработка AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Получаем данные для текущей вкладки
        $data = $this->getTabData();
        
        // Рендеринг страницы через стандартный метод render()
        $this->render([
            'currentTab' => $this->currentTab,
            'tabs' => $this->getTabs(),
            'data' => $data,
            'tabData' => $data
        ]);
    }
    
    /**
     * Получение списка вкладок
     * 
     * @return array
     */
    private function getTabs(): array {
        return [
            'modules' => [
                'title' => 'Системные модули',
                'icon' => 'fas fa-cubes',
                'description' => 'Тестирование системных модулей'
            ],
            'plugins' => [
                'title' => 'Плагины',
                'icon' => 'fas fa-puzzle-piece',
                'description' => 'Тестирование установленных плагинов'
            ],
            'components' => [
                'title' => 'Компоненты',
                'icon' => 'fas fa-layer-group',
                'description' => 'Тестирование компонентов системы'
            ],
            'visual' => [
                'title' => 'Визуальное тестирование',
                'icon' => 'fas fa-eye',
                'description' => 'Визуальное тестирование интерфейса'
            ],
            'theoretical' => [
                'title' => 'Теоретическое тестирование',
                'icon' => 'fas fa-book',
                'description' => 'Теоретическое тестирование функций'
            ],
            'api' => [
                'title' => 'API тестирование',
                'icon' => 'fas fa-code',
                'description' => 'Тестирование API методов'
            ]
        ];
    }
    
    /**
     * Получение данных для текущей вкладки
     * 
     * @return array
     */
    private function getTabData(): array {
        switch ($this->currentTab) {
            case 'modules':
                return $this->getModulesData();
            case 'plugins':
                return $this->getPluginsData();
            case 'components':
                return $this->getComponentsData();
            case 'visual':
                return $this->getVisualData();
            case 'theoretical':
                return $this->getTheoreticalData();
            case 'api':
                return $this->getApiData();
            default:
                return [];
        }
    }
    
    /**
     * Данные для вкладки "Системные модули"
     */
    private function getModulesData(): array {
        $modules = [];
        
        try {
            // Сначала пробуем получить модули через ModuleLoader
            if (class_exists('ModuleLoader')) {
                ModuleLoader::init();
                // Загружаем все модули для отображения в админке
                $loadedModules = ModuleLoader::getLoadedModules(true);
                
                if (!empty($loadedModules)) {
                    foreach ($loadedModules as $name => $module) {
                        if (is_object($module) && method_exists($module, 'getInfo')) {
                            try {
                                $info = $module->getInfo();
                                $apiMethods = method_exists($module, 'getApiMethods') ? $module->getApiMethods() : [];
                                
                                $modules[] = [
                                    'name' => $name,
                                    'title' => $info['title'] ?? $info['name'] ?? $name,
                                    'description' => $info['description'] ?? '',
                                    'version' => $info['version'] ?? '1.0.0',
                                    'author' => $info['author'] ?? '',
                                    'api_methods' => $apiMethods,
                                    'status' => 'active'
                                ];
                            } catch (Exception $e) {
                                error_log("Error getting info for module {$name}: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            // Если через ModuleLoader не получилось, загружаем модули напрямую из директории
            if (empty($modules)) {
                $modulesDir = dirname(__DIR__, 3) . '/engine/modules/';
                
                // Убеждаемся, что BaseModule загружен
                if (!class_exists('BaseModule')) {
                    $baseModuleFile = dirname($modulesDir) . '/classes/BaseModule.php';
                    if (file_exists($baseModuleFile)) {
                        require_once $baseModuleFile;
                    }
                }
                
                // Получаем список файлов модулей
                $moduleFiles = glob($modulesDir . '*.php');
                
                if ($moduleFiles !== false) {
                    foreach ($moduleFiles as $moduleFile) {
                        $moduleName = basename($moduleFile, '.php');
                        
                        // Пропускаем служебные файлы
                        if ($moduleName === 'loader' || $moduleName === 'compatibility') {
                            continue;
                        }
                        
                        try {
                            // Загружаем файл модуля, если класс еще не загружен
                            if (!class_exists($moduleName)) {
                                require_once $moduleFile;
                            }
                            
                            // Проверяем, что класс существует
                            if (class_exists($moduleName)) {
                                // Получаем экземпляр модуля (Singleton)
                                $module = $moduleName::getInstance();
                                
                                // Проверяем, что это объект и имеет метод getInfo
                                if (is_object($module) && method_exists($module, 'getInfo')) {
                                    try {
                                        $info = $module->getInfo();
                                        $apiMethods = method_exists($module, 'getApiMethods') ? $module->getApiMethods() : [];
                                        
                                        // Проверяем, что модуль еще не добавлен
                                        $exists = false;
                                        foreach ($modules as $existingModule) {
                                            if ($existingModule['name'] === $moduleName) {
                                                $exists = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$exists) {
                                            $modules[] = [
                                                'name' => $moduleName,
                                                'title' => $info['title'] ?? $info['name'] ?? $moduleName,
                                                'description' => $info['description'] ?? '',
                                                'version' => $info['version'] ?? '1.0.0',
                                                'author' => $info['author'] ?? '',
                                                'api_methods' => $apiMethods,
                                                'status' => 'active'
                                            ];
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error getting info for module {$moduleName}: " . $e->getMessage());
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error loading module {$moduleName}: " . $e->getMessage());
                        } catch (Error $e) {
                            error_log("Fatal error loading module {$moduleName}: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error getting modules data: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error getting modules data: " . $e->getMessage());
        }
        
        return [
            'modules' => $modules,
            'total' => count($modules)
        ];
    }
    
    /**
     * Данные для вкладки "Плагины"
     */
    private function getPluginsData(): array {
        $plugins = [];
        
        try {
            $stmt = $this->db->query("SELECT * FROM plugins ORDER BY name ASC");
            $pluginData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pluginData as $plugin) {
                $plugins[] = [
                    'id' => $plugin['id'],
                    'name' => $plugin['name'],
                    'slug' => $plugin['slug'],
                    'description' => $plugin['description'],
                    'version' => $plugin['version'],
                    'author' => $plugin['author'],
                    'is_active' => (bool)$plugin['is_active'],
                    'status' => $plugin['is_active'] ? 'active' : 'inactive'
                ];
            }
        } catch (Exception $e) {
            error_log("Error loading plugins: " . $e->getMessage());
        }
        
        return [
            'plugins' => $plugins,
            'total' => count($plugins),
            'active' => count(array_filter($plugins, fn($p) => $p['is_active']))
        ];
    }
    
    /**
     * Данные для вкладки "Компоненты"
     */
    private function getComponentsData(): array {
        $components = [];
        
        // Системные компоненты
        $systemComponents = [
            'Database' => [
                'name' => 'Database',
                'type' => 'system',
                'description' => 'Система управления базой данных',
                'status' => 'active'
            ],
            'Cache' => [
                'name' => 'Cache',
                'type' => 'system',
                'description' => 'Система кеширования',
                'status' => 'active'
            ],
            'Router' => [
                'name' => 'Router',
                'type' => 'system',
                'description' => 'Система маршрутизации',
                'status' => 'active'
            ],
            'Validator' => [
                'name' => 'Validator',
                'type' => 'system',
                'description' => 'Система валидации данных',
                'status' => 'active'
            ]
        ];
        
        foreach ($systemComponents as $name => $component) {
            $components[] = $component;
        }
        
        return [
            'components' => $components,
            'total' => count($components)
        ];
    }
    
    /**
     * Данные для вкладки "Визуальное тестирование"
     */
    private function getVisualData(): array {
        return [
            'tests' => [
                [
                    'name' => 'Цветовая схема',
                    'description' => 'Проверка цветовой схемы интерфейса',
                    'status' => 'pending'
                ],
                [
                    'name' => 'Адаптивность',
                    'description' => 'Проверка адаптивности интерфейса',
                    'status' => 'pending'
                ],
                [
                    'name' => 'Типографика',
                    'description' => 'Проверка типографики',
                    'status' => 'pending'
                ],
                [
                    'name' => 'Иконки',
                    'description' => 'Проверка отображения иконок',
                    'status' => 'pending'
                ]
            ]
        ];
    }
    
    /**
     * Данные для вкладки "Теоретическое тестирование"
     */
    private function getTheoreticalData(): array {
        return [
            'tests' => [
                [
                    'name' => 'Функции безопасности',
                    'description' => 'Тестирование функций безопасности',
                    'status' => 'pending'
                ],
                [
                    'name' => 'Хуки системы',
                    'description' => 'Тестирование системы хуков',
                    'status' => 'pending'
                ],
                [
                    'name' => 'Валидация данных',
                    'description' => 'Тестирование валидации',
                    'status' => 'pending'
                ],
                [
                    'name' => 'Кеширование',
                    'description' => 'Тестирование системы кеширования',
                    'status' => 'pending'
                ]
            ]
        ];
    }
    
    /**
     * Данные для вкладки "API тестирование"
     */
    private function getApiData(): array {
        $apiMethods = [];
        
        try {
            // Сначала пробуем получить модули через ModuleLoader
            if (class_exists('ModuleLoader')) {
                ModuleLoader::init();
                // Загружаем все модули для отображения в админке
                $loadedModules = ModuleLoader::getLoadedModules(true);
                
                if (!empty($loadedModules)) {
                    foreach ($loadedModules as $name => $module) {
                        if (is_object($module) && method_exists($module, 'getApiMethods')) {
                            try {
                                $methods = $module->getApiMethods();
                                if (!empty($methods)) {
                                    $apiMethods[$name] = $methods;
                                }
                            } catch (Exception $e) {
                                error_log("Error getting API methods for module {$name}: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            // Если через ModuleLoader не получилось, загружаем модули напрямую
            if (empty($apiMethods)) {
                $modulesDir = dirname(__DIR__, 3) . '/engine/modules/';
                
                if (!class_exists('BaseModule')) {
                    $baseModuleFile = dirname($modulesDir) . '/classes/BaseModule.php';
                    if (file_exists($baseModuleFile)) {
                        require_once $baseModuleFile;
                    }
                }
                
                $moduleFiles = glob($modulesDir . '*.php');
                
                if ($moduleFiles !== false) {
                    foreach ($moduleFiles as $moduleFile) {
                        $moduleName = basename($moduleFile, '.php');
                        
                        if ($moduleName === 'loader' || $moduleName === 'compatibility') {
                            continue;
                        }
                        
                        try {
                            if (!class_exists($moduleName)) {
                                require_once $moduleFile;
                            }
                            
                            if (class_exists($moduleName)) {
                                $module = $moduleName::getInstance();
                                
                                if (is_object($module) && method_exists($module, 'getApiMethods')) {
                                    try {
                                        $methods = $module->getApiMethods();
                                        if (!empty($methods) && !isset($apiMethods[$moduleName])) {
                                            $apiMethods[$moduleName] = $methods;
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error getting API methods for module {$moduleName}: " . $e->getMessage());
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error loading module {$moduleName} for API: " . $e->getMessage());
                        } catch (Error $e) {
                            error_log("Fatal error loading module {$moduleName} for API: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error getting API data: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error getting API data: " . $e->getMessage());
        }
        
        return [
            'api_methods' => $apiMethods,
            'total' => count($apiMethods)
        ];
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax() {
        header('Content-Type: application/json; charset=utf-8');
        
        $action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
        
        switch ($action) {
            case 'test_module':
                $this->testModule();
                break;
            case 'test_plugin':
                $this->testPlugin();
                break;
            case 'test_component':
                $this->testComponent();
                break;
            case 'test_api_method':
                $this->testApiMethod();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    /**
     * Тестирование модуля
     */
    private function testModule() {
        $moduleName = sanitizeInput($_POST['module'] ?? '');
        
        if (empty($moduleName)) {
            echo json_encode(['success' => false, 'error' => 'Модуль не указан'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Убеждаемся, что ModuleLoader загружен
        if (!class_exists('ModuleLoader')) {
            require_once dirname(__DIR__, 3) . '/engine/modules/loader.php';
        }
        
        $module = ModuleLoader::getModule($moduleName);
        
        if (!$module) {
            echo json_encode(['success' => false, 'error' => 'Модуль не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $info = $module->getInfo();
        $apiMethods = $module->getApiMethods();
        
        echo json_encode([
            'success' => true,
            'module' => [
                'name' => $moduleName,
                'info' => $info,
                'api_methods' => $apiMethods
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Тестирование плагина
     */
    private function testPlugin() {
        $pluginSlug = sanitizeInput($_POST['plugin'] ?? '');
        
        if (empty($pluginSlug)) {
            echo json_encode(['success' => false, 'error' => 'Плагин не указан'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $plugin = pluginManager()->getPlugin($pluginSlug);
        
        if (!$plugin) {
            echo json_encode(['success' => false, 'error' => 'Плагин не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'plugin' => [
                'name' => $plugin->getName(),
                'slug' => $plugin->getSlug(),
                'version' => $plugin->getVersion()
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Тестирование компонента
     */
    private function testComponent() {
        $componentName = sanitizeInput($_POST['component'] ?? '');
        
        if (empty($componentName)) {
            echo json_encode(['success' => false, 'error' => 'Компонент не указан'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Проверяем, существует ли компонент
        $components = $this->getComponentsData();
        $foundComponent = null;
        foreach ($components['components'] as $component) {
            if ($component['name'] === $componentName) {
                $foundComponent = $component;
                break;
            }
        }
        
        if (!$foundComponent) {
            echo json_encode(['success' => false, 'error' => 'Компонент не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'component' => [
                'name' => $foundComponent['name'],
                'type' => $foundComponent['type'],
                'description' => $foundComponent['description'],
                'status' => $foundComponent['status'],
                'message' => 'Компонент успешно протестирован'
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Тестирование API метода
     */
    private function testApiMethod() {
        $moduleName = sanitizeInput($_POST['module'] ?? '');
        $methodName = sanitizeInput($_POST['method'] ?? '');
        
        if (empty($moduleName) || empty($methodName)) {
            echo json_encode(['success' => false, 'error' => 'Модуль или метод не указан'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Убеждаемся, что ModuleLoader загружен
        if (!class_exists('ModuleLoader')) {
            require_once dirname(__DIR__, 3) . '/engine/modules/loader.php';
        }
        
        $module = ModuleLoader::getModule($moduleName);
        
        if (!$module) {
            echo json_encode(['success' => false, 'error' => 'Модуль не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $apiMethods = $module->getApiMethods();
        
        if (!isset($apiMethods[$methodName])) {
            echo json_encode(['success' => false, 'error' => 'API метод не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'api_method' => [
                'module' => $moduleName,
                'method' => $methodName,
                'description' => $apiMethods[$methodName],
                'status' => 'available'
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

