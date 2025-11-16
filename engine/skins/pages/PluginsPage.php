<?php
/**
 * Страница управления плагинами
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class PluginsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Керування плагінами - Flowaxy CMS';
        $this->templateName = 'plugins';
        
        $headerButtons = '<div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadPluginModal">
                <i class="fas fa-upload me-1"></i>Завантажити плагін
            </button>
            <a href="https://flowaxy.com/marketplace/plugins" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-store me-1"></i>Скачати плагіни
            </a>
        </div>';
        
        $this->setPageHeader(
            'Керування плагінами',
            'Встановлення та налаштування плагінів',
            'fas fa-puzzle-piece',
            $headerButtons
        );
    }
    
    public function handle() {
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обработка действий
        if ($_POST) {
            $this->handleAction();
        }
        
        // Автоматическое обнаружение новых плагинов ТОЛЬКО по запросу пользователя
        // (через параметр ?discover=1 или через POST)
        if (isset($_GET['discover']) && $_GET['discover'] == '1') {
            try {
                $discovered = pluginManager()->autoDiscoverPlugins();
                if ($discovered > 0) {
                    $this->setMessage("Обнаружено и установлено новых плагинов: {$discovered}", 'success');
                } else {
                    $this->setMessage("Новых плагинов не обнаружено", 'info');
                }
                // Перенаправляем без параметра discover
                Response::redirectStatic(adminUrl('plugins'));
                return;
            } catch (Exception $e) {
                error_log("Auto-discover plugins error: " . $e->getMessage());
                $this->setMessage("Ошибка при обнаружении плагинов: " . $e->getMessage(), 'danger');
            }
        }
        
        // Получение списка плагинов
        $installedPlugins = $this->getInstalledPlugins();
        $stats = $this->calculateStats($installedPlugins);
        
        // Рендерим страницу
        $this->render([
            'installedPlugins' => $installedPlugins,
            'stats' => $stats
        ]);
    }
    
    /**
     * Обработка действий с плагинами
     */
    private function handleAction() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $action = $_POST['action'] ?? '';
        $pluginSlug = $_POST['plugin_slug'] ?? '';
        
        try {
            switch ($action) {
                case 'install':
                    pluginManager()->installPlugin($pluginSlug);
                    $this->setMessage('Плагін встановлено', 'success');
                    break;
                    
                case 'activate':
                    pluginManager()->activatePlugin($pluginSlug);
                    $this->setMessage('Плагін активовано', 'success');
                    break;
                    
                case 'deactivate':
                    pluginManager()->deactivatePlugin($pluginSlug);
                    $this->setMessage('Плагін деактивовано', 'success');
                    break;
                    
                case 'uninstall':
                    // Проверяем, деактивирован ли плагин
                    $db = $this->db;
                    if ($db) {
                        $stmt = $db->prepare("SELECT is_active FROM plugins WHERE slug = ?");
                        $stmt->execute([$pluginSlug]);
                        $plugin = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($plugin && !empty($plugin['is_active']) && $plugin['is_active'] == 1) {
                            $this->setMessage('Спочатку деактивуйте плагін перед видаленням', 'warning');
                            break;
                        }
                    }
                    
                    if (pluginManager()->uninstallPlugin($pluginSlug)) {
                        $this->setMessage('Плагін видалено', 'success');
                    } else {
                        $this->setMessage('Помилка видалення плагіна. Переконайтеся, що плагін деактивований', 'danger');
                    }
                    break;
            }
        } catch (Exception $e) {
            $this->setMessage('Помилка: ' . $e->getMessage(), 'danger');
            error_log("Plugin action error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение всех плагинов (из папки + БД)
     */
    private function getInstalledPlugins() {
        $allPlugins = [];
        $pluginsDir = dirname(__DIR__, 3) . '/plugins/';
        
        // Получаем плагины из БД
        $dbPlugins = [];
        try {
            $stmt = $this->db->query("SELECT * FROM plugins");
            $dbPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dbPlugins = array_column($dbPlugins, null, 'slug');
        } catch (Exception $e) {
            error_log("DB plugins error: " . $e->getMessage());
        }
        
        // Сканируем папку plugins
        if (is_dir($pluginsDir)) {
            $directories = glob($pluginsDir . '*', GLOB_ONLYDIR);
            
            foreach ($directories as $dir) {
                $slug = basename($dir);
                $configFile = $dir . '/plugin.json';
                
                if (file_exists($configFile) && is_readable($configFile)) {
                    $configContent = @file_get_contents($configFile);
                    if ($configContent === false) {
                        error_log("Cannot read plugin.json for plugin: {$slug}");
                        continue;
                    }
                    
                    $config = json_decode($configContent, true);
                    
                    if ($config && is_array($config)) {
                        // Используем slug из конфига или из имени директории
                        $pluginSlug = $config['slug'] ?? $slug;
                        
                        // Проверяем, установлен ли плагин в БД
                        $isInstalled = isset($dbPlugins[$pluginSlug]);
                        $isActive = $isInstalled && isset($dbPlugins[$pluginSlug]['is_active']) && $dbPlugins[$pluginSlug]['is_active'];
                        
                        $allPlugins[] = [
                            'slug' => $pluginSlug,
                            'name' => $config['name'] ?? $pluginSlug,
                            'description' => $config['description'] ?? '',
                            'version' => $config['version'] ?? '1.0.0',
                            'author' => $config['author'] ?? '',
                            'is_installed' => $isInstalled,
                            'is_active' => $isActive,
                            'settings' => $isInstalled && isset($dbPlugins[$pluginSlug]) ? ($dbPlugins[$pluginSlug]['settings'] ?? null) : null
                        ];
                    } else {
                        error_log("Invalid JSON in plugin.json for plugin: {$slug}");
                    }
                }
            }
        }
        
        return $allPlugins;
    }
    
    /**
     * Расчет статистики
     */
    private function calculateStats($plugins) {
        $installed = 0;
        $active = 0;
        $available = count($plugins);
        
        foreach ($plugins as $plugin) {
            if ($plugin['is_installed']) {
                $installed++;
            }
            if ($plugin['is_active']) {
                $active++;
            }
        }
        
        return [
            'total' => $available,
            'installed' => $installed,
            'active' => $active,
            'inactive' => $installed - $active
        ];
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax(): void {
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        $action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
        switch ($action) {
            case 'upload_plugin':
                $this->ajaxUploadPlugin();
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    /**
     * AJAX завантаження плагіна з ZIP архіву
     */
    private function ajaxUploadPlugin(): void {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!isset($_FILES['plugin_file']) || $_FILES['plugin_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'Помилка завантаження файлу';
            if (isset($_FILES['plugin_file']['error'])) {
                switch ($_FILES['plugin_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMsg = 'Файл занадто великий';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMsg = 'Файл завантажено частково';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMsg = 'Файл не вибрано';
                        break;
                }
            }
            echo json_encode(['success' => false, 'error' => $errorMsg], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $file = $_FILES['plugin_file'];
        $fileName = $file['name'];
        $tmpPath = $file['tmp_name'];
        
        // Перевірка розширення
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            echo json_encode(['success' => false, 'error' => 'Файл повинен бути ZIP архівом'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Перевірка розміру (максимум 50 MB)
        $maxSize = 50 * 1024 * 1024; // 50 MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'Розмір файлу перевищує 50 MB'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Перевіряємо наявність ZipArchive
            if (!class_exists('ZipArchive')) {
                echo json_encode(['success' => false, 'error' => 'Розширення ZipArchive не встановлено'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Відкриваємо ZIP архів
            $zip = new ZipArchive();
            $result = $zip->open($tmpPath);
            
            if ($result !== true) {
                echo json_encode(['success' => false, 'error' => 'Помилка відкриття ZIP архіву'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Перевіряємо наявність plugin.json
            $hasPluginJson = false;
            $pluginSlug = null;
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (basename($entryName) === 'plugin.json') {
                    $hasPluginJson = true;
                    // Спробуємо визначити slug з шляху
                    $pathParts = explode('/', trim($entryName, '/'));
                    if (count($pathParts) >= 2) {
                        $pluginSlug = $pathParts[0];
                    }
                    break;
                }
            }
            
            if (!$hasPluginJson) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Архів не містить plugin.json'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Якщо slug не визначено, спробуємо прочитати plugin.json
            if (!$pluginSlug) {
                $pluginJsonContent = $zip->getFromName('plugin.json');
                if ($pluginJsonContent) {
                    $config = json_decode($pluginJsonContent, true);
                    if ($config && isset($config['slug'])) {
                        $pluginSlug = $config['slug'];
                    }
                }
            }
            
            // Якщо все ще немає slug, використовуємо ім'я файлу без розширення
            if (!$pluginSlug) {
                $pluginSlug = pathinfo($fileName, PATHINFO_FILENAME);
            }
            
            // Очищаємо slug від небезпечних символів
            $pluginSlug = preg_replace('/[^a-z0-9\-_]/i', '', $pluginSlug);
            if (empty($pluginSlug)) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Неможливо визначити slug плагіна'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Шлях до папки плагінів
            $pluginsDir = dirname(__DIR__, 3) . '/plugins/';
            $pluginPath = $pluginsDir . $pluginSlug . '/';
            
            // Перевіряємо, чи не існує вже плагін з таким slug
            if (is_dir($pluginPath)) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Плагін з таким slug вже існує: ' . $pluginSlug], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Створюємо папку для плагіна
            if (!mkdir($pluginPath, 0755, true)) {
                $zip->close();
                echo json_encode(['success' => false, 'error' => 'Помилка створення папки плагіна'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Розпаковуємо архів
            // Визначаємо кореневу папку в архіві
            $rootPath = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (basename($entryName) === 'plugin.json') {
                    $rootPath = dirname($entryName);
                    break;
                }
            }
            
            // Якщо plugin.json в корені, rootPath буде '.'
            if ($rootPath === '.' || $rootPath === '') {
                $rootPath = null;
            }
            
            // Розпаковуємо файли
            $extracted = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                
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
                
                $targetPath = $pluginPath . $relativePath;
                $targetDir = dirname($targetPath);
                
                // Створюємо папки якщо потрібно
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Витягуємо файл
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($targetPath, $content);
                    $extracted++;
                }
            }
            
            $zip->close();
            
            // Автоматично встановлюємо плагін
            try {
                pluginManager()->installPlugin($pluginSlug);
                echo json_encode([
                    'success' => true,
                    'message' => 'Плагін успішно завантажено та встановлено',
                    'plugin_slug' => $pluginSlug,
                    'extracted_files' => $extracted
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Плагін завантажено, але помилка при встановленні: ' . $e->getMessage(),
                    'plugin_slug' => $pluginSlug,
                    'extracted_files' => $extracted
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Plugin upload error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}
