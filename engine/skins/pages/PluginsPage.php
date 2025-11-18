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
        
        // Используем компонент кнопки
        ob_start();
        $text = 'Завантажити плагін';
        $type = 'primary';
        $icon = 'upload';
        $attributes = ['data-bs-toggle' => 'modal', 'data-bs-target' => '#uploadPluginModal'];
        unset($url);
        include __DIR__ . '/../components/button.php';
        $uploadBtn = ob_get_clean();
        
        ob_start();
        $text = 'Скачати плагіни';
        $type = 'outline-primary';
        $url = 'https://flowaxy.com/marketplace/plugins';
        $icon = 'store';
        $attributes = ['target' => '_blank'];
        include __DIR__ . '/../components/button.php';
        $marketplaceBtn = ob_get_clean();
        
        $headerButtons = '<div class="d-flex gap-2">' . $uploadBtn . $marketplaceBtn . '</div>';
        
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
                Response::redirectStatic(UrlHelper::admin('plugins'));
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
                    
                    $config = Json::decode($configContent, true);
                    
                    if ($config && is_array($config)) {
                        // Используем slug из конфига или из имени директории
                        $pluginSlug = $config['slug'] ?? $slug;
                        
                        // Проверяем, установлен ли плагин в БД
                        $isInstalled = isset($dbPlugins[$pluginSlug]);
                        $isActive = $isInstalled && isset($dbPlugins[$pluginSlug]['is_active']) && $dbPlugins[$pluginSlug]['is_active'];
                        
                        // Проверяем наличие страницы настроек
                        $hasSettings = $this->pluginHasSettings($pluginSlug, $dir);
                        
                        $allPlugins[] = [
                            'slug' => $pluginSlug,
                            'name' => $config['name'] ?? $pluginSlug,
                            'description' => $config['description'] ?? '',
                            'version' => $config['version'] ?? '1.0.0',
                            'author' => $config['author'] ?? '',
                            'is_installed' => $isInstalled,
                            'is_active' => $isActive,
                            'has_settings' => $hasSettings,
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
     * Проверка наличия настроек у плагина
     */
    private function pluginHasSettings(string $pluginSlug, string $pluginDir): bool {
        // Проверяем наличие файла страницы настроек
        $settingsFiles = [
            $pluginDir . '/admin/SettingsPage.php',
            $pluginDir . '/admin/' . ucfirst($pluginSlug) . 'SettingsPage.php',
            $pluginDir . '/SettingsPage.php'
        ];
        
        foreach ($settingsFiles as $file) {
            if (file_exists($file)) {
                return true;
            }
        }
        
        // Проверяем наличие файла плагина и ищем регистрацию маршрута настроек
        $pluginFile = $pluginDir . '/' . $this->getPluginClassName($pluginSlug) . '.php';
        if (file_exists($pluginFile)) {
            $content = @file_get_contents($pluginFile);
            if ($content && (
                strpos($content, '-settings') !== false ||
                strpos($content, 'SettingsPage') !== false ||
                strpos($content, 'registerAdminRoute') !== false
            )) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получение имени класса плагина
     */
    private function getPluginClassName(string $pluginSlug): string {
        $parts = explode('-', $pluginSlug);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        return $className . 'Plugin';
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
        
        $action = SecurityHelper::sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
        switch ($action) {
            case 'upload_plugin':
                $this->ajaxUploadPlugin();
                break;
                
            default:
                try {
                    echo Json::encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
        }
    }
    
    /**
     * AJAX завантаження плагіна з ZIP архіву
     */
    private function ajaxUploadPlugin(): void {
        // Відключаємо вивід помилок на екран для запобігання HTML у JSON
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');
        
        // Очищаємо буфер виводу для запобігання виводу HTML перед JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // Встановлюємо заголовок JSON
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->verifyCsrf()) {
            ob_clean();
            try {
                echo Json::encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        }
        
        if (!isset($_FILES['plugin_file'])) {
            ob_clean();
            try {
                echo Json::encode(['success' => false, 'error' => 'Файл не вибрано'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Файл не вибрано'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        }
        
        $uploadedFile = null;
        $zip = null;
        
        try {
            // Завантажуємо файл через клас Upload
            $upload = new Upload();
            $upload->setAllowedExtensions(['zip'])
                   ->setAllowedMimeTypes(['application/zip', 'application/x-zip-compressed'])
                   ->setMaxFileSize(50 * 1024 * 1024) // 50 MB
                   ->setNamingStrategy('random') // Використовуємо випадкове ім'я для уникнення конфліктів
                   ->setOverwrite(true); // Дозволяємо перезаписувати файли
            
            // Створюємо тимчасову директорію для завантаження
            // Використовуємо директорію всередині проекту для сумісності з різними хостингами
            $projectRoot = dirname(__DIR__, 3);
            $tempDir = null;
            $errors = [];
            
            // Клас Directory завантажується через автозавантажувач
            // Не потрібно завантажувати вручну
            
            // Функція для створення та перевірки директорії через клас Directory
            $createTempDir = function($dirPath, $parentDir = null) use (&$tempDir, &$errors) {
                try {
                    // Перевіряємо, чи клас Directory завантажений (наш клас, а не вбудований PHP)
                    // Перевіряємо наявність методу create() щоб переконатися що це наш клас
                    if (!class_exists('Directory') || !method_exists('Directory', 'create')) {
                        // Якщо не завантажений, використовуємо стандартні PHP функції
                        if ($parentDir && !is_dir($parentDir)) {
                            if (!@mkdir($parentDir, 0755, true)) {
                                $errors[] = "Не вдалося створити батьківську директорію: {$parentDir}";
                                return false;
                            }
                        }
                        
                        if ($parentDir && !is_writable($parentDir)) {
                            $errors[] = "Немає прав на запис у директорію: {$parentDir}";
                            return false;
                        }
                        
                        if (!is_dir($dirPath)) {
                            if (!@mkdir($dirPath, 0755, true)) {
                                $errors[] = "Не вдалося створити директорію: {$dirPath}";
                                return false;
                            }
                        }
                        
                        if (!is_writable($dirPath)) {
                            $errors[] = "Немає прав на запис у директорію: {$dirPath}";
                            return false;
                        }
                        
                        $tempDir = $dirPath;
                        return true;
                    }
                    
                    // Використовуємо клас Directory
                    // Спочатку перевіряємо/створюємо батьківську директорію
                    if ($parentDir) {
                        $parentDirObj = new Directory($parentDir);
                        if (!$parentDirObj->exists()) {
                            try {
                                $parentDirObj->create(0755, true);
                            } catch (Exception $e) {
                                $errors[] = "Не вдалося створити батьківську директорію: {$parentDir} - " . $e->getMessage();
                                return false;
                            }
                        }
                        
                        // Перевіряємо права на запис у батьківську директорію
                        if (!is_writable($parentDir)) {
                            $errors[] = "Немає прав на запис у директорію: {$parentDir}";
                            return false;
                        }
                    }
                    
                    // Створюємо тимчасову директорію через клас Directory
                    $dirObj = new Directory($dirPath);
                    if (!$dirObj->exists()) {
                        try {
                            $dirObj->create(0755, true);
                        } catch (Exception $e) {
                            $errors[] = "Не вдалося створити директорію: {$dirPath} - " . $e->getMessage();
                            return false;
                        }
                    }
                    
                    // Перевіряємо права на запис
                    if (!is_writable($dirPath)) {
                        $errors[] = "Немає прав на запис у директорію: {$dirPath}";
                        return false;
                    }
                    
                    $tempDir = $dirPath;
                    return true;
                } catch (Exception $e) {
                    $errors[] = "Помилка при роботі з директорією {$dirPath}: " . $e->getMessage();
                    return false;
                }
            };
            
            // Створюємо директорію в storage/temp/
            $storageParent = $projectRoot . '/storage';
            $storageDir = $storageParent . '/temp/';
            if (!$createTempDir($storageDir, $storageParent)) {
                $errorMsg = 'Не вдалося створити тимчасову директорію. ';
                $errorMsg .= 'Спробовано: ' . implode(', ', array_unique($errors));
                $errorMsg .= '. Перевірте права доступу до директорії storage/temp/';
                throw new Exception($errorMsg);
            }
            
            if (!$tempDir) {
                throw new Exception('Не вдалося визначити тимчасову директорію для завантаження');
            }
            
            $upload->setUploadDir($tempDir);
            
            $uploadResult = $upload->upload($_FILES['plugin_file']);
            
            if (!$uploadResult['success']) {
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => $uploadResult['error']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $uploadResult['error']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            $uploadedFile = $uploadResult['file'];
            
            // Відкриваємо ZIP архів через клас Zip
            $zip = new Zip();
            $zip->open($uploadedFile, ZipArchive::RDONLY);
            
            // Перевіряємо наявність plugin.json
            $entries = $zip->getEntries();
            $hasPluginJson = false;
            $pluginJsonPath = null;
            $pluginSlug = null;
            
            foreach ($entries as $entryName) {
                if (basename($entryName) === 'plugin.json') {
                    $hasPluginJson = true;
                    $pluginJsonPath = $entryName;
                    // Спробуємо визначити slug з шляху
                    $pathParts = explode('/', trim($entryName, '/'));
                    if (count($pathParts) >= 2) {
                        $pluginSlug = $pathParts[0];
                    }
                    break;
                }
            }
            
            if (!$hasPluginJson) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Архів не містить plugin.json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Архів не містить plugin.json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Якщо slug не визначено, спробуємо прочитати plugin.json
            if (!$pluginSlug) {
                $pluginJsonContent = $zip->getEntryContents($pluginJsonPath);
                if ($pluginJsonContent) {
                    $config = Json::decode($pluginJsonContent, true);
                    if ($config && isset($config['slug'])) {
                        $pluginSlug = $config['slug'];
                    }
                }
            }
            
            // Якщо все ще немає slug, використовуємо ім'я файлу без розширення
            if (!$pluginSlug) {
                $pluginSlug = pathinfo($_FILES['plugin_file']['name'], PATHINFO_FILENAME);
            }
            
            // Очищаємо slug від небезпечних символів
            $pluginSlug = preg_replace('/[^a-z0-9\-_]/i', '', $pluginSlug);
            if (empty($pluginSlug)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Неможливо визначити slug плагіна'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Неможливо визначити slug плагіна'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Шлях до папки плагінів
            $pluginsDir = dirname(__DIR__, 3) . '/plugins/';
            $pluginPath = $pluginsDir . $pluginSlug . '/';
            
            // Перевіряємо, чи не існує вже плагін з таким slug
            if (is_dir($pluginPath)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Плагін з таким slug вже існує: ' . $pluginSlug], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Плагін з таким slug вже існує: ' . $pluginSlug], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Створюємо папку для плагіна
            if (!@mkdir($pluginPath, 0755, true)) {
                if ($zip) {
                    $zip->close();
                }
                if ($uploadedFile && file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }
                ob_clean();
                try {
                    echo Json::encode(['success' => false, 'error' => 'Помилка створення папки плагіна'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Помилка створення папки плагіна'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                exit;
            }
            
            // Визначаємо кореневу папку в архіві
            $rootPath = null;
            if ($pluginJsonPath) {
                $rootPath = dirname($pluginJsonPath);
                if ($rootPath === '.' || $rootPath === '') {
                    $rootPath = null;
                }
            }
            
            // Розпаковуємо файли
            $extracted = 0;
            foreach ($entries as $entryName) {
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
                $targetDirPath = dirname($targetPath);
                
                // Створюємо папки якщо потрібно
                if (!is_dir($targetDirPath)) {
                    if (!@mkdir($targetDirPath, 0755, true)) {
                        error_log("Failed to create directory: {$targetDirPath}");
                        continue;
                    }
                }
                
                // Витягуємо файл
                try {
                    $zip->extractFile($entryName, $targetPath);
                    $extracted++;
                } catch (Exception $e) {
                    error_log("Failed to extract file {$entryName}: " . $e->getMessage());
                    // Продовжуємо з наступним файлом
                }
            }
            
            if ($zip) {
                $zip->close();
            }
            if ($uploadedFile && file_exists($uploadedFile)) {
                @unlink($uploadedFile);
            }
            
            // Автоматично встановлюємо плагін
            try {
                pluginManager()->installPlugin($pluginSlug);
                // Очищаємо буфер перед виводом JSON
                ob_clean();
                try {
                    $response = Json::encode([
                        'success' => true,
                        'message' => 'Плагін успішно завантажено та встановлено',
                        'plugin_slug' => $pluginSlug,
                        'extracted_files' => $extracted
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    error_log("JSON encode error: " . $e->getMessage());
                    $response = json_encode([
                        'success' => true,
                        'message' => 'Плагін успішно завантажено та встановлено',
                        'plugin_slug' => $pluginSlug,
                        'extracted_files' => $extracted
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } catch (Exception $e) {
                // Очищаємо буфер перед виводом JSON
                ob_clean();
                try {
                    $response = Json::encode([
                        'success' => true,
                        'message' => 'Плагін завантажено, але помилка при встановленні: ' . $e->getMessage(),
                        'plugin_slug' => $pluginSlug,
                        'extracted_files' => $extracted
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $jsonEx) {
                    error_log("JSON encode error: " . $jsonEx->getMessage());
                    $response = json_encode([
                        'success' => true,
                        'message' => 'Плагін завантажено, але помилка при встановленні: ' . $e->getMessage(),
                        'plugin_slug' => $pluginSlug,
                        'extracted_files' => $extracted
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        } catch (Throwable $e) {
            // Очищаємо ресурси при помилці
            if ($zip) {
                try {
                    $zip->close();
                } catch (Exception $ex) {
                    // Ігноруємо помилки закриття
                }
            }
            if ($uploadedFile && file_exists($uploadedFile)) {
                @unlink($uploadedFile);
            }
            
            error_log("Plugin upload error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Очищаємо буфер перед виводом JSON
            ob_clean();
            try {
                $response = Json::encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $jsonEx) {
                error_log("JSON encode error: " . $jsonEx->getMessage());
                $response = json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } finally {
            // Відновлюємо налаштування помилок
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
            
            // Очищаємо всі буфери перед виводом
            while (ob_get_level() > 1) {
                ob_end_clean();
            }
            
            // Виводимо відповідь
            if (isset($response) && !empty($response)) {
                // Очищаємо буфер перед виводом
                if (ob_get_level()) {
                    ob_clean();
                }
                echo $response;
            } else {
                // Якщо відповідь не встановлена, виводимо помилку
                if (ob_get_level()) {
                    ob_clean();
                }
                try {
                    echo Json::encode(['success' => false, 'error' => 'Помилка: не вдалося сформувати відповідь'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Помилка: не вдалося сформувати відповідь'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            
            // Закриваємо останній буфер
            if (ob_get_level()) {
                ob_end_flush();
            }
        }
        
        exit;
    }
}
