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
        if ($request->isAjax()) {
            $action = Request::post('action', '');
            
            switch ($action) {
                case 'save_file':
                    $this->ajaxSaveFile();
                    return;
                case 'get_file':
                    $this->ajaxGetFile();
                    return;
                case 'create_file':
                    $this->ajaxCreateFile();
                    return;
                case 'delete_file':
                    $this->ajaxDeleteFile();
                    return;
                case 'create_directory':
                    $this->ajaxCreateDirectory();
                    return;
                case 'upload_file':
                    $this->ajaxUploadFile();
                    return;
                case 'save_editor_settings':
                    $this->ajaxSaveEditorSettings();
                    return;
                case 'get_editor_settings':
                    $this->ajaxGetEditorSettings();
                    return;
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
        
        // Отримуємо список файлів теми
        $themeFiles = $this->editorManager->getThemeFiles($themePath);
        
        // Створюємо древовидну структуру файлів
        $fileTree = $this->buildFileTree($themeFiles);
        
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
            'fileExtension' => $fileExtension
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
        
        $zipPath = $this->editorManager->createFolderZip($folderPath, $themePath);
        
        if ($zipPath === null || !file_exists($zipPath)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Не вдалося створити архів'], 500);
            return;
        }
        
        // Відправляємо ZIP файл
        $folderName = basename($folderPath) ?: 'folder';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $folderName . '.zip"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        
        // Видаляємо тимчасовий файл
        @unlink($zipPath);
        exit;
    }
    
    /**
     * Побудова древовидної структури файлів
     */
    private function buildFileTree(array $files): array {
        $tree = [];
        
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
        
        return $tree;
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
            'show_empty_folders' => '0',
            'enable_syntax_highlighting' => '1'
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
}

