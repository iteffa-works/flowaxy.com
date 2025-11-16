<?php
/**
 * Головна точка входу системи
 * Мінімальна ініціалізація та запуск роутера
 * 
 * @version 5.0.0
 */

declare(strict_types=1);

// Підключаємо ініціалізацію системи
require_once __DIR__ . '/engine/init.php';

// Перевірка доступності БД
if (!isDatabaseAvailable()) {
    showDatabaseError([
        'host' => DB_HOST,
        'database' => DB_NAME,
        'error' => 'Не вдалося підключитися до бази даних. Перевірте налаштування підключення.'
    ]);
    exit;
}

// Ініціалізація плагінів
try {
    pluginManager()->initializePlugins();
} catch (Exception $e) {
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

// Хук для обробки ранніх запитів (AJAX, API тощо)
$handled = doHook('handle_early_request', false);
if ($handled === true) {
    exit;
}

// Створюємо роутер для фронтенду
$router = new Router('/', null);

// Хук для реєстрації маршрутів (плагіни та теми можуть реєструвати свої маршрути)
doHook('register_routes', $router);

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$router->dispatch();

