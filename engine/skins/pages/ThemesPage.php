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
        
        // Обробка видалення теми
        if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_theme') {
            $this->deleteTheme();
        }
        
        // Очищаємо кеш тем для отримання актуальної інформації про активність
        themeManager()->clearThemeCache();
        
        // Отримання всіх тем
        $themes = themeManager()->getAllThemes();
        $activeTheme = themeManager()->getActiveTheme();
        
        // Перевіряємо підтримку кастомізації та навігації для кожної теми
        $themesWithCustomization = [];
        $themesWithNavigation = [];
        $themesWithSettings = [];
        $themesFeatures = [];
        foreach ($themes as $theme) {
            $themePath = themeManager()->getThemePath($theme['slug']);
            
            // Використовуємо supports_customization з theme.json, якщо є
            if (isset($theme['supports_customization'])) {
                $themesWithCustomization[$theme['slug']] = (bool)$theme['supports_customization'];
            } else {
                // Fallback: перевіряємо наявність customizer.php
                $themesWithCustomization[$theme['slug']] = file_exists($themePath . 'customizer.php');
            }
            
            // Перевіряємо підтримку навігації
            if (isset($theme['supports_navigation'])) {
                $themesWithNavigation[$theme['slug']] = (bool)$theme['supports_navigation'];
            } else {
                // Fallback: перевіряємо через ThemeManager
                $themesWithNavigation[$theme['slug']] = themeManager()->supportsNavigation($theme['slug']);
            }
            
            // Перевіряємо наявність налаштувань теми
            $themesWithSettings[$theme['slug']] = $this->themeHasSettings($theme['slug'], $themePath);
            
            // Перевіряємо підтримку функцій теми
            $themesFeatures[$theme['slug']] = $this->getThemeFeatures($theme, $themePath);
        }
        
        // Рендеримо сторінку
        $this->render([
            'themes' => $themes,
            'activeTheme' => $activeTheme,
            'themesWithCustomization' => $themesWithCustomization,
            'themesWithNavigation' => $themesWithNavigation,
            'themesWithSettings' => $themesWithSettings,
            'themesFeatures' => $themesFeatures
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
            Response::redirectStatic(UrlHelper::admin('themes'));
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
        
        $action = SecurityHelper::sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
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
                echo Json::encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
        }
    }
    
    /**
     * AJAX активація теми з компіляцією SCSS
     */
    private function ajaxActivateTheme() {
        if (!$this->verifyCsrf()) {
            echo Json::encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        $themeSlug = SecurityHelper::sanitizeInput($_POST['theme_slug'] ?? '');
        
        if (empty($themeSlug)) {
            echo Json::encode(['success' => false, 'error' => 'Тему не вибрано'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            
            echo Json::encode([
                'success' => true,
                'message' => 'Тему успішно активовано',
                'has_scss' => $hasScssSupport,
                'compiled' => $hasScssSupport ? $compileResult : null
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo Json::encode([
                'success' => false,
                'error' => 'Помилка при активації теми'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
    
    /**
     * AJAX перевірка статусу компіляції
     */
    private function ajaxCheckCompilation() {
        $themeSlug = SecurityHelper::sanitizeInput($_GET['theme_slug'] ?? '');
        
        if (empty($themeSlug)) {
            echo Json::encode(['success' => false, 'error' => 'Тему не вказано'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        $hasScssSupport = themeManager()->hasScssSupport($themeSlug);
        $themePath = themeManager()->getThemePath($themeSlug);
        $cssFile = $themePath . 'assets/css/style.css';
        $cssExists = file_exists($cssFile);
        
        echo Json::encode([
            'success' => true,
            'has_scss' => $hasScssSupport,
            'css_exists' => $cssExists,
            'css_file' => $cssExists ? 'assets/css/style.css' : null
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * AJAX завантаження теми з ZIP архіву
     */
    private function ajaxUploadTheme(): void {
        // Відключаємо вивід помилок на екран для запобігання HTML у JSON
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');
        
        // Очищаємо буфер виводу для запобігання виводу HTML перед JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // Встановлюємо заголовок JSON
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->verifyCsrf()) {
            ob_clean();
            try {
                echo Json::encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        }
        
        if (!isset($_FILES['theme_file'])) {
            ob_clean();
            try {
                echo Json::encode(['success' => false, 'error' => 'Файл не вибрано'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Файл не вибрано'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        }
        
        $uploadedFile = null;
        $zip = null;
        
        try {
            // Завантажуємо файл через клас Upload
            $upload = new Upload();
            $upload->setAllowedExtensions(['zip'])
                   ->setAllowedMimeTypes(['application/zip', 'application/x-zip-compressed'])
                   ->setMaxFileSize(50 * 1024 * 1024) // 50 MB
                   ->setNamingStrategy('random') // Використовуємо випадкове ім'я для уникнення конфліктів
                   ->setOverwrite(true); // Дозволяємо перезаписувати файли
            
            // Створюємо тимчасову директорію для завантаження
            // Використовуємо директорію всередині проекту для сумісності з різними хостингами
            $projectRoot = dirname(__DIR__, 3);
            $tempDir = null;
            $errors = [];
            
            // Клас Directory завантажується через автозавантажувач
            // Не потрібно завантажувати вручну
            
            // Функція для створення та перевірки директорії через клас Directory
            $createTempDir = function($dirPath, $parentDir = null) use (&$tempDir, &$errors) {
                try {
                    // Перевіряємо, чи клас Directory завантажений (наш клас, а не вбудований PHP)
                    // Перевіряємо наявність методу create() щоб переконатися що це наш клас
                    if (!class_exists('Directory') || !method_exists('Directory', 'create')) {
                        // Якщо не завантажений, використовуємо стандартні PHP функції
                        if ($parentDir && !is_dir($parentDir)) {
                            if (!@mkdir($parentDir, 0755, true)) {
                                $errors[] = "Не вдалося створити батьківську директорію: {$parentDir}";
                                return false;
                            }
                        }
                        
                        if ($parentDir && !is_writable($parentDir)) {
                            $errors[] = "Немає прав на запис у директорію: {$parentDir}";
                            return false;
                        }
                        
                        if (!is_dir($dirPath)) {
                            if (!@mkdir($dirPath, 0755, true)) {
                                $errors[] = "Не вдалося створити директорію: {$dirPath}";
                                return false;
                            }
                        }
                        
                        if (!is_writable($dirPath)) {
                            $errors[] = "Немає прав на запис у директорію: {$dirPath}";
                            return false;
                        }
                        
                        $tempDir = $dirPath;
                        return true;
                    }
                    
                    // Використовуємо клас Directory
                    // Спочатку перевіряємо/створюємо батьківську директорію
                    if ($parentDir) {
                        $parentDirObj = new Directory($parentDir);
                        if (!$parentDirObj->exists()) {
                            try {
                                $parentDirObj->create(0755, true);
                            } catch (Exception $e) {
                                $errors[] = "Не вдалося створити батьківську директорію: {$parentDir} - " . $e->getMessage();
                                return false;
                            }
                        }
                        
                        // Перевіряємо права на запис у батьківську директорію
                        if (!is_writable($parentDir)) {
                            $errors[] = "Немає прав на запис у директорію: {$parentDir}";
                            return false;
                        }
                    }
                    
                    // Створюємо тимчасову директорію через клас Directory
                    $dirObj = new Directory($dirPath);
                    if (!$dirObj->exists()) {
                        try {
                            $dirObj->create(0755, true);
                        } catch (Exception $e) {
                            $errors[] = "Не вдалося створити директорію: {$dirPath} - " . $e->getMessage();
                            return false;
                        }
                    }
                    
                    // Перевіряємо права на запис
                    if (!is_writable($dirPath)) {
                        $errors[] = "Немає прав на запис у директорію: {$dirPath}";
                        return false;
                    }
                    
                    $tempDir = $dirPath;
                    return true;
                } catch (Exception $e) {
                    $errors[] = "Помилка при роботі з директорією {$dirPath}: " . $e->getMessage();
                    return false;
                }
            };
            
            // Створюємо директорію в storage/temp/
            $storageParent = $projectRoot . '/storage';
            $storageDir = $storageParent . '/temp/';
            if (!$createTempDir($storageDir, $storageParent)) {
                $errorMsg = 'Не вдалося створити тимчасову директорію. ';
                $errorMsg .= 'Спробовано: ' . implode(', ', array_unique($errors));
                $errorMsg .= '. Перевірте права доступу до директорії storage/temp/';
                throw new Exception($errorMsg);
            }
            
            if (!$tempDir) {
                throw new Exception('Не вдалося визначити тимчасову директорію для завантаження');
            }
            
            $upload->setUploadDir($tempDir);
            
            $uploadResult = $upload->upload($_FILES['theme_file']);
            
            if (!$uploadResult['success']) {
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => $uploadResult['error']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $uploadResult['error']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            $uploadedFile = $uploadResult['file'];
            
            // Відкриваємо ZIP архів через клас Zip
            $zip = new Zip();
            $zip->open($uploadedFile, ZipArchive::RDONLY);
            
            // Перевіряємо наявність theme.json
            $entries = $zip->getEntries();
            $hasThemeJson = false;
            $themeJsonPath = null;
            $themeSlug = null;
            
            foreach ($entries as $entryName) {
                if (basename($entryName) === 'theme.json') {
                    $hasThemeJson = true;
                    $themeJsonPath = $entryName;
                    // Спробуємо визначити slug з шляху
                    $pathParts = explode('/', trim($entryName, '/'));
                    if (count($pathParts) >= 2) {
                        $themeSlug = $pathParts[0];
                    }
                    break;
                }
            }
            
            if (!$hasThemeJson) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Архів не містить theme.json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Архів не містить theme.json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Якщо slug не визначено, спробуємо прочитати theme.json
            if (!$themeSlug) {
                $themeJsonContent = $zip->getEntryContents($themeJsonPath);
                if ($themeJsonContent) {
                    $config = Json::decode($themeJsonContent, true);
                    if ($config && isset($config['slug'])) {
                        $themeSlug = $config['slug'];
                    }
                }
            }
            
            // Якщо все ще немає slug, використовуємо ім'я файлу без розширення
            if (!$themeSlug) {
                $themeSlug = pathinfo($_FILES['theme_file']['name'], PATHINFO_FILENAME);
            }
            
            // Очищаємо slug від небезпечних символів
            $themeSlug = preg_replace('/[^a-z0-9\-_]/i', '', $themeSlug);
            if (empty($themeSlug)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Неможливо визначити slug теми'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Неможливо визначити slug теми'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Шлях до папки тем
            $themesDir = dirname(__DIR__, 3) . '/themes/';
            $themePath = $themesDir . $themeSlug . '/';
            
            // Перевіряємо, чи не існує вже тема з таким slug
            if (is_dir($themePath)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Тема з таким slug вже існує: ' . $themeSlug], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Тема з таким slug вже існує: ' . $themeSlug], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Створюємо папку для теми
            if (!@mkdir($themePath, 0755, true)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Помилка створення папки теми'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Помилка створення папки теми'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Визначаємо кореневу папку в архіві
            $rootPath = null;
            if ($themeJsonPath) {
                $rootPath = dirname($themeJsonPath);
                if ($rootPath === '.' || $rootPath === '') {
                    $rootPath = null;
                }
            }
            
            // Розпаковуємо файли
            $extracted = 0;
            foreach ($entries as $entryName) {
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
                $targetDirPath = dirname($targetPath);
                
                // Створюємо папки якщо потрібно
                if (!is_dir($targetDirPath)) {
                    if (!@mkdir($targetDirPath, 0755, true)) {
                        error_log("Failed to create directory: {$targetDirPath}");
                        continue;
                    }
                }
                
                // Витягуємо файл
                try {
                    $zip->extractFile($entryName, $targetPath);
                    $extracted++;
                } catch (Exception $e) {
                    error_log("Failed to extract file {$entryName}: " . $e->getMessage());
                    // Продовжуємо з наступним файлом
                }
            }
            
            if ($zip) {
                $zip->close();
            }
            if ($uploadedFile && file_exists($uploadedFile)) {
                @unlink($uploadedFile);
            }
            
            // Очищаємо кеш тем
            themeManager()->clearThemeCache();
            
            // Очищаємо буфер перед виводом JSON
            ob_clean();
            try {
                $response = Json::encode([
                    'success' => true,
                    'message' => 'Тему успішно завантажено',
                    'theme_slug' => $themeSlug,
                    'extracted_files' => $extracted
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                error_log("JSON encode error: " . $e->getMessage());
                $response = json_encode([
                    'success' => true,
                    'message' => 'Тему успішно завантажено',
                    'theme_slug' => $themeSlug,
                    'extracted_files' => $extracted
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Throwable $e) {
            // Очищаємо ресурси при помилці
            if ($zip) {
                try {
                    $zip->close();
                } catch (Exception $ex) {
                    // Ігноруємо помилки закриття
                }
            }
            if ($uploadedFile && file_exists($uploadedFile)) {
                @unlink($uploadedFile);
            }
            
            error_log("Theme upload error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Очищаємо буфер перед виводом JSON
            ob_clean();
            try {
                $response = Json::encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $jsonEx) {
                error_log("JSON encode error: " . $jsonEx->getMessage());
                $response = json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } finally {
            // Відновлюємо налаштування помилок
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
            
            // Очищаємо всі буфери перед виводом
            while (ob_get_level() > 1) {
                ob_end_clean();
            }
            
            // Виводимо відповідь
            if (isset($response) && !empty($response)) {
                // Очищаємо буфер перед виводом
                if (ob_get_level()) {
                    ob_clean();
                }
                echo $response;
            } else {
                // Якщо відповідь не встановлена, виводимо помилку
                if (ob_get_level()) {
                    ob_clean();
                }
                try {
                    echo Json::encode(['success' => false, 'error' => 'Помилка: не вдалося сформувати відповідь'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Помилка: не вдалося сформувати відповідь'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            
            // Закриваємо останній буфер
            if (ob_get_level()) {
                ob_end_flush();
            }
        }
        
        exit;
    }
    
    /**
     * Проверка наличия настроек у темы
     */
    private function themeHasSettings(string $themeSlug, string $themePath): bool {
        // Проверяем наличие файла страницы настроек
        $settingsFiles = [
            $themePath . 'admin/SettingsPage.php',
            $themePath . 'admin/' . ucfirst($themeSlug) . 'SettingsPage.php',
            $themePath . 'SettingsPage.php'
        ];
        
        foreach ($settingsFiles as $file) {
            if (file_exists($file)) {
                return true;
            }
        }
        
        // Проверяем наличие theme.json с настройками
        $themeJsonFile = $themePath . 'theme.json';
        if (file_exists($themeJsonFile)) {
            $content = @file_get_contents($themeJsonFile);
            if ($content) {
                $config = Json::decode($content, true);
                if ($config && (isset($config['has_settings']) || isset($config['settings_page']))) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Получение поддерживаемых функций темы
     */
    private function getThemeFeatures(array $theme, string $themePath): array {
        $features = [
            'header' => false,
            'parameters' => false,
            'customization' => false,
            'logo' => false,
            'favicon' => false
        ];
        
        // Проверяем theme.json
        $themeJsonFile = $themePath . 'theme.json';
        if (file_exists($themeJsonFile)) {
            $content = @file_get_contents($themeJsonFile);
            if ($content) {
                $config = Json::decode($content, true);
                if ($config) {
                    $features['header'] = isset($config['supports_header']) ? (bool)$config['supports_header'] : false;
                    $features['parameters'] = isset($config['supports_parameters']) ? (bool)$config['supports_parameters'] : false;
                    $features['customization'] = isset($config['supports_customization']) ? (bool)$config['supports_customization'] : false;
                    $features['logo'] = isset($config['supports_logo']) ? (bool)$config['supports_logo'] : false;
                    $features['favicon'] = isset($config['supports_favicon']) ? (bool)$config['supports_favicon'] : false;
                }
            }
        }
        
        // Fallback: проверяем наличие файлов
        if (!$features['customization']) {
            $features['customization'] = file_exists($themePath . 'customizer.php');
        }
        
        if (!$features['parameters']) {
            $features['parameters'] = file_exists($themePath . 'admin/SettingsPage.php') || 
                                      file_exists($themePath . 'SettingsPage.php');
        }
        
        // Проверяем наличие header в index.php
        if (!$features['header']) {
            $indexFile = $themePath . 'index.php';
            if (file_exists($indexFile)) {
                $content = @file_get_contents($indexFile);
                if ($content && (stripos($content, '<header') !== false || stripos($content, 'header') !== false)) {
                    $features['header'] = true;
                }
            }
        }
        
        // Проверяем наличие логотипа и фавикона (обычно в assets или корне)
        if (!$features['logo']) {
            $logoFiles = [
                $themePath . 'assets/images/logo.png',
                $themePath . 'assets/images/logo.jpg',
                $themePath . 'assets/images/logo.svg',
                $themePath . 'logo.png',
                $themePath . 'logo.jpg',
                $themePath . 'logo.svg'
            ];
            foreach ($logoFiles as $logoFile) {
                if (file_exists($logoFile)) {
                    $features['logo'] = true;
                    break;
                }
            }
        }
        
        if (!$features['favicon']) {
            $faviconFiles = [
                $themePath . 'assets/images/favicon.ico',
                $themePath . 'favicon.ico',
                $themePath . 'assets/favicon.ico'
            ];
            foreach ($faviconFiles as $faviconFile) {
                if (file_exists($faviconFile)) {
                    $features['favicon'] = true;
                    break;
                }
            }
        }
        
        return $features;
    }
    
    /**
     * Видалення теми
     */
    private function deleteTheme() {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Помилка безпеки', 'danger');
            return;
        }
        
        $themeSlug = SecurityHelper::sanitizeInput($_POST['theme_slug'] ?? '');
        
        if (empty($themeSlug)) {
            $this->setMessage('Тему не вибрано', 'danger');
            return;
        }
        
        // Перевіряємо, чи тема активна
        $activeTheme = themeManager()->getActiveTheme();
        if ($activeTheme && $activeTheme['slug'] === $themeSlug) {
            $this->setMessage('Неможливо видалити активну тему. Спочатку активуйте іншу тему.', 'danger');
            return;
        }
        
        // Отримуємо шлях до теми
        $themePath = themeManager()->getThemePath($themeSlug);
        
        if (!is_dir($themePath)) {
            $this->setMessage('Тему не знайдено', 'danger');
            return;
        }
        
        // Видаляємо всі налаштування теми з theme_settings
        try {
            $db = DatabaseHelper::getConnection();
            if ($db) {
                $stmt = $db->prepare("DELETE FROM theme_settings WHERE theme_slug = ?");
                $stmt->execute([$themeSlug]);
            }
        } catch (Exception $e) {
            error_log("Error deleting theme settings: " . $e->getMessage());
        }
        
        // Видаляємо папку теми
        try {
            $this->deleteDirectory($themePath);
            
            // Очищаємо кеш тем
            themeManager()->clearThemeCache();
            
            $this->setMessage('Тему успішно видалено', 'success');
            Response::redirectStatic(UrlHelper::admin('themes'));
        } catch (Exception $e) {
            error_log("Theme delete error: " . $e->getMessage());
            $this->setMessage('Помилка при видаленні теми: ' . $e->getMessage(), 'danger');
        }
    }
    
    /**
     * Рекурсивне видалення директорії
     */
    private function deleteDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
}

