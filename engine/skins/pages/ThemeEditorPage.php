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
        
        $this->setPageHeader(
            'Редактор теми',
            'Редагування файлів теми',
            'fas fa-code'
        );
        
        $this->editorManager = ThemeEditorManager::getInstance();
        
        // Додаємо CSS та JS для редактора
        $this->additionalCSS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css';
        $this->additionalCSS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js';
        $this->additionalJS[] = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js';
    }
    
    public function handle(): void {
        $request = Request::getInstance();
        
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
        
        // Отримуємо вміст вибраного файлу
        $selectedFile = $request->query('file', '');
        $fileContent = null;
        $fileExtension = '';
        
        if (!empty($selectedFile)) {
            $filePath = $themePath . $selectedFile;
            $fileContent = $this->editorManager->getFileContent($filePath);
            $fileExtension = pathinfo($selectedFile, PATHINFO_EXTENSION);
        }
        
        // Рендеримо сторінку
        $this->render([
            'theme' => $theme,
            'themePath' => $themePath,
            'themeFiles' => $themeFiles,
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
        $dirPath = SecurityHelper::sanitizeInput(Request::post('dir', ''));
        
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
}

