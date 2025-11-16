<?php
/**
 * Страница управления темами
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class ThemesPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Теми - Landing CMS';
        $this->templateName = 'themes';
        
        $marketplaceButton = '<a href="https://flowaxy.com/marketplace/themes" target="_blank" class="btn btn-primary">
            <i class="fas fa-store me-1"></i>Скачати теми
        </a>';
        
        $this->setPageHeader(
            'Теми',
            'Керування темами дизайну сайту',
            'fas fa-palette',
            $marketplaceButton
        );
    }
    
    public function handle() {
        // Обработка активации темы
        if ($_POST && isset($_POST['activate_theme'])) {
            $this->activateTheme();
        }
        
        // Очищаем кеш тем для получения актуальной информации об активности
        cache_forget('all_themes_filesystem');
        cache_forget('active_theme');
        cache_forget('active_theme_slug');
        
        // Очищаем кеш проверки активности для всех тем
        $themesDir = dirname(__DIR__, 3) . '/themes/';
        if (is_dir($themesDir)) {
            $directories = glob($themesDir . '*', GLOB_ONLYDIR);
            foreach ($directories as $dir) {
                $themeSlug = basename($dir);
                cache_forget('active_theme_check_' . md5($themeSlug));
            }
        }
        
        // Получение всех тем
        $themes = themeManager()->getAllThemes();
        $activeTheme = themeManager()->getActiveTheme();
        
        // Проверяем поддержку кастоматизации для каждой темы (из theme.json или customizer.php)
        $themesWithCustomization = [];
        foreach ($themes as $theme) {
            // Используем supports_customization из theme.json, если есть
            if (isset($theme['supports_customization'])) {
                $themesWithCustomization[$theme['slug']] = (bool)$theme['supports_customization'];
            } else {
                // Fallback: проверяем наличие customizer.php
                $themePath = themeManager()->getThemePath($theme['slug']);
                $themesWithCustomization[$theme['slug']] = file_exists($themePath . 'customizer.php');
            }
        }
        
        // Проверяем поддержку кастоматизации активной темы
        $activeThemeSupportsCustomization = $activeTheme ? ($themesWithCustomization[$activeTheme['slug']] ?? false) : false;
        
        // Рендерим страницу
        $this->render([
            'themes' => $themes,
            'activeTheme' => $activeTheme,
            'themesWithCustomization' => $themesWithCustomization,
            'activeThemeSupportsCustomization' => $activeThemeSupportsCustomization
        ]);
    }
    
    /**
     * Активация темы
     */
    private function activateTheme() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $themeSlug = $_POST['theme_slug'] ?? '';
        
        if (empty($themeSlug)) {
            $this->setMessage('Тему не вибрано', 'danger');
            return;
        }
        
        if (themeManager()->activateTheme($themeSlug)) {
            // Очищаем все кеши после успешной активации
            cache_forget('site_settings');
            cache_forget('active_theme');
            cache_forget('active_theme_slug');
            cache_forget('all_themes_filesystem');
            
            // Очищаем все варианты кеша меню админки
            cache_forget('admin_menu_items_0');
            cache_forget('admin_menu_items_1');
            cache_forget('admin_menu_items_0_0');
            cache_forget('admin_menu_items_0_1');
            cache_forget('admin_menu_items_1_0');
            cache_forget('admin_menu_items_1_1');
            
            // Очищаем кеш проверки активности для всех тем
            $themesDir = dirname(__DIR__, 3) . '/themes/';
            if (is_dir($themesDir)) {
                $directories = glob($themesDir . '*', GLOB_ONLYDIR);
                foreach ($directories as $dir) {
                    $slug = basename($dir);
                    cache_forget('active_theme_check_' . md5($slug));
                }
            }
            
            $this->setMessage('Тему успішно активовано', 'success');
            header('Location: ' . adminUrl('themes'));
            exit;
        } else {
            $this->setMessage('Помилка при активації теми', 'danger');
        }
    }
}

