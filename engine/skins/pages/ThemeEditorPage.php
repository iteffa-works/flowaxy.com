<?php
/**
 * Страница редактора темы
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class ThemeEditorPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Редактор теми - Landing CMS';
        $this->templateName = 'theme-editor';
        
        $this->setPageHeader(
            'Редактор теми',
            'Редагування файлів теми',
            'fas fa-code'
        );
    }
    
    public function handle() {
        $themeSlug = $_GET['theme'] ?? '';
        
        if (empty($themeSlug)) {
            $this->setMessage('Тему не вибрано', 'danger');
            header('Location: ' . adminUrl('themes'));
            exit;
        }
        
        // Проверяем существование темы
        $theme = themeManager()->getTheme($themeSlug);
        if ($theme === null) {
            $this->setMessage('Тему не знайдено', 'danger');
            header('Location: ' . adminUrl('themes'));
            exit;
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        
        // Получаем список файлов темы
        $themeFiles = $this->getThemeFiles($themePath);
        
        // Рендерим страницу
        $this->render([
            'theme' => $theme,
            'themePath' => $themePath,
            'themeFiles' => $themeFiles,
            'selectedFile' => $_GET['file'] ?? null
        ]);
    }
    
    /**
     * Получение списка файлов темы
     */
    private function getThemeFiles(string $themePath): array {
        $files = [];
        
        if (!is_dir($themePath)) {
            return $files;
        }
        
        $allowedExtensions = ['php', 'css', 'js', 'json', 'html', 'htm'];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($themePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $allowedExtensions, true)) {
                    $relativePath = str_replace($themePath, '', $file->getPathname());
                    $relativePath = ltrim($relativePath, '/\\');
                    
                    $files[] = [
                        'path' => $relativePath,
                        'fullPath' => $file->getPathname(),
                        'name' => $file->getFilename(),
                        'extension' => $extension,
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime()
                    ];
                }
            }
        }
        
        // Сортируем файлы: сначала основные, затем по имени
        usort($files, function($a, $b) {
            $priority = ['index.php', 'style.css', 'script.js', 'theme.json', 'customizer.php'];
            $aPriority = array_search($a['name'], $priority, true);
            $bPriority = array_search($b['name'], $priority, true);
            
            if ($aPriority !== false && $bPriority !== false) {
                return $aPriority <=> $bPriority;
            }
            if ($aPriority !== false) {
                return -1;
            }
            if ($bPriority !== false) {
                return 1;
            }
            
            return strcmp($a['name'], $b['name']);
        });
        
        return $files;
    }
}

