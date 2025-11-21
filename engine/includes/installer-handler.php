<?php
/**
 * Обработчик установщика
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

// Получаем переменные из запроса
$step = $_GET['step'] ?? 'welcome';
$action = $_GET['action'] ?? '';
$databaseIniFile = __DIR__ . '/../data/database.ini';

// Блокировка доступа к установщику, если система уже установлена
// Исключение: AJAX запросы для тестирования БД (action=test_db, create_table)
// Это нужно для проверки подключения к БД во время установки
$isAjaxAction = ($action === 'test_db' || $action === 'create_table') && $_SERVER['REQUEST_METHOD'] === 'POST';

// Проверяем, идет ли процесс установки (есть настройки БД в сессии)
$isInstallationInProgress = isset($_SESSION['install_db_config']) && is_array($_SESSION['install_db_config']);

// Блокируем доступ только если файл создан И процесс установки не идет
if (!$isAjaxAction && file_exists($databaseIniFile) && !$isInstallationInProgress) {
    // Система уже установлена - блокируем доступ к установщику
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ заборонено - Flowaxy CMS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 28px;
        }
        p {
            color: #666;
            margin: 0 0 30px 0;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Доступ заборонено</h1>
        <p>Система вже встановлена. Доступ до сторінки установки блокується з метою безпеки.</p>
        <a href="/" class="btn">Перейти на головну</a>
        <a href="/admin" class="btn" style="margin-left: 10px; background: #764ba2;">Перейти в адмінку</a>
    </div>
</body>
</html>';
    exit;
}

// Инициализация переменных для проверки системы
$systemChecks = [];
$systemErrors = [];
$systemWarnings = [];

// AJAX: тест БД
if ($action === 'test_db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $host = $_POST['db_host'] ?? '127.0.0.1';
        $port = (int)($_POST['db_port'] ?? 3306);
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? 'root';
        $pass = $_POST['db_pass'] ?? '';
        
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$name]);
        
        echo json_encode(['success' => true, 'database_exists' => $stmt->fetch() !== false]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// AJAX: создание таблицы
if ($action === 'create_table' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $debugInfo = [];
    
    try {
        // Проверка системы перед созданием таблицы
        $checkErrors = [];
        
        // 1. Загрузка BaseModule
        if (!class_exists('BaseModule')) {
            $baseModuleFile = __DIR__ . '/../classes/base/BaseModule.php';
            $debugInfo['baseModuleFile'] = $baseModuleFile;
            $debugInfo['baseModuleExists'] = file_exists($baseModuleFile);
            if (file_exists($baseModuleFile)) {
                require_once $baseModuleFile;
                $debugInfo['baseModuleLoaded'] = class_exists('BaseModule');
            } else {
                $checkErrors[] = 'BaseModule не найден: ' . $baseModuleFile;
            }
        } else {
            $debugInfo['baseModuleExists'] = true;
            $debugInfo['baseModuleLoaded'] = true;
        }
        
        // 2. Загрузка InstallerManager
        if (!class_exists('InstallerManager')) {
            $installerFile = __DIR__ . '/../classes/managers/InstallerManager.php';
            $debugInfo['installerFile'] = $installerFile;
            $debugInfo['installerFileExists'] = file_exists($installerFile);
            $debugInfo['installerFileReadable'] = file_exists($installerFile) ? is_readable($installerFile) : false;
            
            if (file_exists($installerFile)) {
                require_once $installerFile;
                $debugInfo['installerLoaded'] = class_exists('InstallerManager');
                
                if (!class_exists('InstallerManager')) {
                    $checkErrors[] = 'InstallerManager не загрузился после require_once: ' . $installerFile;
                }
            } else {
                $checkErrors[] = 'InstallerManager не найден: ' . $installerFile;
            }
        } else {
            $debugInfo['installerFileExists'] = true;
            $debugInfo['installerLoaded'] = true;
        }
        
        if (!empty($checkErrors)) {
            error_log('InstallerManager System Check Errors: ' . json_encode($checkErrors, JSON_UNESCAPED_UNICODE));
            error_log('InstallerManager Debug Info: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            echo json_encode([
                'success' => false, 
                'message' => 'Ошибка проверки системы: ' . implode('; ', $checkErrors),
                'errors' => $checkErrors,
                'debug' => $debugInfo
            ]);
            exit;
        }
        
        // Загрузка конфигурации БД из сессии
        loadDatabaseConfigFromSession();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $table = $data['table'] ?? '';
        
        $debugInfo['table'] = $table;
        $debugInfo['dbHost'] = defined('DB_HOST') ? DB_HOST : 'not defined';
        $debugInfo['dbName'] = defined('DB_NAME') ? DB_NAME : 'not defined';
        $debugInfo['dbUser'] = defined('DB_USER') ? DB_USER : 'not defined';
        $debugInfo['dbPass'] = defined('DB_PASS') ? (empty(DB_PASS) ? 'empty' : '***') : 'not defined';
        
        // Проверка наличия конфигурации БД
        $databaseIniFile = __DIR__ . '/../data/database.ini';
        $debugInfo['databaseIniFile'] = $databaseIniFile;
        $debugInfo['databaseIniExists'] = file_exists($databaseIniFile);
        $debugInfo['databaseIniReadable'] = file_exists($databaseIniFile) ? is_readable($databaseIniFile) : false;
        
        if (!defined('DB_HOST') || empty(DB_HOST) || !defined('DB_NAME') || empty(DB_NAME)) {
            error_log('Database configuration not loaded. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            echo json_encode([
                'success' => false, 
                'message' => 'Конфигурация базы данных не загружена. Проверьте настройки подключения на предыдущем шаге.',
                'debug' => $debugInfo
            ]);
            exit;
        }
        
        // Проверка наличия DatabaseHelper
        if (!class_exists('DatabaseHelper')) {
            $databaseHelperFile = __DIR__ . '/../classes/helpers/DatabaseHelper.php';
            if (file_exists($databaseHelperFile)) {
                require_once $databaseHelperFile;
            } else {
                error_log('DatabaseHelper not found: ' . $databaseHelperFile);
                echo json_encode([
                    'success' => false, 
                    'message' => 'DatabaseHelper не найден: ' . $databaseHelperFile,
                    'debug' => $debugInfo
                ]);
                exit;
            }
        }
        
        if (!class_exists('InstallerManager')) {
            error_log('InstallerManager class not found after loading. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            echo json_encode([
                'success' => false, 
                'message' => 'InstallerManager not available after loading',
                'debug' => $debugInfo
            ]);
            exit;
        }
        
        $installer = InstallerManager::getInstance();
        if (!$installer) {
            error_log('Failed to get InstallerManager instance. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to get InstallerManager instance',
                'debug' => $debugInfo
            ]);
            exit;
        }
        
        $tables = $installer->getTableDefinitions();
        $debugInfo['tablesCount'] = count($tables);
        $debugInfo['availableTables'] = array_keys($tables);
        
        if (!isset($tables[$table])) {
            error_log('Table not found: ' . $table . '. Available: ' . implode(', ', array_keys($tables)));
            echo json_encode([
                'success' => false, 
                'message' => 'Table not found: ' . $table, 
                'available' => array_keys($tables),
                'debug' => $debugInfo
            ]);
            exit;
        }
        
        // Попытка подключения к БД
        try {
            $conn = DatabaseHelper::getConnection(false); // Не показываем страницу ошибки
            if (!$conn) {
                $lastError = error_get_last();
                
                // Попытка прямого подключения для диагностики
                try {
                    $hostParts = explode(':', DB_HOST);
                    $host = $hostParts[0] ?? '127.0.0.1';
                    $port = isset($hostParts[1]) ? (int)$hostParts[1] : 3306;
                    
                    $testConn = new PDO(
                        "mysql:host={$host};port={$port};charset=utf8mb4",
                        DB_USER,
                        DB_PASS,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5
                        ]
                    );
                    
                    // Проверка существования базы данных
                    $stmt = $testConn->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
                    $stmt->execute([DB_NAME]);
                    $dbExists = $stmt->fetch() !== false;
                    
                    $debugInfo['directConnection'] = 'success';
                    $debugInfo['databaseExists'] = $dbExists;
                    
                    if (!$dbExists) {
                        error_log('Database does not exist: ' . DB_NAME);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'База данных "' . DB_NAME . '" не существует. Создайте её в панели управления хостингом.',
                            'debug' => $debugInfo,
                            'lastError' => $lastError
                        ]);
                        exit;
                    }
                    
                    // Если база существует, пробуем подключиться к ней
                    $testConn = new PDO(
                        "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=utf8mb4",
                        DB_USER,
                        DB_PASS,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5
                        ]
                    );
                    
                    $debugInfo['directConnectionToDb'] = 'success';
                    $conn = $testConn;
                } catch (PDOException $e) {
                    $debugInfo['directConnectionError'] = $e->getMessage();
                    $debugInfo['directConnectionCode'] = $e->getCode();
                    
                    error_log('Database connection failed. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
                    error_log('PDO Error: ' . $e->getMessage());
                    error_log('Last error: ' . ($lastError ? json_encode($lastError, JSON_UNESCAPED_UNICODE) : 'none'));
                    
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Ошибка подключения к базе данных: ' . $e->getMessage(),
                        'debug' => $debugInfo,
                        'lastError' => $lastError,
                        'pdoCode' => $e->getCode(),
                        'pdoMessage' => $e->getMessage()
                    ]);
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Exception during database connection: ' . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Исключение при подключении к БД: ' . $e->getMessage(),
                'debug' => $debugInfo
            ]);
            exit;
        }
        
        $sql = $tables[$table];
        $debugInfo['sqlLength'] = strlen($sql);
        $debugInfo['sqlPreview'] = substr($sql, 0, 200) . '...';
        
        // Выполняем SQL с обработкой ошибок PDO
        try {
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->exec($sql);
            
            // После создания последней таблицы ролей (role_permissions) выполняем SQL для создания ролей и разрешений
            if ($table === 'role_permissions') {
                try {
                    $rolesSqlFile = __DIR__ . '/../db/roles_permissions.sql';
                    if (file_exists($rolesSqlFile)) {
                        $rolesSql = file_get_contents($rolesSqlFile);
                        if (!empty($rolesSql)) {
                            // Выполняем SQL по частям, пропуская CREATE TABLE (они уже созданы)
                            $statements = array_filter(
                                array_map('trim', explode(';', $rolesSql)),
                                fn($stmt) => !empty($stmt) && 
                                    !preg_match('/^--/', $stmt) && 
                                    !preg_match('/^\/\*/', $stmt) &&
                                    stripos($stmt, 'CREATE TABLE') === false
                            );
                            
                            foreach ($statements as $statement) {
                                // Пропускаем комментарии
                                $statement = preg_replace('/--.*$/m', '', $statement);
                                $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
                                $statement = trim($statement);
                                
                                if (!empty($statement)) {
                                    try {
                                        $conn->exec($statement);
                                    } catch (Exception $e) {
                                        // Игнорируем ошибки типа "уже существует" (INSERT IGNORE)
                                        if (stripos($e->getMessage(), 'Duplicate') === false && 
                                            stripos($e->getMessage(), 'already exists') === false) {
                                            error_log("Roles SQL error: " . $e->getMessage());
                                        }
                                    }
                                }
                            }
                            error_log('Roles and permissions SQL executed successfully after role_permissions table creation');
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error executing roles SQL after role_permissions creation: " . $e->getMessage());
                }
            }
            
            error_log('Table created successfully: ' . $table);
            echo json_encode([
                'success' => true, 
                'table' => $table,
                'debug' => $debugInfo
            ]);
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo ?? [];
            error_log('PDO Error creating table ' . $table . ': ' . $e->getMessage());
            error_log('PDO Error Code: ' . $e->getCode());
            error_log('PDO Error Info: ' . json_encode($errorInfo, JSON_UNESCAPED_UNICODE));
            error_log('SQL: ' . substr($sql, 0, 500));
            
            echo json_encode([
                'success' => false, 
                'message' => 'Ошибка при создании таблицы: ' . $e->getMessage(),
                'pdoCode' => $e->getCode(),
                'pdoErrorInfo' => $errorInfo,
                'table' => $table,
                'sqlPreview' => substr($sql, 0, 300),
                'debug' => $debugInfo
            ]);
        }
    } catch (Exception $e) {
        error_log('Exception creating table: ' . $e->getMessage());
        error_log('File: ' . $e->getFile() . ':' . $e->getLine());
        error_log('Trace: ' . $e->getTraceAsString());
        error_log('Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'debug' => $debugInfo
        ]);
    } catch (Throwable $e) {
        error_log('Throwable creating table: ' . $e->getMessage());
        error_log('File: ' . $e->getFile() . ':' . $e->getLine());
        error_log('Trace: ' . $e->getTraceAsString());
        error_log('Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        
        echo json_encode([
            'success' => false, 
            'message' => 'Критическая ошибка: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'debug' => $debugInfo
        ]);
    }
    exit;
}

