<?php
/**
 * Сторінка документації движка
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class DocumentationPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Документація движка - Flowaxy CMS';
        $this->templateName = 'documentation';
        
        $this->setPageHeader(
            'Документація движка',
            'Довідник для розробників Flowaxy CMS',
            'fas fa-book'
        );
    }
    
    public function handle() {
        // Отримуємо документацію
        $documentation = $this->getDocumentation();
        
        // Отримуємо API методи модулів
        $modulesApi = $this->getModulesApi();
        
        // Рендеримо сторінку
        $this->render([
            'documentation' => $documentation,
            'modulesApi' => $modulesApi
        ]);
    }
    
    /**
     * Отримання документації
     */
    private function getDocumentation() {
        return [
            'structure' => $this->getStructureDoc(),
            'classes' => $this->getClassesDoc(),
            'modules' => $this->getModulesDoc(),
            'hooks' => $this->getHooksDoc(),
            'api' => $this->getApiDoc()
        ];
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
    
    /**
     * Документація структури
     */
    private function getStructureDoc() {
        return [
            'title' => 'Структура проекту',
            'sections' => [
                [
                    'title' => 'engine/classes',
                    'description' => 'Основні класи системи, організовані за категоріями:',
                    'items' => [
                        'base/ - Базові класи (BaseModule, BasePlugin, ThemePlugin)',
                        'files/ - Класи для роботи з файлами (File, Json, Xml, Csv, Yaml, Image, Directory, Upload, MimeType)',
                        'data/ - Класи для роботи з даними (Database, Cache)',
                        'security/ - Класи безпеки (Hash, Encryption, Session, Security)',
                        'http/ - HTTP класи (Request, Response, Router, Cookie)',
                        'view/ - Класи відображення (View)',
                        'mail/ - Класи роботи з поштою (Mail)',
                        'managers/ - Менеджери (ThemeManager, MenuManager)',
                        'compilers/ - Компілятори (ScssCompiler)'
                    ]
                ],
                [
                    'title' => 'engine/modules',
                    'description' => 'Системні модулі:',
                    'items' => [
                        'PluginManager - Керування плагінами',
                        'Media - Керування медіафайлами',
                        'Menu - Керування меню',
                        'Config - Керування конфігурацією',
                        'ThemeManager - Керування темами'
                    ]
                ],
                [
                    'title' => 'engine/data',
                    'description' => 'Файли конфігурації та даних:',
                    'items' => [
                        'config.php - Глобальні константи',
                        'database.php - Константи бази даних'
                    ]
                ],
                [
                    'title' => 'engine/skins',
                    'description' => 'Інтерфейс адмінки:',
                    'items' => [
                        'pages/ - Сторінки адмінки',
                        'templates/ - Шаблони сторінок',
                        'includes/ - Включені файли (AdminPage, SimpleTemplate, menu-items)'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Документація класів
     */
    private function getClassesDoc() {
        return [
            'title' => 'Основні класи',
            'sections' => [
                [
                    'title' => 'BaseModule',
                    'description' => 'Базовий клас для всіх модулів системи. Забезпечує доступ до БД та базову функціональність.',
                    'methods' => [
                        'getInstance() - Отримання єдиного екземпляру модуля',
                        'getDB() - Отримання підключення до БД',
                        'getInfo() - Отримання інформації про модуль',
                        'getApiMethods() - Отримання API методів модуля',
                        'registerHooks() - Реєстрація хуків модуля'
                    ]
                ],
                [
                    'title' => 'BasePlugin',
                    'description' => 'Базовий клас для плагінів. Розширює BaseModule додатковою функціональністю плагінів.',
                    'methods' => [
                        'activate() - Активація плагіна',
                        'deactivate() - Деактивація плагіна',
                        'getInfo() - Отримання інформації про плагін',
                        'registerHooks() - Реєстрація хуків плагіна'
                    ]
                ],
                [
                    'title' => 'Router',
                    'description' => 'Універсальний роутер для адмінки та фронтенду.',
                    'methods' => [
                        'add() - Додавання маршруту',
                        'dispatch() - Обробка запиту',
                        'autoLoad() - Автоматичне завантаження маршрутів',
                        'loadModuleRoutes() - Завантаження маршрутів модулів',
                        'loadThemeRoutes() - Завантаження маршрутів теми'
                    ]
                ],
                [
                    'title' => 'Database',
                    'description' => 'Клас для роботи з базою даних. Використовує PDO.',
                    'methods' => [
                        'getInstance() - Отримання єдиного екземпляру',
                        'query() - Виконання SQL запиту',
                        'prepare() - Підготовка запиту',
                        'beginTransaction() - Початок транзакції',
                        'commit() - Підтвердження транзакції',
                        'rollBack() - Відкат транзакції'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Документація модулів
     */
    private function getModulesDoc() {
        return [
            'title' => 'Системні модулі',
            'sections' => [
                [
                    'title' => 'PluginManager',
                    'description' => 'Модуль керування плагінами. Завантажує, активує та деактивує плагіни.',
                    'methods' => [
                        'getAllPlugins() - Отримання всіх плагінів',
                        'getActivePlugins() - Отримання активних плагінів',
                        'installPlugin() - Встановлення плагіна',
                        'activatePlugin() - Активація плагіна',
                        'deactivatePlugin() - Деактивація плагіна',
                        'addHook() - Додавання хука',
                        'doHook() - Виконання хука'
                    ]
                ],
                [
                    'title' => 'Media',
                    'description' => 'Модуль керування медіафайлами. Завантаження, видалення та отримання файлів.',
                    'methods' => [
                        'uploadFile() - Завантаження файлу',
                        'deleteFile() - Видалення файлу',
                        'getFile() - Отримання файлу за ID',
                        'getFiles() - Отримання списку файлів з фільтрацією',
                        'updateFile() - Оновлення інформації про файл',
                        'getStats() - Отримання статистики медіа'
                    ]
                ],
                [
                    'title' => 'Menu',
                    'description' => 'Модуль керування меню навігації.',
                    'methods' => [
                        'getMenus() - Отримання всіх меню',
                        'getMenu() - Отримання меню за ID',
                        'createMenu() - Створення меню',
                        'updateMenu() - Оновлення меню',
                        'deleteMenu() - Видалення меню',
                        'getMenuItems() - Отримання пунктів меню'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Документація хуків
     */
    private function getHooksDoc() {
        return [
            'title' => 'Система хуків',
            'description' => 'Система хуків дозволяє плагінам та модулям додавати функціональність без зміни ядра.',
            'sections' => [
                [
                    'title' => 'Реєстрація хуків',
                    'description' => 'Хуки реєструються через метод registerHooks() в модулі або плагіні:',
                    'code' => '
public function registerHooks(): void {
    addHook(\'admin_menu\', [$this, \'addAdminMenuItem\']);
    addHook(\'admin_register_routes\', [$this, \'registerAdminRoute\']);
}'
                ],
                [
                    'title' => 'Доступні хуки',
                    'description' => 'Список доступних хуків в системі:',
                    'items' => [
                        'admin_menu - Модифікація меню адмінки',
                        'admin_register_routes - Реєстрація маршрутів адмінки',
                        'plugin_activated - Плагін активовано',
                        'plugin_deactivated - Плагін деактивовано',
                        'module_loaded - Модуль завантажено',
                        'module_error - Помилка завантаження модуля'
                    ]
                ],
                [
                    'title' => 'Використання хуків',
                    'description' => 'Приклад використання:',
                    'code' => '
// Додавання хука
addHook(\'admin_menu\', function($menu) {
    $menu[] = [
        \'href\' => adminUrl(\'my-page\'),
        \'text\' => \'Моя сторінка\',
        \'page\' => \'my-page\'
    ];
    return $menu;
});

// Виконання хука
$result = doHook(\'admin_menu\', $menu);'
                ]
            ]
        ];
    }
    
    /**
     * Документація API
     */
    private function getApiDoc() {
        return [
            'title' => 'API для розробників',
            'sections' => [
                [
                    'title' => 'База даних',
                    'description' => 'Функції для роботи з базою даних:',
                    'items' => [
                        'getDB(bool $showError = true): ?PDO - Отримання підключення до БД',
                        'isDatabaseAvailable(bool $showError = false): bool - Перевірка доступності БД',
                        'showDatabaseError(array $errorDetails): void - Відображення помилки БД'
                    ]
                ],
                [
                    'title' => 'Кешування',
                    'description' => 'Функції для роботи з кешем:',
                    'items' => [
                        'cache(): Cache - Отримання об\'єкта кешу',
                        'cache_get(string $key, $default = null) - Отримання з кешу',
                        'cache_set(string $key, $data, ?int $ttl = null): bool - Збереження в кеш',
                        'cache_remember(string $key, callable $callback, ?int $ttl = null) - Отримання з кешу або виконання callback',
                        'cache_forget(string $key): bool - Видалення з кешу',
                        'cache_flush(): bool - Очищення всього кешу'
                    ]
                ],
                [
                    'title' => 'Безпека',
                    'description' => 'Функції безпеки:',
                    'items' => [
                        'generateCSRFToken(): string - Генерація CSRF токена',
                        'verifyCSRFToken(?string $token): bool - Перевірка CSRF токена',
                        'isAdminLoggedIn(): bool - Перевірка авторизації адміна',
                        'requireAdmin(): void - Обов\'язкова авторизація адміна',
                        'sanitizeInput($input): string - Очищення вводу'
                    ]
                ],
                [
                    'title' => 'URL та навігація',
                    'description' => 'Функції для роботи з URL:',
                    'items' => [
                        'adminUrl(string $path = \'\'): string - Генерація URL адмінки',
                        'getProtocolRelativeUrl(string $path = \'\'): string - Отримання протокол-відносного URL',
                        'getUploadsUrl(string $filePath = \'\'): string - URL завантажень',
                        'toProtocolRelativeUrl(string $url): string - Конвертація в протокол-відносний URL',
                        'redirectTo(string $url): void - Перенаправлення'
                    ]
                ],
                [
                    'title' => 'Модулі та плагіни',
                    'description' => 'Функції для роботи з модулями та плагінами:',
                    'items' => [
                        'pluginManager(): PluginManager - Отримання менеджера плагінів',
                        'themeManager(): ThemeManager - Отримання менеджера тем',
                        'mediaModule(): Media - Отримання модуля медіа',
                        'menuModule(): Menu - Отримання модуля меню',
                        'menuManager(): MenuManager - Отримання менеджера меню',
                        'config(): Config - Отримання об\'єкта конфігурації'
                    ]
                ],
                [
                    'title' => 'Хуки',
                    'description' => 'Функції для роботи з хуками:',
                    'items' => [
                        'addHook(string $hookName, callable $callback, int $priority = 10): void - Додавання хука',
                        'doHook(string $hookName, $data = null) - Виконання хука',
                        'hasHook(string $hookName): bool - Перевірка наявності хука'
                    ]
                ],
                [
                    'title' => 'Конфігурація',
                    'description' => 'Функції для роботи з конфігурацією:',
                    'items' => [
                        'config_get(string $key, $default = null) - Отримання значення конфігурації',
                        'config_set(string $key, $value): bool - Встановлення значення конфігурації',
                        'config_has(string $key): bool - Перевірка наявності ключа',
                        'getSiteSettings(): array - Отримання всіх налаштувань сайту',
                        'getSetting(string $key, string $default = \'\'): string - Отримання налаштування'
                    ]
                ],
                [
                    'title' => 'Утиліти',
                    'description' => 'Допоміжні функції:',
                    'items' => [
                        'formatBytes(int $bytes, int $precision = 2): string - Форматування розміру файлу'
                    ]
                ],
                [
                    'title' => 'Робота з модулями',
                    'description' => 'Приклад створення модуля:',
                    'code' => '
class MyModule extends BaseModule {
    protected function init(): void {
        // Ініціалізація модуля
    }
    
    public function registerHooks(): void {
        addHook(\'admin_menu\', [$this, \'addMenuItem\']);
    }
    
    public function getInfo(): array {
        return [
            \'name\' => \'MyModule\',
            \'title\' => \'Мій модуль\',
            \'version\' => \'1.0.0\'
        ];
    }
    
    public function getApiMethods(): array {
        return [
            \'myMethod\' => \'Опис методу\'
        ];
    }
}'
                ]
            ]
        ];
    }
}

