<?php
/**
 * Сторінка керування темами
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class ThemesPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Теми - Flowaxy CMS';
        $this->templateName = 'themes';
        
        $headerButtons = '<div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadThemeModal">
                <i class="fas fa-upload me-1"></i>Завантажити тему
            </button>
            <a href="https://flowaxy.com/marketplace/themes" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-store me-1"></i>Скачати теми
            </a>
        </div>';
        
        $this->setPageHeader(
            'Теми',
            'Керування темами дизайну сайту',
            'fas fa-palette',
            $headerButtons
        );
    }
    
    public function handle() {
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обробка активації теми
        if ($_POST && isset($_POST['activate_theme'])) {
            $this->activateTheme();
        }
        
        // Очищаємо кеш тем для отримання актуальної інформації про активність
        themeManager()->clearThemeCache();
        
        // Отримання всіх тем
        $themes = themeManager()->getAllThemes();
        $activeTheme = themeManager()->getActiveTheme();
        
        // Перевіряємо підтримку кастомізації для кожної теми (з theme.json або customizer.php)
        $themesWithCustomization = [];
        foreach ($themes as $theme) {
            // Використовуємо supports_customization з theme.json, якщо є
            if (isset($theme['supports_customization'])) {
                $themesWithCustomization[$theme['slug']] = (bool)$theme['supports_customization'];
            } else {
                // Fallback: перевіряємо наявність customizer.php
                $themePath = themeManager()->getThemePath($theme['slug']);
                $themesWithCustomization[$theme['slug']] = file_exists($themePath . 'customizer.php');
            }
        }
        
        // Перевіряємо підтримку кастомізації активної теми
        $activeThemeSupportsCustomization = $activeTheme ? ($themesWithCustomization[$activeTheme['slug']] ?? false) : false;
        
        // Рендеримо сторінку
        $this->render([
            'themes' => $themes,
            'activeTheme' => $activeTheme,
            'themesWithCustomization' => $themesWithCustomization,
            'activeThemeSupportsCustomization' => $activeThemeSupportsCustomization
        ]);
    }
    
    /**
     * Активація теми
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
            Response::redirectStatic(adminUrl('themes'));
        } else {
            $this->setMessage('Помилка при активації теми', 'danger');
        }
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax() {
        // Використовуємо Response клас для встановлення заголовків
        Response::setHeader('Content-Type', 'application/json');
        
        $action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
        switch ($action) {
            case 'activate_theme':
                $this->ajaxActivateTheme();
                break;
                
            case 'check_compilation':
                $this->ajaxCheckCompilation();
                break;
                
            case 'upload_theme':
                $this->ajaxUploadTheme();
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    /**
     * AJAX активація теми з компіляцією SCSS
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
        
        // Перевіряємо, чи підтримує тема SCSS
        $hasScssSupport = themeManager()->hasScssSupport($themeSlug);
        
        // Компілюємо SCSS перед активацією, якщо тема підтримує SCSS
        if ($hasScssSupport) {
            $compileResult = themeManager()->compileScss($themeSlug, true);
            if (!$compileResult) {
                // Попереджаємо, але не блокуємо активацію
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
     * AJAX перевірка статусу компіляції
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
    
    /**
     * AJAX завантаження теми з ZIP архіву
     */
    private function ajaxUploadTheme(): void {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!isset($_FILES['theme_file']) || $_FILES['theme_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'Помилка завантаження файлу';
            if (isset($_FILES['theme_file']['error'])) {
                switch ($_FILES['theme_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMsg = 'Файл занадто великий';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMsg = 'Файл завантажено частково';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMsg = 'Файл не вибрано';
                        break;
                }
            }
            echo json_encode(['success' => false, 'error' => $errorMsg], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $file = $_FILES['theme_file'];
        $fileName = $file['name'];
        $tmpPath = $file['tmp_name'];
        
        // Перевірка розширення
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            echo json_encode(['success' => false, 'error' => 'Файл повинен бути ZIP архівом'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Перевірка розміру (максимум 50 MB)
        $maxSize = 50 * 1024 * 1024; // 50 MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'Розмір файлу перевищує 50 MB'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Перевіряємо наявність ZipArchive
            if (!class_exists('ZipArchive')) {
                echo json_encode(['success' => false, 'error' => 'Розширення ZipArchive не встановлено'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Відкриваємо ZIP архів
            $zip = new ZipArchive();
            $result = $zip->open($tmpPath);
            
            if ($result !== true) {
                echo json_encode(['success' => false, 'error' => 'Помилка відкриття ZIP архіву'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Перевіряємо наявність theme.json
            $hasThemeJson = false;
            $themeSlug = null;
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (basename($entryName) === 'theme.json') {
                    $hasThemeJson = true;
                    // Спробуємо визначити slug з шляху
                    $pathParts = explode('/', trim($entryName, '/'));
                    if (count($pathParts) >= 2) {
                        $themeSlug = $pathParts[0];
                    }
                    break;
                }
            }
            
            if (!$hasThemeJson) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Архів не містить theme.json'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Якщо slug не визначено, спробуємо прочитати theme.json
            if (!$themeSlug) {
                $themeJsonContent = $zip->getFromName('theme.json');
                if ($themeJsonContent) {
                    $config = json_decode($themeJsonContent, true);
                    if ($config && isset($config['slug'])) {
                        $themeSlug = $config['slug'];
                    }
                }
            }
            
            // Якщо все ще немає slug, використовуємо ім'я файлу без розширення
            if (!$themeSlug) {
                $themeSlug = pathinfo($fileName, PATHINFO_FILENAME);
            }
            
            // Очищаємо slug від небезпечних символів
            $themeSlug = preg_replace('/[^a-z0-9\-_]/i', '', $themeSlug);
            if (empty($themeSlug)) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Неможливо визначити slug теми'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Шлях до папки тем
            $themesDir = dirname(__DIR__, 3) . '/themes/';
            $themePath = $themesDir . $themeSlug . '/';
            
            // Перевіряємо, чи не існує вже тема з таким slug
            if (is_dir($themePath)) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Тема з таким slug вже існує: ' . $themeSlug], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Створюємо папку для теми
            if (!mkdir($themePath, 0755, true)) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Помилка створення папки теми'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Розпаковуємо архів
            // Визначаємо кореневу папку в архіві
            $rootPath = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (basename($entryName) === 'theme.json') {
                    $rootPath = dirname($entryName);
                    break;
                }
            }
            
            // Якщо theme.json в корені, rootPath буде '.'
            if ($rootPath === '.' || $rootPath === '') {
                $rootPath = null;
            }
            
            // Розпаковуємо файли
            $extracted = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                
                // Пропускаємо папки
                if (substr($entryName, -1) === '/') {
                    continue;
                }
                
                // Визначаємо шлях для витягування
                if ($rootPath) {
                    // Якщо є коренева папка, видаляємо її з шляху
                    if (strpos($entryName, $rootPath . '/') === 0) {
                        $relativePath = substr($entryName, strlen($rootPath) + 1);
                    } else {
                        continue; // Пропускаємо файли поза кореневою папкою
                    }
                } else {
                    $relativePath = $entryName;
                }
                
                // Пропускаємо небезпечні шляхи
                if (strpos($relativePath, '../') !== false || strpos($relativePath, '..\\') !== false) {
                    continue;
                }
                
                $targetPath = $themePath . $relativePath;
                $targetDir = dirname($targetPath);
                
                // Створюємо папки якщо потрібно
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Витягуємо файл
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($targetPath, $content);
                    $extracted++;
                }
            }
            
            $zip->close();
            
            // Очищаємо кеш тем
            themeManager()->clearThemeCache();
            
            echo json_encode([
                'success' => true,
                'message' => 'Тему успішно завантажено',
                'theme_slug' => $themeSlug,
                'extracted_files' => $extracted
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Theme upload error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}

