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
        // Обработка AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обработка активации темы
        if ($_POST && isset($_POST['activate_theme'])) {
            $this->activateTheme();
        }
        
        // Очищаем кеш тем для получения актуальной информации об активности
        themeManager()->clearThemeCache();
        
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
            themeManager()->clearThemeCache();
            
            // Очищаем кеш меню админки (все варианты)
            $cachePatterns = [
                'admin_menu_items_0',
                'admin_menu_items_1',
                'admin_menu_items_0_0',
                'admin_menu_items_0_1',
                'admin_menu_items_1_0',
                'admin_menu_items_1_1'
            ];
            foreach ($cachePatterns as $pattern) {
                cache_forget($pattern);
            }
            
            $this->setMessage('Тему успішно активовано', 'success');
            header('Location: ' . adminUrl('themes'));
            exit;
        } else {
            $this->setMessage('Помилка при активації теми', 'danger');
        }
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax() {
        header('Content-Type: application/json');
        
        $action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
        switch ($action) {
            case 'activate_theme':
                $this->ajaxActivateTheme();
                break;
                
            case 'check_compilation':
                $this->ajaxCheckCompilation();
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    /**
     * AJAX активация темы с компиляцией SCSS
     */
    private function ajaxActivateTheme() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $themeSlug = sanitizeInput($_POST['theme_slug'] ?? '');
        
        if (empty($themeSlug)) {
            echo json_encode(['success' => false, 'error' => 'Тему не вибрано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Проверяем, поддерживает ли тема SCSS
        $hasScssSupport = themeManager()->hasScssSupport($themeSlug);
        
        // Компилируем SCSS перед активацией, если тема поддерживает SCSS
        if ($hasScssSupport) {
            $compileResult = themeManager()->compileScss($themeSlug, true);
            if (!$compileResult) {
                // Предупреждаем, но не блокируем активацию
                error_log("ThemeManager: SCSS compilation failed for theme: {$themeSlug}");
            }
        }
        
        // Активируем тему
        if (themeManager()->activateTheme($themeSlug)) {
            // Очищаем все кеши после успешной активации
            themeManager()->clearThemeCache();
            
            // Очищаем кеш меню админки (все варианты)
            $cachePatterns = [
                'admin_menu_items_0',
                'admin_menu_items_1',
                'admin_menu_items_0_0',
                'admin_menu_items_0_1',
                'admin_menu_items_1_0',
                'admin_menu_items_1_1'
            ];
            foreach ($cachePatterns as $pattern) {
                cache_forget($pattern);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Тему успішно активовано',
                'has_scss' => $hasScssSupport,
                'compiled' => $hasScssSupport ? $compileResult : null
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Помилка при активації теми'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * AJAX проверка статуса компиляции
     */
    private function ajaxCheckCompilation() {
        $themeSlug = sanitizeInput($_GET['theme_slug'] ?? '');
        
        if (empty($themeSlug)) {
            echo json_encode(['success' => false, 'error' => 'Тему не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $hasScssSupport = themeManager()->hasScssSupport($themeSlug);
        $themePath = themeManager()->getThemePath($themeSlug);
        $cssFile = $themePath . 'assets/css/style.css';
        $cssExists = file_exists($cssFile);
        
        echo json_encode([
            'success' => true,
            'has_scss' => $hasScssSupport,
            'css_exists' => $cssExists,
            'css_file' => $cssExists ? 'assets/css/style.css' : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

