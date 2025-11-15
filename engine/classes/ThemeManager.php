<?php
/**
 * Менеджер тем
 * Управление темами и их настройками
 * 
 * @package Core
 * @version 2.0.0
 */

declare(strict_types=1);

class ThemeManager {
    private ?PDO $db = null;
    private ?array $activeTheme = null;
    private array $themeSettings = [];
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = getDB();
        $this->loadActiveTheme();
    }
    
    /**
     * Загрузка активной темы с кешированием
     * 
     * @return void
     */
    private function loadActiveTheme(): void {
        if ($this->db === null) {
            return;
        }
        
        // Используем кеширование для активной темы
        $cacheKey = 'active_theme';
        $db = $this->db;
        $theme = cache_remember($cacheKey, function() use ($db) {
            if ($db === null) {
                return null;
            }
            
            try {
                $stmt = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
                if ($stmt === false) {
                    return null;
                }
                
                $theme = $stmt->fetch(PDO::FETCH_ASSOC);
                return $theme ?: null;
            } catch (PDOException $e) {
                error_log("ThemeManager loadActiveTheme error: " . $e->getMessage());
                return null;
            }
        }, 3600); // Кешируем на 1 час
        
        if ($theme !== null && is_array($theme)) {
            $this->activeTheme = $theme;
            $this->loadThemeSettings($theme['slug']);
        }
    }
    
    /**
     * Загрузка настроек темы с кешированием
     * 
     * @param string $themeSlug Slug темы
     * @return void
     */
    private function loadThemeSettings(string $themeSlug): void {
        if ($this->db === null || empty($themeSlug)) {
            return;
        }
        
        // Валидация slug
        if (!Validator::validateSlug($themeSlug)) {
            error_log("ThemeManager: Invalid theme slug: {$themeSlug}");
            return;
        }
        
        // Используем кеширование для настроек темы
        $cacheKey = 'theme_settings_' . $themeSlug;
        $db = $this->db;
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
     * Получение всех тем с кешированием
     * 
     * @return array
     */
    public function getAllThemes(): array {
        if ($this->db === null) {
            return [];
        }
        
        $db = $this->db;
        return cache_remember('all_themes', function() use ($db): array {
            if ($db === null) {
                return [];
            }
            
            try {
                $stmt = $db->query("SELECT * FROM themes ORDER BY is_active DESC, name ASC");
                if ($stmt === false) {
                    return [];
                }
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("ThemeManager getAllThemes error: " . $e->getMessage());
                return [];
            }
        }, 3600); // Кешируем на 1 час
    }
    
    /**
     * Получение темы по slug с кешированием
     * 
     * @param string $slug Slug темы
     * @return array|null
     */
    public function getTheme(string $slug): ?array {
        if ($this->db === null || empty($slug)) {
            return null;
        }
        
        // Валидация slug
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug: {$slug}");
            return null;
        }
        
        $cacheKey = 'theme_' . $slug;
        $db = $this->db;
        return cache_remember($cacheKey, function() use ($slug, $db): ?array {
            if ($db === null) {
                return null;
            }
            
            try {
                $stmt = $db->prepare("SELECT * FROM themes WHERE slug = ? LIMIT 1");
                $stmt->execute([$slug]);
                
                $theme = $stmt->fetch(PDO::FETCH_ASSOC);
                return $theme ?: null;
            } catch (PDOException $e) {
                error_log("ThemeManager getTheme error: " . $e->getMessage());
                return null;
            }
        }, 3600); // Кешируем на 1 час
    }
    
    /**
     * Активация темы
     * 
     * @param string $slug Slug темы
     * @return bool
     */
    public function activateTheme(string $slug): bool {
        if ($this->db === null || empty($slug)) {
            return false;
        }
        
        // Валидация slug
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for activation: {$slug}");
            return false;
        }
        
        // Проверяем существование темы
        $theme = $this->getTheme($slug);
        if ($theme === null) {
            error_log("ThemeManager: Theme not found: {$slug}");
            return false;
        }
        
        try {
            Database::getInstance()->transaction(function(PDO $db) use ($slug): void {
                // Деактивируем все темы
                $stmt = $db->prepare("UPDATE themes SET is_active = 0");
                $stmt->execute();
                
                // Активируем выбранную тему
                $stmt = $db->prepare("UPDATE themes SET is_active = 1 WHERE slug = ?");
                $stmt->execute([$slug]);
            });
            
            // Перезагружаем активную тему
            $this->loadActiveTheme();
            
            // Инициализируем настройки по умолчанию, если их еще нет
            $this->initializeDefaultSettings($slug);
            
            // Очищаем кеш
            cache_forget('active_theme');
            cache_forget('theme_settings_' . $slug);
            cache_forget('all_themes');
            cache_forget('theme_' . $slug);
            
            return true;
        } catch (Exception $e) {
            error_log("ThemeManager activateTheme error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Инициализация настроек по умолчанию для темы
     * 
     * @param string $themeSlug Slug темы
     * @return bool
     */
    private function initializeDefaultSettings(string $themeSlug): bool {
        if ($this->db === null || empty($themeSlug)) {
            return false;
        }
        
        // Валидация slug
        if (!Validator::validateSlug($themeSlug)) {
            error_log("ThemeManager: Invalid theme slug for default settings: {$themeSlug}");
            return false;
        }
        
        try {
            // Загружаем конфигурацию темы
            $themeConfig = $this->getThemeConfig($themeSlug);
            $defaultSettings = $themeConfig['default_settings'] ?? [];
            
            if (empty($defaultSettings) || !is_array($defaultSettings)) {
                return true;
            }
            
            // Проверяем, есть ли уже настройки для этой темы
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM theme_settings WHERE theme_slug = ?");
            $stmt->execute([$themeSlug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Если настроек нет, инициализируем значения по умолчанию
            if (isset($result['count']) && (int)$result['count'] === 0) {
                Database::getInstance()->transaction(function(PDO $db) use ($themeSlug, $defaultSettings): void {
                    foreach ($defaultSettings as $key => $value) {
                        // Валидация ключа
                        if (empty($key) || !Validator::validateString($key, 1, 255)) {
                            error_log("ThemeManager: Invalid default setting key: {$key}");
                            continue;
                        }
                        
                        // Преобразуем значение в строку
                        $valueStr = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                        
                        $stmt = $db->prepare("
                            INSERT INTO theme_settings (theme_slug, setting_key, setting_value) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$themeSlug, $key, $valueStr]);
                    }
                });
                
                // Перезагружаем настройки
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
     * 
     * @param string $key Ключ настройки
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getSetting(string $key, $default = null) {
        if (empty($key)) {
            return $default;
        }
        
        return $this->themeSettings[$key] ?? $default;
    }
    
    /**
     * Получение всех настроек темы
     * 
     * @return array
     */
    public function getSettings(): array {
        return $this->themeSettings;
    }
    
    /**
     * Сохранение настройки темы
     * 
     * @param string $key Ключ настройки
     * @param mixed $value Значение настройки
     * @return bool
     */
    public function setSetting(string $key, $value): bool {
        if ($this->db === null || $this->activeTheme === null || empty($key)) {
            return false;
        }
        
        // Валидация ключа
        if (!Validator::validateString($key, 1, 255)) {
            error_log("ThemeManager: Invalid setting key: {$key}");
            return false;
        }
        
        $themeSlug = $this->activeTheme['slug'];
        $valueStr = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE);
        
        try {
            $stmt = $this->db->prepare("
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
     * 
     * @param array $settings Массив настроек
     * @return bool
     */
    public function setSettings(array $settings): bool {
        if ($this->db === null || $this->activeTheme === null) {
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
                    // Валидация ключа
                    if (empty($key) || !Validator::validateString($key, 1, 255)) {
                        error_log("ThemeManager: Invalid setting key: {$key}");
                        continue;
                    }
                    
                    // Преобразуем значение в строку для сохранения в БД
                    $valueStr = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    
                    $stmt = $db->prepare("
                        INSERT INTO theme_settings (theme_slug, setting_key, setting_value) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    
                    $stmt->execute([$themeSlug, $key, $valueStr]);
                }
            });
            
            // Перезагружаем настройки
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
     * 
     * @param string|null $themeSlug Slug темы
     * @return string
     */
    public function getThemePath(?string $themeSlug = null): string {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        
        if ($theme === null || !isset($theme['slug'])) {
            return dirname(__DIR__, 2) . '/themes/default/';
        }
        
        $slug = $theme['slug'];
        // Безопасная проверка пути
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for path: {$slug}");
            return dirname(__DIR__, 2) . '/themes/default/';
        }
        
        $path = dirname(__DIR__, 2) . '/themes/' . $slug . '/';
        return file_exists($path) ? $path : dirname(__DIR__, 2) . '/themes/default/';
    }
    
    /**
     * Получение URL темы
     * 
     * @param string|null $themeSlug Slug темы
     * @return string
     */
    public function getThemeUrl(?string $themeSlug = null): string {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        
        // Определяем протокол из текущего запроса
        $protocol = 'http://';
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)) {
            $protocol = 'https://';
        }
        
        // Получаем хост из SITE_URL или из текущего запроса
        $host = parse_url(SITE_URL, PHP_URL_HOST);
        if (empty($host) && isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }
        
        $baseUrl = $protocol . $host;
        
        if ($theme === null || !isset($theme['slug'])) {
            return $baseUrl . '/themes/default/';
        }
        
        $slug = $theme['slug'];
        // Безопасная проверка slug
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for URL: {$slug}");
            return $baseUrl . '/themes/default/';
        }
        
        return $baseUrl . '/themes/' . $slug . '/';
    }
    
    /**
     * Проверка существования темы
     * 
     * @param string $slug Slug темы
     * @return bool
     */
    public function themeExists(string $slug): bool {
        if (empty($slug)) {
            return false;
        }
        
        $theme = $this->getTheme($slug);
        return $theme !== null;
    }
    
    /**
     * Установка темы (регистрация в БД)
     * 
     * @param string $slug Slug темы
     * @param string $name Название темы
     * @param string $description Описание темы
     * @param string $version Версия темы
     * @param string $author Автор темы
     * @return bool
     */
    public function installTheme(string $slug, string $name, string $description = '', string $version = '1.0.0', string $author = ''): bool {
        if ($this->db === null || empty($slug) || empty($name)) {
            return false;
        }
        
        // Валидация входных данных
        if (!Validator::validateSlug($slug)) {
            error_log("ThemeManager: Invalid theme slug for installation: {$slug}");
            return false;
        }
        
        if (!Validator::validateString($name, 1, 255)) {
            error_log("ThemeManager: Invalid theme name: {$name}");
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO themes (slug, name, description, version, author, is_active) 
                VALUES (?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    description = VALUES(description),
                    version = VALUES(version),
                    author = VALUES(author)
            ");
            
            $result = $stmt->execute([$slug, $name, $description, $version, $author]);
            
            if ($result) {
                // Очищаем кеш
                cache_forget('all_themes');
                cache_forget('theme_' . $slug);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("ThemeManager installTheme error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Загрузка конфигурации темы
     * 
     * @param string|null $themeSlug Slug темы
     * @return array
     */
    public function getThemeConfig(?string $themeSlug = null): array {
        $theme = $themeSlug ? $this->getTheme($themeSlug) : $this->activeTheme;
        
        if ($theme === null) {
            // Возвращаем конфигурацию по умолчанию
            return [
                'name' => 'Default',
                'version' => '1.0.0',
                'description' => '',
                'default_settings' => [],
                'available_settings' => []
            ];
        }
        
        $slug = $theme['slug'] ?? 'default';
        
        // Безопасная проверка пути
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
        
        $configFile = dirname(__DIR__, 2) . '/themes/' . $slug . '/theme-config.php';
        
        if (file_exists($configFile) && is_readable($configFile)) {
            try {
                $config = require $configFile;
                return is_array($config) ? $config : [];
            } catch (Exception $e) {
                error_log("ThemeManager: Error loading theme config for {$slug}: " . $e->getMessage());
            }
        }
        
        // Возвращаем базовую конфигурацию, если файл не найден
        return [
            'name' => $theme['name'] ?? 'Default',
            'version' => $theme['version'] ?? '1.0.0',
            'description' => $theme['description'] ?? '',
            'default_settings' => [],
            'available_settings' => []
        ];
    }
}

/**
 * Глобальная функция для получения менеджера тем
 * 
 * @return ThemeManager
 */
function themeManager(): ThemeManager {
    static $instance = null;
    if ($instance === null) {
        $instance = new ThemeManager();
    }
    return $instance;
}

