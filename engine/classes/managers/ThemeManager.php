<?php
/**
 * Модуль управления темами
 * Управление темами и их настройками
 * 
 * @package Engine\Modules
 * @version 2.0.0
 */

declare(strict_types=1);

class ThemeManager extends BaseModule {
    private ?array $activeTheme = null;
    private array $themeSettings = [];
    
    /**
     * Инициализация модуля
     */
    protected function init(): void {
        $this->loadActiveTheme();
    }
    
    /**
     * Регистрация хуков модуля
     */
    public function registerHooks(): void {
        // Модуль ThemeManager не регистрирует хуки
    }
    
    /**
     * Получение информации о модуле
     */
    public function getInfo(): array {
        return [
            'name' => 'ThemeManager',
            'title' => 'Менеджер тем',
            'description' => 'Управление темами и их настройками',
            'version' => '2.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Получение API методов модуля
     */
    public function getApiMethods(): array {
        return [
            'getActiveTheme' => 'Получение активной темы',
            'getAllThemes' => 'Получение всех тем',
            'getTheme' => 'Получение темы по slug',
            'activateTheme' => 'Активация темы',
            'getSetting' => 'Получение настройки темы',
            'setSetting' => 'Сохранение настройки темы',
            'supportsCustomization' => 'Проверка поддержки кастомизации',
            'hasScssSupport' => 'Проверка поддержки SCSS',
            'compileScss' => 'Компиляция SCSS'
        ];
    }
    
    
    /**
     * Загрузка активной темы с кешированием
     * Получает активную тему из site_settings, затем загружает данные из файловой системы
     * 
     * @return void
     */
    private function loadActiveTheme(): void {
        $db = $this->getDB();
        if ($db === null) {
            return;
        }
        
        // Используем кеширование для активной темы
        $cacheKey = 'active_theme_slug';
        $activeSlug = cache_remember($cacheKey, function() use ($db) {
            if ($db === null) {
                return null;
            }
            
            try {
                $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'active_theme' LIMIT 1");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $slug = $result ? ($result['setting_value'] ?? null) : null;
                
                // Валидация slug
                if ($slug && !Validator::validateSlug($slug)) {
                    error_log("ThemeManager: Invalid active theme slug from database: {$slug}");
                    return null;
                }
                
                return $slug;
            } catch (PDOException $e) {
                error_log("ThemeManager loadActiveTheme error: " . $e->getMessage());
                return null;
            }
        }, 60); // Кешируем на 1 минуту (меньше время для быстрого обновления)
        
        if ($activeSlug) {
            // Загружаем тему из файловой системы
            $theme = $this->getTheme($activeSlug);
            if ($theme !== null && is_array($theme)) {
                $this->activeTheme = $theme;
                $this->loadThemeSettings($theme['slug']);
            }
        }
    }
    
    /**
     * Загрузка настроек темы с кешированием
     * 
     * @param string $themeSlug Slug темы
     * @return void
     */
    private function loadThemeSettings(string $themeSlug): void {
        $db = $this->getDB();
        if ($db === null || empty($themeSlug)) {
            return;
        }
        
        // Валидация slug
        if (!Validator::validateSlug($themeSlug)) {
            error_log("ThemeManager: Invalid theme slug: {$themeSlug}");
            return;
        }
        
        // Используем кеширование для настроек темы
        $cacheKey = 'theme_settings_' . $themeSlug;
        $this->themeSettings = cache_remember($cacheKey, function() use ($themeSlug, $db): array {
            if ($db === null) {
                return [];
            }
            
            try {
                $stmt = $db->prepare("SELECT setting_key, setting_value FROM theme_settings WHERE theme_slug = ?");
                $stmt->execute([$themeSlug]);
                
                $settings = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                
                return $settings;
            } catch (PDOException $e) {
                error_log("ThemeManager loadThemeSettings error: " . $e->getMessage());
                return [];
            }
        }, 3600); // Кешируем на 1 час
    }
    
    /**
     * Получение активной темы
     * 
     * @return array|null
     */
    public function getActiveTheme(): ?array {
        return $this->activeTheme;
    }
    
    /**
     * Получение всех тем (из файловой системы)
     * Автоматически обнаруживает темы по наличию theme.json
     * 
     * @return array
     */
    public function getAllThemes(): array {
        // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
        $baseDir = dirname(__DIR__, 1); // engine/classes
        $engineDir = dirname($baseDir); // engine
        $rootDir = dirname($engineDir); // корень проекта
        $themesDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        
        // Нормализуем путь
        $themesDir = realpath($themesDir) ? realpath($themesDir) . DIRECTORY_SEPARATOR : $themesDir;
        
        if (!is_dir($themesDir)) {
            error_log("ThemeManager: Themes directory not found: {$themesDir}");
            return [];
        }
        
        return cache_remember('all_themes_filesystem', function() use ($themesDir): array {
            $themes = [];
            $directories = glob($themesDir . '*', GLOB_ONLYDIR);
            
            foreach ($directories as $dir) {
                $themeSlug = basename($dir);
                $configFile = $dir . '/theme.json';
                
                if (file_exists($configFile) && is_readable($configFile)) {
                    $configContent = @file_get_contents($configFile);
                    if ($configContent === false) {
                        error_log("ThemeManager: Cannot read theme.json for theme: {$themeSlug}");
                        continue;
                    }
                    
                    $config = json_decode($configContent, true);
                    if ($config && is_array($config)) {
                        if (empty($config['slug'])) {
                            $config['slug'] = $themeSlug;
                        }
                        
                        $isActive = $this->isThemeActive($config['slug'] ?? $themeSlug);
                        
                        $theme = [
                            'slug' => $config['slug'],
                            'name' => $config['name'] ?? $themeSlug,
                            'description' => $config['description'] ?? '',
                            'version' => $config['version'] ?? '1.0.0',
                            'author' => $config['author'] ?? '',
                            'is_active' => $isActive ? 1 : 0,
                            'screenshot' => $this->getThemeScreenshot($themeSlug),
                            'supports_customization' => $config['supports_customization'] ?? false
                        ];
                        
                        $themes[$themeSlug] = $theme;
                    } else {
                        error_log("ThemeManager: Invalid JSON in theme.json for theme: {$themeSlug}");
                    }
                }
            }
            
            usort($themes, function($a, $b) {
                if ($a['is_active'] != $b['is_active']) {
                    return $b['is_active'] - $a['is_active'];
                }
                return strcmp($a['name'], $b['name']);
            });
            
            return array_values($themes);
        }, 300);
    }
    
    /**
     * Проверка активности темы из site_settings
     */
    private function isThemeActive(string $themeSlug): bool {
        $db = $this->getDB();
        if ($db === null || empty($themeSlug)) {
            return false;
        }
        
        try {
            $cacheKey = 'active_theme_check_' . md5($themeSlug);
            
            return cache_remember($cacheKey, function() use ($themeSlug, $db): bool {
                if ($db === null) {
                    return false;
                }
                
                try {
                    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'active_theme' LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $activeTheme = $result ? ($result['setting_value'] ?? '') : '';
                    return !empty($activeTheme) && $activeTheme === $themeSlug;
                } catch (PDOException $e) {
                    error_log("ThemeManager isThemeActive error: " . $e->getMessage());
                    return false;
                }
            }, 60);
        } catch (Exception $e) {
            error_log("ThemeManager isThemeActive error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение пути к скриншоту темы
     */
    private function getThemeScreenshot(string $themeSlug): ?string {
        // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
        $baseDir = dirname(__DIR__, 1); // engine/classes
        $engineDir = dirname($baseDir); // engine
        $rootDir = dirname($engineDir); // корень проекта
        $themesDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $themesDir = realpath($themesDir) ? realpath($themesDir) . DIRECTORY_SEPARATOR : $themesDir;
        $screenshotPath = $themesDir . $themeSlug . '/screenshot.png';
        
        if (file_exists($screenshotPath)) {
            // Используем UrlHelper для получения актуального URL с правильным протоколом
            if (class_exists('UrlHelper')) {
                return UrlHelper::site('/themes/' . $themeSlug . '/screenshot.png');
            }
            // Fallback на константу, если UrlHelper не доступен
            $siteUrl = defined('SITE_URL') ? SITE_URL : '';
            return $siteUrl . '/themes/' . $themeSlug . '/screenshot.png';
        }
        
        return null;
    }
    
    /**
     * Получение темы по slug
     */
    public function getTheme(string $slug): ?array {
        $allThemes = $this->getAllThemes();
        foreach ($allThemes as $theme) {
            if ($theme['slug'] === $slug) {
                return $theme;
            }
        }
        return null;
    }
    
    /**
     * Активация темы
     */
    public function activateTheme(string $slug): bool {
        $db = $this->getDB();
        if ($db === null || empty($slug)) {
            return false;
        }
        
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for activation: {$slug}");
            return false;
        }
        
        // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
        $baseDir = dirname(__DIR__, 1); // engine/classes
        $engineDir = dirname($baseDir); // engine
        $rootDir = dirname($engineDir); // корень проекта
        $themesDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $themesDir = realpath($themesDir) ? realpath($themesDir) . DIRECTORY_SEPARATOR : $themesDir;
        $themeFolderSlug = null;
        
        $directories = glob($themesDir . '*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $folderName = basename($dir);
            $configFile = $dir . '/theme.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                if ($config && isset($config['slug']) && $config['slug'] === $slug) {
                    $themeFolderSlug = $folderName;
                    break;
                }
            }
        }
        
        if ($themeFolderSlug === null) {
            $themePath = $themesDir . $slug . '/';
            $themeJsonFile = $themePath . 'theme.json';
            if (is_dir($themePath) && file_exists($themeJsonFile)) {
                $themeFolderSlug = $slug;
            }
        }
        
        if ($themeFolderSlug === null) {
            error_log("ThemeManager: Theme not found: {$slug}");
            return false;
        }
        
        $themePath = $themesDir . $themeFolderSlug . '/';
        $themeJsonFile = $themePath . 'theme.json';
        
        if (!is_dir($themePath) || !file_exists($themeJsonFile)) {
            error_log("ThemeManager: Theme folder not found: {$themeFolderSlug}");
            return false;
        }
        
        try {
            $previousThemeSlug = null;
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'active_theme' LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $previousThemeSlug = $result ? ($result['setting_value'] ?? null) : null;
                } catch (PDOException $e) {
                    error_log("ThemeManager: Error getting previous theme: " . $e->getMessage());
                }
            }
            
            if ($previousThemeSlug && $previousThemeSlug !== $slug) {
                if ($this->hasScssSupport($previousThemeSlug)) {
                    $previousThemePath = $this->getThemePath($previousThemeSlug);
                    if (!empty($previousThemePath)) {
                        $previousCssFile = $previousThemePath . 'assets/css/style.css';
                        if (file_exists($previousCssFile) && is_file($previousCssFile)) {
                            @unlink($previousCssFile);
                        }
                    }
                }
            }
            
            // Сохраняем активную тему в site_settings
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES ('active_theme', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $result = $stmt->execute([$slug]);
            
            if (!$result) {
                error_log("ThemeManager: Failed to save active theme to database");
                return false;
            }
            
            // Очищаем все кеши связанные с темами
            $this->clearThemeCache($slug);
            
            // Очищаем кеш активной темы
            cache_forget('active_theme_slug');
            cache_forget('active_theme');
            
            // Перезагружаем активную тему
            $this->loadActiveTheme();
            
            $themeConfig = $this->getThemeConfig($slug);
            if (!empty($themeConfig)) {
                $this->initializeDefaultSettings($slug);
            }
            
            if ($this->hasScssSupport($slug)) {
                $compileResult = $this->compileScss($slug, true);
                if (!$compileResult) {
                    error_log("ThemeManager: SCSS compilation failed for theme: {$slug}");
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("ThemeManager activateTheme error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Инициализация настроек по умолчанию для темы
     */
    private function initializeDefaultSettings(string $themeSlug): bool {
        $db = $this->getDB();
        if ($db === null || empty($themeSlug)) {
            return false;
        }
        
        if (!Validator::validateSlug($themeSlug)) {
            error_log("ThemeManager: Invalid theme slug for default settings: {$themeSlug}");
            return false;
        }
        
        try {
            $themeConfig = $this->getThemeConfig($themeSlug);
            $defaultSettings = $themeConfig['default_settings'] ?? [];
            
            if (empty($defaultSettings) || !is_array($defaultSettings)) {
                return true;
            }
            
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM theme_settings WHERE theme_slug = ?");
            $stmt->execute([$themeSlug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (isset($result['count']) && (int)$result['count'] === 0) {
                Database::getInstance()->transaction(function(PDO $db) use ($themeSlug, $defaultSettings): void {
                    foreach ($defaultSettings as $key => $value) {
                        if (empty($key) || !Validator::validateString($key, 1, 255)) {
                            error_log("ThemeManager: Invalid default setting key: {$key}");
                            continue;
                        }
                        
                        $valueStr = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                        
                        $stmt = $db->prepare("
                            INSERT INTO theme_settings (theme_slug, setting_key, setting_value) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$themeSlug, $key, $valueStr]);
                    }
                });
                
                $this->loadThemeSettings($themeSlug);
                cache_forget('theme_settings_' . $themeSlug);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("ThemeManager initializeDefaultSettings error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение настройки темы
     */
    public function getSetting(string $key, $default = null) {
        if (empty($key)) {
            return $default;
        }
        return $this->themeSettings[$key] ?? $default;
    }
    
    /**
     * Получение всех настроек темы
     */
    public function getSettings(): array {
        return $this->themeSettings;
    }
    
    /**
     * Сохранение настройки темы
     */
    public function setSetting(string $key, $value): bool {
        $db = $this->getDB();
        if ($db === null || $this->activeTheme === null || empty($key)) {
            return false;
        }
        
        if (!Validator::validateString($key, 1, 255)) {
            error_log("ThemeManager: Invalid setting key: {$key}");
            return false;
        }
        
        $themeSlug = $this->activeTheme['slug'];
        $valueStr = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE);
        
        try {
            $stmt = $db->prepare("
                INSERT INTO theme_settings (theme_slug, setting_key, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            $result = $stmt->execute([$themeSlug, $key, $valueStr]);
            
            if ($result) {
                $this->themeSettings[$key] = $valueStr;
                cache_forget('theme_settings_' . $themeSlug);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("ThemeManager setSetting error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Сохранение нескольких настроек темы
     */
    public function setSettings(array $settings): bool {
        $db = $this->getDB();
        if ($db === null || $this->activeTheme === null) {
            error_log("ThemeManager setSettings: DB or active theme not available");
            return false;
        }
        
        if (empty($settings)) {
            error_log("ThemeManager setSettings: Empty settings array");
            return false;
        }
        
        $themeSlug = $this->activeTheme['slug'];
        
        try {
            Database::getInstance()->transaction(function(PDO $db) use ($settings, $themeSlug): void {
                foreach ($settings as $key => $value) {
                    if (empty($key) || !Validator::validateString($key, 1, 255)) {
                        error_log("ThemeManager: Invalid setting key: {$key}");
                        continue;
                    }
                    
                    $valueStr = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    
                    $stmt = $db->prepare("
                        INSERT INTO theme_settings (theme_slug, setting_key, setting_value) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    
                    $stmt->execute([$themeSlug, $key, $valueStr]);
                }
            });
            
            $this->loadThemeSettings($themeSlug);
            cache_forget('theme_settings_' . $themeSlug);
            
            return true;
        } catch (Exception $e) {
            error_log("ThemeManager setSettings error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение пути к теме
     */
    public function getThemePath(?string $themeSlug = null): string {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        
        // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
        $baseDir = dirname(__DIR__, 1); // engine/classes
        $engineDir = dirname($baseDir); // engine
        $rootDir = dirname($engineDir); // корень проекта
        $themesBaseDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $themesBaseDir = realpath($themesBaseDir) ? realpath($themesBaseDir) . DIRECTORY_SEPARATOR : $themesBaseDir;
        $themesBaseDir = realpath($themesBaseDir) ? realpath($themesBaseDir) . DIRECTORY_SEPARATOR : $themesBaseDir;
        
        if ($theme === null || !isset($theme['slug'])) {
            return $themesBaseDir . 'default/';
        }
        
        $slug = $theme['slug'];
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for path: {$slug}");
            return $themesBaseDir . 'default/';
        }
        
        $path = $themesBaseDir . $slug . '/';
        return file_exists($path) ? $path : $themesBaseDir . 'default/';
    }
    
    /**
     * Получение URL темы
     */
    public function getThemeUrl(?string $themeSlug = null): string {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        
        // Используем UrlHelper для получения актуального протокола из настроек
        if (class_exists('UrlHelper')) {
            $protocol = UrlHelper::getProtocol();
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . $host;
        } elseif (function_exists('detectProtocol')) {
            $protocol = detectProtocol();
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . $host;
        } else {
            // Fallback на автоматическое определение
            $protocol = 'http://';
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)) {
                $protocol = 'https://';
            }
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . $host;
        }
        
        if ($theme === null || !isset($theme['slug'])) {
            return $baseUrl . '/themes/default/';
        }
        
        $slug = $theme['slug'];
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for URL: {$slug}");
            return $baseUrl . '/themes/default/';
        }
        
        return $baseUrl . '/themes/' . $slug . '/';
    }
    
    /**
     * Проверка существования темы
     */
    public function themeExists(string $slug): bool {
        if (empty($slug)) {
            return false;
        }
        $theme = $this->getTheme($slug);
        return $theme !== null;
    }
    
    /**
     * Валидация структуры темы
     */
    public function validateThemeStructure(string $slug): array {
        $errors = [];
        $warnings = [];
        
        if (!Validator::validateSlug($slug)) {
            $errors[] = "Невірний slug теми: {$slug}";
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
        
        // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
        $baseDir = dirname(__DIR__, 1); // engine/classes
        $engineDir = dirname($baseDir); // engine
        $rootDir = dirname($engineDir); // корень проекта
        $themesBaseDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $themesBaseDir = realpath($themesBaseDir) ? realpath($themesBaseDir) . DIRECTORY_SEPARATOR : $themesBaseDir;
        $themePath = $themesBaseDir . $slug . '/';
        
        if (!is_dir($themePath)) {
            $errors[] = "Директорія теми не знайдена: {$themePath}";
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
        
        $requiredFiles = [
            'index.php' => 'Головний шаблон теми',
            'theme.json' => 'Конфігурація теми'
        ];
        
        foreach ($requiredFiles as $file => $description) {
            if (!file_exists($themePath . $file)) {
                $errors[] = "Відсутній обов'язковий файл: {$file} ({$description})";
            }
        }
        
        $jsonFile = $themePath . 'theme.json';
        if (file_exists($jsonFile)) {
            try {
                $jsonContent = @file_get_contents($jsonFile);
                if ($jsonContent === false) {
                    $errors[] = "Неможливо прочитати theme.json";
                } else {
                    $config = json_decode($jsonContent, true);
                    if (!is_array($config)) {
                        $errors[] = "theme.json повинен містити валідний JSON";
                    } else {
                        $requiredConfigKeys = ['name', 'version', 'slug'];
                        foreach ($requiredConfigKeys as $key) {
                            if (!isset($config[$key])) {
                                $errors[] = "Відсутнє обов'язкове поле в theme.json: {$key}";
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Помилка завантаження theme.json: " . $e->getMessage();
            }
        }
        
        $recommendedFiles = [
            'style.css' => 'Стилі теми',
            'script.js' => 'JavaScript теми',
            'screenshot.png' => 'Скріншот теми',
            'customizer.php' => 'Конфігурація кастомізатора'
        ];
        
        foreach ($recommendedFiles as $file => $description) {
            if (!file_exists($themePath . $file)) {
                $warnings[] = "Рекомендується додати файл: {$file} ({$description})";
            }
        }
        
        $customizerFile = $themePath . 'customizer.php';
        if (file_exists($customizerFile)) {
            try {
                $customizerConfig = require $customizerFile;
                if (!is_array($customizerConfig)) {
                    $warnings[] = "customizer.php повинен повертати масив";
                }
            } catch (Exception $e) {
                $warnings[] = "Помилка завантаження customizer.php: " . $e->getMessage();
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Установка темы
     */
    public function installTheme(string $slug, string $name, string $description = '', string $version = '1.0.0', string $author = ''): bool {
        $db = $this->getDB();
        if ($db === null || empty($slug) || empty($name)) {
            return false;
        }
        
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for installation: {$slug}");
            return false;
        }
        
        if (!Validator::validateString($name, 1, 255)) {
            error_log("ThemeManager: Invalid theme name: {$name}");
            return false;
        }
        
        $validation = $this->validateThemeStructure($slug);
        if (!$validation['valid']) {
            error_log("ThemeManager: Theme structure validation failed for {$slug}: " . implode(', ', $validation['errors']));
            return false;
        }
        
        if (!empty($validation['warnings'])) {
            error_log("ThemeManager: Theme structure warnings for {$slug}: " . implode(', ', $validation['warnings']));
        }
        
        $themeConfig = $this->getThemeConfig($slug);
        if (!empty($themeConfig)) {
            $name = $themeConfig['name'] ?? $name;
            $description = $themeConfig['description'] ?? $description;
            $version = $themeConfig['version'] ?? $version;
            $author = $themeConfig['author'] ?? $author;
        }
        
        try {
            $activeTheme = getSetting('active_theme');
            if (empty($activeTheme)) {
                $this->activateTheme($slug);
            }
            
            cache_forget('all_themes_filesystem');
            cache_forget('theme_' . $slug);
            
            return true;
        } catch (Exception $e) {
            error_log("ThemeManager installTheme error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Загрузка конфигурации темы из theme.json
     */
    public function getThemeConfig(?string $themeSlug = null): array {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        
        if ($theme === null) {
            return [
                'name' => 'Default',
                'version' => '1.0.0',
                'description' => '',
                'default_settings' => [],
                'available_settings' => []
            ];
        }
        
        $slug = $theme['slug'] ?? 'default';
        
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for config: {$slug}");
            return [
                'name' => $theme['name'] ?? 'Default',
                'version' => $theme['version'] ?? '1.0.0',
                'description' => $theme['description'] ?? '',
                'default_settings' => [],
                'available_settings' => []
            ];
        }
        
        $cacheKey = 'theme_config_' . $slug;
        return cache_remember($cacheKey, function() use ($slug, $theme) {
            // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
            $baseDir = dirname(__DIR__, 1); // engine/classes
            $engineDir = dirname($baseDir); // engine
            $rootDir = dirname($engineDir); // корень проекта
            $themesBaseDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
            $themesBaseDir = realpath($themesBaseDir) ? realpath($themesBaseDir) . DIRECTORY_SEPARATOR : $themesBaseDir;
            $jsonFile = $themesBaseDir . $slug . '/theme.json';
            
            if (file_exists($jsonFile) && is_readable($jsonFile)) {
                try {
                    $jsonContent = @file_get_contents($jsonFile);
                    if ($jsonContent !== false) {
                        $config = json_decode($jsonContent, true);
                        if (is_array($config)) {
                            return $config;
                        }
                    }
                } catch (Exception $e) {
                    error_log("ThemeManager: Error loading theme.json for {$slug}: " . $e->getMessage());
                }
            }
            
            return [
                'name' => $theme['name'] ?? 'Default',
                'version' => $theme['version'] ?? '1.0.0',
                'description' => $theme['description'] ?? '',
                'default_settings' => [],
                'available_settings' => []
            ];
        }, 3600);
    }
    
    /**
     * Проверка поддержки кастоматизации темой
     */
    public function supportsCustomization(?string $themeSlug = null): bool {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        if (!$theme) {
            return false;
        }
        
        $themeConfig = $this->getThemeConfig($theme['slug']);
        if (isset($themeConfig['supports_customization'])) {
            return (bool)$themeConfig['supports_customization'];
        }
        
        $themePath = $this->getThemePath($theme['slug']);
        return file_exists($themePath . 'customizer.php');
    }
    
    /**
     * Проверка поддержки навигации темой
     */
    public function supportsNavigation(?string $themeSlug = null): bool {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        if (!$theme) {
            return false;
        }
        
        $themeConfig = $this->getThemeConfig($theme['slug']);
        return (bool)($themeConfig['supports_navigation'] ?? false);
    }
    
    /**
     * Проверка поддержки SCSS темой
     */
    public function hasScssSupport(?string $themeSlug = null): bool {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        if (!$theme) {
            return false;
        }
        
        $themePath = $this->getThemePath($theme['slug']);
        $scssFile = $themePath . 'assets/scss/main.scss';
        
        return file_exists($scssFile) && is_readable($scssFile);
    }
    
    /**
     * Компиляция SCSS в CSS для темы
     */
    public function compileScss(?string $themeSlug = null, bool $force = false): bool {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        if (!$theme) {
            return false;
        }
        
        $themePath = $this->getThemePath($theme['slug']);
        
        $compiler = new ScssCompiler($themePath, 'assets/scss/main.scss', 'assets/css/style.css');
        
        if (!$compiler->hasScssFiles()) {
            return false;
        }
        
        return $compiler->compile($force);
    }
    
    /**
     * Получение URL файла стилей темы
     */
    public function getStylesheetUrl(?string $themeSlug = null, string $cssFile = 'style.css'): string {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        if (!$theme) {
            return $this->getThemeUrl() . $cssFile;
        }
        
        $themePath = $this->getThemePath($theme['slug']);
        
        if ($this->hasScssSupport($theme['slug'])) {
            try {
                $this->compileScss($theme['slug']);
            } catch (Exception $e) {
                error_log("ThemeManager: SCSS compilation failed: " . $e->getMessage());
            }
            
            $compiledCssFile = $themePath . 'assets/css/style.css';
            
            if (file_exists($compiledCssFile) && is_readable($compiledCssFile)) {
                return $this->getThemeUrl($theme['slug']) . 'assets/css/style.css';
            }
        }
        
        $regularCssFile = $themePath . $cssFile;
        
        if (file_exists($regularCssFile) && is_readable($regularCssFile)) {
            return $this->getThemeUrl($theme['slug']) . $cssFile;
        }
        
        return $this->getThemeUrl($theme['slug']) . $cssFile;
    }
    
    /**
     * Очистка кеша темы
     */
    public function clearThemeCache(?string $themeSlug = null): void {
        // Всегда очищаем кеш активной темы
        cache_forget('active_theme');
        cache_forget('active_theme_slug');
        cache_forget('all_themes_filesystem');
        
        if ($themeSlug) {
            cache_forget('theme_settings_' . $themeSlug);
            cache_forget('theme_config_' . $themeSlug);
            cache_forget('theme_' . $themeSlug);
            cache_forget('active_theme_check_' . md5($themeSlug));
        } else {
            // Очищаем кеш для всех тем
            // Путь к темам: engine/classes/managers -> engine/classes -> engine -> корень -> themes
            $baseDir = dirname(__DIR__, 1); // engine/classes
            $engineDir = dirname($baseDir); // engine
            $rootDir = dirname($engineDir); // корень проекта
            $themesDir = $rootDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
            $themesDir = realpath($themesDir) ? realpath($themesDir) . DIRECTORY_SEPARATOR : $themesDir;
            if (is_dir($themesDir)) {
                $directories = glob($themesDir . '*', GLOB_ONLYDIR);
                if ($directories !== false) {
                    foreach ($directories as $dir) {
                        $slug = basename($dir);
                        cache_forget('active_theme_check_' . md5($slug));
                        cache_forget('theme_config_' . $slug);
                        cache_forget('theme_settings_' . $slug);
                    }
                }
            }
        }
    }
}

/**
 * Глобальная функция для получения менеджера тем
 * 
 * @return ThemeManager
 */
function themeManager(): ThemeManager {
    return ThemeManager::getInstance();
}

