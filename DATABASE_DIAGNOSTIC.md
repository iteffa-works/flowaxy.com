# Диагностика базы данных Landing CMS

## Используемые таблицы

### ✅ Основные таблицы (используются в коде)

1. **`cache`** - Кеширование данных
   - Используется в: `engine/classes/Cache.php`
   - Поля: `key`, `value`, `expiration`

2. **`media_files`** - Медиафайлы
   - Используется в: `engine/modules/Media.php`
   - Поля: `id`, `filename`, `original_name`, `file_path`, `file_url`, `file_size`, `mime_type`, `media_type`, `width`, `height`, `title`, `description`, `alt_text`, `uploaded_by`, `uploaded_at`, `updated_at`

3. **`menus`** - Меню навигации
   - Используется в: `engine/classes/MenuManager.php`
   - Поля: `id`, `name`, `slug`, `description`, `location`, `created_at`, `updated_at`

4. **`menu_items`** - Элементы меню
   - Используется в: `engine/classes/MenuManager.php`
   - Поля: `id`, `menu_id`, `parent_id`, `title`, `url`, `target`, `css_classes`, `icon`, `order_num`, `is_active`, `created_at`, `updated_at`

5. **`plugins`** - Плагины
   - Используется в: `engine/modules/PluginManager.php`
   - Поля: `id`, `slug`, `name`, `description`, `version`, `author`, `is_active`, `settings`, `installed_at`, `updated_at`

6. **`plugin_settings`** - Настройки плагинов
   - Используется в: `engine/classes/BasePlugin.php`, `engine/modules/PluginManager.php`
   - Поля: `id`, `plugin_slug`, `setting_key`, `setting_value`, `created_at`, `updated_at`

7. **`site_settings`** - Настройки сайта
   - Используется в: `config/config.php`, `engine/skins/pages/SettingsPage.php`, `engine/modules/Logger.php`
   - Поля: `id`, `setting_key`, `setting_value`, `created_at`, `updated_at`

8. **`themes`** - Темы оформления
   - Используется в: `engine/classes/ThemeManager.php`
   - Поля: `id`, `slug`, `name`, `description`, `version`, `author`, `is_active`, `created_at`, `updated_at`
   - **Примечание**: Метаданные тем теперь в `theme.json`, в БД хранится только активность

9. **`theme_settings`** - Настройки тем
   - Используется в: `engine/classes/ThemeManager.php`, `engine/skins/pages/CustomizerPage.php`
   - Поля: `id`, `theme_slug`, `setting_key`, `setting_value`, `created_at`, `updated_at`

10. **`users`** - Пользователи
    - Используется в: `engine/skins/pages/LoginPage.php`, `engine/skins/pages/ProfilePage.php`
    - Поля: `id`, `username`, `password`, `email`, `created_at`, `updated_at`

## ❌ Неиспользуемые таблицы (удалены)

1. **`catalog_categories`** - Категории каталога
   - Не найдено использования в коде
   - **Статус**: Удалена из структуры

2. **`pb_sections`** - Секции Page Builder
   - Не найдено использования в коде
   - **Статус**: Удалена из структуры

3. **`social_networks`** - Социальные сети
   - Не найдено использования в коде
   - **Статус**: Удалена из структуры

4. **`telegram_settings`** - Настройки Telegram
   - Не найдено использования в коде
   - **Статус**: Удалена из структуры

## Оптимизации

### Индексы
- Все таблицы имеют правильные индексы для быстрого поиска
- Уникальные ключи на `slug`, `username`, `setting_key`
- Составные индексы для связей (`plugin_slug`, `setting_key`)

### Внешние ключи
- `menu_items.menu_id` → `menus.id` (CASCADE)
- `menu_items.parent_id` → `menu_items.id` (CASCADE)

### Улучшения
- Удалены неиспользуемые поля из `themes` (`author_url`, `screenshot` - теперь в `theme.json`)
- Добавлен индекс на `users.email` для быстрого поиска
- Все таблицы используют `utf8mb4_unicode_ci` для поддержки эмодзи
- Используется `ON DUPLICATE KEY UPDATE` для безопасной инициализации

## Инициализация

База данных автоматически инициализируется:
- Создается администратор (username: `admin`, password: `admin`)
- Устанавливаются базовые настройки сайта
- Создается главное меню
- Настраиваются параметры логгера

## Рекомендации

1. **Безопасность**: Измените пароль администратора после первого входа
2. **Резервное копирование**: Регулярно делайте бэкапы базы данных
3. **Оптимизация**: Периодически выполняйте `OPTIMIZE TABLE` для больших таблиц
4. **Мониторинг**: Следите за размером таблицы `cache` и очищайте устаревшие записи

