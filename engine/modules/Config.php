<?php
/**
 * Класс для работы с конфигурациями сайта
 * Управление конфигурационными файлами в директории engine/data
 * 
 * @package Engine\Classes
 * @version 1.0.0
 */

declare(strict_types=1);

class Config {
    private static ?self $instance = null;
    private string $configDir;
    private array $loadedConfigs = [];
    private const DEFAULT_EXTENSION = '.json';
    
    /**
     * Конструктор (приватный для Singleton)
     * 
     * @param string|null $configDir Директория для конфигураций
     */
    private function __construct(?string $configDir = null) {
        $this->configDir = $configDir ?? dirname(__DIR__) . '/data/';
        $this->configDir = rtrim($this->configDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->ensureConfigDir();
    }
    
    /**
     * Получение экземпляра класса (Singleton)
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Создание директории конфигураций
     * 
     * @return void
     */
    private function ensureConfigDir(): void {
        if (!is_dir($this->configDir)) {
            @mkdir($this->configDir, 0755, true);
        }
        
        // Создаем .htaccess для защиты
        $htaccessFile = $this->configDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Deny from all\n");
        }
        
        // Создаем .gitkeep для git
        $gitkeepFile = $this->configDir . '.gitkeep';
        if (!file_exists($gitkeepFile)) {
            @file_put_contents($gitkeepFile, '');
        }
    }
    
    /**
     * Получение значения конфигурации
     * 
     * @param string $key Ключ конфигурации (может быть в формате "config.key" или "config.section.key")
     * @param mixed $default Значение по умолчанию
     * @param string|null $extension Расширение файла (.json или .ini)
     * @return mixed
     */
    public function get(string $key, $default = null, ?string $extension = null) {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        
        // Разбираем ключ
        if (strpos($key, '.') === false) {
            // Если ключ без точки, значит это имя конфига
            $configName = $key;
            $path = null;
        } else {
            $parts = explode('.', $key, 2);
            $configName = $parts[0];
            $path = $parts[1] ?? null;
        }
        
        // Загружаем конфигурацию, если её нет в памяти
        if (!isset($this->loadedConfigs[$configName])) {
            $this->loadConfig($configName, $extension);
        }
        
        $config = $this->loadedConfigs[$configName];
        
        if ($path === null) {
            // Возвращаем весь конфиг
            return $config;
        }
        
        // Получаем значение по пути
        return $this->getValueByPath($config, $path, $default);
    }
    
    /**
     * Установка значения конфигурации
     * 
     * @param string $key Ключ конфигурации
     * @param mixed $value Значение
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function set(string $key, $value, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        
        // Разбираем ключ
        if (strpos($key, '.') === false) {
            throw new Exception("Для установки значения требуется указать путь (например, 'config.key')");
        }
        
        $parts = explode('.', $key, 2);
        $configName = $parts[0];
        $path = $parts[1];
        
        // Загружаем конфигурацию, если её нет в памяти
        if (!isset($this->loadedConfigs[$configName])) {
            $this->loadConfig($configName, $extension);
        }
        
        // Устанавливаем значение по пути
        $this->setValueByPath($this->loadedConfigs[$configName], $path, $value);
        
        // Сохраняем конфигурацию
        return $this->saveConfig($configName, $extension);
    }
    
    /**
     * Проверка наличия ключа в конфигурации
     * 
     * @param string $key Ключ конфигурации
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function has(string $key, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        
        // Разбираем ключ
        if (strpos($key, '.') === false) {
            $configName = $key;
            $path = null;
        } else {
            $parts = explode('.', $key, 2);
            $configName = $parts[0];
            $path = $parts[1] ?? null;
        }
        
        // Загружаем конфигурацию, если её нет в памяти
        if (!isset($this->loadedConfigs[$configName])) {
            $this->loadConfig($configName, $extension);
        }
        
        if ($path === null) {
            return isset($this->loadedConfigs[$configName]);
        }
        
        $config = $this->loadedConfigs[$configName];
        return $this->hasValueByPath($config, $path);
    }
    
    /**
     * Удаление значения из конфигурации
     * 
     * @param string $key Ключ конфигурации
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function remove(string $key, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        
        if (strpos($key, '.') === false) {
            throw new Exception("Для удаления значения требуется указать путь (например, 'config.key')");
        }
        
        $parts = explode('.', $key, 2);
        $configName = $parts[0];
        $path = $parts[1];
        
        // Загружаем конфигурацию, если её нет в памяти
        if (!isset($this->loadedConfigs[$configName])) {
            $this->loadConfig($configName, $extension);
        }
        
        // Удаляем значение по пути
        $this->removeValueByPath($this->loadedConfigs[$configName], $path);
        
        // Сохраняем конфигурацию
        return $this->saveConfig($configName, $extension);
    }
    
    /**
     * Загрузка конфигурации
     * 
     * @param string $configName Имя конфигурации (без расширения)
     * @param string|null $extension Расширение файла
     * @return void
     */
    private function loadConfig(string $configName, ?string $extension = null): void {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        if ($extension === '.json') {
            if (file_exists($filePath)) {
                try {
                    $json = new Json($filePath);
                    $json->load(true);
                    $this->loadedConfigs[$configName] = $json->get([]);
                } catch (Exception $e) {
                    error_log("Config load error for '{$configName}': " . $e->getMessage());
                    $this->loadedConfigs[$configName] = [];
                }
            } else {
                $this->loadedConfigs[$configName] = [];
            }
        } elseif ($extension === '.ini') {
            if (file_exists($filePath)) {
                try {
                    $ini = new Ini($filePath);
                    $ini->load();
                    $this->loadedConfigs[$configName] = $ini->all();
                } catch (Exception $e) {
                    error_log("Config load error for '{$configName}': " . $e->getMessage());
                    $this->loadedConfigs[$configName] = [];
                }
            } else {
                $this->loadedConfigs[$configName] = [];
            }
        } else {
            throw new Exception("Неподдерживаемое расширение файла: {$extension}");
        }
    }
    
