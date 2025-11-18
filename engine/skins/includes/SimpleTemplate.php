<?php
/**
 * Простий шаблонізатор без складностей
 * Тільки основні функції без MVC
 */
class SimpleTemplate {
    private $templateDir;
    private $data = [];
    
    public function __construct() {
        $this->templateDir = __DIR__ . '/../templates/';
    }
    
    /**
     * Рендерить шаблон
     */
    public function render($template, $data = []) {
        // Убеждаемся, что все необходимые классы загружены
        $this->ensureClassesLoaded();
        
        // Об'єднуємо дані
        $this->data = array_merge($this->data, $data);
        
        // Витягуємо змінні
        extract($this->data);
        
        // Підключаємо базовий layout
        include __DIR__ . '/../layouts/base.php';
    }
    
    /**
     * Убеждаемся, что все необходимые классы загружены
     */
    private function ensureClassesLoaded(): void {
        $classes = ['UrlHelper', 'SecurityHelper', 'DatabaseHelper', 'SettingsManager'];
        foreach ($classes as $className) {
            if (!class_exists($className)) {
                // Пытаемся загрузить через автозагрузчик
                // Автозагрузчик должен загрузить класс автоматически
            }
        }
    }
    
    /**
     * Отримує контент шаблону (використовує View клас)
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
     * Рендерить компонент
     */
    public function component($component, $data = []) {
        $componentData = array_merge($this->data, $data);
        extract($componentData);
        
        // Компоненты теперь находятся в engine/skins/components/
        $componentFile = __DIR__ . '/../components/' . $component . '.php';
        if (file_exists($componentFile)) {
            include $componentFile;
        }
    }
    
    /**
     * Додає дані
     */
    public function assign($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Екранує HTML (використовує Security клас)
     */
    public function escape($string) {
        return Security::clean($string);
    }
    
    /**
     * Форматує дату
     */
    public function formatDate($date, $format = 'd.m.Y H:i') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        return $date instanceof DateTime ? $date->format($format) : '';
    }
    
    /**
     * Форматує число
     */
    public function formatNumber($number, $decimals = 0) {
        return number_format((float)$number, $decimals, ',', ' ');
    }
}

/**
 * Глобальна функція для рендерингу
 */
function renderTemplate($template, $data = []) {
    $engine = new SimpleTemplate();
    $engine->render($template, $data);
}
