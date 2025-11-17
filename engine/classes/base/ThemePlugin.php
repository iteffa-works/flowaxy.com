<?php
/**
 * Базовий клас для роботи з шаблонами теми
 * Надає зручні методи для роботи з шаблонами, хуками, стилями та скриптами теми
 * 
 * @package Engine\Classes\Base
 * @version 1.0.0
 */

declare(strict_types=1);

abstract class ThemePlugin extends BasePlugin {
    protected ?ThemeManager $themeManager = null;
    protected ?array $themeData = null;
    protected array $enqueuedStyles = [];
    protected array $enqueuedScripts = [];
    
    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();
        
        try {
            if (function_exists('themeManager')) {
                $this->themeManager = themeManager();
                $this->loadThemeData();
            }
        } catch (Exception $e) {
            error_log("ThemePlugin constructor error: " . $e->getMessage());
            $this->themeManager = null;
        }
    }
    
    /**
     * Завантаження даних активної теми
     * 
     * @return void
     */
    private function loadThemeData(): void {
        if ($this->themeManager === null) {
            return;
        }
        
        try {
            $this->themeData = $this->themeManager->getActiveTheme();
        } catch (Exception $e) {
            error_log("ThemePlugin loadThemeData error: " . $e->getMessage());
            $this->themeData = null;
        }
    }
    
    /**
     * Отримання менеджера тем
     * 
     * @return ThemeManager|null
     */
    protected function getThemeManager(): ?ThemeManager {
        return $this->themeManager;
    }
    
    /**
     * Отримання даних активної теми
     * 
     * @return array|null
     */
    protected function getThemeData(): ?array {
        return $this->themeData;
    }
    
    /**
     * Отримання slug активної теми
     * 
     * @return string
     */
    protected function getThemeSlug(): string {
        return $this->themeData['slug'] ?? '';
    }
    
    /**
     * Отримання URL теми
     * 
     * @return string
     */
    protected function getThemeUrl(): string {
        return $this->themeManager !== null ? $this->themeManager->getThemeUrl() : '';
    }
    
    /**
     * Отримання шляху до теми
     * 
     * @return string
     */
    protected function getThemePath(): string {
        return $this->themeManager !== null ? $this->themeManager->getThemePath() : '';
    }
    
    /**
     * Рендеринг шаблону теми
     * 
     * @param string $template Назва шаблону (без розширення)
     * @param array $data Дані для передачі в шаблон
     * @return string
     * @throws Exception Якщо шаблон не знайдено
     */
    protected function renderTemplate(string $template, array $data = []): string {
        $themePath = $this->getThemePath();
        
        if (empty($themePath)) {
            throw new Exception("Шлях до теми не знайдено");
        }
        
        $templateFile = $themePath . $template . '.php';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Шаблон не знайдено: {$template}");
        }
        
        // Виділяємо змінні з масиву даних
        extract($data, EXTR_SKIP);
        
        // Захоплюємо вивід шаблону
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }
    
    /**
     * Перевірка існування шаблону
     * 
     * @param string $template Назва шаблону
     * @return bool
     */
    protected function templateExists(string $template): bool {
        $themePath = $this->getThemePath();
        
        if (empty($themePath)) {
            return false;
        }
        
        return file_exists($themePath . $template . '.php');
    }
    
    /**
     * Отримання налаштування теми
     * 
     * @param string $key Ключ налаштування
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    protected function getThemeSetting(string $key, mixed $default = null): mixed {
        if ($this->themeManager === null) {
            return $default;
        }
        
        try {
            return $this->themeManager->getSetting($key, $default);
        } catch (Exception $e) {
            error_log("ThemePlugin getThemeSetting error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Встановлення налаштування теми
     * 
     * @param string $key Ключ налаштування
     * @param mixed $value Значення
     * @return bool
     */
    protected function setThemeSetting(string $key, mixed $value): bool {
        if ($this->themeManager === null) {
            return false;
        }
        
        try {
            return $this->themeManager->setSetting($key, $value);
        } catch (Exception $e) {
            error_log("ThemePlugin setThemeSetting error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Підключення CSS файлу теми
     * 
     * @param string $handle Унікальний ідентифікатор
     * @param string $file Шлях до файлу відносно папки теми (наприклад, 'assets/css/style.css')
     * @param array $dependencies Залежності
     * @param string|null $version Версія для кешування
     * @return void
     */
    public function enqueueStyle($handle, $file, $dependencies = [], $version = null) {
        if (isset($this->enqueuedStyles[$handle])) {
            return; // Вже підключено
        }
        
        $themeUrl = $this->getThemeUrl();
        $url = $themeUrl . $file;
        
        // Додаємо версію якщо вказано
        if ($version !== null) {
            $url .= '?v=' . urlencode((string)$version);
        } else {
            // Автоматично додаємо версію з файлу (mtime)
            $themePath = $this->getThemePath();
            $filePath = $themePath . $file;
            if (file_exists($filePath)) {
                $mtime = filemtime($filePath);
                if ($mtime !== false) {
                    $url .= '?v=' . $mtime;
                }
            }
        }
        
        $this->enqueuedStyles[$handle] = [
            'url' => $url,
            'handle' => $handle,
            'dependencies' => $dependencies
        ];
        
        // Додаємо хук для виводу
        addHook('theme_head', function() use ($url, $handle) {
            echo "<link rel='stylesheet' id='{$handle}-css' href='{$url}' type='text/css' media='all' />\n";
        });
    }
    
    /**
     * Підключення JavaScript файлу теми
     * 
     * @param string $handle Унікальний ідентифікатор
     * @param string $file Шлях до файлу відносно папки теми
     * @param array $dependencies Залежності
     * @param bool $inFooter Підключати в footer
     * @param string|null $version Версія для кешування
     * @return void
     */
    public function enqueueScript($handle, $file, $dependencies = [], $inFooter = true, $version = null) {
        if (isset($this->enqueuedScripts[$handle])) {
            return; // Вже підключено
        }
        
        $themeUrl = $this->getThemeUrl();
        $url = $themeUrl . $file;
        
        // Додаємо версію якщо вказано
        if ($version !== null) {
            $url .= '?v=' . urlencode((string)$version);
        } else {
            // Автоматично додаємо версію з файлу (mtime)
            $themePath = $this->getThemePath();
            $filePath = $themePath . $file;
            if (file_exists($filePath)) {
                $mtime = filemtime($filePath);
                if ($mtime !== false) {
                    $url .= '?v=' . $mtime;
                }
            }
        }
        
        $this->enqueuedScripts[$handle] = [
            'url' => $url,
            'handle' => $handle,
            'dependencies' => $dependencies,
            'inFooter' => $inFooter
        ];
        
        // Додаємо хук для виводу
        $hookName = $inFooter ? 'theme_footer' : 'theme_head';
        addHook($hookName, function() use ($url, $handle) {
            echo "<script id='{$handle}-js' src='{$url}'></script>\n";
        });
    }
    
    /**
     * Видалення підключеного стилю
     * 
     * @param string $handle Ідентифікатор
     * @return void
     */
    protected function dequeueStyle(string $handle): void {
        unset($this->enqueuedStyles[$handle]);
    }
    
    /**
     * Видалення підключеного скрипта
     * 
     * @param string $handle Ідентифікатор
     * @return void
     */
    protected function dequeueScript(string $handle): void {
        unset($this->enqueuedScripts[$handle]);
    }
    
    /**
     * Отримання URL зображення з теми
     * 
     * @param string $file Шлях до файлу відносно папки теми
     * @return string
     */
    protected function getThemeImageUrl(string $file): string {
        return $this->getThemeUrl() . $file;
    }
    
    /**
     * Отримання URL файлу з теми
     * 
     * @param string $file Шлях до файлу відносно папки теми
     * @return string
     */
    protected function getThemeFileUrl(string $file): string {
        return $this->getThemeUrl() . $file;
    }
    
    /**
     * Отримання шляху до файлу в темі
     * 
     * @param string $file Шлях до файлу відносно папки теми
     * @return string
     */
    protected function getThemeFilePath(string $file): string {
        return $this->getThemePath() . $file;
    }
    
    /**
     * Перевірка підтримки можливості теми
     * 
     * @param string $feature Назва можливості (наприклад, 'customization', 'navigation')
     * @return bool
     */
    protected function themeSupports(string $feature): bool {
        if ($this->themeData === null) {
            return false;
        }
        
        $key = 'supports_' . strtolower($feature);
        return isset($this->themeData[$key]) && (bool)$this->themeData[$key];
    }
    
    /**
     * Отримання назви теми
     * 
     * @return string
     */
    protected function getThemeName(): string {
        return $this->themeData['name'] ?? '';
    }
    
    /**
     * Отримання опису теми
     * 
     * @return string
     */
    protected function getThemeDescription(): string {
        return $this->themeData['description'] ?? '';
    }
    
    /**
     * Отримання версії теми
     * 
     * @return string
     */
    protected function getThemeVersion(): string {
        return $this->themeData['version'] ?? '1.0.0';
    }
    
    /**
     * Отримання автора теми
     * 
     * @return string
     */
    protected function getThemeAuthor(): string {
        return $this->themeData['author'] ?? '';
    }
    
    /**
     * Додавання хука до теми
     * 
     * @param string $hook Назва хука
     * @param callable $callback Функція зворотного виклику
     * @param int $priority Пріоритет (менше = вище)
     * @return void
     */
    protected function addThemeHook(string $hook, callable $callback, int $priority = 10): void {
        addHook($hook, $callback, $priority);
    }
    
    /**
     * Виклик хука теми
     * 
     * @param string $hook Назва хука
     * @param mixed ...$args Аргументи для хука
     * @return mixed
     */
    protected function doThemeHook(string $hook, mixed ...$args): mixed {
        return doHook($hook, ...$args);
    }
    
    /**
     * Отримання URL сайту
     * 
     * @return string
     */
    protected function getSiteUrl(): string {
        return defined('SITE_URL') ? SITE_URL : '';
    }
    
    /**
     * Отримання назви сайту
     * 
     * @return string
     */
    protected function getSiteName(): string {
        return getSetting('site_name', 'Flowaxy CMS');
    }
    
    /**
     * Отримання опису сайту
     * 
     * @return string
     */
    protected function getSiteDescription(): string {
        return getSetting('site_description', '');
    }
    
    /**
     * Отримання тегов сайту
     * 
     * @return string
     */
    protected function getSiteTagline(): string {
        return getSetting('site_tagline', '');
    }
    
    /**
     * Безпечний вивід HTML
     * 
     * @param string $text Текст для виводу
     * @return string
     */
    protected function escapeHtml(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Отримання списку підключених стилів
     * 
     * @return array
     */
    protected function getEnqueuedStyles(): array {
        return $this->enqueuedStyles;
    }
    
    /**
     * Отримання списку підключених скриптів
     * 
     * @return array
     */
    protected function getEnqueuedScripts(): array {
        return $this->enqueuedScripts;
    }
}