    /**
     * Сохранение конфигурации
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return bool
     */
    private function saveConfig(string $configName, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        if (!isset($this->loadedConfigs[$configName])) {
            return false;
        }
        
        $data = $this->loadedConfigs[$configName];
        
        if ($extension === '.json') {
            try {
                return Json::write($filePath, $data);
            } catch (Exception $e) {
                error_log("Config save error for '{$configName}': " . $e->getMessage());
                return false;
            }
        } elseif ($extension === '.ini') {
            try {
                $ini = new Ini($filePath);
                $ini->setFile($filePath);
                foreach ($data as $section => $values) {
                    if (is_array($values)) {
                        $ini->setSection($section, $values);
                    } else {
                        $ini->set($section, $values);
                    }
                }
                return $ini->save();
            } catch (Exception $e) {
                error_log("Config save error for '{$configName}': " . $e->getMessage());
                return false;
            }
        } else {
            throw new Exception("Неподдерживаемое расширение файла: {$extension}");
        }
    }
    
    /**
     * Получение пути к файлу конфигурации
     * 
     * @param string $configName Имя конфигурации
     * @param string $extension Расширение файла
     * @return string
     */
    private function getConfigPath(string $configName, string $extension): string {
        // Безопасность: убираем недопустимые символы из имени конфига
        $configName = preg_replace('/[^a-zA-Z0-9_-]/', '', $configName);
        
        return $this->configDir . $configName . $extension;
    }
    
    /**
     * Получение значения по пути (точечная нотация)
     * 
     * @param array $data Данные
     * @param string $path Путь
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    private function getValueByPath($data, string $path, $default = null) {
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value)) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return $default;
                }
            } elseif (is_object($value)) {
                if (isset($value->$key)) {
                    $value = $value->$key;
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Установка значения по пути (точечная нотация)
     * 
     * @param array $data Данные (передается по ссылке)
     * @param string $path Путь
     * @param mixed $value Значение
     * @return void
     */
    private function setValueByPath(&$data, string $path, $value): void {
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $target = &$data;
        
        foreach ($keys as $key) {
            if (is_array($target)) {
                if (!isset($target[$key]) || !is_array($target[$key])) {
                    $target[$key] = [];
                }
                $target = &$target[$key];
            } elseif (is_object($target)) {
                if (!isset($target->$key) || !is_array($target->$key)) {
                    $target->$key = [];
                }
                $target = &$target->$key;
            } else {
                $target = (array)$target;
                if (!isset($target[$key])) {
                    $target[$key] = [];
                }
                $target = &$target[$key];
            }
        }
        
        if (is_array($target)) {
            $target[$lastKey] = $value;
        } elseif (is_object($target)) {
            $target->$lastKey = $value;
        } else {
            $target = [$lastKey => $value];
        }
    }
    
