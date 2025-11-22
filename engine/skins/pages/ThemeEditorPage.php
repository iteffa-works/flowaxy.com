<?php
/**
 * Сторінка редактора теми
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';
require_once __DIR__ . '/../../classes/managers/ThemeEditorManager.php';
require_once __DIR__ . '/../../classes/files/File.php';
// Directory завантажується через autoloader, не потрібно require_once
require_once __DIR__ . '/../../classes/files/Ini.php';
require_once __DIR__ . '/../../classes/files/Zip.php';
require_once __DIR__ . '/../../classes/data/Logger.php';
require_once __DIR__ . '/../../classes/validators/Validator.php';
require_once __DIR__ . '/../../classes/security/Security.php';
require_once __DIR__ . '/../../classes/security/Hash.php';
require_once __DIR__ . '/../../classes/http/Response.php';
require_once __DIR__ . '/../../classes/files/MimeType.php';
require_once __DIR__ . '/../../classes/data/Cache.php';

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
            // Використовуємо Hash для створення версії файлу (замість time() для кешування)
            $cssVersion = class_exists('Hash') ? substr(Hash::md5((string)filemtime(__DIR__ . '/../assets/styles/theme-editor.css')), 0, 8) : time();
            $this->additionalCSS[] = UrlHelper::admin('assets/styles/theme-editor.css') . '?v=' . $cssVersion;
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js';
        // Використовуємо Hash для створення версії файлу (замість time() для кешування)
        $jsVersion = class_exists('Hash') ? substr(Hash::md5((string)filemtime(__DIR__ . '/../assets/scripts/theme-editor.js')), 0, 8) : time();
        $this->additionalJS[] = UrlHelper::admin('assets/scripts/theme-editor.js') . '?v=' . $jsVersion;
    }
    
    public function handle(): void {
        $request = $this->request();
        
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
        
        // Обробка AJAX запитів через AdminPage метод
        if ($this->isAjaxRequest()) {
            $action = $request->postValue('action', '');
            
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
                $this->sendJsonResponse(['success' => false, 'error' => 'Невідома дія: ' . Security::clean($action)], 404);
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
            
            // Використовуємо клас File для отримання розширення
            $file = new File($filePath);
            $fileExtension = $file->getExtension();
        }
        
        // Встановлюємо заголовок сторінки з інформацією про тему
        $this->setPageHeader(
            'Редактор теми',
            'Редактор теми: ' . Security::clean($theme['name']),
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
        
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->postValue('theme', ''));
        $filePath = Validator::sanitizeString($request->postValue('file', ''));
        $content = $request->postValue('content', '');
        
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
        
        $request = $this->request();
        // Пробуем получить из POST, если нет - из GET
        $themeSlug = Validator::sanitizeString($request->postValue('theme', $request->query('theme', '')));
        $filePath = Validator::sanitizeString($request->postValue('file', $request->query('file', '')));
        
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
        
        // Використовуємо клас File для отримання розширення
        $file = new File($fullPath);
        
        $this->sendJsonResponse([
            'success' => true,
            'content' => $content,
            'extension' => $file->getExtension()
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
        
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->postValue('theme', ''));
        $filePath = Validator::sanitizeString($request->postValue('file', ''));
        $content = $request->postValue('content', '');
        
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
        
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->postValue('theme', ''));
        $filePath = Validator::sanitizeString($request->postValue('file', ''));
        
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
        
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->postValue('theme', ''));
        $dirPath = Validator::sanitizeString($request->postValue('directory', ''));
        
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
        
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->postValue('theme', ''));
        $folderPath = Validator::sanitizeString($request->postValue('folder', ''));
        
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
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->query('theme', ''));
        $filePath = Validator::sanitizeString($request->query('file', ''));
        
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
        
        try {
            // Використовуємо клас File для перевірки та відправки файлу
            $file = new File($fullPath);
            
            // Перевірка безпеки шляху через клас File
            $realThemePath = realpath($themePath);
            if ($realThemePath === false || !$file->exists() || !$file->isFile() || !$file->isPathSafe($realThemePath)) {
                $this->sendJsonResponse(['success' => false, 'error' => 'Файл не знайдено'], 404);
                return;
            }
            
            // Відправляємо файл через Response клас
            $fileName = $file->getBasename();
            // Використовуємо MimeType клас для визначення типу файлу
            $mimeType = $file->getMimeType();
            if ($mimeType === false) {
                // Якщо не вдалося визначити через finfo, використовуємо MimeType клас
                $mimeType = MimeType::get($file->getPath());
            }
            $contentType = $mimeType;
            
            // Вимикаємо буферизацію перед відправкою файлу
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Використовуємо Response для відправки файлу
            $response = new Response();
            $response->status(200)
                     ->header('Content-Type', $contentType)
                     ->header('Content-Disposition', 'attachment; filename="' . Security::clean($fileName) . '"')
                     ->header('Content-Length', (string)$file->getSize())
                     ->send();
            
            // Відправляємо вміст файлу
            readfile($file->getPath());
            exit;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorPage: Error downloading file', [
                    'error' => $e->getMessage(),
                    'theme' => $themeSlug,
                    'file' => $filePath
                ]);
            } else {
                error_log("ThemeEditorPage: Error downloading file: " . $e->getMessage());
            }
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка завантаження файлу'], 500);
            return;
        }
    }
    
    /**
     * AJAX: Скачування папки (ZIP архів)
     */
    private function ajaxDownloadFolder(): void {
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->query('theme', ''));
        $folderPath = Validator::sanitizeString($request->query('folder', ''));
        
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
            
            // Використовуємо клас File для перевірки та відправки ZIP
            $zipFile = new File($zipPath);
            
            if ($zipPath === null || !$zipFile->exists()) {
                if (class_exists('Logger')) {
                    Logger::getInstance()->logError('ThemeEditorPage: Failed to create ZIP archive', ['theme' => $themeSlug, 'folder' => $folderPath]);
                } else {
                    error_log("ThemeEditorPage: Failed to create ZIP archive. Theme: {$themeSlug}, Folder: {$folderPath}");
                }
                $this->sendJsonResponse(['success' => false, 'error' => 'Не вдалося створити архів. Перевірте права доступу та логи сервера.'], 500);
                return;
            }
            
            // Відправляємо ZIP файл
            // Використовуємо клас File для отримання імені папки
            $folderFile = new File($folderPath);
            $folderName = $folderFile->getFilename() ?: 'folder';
            $folderName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $folderName); // Безпечне ім'я файлу
            
            // Вимикаємо буферизацію перед відправкою файлу
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Використовуємо Response для відправки ZIP файлу
            $response = new Response();
            $response->status(200)
                     ->header('Content-Type', 'application/zip')
                     ->header('Content-Disposition', 'attachment; filename="' . Security::clean($folderName . '.zip') . '"')
                     ->header('Content-Length', (string)$zipFile->getSize())
                     ->header('Cache-Control', 'no-cache, must-revalidate')
                     ->header('Pragma', 'no-cache')
                     ->send();
            
            // Відправляємо вміст ZIP файлу
            readfile($zipPath);
            
            // Видаляємо тимчасовий файл
            try {
                $zipFile->delete();
            } catch (Exception $e) {
                @unlink($zipPath);
            }
            exit;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorPage: Exception in ajaxDownloadFolder', [
                    'error' => $e->getMessage(),
                    'theme' => $themeSlug,
                    'folder' => $folderPath
                ]);
            } else {
                error_log("ThemeEditorPage: Exception in ajaxDownloadFolder: " . $e->getMessage());
            }
            $this->sendJsonResponse(['success' => false, 'error' => 'Помилка створення архіву: ' . $e->getMessage()], 500);
            return;
        }
    }
    
    /**
     * Завантаження налаштувань редактора
     */
    private function loadEditorSettings(): array {
        $settingsFile = dirname(__DIR__, 2) . '/data/theme-editor.ini';
        
        $defaultSettings = [
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
        
        try {
            // Використовуємо клас Ini для роботи з налаштуваннями
            $ini = new Ini($settingsFile);
            
            if ($ini->exists() && $ini->isReadable()) {
                $ini->load();
                
                // Отримуємо всі налаштування
                foreach ($defaultSettings as $key => $defaultValue) {
                    $value = $ini->get($key, $defaultValue);
                    $defaultSettings[$key] = $value;
                }
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logWarning('ThemeEditorPage: Error loading editor settings', ['error' => $e->getMessage()]);
            } else {
                error_log("ThemeEditorPage: Error loading editor settings: " . $e->getMessage());
            }
        }
        
        return $defaultSettings;
    }
    
    /**
     * Отримання всіх папок теми (включаючи порожні)
     */
    private function getAllFolders(string $themePath): array {
        $folders = [];
        
        try {
            // Використовуємо стандартні PHP функції для перевірки
            if (!is_dir($themePath) || !is_readable($themePath)) {
                return $folders;
            }
            
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
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Error getting all folders', ['error' => $e->getMessage(), 'themePath' => $themePath]);
            } else {
                error_log("Error getting all folders: " . $e->getMessage());
            }
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
        
        $request = $this->request();
        $showEmptyFolders = $request->postValue('show_empty_folders', '0') === '1' ? '1' : '0';
        $enableSyntaxHighlighting = $request->postValue('enable_syntax_highlighting', '1') === '1' ? '1' : '0';
        $showLineNumbers = $request->postValue('show_line_numbers', '1') === '1' ? '1' : '0';
        $fontFamily = $request->postValue('font_family', "'Consolas', monospace");
        $fontSize = max(12, min(24, (int)$request->postValue('font_size', '14')));
        $editorTheme = $request->postValue('editor_theme', 'monokai');
        $indentSize = max(2, min(8, (int)$request->postValue('indent_size', '4')));
        $wordWrap = $request->postValue('word_wrap', '1') === '1' ? '1' : '0';
        $autoSave = $request->postValue('auto_save', '0') === '1' ? '1' : '0';
        $autoSaveInterval = max(30, min(300, (int)$request->postValue('auto_save_interval', '60')));
        
        $settingsDir = dirname(__DIR__, 2) . '/data';
        $settingsFile = $settingsDir . '/theme-editor.ini';
        
        try {
            // Створюємо директорію, якщо потрібно
            $dir = new Directory($settingsDir);
            if (!$dir->exists()) {
                $dir->create(0755, true);
            }
            
            // Використовуємо клас Ini для збереження налаштувань
            $ini = new Ini($settingsFile);
            
            // Встановлюємо всі налаштування
            $ini->set('show_empty_folders', $showEmptyFolders)
                ->set('enable_syntax_highlighting', $enableSyntaxHighlighting)
                ->set('show_line_numbers', $showLineNumbers)
                ->set('font_family', $fontFamily)
                ->set('font_size', (string)$fontSize)
                ->set('editor_theme', $editorTheme)
                ->set('indent_size', (string)$indentSize)
                ->set('word_wrap', $wordWrap)
                ->set('auto_save', $autoSave)
                ->set('auto_save_interval', (string)$autoSaveInterval);
            
            // Зберігаємо налаштування
            if (!$ini->save()) {
                throw new Exception('Не вдалося зберегти налаштування');
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('ThemeEditorPage: Error saving editor settings', ['error' => $e->getMessage()]);
            } else {
                error_log("ThemeEditorPage: Error saving editor settings: " . $e->getMessage());
            }
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вдалося зберегти налаштування: ' . $e->getMessage()], 500);
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
        
        $defaultSettings = [
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
        
        try {
            // Використовуємо клас Ini для роботи з налаштуваннями
            $ini = new Ini($settingsFile);
            
            if ($ini->exists() && $ini->isReadable()) {
                $ini->load();
                
                // Отримуємо всі налаштування
                foreach ($defaultSettings as $key => $defaultValue) {
                    $value = $ini->get($key, $defaultValue);
                    $defaultSettings[$key] = $value;
                }
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logWarning('ThemeEditorPage: Error getting editor settings', ['error' => $e->getMessage()]);
            } else {
                error_log("ThemeEditorPage: Error getting editor settings: " . $e->getMessage());
            }
        }

        $this->sendJsonResponse([
            'success' => true,
            'settings' => $defaultSettings
        ], 200);
    }
    
    /**
     * AJAX: Получение дерева файлов
     */
    private function ajaxGetFileTree(): void {
        $request = $this->request();
        $themeSlug = Validator::sanitizeString($request->postValue('theme', $request->query('theme', '')));
        
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
        $request = $this->request();
        $showEmptyFoldersParam = $request->postValue('show_empty_folders', '');
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

