<?php
/**
 * Простой шаблонизатор без сложностей
 * Только основные функции без MVC
 */
class SimpleTemplate {
    private $templateDir;
    private $data = [];
    
    public function __construct() {
        $this->templateDir = __DIR__ . '/../templates/';
    }
    
    /**
     * Рендерит шаблон
     */
    public function render($template, $data = []) {
        // Объединяем данные
        $this->data = array_merge($this->data, $data);
        
        // Извлекаем переменные
        extract($this->data);
        
        // Подключаем базовый layout
        include $this->templateDir . 'layout/base.php';
    }
    
    /**
     * Получает контент шаблона (використовує View клас)
     */
    public function getContent($templateName, $data = []) {
        $templateData = array_merge($this->data, $data);
        
        try {
            // Використовуємо View клас для рендерингу
            $viewPath = str_replace('.php', '', $templateName);
            return View::render($viewPath, $templateData);
        } catch (Exception $e) {
            // Fallback на старий метод
            extract($templateData);
            $templateFile = $this->templateDir . $templateName . '.php';
            if (file_exists($templateFile)) {
                ob_start();
                include $templateFile;
                return ob_get_clean();
            }
        }
        
        return '';
    }
    
    /**
     * Рендерит компонент
     */
    public function component($component, $data = []) {
        $componentData = array_merge($this->data, $data);
        extract($componentData);
        
        $componentFile = $this->templateDir . 'components/' . $component . '.php';
        if (file_exists($componentFile)) {
            include $componentFile;
        }
    }
    
    /**
     * Добавляет данные
     */
    public function assign($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Экранирует HTML (використовує Security клас)
     */
    public function escape($string) {
        return Security::clean($string);
    }
    
    /**
     * Форматирует дату
     */
    public function formatDate($date, $format = 'd.m.Y H:i') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        return $date instanceof DateTime ? $date->format($format) : '';
    }
    
    /**
     * Форматирует число
     */
    public function formatNumber($number, $decimals = 0) {
        return number_format((float)$number, $decimals, ',', ' ');
    }
}

/**
 * Глобальная функция для рендеринга
 */
function renderTemplate($template, $data = []) {
    $engine = new SimpleTemplate();
    $engine->render($template, $data);
}
