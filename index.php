<?php
/**
 * Flowaxy CMS - Entry Point
 * Главная точка входа системы
 * 
 * @version 7.0.0
 */

declare(strict_types=1);

define('FLOWAXY', true);
define('FLOWAXY_CMS', true);
define('ROOT_DIR', dirname(__FILE__));
define('ENGINE_DIR', ROOT_DIR . '/engine');

// Проверка существования необходимых файлов
$requiredFiles = [
    'flowaxy.php' => 'Файл движка не найден',
    'init.php' => 'Файл инициализации не найден'
];

foreach ($requiredFiles as $file => $errorMessage) {
    $filePath = ENGINE_DIR . '/' . $file;
    if (!file_exists($filePath)) {
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body><h1>' . 
            htmlspecialchars($errorMessage) . '</h1><p>Файл engine/' . htmlspecialchars($file) . ' отсутствует.</p></body></html>');
    }
    require_once $filePath;
}
