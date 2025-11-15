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
        
        // Рендерим страницу
        $this->render([
            'themes' => $themes,
            'activeTheme' => $activeTheme
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
            // Очищаем кеш настроек сайта (ThemeManager уже очищает кеш темы)
            cache_forget('site_settings');
        } else {
            $this->setMessage('Помилка при активації теми', 'danger');
        }
    }
}

