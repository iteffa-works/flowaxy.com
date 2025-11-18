<?php
/**
 * Хелпер для работы с компонентами и шаблонами
 * Упрощает включение компонентов в шаблоны
 */

/**
 * Включить компонент
 * 
 * @param string $componentName Имя компонента (без расширения .php)
 * @param array $data Данные для передачи в компонент (будут извлечены как переменные)
 * @return void
 */
function includeComponent(string $componentName, array $data = []): void {
    $componentPath = __DIR__ . '/../components/' . $componentName . '.php';
    
    if (!file_exists($componentPath)) {
        trigger_error("Component not found: {$componentName}", E_USER_WARNING);
        return;
    }
    
    // Извлекаем переменные из данных
    extract($data);
    
    // Включаем компонент
    include $componentPath;
}

/**
 * Получить содержимое компонента в строку
 * 
 * @param string $componentName Имя компонента
 * @param array $data Данные для передачи в компонент
 * @return string Содержимое компонента
 */
function getComponent(string $componentName, array $data = []): string {
    ob_start();
    includeComponent($componentName, $data);
    return ob_get_clean();
}

/**
 * Включить шаблон
 * 
 * @param string $templateName Имя шаблона (без расширения .php)
 * @param array $data Данные для передачи в шаблон
 * @return void
 */
function includeTemplate(string $templateName, array $data = []): void {
    $templatePath = __DIR__ . '/../templates/' . $templateName . '.php';
    
    if (!file_exists($templatePath)) {
        trigger_error("Template not found: {$templateName}", E_USER_WARNING);
        return;
    }
    
    // Извлекаем переменные из данных
    extract($data);
    
    // Включаем шаблон
    include $templatePath;
}

/**
 * Получить содержимое шаблона в строку
 * 
 * @param string $templateName Имя шаблона
 * @param array $data Данные для передачи в шаблон
 * @return string Содержимое шаблона
 */
function getTemplate(string $templateName, array $data = []): string {
    ob_start();
    includeTemplate($templateName, $data);
    return ob_get_clean();
}

/**
 * Получить путь к ассету
 * 
 * @param string $assetPath Путь к ассету (относительно assets/)
 * @return string Полный URL к ассету
 */
function asset(string $assetPath): string {
    return UrlHelper::admin('assets/' . ltrim($assetPath, '/'));
}

