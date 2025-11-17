<?php
/**
 * Оптимизированная система кеширования
 * 
 * @package Core
 * @version 2.0.0
 */

declare(strict_types=1);

class Cache {
    private static ?self $instance = null;
    private string $cacheDir;
    private int $defaultTtl = 3600; // 1 час
    private array $memoryCache = []; // Кеш в памяти для текущего запроса
    private bool $enabled = true;
    private bool $autoCleanup = true;
    private const CACHE_FILE_EXTENSION = '.cache';
    
    /**
     * Конструктор (приватный для Singleton)
     */
    private function __construct() {
        $this->cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/storage/cache/';
        $this->cacheDir = rtrim($this->cacheDir, '/') . '/';
        $this->ensureCacheDir();
        // Пропускаем автоматическую очистку в конструкторе, чтобы избежать циклических зависимостей
        $this->loadSettings(true);
    }
    
    /**
     * Загрузка настроек из SettingsManager
     * 
     * @param bool $skipCleanup Пропустить автоматическую очистку (для избежания циклических зависимостей)
     * @return void
     */
    private function loadSettings(bool $skipCleanup = false): void {
        if (class_exists('SettingsManager')) {
            try {
                $settings = settingsManager();
                $this->enabled = $settings->get('cache_enabled', '1') === '1';
                $this->defaultTtl = (int)$settings->get('cache_default_ttl', '3600');
                $this->autoCleanup = $settings->get('cache_auto_cleanup', '1') === '1';
                
                // Выполняем автоматическую очистку при необходимости (только если не в конструкторе)
                if (!$skipCleanup && $this->autoCleanup && mt_rand(1, 1000) <= 1) { // 0.1% шанс на очистку при каждом запросе
                    // Запускаем очистку в фоне, чтобы не блокировать запрос
                    register_shutdown_function(function() {
                        $this->cleanup();
                    });
                }
            } catch (Exception $e) {
                // В случае ошибки используем значения по умолчанию
            }
        }
    }
    
    /**
     * Обновление настроек (вызывается после изменения настроек)
     * 
     * @return void
     */
    public function reloadSettings(): void {
        // При обновлении настроек разрешаем автоматическую очистку
        $this->loadSettings(false);
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
     * Создание директории кеша
     * 
     * @return void
     */
    private function ensureCacheDir(): void {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        
        // Создаем .htaccess для защиты
        $htaccessFile = $this->cacheDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Deny from all\n");
        }
        
        // Создаем .gitkeep для git
        $gitkeepFile = $this->cacheDir . '.gitkeep';
        if (!file_exists($gitkeepFile)) {
            @file_put_contents($gitkeepFile, '');
        }
    }
    
    /**
     * Получение данных из кеша
     * 
     * @param string $key Ключ кеша
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null) {
        // Если кеширование отключено, возвращаем значение по умолчанию
        if (!$this->enabled) {
            return $default;
        }
        
        // Валидация ключа
        if (empty($key)) {
            return $default;
        }
        
        // Сначала проверяем кеш в памяти
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename) || !is_readable($filename)) {
            return $default;
        }
        
        $data = @file_get_contents($filename);
        if ($data === false) {
            return $default;
        }
        
        try {
            $cached = unserialize($data, ['allowed_classes' => false]);
            
            // Проверяем структуру данных
            if (!is_array($cached) || !isset($cached['expires']) || !isset($cached['data'])) {
                @unlink($filename);
                return $default;
            }
            
            // Проверяем срок действия
            if ($cached['expires'] < time()) {
                $this->delete($key);
                return $default;
            }
            
            // Сохраняем в кеш памяти
            $this->memoryCache[$key] = $cached['data'];
            
            return $cached['data'];
        } catch (Exception $e) {
            error_log("Cache unserialize error for key '{$key}': " . $e->getMessage());
            @unlink($filename);
            return $default;
        }
    }
    
    /**
     * Сохранение данных в кеш
     * 
     * @param string $key Ключ кеша
     * @param mixed $data Данные для кеширования
     * @param int|null $ttl Время жизни в секундах
     * @return bool
     */
    public function set(string $key, $data, ?int $ttl = null): bool {
        // Если кеширование отключено, не сохраняем
        if (!$this->enabled) {
            return false;
        }
        
        // Валидация ключа
        if (empty($key)) {
            return false;
        }
        
        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }
        
        // Валидация TTL
        if ($ttl < 0) {
            $ttl = $this->defaultTtl;
        }
        
