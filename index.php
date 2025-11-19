<?php
/**
 * Головна точка входу системи
 * Універсальна обробка як фронтенду, так і адмінки
 * 
 * @version 6.0.0
 */

declare(strict_types=1);

// Підключаємо ініціалізацію системи
require_once __DIR__ . '/engine/init.php';

/**
 * Перезагрузка настроек БД из database.ini
 * Используется в установщике для обновления констант
 * 
 * @return void
 */
function reloadDatabaseConfig(): void {
    $databaseIniFile = __DIR__ . '/engine/data/database.ini';
    if (!file_exists($databaseIniFile)) {
        return;
    }
    
    try {
        $dbConfig = null;
        
        if (class_exists('Ini')) {
            $ini = new Ini($databaseIniFile);
            $dbConfig = $ini->getSection('database', []);
        }
        
        if (empty($dbConfig)) {
            $parsed = @parse_ini_file($databaseIniFile, true);
            $dbConfig = $parsed['database'] ?? [];
        }
        
        if (!empty($dbConfig)) {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = (int)($dbConfig['port'] ?? 3306);
            
            // Переопределяем константы (если они пустые)
            if (!defined('DB_HOST') || DB_HOST === '') {
                define('DB_HOST', $host . ':' . $port);
            }
            if (!defined('DB_NAME') || DB_NAME === '') {
                define('DB_NAME', $dbConfig['name'] ?? '');
            }
            if (!defined('DB_USER') || DB_USER === '') {
                define('DB_USER', $dbConfig['user'] ?? 'root');
            }
            if (!defined('DB_PASS') || DB_PASS === '') {
                define('DB_PASS', $dbConfig['pass'] ?? '');
            }
        }
    } catch (Exception $e) {
        error_log("Error reloading database config: " . $e->getMessage());
    }
}

// Проверка установки: если database.ini нет - запускаем установщик
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/install') !== 0) {
    $databaseIniFile = __DIR__ . '/engine/data/database.ini';
    if (!file_exists($databaseIniFile) && php_sapi_name() !== 'cli') {
        header('Location: /install');
        exit;
    }
}

/**
 * Перевірка та ініціалізація системи
 */
function initializeSystem(): void {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // Пропускаем проверку БД для установщика
    if (strpos($requestUri, '/install') === 0) {
        return;
    }
    
    // Проверяем наличие database.ini
    $databaseIniFile = __DIR__ . '/engine/data/database.ini';
    if (!file_exists($databaseIniFile) && php_sapi_name() !== 'cli') {
        header('Location: /install');
        exit;
    }
    
    // Проверяем подключение к БД
    if (!DatabaseHelper::isAvailable(false)) {
        showDatabaseError([
            'host' => DB_HOST,
            'database' => DB_NAME,
            'error' => 'Не вдалося підключитися до бази даних. Перевірте налаштування підключення.'
        ]);
        exit;
    }
}


/**
 * Рендеринг fallback сторінки коли тема не встановлена
 * 
 * @return bool
 */
function renderThemeFallback(): bool {
    http_response_code(200);
    $templatePath = __DIR__ . '/engine/templates/theme-not-installed.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="uk">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Встановіть тему</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <h1>Встановіть тему</h1>
                        <p class="text-muted">Для відображення сайту необхідно встановити та активувати тему.</p>
                        <a href="/admin/themes" class="btn btn-primary">Перейти до тем</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    return true;
}


// Ініціалізація системи
initializeSystem();

// Хук для обробки ранніх запитів (AJAX, API тощо)
$handled = doHook('handle_early_request', false);
if ($handled === true) {
    exit;
}

