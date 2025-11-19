<?php
/**
 * Flowaxy CMS - Entry Point
 * Главная точка входа системы
 * 
 * @version 7.0.0
 */

define('FLOWAXY', true);
define('ROOT_DIR', dirname(__FILE__));
define('ENGINE_DIR', ROOT_DIR . '/engine');

// Проверка существования необходимых файлов
$flowaxyFile = ENGINE_DIR . '/flowaxy.php';
if (!file_exists($flowaxyFile)) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body><h1>Файл движка не найден</h1><p>Файл engine/flowaxy.php отсутствует.</p></body></html>');
}

$initFile = ENGINE_DIR . '/init.php';
if (!file_exists($initFile)) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body><h1>Файл инициализации не найден</h1><p>Файл engine/init.php отсутствует.</p></body></html>');
}

// Подключаем ядро системы
require_once $flowaxyFile;

// Подключаем инициализацию
require_once $initFile;
