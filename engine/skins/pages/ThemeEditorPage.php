<?php
/**
 * Сторінка редактора теми
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';
require_once __DIR__ . '/../../classes/managers/ThemeEditorManager.php';

class ThemeEditorPage extends AdminPage {
    private ?ThemeEditorManager $editorManager = null;
    
    public function __construct() {
        parent::__construct();
        
        // Перевірка прав доступу
        if (!function_exists('current_user_can') || !current_user_can('admin.themes.edit')) {
            Response::redirectStatic(UrlHelper::admin('dashboard'));
            exit;
        }
        
        $this->pageTitle = 'Редактор теми - Flowaxy CMS';
        $this->templateName = 'theme-editor';
        
        $this->editorManager = ThemeEditorManager::getInstance();
        
        // Додаємо CSS та JS для редактора
        $this->additionalCSS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css';
        $this->additionalCSS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css';
        $this->additionalCSS[] = UrlHelper::admin('assets/styles/theme-editor.css') . '?v=' . time();
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js';
        $this->additionalJS[] = UrlHelper::admin('assets/scripts/theme-editor.js') . '?v=' . time();
    }
    
    public function handle(): void {
        $request = Request::getInstance();
        
        // Обробка GET запитів для скачування
        $getAction = $request->query('action', '');
        if ($getAction === 'download_file') {
            $this->ajaxDownloadFile();
            return;
        }
        if ($getAction === 'download_folder') {
            $this->ajaxDownloadFolder();
            return;
        }
        
        // Обробка AJAX запитів
        // Проверяем как через Request::isAjax(), так и напрямую через заголовок
        $xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $isAjax = $request->isAjax() || 
                  (!empty($xRequestedWith) && 
                   strtolower($xRequestedWith) === 'xmlhttprequest');
        
        if ($isAjax) {
            $action = Request::post('action', '');
            
            // Если action не найден в POST, пробуем GET
            if (empty($action)) {
                $action = $request->query('action', '');
            }
            
            // Если action пустой, не обрабатываем как AJAX
            if (empty($action)) {
                // Это не AJAX запрос с action, продолжаем обычную обработку
            } else {
                switch ($action) {
                    case 'save_file':
                        $this->ajaxSaveFile();
                        exit;
                    case 'get_file':
                        $this->ajaxGetFile();
                        exit;
                    case 'create_file':
                        $this->ajaxCreateFile();
                        exit;
                    case 'delete_file':
                        $this->ajaxDeleteFile();
                        exit;
                    case 'create_directory':
                        $this->ajaxCreateDirectory();
                        exit;
                    case 'upload_file':
                        $this->ajaxUploadFile();
                        exit;
                    case 'save_editor_settings':
                        $this->ajaxSaveEditorSettings();
                        exit;
                    case 'get_editor_settings':
                        $this->ajaxGetEditorSettings();
                        exit;
                    case 'get_file_tree':
                        $this->ajaxGetFileTree();
                        exit;
                }
                // Если мы дошли сюда, action не найден
                $this->sendJsonResponse(['success' => false, 'error' => 'Невідома дія: ' . htmlspecialchars($action)], 404);
                exit;
            }
        }
        
        $themeSlug = $request->query('theme', '');
        
        // Якщо тема не вказана, використовуємо активну тему
        if (empty($themeSlug)) {
            $activeTheme = themeManager()->getActiveTheme();
            if ($activeTheme !== null && isset($activeTheme['slug'])) {
                $themeSlug = $activeTheme['slug'];
            } else {
                // Якщо активної теми немає, перенаправляємо на сторінку тем
                $session = sessionManager();
                $session->setFlash('admin_message', 'Спочатку активуйте тему в розділі "Теми"');
                $session->setFlash('admin_message_type', 'warning');
                $this->redirect('themes');
                return;
            }
        }
        
        // Перевіряємо існування теми
        $theme = themeManager()->getTheme($themeSlug);
        if ($theme === null) {
            // Зберігаємо повідомлення в сесії для відображення після редиректу
            $session = sessionManager();
            $session->setFlash('admin_message', 'Тему не знайдено');
            $session->setFlash('admin_message_type', 'danger');
            $this->redirect('themes');
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        
        // Завантажуємо налаштування редактора
        $editorSettings = $this->loadEditorSettings();
        $showEmptyFolders = ($editorSettings['show_empty_folders'] ?? '1') === '1';
        $enableSyntaxHighlighting = ($editorSettings['enable_syntax_highlighting'] ?? '1') === '1';
        
        // Отримуємо список файлів теми
        $themeFiles = $this->editorManager->getThemeFiles($themePath);
        
        // Отримуємо список всіх папок (включаючи порожні)
        $allFolders = $this->getAllFolders($themePath);
        
        // Створюємо древовидну структуру файлів
        $fileTree = $this->buildFileTree($themeFiles, $allFolders, $showEmptyFolders);
        
        // Отримуємо вміст вибраного файлу
        $selectedFile = $request->query('file', '');
        $fileContent = null;
        $fileExtension = '';
        
        if (!empty($selectedFile)) {
            $filePath = $themePath . $selectedFile;
            $fileContent = $this->editorManager->getFileContent($filePath);
            $fileExtension = pathinfo($selectedFile, PATHINFO_EXTENSION);
        }
        
        // Встановлюємо заголовок сторінки з інформацією про тему
        $this->setPageHeader(
            'Редактор теми',
            'Редактор теми: ' . htmlspecialchars($theme['name']),
            'fas fa-code'
        );
        
        // Рендеримо сторінку
        $this->render([
            'theme' => $theme,
            'themePath' => $themePath,
            'themeFiles' => $themeFiles,
            'fileTree' => $fileTree,
            'selectedFile' => $selectedFile,
            'fileContent' => $fileContent,
            'fileExtension' => $fileExtension,
            'enableSyntaxHighlighting' => $enableSyntaxHighlighting,
            'editorSettings' => $editorSettings
        ]);
    }
    
    /**
     * AJAX: Збереження файлу
     */
    private function ajaxSaveFile(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput(Request::post('theme', ''));
        $filePath = SecurityHelper::sanitizeInput(Request::post('file', ''));
        $content = Request::post('content', '');
        
        if (empty($themeSlug) || empty($filePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або файл'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $fullPath = $themePath . $filePath;
        $result = $this->editorManager->saveFile($fullPath, $content, $themePath);
        
        if ($result['success']) {
            $this->sendJsonResponse($result, 200);
        } else {
            $this->sendJsonResponse($result, 400);
        }
    }
    
    /**
     * AJAX: Отримання вмісту файлу
     */
    private function ajaxGetFile(): void {
        // Убеждаемся, что это AJAX запрос
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendJsonResponse(['success' => false, 'error' => 'Это не AJAX запрос'], 400);
            return;
        }
        
        $request = Request::getInstance();
        // Пробуем получить из POST, если нет - из GET
        $themeSlug = SecurityHelper::sanitizeInput(Request::post('theme', $request->query('theme', '')));
        $filePath = SecurityHelper::sanitizeInput(Request::post('file', $request->query('file', '')));
        
        if (empty($themeSlug) || empty($filePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або файл'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $fullPath = $themePath . $filePath;
        $content = $this->editorManager->getFileContent($fullPath);
        
        if ($content === null) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Файл не знайдено або недоступний'], 404);
            return;
        }
        
        $this->sendJsonResponse([
            'success' => true,
            'content' => $content,
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION)
        ], 200);
    }
    
    /**
     * AJAX: Створення файлу
     */
    private function ajaxCreateFile(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput(Request::post('theme', ''));
        $filePath = SecurityHelper::sanitizeInput(Request::post('file', ''));
        $content = Request::post('content', '');
        
        if (empty($themeSlug) || empty($filePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або файл'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $result = $this->editorManager->createFile($filePath, $themePath, $content);
        
        if ($result['success']) {
            $this->sendJsonResponse($result, 200);
        } else {
            $this->sendJsonResponse($result, 400);
        }
    }
    
    /**
     * AJAX: Видалення файлу
     */
    private function ajaxDeleteFile(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput(Request::post('theme', ''));
        $filePath = SecurityHelper::sanitizeInput(Request::post('file', ''));
        
        if (empty($themeSlug) || empty($filePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або файл'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $fullPath = $themePath . $filePath;
        $result = $this->editorManager->deleteFile($fullPath, $themePath);
        
        if ($result['success']) {
            $this->sendJsonResponse($result, 200);
        } else {
            $this->sendJsonResponse($result, 400);
        }
    }
    
    /**
     * AJAX: Створення директорії
     */
    private function ajaxCreateDirectory(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput(Request::post('theme', ''));
        $dirPath = SecurityHelper::sanitizeInput(Request::post('directory', ''));
        
        if (empty($themeSlug) || empty($dirPath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або директорію'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $result = $this->editorManager->createDirectory($dirPath, $themePath);
        
        if ($result['success']) {
            $this->sendJsonResponse($result, 200);
        } else {
            $this->sendJsonResponse($result, 400);
        }
    }
    
    /**
     * AJAX: Завантаження файлу
     */
    private function ajaxUploadFile(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput(Request::post('theme', ''));
        $folderPath = SecurityHelper::sanitizeInput(Request::post('folder', ''));
        
        if (empty($themeSlug)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка завантаження файлу'], 400);
            return;
        }
        
        $result = $this->editorManager->uploadFile($_FILES['file'], $folderPath, $themePath);
        
        if ($result['success']) {
            $this->sendJsonResponse($result, 200);
        } else {
            $this->sendJsonResponse($result, 400);
        }
    }
    
    /**
     * AJAX: Скачування файлу
     */
    private function ajaxDownloadFile(): void {
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput($request->query('theme', ''));
        $filePath = SecurityHelper::sanitizeInput($request->query('file', ''));
        
        if (empty($themeSlug) || empty($filePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або файл'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $fullPath = $themePath . $filePath;
        
        // Перевірка безпеки шляху
        $realThemePath = realpath($themePath);
        $realFilePath = realpath($fullPath);
        
        if ($realThemePath === false || $realFilePath === false || 
            !str_starts_with($realFilePath, $realThemePath) || !is_file($realFilePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Файл не знайдено'], 404);
            return;
        }
        
        // Відправляємо файл
        $fileName = basename($filePath);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($realFilePath));
        readfile($realFilePath);
        exit;
    }
    
    /**
     * AJAX: Скачування папки (ZIP архів)
     */
    private function ajaxDownloadFolder(): void {
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput($request->query('theme', ''));
        $folderPath = SecurityHelper::sanitizeInput($request->query('folder', ''));
        
        if (empty($themeSlug) || empty($folderPath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вказано тему або папку'], 400);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        if (empty($themePath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        try {
            $zipPath = $this->editorManager->createFolderZip($folderPath, $themePath);
            
            if ($zipPath === null || !file_exists($zipPath)) {
                error_log("ThemeEditorPage: Failed to create ZIP archive. Theme: {$themeSlug}, Folder: {$folderPath}");
                $this->sendJsonResponse(['success' => false, 'error' => 'Не вдалося створити архів. Перевірте права доступу та логи сервера.'], 500);
                return;
            }
            
            // Відправляємо ZIP файл
            $folderName = basename($folderPath) ?: 'folder';
            $folderName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $folderName); // Безпечне ім'я файлу
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $folderName . '.zip"');
            header('Content-Length: ' . filesize($zipPath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Вимикаємо буферизацію для великих файлів
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            readfile($zipPath);
            
            // Видаляємо тимчасовий файл
            @unlink($zipPath);
            exit;
        } catch (Exception $e) {
            error_log("ThemeEditorPage: Exception in ajaxDownloadFolder: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка створення архіву: ' . $e->getMessage()], 500);
            return;
        }
    }
    
    /**
     * Завантаження налаштувань редактора
     */
    private function loadEditorSettings(): array {
        $settingsFile = dirname(__DIR__, 2) . '/data/theme-editor.ini';
        
        $settings = [
            'show_empty_folders' => '1',
            'enable_syntax_highlighting' => '1',
            'show_line_numbers' => '1',
            'font_family' => "'Consolas', monospace",
            'font_size' => '14',
            'editor_theme' => 'monokai',
            'indent_size' => '4',
            'word_wrap' => '1',
            'auto_save' => '0',
            'auto_save_interval' => '60'
        ];
        
        if (file_exists($settingsFile)) {
            $parsed = parse_ini_file($settingsFile);
            if ($parsed !== false) {
                $settings = array_merge($settings, $parsed);
            }
        }
        
        return $settings;
    }
    
    /**
     * Отримання всіх папок теми (включаючи порожні)
     */
    private function getAllFolders(string $themePath): array {
        $folders = [];
        
        if (!is_dir($themePath) || !is_readable($themePath)) {
            return $folders;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($themePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $relativePath = str_replace($themePath, '', $item->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $relativePath = ltrim($relativePath, '/');
                    
                    if (!empty($relativePath)) {
                        $folders[] = $relativePath;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error getting all folders: " . $e->getMessage());
        }
        
        return $folders;
    }
    
    /**
     * Побудова древовидної структури файлів
     */
    private function buildFileTree(array $files, array $allFolders = [], bool $showEmptyFolders = false): array {
        $tree = [];
        
        // Спочатку додаємо всі папки (включаючи порожні), якщо налаштування дозволяє
        if ($showEmptyFolders && !empty($allFolders)) {
            foreach ($allFolders as $folderPath) {
                if (empty($folderPath)) {
                    continue;
                }
                
                $parts = explode('/', $folderPath);
                $current = &$tree;
                
                // Проходимо по всіх частинах шляху
                foreach ($parts as $index => $part) {
                    if (!isset($current[$part])) {
                        // Створюємо папку, якщо її немає
                        $folderParts = array_slice($parts, 0, $index + 1);
                        $current[$part] = [
                            'type' => 'folder',
                            'name' => $part,
                            'path' => implode('/', $folderParts),
                            'children' => []
                        ];
                    }
                    
                    if (isset($current[$part]['type']) && $current[$part]['type'] === 'folder') {
                        if (!isset($current[$part]['children'])) {
                            $current[$part]['children'] = [];
                        }
                        $current = &$current[$part]['children'];
                    } else {
                        break;
                    }
                }
            }
        }
        
        // Потім додаємо файли та папки, що містять файли
        foreach ($files as $file) {
            $path = $file['path'];
            $parts = explode('/', $path);
            $current = &$tree;
            
            // Проходимо по всіх частинах шляху
            for ($i = 0; $i < count($parts) - 1; $i++) {
                $part = $parts[$i];
                
                if (!isset($current[$part])) {
                    $current[$part] = [
                        'type' => 'folder',
                        'name' => $part,
                        'path' => implode('/', array_slice($parts, 0, $i + 1)),
                        'children' => []
                    ];
                } else if (!isset($current[$part]['children'])) {
                    $current[$part]['children'] = [];
                }
                
                $current = &$current[$part]['children'];
            }
            
            // Додаємо файл
            $fileName = $parts[count($parts) - 1];
            $current[$fileName] = [
                'type' => 'file',
                'name' => $fileName,
                'path' => $path,
                'data' => $file
            ];
        }
        
        // Сортуємо: спочатку папки, потім файли
        $this->sortFileTree($tree);
        
        // Фільтруємо пусті папки, якщо потрібно
        if (!$showEmptyFolders) {
            $tree = $this->filterEmptyFolders($tree);
        }
        
        return $tree;
    }
    
    /**
     * Фільтрація пустих папок
     */
    private function filterEmptyFolders(array $tree): array {
        $filtered = [];
        
        foreach ($tree as $key => $item) {
            if ($item['type'] === 'folder') {
                // Рекурсивно фільтруємо дітей
                if (!empty($item['children'])) {
                    $filteredChildren = $this->filterEmptyFolders($item['children']);
                    // Якщо після фільтрації залишились діти, додаємо папку
                    if (!empty($filteredChildren)) {
                        $item['children'] = $filteredChildren;
                        $filtered[$key] = $item;
                    }
                }
            } else {
                // Файли завжди додаємо
                $filtered[$key] = $item;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Сортування древовидної структури
     */
    private function sortFileTree(array &$tree): void {
        uksort($tree, function($a, $b) use ($tree) {
            $aIsFolder = isset($tree[$a]['type']) && $tree[$a]['type'] === 'folder';
            $bIsFolder = isset($tree[$b]['type']) && $tree[$b]['type'] === 'folder';
            
            if ($aIsFolder && !$bIsFolder) {
                return -1;
            }
            if (!$aIsFolder && $bIsFolder) {
                return 1;
            }
            
            return strcmp($a, $b);
        });
        
        foreach ($tree as &$item) {
            if (isset($item['children'])) {
                $this->sortFileTree($item['children']);
            }
        }
    }
    
    /**
     * AJAX: Сохранение настроек редактора
     */
    private function ajaxSaveEditorSettings(): void {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка безпеки'], 403);
            return;
        }
        
        $showEmptyFolders = Request::post('show_empty_folders', '0') === '1' ? '1' : '0';
        $enableSyntaxHighlighting = Request::post('enable_syntax_highlighting', '1') === '1' ? '1' : '0';
        $showLineNumbers = Request::post('show_line_numbers', '1') === '1' ? '1' : '0';
        $fontFamily = Request::post('font_family', "'Consolas', monospace");
        $fontSize = max(12, min(24, (int)Request::post('font_size', '14')));
        $editorTheme = Request::post('editor_theme', 'monokai');
        $indentSize = max(2, min(8, (int)Request::post('indent_size', '4')));
        $wordWrap = Request::post('word_wrap', '1') === '1' ? '1' : '0';
        $autoSave = Request::post('auto_save', '0') === '1' ? '1' : '0';
        $autoSaveInterval = max(30, min(300, (int)Request::post('auto_save_interval', '60')));
        
        $settingsDir = dirname(__DIR__, 2) . '/data';
        if (!is_dir($settingsDir)) {
            if (!mkdir($settingsDir, 0755, true)) {
                $this->sendJsonResponse(['success' => false, 'error' => 'Не вдалося створити директорію для налаштувань'], 500);
                return;
            }
        }
        
        $settingsFile = $settingsDir . '/theme-editor.ini';
        
        $iniContent = "; Налаштування редактора теми\n";
        $iniContent .= "; Автоматично згенеровано\n\n";
        $iniContent .= "show_empty_folders = " . $showEmptyFolders . "\n";
        $iniContent .= "enable_syntax_highlighting = " . $enableSyntaxHighlighting . "\n";
        $iniContent .= "show_line_numbers = " . $showLineNumbers . "\n";
        $iniContent .= "font_family = " . $fontFamily . "\n";
        $iniContent .= "font_size = " . $fontSize . "\n";
        $iniContent .= "editor_theme = " . $editorTheme . "\n";
        $iniContent .= "indent_size = " . $indentSize . "\n";
        $iniContent .= "word_wrap = " . $wordWrap . "\n";
        $iniContent .= "auto_save = " . $autoSave . "\n";
        $iniContent .= "auto_save_interval = " . $autoSaveInterval . "\n";
        
        if (file_put_contents($settingsFile, $iniContent) === false) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вдалося зберегти налаштування'], 500);
            return;
        }
        
        $this->sendJsonResponse([
            'success' => true,
            'message' => 'Налаштування успішно збережено'
        ], 200);
    }
    
    /**
     * AJAX: Получение настроек редактора
     */
    private function ajaxGetEditorSettings(): void {
        $settingsFile = dirname(__DIR__, 2) . '/data/theme-editor.ini';
        
        $settings = [
            'show_empty_folders' => '1',
            'enable_syntax_highlighting' => '1',
            'show_line_numbers' => '1',
            'font_family' => "'Consolas', monospace",
            'font_size' => '14',
            'editor_theme' => 'monokai',
            'indent_size' => '4',
            'word_wrap' => '1',
            'auto_save' => '0',
            'auto_save_interval' => '60'
        ];
        
        if (file_exists($settingsFile)) {
            $parsed = parse_ini_file($settingsFile);
            if ($parsed !== false) {
                $settings = array_merge($settings, $parsed);
            }
        }

        $this->sendJsonResponse([
            'success' => true,
            'settings' => $settings
        ], 200);
    }
    
    /**
     * AJAX: Получение дерева файлов
     */
    private function ajaxGetFileTree(): void {
        $request = Request::getInstance();
        $themeSlug = SecurityHelper::sanitizeInput($request->post('theme', $request->query('theme', '')));
        
        if (empty($themeSlug)) {
            $activeTheme = themeManager()->getActiveTheme();
            if ($activeTheme !== null && isset($activeTheme['slug'])) {
                $themeSlug = $activeTheme['slug'];
            } else {
                $this->sendJsonResponse(['success' => false, 'error' => 'Тему не вказано'], 400);
                return;
            }
        }
        
        // Перевіряємо існування теми
        $theme = themeManager()->getTheme($themeSlug);
        if ($theme === null) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Тему не знайдено'], 404);
            return;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        
        // Завантажуємо налаштування редактора
        $editorSettings = $this->loadEditorSettings();
        
        // Отримуємо значення show_empty_folders з POST (якщо передано) або з налаштувань
        $showEmptyFoldersParam = Request::post('show_empty_folders', '');
        if ($showEmptyFoldersParam !== '') {
            // Якщо передано в POST, використовуємо його
            $showEmptyFolders = $showEmptyFoldersParam === '1';
        } else {
            // Інакше використовуємо з налаштувань
            $showEmptyFolders = ($editorSettings['show_empty_folders'] ?? '1') === '1';
        }
        
        // Отримуємо список файлів теми
        $themeFiles = $this->editorManager->getThemeFiles($themePath);
        
        // Отримуємо список всіх папок (включаючи порожні)
        $allFolders = $this->getAllFolders($themePath);
        
        // Створюємо древовидну структуру файлів
        $fileTree = $this->buildFileTree($themeFiles, $allFolders, $showEmptyFolders);
        
        // Конвертуємо дерево в массив для JSON
        $treeArray = $this->convertTreeToArray($fileTree);
        
        $this->sendJsonResponse([
            'success' => true,
            'tree' => $treeArray,
            'theme' => $theme
        ], 200);
    }
    
    /**
     * Конвертация дерева в массив для JSON
     */
    private function convertTreeToArray(array $tree): array {
        $result = [];
        
        foreach ($tree as $key => $item) {
            if ($item['type'] === 'folder') {
                $result[] = [
                    'type' => 'folder',
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'children' => $this->convertTreeToArray($item['children'] ?? [])
                ];
            } else {
                $result[] = [
                    'type' => 'file',
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'extension' => $item['data']['extension'] ?? '',
                    'size' => $item['data']['size'] ?? 0
                ];
            }
        }
        
        return $result;
    }
}

