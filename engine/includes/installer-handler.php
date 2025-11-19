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
    try {
        loadDatabaseConfig(true);
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
        
        DatabaseHelper::getConnection()->exec($tables[$table]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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

if ($step === 'tables') {
    loadDatabaseConfig(true);
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

// Подключаем единый шаблон установщика
$template = __DIR__ . '/../templates/installer.php';
if (file_exists($template)) {
    include $template;
} else {
    echo '<h1>Flowaxy CMS Installation</h1><p>Installer template not found</p>';
}
exit;

