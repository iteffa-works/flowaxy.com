<?php
/**
 * Сторінка документації движка
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class DocumentationPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Документація движка - Landing CMS';
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
        
        // Рендеримо сторінку
        $this->render([
            'documentation' => $documentation
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
                    'title' => 'Глобальні функції',
                    'description' => 'Доступні глобальні функції:',
                    'items' => [
                        'getDB() - Отримання підключення до БД',
                        'cache() - Отримання об\'єкта кешу',
                        'cache_remember() - Отримання з кешу або збереження',
                        'cache_forget() - Видалення з кешу',
                        'pluginManager() - Отримання менеджера плагінів',
                        'themeManager() - Отримання менеджера тем',
                        'mediaModule() - Отримання модуля медіа',
                        'adminUrl() - Генерація URL адмінки',
                        'doHook() - Виконання хука',
                        'addHook() - Додавання хука'
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

