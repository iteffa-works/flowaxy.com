<?php
/**
 * Инициализация ядра системы
 * Подключение всех основных классов
 * 
 * @package Engine
 * @version 1.0.0
 */

declare(strict_types=1);

// Подключение основных классов CMS
$coreClasses = [
    'Validator',
    'BasePlugin',
    'Cache',
    'ThemeManager',
    'MenuManager',
    'ScssCompiler'
];

foreach ($coreClasses as $class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file) && is_readable($file)) {
        require_once $file;
    } else {
        error_log("Engine class file not found: {$file}");
    }
}

