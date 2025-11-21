<?php
/**
 * Обработчик установщика
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

// Инициализация сессии для установщика (важно для Linux)
if (session_status() === PHP_SESSION_NONE) {
    // Убеждаемся, что директория для сессий существует и доступна для записи
    $sessionSavePath = session_save_path();
    if (empty($sessionSavePath) || !is_writable($sessionSavePath)) {
        // Пытаемся использовать директорию storage/sessions
        $customSessionPath = __DIR__ . '/../../storage/sessions';
        if (!is_dir($customSessionPath)) {
            @mkdir($customSessionPath, 0755, true);
        }
        if (is_dir($customSessionPath) && is_writable($customSessionPath)) {
            session_save_path($customSessionPath);
            error_log('Installer: Using custom session path: ' . $customSessionPath);
        } else {
            error_log('Installer: WARNING - Custom session path not writable: ' . $customSessionPath);
        }
    }
    
    // Инициализируем сессию
    if (!headers_sent()) {
        session_start();
        error_log('Installer: Session started. ID: ' . session_id() . ', Path: ' . session_save_path());
    } else {
        error_log('Installer: WARNING - Headers already sent, cannot start session');
    }
} else {
    error_log('Installer: Session already active. ID: ' . session_id() . ', Status: ' . session_status());
}

// Получаем переменные из запроса
$step = $_GET['step'] ?? 'welcome';
$action = $_GET['action'] ?? '';
$databaseIniFile = __DIR__ . '/../data/database.ini';

// Блокировка доступа к установщику, если система уже установлена
// Исключение: AJAX запросы для тестирования БД (action=test_db, create_table)
// Это нужно для проверки подключения к БД во время установки
$isAjaxAction = ($action === 'test_db' || $action === 'create_table') && $_SERVER['REQUEST_METHOD'] === 'POST';

// Проверяем, идет ли процесс установки (есть настройки БД в сессии)
if (function_exists('sessionManager')) {
    $session = sessionManager('installer');
    $isInstallationInProgress = $session->has('db_config') && is_array($session->get('db_config'));
} else {
    // Fallback на прямой доступ к сессии для проверки
    $isInstallationInProgress = isset($_SESSION['install_db_config']) && is_array($_SESSION['install_db_config']);
}

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
        $version = $_POST['db_version'] ?? '8.4';
        
        // Настройки подключения в зависимости от версии MySQL
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ];
        
        $selectedCharset = $_POST['db_charset'] ?? 'utf8mb4';
        
        // Для MySQL 5.7 используем старый способ подключения
        if ($version === '5.7') {
            $dsn = "mysql:host={$host};port={$port};charset={$selectedCharset}";
        } else {
            // Для MySQL 8.4 используем новый способ
            $dsn = "mysql:host={$host};port={$port};charset={$selectedCharset}";
        }
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Проверяем версию MySQL
        $versionStmt = $pdo->query("SELECT VERSION()");
        $mysqlVersion = $versionStmt->fetchColumn();
        
        // Проверяем кодировку базы данных
        $charsetInfo = null;
        $dbCharset = null;
        $dbCollation = null;
        try {
            if (!empty($name)) {
                $charsetStmt = $pdo->prepare("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
                $charsetStmt->execute([$name]);
                $charsetInfo = $charsetStmt->fetch(PDO::FETCH_ASSOC);
                if ($charsetInfo) {
                    $dbCharset = $charsetInfo['DEFAULT_CHARACTER_SET_NAME'] ?? null;
                    $dbCollation = $charsetInfo['DEFAULT_COLLATION_NAME'] ?? null;
                }
            }
        } catch (Exception $e) {
            // Игнорируем ошибку проверки кодировки
        }
        
        // Определяем мажорную версию MySQL
        $versionParts = explode('.', $mysqlVersion);
        $majorVersion = (int)($versionParts[0] ?? 0);
        $minorVersion = (int)($versionParts[1] ?? 0);
        
        // Определяем, какая версия установлена (для селекта)
        $detectedVersion = '8.4';
        if ($majorVersion === 5) {
            $detectedVersion = '5.7'; // Для всех версий 5.x (5.5, 5.6, 5.7) считаем как 5.7
        } elseif ($majorVersion >= 8) {
            $detectedVersion = '8.4'; // Для всех версий 8.x (8.0, 8.1, 8.2, 8.3, 8.4) считаем как 8.4
        }
        
        // Проверяем соответствие выбранной версии реальной
        $versionMatch = ($version === $detectedVersion);
        $versionWarning = '';
        if (!$versionMatch) {
            // Определяем язык для сообщения (по умолчанию украинский)
            $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'uk';
            $isUkrainian = strpos($lang, 'uk') !== false || strpos($lang, 'ru') !== false;
            
            if ($isUkrainian) {
                $versionWarning = "Увага: Вибрана версія MySQL {$version}, але на сервері встановлена версія {$mysqlVersion} (визначено як {$detectedVersion}). Версія автоматично оновлена.";
            } else {
                $versionWarning = "Warning: Selected MySQL version {$version}, but server has version {$mysqlVersion} (detected as {$detectedVersion}). Version automatically updated.";
            }
        }
        
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$name]);
        $databaseExists = $stmt->fetch() !== false;
        
        $selectedCharset = $_POST['db_charset'] ?? 'utf8mb4';
        $charsetWarning = '';
        $charsetError = '';
        $charsetMatch = true;
        $connectionSuccess = true;
        
        // Если база данных существует, но кодировка не была получена, пытаемся получить еще раз
        if ($databaseExists && !$dbCharset) {
            try {
                $charsetStmt = $pdo->prepare("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
                $charsetStmt->execute([$name]);
                $charsetInfo = $charsetStmt->fetch(PDO::FETCH_ASSOC);
                if ($charsetInfo) {
                    $dbCharset = $charsetInfo['DEFAULT_CHARACTER_SET_NAME'] ?? null;
                    $dbCollation = $charsetInfo['DEFAULT_COLLATION_NAME'] ?? null;
                }
            } catch (Exception $e) {
                // Игнорируем ошибку
            }
        }
        
        // Если база данных не существует, получаем кодировку сервера по умолчанию
        if (!$databaseExists && !$dbCharset) {
            try {
                $defaultCharsetStmt = $pdo->query("SELECT @@character_set_server, @@collation_server");
                $defaultCharset = $defaultCharsetStmt->fetch(PDO::FETCH_NUM);
                if ($defaultCharset) {
                    $dbCharset = $defaultCharset[0] ?? null;
                    $dbCollation = $defaultCharset[1] ?? null;
                }
            } catch (Exception $e) {
                // Игнорируем ошибку
            }
        }
        
        // Проверяем соответствие выбранной кодировки кодировке БД или сервера
        if ($dbCharset) {
            // Нормализуем кодировки для сравнения - извлекаем базовую кодировку
            // Например: utf8mb4_0900_ai_ci -> utf8mb4, utf8mb4_unicode_ci -> utf8mb4
            $normalizedDbCharset = strtolower(preg_replace('/[^a-z0-9]/', '', explode('_', $dbCharset)[0]));
            $normalizedSelectedCharset = strtolower($selectedCharset);
            
            // Проверяем совпадение базовых кодировок
            if ($normalizedDbCharset !== $normalizedSelectedCharset) {
                $charsetMatch = false;
                $connectionSuccess = false; // Блокируем продолжение при несовпадении
                $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'uk';
                $isUkrainian = strpos($lang, 'uk') !== false || strpos($lang, 'ru') !== false;
                
                if ($databaseExists) {
                    // База существует - это ошибка
                    if ($isUkrainian) {
                        $charsetError = "Помилка: Вибрана кодування {$selectedCharset}, але база даних має кодування {$dbCharset} (collation: {$dbCollation}). Кодування повинні співпадати! Змініть кодування бази даних або виберіть правильну кодування.";
                    } else {
                        $charsetError = "Error: Selected charset {$selectedCharset}, but database has charset {$dbCharset} (collation: {$dbCollation}). Charsets must match! Change database charset or select correct charset.";
                    }
                } else {
                    // База не существует - предупреждение о кодировке сервера
                    if ($isUkrainian) {
                        $charsetWarning = "Увага: Сервер MySQL має кодування за замовчуванням {$dbCharset} (collation: {$dbCollation}). При створенні бази даних буде використано кодування сервера. Рекомендується створити базу з кодуванням utf8mb4: CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
                    } else {
                        $charsetWarning = "Warning: MySQL server default charset is {$dbCharset} (collation: {$dbCollation}). Database will be created with server charset. Recommended to create database with utf8mb4: CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
                    }
                    // Для несуществующей базы не блокируем, только предупреждаем
                    $connectionSuccess = true;
                }
            } else {
                // Кодировки совпадают, но могут отличаться collation - показываем информацию
                if ($dbCollation && strpos(strtolower($dbCollation), strtolower($selectedCharset)) === false) {
                    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'uk';
                    $isUkrainian = strpos($lang, 'uk') !== false || strpos($lang, 'ru') !== false;
                    
                    if ($isUkrainian) {
                        $charsetWarning = "Кодування співпадає ({$dbCharset}), але collation відрізняється: {$dbCollation}. Рекомендується utf8mb4_unicode_ci.";
                    } else {
                        $charsetWarning = "Charset matches ({$dbCharset}), but collation differs: {$dbCollation}. utf8mb4_unicode_ci is recommended.";
                    }
                }
            }
        } elseif (!$dbCharset && $selectedCharset !== 'utf8mb4') {
            $charsetWarning = "Рекомендується використовувати utf8mb4 для підтримки emoji та всіх Unicode символів.";
        }
        
        echo json_encode([
            'success' => $connectionSuccess, 
            'database_exists' => $databaseExists,
            'mysql_version' => $mysqlVersion,
            'detected_version' => $detectedVersion,
            'selected_version' => $version,
            'version_match' => $versionMatch,
            'version_warning' => $versionWarning,
            'db_charset' => $dbCharset,
            'db_collation' => $dbCollation,
            'selected_charset' => $selectedCharset,
            'charset_match' => $charsetMatch,
            'charset_warning' => $charsetWarning,
            'charset_error' => $charsetError,
            'message' => $charsetError ?: ($charsetWarning ?: 'Підключення успішне!')
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// AJAX: создание таблицы
if ($action === 'create_table' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Очищаем буфер вывода перед отправкой JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Устанавливаем заголовки
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    // Отключаем вывод ошибок на экран (но логируем их)
    $oldErrorReporting = error_reporting(E_ALL);
    $oldDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', '0');
    
    // Регистрируем обработчик ошибок для гарантии JSON ответа
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Если была фатальная ошибка, отправляем JSON с ошибкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode([
                'success' => false,
                'message' => 'Критическая ошибка PHP: ' . $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    });
    
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
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'Ошибка проверки системы: ' . implode('; ', $checkErrors),
                'errors' => $checkErrors,
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Загрузка конфигурации БД из сессии
        loadDatabaseConfigFromSession();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $table = $data['table'] ?? '';
        
        $debugInfo['table'] = $table;
        $debugInfo['sessionStatus'] = session_status();
        $debugInfo['sessionId'] = session_id();
        $debugInfo['sessionKeys'] = array_keys($_SESSION ?? []);
        $storedConfig = getInstallerDbConfig(false);
        $debugInfo['dbConfigFound'] = is_array($storedConfig);
        if (is_array($storedConfig)) {
            $debugInfo['dbConfigKeys'] = array_keys($storedConfig);
        }
        $debugInfo['dbHost'] = defined('DB_HOST') ? DB_HOST : 'not defined';
        $debugInfo['dbName'] = defined('DB_NAME') ? DB_NAME : 'not defined';
        $debugInfo['dbUser'] = defined('DB_USER') ? DB_USER : 'not defined';
        $debugInfo['dbPass'] = defined('DB_PASS') ? (empty(DB_PASS) ? 'empty' : '***') : 'not defined';
        
        // Проверка наличия конфигурации БД
        $databaseIniFile = __DIR__ . '/../data/database.ini';
        $debugInfo['databaseIniFile'] = $databaseIniFile;
        $debugInfo['databaseIniExists'] = file_exists($databaseIniFile);
        $debugInfo['databaseIniReadable'] = file_exists($databaseIniFile) ? is_readable($databaseIniFile) : false;
        
        // Загружаем конфигурацию БД
        $dbConfig = getInstallerDbConfig();
        $debugInfo['dbConfigRetrieved'] = is_array($dbConfig);
        
        if (is_array($dbConfig) && !empty($dbConfig)) {
            $debugInfo['dbConfigKeys'] = array_keys($dbConfig);
            $debugInfo['dbConfigHost'] = $dbConfig['host'] ?? 'not set';
            $debugInfo['dbConfigName'] = $dbConfig['name'] ?? 'not set';
            $debugInfo['dbConfigHasPass'] = isset($dbConfig['pass']) && $dbConfig['pass'] !== '';
            
            // Получаем значения из конфигурации
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            $name = $dbConfig['name'] ?? '';
            $user = $dbConfig['user'] ?? 'root';
            $pass = $dbConfig['pass'] ?? '';
            $charset = $dbConfig['charset'] ?? 'utf8mb4';
            
            // Упрощенная логика: используем ТОЛЬКО GLOBALS для инсталлера
            $GLOBALS['_INSTALLER_DB_HOST'] = $host . ':' . $port;
            $GLOBALS['_INSTALLER_DB_NAME'] = $name;
            $GLOBALS['_INSTALLER_DB_USER'] = $user;
            $GLOBALS['_INSTALLER_DB_PASS'] = $pass;
            $GLOBALS['_INSTALLER_DB_CHARSET'] = $charset;
            
            error_log('GLOBALS set for DB config: HOST=' . $GLOBALS['_INSTALLER_DB_HOST'] . ', NAME=' . $GLOBALS['_INSTALLER_DB_NAME'] . ', USER=' . $GLOBALS['_INSTALLER_DB_USER'] . ', PASS=' . (strlen($pass) > 0 ? '***' : 'empty'));
        } else {
            $debugInfo['dbConfigError'] = 'Failed to retrieve dbConfig from any source';
        }
        
        // Проверяем наличие валидной конфигурации (из GLOBALS)
        $dbHost = $GLOBALS['_INSTALLER_DB_HOST'] ?? '';
        $dbName = $GLOBALS['_INSTALLER_DB_NAME'] ?? '';
        $hasValidConfig = !empty($dbHost) && !empty($dbName);
        
        if (!$hasValidConfig) {
            error_log('Database configuration not loaded. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'Конфигурация базы данных не загружена. Проверьте настройки подключения на предыдущем шаге.',
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Обновляем debug info с финальными значениями
        $debugInfo['finalDbHost'] = defined('DB_HOST') ? DB_HOST : 'not defined';
        $debugInfo['finalDbName'] = defined('DB_NAME') ? DB_NAME : 'not defined';
        $debugInfo['finalDbUser'] = defined('DB_USER') ? DB_USER : 'not defined';
        $debugInfo['finalDbCharset'] = defined('DB_CHARSET') ? DB_CHARSET : 'not defined';
        
        // Проверка наличия DatabaseHelper
        if (!class_exists('DatabaseHelper')) {
            $databaseHelperFile = __DIR__ . '/../classes/helpers/DatabaseHelper.php';
            if (file_exists($databaseHelperFile)) {
                require_once $databaseHelperFile;
            } else {
                error_log('DatabaseHelper not found: ' . $databaseHelperFile);
                
                // Убеждаемся, что буфер чист перед отправкой
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                $response = [
                    'success' => false, 
                    'message' => 'DatabaseHelper не найден: ' . $databaseHelperFile,
                    'debug' => $debugInfo
                ];
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        if (!class_exists('InstallerManager')) {
            error_log('InstallerManager class not found after loading. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'InstallerManager not available after loading',
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $installer = InstallerManager::getInstance();
        if (!$installer) {
            error_log('Failed to get InstallerManager instance. Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'Failed to get InstallerManager instance',
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Получаем кодировку из конфигурации
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
        $collation = 'utf8mb4_unicode_ci';
        if ($charset === 'utf8') {
            $collation = 'utf8_unicode_ci';
        } elseif ($charset === 'latin1') {
            $collation = 'latin1_swedish_ci';
        }
        
        // Пробуем получить из конфигурации установщика
        $dbConfig = getInstallerDbConfig();
        if (is_array($dbConfig) && isset($dbConfig['charset'])) {
            $charset = $dbConfig['charset'];
            // Определяем collation на основе charset
            if ($charset === 'utf8mb4') {
                $collation = 'utf8mb4_unicode_ci';
            } elseif ($charset === 'utf8') {
                $collation = 'utf8_unicode_ci';
            } elseif ($charset === 'latin1') {
                $collation = 'latin1_swedish_ci';
            }
        }
        
        $tables = $installer->getTableDefinitions($charset, $collation);
        $debugInfo['tablesCount'] = count($tables);
        $debugInfo['availableTables'] = array_keys($tables);
        
        if (!isset($tables[$table])) {
            error_log('Table not found: ' . $table . '. Available: ' . implode(', ', array_keys($tables)));
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'Table not found: ' . $table, 
                'available' => array_keys($tables),
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Попытка подключения к БД
        try {
            $conn = DatabaseHelper::getConnection(false); // Не показываем страницу ошибки
            if (!$conn) {
                $lastError = error_get_last();
                
                // Упрощенная логика: используем ТОЛЬКО GLOBALS для инсталлера
                $dbHost = $GLOBALS['_INSTALLER_DB_HOST'] ?? '127.0.0.1';
                $dbName = $GLOBALS['_INSTALLER_DB_NAME'] ?? '';
                $dbUser = $GLOBALS['_INSTALLER_DB_USER'] ?? 'root';
                $dbPass = $GLOBALS['_INSTALLER_DB_PASS'] ?? '';
                $dbCharset = $GLOBALS['_INSTALLER_DB_CHARSET'] ?? 'utf8mb4';
                
                // Логируем для диагностики (без пароля)
                error_log('Direct connection attempt - Host: ' . $dbHost . ', Name: ' . $dbName . ', User: ' . $dbUser . ', Pass: ' . (strlen($dbPass) > 0 ? '***' : 'empty') . ', Charset: ' . $dbCharset);
                
                // Проверяем, что у нас есть минимальная конфигурация
                if (empty($dbHost) || empty($dbName)) {
                    // Убеждаемся, что буфер чист перед отправкой
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    
                    $response = [
                        'success' => false,
                        'message' => 'Конфигурация базы данных не загружена. Проверьте настройки подключения на предыдущем шаге.',
                        'debug' => array_merge($debugInfo, [
                            'dbHost' => $dbHost,
                            'dbName' => $dbName,
                            'dbUser' => $dbUser,
                            'dbCharset' => $dbCharset,
                            'globals' => [
                                'host' => $GLOBALS['_INSTALLER_DB_HOST'] ?? 'not set',
                                'name' => $GLOBALS['_INSTALLER_DB_NAME'] ?? 'not set',
                                'user' => $GLOBALS['_INSTALLER_DB_USER'] ?? 'not set',
                                'charset' => $GLOBALS['_INSTALLER_DB_CHARSET'] ?? 'not set'
                            ]
                        ])
                    ];
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // Попытка прямого подключения для диагностики
                try {
                    $hostParts = explode(':', $dbHost);
                    $host = $hostParts[0] ?? '127.0.0.1';
                    $port = isset($hostParts[1]) ? (int)$hostParts[1] : 3306;
                    
                    $testConn = new PDO(
                        "mysql:host={$host};port={$port};charset={$dbCharset}",
                        $dbUser,
                        $dbPass,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5
                        ]
                    );
                    
                    // Проверка существования базы данных
                    $stmt = $testConn->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
                    $stmt->execute([$dbName]);
                    $dbExists = $stmt->fetch() !== false;
                    
                    $debugInfo['directConnection'] = 'success';
                    $debugInfo['databaseExists'] = $dbExists;
                    
                    if (!$dbExists) {
                        error_log('Database does not exist: ' . $dbName);
                        
                        // Убеждаемся, что буфер чист перед отправкой
                        while (ob_get_level() > 0) {
                            ob_end_clean();
                        }
                        
                        $response = [
                            'success' => false, 
                            'message' => 'База данных "' . $dbName . '" не существует. Создайте её в панели управления хостингом.',
                            'debug' => $debugInfo,
                            'lastError' => $lastError
                        ];
                        
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    
                    // Если база существует, пробуем подключиться к ней
                    $testConn = new PDO(
                        "mysql:host={$host};port={$port};dbname={$dbName};charset={$dbCharset}",
                        $dbUser,
                        $dbPass,
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
                    
                    // Убеждаемся, что буфер чист перед отправкой
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    
                    $response = [
                        'success' => false, 
                        'message' => 'Ошибка подключения к базе данных: ' . $e->getMessage(),
                        'debug' => $debugInfo,
                        'lastError' => $lastError,
                        'pdoCode' => $e->getCode(),
                        'pdoMessage' => $e->getMessage()
                    ];
                    
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Exception during database connection: ' . $e->getMessage());
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'Исключение при подключении к БД: ' . $e->getMessage(),
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $sql = $tables[$table];
        $debugInfo['sqlLength'] = strlen($sql);
        $debugInfo['sqlPreview'] = substr($sql, 0, 200) . '...';
        
        // Проверяем, существует ли таблица
        $tableExists = false;
        try {
            $dbName = $GLOBALS['_INSTALLER_DB_NAME'] ?? '';
            $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $stmt->execute([$dbName, $table]);
            $tableExists = $stmt->fetchColumn() > 0;
            $debugInfo['tableExists'] = $tableExists;
        } catch (Exception $e) {
            error_log('Error checking table existence: ' . $e->getMessage());
            // Продолжаем выполнение, если проверка не удалась
        }
        
        // Если таблица уже существует, возвращаем специальный статус
        if ($tableExists) {
            error_log('Table already exists: ' . $table);
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => true,
                'exists' => true,
                'table' => $table,
                'message' => 'Таблиця вже існує в базі даних',
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
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
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => true, 
                'table' => $table,
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo ?? [];
            error_log('PDO Error creating table ' . $table . ': ' . $e->getMessage());
            error_log('PDO Error Code: ' . $e->getCode());
            error_log('PDO Error Info: ' . json_encode($errorInfo, JSON_UNESCAPED_UNICODE));
            error_log('SQL: ' . substr($sql, 0, 500));
            
            // Убеждаемся, что буфер чист перед отправкой
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $response = [
                'success' => false, 
                'message' => 'Ошибка при создании таблицы: ' . $e->getMessage(),
                'pdoCode' => $e->getCode(),
                'pdoErrorInfo' => $errorInfo,
                'table' => $table,
                'sqlPreview' => substr($sql, 0, 300),
                'debug' => $debugInfo
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        error_log('Exception creating table: ' . $e->getMessage());
        error_log('File: ' . $e->getFile() . ':' . $e->getLine());
        error_log('Trace: ' . $e->getTraceAsString());
        error_log('Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        
        // Убеждаемся, что буфер чист перед отправкой
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        $response = [
            'success' => false, 
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'debug' => $debugInfo
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        error_log('Throwable creating table: ' . $e->getMessage());
        error_log('File: ' . $e->getFile() . ':' . $e->getLine());
        error_log('Trace: ' . $e->getTraceAsString());
        error_log('Debug: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        
        // Убеждаемся, что буфер чист перед отправкой
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        $response = [
            'success' => false, 
            'message' => 'Критическая ошибка: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'debug' => $debugInfo
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Восстанавливаем настройки
    error_reporting($oldErrorReporting);
    ini_set('display_errors', $oldDisplayErrors);
    
    exit;
}

/**
 * Загрузка настроек БД из сессии (для использования во время установки)
 */
