# Руководство по разработке

## Начало работы

### Требования

- PHP 8.4.0+
- MySQL 5.7+ / MariaDB 10.2+
- Composer (опционально, для зависимостей)
- Git (для версионирования)

### Настройка окружения

1. Клонируйте репозиторий
2. Настройте базу данных
3. Запустите установщик или импортируйте структуру БД
4. Настройте виртуальный хост

### Структура проекта

```
flowaxy.com/
├── .dev/              # Документация и разработка
├── engine/            # Ядро системы
├── plugins/           # Плагины
├── themes/            # Темы
└── storage/           # Хранилище данных
```

## Стандарты кодирования

### PSR-12

Flowaxy CMS следует стандарту PSR-12 для форматирования кода.

### Типизация

Всегда используйте строгую типизацию:

```php
<?php
declare(strict_types=1);

class MyClass {
    private string $property;
    
    public function method(string $param): string {
        return $param;
    }
}
```

### Именование

- **Классы**: `PascalCase` - `MyClass`
- **Методы**: `camelCase` - `myMethod()`
- **Константы**: `UPPER_SNAKE_CASE` - `MY_CONSTANT`
- **Переменные**: `camelCase` - `$myVariable`

## Работа с Git

### Ветки

- `main` - стабильная версия
- `develop` - разработка
- `feature/*` - новые функции
- `fix/*` - исправления багов

### Коммиты

Используйте понятные сообщения коммитов:

```
feat: добавлена система кеширования
fix: исправлена ошибка в Database::query()
docs: обновлена документация
refactor: рефакторинг RouterManager
```

## Отладка

### Логирование

```php
$logger = logger();
$logger->logDebug('Отладочное сообщение', ['data' => $data]);
$logger->logError('Ошибка выполнения', ['error' => $error]);
```

### Просмотр логов

Логи доступны в админ-панели: `/admin/logs-view`

### Обработка ошибок

Система автоматически логирует все ошибки. Для отладки включите отображение ошибок:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Тестирование

### Ручное тестирование

1. Проверьте все функции
2. Протестируйте на разных браузерах
3. Проверьте на мобильных устройствах
4. Проверьте производительность

### Автоматическое тестирование

(В разработке) Использование PHPUnit для unit-тестов.

## Производительность

### Оптимизация запросов

- Используйте индексы в БД
- Избегайте N+1 запросов
- Используйте кеширование

### Кеширование

```php
$cache = cache();
$data = $cache->get('key', function() {
    // Тяжелая операция
    return expensiveOperation();
}, 3600);
```

### Минимизация ресурсов

- Минифицируйте CSS/JS
- Используйте CDN для статики
- Оптимизируйте изображения

## Безопасность

### Валидация данных

Всегда валидируйте входные данные:

```php
use Engine\Classes\Validators\Validator;

$validator = new Validator();
if (!$validator->email($email)) {
    throw new InvalidArgumentException('Invalid email');
}
```

### Экранирование вывода

Всегда экранируйте вывод:

```php
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### SQL запросы

Всегда используйте prepared statements:

```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
```

## Документация кода

### PHPDoc

Документируйте все классы и методы:

```php
/**
 * Класс для работы с пользователями
 * 
 * @package Engine\Classes
 * @version 1.0.0
 */
class User {
    /**
     * Получение пользователя по ID
     * 
     * @param int $id ID пользователя
     * @return array|null Данные пользователя или null
     */
    public function getById(int $id): ?array {
        // ...
    }
}
```

## Релизы

### Версионирование

Используйте Semantic Versioning:
- `MAJOR.MINOR.PATCH`
- Пример: `7.0.0`

### Чеклист перед релизом

- [ ] Все тесты пройдены
- [ ] Документация обновлена
- [ ] Версия обновлена
- [ ] CHANGELOG обновлен
- [ ] Код проверен на безопасность

## Полезные ресурсы

- [Документация PHP 8.4](https://www.php.net/manual/ru/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

