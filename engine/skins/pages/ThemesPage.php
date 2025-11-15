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
        
        $this->setPageHeader(
            'Теми',
            'Керування темами дизайну сайту',
            'fas fa-palette'
        );
    }
    
    public function handle() {
        // Обработка активации темы
        if ($_POST && isset($_POST['activate_theme'])) {
            $this->activateTheme();
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
            $this->setMessage('Тему успішно активовано', 'success');
            cache_forget('site_settings');
            cache_forget('admin_menu_items_0');
            cache_forget('admin_menu_items_1');
            cache_forget('active_theme');
            header('Location: ' . adminUrl('themes'));
            exit;
        } else {
            $this->setMessage('Помилка при активації теми', 'danger');
        }
    }
}