function loadDatabaseConfigFromSession(): void {
    // Убеждаемся, что сессия инициализирована
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
    
    $dbConfig = getInstallerDbConfig();
    
    if (is_array($dbConfig) && !empty($dbConfig)) {
        // Упрощенная логика: используем ТОЛЬКО GLOBALS для инсталлера
        // Не пытаемся определять константы, так как они могут быть уже определены как пустые
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $name = $dbConfig['name'] ?? '';
        $user = $dbConfig['user'] ?? 'root';
        $pass = $dbConfig['pass'] ?? '';
        $charset = $dbConfig['charset'] ?? 'utf8mb4';
        
        // Устанавливаем GLOBALS - это единственный источник конфигурации для инсталлера
        $GLOBALS['_INSTALLER_DB_HOST'] = $host . ':' . $port;
        $GLOBALS['_INSTALLER_DB_NAME'] = $name;
        $GLOBALS['_INSTALLER_DB_USER'] = $user;
        $GLOBALS['_INSTALLER_DB_PASS'] = $pass;
        $GLOBALS['_INSTALLER_DB_CHARSET'] = $charset;
        
        error_log('loadDatabaseConfigFromSession: GLOBALS set - HOST=' . $GLOBALS['_INSTALLER_DB_HOST'] . ', NAME=' . $GLOBALS['_INSTALLER_DB_NAME'] . ', USER=' . $GLOBALS['_INSTALLER_DB_USER'] . ', PASS=' . (strlen($pass) > 0 ? '***' : 'empty'));
    } else {
        // Логируем для отладки
        $sessionKeys = (isset($_SESSION) && is_array($_SESSION)) ? array_keys($_SESSION) : [];
        error_log('loadDatabaseConfigFromSession: dbConfig is empty. Session keys: ' . implode(', ', $sessionKeys));
    }
}

