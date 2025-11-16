# Структура классов (Classes Structure)

Классы организованы по категориям для лучшей навигации и поддержки.

## Структура директорий

### `base/` - Базовые классы
Базовые абстрактные классы для расширения функциональности системы:
- **BaseModule.php** - Базовый класс для всех системных модулей
- **BasePlugin.php** - Базовый класс для всех плагинов
- **ThemePlugin.php** - Базовый класс для работы с шаблонами темы (расширяет BasePlugin)

### `files/` - Работа с файлами
Классы для работы с различными типами файлов:
- **File.php** - Общие операции с файлами (чтение, запись, копирование, удаление)
- **Ini.php** - Работа с INI файлами
- **Json.php** - Работа с JSON файлами
- **Xml.php** - Работа с XML файлами (парсинг, создание, XPath)
- **Csv.php** - Работа с CSV файлами (чтение, запись, манипуляции)
- **Yaml.php** - Работа с YAML файлами (требуется расширение yaml или symfony/yaml)
- **Zip.php** - Работа с ZIP архивами
- **Image.php** - Работа с изображениями (ресайз, кроп, конвертация форматов)
- **Directory.php** - Работа с директориями (создание, удаление, копирование)
- **Upload.php** - Безопасная загрузка файлов с валидацией
- **MimeType.php** - Определение MIME типов файлов

**Примечание:** Config.php перемещен в `engine/modules/`

### `data/` - Работа с данными
Классы для работы с данными и кешированием:
- **Cache.php** - Система кеширования
- **Database.php** - Работа с базой данных (PDO обертка)

### `managers/` - Менеджеры
Менеджеры для управления различными компонентами системы:
- **MenuManager.php** - Управление меню
- **ThemeManager.php** - Управление темами

### `compilers/` - Компиляторы
Классы для компиляции и обработки файлов:
- **ScssCompiler.php** - Компиляция SCSS в CSS

### `validators/` - Валидаторы
Классы для валидации данных:
- **Validator.php** - Валидация различных типов данных

### `security/` - Безопасность
Классы для обеспечения безопасности:
- **Security.php** - Защита от XSS, CSRF, SQL инъекций и других атак
- **Hash.php** - Хеширование паролей и создание токенов
- **Encryption.php** - Шифрование и расшифровка данных
- **Session.php** - Управление сессиями пользователей

### `http/` - HTTP запросы и ответы
Классы для работы с HTTP:
- **Request.php** - Обработка HTTP запросов (GET, POST, FILES)
- **Response.php** - Отправка HTTP ответов, редиректы, JSON
- **Cookie.php** - Управление cookies

### `view/` - Представления
Классы для работы с шаблонами:
- **View.php** - Рендеринг шаблонов с передачей данных

### `mail/` - Почта
Классы для отправки email:
- **Mail.php** - Отправка писем через mail() или SMTP

## Автозагрузка классов

Все классы загружаются автоматически через `spl_autoload_register` в `config/config.php`. 
Автозагрузчик автоматически ищет классы в соответствующих подкаталогах.

## Добавление новых классов

При добавлении нового класса:
1. Определите категорию класса
2. Поместите файл класса в соответствующую директорию
3. Если необходимо, добавьте класс в карту подкаталогов в `config/config.php` (автозагрузчик также ищет во всех подкаталогах)

## Примеры использования

```php
// Все классы доступны напрямую через автозагрузчик

// Работа с файлами
$json = new Json('path/to/file.json');
$file = new File('path/to/file.txt');
$file->write('content');
$zip = Zip::unpack('archive.zip', 'destination/');

// Безопасность
$hash = Hash::make('password');
$encrypted = Encryption::encrypt('sensitive data');
Session::set('key', 'value');
Security::csrfToken();

// HTTP
$request = Request::getInstance();
$value = $request->get('key');
Response::json(['success' => true]);
Cookie::set('name', 'value');

// Работа с данными
$db = Database::getInstance();
$cache = Cache::getInstance();

// Представления
View::render('template', ['data' => 'value']);

// Почта
Mail::send('user@example.com', 'Subject', 'Body');

// Робота з темами через ThemePlugin
class MyThemePlugin extends ThemePlugin {
    public function init() {
        // Підключення стилів
        $this->enqueueStyle('theme-style', 'assets/css/style.css');
        $this->enqueueScript('theme-script', 'assets/js/main.js', [], true);
        
        // Рендеринг шаблону
        $content = $this->renderTemplate('partials/header', ['title' => 'Заголовок']);
        
        // Отримання налаштувань теми
        $setting = $this->getThemeSetting('color_scheme', 'default');
    }
}
```

## Обратная совместимость

Автозагрузчик поддерживает обратную совместимость - если класс не найден в подкаталоге, 
он будет искать в корневой директории `engine/classes/`.

