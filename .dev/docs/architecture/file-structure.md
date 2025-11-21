# Структура файлов Flowaxy CMS

## Корневая директория

```
flowaxy.com/
├── .dev/                    # Документация и разработка
├── engine/                  # Ядро системы
├── plugins/                 # Плагины
├── themes/                  # Темы
├── storage/                 # Хранилище данных
├── uploads/                 # Загруженные файлы
├── index.php                # Точка входа
└── .htaccess                # Конфигурация Apache
```

## Директория engine/

### Основные файлы
- `flowaxy.php` - Автозагрузчик классов и константы
- `init.php` - Инициализация системы

### engine/classes/ - Классы системы

#### base/
- `BaseModule.php` - Базовый класс модулей
- `BasePlugin.php` - Базовый класс плагинов

#### data/
- `Database.php` - Абстракция базы данных
- `Cache.php` - Система кеширования
- `Logger.php` - Логирование
- `Config.php` - Конфигурация
- `SystemConfig.php` - Системная конфигурация

#### files/
- `File.php` - Работа с файлами
- `Directory.php` - Работа с директориями
- `Upload.php` - Загрузка файлов
- `Image.php` - Обработка изображений
- `Zip.php` - Архивация
- `Json.php` - Работа с JSON
- `Xml.php` - Работа с XML
- `Yaml.php` - Работа с YAML
- `Ini.php` - Работа с INI
- `Csv.php` - Работа с CSV
- `MimeType.php` - Определение MIME-типов

#### http/
- `Request.php` - Обработка HTTP запросов
- `Response.php` - Формирование HTTP ответов
- `Router.php` - Маршрутизация
- `AjaxHandler.php` - Обработка AJAX запросов
- `ApiHandler.php` - Обработка API запросов
- `ApiController.php` - Контроллер API
- `Cookie.php` - Работа с cookies
- `WebhookDispatcher.php` - Отправка webhooks

#### managers/
- `PluginManager.php` - Управление плагинами
- `ThemeManager.php` - Управление темами
- `SettingsManager.php` - Управление настройками
- `RoleManager.php` - Управление ролями
- `RouterManager.php` - Управление роутингом
- `ApiManager.php` - Управление API ключами
- `WebhookManager.php` - Управление webhooks
- `CookieManager.php` - Управление cookies
- `SessionManager.php` - Управление сессиями
- `StorageManager.php` - Управление клиентским хранилищем
- `StorageFactory.php` - Фабрика менеджеров хранилища
- `ThemeCustomizer.php` - Кастомизация тем
- `InstallerManager.php` - Управление установкой

#### security/
- `Security.php` - Основные функции безопасности
- `Hash.php` - Хеширование
- `Encryption.php` - Шифрование
- `Session.php` - Управление сессиями

#### system/
- `HookManager.php` - Управление хуками
- `ModuleLoader.php` - Загрузка модулей

#### helpers/
- `SecurityHelper.php` - Хелперы безопасности
- `DatabaseHelper.php` - Хелперы БД
- `UrlHelper.php` - Хелперы URL

#### validators/
- `Validator.php` - Валидация данных

#### view/
- `View.php` - Шаблонизация

#### ui/
- `ModalHandler.php` - Модальные окна

#### mail/
- `Mail.php` - Отправка почты

#### compilers/
- `ScssCompiler.php` - Компиляция SCSS

#### storage/
- `StorageInterface.php` - Интерфейс хранилища

### engine/includes/ - Вспомогательные файлы
- `functions.php` - Глобальные функции
- `role-functions.php` - Функции для работы с ролями
- `roles-init.php` - Инициализация ролей
- `router-handler.php` - Обработчик роутинга
- `api-routes.php` - API маршруты
- `installer-handler.php` - Обработчик установки
- `migrations/` - Миграции БД

### engine/skins/ - Административная панель

#### pages/
Страницы админки:
- `DashboardPage.php`
- `PluginsPage.php`
- `ThemesPage.php`
- `SettingsPage.php`
- `SiteSettingsPage.php`
- `RolesPage.php`
- `UsersPage.php`
- `ProfilePage.php`
- `ApiKeysPage.php`
- `WebhooksPage.php`
- `CacheViewPage.php`
- `LogsViewPage.php`
- `CustomizerPage.php`
- `ThemeEditorPage.php`
- `LoginPage.php`
- `LogoutPage.php`

#### templates/
Шаблоны страниц админки

#### components/
UI компоненты:
- `alert.php`
- `badge.php`
- `button.php`
- `card.php`
- `content-section.php`
- `empty-state.php`
- `form-group.php`
- `header.php`
- `modal.php`
- `notifications.php`
- `page-header.php`
- `plugin-card.php`
- `sidebar.php`
- `spinner.php`
- `stats-card.php`
- `table.php`
- `theme-card.php`
- `upload-modal.php`

#### includes/
- `AdminPage.php` - Базовый класс страниц админки
- `SimpleTemplate.php` - Упрощенный шаблонизатор
- `ComponentHelper.php` - Хелпер компонентов
- `menu-items.php` - Элементы меню
- `admin-routes.php` - Маршруты админки

#### layouts/
- `base.php` - Базовый макет

#### assets/
- `styles/` - CSS файлы
- `scripts/` - JavaScript файлы
- `images/` - Изображения

### engine/templates/ - Системные шаблоны
- `installer.php` - Шаблон установщика
- `error-404.php` - Страница 404
- `error-500.php` - Страница 500
- `database-error.php` - Ошибка БД
- `theme-not-installed.php` - Тема не установлена

## Директория storage/

```
storage/
├── cache/          # Кеш-файлы
├── logs/           # Логи системы
└── uploads/        # Загруженные файлы (символическая ссылка на uploads/)
```

## Директория plugins/

Каждый плагин в отдельной папке:
```
plugins/
└── plugin-name/
    ├── plugin.json    # Метаданные плагина
    ├── index.php      # Точка входа
    ├── migrations/    # Миграции БД
    └── assets/        # Статические файлы
```

## Директория themes/

Каждая тема в отдельной папке:
```
themes/
└── theme-name/
    ├── theme.json     # Метаданные темы
    ├── index.php      # Точка входа
    ├── style.css      # Стили темы
    ├── functions.php  # Функции темы
    └── screenshot.png # Превью темы
```

## Константы системы

Определяются в `engine/flowaxy.php`:
- `ROOT_DIR` - Корневая директория
- `ENGINE_DIR` - Директория движка
- `SITE_URL` - URL сайта
- `ADMIN_URL` - URL админки
- `UPLOADS_DIR` - Директория загрузок
- `CACHE_DIR` - Директория кеша
- `LOGS_DIR` - Директория логов