/**
 * Получение настроек БД из доступных источников (сессия, cookies, database.ini)
 */
function getInstallerDbConfig(bool $useSession = true): ?array {
    $dbConfig = null;
    
    // 1. Пробуем загрузить из database.ini (если он уже создан)
    $databaseIniFile = __DIR__ . '/../data/database.ini';
    if (file_exists($databaseIniFile) && is_readable($databaseIniFile)) {
        try {
            // Загружаем класс Ini, если он еще не загружен
            if (!class_exists('Ini')) {
                $iniFile = __DIR__ . '/../classes/data/Ini.php';
                if (file_exists($iniFile)) {
                    require_once $iniFile;
                }
            }
            
            if (class_exists('Ini')) {
                $ini = new Ini($databaseIniFile);
                $dbSection = $ini->getSection('database');
                if (is_array($dbSection) && !empty($dbSection)) {
                    // Парсим host:port если нужно
                    $host = $dbSection['host'] ?? '127.0.0.1';
                    $port = 3306;
                    if (strpos($host, ':') !== false) {
                        list($host, $port) = explode(':', $host, 2);
                        $port = (int)$port;
                    } else {
                        $port = (int)($dbSection['port'] ?? 3306);
                    }
                    
                    $dbConfig = [
                        'host' => $host,
                        'port' => $port,
                        'name' => $dbSection['name'] ?? '',
                        'user' => $dbSection['user'] ?? 'root',
                        'pass' => $dbSection['pass'] ?? '',
                        'charset' => $dbSection['charset'] ?? 'utf8mb4'
                    ];
                    // Если нашли в ini, возвращаем сразу
                    if (!empty($dbConfig['host']) && !empty($dbConfig['name'])) {
                        return $dbConfig;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error reading database.ini: ' . $e->getMessage());
        }
    }
    
    // 2. Пробуем загрузить из сессии (проверяем все возможные ключи)
    if ($useSession) {
        // Приоритет 1: Прямой доступ к сессии (без префикса)
        if (isset($_SESSION['install_db_config']) && is_array($_SESSION['install_db_config'])) {
            $dbConfig = $_SESSION['install_db_config'];
            error_log('getInstallerDbConfig: Found in $_SESSION[install_db_config]');
        } elseif (isset($_SESSION['db_config']) && is_array($_SESSION['db_config'])) {
            $dbConfig = $_SESSION['db_config'];
            error_log('getInstallerDbConfig: Found in $_SESSION[db_config]');
        } elseif (isset($_SESSION['installer.db_config']) && is_array($_SESSION['installer.db_config'])) {
            $dbConfig = $_SESSION['installer.db_config'];
            error_log('getInstallerDbConfig: Found in $_SESSION[installer.db_config]');
        }
        
        // Приоритет 2: Через SessionManager с префиксом
        if ((!is_array($dbConfig) || empty($dbConfig)) && function_exists('sessionManager')) {
            try {
                $session = sessionManager('installer');
                $dbConfig = $session->get('db_config');
                if (is_array($dbConfig) && !empty($dbConfig)) {
                    error_log('getInstallerDbConfig: Found via sessionManager(installer)');
                }
            } catch (Exception $e) {
                error_log('Error getting session via sessionManager: ' . $e->getMessage());
            }
        }
        
        // Приоритет 3: Поиск в любом месте сессии
        if ((!is_array($dbConfig) || empty($dbConfig)) && isset($_SESSION) && is_array($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                if (is_array($value) && isset($value['host']) && isset($value['name'])) {
                    $dbConfig = $value;
                    error_log('getInstallerDbConfig: Found in $_SESSION[' . $key . ']');
                    break;
                }
            }
        }
    }
    
    // 3. Fallback на cookie (если сессия не работает)
    if ((!is_array($dbConfig) || empty($dbConfig)) && isset($_COOKIE['installer_db_config'])) {
        try {
            $cookieData = json_decode($_COOKIE['installer_db_config'], true);
            if (is_array($cookieData) && !empty($cookieData['host']) && !empty($cookieData['name'])) {
                $dbConfig = $cookieData;
                $hasPassword = isset($cookieData['pass']) && $cookieData['pass'] !== '';
                error_log('getInstallerDbConfig: Found in cookie' . ($hasPassword ? ' (with password)' : ' (password may be empty)'));
            }
        } catch (Exception $e) {
            error_log('Error reading cookie: ' . $e->getMessage());
        }
    }
    
    if (is_array($dbConfig) && !empty($dbConfig)) {
        error_log('getInstallerDbConfig: Successfully loaded config with keys: ' . implode(', ', array_keys($dbConfig)));
    } else {
        error_log('getInstallerDbConfig: No config found in any source');
        error_log('getInstallerDbConfig: Session keys: ' . implode(', ', array_keys($_SESSION ?? [])));
        error_log('getInstallerDbConfig: Cookie keys: ' . implode(', ', array_keys($_COOKIE ?? [])));
    }
    
    return is_array($dbConfig) && !empty($dbConfig) ? $dbConfig : null;
}

/**
 * Сохранение настроек БД в сессию и cookies (fallback)
 */
function storeInstallerDbConfig(array $dbConfig): void {
    // Убеждаемся, что сессия активна
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
    
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        $_SESSION = [];
    }
    
    // Сохраняем напрямую в сессию (приоритет) - БЕЗ префикса
    $_SESSION['install_db_config'] = $dbConfig;
    $_SESSION['db_config'] = $dbConfig;
    
    // Также сохраняем с префиксом installer для SessionManager
    $_SESSION['installer.db_config'] = $dbConfig;
    
    // Также сохраняем через SessionManager, если доступен
    if (function_exists('sessionManager')) {
        try {
            $session = sessionManager('installer');
            $session->set('db_config', $dbConfig);
        } catch (Exception $e) {
            error_log('Error saving to sessionManager: ' . $e->getMessage());
        }
    }
    
    // Сохраняем в cookie как fallback (включая пароль, так как это нужно для установки)
    // В production это не используется, только во время установки
    $cookieData = [
        'host' => $dbConfig['host'] ?? '',
        'port' => $dbConfig['port'] ?? 3306,
        'name' => $dbConfig['name'] ?? '',
        'user' => $dbConfig['user'] ?? '',
        'pass' => $dbConfig['pass'] ?? '', // Сохраняем пароль для установки
        'version' => $dbConfig['version'] ?? '8.4',
        'charset' => $dbConfig['charset'] ?? 'utf8mb4'
    ];
    setcookie('installer_db_config', json_encode($cookieData), time() + 3600, '/', '', false, true);
    
    // Логируем для отладки
    error_log('storeInstallerDbConfig: Config saved. Session ID: ' . session_id());
    error_log('storeInstallerDbConfig: Session save path: ' . session_save_path());
    error_log('storeInstallerDbConfig: Config keys: ' . implode(', ', array_keys($dbConfig)));
    error_log('storeInstallerDbConfig: Session keys after save: ' . implode(', ', array_keys($_SESSION ?? [])));
}

/**
 * Очистка всех данных установщика из сессии и cookies
 */
function clearInstallerDbConfig(): void {
    // Очищаем сессию установщика
    if (function_exists('sessionManager')) {
        try {
            $session = sessionManager('installer');
            $session->remove('db_config');
            $session->clear(); // Очищаем всю сессию установщика
        } catch (Exception $e) {
            error_log('Error clearing sessionManager: ' . $e->getMessage());
        }
    }
    
    // Очищаем все ключи установщика из $_SESSION
    $installerKeys = ['install_db_config', 'db_config', 'installer_step', 'installer_data'];
    foreach ($installerKeys as $key) {
        unset($_SESSION[$key]);
    }
    
    // Очищаем cookies установщика
    $cookieKeys = ['installer_db_config', 'installer_step'];
    foreach ($cookieKeys as $key) {
        if (isset($_COOKIE[$key])) {
            setcookie($key, '', time() - 3600, '/');
            unset($_COOKIE[$key]);
        }
    }
    
    // Очищаем GLOBALS установщика
    $globalsKeys = ['_INSTALLER_DB_HOST', '_INSTALLER_DB_NAME', '_INSTALLER_DB_USER', '_INSTALLER_DB_PASS', '_INSTALLER_DB_CHARSET'];
    foreach ($globalsKeys as $key) {
        unset($GLOBALS[$key]);
    }
    
    // Уничтожаем сессию установщика полностью (используем наши классы)
    if (Session::isStarted()) {
        // Очищаем все данные сессии через наш класс
        Session::clear();
        
        // Уничтожаем сессию через наш класс (он сам обработает cookie)
        Session::destroy();
    }
    
    error_log('Installer session and data cleared');
}

/**
 * Создание файла database.ini из настроек в сессии
 */
function saveDatabaseIniFile(): bool {
    $dbConfig = getInstallerDbConfig();
    
    if (!is_array($dbConfig) || empty($dbConfig)) {
        return false;
    }
    
    $databaseIniFile = __DIR__ . '/../data/database.ini';
    
    try {
        // Используем класс Ini из engine/classes
        if (class_exists('Ini')) {
            $ini = new Ini();
            $ini->setSection('database', $dbConfig);
            $ini->save($databaseIniFile);
        } else {
            // Fallback на ручное создание
            $content = "[database]\n";
            foreach ($dbConfig as $k => $v) {
                // Экранируем значения, если они содержат специальные символы
                if (is_string($v) && (strpos($v, ' ') !== false || strpos($v, '=') !== false)) {
                    $v = '"' . addslashes($v) . '"';
                }
                $content .= "{$k} = {$v}\n";
            }
            @file_put_contents($databaseIniFile, $content);
        }
        
        // Перенаправляем на админку после успешного сохранения
        if (file_exists($databaseIniFile)) {
            // Очищаем временные данные после успешного сохранения
            clearInstallerDbConfig();
            
            // Редирект на админку (clearInstallerDbConfig уже уничтожил сессию)
            header('Location: /admin/login');
            exit;
        }
        
        return false;
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
            'version' => $_POST['db_version'] ?? '8.4',
            'charset' => $_POST['db_charset'] ?? 'utf8mb4'
        ];
        
        // Убеждаемся, что сессия инициализирована
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            }
        }
        
        // Сохраняем конфигурацию в сессию и cookies
        storeInstallerDbConfig($dbConfig);
        
        // Логируем для отладки
        error_log('=== DATABASE CONFIG SAVE ===');
        error_log('Session ID: ' . session_id());
        error_log('Session status: ' . session_status());
        error_log('Session save path: ' . session_save_path());
        error_log('Session keys after save: ' . implode(', ', array_keys($_SESSION ?? [])));
        
        // Проверяем, что данные действительно сохранены
        $savedConfig = $_SESSION['install_db_config'] ?? $_SESSION['db_config'] ?? $_SESSION['installer.db_config'] ?? null;
        if (is_array($savedConfig)) {
            error_log('Config verified in session. Keys: ' . implode(', ', array_keys($savedConfig)));
        } else {
            error_log('WARNING: Config NOT found in session after save!');
        }
        
        // Явно сохраняем сессию перед редиректом
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            error_log('Session written and closed');
        }
        
        error_log('=== END DATABASE CONFIG SAVE ===');
        
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
    
    // Описания компонентов для пользователей
    $componentDescriptions = [
        'BaseModule' => 'Базовий клас модулів системи',
        'InstallerManager' => 'Менеджер установки системи',
        'DatabaseHelper' => 'Допоміжний клас для роботи з базою даних',
        'PHP' => 'Мова програмування PHP',
        'PHP_Ext_pdo' => 'Розширення PDO для роботи з базами даних',
        'PHP_Ext_pdo_mysql' => 'Драйвер PDO для MySQL',
        'PHP_Ext_mbstring' => 'Розширення для роботи з багатобайтовими рядками',
        'PHP_Ext_json' => 'Розширення для роботи з JSON',
        'PHP_Ext_openssl' => 'Розширення для шифрування та безпеки',
        'DataDir' => 'Директорія для зберігання конфігурації',
        'CacheDir' => 'Директорія для зберігання кешу',
        'SessionsDir' => 'Директорія для зберігання сесій',
        'LogsDir' => 'Директорія для зберігання логів',
        'UploadsDir' => 'Директорія для завантажених файлів',
        'PluginsDir' => 'Директорія для плагінів',
        'ThemesDir' => 'Директорія для тем оформлення',
        'TempDir' => 'Тимчасова директорія для завантаження файлів',
        'SessionSupport' => 'Підтримка сесій PHP',
        'JsonSupport' => 'Підтримка роботи з JSON',
        'FileFunctions' => 'Функції для роботи з файлами',
        'TableDefinitions' => 'Визначення таблиць бази даних'
    ];
    
    // Функция для получения относительного пути
    function getRelativePath($absolutePath) {
        $rootDir = dirname(__DIR__, 2); // Корень проекта
        if (strpos($absolutePath, $rootDir) === 0) {
            return str_replace($rootDir, '', $absolutePath);
        }
        return $absolutePath;
    }
    
    // Функция для проверки прав на запись с реальной попыткой записи и создания .htaccess
    function checkDirectoryWritable($dir, $name, $createHtaccess = false): array {
        $relativePath = getRelativePath($dir);
        $result = ['status' => 'ok', 'path' => $dir, 'display_path' => $relativePath];
        
        // Проверяем существование директории
        if (!is_dir($dir)) {
            // Пытаемся создать
            if (@mkdir($dir, 0755, true)) {
                $result['status'] = 'ok';
                $result['created'] = true;
            } else {
                $result['status'] = 'error';
                $result['error'] = "Директория не существует и не может быть создана";
                return $result;
            }
        }
        
        // Создаем .htaccess для cache и logs
        if ($createHtaccess) {
            $htaccessFile = $dir . '/.htaccess';
            if (!file_exists($htaccessFile)) {
                @file_put_contents($htaccessFile, "Deny from all\n");
            }
        }
        
        // Проверяем права на чтение
        if (!is_readable($dir)) {
            $result['status'] = 'error';
            $result['error'] = "Нет прав на чтение";
            return $result;
        }
        
        // Реальная проверка записи - пытаемся создать тестовый файл
        $testFile = $dir . '/.test_write_' . time() . '.tmp';
        $testWrite = @file_put_contents($testFile, 'test');
        if ($testWrite === false) {
            $result['status'] = 'error';
            $result['error'] = "Нет прав на запись (не удалось создать тестовый файл)";
            return $result;
        }
        
        // Удаляем тестовый файл
        @unlink($testFile);
        
        // Проверяем права на удаление
        if (file_exists($testFile)) {
            $result['status'] = 'warning';
            $result['warning'] = "Не удалось удалить тестовый файл (возможны проблемы с правами)";
        }
        
        return $result;
    }
    
    // 1. Проверка наличия BaseModule
    if (!class_exists('BaseModule')) {
        $baseModuleFile = __DIR__ . '/../classes/base/BaseModule.php';
        if (file_exists($baseModuleFile)) {
            require_once $baseModuleFile;
            if (class_exists('BaseModule')) {
                $systemChecks['BaseModule'] = [
                    'status' => 'ok', 
                    'file' => $baseModuleFile,
                    'description' => $componentDescriptions['BaseModule'] ?? '',
                    'display_path' => getRelativePath($baseModuleFile)
                ];
            } else {
                $systemErrors[] = 'BaseModule не загрузился после require_once';
                $systemChecks['BaseModule'] = [
                    'status' => 'error', 
                    'error' => 'Класс не загрузился после require_once',
                    'description' => $componentDescriptions['BaseModule'] ?? ''
                ];
            }
        } else {
            $systemErrors[] = 'BaseModule не найден: ' . $baseModuleFile;
            $systemChecks['BaseModule'] = [
                'status' => 'error', 
                'error' => 'Файл не найден',
                'description' => $componentDescriptions['BaseModule'] ?? ''
            ];
        }
    } else {
        $systemChecks['BaseModule'] = [
            'status' => 'ok',
            'description' => $componentDescriptions['BaseModule'] ?? ''
        ];
    }
    
    // 2. Проверка наличия InstallerManager
    if (!class_exists('InstallerManager')) {
        $installerFile = __DIR__ . '/../classes/managers/InstallerManager.php';
        if (file_exists($installerFile)) {
            require_once $installerFile;
            if (class_exists('InstallerManager')) {
                $systemChecks['InstallerManager'] = [
                    'status' => 'ok', 
                    'file' => $installerFile,
                    'description' => $componentDescriptions['InstallerManager'] ?? '',
                    'display_path' => getRelativePath($installerFile)
                ];
            } else {
                $systemErrors[] = 'InstallerManager не загрузился после require_once';
                $systemChecks['InstallerManager'] = [
                    'status' => 'error', 
                    'error' => 'Класс не загрузился после require_once',
                    'description' => $componentDescriptions['InstallerManager'] ?? ''
                ];
            }
        } else {
            $systemErrors[] = 'InstallerManager не найден: ' . $installerFile;
            $systemChecks['InstallerManager'] = [
                'status' => 'error', 
                'error' => 'Файл не найден',
                'description' => $componentDescriptions['InstallerManager'] ?? ''
            ];
        }
    } else {
        $systemChecks['InstallerManager'] = [
            'status' => 'ok',
            'description' => $componentDescriptions['InstallerManager'] ?? ''
        ];
    }
    
    // 3. Проверка наличия DatabaseHelper
    $databaseHelperFile = __DIR__ . '/../classes/helpers/DatabaseHelper.php';
    if (file_exists($databaseHelperFile)) {
        if (!class_exists('DatabaseHelper')) {
            require_once $databaseHelperFile;
        }
        if (class_exists('DatabaseHelper')) {
            $systemChecks['DatabaseHelper'] = [
                'status' => 'ok', 
                'file' => $databaseHelperFile,
                'description' => $componentDescriptions['DatabaseHelper'] ?? '',
                'display_path' => getRelativePath($databaseHelperFile)
            ];
        } else {
            $systemErrors[] = 'DatabaseHelper не загрузился после require_once';
            $systemChecks['DatabaseHelper'] = [
                'status' => 'error', 
                'error' => 'Класс не загрузился',
                'description' => $componentDescriptions['DatabaseHelper'] ?? ''
            ];
        }
    } else {
        $systemErrors[] = 'DatabaseHelper не найден: ' . $databaseHelperFile;
        $systemChecks['DatabaseHelper'] = [
            'status' => 'error', 
            'error' => 'Файл не найден',
            'description' => $componentDescriptions['DatabaseHelper'] ?? ''
        ];
    }
    
    // 4. Проверка версии PHP
    $phpVersion = PHP_VERSION;
    $phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
    if ($phpVersionOk) {
        $systemChecks['PHP'] = [
            'status' => 'ok', 
            'version' => $phpVersion,
            'description' => $componentDescriptions['PHP'] ?? ''
        ];
    } else {
        $systemWarnings[] = "PHP версия {$phpVersion} ниже рекомендуемой (7.4.0+)";
        $systemChecks['PHP'] = [
            'status' => 'warning', 
            'version' => $phpVersion, 
            'warning' => 'Рекомендуется версия 7.4.0+',
            'description' => $componentDescriptions['PHP'] ?? ''
        ];
    }
    
    // 5. Проверка расширений PHP
    $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
    foreach ($requiredExtensions as $ext) {
        $extKey = 'PHP_Ext_' . $ext;
        if (extension_loaded($ext)) {
            $systemChecks[$extKey] = [
                'status' => 'ok', 
                'extension' => $ext,
                'description' => $componentDescriptions[$extKey] ?? ''
            ];
        } else {
            $systemErrors[] = "Расширение PHP {$ext} не установлено";
            $systemChecks[$extKey] = [
                'status' => 'error', 
                'extension' => $ext, 
                'error' => 'Расширение не установлено',
                'description' => $componentDescriptions[$extKey] ?? ''
            ];
        }
    }
    
    // 6. Проверка прав на запись в директорию data
    $dataDir = __DIR__ . '/../data';
    $dataCheck = checkDirectoryWritable($dataDir, 'DataDir');
    $dataCheck['description'] = $componentDescriptions['DataDir'] ?? '';
    $systemChecks['DataDir'] = $dataCheck;
    if ($dataCheck['status'] === 'error') {
        $systemErrors[] = "Директория data недоступна для записи: " . ($dataCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 7. Проверка доступности директории для кеша (создаем с .htaccess)
    $cacheDir = __DIR__ . '/../../storage/cache';
    $cacheCheck = checkDirectoryWritable($cacheDir, 'CacheDir', true);
    $cacheCheck['description'] = $componentDescriptions['CacheDir'] ?? '';
    $systemChecks['CacheDir'] = $cacheCheck;
    if ($cacheCheck['status'] === 'error') {
        $systemErrors[] = "Директория кеша недоступна для записи: " . ($cacheCheck['error'] ?? 'Неизвестная ошибка');
    } elseif ($cacheCheck['status'] === 'warning') {
        $systemWarnings[] = "Директория кеша: " . ($cacheCheck['warning'] ?? 'Предупреждение');
    }
    
    // 8. Проверка директории storage/sessions (создаем автоматически с .htaccess)
    $sessionsDir = __DIR__ . '/../../storage/sessions';
    $sessionsCheck = checkDirectoryWritable($sessionsDir, 'SessionsDir', true);
    $sessionsCheck['description'] = $componentDescriptions['SessionsDir'] ?? '';
    $systemChecks['SessionsDir'] = $sessionsCheck;
    if ($sessionsCheck['status'] === 'error') {
        $systemErrors[] = "Директория sessions недоступна для записи: " . ($sessionsCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 9. Проверка директории storage/logs (создаем с .htaccess)
    $logsDir = __DIR__ . '/../../storage/logs';
    $logsCheck = checkDirectoryWritable($logsDir, 'LogsDir', true);
    $logsCheck['description'] = $componentDescriptions['LogsDir'] ?? '';
    $systemChecks['LogsDir'] = $logsCheck;
    if ($logsCheck['status'] === 'error') {
        $systemErrors[] = "Директория logs недоступна для записи: " . ($logsCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 10. Проверка директории uploads (создаем автоматически)
    $uploadsDir = __DIR__ . '/../../uploads';
    $uploadsCheck = checkDirectoryWritable($uploadsDir, 'UploadsDir', false);
    $uploadsCheck['description'] = $componentDescriptions['UploadsDir'] ?? '';
    $systemChecks['UploadsDir'] = $uploadsCheck;
    if ($uploadsCheck['status'] === 'error') {
        $systemErrors[] = "Директория uploads недоступна для записи: " . ($uploadsCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 11. Проверка директории plugins (создаем автоматически)
    $pluginsDir = __DIR__ . '/../../plugins';
    $pluginsCheck = checkDirectoryWritable($pluginsDir, 'PluginsDir', false);
    $pluginsCheck['description'] = $componentDescriptions['PluginsDir'] ?? '';
    $systemChecks['PluginsDir'] = $pluginsCheck;
    if ($pluginsCheck['status'] === 'error') {
        $systemErrors[] = "Директория plugins недоступна для записи: " . ($pluginsCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 12. Проверка директории themes (создаем автоматически)
    $themesDir = __DIR__ . '/../../themes';
    $themesCheck = checkDirectoryWritable($themesDir, 'ThemesDir', false);
    $themesCheck['description'] = $componentDescriptions['ThemesDir'] ?? '';
    $systemChecks['ThemesDir'] = $themesCheck;
    if ($themesCheck['status'] === 'error') {
        $systemErrors[] = "Директория themes недоступна для записи: " . ($themesCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 13. Проверка директории storage/temp (создаем автоматически с .htaccess)
    $tempDir = __DIR__ . '/../../storage/temp';
    $tempCheck = checkDirectoryWritable($tempDir, 'TempDir', true);
    $tempCheck['description'] = $componentDescriptions['TempDir'] ?? '';
    $systemChecks['TempDir'] = $tempCheck;
    if ($tempCheck['status'] === 'error') {
        $systemErrors[] = "Директория temp недоступна для записи: " . ($tempCheck['error'] ?? 'Неизвестная ошибка');
    }
    
    // 14. Проверка доступности функции session_start
    if (function_exists('session_start')) {
        $systemChecks['SessionSupport'] = [
            'status' => 'ok',
            'description' => $componentDescriptions['SessionSupport'] ?? ''
        ];
    } else {
        $systemErrors[] = "Функция session_start недоступна";
        $systemChecks['SessionSupport'] = [
            'status' => 'error', 
            'error' => 'Функция session_start недоступна',
            'description' => $componentDescriptions['SessionSupport'] ?? ''
        ];
    }
    
    // 14. Проверка доступности функции json_encode/json_decode
    if (function_exists('json_encode') && function_exists('json_decode')) {
        $systemChecks['JsonSupport'] = [
            'status' => 'ok',
            'description' => $componentDescriptions['JsonSupport'] ?? ''
        ];
    } else {
        $systemErrors[] = "Функции JSON недоступны";
        $systemChecks['JsonSupport'] = [
            'status' => 'error', 
            'error' => 'Функции JSON недоступны',
            'description' => $componentDescriptions['JsonSupport'] ?? ''
        ];
    }
    
    // 15. Проверка доступности функции file_get_contents/file_put_contents
    if (function_exists('file_get_contents') && function_exists('file_put_contents')) {
        $systemChecks['FileFunctions'] = [
            'status' => 'ok',
            'description' => $componentDescriptions['FileFunctions'] ?? ''
        ];
    } else {
        $systemErrors[] = "Функции работы с файлами недоступны";
        $systemChecks['FileFunctions'] = [
            'status' => 'error', 
            'error' => 'Функции работы с файлами недоступны',
            'description' => $componentDescriptions['FileFunctions'] ?? ''
        ];
    }
    
    // 16. Проверка метода getTableDefinitions у InstallerManager (после загрузки)
    if (class_exists('InstallerManager')) {
        try {
            $installer = InstallerManager::getInstance();
            if ($installer && method_exists($installer, 'getTableDefinitions')) {
                // Получаем кодировку из конфигурации
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
        $collation = 'utf8mb4_unicode_ci';
        if ($charset === 'utf8') {
            $collation = 'utf8_unicode_ci';
        } elseif ($charset === 'latin1') {
            $collation = 'latin1_swedish_ci';
        }
        
        // Пробуем получить из конфигурации установщика
        $dbConfig = getInstallerDbConfig();
        if (is_array($dbConfig) && isset($dbConfig['charset'])) {
            $charset = $dbConfig['charset'];
            // Определяем collation на основе charset
            if ($charset === 'utf8mb4') {
                $collation = 'utf8mb4_unicode_ci';
            } elseif ($charset === 'utf8') {
                $collation = 'utf8_unicode_ci';
            } elseif ($charset === 'latin1') {
                $collation = 'latin1_swedish_ci';
            }
        }
        
        $tables = $installer->getTableDefinitions($charset, $collation);
                $tablesCount = count($tables);
                if ($tablesCount > 0) {
                    $systemChecks['TableDefinitions'] = [
                        'status' => 'ok', 
                        'count' => $tablesCount, 
                        'info' => "Доступно таблиць: {$tablesCount}",
                        'description' => $componentDescriptions['TableDefinitions'] ?? ''
                    ];
                } else {
                    $systemErrors[] = 'Не удалось получить определения таблиц';
                    $systemChecks['TableDefinitions'] = [
                        'status' => 'error', 
                        'error' => 'Не удалось получить определения таблиц',
                        'description' => $componentDescriptions['TableDefinitions'] ?? ''
                    ];
                }
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
                // saveDatabaseIniFile() уже делает редирект на /admin/login и очистку сессий
                if (!saveDatabaseIniFile()) {
                    $error = 'Помилка при збереженні конфігурації бази даних. Перевірте права доступу до директорії engine/data/';
                }
                // Если saveDatabaseIniFile() успешно, редирект уже выполнен
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
if ($step === 'tables' || $step === 'user') {
    // Убеждаемся, что сессия инициализирована
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
    
    // Логируем для отладки
    error_log('=== LOADING DB CONFIG FOR STEP: ' . $step . ' ===');
    error_log('Session ID: ' . session_id());
    error_log('Session status: ' . session_status());
    error_log('Session save path: ' . session_save_path());
    error_log('Session keys: ' . implode(', ', array_keys($_SESSION ?? [])));
    error_log('Cookie keys: ' . implode(', ', array_keys($_COOKIE ?? [])));
    
    // Пробуем загрузить конфигурацию
    $dbConfig = getInstallerDbConfig(true);
    
    if (is_array($dbConfig) && !empty($dbConfig)) {
        // Загружаем конфигурацию в константы
        loadDatabaseConfigFromSession();
        error_log('DB config loaded successfully for step: ' . $step);
        error_log('DB_HOST: ' . (defined('DB_HOST') ? DB_HOST : 'not defined'));
        error_log('DB_NAME: ' . (defined('DB_NAME') ? DB_NAME : 'not defined'));
    } else {
        error_log('ERROR: No db_config found in any source for step: ' . $step);
        error_log('Session dump: ' . json_encode($_SESSION ?? [], JSON_UNESCAPED_UNICODE));
        error_log('Cookie dump: ' . json_encode($_COOKIE ?? [], JSON_UNESCAPED_UNICODE));
    }
    
    error_log('=== END LOADING DB CONFIG ===');
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