/**
 * Загрузка настроек БД из сессии (для использования во время установки)
 */
function loadDatabaseConfigFromSession(): void {
    if (isset($_SESSION['install_db_config']) && is_array($_SESSION['install_db_config'])) {
        $dbConfig = $_SESSION['install_db_config'];
        
        // Определяем константы для подключения к БД
        if (!defined('DB_HOST')) {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            define('DB_HOST', $host . ':' . $port);
        }
        if (!defined('DB_NAME')) define('DB_NAME', $dbConfig['name'] ?? '');
        if (!defined('DB_USER')) define('DB_USER', $dbConfig['user'] ?? 'root');
        if (!defined('DB_PASS')) define('DB_PASS', $dbConfig['pass'] ?? '');
        if (!defined('DB_CHARSET')) define('DB_CHARSET', $dbConfig['charset'] ?? 'utf8mb4');
    }
}

/**
 * Создание файла database.ini из настроек в сессии
 */
function saveDatabaseIniFile(): bool {
    if (!isset($_SESSION['install_db_config']) || !is_array($_SESSION['install_db_config'])) {
        return false;
    }
    
    $dbConfig = $_SESSION['install_db_config'];
    $databaseIniFile = __DIR__ . '/../data/database.ini';
    
    try {
        if (class_exists('Ini')) {
            $ini = new Ini();
            $ini->setSection('database', $dbConfig);
            $ini->save($databaseIniFile);
        } else {
            $content = "[database]\n";
            foreach ($dbConfig as $k => $v) {
                $content .= "{$k} = {$v}\n";
            }
            @file_put_contents($databaseIniFile, $content);
        }
        
        // Очищаем настройки из сессии после успешного сохранения
        unset($_SESSION['install_db_config']);
        
        return file_exists($databaseIniFile);
    } catch (Exception $e) {
        error_log("Error saving database.ini: " . $e->getMessage());
        return false;
    }
}

