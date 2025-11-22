# Theme Name

Опис теми

## Встановлення

1. Скопіюйте папку `theme-template` в папку `themes`
2. Перейменуйте папку на назву вашої теми (наприклад, `my-theme`)
3. Оновіть `theme.json` з інформацією про вашу тему
4. Оновіть `index.php` з вашим HTML/CSS/JS

## Структура

```
theme-name/
├── theme.json           # Метадані теми
├── index.php            # Головний шаблон (обов'язково)
├── templates/           # Шаблони сторінок (опціонально)
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
├── assets/              # Ресурси теми
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── admin/               # Адмін-панель теми (опціонально)
│   └── SettingsPage.php
└── README.md            # Документація
```

## Обов'язкові файли

- `theme.json` - метадані теми
- `index.php` - головний шаблон

## Опціональні можливості

- `supports_customization: true` - підтримка кастомізації
- `supports_navigation: true` - підтримка навігації
- `admin/SettingsPage.php` - сторінка налаштувань теми

## Використання

Після встановлення тема буде доступна в адмін-панелі в розділі "Теми".

