<?php
/**
 * Сторінка редактора теми
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class ThemeEditorPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Редактор теми - Flowaxy CMS';
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
            $this->redirect('themes');
        }
        
        // Перевіряємо існування теми
        $theme = themeManager()->getTheme($themeSlug);
        if ($theme === null) {
            $this->setMessage('Тему не знайдено', 'danger');
            $this->redirect('themes');
        }
        
        $themePath = themeManager()->getThemePath($themeSlug);
        
        // Отримуємо список файлів теми
        $themeFiles = $this->getThemeFiles($themePath);
        
        // Рендеримо сторінку
        $this->render([
            'theme' => $theme,
            'themePath' => $themePath,
            'themeFiles' => $themeFiles,
            'selectedFile' => $_GET['file'] ?? null
        ]);
    }
    
    /**
     * Отримання списку файлів теми
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
        
        // Сортуємо файли: спочатку основні, потім за ім'ям
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