// Обработка POST запросов
if ($step === 'database' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dbConfig = [
            'host' => $_POST['db_host'] ?? '127.0.0.1',
            'port' => (int)($_POST['db_port'] ?? 3306),
            'name' => $_POST['db_name'] ?? '',
            'user' => $_POST['db_user'] ?? 'root',
            'pass' => $_POST['db_pass'] ?? '',
            'charset' => 'utf8mb4'
        ];
        
        // Сохраняем настройки БД в сессию вместо создания файла
        $_SESSION['install_db_config'] = $dbConfig;
        
        header('Location: /install?step=tables');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Шаг проверки системы (перед настройкой БД)
if ($step === 'system-check') {
    // Проверка системы перед настройкой БД
    // Выполняем проверки без подключения к БД
    $systemChecks = [];
    $systemErrors = [];
    $systemWarnings = [];
    
    // 1. Проверка наличия BaseModule
    if (!class_exists('BaseModule')) {
        $baseModuleFile = __DIR__ . '/../classes/base/BaseModule.php';
        if (file_exists($baseModuleFile)) {
            require_once $baseModuleFile;
            $systemChecks['BaseModule'] = ['status' => 'loaded', 'file' => $baseModuleFile];
        } else {
            $systemErrors[] = 'BaseModule не найден: ' . $baseModuleFile;
            $systemChecks['BaseModule'] = ['status' => 'error', 'error' => 'Файл не найден: ' . $baseModuleFile];
        }
    } else {
        $systemChecks['BaseModule'] = ['status' => 'ok'];
    }
    
    // 2. Проверка наличия InstallerManager
    if (!class_exists('InstallerManager')) {
        $installerFile = __DIR__ . '/../classes/managers/InstallerManager.php';
        if (file_exists($installerFile)) {
            require_once $installerFile;
            $systemChecks['InstallerManager'] = ['status' => 'loaded', 'file' => $installerFile];
        } else {
            $systemErrors[] = 'InstallerManager не найден: ' . $installerFile;
            $systemChecks['InstallerManager'] = ['status' => 'error', 'error' => 'Файл не найден: ' . $installerFile];
        }
    } else {
        $systemChecks['InstallerManager'] = ['status' => 'ok'];
    }
    
    // 3. Проверка наличия DatabaseHelper
    $databaseHelperFile = __DIR__ . '/../classes/helpers/DatabaseHelper.php';
    if (file_exists($databaseHelperFile)) {
        $systemChecks['DatabaseHelper'] = ['status' => 'ok', 'file' => $databaseHelperFile];
    } else {
        $systemErrors[] = 'DatabaseHelper не найден: ' . $databaseHelperFile;
        $systemChecks['DatabaseHelper'] = ['status' => 'error', 'error' => 'Файл не найден: ' . $databaseHelperFile];
    }
    
    // 4. Проверка версии PHP
    $phpVersion = PHP_VERSION;
    $phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
    if ($phpVersionOk) {
        $systemChecks['PHP'] = ['status' => 'ok', 'version' => $phpVersion];
    } else {
        $systemWarnings[] = "PHP версия {$phpVersion} ниже рекомендуемой (7.4.0+)";
        $systemChecks['PHP'] = ['status' => 'warning', 'version' => $phpVersion, 'warning' => 'Рекомендуется версия 7.4.0+'];
    }
    
    // 5. Проверка расширений PHP
    $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            $systemChecks['PHP_Ext_' . $ext] = ['status' => 'ok', 'extension' => $ext];
        } else {
            $systemErrors[] = "Расширение PHP {$ext} не установлено";
            $systemChecks['PHP_Ext_' . $ext] = ['status' => 'error', 'extension' => $ext, 'error' => 'Расширение не установлено'];
        }
    }
    
    // 6. Проверка прав на запись в директорию data
    $dataDir = __DIR__ . '/../data';
    if (is_dir($dataDir)) {
        $isWritable = is_writable($dataDir);
        if ($isWritable) {
            $systemChecks['DataDir'] = ['status' => 'ok', 'path' => $dataDir];
        } else {
            $systemErrors[] = "Директория {$dataDir} недоступна для записи";
            $systemChecks['DataDir'] = ['status' => 'error', 'path' => $dataDir, 'error' => 'Нет прав на запись'];
        }
    } else {
        // Попытка создать директорию
        if (@mkdir($dataDir, 0755, true)) {
            $systemChecks['DataDir'] = ['status' => 'ok', 'path' => $dataDir, 'created' => true];
        } else {
            $systemWarnings[] = "Директория {$dataDir} не существует и не может быть создана";
            $systemChecks['DataDir'] = ['status' => 'warning', 'path' => $dataDir, 'warning' => 'Директория не существует'];
        }
    }
    
    // 7. Проверка доступности директории для кеша
    $cacheDir = __DIR__ . '/../data/cache';
    if (is_dir($cacheDir)) {
        $isWritable = is_writable($cacheDir);
        if ($isWritable) {
            $systemChecks['CacheDir'] = ['status' => 'ok', 'path' => $cacheDir];
        } else {
            $systemWarnings[] = "Директория кеша {$cacheDir} недоступна для записи";
            $systemChecks['CacheDir'] = ['status' => 'warning', 'path' => $cacheDir, 'warning' => 'Нет прав на запись'];
        }
    } else {
        // Попытка создать директорию
        if (@mkdir($cacheDir, 0755, true)) {
            $systemChecks['CacheDir'] = ['status' => 'ok', 'path' => $cacheDir, 'created' => true];
        } else {
            $systemWarnings[] = "Не удалось создать директорию кеша {$cacheDir}";
            $systemChecks['CacheDir'] = ['status' => 'warning', 'path' => $cacheDir, 'warning' => 'Не удалось создать'];
        }
    }
    
    // 8. Проверка метода getTableDefinitions у InstallerManager (после загрузки)
    if (class_exists('InstallerManager')) {
        try {
            $installer = InstallerManager::getInstance();
            if ($installer && method_exists($installer, 'getTableDefinitions')) {
                $tables = $installer->getTableDefinitions();
                $systemChecks['TableDefinitions'] = ['status' => 'ok', 'count' => count($tables)];
            } else {
                $systemErrors[] = 'Метод getTableDefinitions не найден в InstallerManager';
                $systemChecks['TableDefinitions'] = ['status' => 'error', 'error' => 'Метод не существует'];
            }
        } catch (Exception $e) {
            $systemErrors[] = 'Ошибка при проверке InstallerManager: ' . $e->getMessage();
            $systemChecks['TableDefinitions'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    // Логируем проверки
    error_log('Installer System Checks: ' . json_encode($systemChecks, JSON_UNESCAPED_UNICODE));
    if (!empty($systemErrors)) {
        error_log('Installer System Errors: ' . implode('; ', $systemErrors));
    }
}

if ($step === 'user') {
    // Загружаем настройки БД из сессии
    loadDatabaseConfigFromSession();
    
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
                
                // Проверяем, что роли и разрешения созданы (они должны быть созданы после создания таблицы role_permissions)
                $stmt = $db->query("SELECT COUNT(*) FROM roles");
                $rolesCount = (int)$stmt->fetchColumn();
                
                if ($rolesCount === 0) {
                    // Если ролей нет, создаем их (на случай если SQL не выполнился)
                    ensureRolesAndPermissions($db);
                }
                
                // Создаем пользователя
                $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int)$db->lastInsertId();
                
                // Назначаем роль разработчика первому пользователю
                try {
                    // Получаем ID роли developer
                    $stmt = $db->prepare("SELECT id FROM roles WHERE slug = 'developer' LIMIT 1");
                    $stmt->execute();
                    $role = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($role) {
                        $roleId = (int)$role['id'];
                        // Назначаем роль разработчика
                        // Назначаем роль пользователю (role_ids в users)
                        $stmt = $db->prepare("UPDATE users SET role_ids = ? WHERE id = ?");
                        $stmt->execute([json_encode([$roleId]), $userId]);
                    } else {
                        // Если роль не найдена, это критическая ошибка
                        error_log("Critical: Role 'developer' not found. Creating it manually...");
                        // Создаем роль developer вручную
                        $stmt = $db->prepare("INSERT INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, ?)");
                        $stmt->execute(['Разработчик', 'developer', 'Полный доступ ко всем функциям системы. Роль создается только при установке движка и не может быть удалена.', 1]);
                        $roleId = (int)$db->lastInsertId();
                        
                        // Назначаем все разрешения роли developer
                        $stmt = $db->query("SELECT id FROM permissions");
                        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($permissions as $permissionId) {
                            $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                            $stmt->execute([$roleId, $permissionId]);
                        }
                        
                        // Назначаем роль пользователю
                        // Назначаем роль пользователю (role_ids в users)
                        $stmt = $db->prepare("UPDATE users SET role_ids = ? WHERE id = ?");
                        $stmt->execute([json_encode([$roleId]), $userId]);
                    }
                } catch (Exception $e) {
                    error_log("Error assigning developer role: " . $e->getMessage());
                    // Не прерываем установку, но логируем ошибку
                }
                
                // Установка завершена - создаем файл database.ini только сейчас
                if (saveDatabaseIniFile()) {
                    header('Location: /admin/login');
                    exit;
                } else {
                    $error = 'Помилка при збереженні конфігурації бази даних. Перевірте права доступу до директорії engine/data/';
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

/**
 * Убеждаемся, что роли и разрешения созданы
 */
function ensureRolesAndPermissions(PDO $db): void {
    try {
        // Проверяем, есть ли уже роли
        $stmt = $db->query("SELECT COUNT(*) FROM roles");
        $rolesCount = (int)$stmt->fetchColumn();
        
        if ($rolesCount === 0) {
            // Создаем базовые роли (только системные: Guest, user, developer)
            $roles = [
                ['Разработчик', 'developer', 'Полный доступ ко всем функциям системы. Роль создается только при установке движка и не может быть удалена.', 1],
                ['Пользователь', 'user', 'Обычный пользователь с базовыми правами', 1],
                ['Гость', 'guest', 'Базовая роль для неавторизованных пользователей', 1]
            ];
            
            foreach ($roles as $role) {
                $stmt = $db->prepare("INSERT IGNORE INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, ?)");
                $stmt->execute($role);
            }
        }
        
        // Проверяем, есть ли уже разрешения
        $stmt = $db->query("SELECT COUNT(*) FROM permissions");
        $permissionsCount = (int)$stmt->fetchColumn();
        
        if ($permissionsCount === 0) {
            // Создаем базовые разрешения
            $permissions = [
                // Админка
                ['Доступ к админ-панели', 'admin.access', 'Доступ к административной панели', 'admin'],
                ['Управление плагинами', 'admin.plugins', 'Установка, активация и удаление плагинов', 'admin'],
                ['Управление темами', 'admin.themes', 'Установка и активация тем', 'admin'],
                ['Управление настройками', 'admin.settings', 'Изменение системных настроек', 'admin'],
                ['Просмотр логов', 'admin.logs.view', 'Просмотр системных логов', 'admin'],
                ['Управление пользователями', 'admin.users', 'Создание, редактирование и удаление пользователей', 'admin'],
                ['Управление ролями', 'admin.roles', 'Управление ролями и правами доступа', 'admin'],
            ];
            
            foreach ($permissions as $permission) {
                $stmt = $db->prepare("INSERT IGNORE INTO permissions (name, slug, description, category) VALUES (?, ?, ?, ?)");
                $stmt->execute($permission);
            }
            
            // Назначаем все разрешения роли developer
            $stmt = $db->prepare("SELECT id FROM roles WHERE slug = 'developer' LIMIT 1");
            $stmt->execute();
            $developerRole = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($developerRole) {
                $roleId = (int)$developerRole['id'];
                $stmt = $db->query("SELECT id FROM permissions");
                $permissionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($permissionIds as $permissionId) {
                    $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                    $stmt->execute([$roleId, $permissionId]);
                }
            }
            
            // Назначаем базовые разрешения роли user
            $stmt = $db->prepare("SELECT id FROM roles WHERE slug = 'user' LIMIT 1");
            $stmt->execute();
            $userRole = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Разрешения для роли user удалены (разрешения кабинета - это плагин)
        }
    } catch (Exception $e) {
        error_log("Error ensuring roles and permissions: " . $e->getMessage());
    }
}

// Загружаем настройки БД из сессии для шагов tables и user
if (($step === 'tables' || $step === 'user') && isset($_SESSION['install_db_config'])) {
    loadDatabaseConfigFromSession();
}

// Передаем результаты проверок системы в шаблон
$systemChecks = $systemChecks ?? [];
$systemErrors = $systemErrors ?? [];

// Подключаем единый шаблон установщика
$template = __DIR__ . '/../templates/installer.php';
if (file_exists($template)) {
    include $template;
} else {
    echo '<h1>Flowaxy CMS Installation</h1><p>Installer template not found</p>';
    if (!empty($systemErrors)) {
        echo '<h2>Ошибки системы:</h2><ul>';
        foreach ($systemErrors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
    }
    if (!empty($systemChecks)) {
        echo '<h2>Проверки системы:</h2><pre>' . print_r($systemChecks, true) . '</pre>';
    }
}
exit;