// Визначаємо тип запиту
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminRequest = strpos($requestUri, '/admin') === 0;
if (strpos($requestUri, '/install') === 0) {
    $step = $_GET['step'] ?? 'welcome';
    $action = $_GET['action'] ?? '';
    
    // AJAX действия
    if ($action === 'test_db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        try {
            $host = $_POST['db_host'] ?? '127.0.0.1';
            $port = (int)($_POST['db_port'] ?? 3306);
            $name = $_POST['db_name'] ?? '';
            $user = $_POST['db_user'] ?? 'root';
            $pass = $_POST['db_pass'] ?? '';
            
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);
            
            // Проверяем существование базы данных
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$name]);
            $dbExists = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'database_exists' => $dbExists !== false
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($action === 'create_table' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        try {
            reloadDatabaseConfig();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $table = $data['table'] ?? '';
            
            if (!class_exists('Installer')) {
                echo json_encode(['success' => false, 'message' => 'Installer not available']);
                exit;
            }
            
            $installer = Installer::getInstance();
            $tables = $installer->getTableDefinitions();
            
            if (!isset($tables[$table])) {
                echo json_encode(['success' => false, 'message' => 'Table not found']);
                exit;
            }
            
            $db = DatabaseHelper::getConnection();
            $db->exec($tables[$table]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Обработка шагов
    if ($step === 'welcome' || empty($step)) {
        $template = __DIR__ . '/engine/templates/installer-welcome.php';
        if (file_exists($template)) {
            include $template;
        } else {
            http_response_code(200);
            echo '<h1>Установка Flowaxy CMS</h1><p>Шаблон приветствия не найден</p>';
        }
        exit;
    }
    
    if ($step === 'database') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dbConfig = [
                    'host' => $_POST['db_host'] ?? '127.0.0.1',
                    'port' => (int)($_POST['db_port'] ?? 3306),
                    'name' => $_POST['db_name'] ?? '',
                    'user' => $_POST['db_user'] ?? 'root',
                    'pass' => $_POST['db_pass'] ?? '',
                    'charset' => 'utf8mb4'
                ];
                
                $iniFile = __DIR__ . '/engine/data/database.ini';
                
                if (class_exists('Ini')) {
                    $ini = new Ini();
                    $ini->setSection('database', $dbConfig);
                    $ini->save($iniFile);
                } else {
                    // Fallback: создаем файл вручную
                    $content = "[database]\n";
                    foreach ($dbConfig as $key => $value) {
                        $content .= "{$key} = {$value}\n";
                    }
                    @file_put_contents($iniFile, $content);
                }
                
                header('Location: /install?step=tables');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $template = __DIR__ . '/engine/templates/installer-database.php';
        if (file_exists($template)) {
            include $template;
        }
        exit;
    }
    
    if ($step === 'tables') {
        reloadDatabaseConfig();
        
        $template = __DIR__ . '/engine/templates/installer-tables.php';
        if (file_exists($template)) {
            include $template;
        }
        exit;
    }
    
    if ($step === 'user') {
        reloadDatabaseConfig();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';
                
                if ($password !== $passwordConfirm) {
                    $error = 'Паролі не співпадають';
                } elseif (strlen($password) < 8) {
                    $error = 'Пароль повинен містити мінімум 8 символів';
                } else {
                    $db = DatabaseHelper::getConnection();
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword]);
                    
                    // Устанавливаем флаг установки в БД
                    if (class_exists('Installer')) {
                        Installer::getInstance()->setInstallFlag($db);
                    }
                    
                    header('Location: /admin/login');
                    exit;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $template = __DIR__ . '/engine/templates/installer-user.php';
        if (file_exists($template)) {
            include $template;
        }
        exit;
    }
}

if ($isAdminRequest) {
    // Підключаємо шаблонизатор для адмінки
    require_once __DIR__ . '/engine/skins/includes/SimpleTemplate.php';
    
    // Створюємо роутер для адмінки з базовим шляхом /admin
    $router = new Router('/admin', 'dashboard');
    
    // Реєструємо маршрути адмінки з окремого файлу
    require_once __DIR__ . '/engine/skins/includes/admin-routes.php';
} else {
    // Створюємо роутер для фронтенду
    $router = new Router('/', null);
    
    // Додаємо дефолтний маршрут для фронтенду
    // Це гарантує, що маршрут буде доступний, якщо тема не зареєструє свій
    $router->add(['GET', 'POST'], '', function() {
        // Завантажуємо активну тему
        if (function_exists('themeManager')) {
            $themeManager = themeManager();
            $activeTheme = $themeManager->getActiveTheme();
            
            if ($activeTheme !== null && isset($activeTheme['slug'])) {
                $themePath = $themeManager->getThemePath($activeTheme['slug']);
                
                if (!empty($themePath)) {
                    $themeIndexFile = $themePath . 'index.php';
                    if (file_exists($themeIndexFile)) {
                        // Передаємо дані теми в шаблон
                        $themeData = [
                            'theme_path' => $themePath,
                            'theme_url' => '/themes/' . $activeTheme['slug'],
                            'theme_slug' => $activeTheme['slug']
                        ];
                        
                        // Включаємо шаблон теми
                        extract($themeData);
                        include $themeIndexFile;
                        return true;
                    }
                }
            }
        }
        
        // Якщо тема не завантажена, показуємо повідомлення
        return renderThemeFallback();
    });
}

// Для AJAX запросов очищаем буфер вывода перед обработкой
if (AjaxHandler::isAjax()) {
    // Очищаем все уровни буфера вывода
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Отключаем вывод ошибок на экран для AJAX
    ini_set('display_errors', '0');
}

// Обробляємо запит (роутер автоматично завантажить маршрути з модулів, плагінів та теми)
$router->dispatch();

