<?php
/**
 * Главная страница сайта
 * Минимальный файл - только инициализация и подключение темы
 * 
 * @version 3.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

// Инициализация плагинов (для регистрации хуков)
pluginManager()->initializePlugins();

// Хук для обработки ранних запросов (до загрузки темы)
// Плагины могут использовать этот хук для обработки AJAX запросов и других ранних действий
$handled = doHook('handle_early_request', false);
if ($handled === true) {
    exit; // Запрос обработан плагином
}

// Получаем активную тему
$themeManager = themeManager();
$activeTheme = $themeManager->getActiveTheme();
$themePath = $themeManager->getThemePath();

// Проверяем, есть ли шаблон темы
if ($activeTheme !== null && !empty($themePath)) {
    $themeTemplate = $themePath . 'index.php';
    if (file_exists($themeTemplate) && is_readable($themeTemplate)) {
        // Используем шаблон темы
        include $themeTemplate;
        exit;
    }
}

// Если тема не найдена, показываем ошибку
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Помилка - Тема не знайдена</title>
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h1>Помилка</h1>
        <p>Тема не знайдена або не може бути завантажена.</p>
        <p><a href="/admin/">Перейти в адмін-панель</a></p>
    </div>
</body>
</html>
