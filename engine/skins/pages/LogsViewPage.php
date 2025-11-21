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
        
        // Підключаємо зовнішні CSS та JS файли
        $this->additionalCSS[] = UrlHelper::admin('assets/styles/logs-view.css') . '?v=' . time();
        $this->additionalJS[] = UrlHelper::admin('assets/scripts/logs-view.js') . '?v=' . time();
    }
    
    public function handle() {
        // Обробка очистки логів
        if ($_POST && isset($_POST['clear_logs'])) {
            $fileToDelete = $this->post('file', '');
            $this->clearLogs();
            // Після видалення конкретного файла відбувається редирект з exit, далі не виконуємо
            // Якщо видалення всіх файлів - продовжуємо виконання для відображення повідомлення
            if ($fileToDelete !== 'all') {
                return;
            }
        }
        
        // Отримання списку логів
        $logFiles = $this->getLogFiles();
        $logContent = $this->getLogContent();
        
        // Кнопка очистки всіх логів у заголовку (додаємо після отримання логів)
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
        
        // Оновлюємо заголовок з кнопкою
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
        
        // Получаем лимит из GET параметра
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        if ($limit <= 0) {
            $limit = PHP_INT_MAX; // Все записи
        }
        
        // Читаємо рядки
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
            
            // Читаємо все файл для парсинга записей (не строк)
            // Если лимит небольшой, можно оптимизировать, но для простоты читаем весь файл
            $currentLogEntry = '';
            $allEntries = [];
            
            // Читаем все записи из файла
            while (($line = fgets($file)) !== false) {
                $trimmedLine = rtrim($line);
                
                // Проверяем, начинается ли строка с timestamp [YYYY-MM-DD HH:MM:SS]
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $trimmedLine)) {
                    // Если есть незавершенная запись, сохраняем её
                    if (!empty($currentLogEntry)) {
                        $parsedLog = $this->parseLogEntry(trim($currentLogEntry));
                        if ($parsedLog) {
                            $allEntries[] = $parsedLog;
                        }
                    }
                    // Начинаем новую запись
                    $currentLogEntry = $trimmedLine;
                } else {
                    // Продолжение предыдущей записи (многострочный контекст)
                    $currentLogEntry .= "\n" . $trimmedLine;
                }
            }
            
            // Сохраняем последнюю запись
            if (!empty($currentLogEntry)) {
                $parsedLog = $this->parseLogEntry(trim($currentLogEntry));
                if ($parsedLog) {
                    $allEntries[] = $parsedLog;
                }
            }
            
            // Берем последние N записей и переворачиваем порядок (новые сначала)
            if ($limit === PHP_INT_MAX) {
                $lines = array_reverse($allEntries);
            } else {
                $lastEntries = array_slice($allEntries, -$limit);
                $lines = array_reverse($lastEntries); // Новые записи сначала
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
            // Редирект после удаления всех логов для предотвращения повторного выполнения
            Response::redirectStatic(UrlHelper::admin('logs-view'));
            exit;
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
     * Парсинг лог-записи
     */
    private function parseLogEntry(string $logLine): ?array {
        // Формат: [timestamp] LEVEL: message | IP: ... | Context: {...}
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(ERROR|WARNING|INFO|DEBUG|NOTICE):\s+(.+?)(?:\s+\|\s+IP:\s+([^\|]+))?(?:\s+\|\s+GET\s+([^\|]+))?(?:\s+\|\s+Context:\s+(.+))?$/s';
        
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(ERROR|WARNING|INFO|DEBUG|NOTICE):\s+(.+)$/s', $logLine, $matches)) {
            $timestamp = $matches[1] ?? '';
            $level = $matches[2] ?? 'INFO';
            $messageAndContext = $matches[3] ?? '';
            
            // Парсим дополнительные данные
            $ip = null;
            $url = null;
            $context = null;
            
            if (preg_match('/\|\s+IP:\s+([^\|]+)/', $logLine, $ipMatch)) {
                $ip = trim($ipMatch[1]);
            }
            
            if (preg_match('/\|\s+GET\s+([^\|]+)/', $logLine, $urlMatch)) {
                $url = trim($urlMatch[1]);
            }
            
            if (preg_match('/\|\s+Context:\s+(.+)$/s', $logLine, $contextMatch)) {
                $contextJson = trim($contextMatch[1]);
                // Пробуем распарсить JSON
                $decoded = json_decode($contextJson, true);
                $context = $decoded !== null ? $decoded : $contextJson;
            }
            
            // Убираем из сообщения уже распарсенные части
            $message = $messageAndContext;
            
            // Сначала убираем Context (самое длинное, может содержать пробелы)
            if ($context) {
                $message = preg_replace('/\s*\|\s+Context:\s+.+$/s', '', $message);
            }
            
            // Убираем IP и URL
            if ($ip) {
                $message = preg_replace('/\s*\|\s+IP:\s+[^\|]+/', '', $message);
            }
            if ($url) {
                $message = preg_replace('/\s*\|\s+GET\s+[^\|]+/', '', $message);
            }
            
            // Тщательная очистка от всех лишних пробелов
            $message = trim($message); // Убираем пробелы в начале и конце
            $message = preg_replace('/\s+/u', ' ', $message); // Заменяем множественные пробелы, табы, переносы на один пробел
            $message = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $message); // Заменяем все переносы и табы на пробелы
            $message = preg_replace('/\s+/u', ' ', $message); // Еще раз заменяем множественные пробелы на один
            $message = trim($message); // Финальная очистка
            
            return [
                'timestamp' => $timestamp,
                'level' => $level,
                'message' => $message,
                'ip' => $ip,
                'url' => $url,
                'context' => $context,
                'raw' => $logLine
            ];
        }
        
        // Если не удалось распарсить, возвращаем как есть
        return [
            'timestamp' => '',
            'level' => 'INFO',
            'message' => $logLine,
            'ip' => null,
            'url' => null,
            'context' => null,
            'raw' => $logLine
        ];
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

