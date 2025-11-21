<?php
/**
 * Сторінка перегляду кешу
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminPage.php';

class CacheViewPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Перегляд кешу - Flowaxy CMS';
        $this->templateName = 'cache-view';
    }
    
    public function handle() {
        // Обробка AJAX запитів для очистки кешу
        if ($this->isAjaxRequest() && isset($_POST['cache_action'])) {
            $this->handleAjaxCacheClear();
            return;
        }
        
        // Обробка очистки кешу через форму
        if ($_POST && isset($_POST['clear_cache'])) {
            $this->clearCache();
            // Після очистки робимо редирект для оновлення статистики
            Response::redirectStatic(UrlHelper::admin('cache-view'));
            exit;
        }
        
        // Отримання статистики кешу
        $cacheStats = $this->getCacheStats();
        $cacheFiles = $this->getCacheFiles();
        
        // Кнопки в заголовку
        $headerButtons = $this->createButtonGroup([
            [
                'text' => 'Очистити весь кеш',
                'type' => 'danger',
                'options' => [
                    'icon' => 'trash',
                    'attributes' => [
                        'class' => 'btn-sm',
                        'onclick' => "if(confirm('Ви впевнені, що хочете очистити весь кеш?')) { document.getElementById('clearAllCacheForm').submit(); } return false;"
                    ]
                ]
            ],
            [
                'text' => 'Очистити прострочений кеш',
                'type' => 'warning',
                'options' => [
                    'icon' => 'broom',
                    'attributes' => [
                        'class' => 'btn-sm',
                        'onclick' => "document.getElementById('clearExpiredCacheForm').submit(); return false;"
                    ]
                ]
            ]
        ]);
        
        $this->setPageHeader(
            'Перегляд кешу',
            'Статистика та управління кешем системи',
            'fas fa-database',
            $headerButtons
        );
        
        // Рендеримо сторінку
        $this->render([
            'cacheStats' => $cacheStats,
            'cacheFiles' => $cacheFiles
        ]);
    }
    
    /**
     * Обробка AJAX запиту для очистки кешу
     */
    private function handleAjaxCacheClear() {
        if (!$this->verifyCsrf()) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Помилка безпеки: невірний CSRF токен'
            ], 403);
            return;
        }
        
        $action = $this->post('cache_action', '');
        
        if ($action === 'clear_all') {
            if (function_exists('cache_flush')) {
                $result = cache_flush();
                if ($result) {
                    $this->sendJsonResponse([
                        'success' => true,
                        'message' => 'Весь кеш успішно очищено',
                        'reload' => true
                    ]);
                } else {
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => 'Помилка при очищенні кешу'
                    ], 500);
                }
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Помилка: функція очистки кешу недоступна'
                ], 500);
            }
        } elseif ($action === 'clear_expired') {
            if (function_exists('cache')) {
                $cleaned = cache()->cleanup();
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => "Очищено {$cleaned} прострочених файлів кешу",
                    'reload' => true
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Помилка: функція очистки кешу недоступна'
                ], 500);
            }
        } else {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Невідома дія'
            ], 400);
        }
    }
    
    /**
     * Отримання статистики кешу
     */
    private function getCacheStats(): array {
        if (!function_exists('cache')) {
            return [
                'total_files' => 0,
                'valid_files' => 0,
                'expired_files' => 0,
                'total_size' => 0,
                'total_size_formatted' => '0 B',
                'memory_cache_size' => 0
            ];
        }
        
        $stats = cache()->getStats();
        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size'] ?? 0);
        
        return $stats;
    }
    
    /**
     * Отримання списку файлів кешу
     */
    private function getCacheFiles(): array {
        if (!function_exists('cache')) {
            return [];
        }
        
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 3) . '/storage/cache/';
        $cacheDir = rtrim($cacheDir, '/') . '/';
        
        $files = [];
        $pattern = $cacheDir . '*.cache';
        $fileList = glob($pattern);
        
        if ($fileList === false) {
            return [];
        }
        
        $currentTime = time();
        
        foreach ($fileList as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            
            $data = @file_get_contents($file);
            if ($data === false) {
                continue;
            }
            
            try {
                $cached = unserialize($data, ['allowed_classes' => false]);
                
                if (!is_array($cached) || !isset($cached['expires'])) {
                    continue;
                }
                
                // Если поле created отсутствует (старые файлы), используем время модификации файла
                $created = $cached['created'] ?? filemtime($file);
                
                $key = basename($file, '.cache');
                $isExpired = ($cached['expires'] !== 0) && ($cached['expires'] < $currentTime);
                $fileSize = filesize($file);
                
                $ttl = ($cached['expires'] === 0) ? 0 : ($cached['expires'] - $created);
                
                $files[] = [
                    'key' => $key,
                    'file' => basename($file),
                    'size' => $fileSize,
                    'size_formatted' => $this->formatBytes($fileSize),
                    'created' => date('d.m.Y H:i:s', $created),
                    'expires' => ($cached['expires'] === 0) ? 'Без обмеження' : date('d.m.Y H:i:s', $cached['expires']),
                    'ttl' => $ttl,
                    'ttl_formatted' => ($ttl === 0) ? 'Без обмеження' : $this->formatDuration($ttl),
                    'is_expired' => $isExpired
                ];
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Сортуємо за датою створення (нові спочатку)
        usort($files, function($a, $b) {
            return strcmp($b['created'], $a['created']);
        });
        
        return $files;
    }
    
    /**
     * Очистка кешу
     */
    private function clearCache() {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Помилка безпеки: невірний CSRF токен', 'danger');
            return;
        }
        
        $action = $this->post('action', 'clear_all');
        
        if ($action === 'clear_all') {
            if (function_exists('cache_flush')) {
                $result = cache_flush();
                if ($result) {
                    $this->setMessage('Весь кеш успішно очищено', 'success');
                } else {
                    $this->setMessage('Помилка при очищенні кешу', 'danger');
                }
            } else {
                $this->setMessage('Помилка: функція очистки кешу недоступна', 'danger');
            }
        } elseif ($action === 'clear_expired') {
            if (function_exists('cache')) {
                $cleaned = cache()->cleanup();
                $this->setMessage("Очищено {$cleaned} прострочених файлів кешу", 'success');
            } else {
                $this->setMessage('Помилка: функція очистки кешу недоступна', 'danger');
            }
        }
    }
    
    /**
     * Форматування байтів
     */
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Форматування тривалості
     */
    private function formatDuration(int $seconds): string {
        if ($seconds < 60) {
            return $seconds . ' сек';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . ' хв';
        } elseif ($seconds < 86400) {
            return round($seconds / 3600, 1) . ' год';
        } else {
            return round($seconds / 86400, 1) . ' дн';
        }
    }
}