        $cached = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        try {
            $serialized = serialize($cached);
        } catch (Exception $e) {
            error_log("Cache serialize error for key '{$key}': " . $e->getMessage());
            return false;
        }
        
        $filename = $this->getFilename($key);
        $result = @file_put_contents($filename, $serialized, LOCK_EX);
        
        if ($result !== false) {
            // Устанавливаем права доступа
            @chmod($filename, 0644);
            
            // Сохраняем в кеш памяти
            $this->memoryCache[$key] = $data;
            return true;
        }
        
        error_log("Cache write error for key '{$key}' to file '{$filename}'");
        return false;
    }
    
    /**
     * Удаление из кеша
     * 
     * @param string $key Ключ кеша
     * @return bool
     */
    public function delete(string $key): bool {
        // Валидация ключа
        if (empty($key)) {
            return false;
        }
        
        unset($this->memoryCache[$key]);
        
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Проверка существования ключа
     * 
     * @param string $key Ключ кеша
     * @return bool
     */
    public function has(string $key): bool {
        // Валидация ключа
        if (empty($key)) {
            return false;
        }
        
        if (isset($this->memoryCache[$key])) {
            return true;
        }
        
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }
        
        $data = @file_get_contents($filename);
        if ($data === false) {
            return false;
        }
        
        try {
            $cached = unserialize($data, ['allowed_classes' => false]);
            
            // Проверяем структуру данных
            if (!is_array($cached) || !isset($cached['expires'])) {
                @unlink($filename);
                return false;
            }
            
            // Проверяем срок действия
            if ($cached['expires'] < time()) {
                $this->delete($key);
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Cache has check error for key '{$key}': " . $e->getMessage());
            @unlink($filename);
            return false;
        }
    }
    
    /**
     * Получение или установка значения
     * 
     * @param string $key Ключ кеша
     * @param callable $callback Функция для получения данных
     * @param int|null $ttl Время жизни в секундах
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null) {
        // Если кеширование отключено, просто выполняем callback
        if (!$this->enabled) {
            try {
                return $callback();
            } catch (Exception $e) {
                error_log("Cache remember callback error for key '{$key}': " . $e->getMessage());
                throw $e;
            }
        }
        
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        try {
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        } catch (Exception $e) {
            error_log("Cache remember callback error for key '{$key}': " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Очистка всего кеша
     * 
     * @return bool
     */
    public function clear(): bool {
        $this->memoryCache = [];
        
        $pattern = $this->cacheDir . '*' . self::CACHE_FILE_EXTENSION;
        $files = glob($pattern);
        
        if ($files === false) {
            return false;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!@unlink($file)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Очистка устаревшего кеша
     * 
     * @return int Количество удаленных файлов
     */
    public function cleanup(): int {
        $cleaned = 0;
        $pattern = $this->cacheDir . '*' . self::CACHE_FILE_EXTENSION;
        $files = glob($pattern);
        
        if ($files === false) {
            return 0;
        }
        
        $currentTime = time();
        
        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            
            $data = @file_get_contents($file);
            if ($data === false) {
                continue;
            }
            
            try {
                $cached = unserialize($data, ['allowed_classes' => false]);
                
                if (!is_array($cached) || !isset($cached['expires'])) {
                    @unlink($file);
                    $cleaned++;
                    continue;
                }
                
                if ($cached['expires'] < $currentTime) {
                    @unlink($file);
                    $cleaned++;
                }
            } catch (Exception $e) {
                // Удаляем поврежденный файл
                @unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Получение статистики кеша
     * 
     * @return array
     */
    public function getStats(): array {
        $pattern = $this->cacheDir . '*' . self::CACHE_FILE_EXTENSION;
        $files = glob($pattern);
        
        if ($files === false) {
            return [
                'total_files' => 0,
                'valid_files' => 0,
                'expired_files' => 0,
                'total_size' => 0,
                'memory_cache_size' => count($this->memoryCache)
            ];
        }
        
        $totalSize = 0;
        $expired = 0;
        $valid = 0;
        $currentTime = time();
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $fileSize = @filesize($file);
            if ($fileSize !== false) {
                $totalSize += $fileSize;
            }
            
            $data = @file_get_contents($file);
            if ($data === false) {
                continue;
            }
            
            try {
                $cached = unserialize($data, ['allowed_classes' => false]);
                
                if (!is_array($cached) || !isset($cached['expires'])) {
                    $expired++;
                    continue;
                }
                
                if ($cached['expires'] < $currentTime) {
                    $expired++;
                } else {
                    $valid++;
                }
            } catch (Exception $e) {
                $expired++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $valid,
            'expired_files' => $expired,
            'total_size' => $totalSize,
            'memory_cache_size' => count($this->memoryCache)
        ];
    }
    
    /**
     * Получение имени файла для ключа
     * 
     * @param string $key Ключ кеша
     * @return string
     */
    private function getFilename(string $key): string {
        $hash = md5($key);
        return $this->cacheDir . $hash . self::CACHE_FILE_EXTENSION;
    }
    
    /**
     * Тегированный кеш
     * 
     * @param array|string $tags Теги
     * @return TaggedCache
     */
    public function tags($tags): TaggedCache {
        return new TaggedCache($this, (array)$tags);
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

/**
 * Тегированный кеш
 */
class TaggedCache {
    private Cache $cache;
    private array $tags;
    
    /**
     * Конструктор
     * 
     * @param Cache $cache Экземпляр кеша
     * @param array $tags Массив тегов
     */
    public function __construct(Cache $cache, array $tags) {
        $this->cache = $cache;
        $this->tags = array_filter($tags, function($tag) {
            return is_string($tag) && !empty($tag);
        });
    }
    
    /**
     * Получение данных из кеша
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return $this->cache->get($this->taggedKey($key), $default);
    }
    
    /**
     * Сохранение данных в кеш
     * 
     * @param string $key Ключ
     * @param mixed $data Данные
     * @param int|null $ttl Время жизни
     * @return bool
     */
    public function set(string $key, $data, ?int $ttl = null): bool {
        $result = $this->cache->set($this->taggedKey($key), $data, $ttl);
        
        // Сохраняем информацию о тегах
        foreach ($this->tags as $tag) {
            $tagKey = 'tag:' . $tag;
            $taggedKeys = $this->cache->get($tagKey, []);
            
            if (!is_array($taggedKeys)) {
                $taggedKeys = [];
            }
            
            $taggedKeys[] = $this->taggedKey($key);
            $taggedKeys = array_unique($taggedKeys);
            $this->cache->set($tagKey, $taggedKeys, 86400); // 24 часа
        }
        
        return $result;
    }
    
    /**
     * Очистка всех данных с указанными тегами
     * 
     * @return void
     */
    public function flush(): void {
        foreach ($this->tags as $tag) {
            $tagKey = 'tag:' . $tag;
            $taggedKeys = $this->cache->get($tagKey, []);
            
            if (is_array($taggedKeys)) {
                foreach ($taggedKeys as $key) {
                    $this->cache->delete($key);
                }
            }
            
            $this->cache->delete($tagKey);
        }
    }
    
    /**
     * Генерация тегированного ключа
     * 
     * @param string $key Ключ
     * @return string
     */
    private function taggedKey(string $key): string {
        $tagsStr = implode(':', array_map('md5', $this->tags));
        return 'tagged:' . $tagsStr . ':' . $key;
    }
}

// Глобальные функции для удобства
/**
 * Получение экземпляра кеша
 * 
 * @return Cache
 */
function cache(): Cache {
    return Cache::getInstance();
}

/**
 * Получение данных из кеша
 * 
 * @param string $key Ключ
 * @param mixed $default Значение по умолчанию
 * @return mixed
 */
function cache_get(string $key, $default = null) {
    return cache()->get($key, $default);
}

/**
 * Сохранение данных в кеш
 * 
 * @param string $key Ключ
 * @param mixed $data Данные
 * @param int|null $ttl Время жизни
 * @return bool
 */
function cache_set(string $key, $data, ?int $ttl = null): bool {
    return cache()->set($key, $data, $ttl);
}

/**
 * Получение или установка значения
 * 
 * @param string $key Ключ
 * @param callable $callback Функция
 * @param int|null $ttl Время жизни
 * @return mixed
 */
function cache_remember(string $key, callable $callback, ?int $ttl = null) {
    return cache()->remember($key, $callback, $ttl);
}

/**
 * Удаление из кеша
 * 
 * @param string $key Ключ
 * @return bool
 */
function cache_forget(string $key): bool {
    return cache()->delete($key);
}

/**
 * Очистка всего кеша
 * 
 * @return bool
 */
function cache_flush(): bool {
    return cache()->clear();
}
?>

