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
        
        // Регистрируем модальное окно загрузки темы через ModalHandler
        $this->registerModal('uploadThemeModal', [
            'title' => 'Завантажити тему',
            'type' => 'upload',
            'action' => 'upload_theme',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'fields' => [
                [
                    'type' => 'file',
                    'name' => 'theme_file',
                    'label' => 'ZIP архів з темою',
                    'help' => 'Максимальний розмір: 50 MB',
                    'required' => true,
                    'attributes' => [
                        'accept' => '.zip'
                    ]
                ]
            ],
            'buttons' => [
                [
                    'text' => 'Скасувати',
                    'type' => 'secondary',
                    'action' => 'close'
                ],
                [
                    'text' => 'Завантажити',
                    'type' => 'primary',
                    'icon' => 'upload',
                    'action' => 'submit'
                ]
            ]
        ]);
        
        // Регистрируем обработчик загрузки темы
        $this->registerModalHandler('uploadThemeModal', 'upload_theme', [$this, 'handleUploadTheme']);
        
        // Используем вспомогательные методы для создания кнопок
        $headerButtons = $this->createButtonGroup([
            [
                'text' => 'Завантажити тему',
                'type' => 'primary',
                'options' => [
                    'icon' => 'upload',
                    'attributes' => [
                        'data-bs-toggle' => 'modal', 
                        'data-bs-target' => '#uploadThemeModal',
                        'onclick' => 'window.ModalHandler && window.ModalHandler.show("uploadThemeModal")'
                    ]
                ]
            ]
        ]);
        
        $this->setPageHeader(
            'Теми',
            'Керування темами дизайну сайту',
            'fas fa-palette',
            $headerButtons
        );
    }
    
    public function handle() {
        // Обробка AJAX запитів через ModalHandler
        if ($this->isAjaxRequest()) {
            $modalId = $this->post('modal_id', '');
            $action = SecurityHelper::sanitizeInput($this->post('action', ''));
            
            if (!empty($modalId) && !empty($action)) {
                $this->handleModalRequest($modalId, $action);
                return;
            }
            
            // Старый способ обработки AJAX (для обратной совместимости)
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
            'themesFeatures' => $themesFeatures,
            'uploadModalHtml' => $this->renderModal('uploadThemeModal')
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
        
        // Активируем тему
        $result = themeManager()->activateTheme($themeSlug);
        
        if ($result) {
            // Очищаем все кеши после успешной активации
            themeManager()->clearThemeCache($themeSlug);
            
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
            
            // Дополнительно очищаем кеш активной темы
            cache_forget('active_theme_slug');
            cache_forget('active_theme');
            
            $this->setMessage('Тему успішно активовано', 'success');
            $this->redirect('themes');
        } else {
            $this->setMessage('Помилка при активації теми. Перевірте логи системи.', 'danger');
        }
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax() {
        // Используем Request напрямую из engine/classes
        $request = Request::getInstance();
        $action = SecurityHelper::sanitizeInput($request->get('action', $request->post('action', '')));
        
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
                $this->sendJsonResponse(['success' => false, 'error' => 'Невідома дія'], 400);
        }
    }
    
    /**
     * AJAX активація теми з компіляцією SCSS
     */
    private function ajaxActivateTheme() {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput($request->post('theme_slug', ''));
        
        if (empty($themeSlug)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не вибрано'], 400);
            return;
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
        $result = themeManager()->activateTheme($themeSlug);
        
        if ($result) {
            // Очищаем все кеши после успешной активации
            themeManager()->clearThemeCache($themeSlug);
            
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
            
            // Дополнительно очищаем кеш активной темы
            cache_forget('active_theme_slug');
            cache_forget('active_theme');
            
            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Тему успішно активовано',
                'has_scss' => $hasScssSupport,
                'compiled' => $hasScssSupport ? $compileResult : null
            ], 200);
            return;
        } else {
            $this->sendJsonResponse([
                'success' => false,
                'error' => 'Помилка при активації теми. Перевірте логи системи.'
            ], 500);
            return;
        }
    }
    
    /**
     * AJAX перевірка статусу компіляції
     */
    private function ajaxCheckCompilation() {
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput($request->query('theme_slug', ''));
        
        if (empty($themeSlug)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не вказано'], 400);
        }
        
        $hasScssSupport = themeManager()->hasScssSupport($themeSlug);
        $themePath = themeManager()->getThemePath($themeSlug);
        $cssFile = new File($themePath . 'assets/css/style.css');
        $cssExists = $cssFile->exists();
        
        $this->sendJsonResponse([
            'success' => true,
            'has_scss' => $hasScssSupport,
            'css_exists' => $cssExists,
            'css_file' => $cssExists ? 'assets/css/style.css' : null
        ], 200);
    }
    
    /**
     * Обработчик загрузки темы для ModalHandler
     * Использует логику из ajaxUploadTheme, но возвращает массив вместо отправки JSON
     * 
     * @param array $data Данные запроса
     * @param array $files Файлы
     * @return array Результат
     */
    public function handleUploadTheme(array $data, array $files): array {
        if (!$this->verifyCsrf()) {
            return ['success' => false, 'error' => 'Помилка безпеки', 'reload' => false];
        }
        
        if (!isset($files['theme_file'])) {
            return ['success' => false, 'error' => 'Файл не вибрано', 'reload' => false];
        }
        
        $uploadedFile = null;
        $zip = null;
        
        try {
            // Используем ту же логику что и в ajaxUploadTheme
            $upload = new Upload();
            $upload->setAllowedExtensions(['zip'])
                   ->setAllowedMimeTypes(['application/zip', 'application/x-zip-compressed'])
                   ->setMaxFileSize(50 * 1024 * 1024)
                   ->setNamingStrategy('random')
                   ->setOverwrite(true);
            
            $projectRoot = dirname(__DIR__, 3);
            $storageParent = $projectRoot . '/storage';
            $storageDir = $storageParent . '/temp/';
            
            // Создаем временную директорию
            if (!is_dir($storageDir)) {
                if (!@mkdir($storageDir, 0755, true)) {
                    return ['success' => false, 'error' => 'Не вдалося створити тимчасову директорію', 'reload' => false];
                }
            }
            
            $upload->setUploadDir($storageDir);
            $uploadResult = $upload->upload($files['theme_file']);
            
            if (!$uploadResult['success']) {
                return ['success' => false, 'error' => $uploadResult['error'], 'reload' => false];
            }
            
            $uploadedFile = $uploadResult['file'];
            
            // Открываем ZIP
            $zip = new Zip();
            $zip->open($uploadedFile, ZipArchive::RDONLY);
            
            // Проверяем наличие theme.json
            $entries = $zip->getEntries();
            $hasThemeJson = false;
            $themeJsonPath = null;
            $themeSlug = null;
            
            foreach ($entries as $entryName) {
                // Нормализуем путь (заменяем обратные слеши на прямые для Windows архивов)
                $normalizedPath = str_replace('\\', '/', $entryName);
                $normalizedPath = trim($normalizedPath, '/');
                
                // Пропускаем директории
                if (substr($normalizedPath, -1) === '/') {
                    continue;
                }
                
                // Проверяем, является ли файл theme.json (в любой папке)
                if (basename($normalizedPath) === 'theme.json') {
                    $hasThemeJson = true;
                    $themeJsonPath = $entryName; // Используем оригинальное имя для извлечения
                    
                    // Определяем slug из пути
                    $pathParts = explode('/', $normalizedPath);
                    if (count($pathParts) >= 2) {
                        // Первая часть пути - это обычно название темы (slug)
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
                return ['success' => false, 'error' => 'Архів не містить theme.json', 'reload' => false];
            }
            
            // Определяем slug
            if (!$themeSlug) {
                $themeJsonContent = $zip->getEntryContents($themeJsonPath);
                if ($themeJsonContent) {
                    $config = Json::decode($themeJsonContent, true);
                    if ($config && isset($config['slug'])) {
                        $themeSlug = $config['slug'];
                    }
                }
            }
            
            if (!$themeSlug) {
                $themeSlug = pathinfo($files['theme_file']['name'], PATHINFO_FILENAME);
            }
            
            $themeSlug = preg_replace('/[^a-z0-9\-_]/i', '', $themeSlug);
            if (empty($themeSlug)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                return ['success' => false, 'error' => 'Неможливо визначити slug теми', 'reload' => false];
            }
            
            // Путь к папке тем
            $themesDir = dirname(__DIR__, 3) . '/themes/';
            $themePath = $themesDir . $themeSlug . '/';
            
            // Проверяем, не существует ли уже тема
            if (is_dir($themePath)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                return ['success' => false, 'error' => 'Тема з таким slug вже існує: ' . $themeSlug, 'reload' => false];
            }
            
            // Создаем папку для темы
            if (!@mkdir($themePath, 0755, true)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                return ['success' => false, 'error' => 'Помилка створення папки теми', 'reload' => false];
            }
            
            // Определяем корневую папку в архиве
            $rootPath = null;
            if ($themeJsonPath) {
                $rootPath = dirname($themeJsonPath);
                if ($rootPath === '.' || $rootPath === '') {
                    $rootPath = null;
                }
            }
            
            // Распаковываем файлы
            $extracted = 0;
            foreach ($entries as $entryName) {
                if (substr($entryName, -1) === '/') {
                    continue;
                }
                
                if ($rootPath) {
                    if (strpos($entryName, $rootPath . '/') === 0) {
                        $relativePath = substr($entryName, strlen($rootPath) + 1);
                    } else {
                        continue;
                    }
                } else {
                    $relativePath = $entryName;
                }
                
                if (strpos($relativePath, '../') !== false || strpos($relativePath, '..\\') !== false) {
                    continue;
                }
                
                $targetPath = $themePath . $relativePath;
                $targetDirPath = dirname($targetPath);
                
                if (!is_dir($targetDirPath)) {
                    if (!@mkdir($targetDirPath, 0755, true)) {
                        error_log("Failed to create directory: {$targetDirPath}");
                        continue;
                    }
                }
                
                try {
                    $zip->extractFile($entryName, $targetPath);
                    $extracted++;
                } catch (Exception $e) {
                    error_log("Failed to extract file {$entryName}: " . $e->getMessage());
                }
            }
            
            if ($zip) {
                $zip->close();
            }
            if ($uploadedFile && file_exists($uploadedFile)) {
                @unlink($uploadedFile);
            }
            
            // Очищаем кеш тем
            themeManager()->clearThemeCache();
            
            return [
                'success' => true,
                'message' => 'Тему успішно завантажено',
                'theme_slug' => $themeSlug,
                'extracted_files' => $extracted,
                'reload' => true,
                'closeModal' => true
            ];
        } catch (Throwable $e) {
            if ($zip) {
                try {
                    $zip->close();
                } catch (Exception $ex) {
                    // Игнорируем ошибки закрытия
                }
            }
            if ($uploadedFile && file_exists($uploadedFile)) {
                @unlink($uploadedFile);
            }
            
            error_log("Theme upload error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage(), 'reload' => false];
        }
    }
    
    /**
     * AJAX завантаження теми з ZIP архіву (старый метод для обратной совместимости)
     */
    private function ajaxUploadTheme(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
        }
        
        $request = Request::getInstance();
        $files = $request->files();
        
        if (!isset($files['theme_file'])) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Файл не вибрано'], 400);
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
            
            // Создаем временную директорию
            $storageParent = $projectRoot . '/storage';
            $storageDir = $storageParent . '/temp/';
            
            // Создаем родительскую директорию если нужно
            if (!is_dir($storageParent)) {
                if (!@mkdir($storageParent, 0755, true)) {
                    throw new Exception('Не вдалося створити батьківську директорію: ' . $storageParent);
                }
            }
            
            // Создаем временную директорию
            if (!is_dir($storageDir)) {
                if (!@mkdir($storageDir, 0755, true)) {
                    throw new Exception('Не вдалося створити тимчасову директорію: ' . $storageDir);
                }
            }
            
            // Проверяем права на запись
            if (!is_writable($storageDir)) {
                throw new Exception('Немає прав на запис у директорію: ' . $storageDir);
            }
            
            $upload->setUploadDir($storageDir);
            $uploadResult = $upload->upload($files['theme_file']);
            
            if (!$uploadResult['success']) {
                $this->sendJsonResponse(['success' => false, 'error' => $uploadResult['error']], 400);
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
                // Нормализуем путь (заменяем обратные слеши на прямые для Windows архивов)
                $normalizedPath = str_replace('\\', '/', $entryName);
                $normalizedPath = trim($normalizedPath, '/');
                
                // Пропускаем директории
                if (substr($normalizedPath, -1) === '/') {
                    continue;
                }
                
                // Проверяем, является ли файл theme.json (в любой папке)
                if (basename($normalizedPath) === 'theme.json') {
                    $hasThemeJson = true;
                    $themeJsonPath = $entryName; // Используем оригинальное имя для извлечения
                    
                    // Определяем slug из пути
                    $pathParts = explode('/', $normalizedPath);
                    if (count($pathParts) >= 2) {
                        // Первая часть пути - это обычно название темы (slug)
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
                $this->sendJsonResponse(['success' => false, 'error' => 'Архів не містить theme.json'], 400);
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
                $request = Request::getInstance();
                $files = $request->files();
                $themeSlug = pathinfo($files['theme_file']['name'], PATHINFO_FILENAME);
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
                $this->sendJsonResponse(['success' => false, 'error' => 'Неможливо визначити slug теми'], 400);
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
                $this->sendJsonResponse(['success' => false, 'error' => 'Тема з таким slug вже існує: ' . $themeSlug], 400);
            }
            
            // Створюємо папку для теми
            if (!@mkdir($themePath, 0755, true)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                $this->sendJsonResponse(['success' => false, 'error' => 'Помилка створення папки теми'], 500);
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
            
            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Тему успішно завантажено',
                'theme_slug' => $themeSlug,
                'extracted_files' => $extracted
            ], 200);
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
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], 500);
        }
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
        
        // Проверяем наличие theme.json с настройками (используем File класс)
        $themeJsonFile = new File($themePath . 'theme.json');
        if ($themeJsonFile->exists()) {
            try {
                $content = $themeJsonFile->read();
                if ($content) {
                    $config = Json::decode($content, true);
                    if ($config && (isset($config['has_settings']) || isset($config['settings_page']))) {
                        return true;
                    }
                }
            } catch (Exception $e) {
                // Если не удалось прочитать файл, игнорируем
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
            $this->redirect('themes');
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