    /**
     * Проверка наличия значения по пути
     * 
     * @param mixed $data Данные
     * @param string $path Путь
     * @return bool
     */
    private function hasValueByPath($data, string $path): bool {
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value)) {
                if (!isset($value[$key])) {
                    return false;
                }
                $value = $value[$key];
            } elseif (is_object($value)) {
                if (!isset($value->$key)) {
                    return false;
                }
                $value = $value->$key;
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Удаление значения по пути
     * 
     * @param mixed $data Данные (передается по ссылке)
     * @param string $path Путь
     * @return void
     */
    private function removeValueByPath(&$data, string $path): void {
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $target = &$data;
        
        foreach ($keys as $key) {
            if (is_array($target)) {
                if (!isset($target[$key])) {
                    return;
                }
                $target = &$target[$key];
            } elseif (is_object($target)) {
                if (!isset($target->$key)) {
                    return;
                }
                $target = &$target->$key;
            } else {
                return;
            }
        }
        
        if (is_array($target)) {
            unset($target[$lastKey]);
        } elseif (is_object($target)) {
            unset($target->$lastKey);
        }
    }
    
    /**
     * Получение всех данных конфигурации
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return array
     */
    public function all(string $configName, ?string $extension = null): array {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        
        if (!isset($this->loadedConfigs[$configName])) {
            $this->loadConfig($configName, $extension);
        }
        
        return $this->loadedConfigs[$configName] ?? [];
    }
    
    /**
     * Установка всех данных конфигурации
     * 
     * @param string $configName Имя конфигурации
     * @param array $data Данные
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function setAll(string $configName, array $data, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $this->loadedConfigs[$configName] = $data;
        return $this->saveConfig($configName, $extension);
    }
    
    /**
     * Очистка конфигурации из памяти
     * 
     * @param string|null $configName Имя конфигурации (если null, очищаются все)
     * @return self
     */
    public function clear(?string $configName = null): self {
        if ($configName === null) {
            $this->loadedConfigs = [];
        } else {
            unset($this->loadedConfigs[$configName]);
        }
        
        return $this;
    }
    
    /**
     * Перезагрузка конфигурации
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function reload(string $configName, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        unset($this->loadedConfigs[$configName]);
        $this->loadConfig($configName, $extension);
        return isset($this->loadedConfigs[$configName]);
    }
    
    /**
     * Получение пути к директории конфигураций
     * 
     * @return string
     */
    public function getConfigDir(): string {
        return $this->configDir;
    }
    
    /**
     * Получение списка всех конфигурационных файлов
     * 
     * @param string|null $extension Расширение файла (если null, все расширения)
     * @return array
     */
    public function getConfigFiles(?string $extension = null): array {
        $files = [];
        
        if (!is_dir($this->configDir)) {
            return $files;
        }
        
        $pattern = $this->configDir . '*' . ($extension ?? '*');
        $foundFiles = glob($pattern);
        
        if ($foundFiles === false) {
            return $files;
        }
        
        foreach ($foundFiles as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                // Пропускаем служебные файлы
                if (in_array($filename, ['.htaccess', '.gitkeep'], true)) {
                    continue;
                }
                $files[] = $filename;
            }
        }
        
        return $files;
    }
    
    /**
     * Экспорт конфигурации в JSON
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return string JSON строка
     * @throws Exception Если конфигурация не найдена
     */
    public function export(string $configName, ?string $extension = null): string {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $data = $this->all($configName, $extension);
        return Json::stringify($data);
    }
    
    /**
     * Импорт конфигурации из JSON
     * 
     * @param string $configName Имя конфигурации
     * @param string $jsonData JSON строка
     * @param string|null $extension Расширение файла
     * @return bool
     * @throws Exception Если не удалось импортировать
     */
    public function import(string $configName, string $jsonData, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        
        try {
            $data = Json::decode($jsonData);
            return $this->setAll($configName, $data, $extension);
        } catch (Exception $e) {
            error_log("Config import error for '{$configName}': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Копирование конфигурации
     * 
     * @param string $sourceConfig Исходная конфигурация
     * @param string $targetConfig Целевая конфигурация
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function copy(string $sourceConfig, string $targetConfig, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $data = $this->all($sourceConfig, $extension);
        return $this->setAll($targetConfig, $data, $extension);
    }
    
    /**
     * Удаление конфигурационного файла
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function deleteFile(string $configName, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        // Удаляем из памяти
        unset($this->loadedConfigs[$configName]);
        
        // Удаляем файл
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Проверка существования конфигурационного файла
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function fileExists(string $configName, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        return file_exists($filePath);
    }
    
    /**
     * Получение размера конфигурационного файла
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return int Размер файла в байтах, или -1 если файл не существует
     */
    public function getFileSize(string $configName, ?string $extension = null): int {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        if (file_exists($filePath)) {
            return filesize($filePath);
        }
        
        return -1;
    }
    
    /**
     * Получение времени последнего изменения конфигурационного файла
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $extension Расширение файла
     * @return int|false Unix timestamp или false если файл не существует
     */
    public function getFileMTime(string $configName, ?string $extension = null) {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        if (file_exists($filePath)) {
            return filemtime($filePath);
        }
        
        return false;
    }
    
    /**
     * Объединение двух конфигураций
     * 
     * @param string $configName1 Первая конфигурация
     * @param string $configName2 Вторая конфигурация
     * @param string $targetConfig Результирующая конфигурация
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function merge(string $configName1, string $configName2, string $targetConfig, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $data1 = $this->all($configName1, $extension);
        $data2 = $this->all($configName2, $extension);
        $merged = array_merge_recursive($data1, $data2);
        return $this->setAll($targetConfig, $merged, $extension);
    }
    
    /**
     * Резервное копирование конфигурации
     * 
     * @param string $configName Имя конфигурации
     * @param string|null $backupPath Путь для резервной копии (если null, создается автоматически)
     * @param string|null $extension Расширение файла
     * @return bool|string Путь к резервной копии или false при ошибке
     */
    public function backup(string $configName, ?string $backupPath = null, ?string $extension = null) {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        if ($backupPath === null) {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = $configName . '_backup_' . $timestamp . $extension;
            $backupPath = $this->configDir . $backupName;
        }
        
        // Создаем директорию, если её нет
        $dir = dirname($backupPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return false;
            }
        }
        
        if (@copy($filePath, $backupPath)) {
            @chmod($backupPath, 0644);
            return $backupPath;
        }
        
        return false;
    }
    
    /**
     * Восстановление конфигурации из резервной копии
     * 
     * @param string $backupPath Путь к резервной копии
     * @param string $configName Имя конфигурации для восстановления
     * @param string|null $extension Расширение файла
     * @return bool
     */
    public function restore(string $backupPath, string $configName, ?string $extension = null): bool {
        $extension = $extension ?? self::DEFAULT_EXTENSION;
        $filePath = $this->getConfigPath($configName, $extension);
        
        if (!file_exists($backupPath)) {
            return false;
        }
        
        // Удаляем из памяти
        unset($this->loadedConfigs[$configName]);
        
        if (@copy($backupPath, $filePath)) {
            @chmod($filePath, 0644);
            return true;
        }
        
        return false;
    }
    
    // Предотвращение клонирования и десериализации
    private function __clone() {}
    
    /**
     * @return void
     * @throws Exception
     */
    public function __wakeup(): void {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Глобальные функции для удобства
/**
 * Получение экземпляра конфигурации
 * 
 * @return Config
 */
function config(): Config {
    return Config::getInstance();
}

/**
 * Получение значения конфигурации
 * 
 * @param string $key Ключ
 * @param mixed $default Значение по умолчанию
 * @return mixed
 */
function config_get(string $key, $default = null) {
    return config()->get($key, $default);
}

/**
 * Установка значения конфигурации
 * 
 * @param string $key Ключ
 * @param mixed $value Значение
 * @return bool
 */
function config_set(string $key, $value): bool {
    return config()->set($key, $value);
}

/**
 * Проверка наличия ключа в конфигурации
 * 
 * @param string $key Ключ
 * @return bool
 */
function config_has(string $key): bool {
    return config()->has($key);
}

