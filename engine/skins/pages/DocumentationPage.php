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
            'api' => $this->getApiDoc(),
            'plugins' => $this->getPluginsDoc(),
            'themes' => $this->getThemesDoc()
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
    
    /**
     * Документація плагінів
     */
    private function getPluginsDoc() {
        return [
            'title' => 'Створення плагінів',
            'sections' => [
                [
                    'title' => 'Вступ',
                    'description' => 'Плагіни дозволяють розширювати функціональність CMS без зміни ядра системи. Всі плагіни наслідуються від класу BasePlugin.',
                    'items' => [
                        'Плагіни знаходяться в директорії /plugins/',
                        'Кожен плагін має свою папку з унікальним slug',
                        'Обов\'язковий файл: plugin.json з метаданими',
                        'Основний файл: клас плагіна, що наслідується від BasePlugin'
                    ]
                ],
                [
                    'title' => 'Структура плагіна',
                    'description' => 'Мінімальна структура плагіна:',
                    'code' => 'plugins/
  my-plugin/
    plugin.json          # Метадані плагіна
    MyPlugin.php        # Основний клас плагіна
    assets/
      css/
        style.css       # Стилі плагіна
      js/
        script.js       # Скрипти плагіна
    templates/
      admin.php         # Адмін-шаблон (опціонально)'
                ],
                [
                    'title' => 'plugin.json',
                    'description' => 'Файл метаданих плагіна:',
                    'code' => '{
    "slug": "my-plugin",
    "name": "Мій плагін",
    "version": "1.0.0",
    "description": "Опис функціональності плагіна",
    "author": "Ваше ім\'я",
    "requires": "1.0.0",
    "tested": "1.0.0"
}'
                ],
                [
                    'title' => 'Базовий клас плагіна',
                    'description' => 'Приклад створення простого плагіна:',
                    'code' => '<?php
/**
 * Приклад плагіна
 */
require_once __DIR__ . \'/../../../engine/classes/base/BasePlugin.php\';

class MyPlugin extends BasePlugin {
    
    /**
     * Ініціалізація плагіна
     */
    public function init() {
        // Реєстрація хуків
        addHook(\'admin_menu\', [$this, \'addAdminMenuItem\']);
        addHook(\'theme_head\', [$this, \'addHeadContent\']);
    }
    
    /**
     * Активація плагіна
     */
    public function activate() {
        // Створення таблиць, налаштувань тощо
        $this->createTables();
    }
    
    /**
     * Деактивація плагіна
     */
    public function deactivate() {
        // Очищення тимчасових даних
    }
    
    /**
     * Додавання пункту меню в адмінку
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            \'href\' => adminUrl(\'my-plugin\'),
            \'text\' => \'Мій плагін\',
            \'page\' => \'my-plugin\',
            \'icon\' => \'fas fa-star\',
            \'order\' => 100
        ];
        return $menu;
    }
    
    /**
     * Додавання контенту в head
     */
    public function addHeadContent() {
        echo \'<meta name="generator" content="MyPlugin">\';
    }
    
    /**
     * Створення таблиць
     */
    private function createTables() {
        $db = getDB();
        if (!$db) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS my_plugin_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
    }
}'
                ],
                [
                    'title' => 'Методи BasePlugin',
                    'description' => 'Доступні методи базового класу:',
                    'items' => [
                        'init() - Ініціалізація плагіна',
                        'activate() - Активація плагіна',
                        'deactivate() - Деактивація плагіна',
                        'install() - Встановлення плагіна',
                        'uninstall() - Видалення плагіна',
                        'getSettings(): array - Отримання всіх налаштувань',
                        'getSetting(string $key, $default) - Отримання налаштування',
                        'setSetting(string $key, $value): bool - Збереження налаштування',
                        'getSlug(): string - Отримання slug плагіна',
                        'getName(): string - Отримання назви плагіна',
                        'getVersion(): string - Отримання версії',
                        'enqueueStyle($handle, $file) - Підключення CSS',
                        'enqueueScript($handle, $file) - Підключення JS',
                        'getPluginUrl(): string - URL плагіна',
                        'getPluginPath(): string - Шлях до плагіна'
                    ]
                ],
                [
                    'title' => 'Робота з хуками',
                    'description' => 'Приклади використання хуків:',
                    'code' => '// Додавання хука
addHook(\'admin_menu\', [$this, \'addMenuItem\']);
addHook(\'theme_head\', function() {
    echo \'<meta name="viewport" content="width=device-width">\';
});

// Реєстрація маршруту
addHook(\'admin_register_routes\', function($router) {
    $router->add([\'GET\', \'POST\'], \'my-plugin\', \'MyPluginPage\');
});

// Підключення стилів та скриптів
$this->enqueueStyle(\'my-plugin-style\', \'assets/css/style.css\');
$this->enqueueScript(\'my-plugin-script\', \'assets/js/script.js\', [], true);'
                ],
                [
                    'title' => 'Доступні хуки для плагінів',
                    'description' => 'Список хуків, які можна використовувати:',
                    'items' => [
                        'admin_menu - Додавання пунктів меню адмінки',
                        'admin_register_routes - Реєстрація маршрутів адмінки',
                        'theme_head - Додавання контенту в <head>',
                        'theme_body_start - Після відкриття <body>',
                        'theme_footer - Перед закриттям </body>',
                        'theme_menu - Відображення меню теми',
                        'plugin_activated - Після активації плагіна',
                        'plugin_deactivated - Після деактивації плагіна'
                    ]
                ],
                [
                    'title' => 'Робота з базою даних',
                    'description' => 'Приклад роботи з БД в плагіні:',
                    'code' => '// Отримання підключення
$db = getDB();
if (!$db) {
    return;
}

// Підготовлений запит
$stmt = $db->prepare("SELECT * FROM my_table WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Вставка даних
$stmt = $db->prepare("INSERT INTO my_table (name, value) VALUES (?, ?)");
$stmt->execute([$name, $value]);

// Транзакції
$db->beginTransaction();
try {
    $db->exec("UPDATE table1 SET value = 1");
    $db->exec("UPDATE table2 SET value = 2");
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}'
                ],
                [
                    'title' => 'Робота з налаштуваннями',
                    'description' => 'Збереження та отримання налаштувань:',
                    'code' => '// Збереження налаштування
$this->setSetting(\'api_key\', \'my-secret-key\');

// Отримання налаштування
$apiKey = $this->getSetting(\'api_key\', \'default-value\');

// Отримання всіх налаштувань
$allSettings = $this->getSettings();'
                ],
                [
                    'title' => 'Створення адмін-сторінки',
                    'description' => 'Приклад створення сторінки в адмінці:',
                    'code' => '// В файлі plugins/my-plugin/MyPlugin.php
public function addAdminMenuItem(array $menu): array {
    $menu[] = [
        \'href\' => adminUrl(\'my-plugin\'),
        \'text\' => \'Мій плагін\',
        \'page\' => \'my-plugin\',
        \'icon\' => \'fas fa-cog\',
        \'order\' => 50
    ];
    return $menu;
}

// Реєстрація маршруту
public function registerRoutes($router) {
    addHook(\'admin_register_routes\', function($router) {
        $router->add([\'GET\', \'POST\'], \'my-plugin\', \'MyPluginAdminPage\');
    });
}

// Створення класу сторінки
// В файлі engine/skins/pages/MyPluginAdminPage.php
class MyPluginAdminPage extends AdminPage {
    public function __construct() {
        parent::__construct();
        $this->pageTitle = \'Мій плагін - Flowaxy CMS\';
        $this->templateName = \'my-plugin\';
    }
    
    public function handle() {
        // Обробка логіки
        $this->render([\'data\' => $data]);
    }
}'
                ]
            ]
        ];
    }
    
    /**
     * Документація тем
     */
    private function getThemesDoc() {
        return [
            'title' => 'Створення тем',
            'sections' => [
                [
                    'title' => 'Вступ',
                    'description' => 'Теми визначають зовнішній вигляд сайту. Всі теми знаходяться в директорії /themes/ і містять шаблони, стилі та скрипти.',
                    'items' => [
                        'Теми знаходяться в директорії /themes/',
                        'Кожна тема має свою папку з унікальним slug',
                        'Обов\'язковий файл: theme.json з метаданими',
                        'Обов\'язковий файл: index.php - головний шаблон',
                        'Підтримка SCSS компіляції (автоматично)'
                    ]
                ],
                [
                    'title' => 'Структура теми',
                    'description' => 'Мінімальна структура теми:',
                    'code' => 'themes/
  my-theme/
    theme.json           # Метадані теми
    index.php           # Головний шаблон (обов\'язково)
    style.css           # Головний CSS файл
    screenshot.png      # Скріншот теми (опціонально)
    assets/
      css/
        style.css       # Стилі теми
        main.css        # Додаткові стилі
      scss/
        main.scss       # SCSS файли (компілюються автоматично)
      js/
        main.js         # JavaScript файли
      images/
        logo.png        # Зображення теми
    templates/
      header.php        # Шаблон header (опціонально)
      footer.php        # Шаблон footer (опціонально)
      sidebar.php       # Шаблон sidebar (опціонально)
    functions.php       # Функції теми (опціонально)
    customizer.php      # Кастомізатор теми (опціонально)'
                ],
                [
                    'title' => 'theme.json',
                    'description' => 'Файл метаданих теми:',
                    'code' => '{
    "slug": "my-theme",
    "name": "Моя тема",
    "version": "1.0.0",
    "description": "Опис теми",
    "author": "Ваше ім\'я",
    "supports_customization": true,
    "supports_navigation": true,
    "requires": "1.0.0"
}'
                ],
                [
                    'title' => 'Головний шаблон (index.php)',
                    'description' => 'Базовий приклад index.php:',
                    'code' => '<?php
/**
 * Головний шаблон теми
 */

$themeManager = themeManager();
$themeUrl = $themeManager->getThemeUrl();

// Отримання налаштувань сайту
$siteTitle = getSetting(\'site_name\', \'Flowaxy CMS\');
$siteTagline = getSetting(\'site_tagline\', \'Сучасна CMS система\');
$siteDescription = getSetting(\'site_description\', \'\');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= safe_html($siteDescription) ?>">
    <title><?= safe_html($siteTitle) ?></title>
    
    <!-- Підключення стилів -->
    <link rel="stylesheet" href="<?= $themeManager->getStylesheetUrl() ?>">
    
    <?php doHook(\'theme_head\'); ?>
</head>
<body>
    <?php doHook(\'theme_body_start\'); ?>
    
    <header class="header">
        <div class="container">
            <a href="<?= SITE_URL ?>" class="logo">
                <h1><?= safe_html($siteTitle) ?></h1>
            </a>
            <nav class="nav">
                <?php doHook(\'theme_menu\'); ?>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <h2>Ласкаво просимо</h2>
            <p>Контент вашої теми</p>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date(\'Y\') ?> <?= safe_html($siteTitle) ?></p>
        </div>
    </footer>
    
    <?php doHook(\'theme_footer\'); ?>
</body>
</html>'
                ],
                [
                    'title' => 'Робота з хуками в темі',
                    'description' => 'Доступні хуки для тем:',
                    'items' => [
                        'theme_head - Додавання контенту в <head>',
                        'theme_body_start - Після відкриття <body>',
                        'theme_menu - Відображення навігаційного меню',
                        'theme_footer - Перед закриттям </body>',
                        'theme_content_before - Перед основним контентом',
                        'theme_content_after - Після основного контенту'
                    ]
                ],
                [
                    'title' => 'Використання функцій теми',
                    'description' => 'Приклад functions.php:',
                    'code' => '<?php
/**
 * Функції теми
 */

// Реєстрація меню
addHook(\'theme_menu\', function() {
    $menuModule = menuModule();
    if (!$menuModule) return;
    
    $menus = $menuModule->getMenus();
    $primaryMenu = null;
    
    foreach ($menus as $menu) {
        if ($menu[\'location\'] === \'primary\') {
            $primaryMenu = $menu;
            break;
        }
    }
    
    if ($primaryMenu) {
        $items = $menuModule->getMenuItems($primaryMenu[\'id\']);
        renderMenu($items);
    }
});

function renderMenu($items) {
    echo \'<ul class="menu">\';
    foreach ($items as $item) {
        $target = !empty($item[\'target\']) ? $item[\'target\'] : \'_self\';
        echo \'<li>\';
        echo \'<a href="\' . safe_html($item[\'url\']) . \'" target="\' . $target . \'">\';
        echo safe_html($item[\'title\']);
        echo \'</a>\';
        echo \'</li>\';
    }
    echo \'</ul>\';
}'
                ],
                [
                    'title' => 'Робота з налаштуваннями теми',
                    'description' => 'Отримання та збереження налаштувань теми:',
                    'code' => '<?php
// Отримання менеджера тем
$themeManager = themeManager();

// Отримання активної теми
$activeTheme = $themeManager->getActiveTheme();
$themeSlug = $activeTheme[\'slug\'] ?? \'\';

// Отримання налаштувань теми
$themeSettings = $themeManager->getThemeSettings($themeSlug);

// Отримання конкретного налаштування
$color = $themeManager->getThemeSetting($themeSlug, \'primary_color\', \'#0073aa\');

// Збереження налаштування (через БД)
$db = getDB();
if ($db) {
    $stmt = $db->prepare("
        INSERT INTO theme_settings (theme_slug, setting_key, setting_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([$themeSlug, \'primary_color\', \'#ff0000\']);
}'
                ],
                [
                    'title' => 'Підтримка SCSS',
                    'description' => 'Тема автоматично компілює SCSS файли:',
                    'code' => '// Структура SCSS файлів:
assets/
  scss/
    main.scss          # Головний файл
    _variables.scss    # Змінні
    _mixins.scss       # Міксини
    _header.scss       # Стилі header
    _footer.scss       # Стилі footer

// main.scss
@import \'variables\';
@import \'mixins\';
@import \'header\';
@import \'footer\';

body {
    font-family: $font-family;
    color: $text-color;
}

// Компіляція відбувається автоматично при зверненні до стилів'
                ],
                [
                    'title' => 'Кастомізатор теми',
                    'description' => 'Створення customizer.php для налаштування теми:',
                    'code' => '<?php
/**
 * Кастомізатор теми
 * Файл customizer.php дозволяє налаштовувати тему через адмінку
 */

return [
    \'sections\' => [
        [
            \'id\' => \'colors\',
            \'title\' => \'Кольори\',
            \'settings\' => [
                [
                    \'id\' => \'primary_color\',
                    \'label\' => \'Основний колір\',
                    \'type\' => \'color\',
                    \'default\' => \'#0073aa\'
                ],
                [
                    \'id\' => \'secondary_color\',
                    \'label\' => \'Додатковий колір\',
                    \'type\' => \'color\',
                    \'default\' => \'#005a87\'
                ]
            ]
        ],
        [
            \'id\' => \'typography\',
            \'title\' => \'Типографіка\',
            \'settings\' => [
                [
                    \'id\' => \'font_family\',
                    \'label\' => \'Шрифт\',
                    \'type\' => \'select\',
                    \'default\' => \'Arial\',
                    \'options\' => [
                        \'Arial\' => \'Arial\',
                        \'Georgia\' => \'Georgia\',
                        \'Verdana\' => \'Verdana\'
                    ]
                ]
            ]
        ]
    ]
];'
                ],
                [
                    'title' => 'Використання ThemePlugin',
                    'description' => 'Створення плагіна для теми (extends ThemePlugin):',
                    'code' => '<?php
/**
 * Плагін теми
 */
require_once __DIR__ . \'/../../../engine/classes/base/ThemePlugin.php\';

class MyThemePlugin extends ThemePlugin {
    
    public function init() {
        // Підключення стилів та скриптів
        $this->enqueueStyle(\'theme-style\', \'assets/css/style.css\');
        $this->enqueueScript(\'theme-script\', \'assets/js/main.js\');
        
        // Реєстрація хуків
        addHook(\'theme_menu\', [$this, \'renderMenu\']);
    }
    
    /**
     * Відображення меню
     */
    public function renderMenu() {
        $menuModule = menuModule();
        if (!$menuModule) return;
        
        $menus = $menuModule->getMenus();
        foreach ($menus as $menu) {
            if ($menu[\'location\'] === \'primary\') {
                $items = $menuModule->getMenuItems($menu[\'id\']);
                $this->renderMenuItems($items);
                break;
            }
        }
    }
    
    /**
     * Рендеринг пунктів меню
     */
    private function renderMenuItems($items) {
        echo \'<ul class="menu">\';
        foreach ($items as $item) {
            $target = $item[\'target\'] ?? \'_self\';
            echo \'<li>\';
            echo \'<a href="\' . safe_html($item[\'url\']) . \'" target="\' . $target . \'">\';
            echo safe_html($item[\'title\']);
            echo \'</a>\';
            echo \'</li>\';
        }
        echo \'</ul>\';
    }
    
    /**
     * Отримання URL теми
     */
    public function getThemeAssetUrl($path) {
        return $this->getThemeUrl() . \'/\' . ltrim($path, \'/\');
    }
}'
                ],
                [
                    'title' => 'Методи ThemeManager',
                    'description' => 'Доступні методи для роботи з темами:',
                    'items' => [
                        'getActiveTheme(): ?array - Отримання активної теми',
                        'getAllThemes(): array - Отримання всіх тем',
                        'getTheme(string $slug): ?array - Отримання теми за slug',
                        'activateTheme(string $slug): bool - Активація теми',
                        'getThemePath(?string $slug): string - Шлях до теми',
                        'getThemeUrl(?string $slug): string - URL теми',
                        'getStylesheetUrl(): string - URL стилів теми',
                        'getThemeSettings(string $slug): array - Налаштування теми',
                        'getThemeSetting(string $slug, string $key, $default) - Налаштування',
                        'setThemeSetting(string $slug, string $key, $value): bool - Збереження'
                    ]
                ],
                [
                    'title' => 'Доступні функції в темі',
                    'description' => 'Глобальні функції, доступні в шаблонах:',
                    'items' => [
                        'themeManager(): ThemeManager - Менеджер тем',
                        'getSetting(string $key, $default) - Налаштування сайту',
                        'getSiteSettings(): array - Всі налаштування',
                        'SITE_URL - URL сайту',
                        'ADMIN_URL - URL адмінки',
                        'adminUrl(string $path) - URL адмінки',
                        'safe_html(string $text) - Безпечний вивід HTML',
                        'doHook(string $hook, $data) - Виконання хука',
                        'menuModule(): Menu - Модуль меню',
                        'getProtocolRelativeUrl(string $path) - URL без протоколу'
                    ]
                ]
            ]
        ];
    }
}

