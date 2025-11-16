<?php
/**
 * Головна сторінка сайту
 * Використовує універсальний роутер для обробки всіх запитів
 * 
 * @version 4.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/engine/init.php';

// Перевірка доступності БД перед завантаженням системи
if (!isDatabaseAvailable()) {
    showDatabaseError([
        'host' => DB_HOST,
        'database' => DB_NAME,
        'error' => 'Не вдалося підключитися до бази даних. Перевірте налаштування підключення.'
    ]);
    exit;
}

// Ініціалізація плагінів (для реєстрації хуків)
try {
    pluginManager()->initializePlugins();
} catch (Exception $e) {
    // Якщо помилка пов'язана з БД, показуємо сторінку помилки
    if (strpos($e->getMessage(), 'database') !== false || strpos($e->getMessage(), 'PDO') !== false) {
        showDatabaseError([
            'host' => DB_HOST,
            'database' => DB_NAME,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    throw $e;
}

// Завантаження модуля Menu для обробки шорткодів
if (class_exists('ModuleLoader')) {
    ModuleLoader::loadModule('Menu');
}

// Хук для обробки ранніх запитів (до завантаження теми)
// Плагіни можуть використовувати цей хук для обробки AJAX запитів та інших ранніх дій
$handled = doHook('handle_early_request', false);
if ($handled === true) {
    exit; // Запит оброблено плагіном
}

// Створюємо роутер для фронтенду (без базового шляху)
$router = new Router('/', null);

// Хук для реєстрації маршрутів (плагіни та теми можуть реєструвати свої маршрути)
doHook('register_routes', $router);

// Якщо тема підтримує маршрути через routes.php, вони будуть завантажені автоматично
// Якщо ні, використовуємо стандартну обробку через шаблон теми
$themeManager = themeManager();
$activeTheme = $themeManager->getActiveTheme();
$themePath = $themeManager->getThemePath();

// Перевіряємо, чи є routes.php у темі
$themeRoutesFile = $themePath . 'routes.php';
$file = new File($themeRoutesFile);
$hasThemeRoutes = $file->exists() && $file->isReadable();

// Якщо у темі немає routes.php, додаємо маршрут за замовчуванням
if (!$hasThemeRoutes && $activeTheme !== null && !empty($themePath)) {
    $themeTemplate = $themePath . 'index.php';
    $templateFile = new File($themeTemplate);
    
    if ($templateFile->exists() && $templateFile->isReadable()) {
        // Реєструємо маршрут для головної сторінки теми
        $router->get('', function() use ($themeTemplate) {
            include $themeTemplate;
        }, ['name' => 'theme.index']);
    }
}

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$handled = $router->dispatch();

// Якщо роутер не знайшов маршрут, показуємо сторінку про відсутність теми
if (!$handled && !$hasThemeRoutes) {
    // Якщо тема не знайдена або не активна, показуємо заглушку
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Активна тема не встановлена - Landing CMS</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
            }
            .container {
                text-align: center;
                max-width: 600px;
                padding: 40px 20px;
            }
            .icon {
                font-size: 80px;
                margin-bottom: 30px;
                opacity: 0.9;
            }
            h1 {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 20px;
                line-height: 1.2;
            }
            p {
                font-size: 18px;
                line-height: 1.6;
                margin-bottom: 30px;
                opacity: 0.95;
            }
            .btn {
                display: inline-block;
                padding: 14px 32px;
                background: #fff;
                color: #667eea;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            }
            .info {
                margin-top: 40px;
                padding: 20px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                font-size: 14px;
                opacity: 0.9;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🎨</div>
            <h1>Активна тема не встановлена</h1>
            <p>Для відображення сайту необхідно активувати тему в адмін-панелі.</p>
            <a href="/admin/themes" class="btn">Перейти до управління темами</a>
            <div class="info">
                <strong>Інструкція:</strong><br>
                Перейдіть в адмін-панель → Теми → Оберіть тему та натисніть "Активувати"
            </div>
        </div>
    </body>
    </html>
    <?php
}

