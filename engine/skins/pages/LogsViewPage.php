<?php
/**
 * Сторінка перегляду логів
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class LogsViewPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Перегляд логів - Flowaxy CMS';
        $this->templateName = 'logs-view';
        
        $this->setPageHeader(
            'Перегляд логів',
            'Системні логи та події',
            'fas fa-file-alt'
        );
    }
    
    public function handle() {
        // Обробка очистки логів
        if ($_POST && isset($_POST['clear_logs'])) {
            $fileToDelete = $this->post('file', '');
            $this->clearLogs();
            // После удаления конкретного файла происходит редирект с exit, дальше не выполняем
            // Если удаление всех файлов - продолжаем выполнение для отображения сообщения
            if ($fileToDelete !== 'all') {
                return;
            }
        }
        
        // Отримання списку логів
        $logFiles = $this->getLogFiles();
        $logContent = $this->getLogContent();
        
        // Кнопка очистки всех логов в заголовке (добавляем после получения логов)
        $headerButtons = '';
        if (!empty($logFiles)) {
            $headerButtons = $this->createButton('Очистити всі логи', 'danger', [
                'icon' => 'trash',
                'attributes' => [
                    'class' => 'btn-sm',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#clearAllLogsModal',
                    'onclick' => 'return false;'
                ]
            ]);
        }
        
        // Обновляем заголовок с кнопкой
        $this->setPageHeader(
            'Перегляд логів',
            'Системні логи та події',
            'fas fa-file-alt',
            $headerButtons
        );
        
        // Рендеримо сторінку
        $this->render([
            'logFiles' => $logFiles,
            'logContent' => $logContent,
            'selectedFile' => $_GET['file'] ?? null
        ]);
    }
    
    /**
     * Отримання списку файлів логів
     */
    private function getLogFiles(): array {
        $logsDir = dirname(__DIR__, 3) . '/storage/logs/';
        
        if (!is_dir($logsDir)) {
            return [];
        }
        
        $files = [];
        $pattern = $logsDir . '*.log';
        $fileList = glob($pattern);
        
        if ($fileList === false) {
            return [];
        }
        
        foreach ($fileList as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            
            $filename = basename($file);
            $fileSize = filesize($file);
            $modified = filemtime($file);
            
            $files[] = [
                'name' => $filename,
                'path' => $file,
                'size' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'modified' => date('d.m.Y H:i:s', $modified),
                'modified_timestamp' => $modified
            ];
        }
        
        // Сортуємо за датою модифікації (нові спочатку)
        usort($files, function($a, $b) {
            return $b['modified_timestamp'] - $a['modified_timestamp'];
        });
        
        return $files;
    }
    
    /**
     * Отримання вмісту лог-файлу
     */
    private function getLogContent(): array {
        $selectedFile = $_GET['file'] ?? null;
        
        if (empty($selectedFile)) {
            return [
                'lines' => [],
                'total_lines' => 0,
                'file' => null
            ];
        }
        
        $logsDir = dirname(__DIR__, 3) . '/storage/logs/';
        $filePath = $logsDir . basename($selectedFile);
        
        // Безпека: перевіряємо, що файл знаходиться в директорії логів
        if (!file_exists($filePath) || 
            !is_file($filePath) || 
            !is_readable($filePath) ||
            strpos(realpath($filePath), realpath($logsDir)) !== 0) {
            return [
                'lines' => [],
                'total_lines' => 0,
                'file' => null,
                'error' => 'Файл не знайдено або недоступний'
            ];
        }
        
        // Читаємо останні 500 рядків
        $lines = [];
        $file = fopen($filePath, 'r');
        
        if ($file) {
            $lineCount = 0;
            $totalLines = 0;
            
            // Підраховуємо загальну кількість рядків
            while (fgets($file) !== false) {
                $totalLines++;
            }
            rewind($file);
            
            // Читаємо останні 500 рядків
            $startLine = max(0, $totalLines - 500);
            $currentLine = 0;
            
            while (($line = fgets($file)) !== false) {
                if ($currentLine >= $startLine) {
                    $lines[] = [
                        'number' => $currentLine + 1,
                        'content' => rtrim($line)
                    ];
                }
                $currentLine++;
            }
            
            fclose($file);
        }
        
        return [
            'lines' => $lines,
            'total_lines' => $totalLines ?? 0,
            'file' => basename($selectedFile)
        ];
    }
    
    /**
     * Очистка логів (удаление файлов)
     */
    private function clearLogs() {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Помилка безпеки: невірний CSRF токен', 'danger');
            return;
        }
        
        $file = $this->post('file', null);
        $logsDir = dirname(__DIR__, 3) . '/storage/logs/';
        
        // Проверяем, что директория существует
        if (!is_dir($logsDir)) {
            $this->setMessage('Помилка: директорія логів не знайдена', 'danger');
            return;
        }
        
        if ($file === 'all') {
            // Удаляем все файлы логов
            $pattern = $logsDir . '*.log';
            $files = glob($pattern);
            $deleted = 0;
            
            if ($files !== false) {
                foreach ($files as $logFile) {
                    if (is_file($logFile) && is_writable($logFile)) {
                        if (@unlink($logFile)) {
                            $deleted++;
                        }
                    }
                }
            }
            
            $this->setMessage("Видалено {$deleted} файлів логів", 'success');
        } elseif (!empty($file)) {
            // Удаляем конкретный файл
            $filePath = $logsDir . basename($file);
            
            // Нормализуем пути для безопасной проверки
            $realFilePath = realpath($filePath);
            $realLogsDir = realpath($logsDir);
            
            if ($realFilePath === false || $realLogsDir === false) {
                $this->setMessage('Помилка: не вдалося визначити шлях до файлу', 'danger');
                return;
            }
            
            if (file_exists($filePath) && 
                is_file($filePath) && 
                is_writable($filePath)) {
                // Проверяем, что файл находится в директории логов
                if (strpos($realFilePath, $realLogsDir) === 0) {
                    if (@unlink($filePath)) {
                        $this->setMessage('Файл логу успішно видалено', 'success');
                        // Перенаправляем на страницу без выбранного файла
                        Response::redirectStatic(UrlHelper::admin('logs-view'));
                        exit; // Обязательно выходим после редиректа
                    } else {
                        $this->setMessage('Помилка при видаленні файлу логу. Перевірте права доступу.', 'danger');
                    }
                } else {
                    $this->setMessage('Помилка безпеки: файл знаходиться поза дозволеною директорією', 'danger');
                }
            } else {
                if (!file_exists($filePath)) {
                    $this->setMessage('Файл не знайдено: ' . basename($file), 'danger');
                } else if (!is_writable($filePath)) {
                    $this->setMessage('Файл недоступний для запису. Перевірте права доступу.', 'danger');
                } else {
                    $this->setMessage('Файл не знайдено або недоступний', 'danger');
                }
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
}

