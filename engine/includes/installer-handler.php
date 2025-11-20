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
        
        // Загрузка конфигурации БД
        loadDatabaseConfig(true);
        
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
        
        $databaseIniFile = __DIR__ . '/../data/database.ini';
        
        if (class_exists('Ini')) {
            $ini = new Ini();
            $ini->setSection('database', $dbConfig);
            $ini->save($databaseIniFile);
        } else {
            $content = "[database]\n";
            foreach ($dbConfig as $k => $v) $content .= "{$k} = {$v}\n";
            @file_put_contents($databaseIniFile, $content);
        }
        
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
    loadDatabaseConfig(true);
    
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
                $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                
                // Установка завершена - database.ini уже создан на шаге database
                header('Location: /admin/login');
                exit;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
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

