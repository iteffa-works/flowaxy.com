<?php
/**
 * Клас для роботи з INI файлами
 * Читання, запис та маніпуляції з INI конфігураційними файлами
 * 
 * @package Engine\Classes
 * @version 1.0.0
 */

declare(strict_types=1);

class Ini {
    private string $filePath;
    private array $data = [];
    private bool $hasData = false;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Шлях до INI файлу
     */
    public function __construct(?string $filePath = null) {
        if ($filePath !== null) {
            $this->setFile($filePath);
        }
    }
    
    /**
     * Встановлення шляху до файлу
     * 
     * @param string $filePath Шлях до INI файлу
     * @return self
     * @throws Exception Якщо файл не існує або недоступний
     */
    public function setFile(string $filePath): self {
        if (!is_readable($filePath) && file_exists($filePath)) {
            throw new Exception("INI файл існує, але недоступний для читання: {$filePath}");
        }
        
        $this->filePath = $filePath;
        $this->hasData = false;
        
        if (file_exists($filePath)) {
            $this->load();
        }
        
        return $this;
    }
    
    /**
     * Завантаження даних з INI файлу
     * 
     * @param int $mode Режим парсингу (INIScanner::NORMAL або INIScanner::RAW)
     * @return self
     * @throws Exception Якщо файл не існує або не може бути прочитаний
     */
    public function load(int $mode = INI_SCANNER_NORMAL): self {
        if (empty($this->filePath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        if (!file_exists($this->filePath)) {
            throw new Exception("INI файл не існує: {$this->filePath}");
        }
        
        if (!is_readable($this->filePath)) {
            throw new Exception("INI файл недоступний для читання: {$this->filePath}");
        }
        
        $data = @parse_ini_file($this->filePath, true, $mode);
        
        if ($data === false) {
            throw new Exception("Помилка парсингу INI файлу: {$this->filePath}");
        }
        
        $this->data = $data;
        $this->hasData = true;
        
        return $this;
    }
    
    /**
     * Збереження даних у INI файл
     * 
     * @param string|null $filePath Шлях до файлу (якщо null, використовується поточний)
     * @param array|null $data Дані для збереження (якщо null, використовуються поточні)
     * @return bool
     * @throws Exception Якщо не вдалося зберегти файл
     */
    public function save(?string $filePath = null, ?array $data = null): bool {
        $targetPath = $filePath ?? $this->filePath;
        $dataToSave = $data ?? $this->data;
        
        if (empty($targetPath)) {
            throw new Exception("Шлях до файлу не встановлено");
        }
        
        // Створюємо директорію, якщо її немає
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не вдалося створити директорію: {$dir}");
            }
        }
        
        $content = $this->arrayToIni($dataToSave);
        
        $result = @file_put_contents($targetPath, $content, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не вдалося зберегти INI файл: {$targetPath}");
        }
        
        @chmod($targetPath, 0644);
        
        // Оновлюємо поточні дані
        if ($filePath === null) {
            $this->data = $dataToSave;
            $this->hasData = true;
        }
        
        return true;
    }
    
    /**
     * Отримання значення з INI
     * 
     * @param string $key Ключ (може бути у форматі "section.key" або просто "key")
     * @param mixed $default Значення за замовчуванням
     * @return mixed
     */
    public function get(string $key, $default = null) {
        if (!$this->hasData) {
            return $default;
        }
        
        // Перевіряємо, чи є секція в ключі
        if (str_contains($key, '.')) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (isset($this->data[$section]) && is_array($this->data[$section])) {
                return $this->data[$section][$sectionKey] ?? $default;
            }
        }
        
        // Якщо це проста секція
        if (isset($this->data[$key])) {
            if (is_array($this->data[$key])) {
                return $this->data[$key];
            }
            return $this->data[$key];
        }
        
        // Шукаємо в корені
        foreach ($this->data as $sectionKey => $sectionValue) {
            if (is_array($sectionValue) && isset($sectionValue[$key])) {
                return $sectionValue[$key];
            }
        }
        
        return $default;
    }
    
    /**
     * Встановлення значення в INI
     * 
     * @param string $key Ключ (може бути у форматі "section.key")
     * @param mixed $value Значення
     * @return self
     */
    public function set(string $key, $value): self {
        if (!$this->hasData) {
            $this->data = [];
            $this->hasData = true;
        }
        
        // Перевіряємо, чи є секція в ключі
        if (str_contains($key, '.')) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (!isset($this->data[$section]) || !is_array($this->data[$section])) {
                $this->data[$section] = [];
            }
            
            $this->data[$section][$sectionKey] = $value;
        } else {
            // Встановлюємо в корінь
            $this->data[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Перевірка наявності ключа
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool {
        if (!$this->hasData) {
            return false;
        }
        
        if (str_contains($key, '.')) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (isset($this->data[$section]) && is_array($this->data[$section])) {
                return isset($this->data[$section][$sectionKey]);
            }
        }
        
        return isset($this->data[$key]);
    }
    
    /**
     * Видалення ключа
     * 
     * @param string $key Ключ
     * @return self
     */
    public function remove(string $key): self {
        if (!$this->hasData) {
            return $this;
        }
        
        if (str_contains($key, '.')) {
            [$section, $sectionKey] = explode('.', $key, 2);
            
            if (isset($this->data[$section]) && is_array($this->data[$section])) {
                unset($this->data[$section][$sectionKey]);
                
                // Видаляємо секцію, якщо вона порожня
                if (empty($this->data[$section])) {
                    unset($this->data[$section]);
                }
            }
        } else {
            unset($this->data[$key]);
        }
        
        return $this;
    }
    
    /**
     * Отримання всіх даних
     * 
     * @return array
     */
    public function all(): array {
        return $this->data;
    }
    
    /**
     * Очищення всіх даних
     * 
     * @return self
     */
    public function clear(): self {
        $this->data = [];
        $this->hasData = false;
        return $this;
    }
    
    /**
     * Отримання секції
     * 
     * @param string $section Ім'я секції
     * @param array $default Значення за замовчуванням
     * @return array
     */
    public function getSection(string $section, array $default = []): array {
        if (!$this->hasData) {
            return $default;
        }
        
        if (isset($this->data[$section]) && is_array($this->data[$section])) {
            return $this->data[$section];
        }
        
        return $default;
    }
    
    /**
     * Встановлення секції
     * 
     * @param string $section Ім'я секції
     * @param array $data Дані секції
     * @return self
     */
    public function setSection(string $section, array $data): self {
        if (!$this->hasData) {
            $this->data = [];
            $this->hasData = true;
        }
        
        $this->data[$section] = $data;
        
        return $this;
    }
    
    /**
     * Видалення секції
     * 
     * @param string $section Ім'я секції
     * @return self
     */
    public function removeSection(string $section): self {
        if (!$this->hasData) {
            return $this;
        }
        
        unset($this->data[$section]);
        
        return $this;
    }
    
    /**
     * Перевірка існування секції
     * 
     * @param string $section Ім'я секції
     * @return bool
     */
    public function hasSection(string $section): bool {
        if (!$this->hasData) {
            return false;
        }
        
        return isset($this->data[$section]) && is_array($this->data[$section]);
    }
    
    /**
     * Отримання списку всіх секцій
     * 
     * @return array
     */
    public function getSections(): array {
        if (!$this->hasData) {
            return [];
        }
        
        $sections = [];
        
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $sections[] = $key;
            }
        }
        
        return $sections;
    }
    
    /**
     * Перетворення масиву в INI формат
     * 
     * @param array $data Дані
     * @param string $prefix Префікс для ключів (внутрішнє використання)
     * @return string
     */
    private function arrayToIni(array $data, string $prefix = ''): string {
        $content = '';
        
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                // Перевіряємо, чи є це секцією (всі значення - скаляри)
                $isSection = true;
                foreach ($value as $v) {
                    if (is_array($v) || is_object($v) || is_resource($v)) {
                        $isSection = false;
                        break;
                    }
                }
                
                if ($isSection) {
                    // Це секція INI
                    $content .= "\n[{$key}]\n";
                    foreach ($value as $k => $v) {
                        $content .= $this->formatIniLine($k, $v);
                    }
                } else {
                    // Вкладений масив - рекурсивно обробляємо
                    $content .= $this->arrayToIni($value, $fullKey);
                }
            } else {
                // Скалярне значення
                if ($prefix === '') {
                    // Це кореневе значення
                    $content .= $this->formatIniLine($key, $value);
                } else {
                    // Це вкладене значення - використовуємо крапкову нотацію
                    $content .= $this->formatIniLine($fullKey, $value);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Форматування рядка INI
     * 
     * @param string $key Ключ
     * @param mixed $value Значення
     * @return string
     */
    private function formatIniLine(string $key, $value): string {
        $key = $this->escapeKey($key);
        
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = '';
        } else {
            $value = $this->escapeValue((string)$value);
        }
        
        return "{$key} = {$value}\n";
    }
    
    /**
     * Екранування ключа INI
     * 
     * @param string $key Ключ
     * @return string
     */
    private function escapeKey(string $key): string {
        // Екрануємо спеціальні символи в ключах
        $key = str_replace(['=', ';', '"'], ['\=', '\;', '\"'], $key);
        
        // Якщо ключ містить пробіли або спеціальні символи, обгортаємо в лапки
        if (preg_match('/[\s\[\]]/', $key)) {
            $key = '"' . str_replace('"', '\"', $key) . '"';
        }
        
        return $key;
    }
    
    /**
     * Екранування значення INI
     * 
     * @param string $value Значення
     * @return string
     */
    private function escapeValue(string $value): string {
        // Якщо значення містить спеціальні символи, лапки або починається з пробілу, обгортаємо в лапки
        if (preg_match('/[;\s"\[\]]/', $value) || $value === '' || (isset($value[0]) && $value[0] === ' ')) {
            $value = '"' . str_replace('"', '\"', $value) . '"';
        }
        
        return $value;
    }
    
    /**
     * Отримання шляху до файлу
     * 
     * @return string
     */
    public function getFilePath(): string {
        return $this->filePath;
    }
    
    /**
     * Перевірка, чи завантажені дані
     * 
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->hasData;
    }
    
    /**
     * Статичний метод: Парсинг INI файлу
     * 
     * @param string $path Шлях до INI файлу
     * @param bool $processSections Обробляти чи секції
     * @param int $mode Режим парсингу
     * @return array
     * @throws Exception Якщо файл не існує або не може бути прочитаний
     */
    public static function parse(string $path, bool $processSections = true, int $mode = INI_SCANNER_NORMAL): array {
        if (!file_exists($path)) {
            throw new Exception("INI файл не існує: {$path}");
        }
        
        if (!is_readable($path)) {
            throw new Exception("INI файл недоступний для читання: {$path}");
        }
        
        $data = @parse_ini_file($path, $processSections, $mode);
        
        if ($data === false) {
            throw new Exception("Помилка парсингу INI файлу: {$path}");
        }
        
        return $data;
    }
    
    /**
     * Статичний метод: Оновлення значення в INI файлі
     * 
     * @param string $path Шлях до INI файлу
     * @param string $key Ключ для оновлення
     * @param mixed $value Нове значення
     * @param bool $processSections Обробляти чи секції
     * @return bool
     * @throws Exception Якщо файл не існує або не вдалося оновити
     */
    public static function upgrade(string $path, string $key, $value, bool $processSections = true): bool {
        try {
            $ini = new self($path);
            if ($processSections) {
                $ini->load(INI_SCANNER_NORMAL);
            } else {
                $data = self::parse($path, false);
                $ini->clear();
                foreach ($data as $k => $v) {
                    $ini->set($k, $v);
                }
            }
            
            $ini->set($key, $value);
            return $ini->save();
        } catch (Exception $e) {
            error_log("Ini::upgrade помилка: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статичний метод: Додавання/встановлення значення в INI файлі
     * 
     * @param string $path Шлях до INI файлу
     * @param string $key Ключ
     * @param mixed $value Значення
     * @param bool $processSections Обробляти чи секції
     * @return bool
     * @throws Exception Якщо не вдалося додати
     */
    public static function add(string $path, string $key, $value, bool $processSections = true): bool {
        return self::upgrade($path, $key, $value, $processSections);
    }
    
    /**
     * Статичний метод: Видалення ключа з INI файлу
     * 
     * @param string $path Шлях до INI файлу
     * @param string $key Ключ для видалення
     * @param bool $processSections Обробляти чи секції
     * @return bool
     * @throws Exception Якщо файл не існує або не вдалося видалити
     */
    public static function delete(string $path, string $key, bool $processSections = true): bool {
        if (!file_exists($path)) {
            return false;
        }
        
        try {
            $ini = new self($path);
            if ($processSections) {
                $ini->load(INI_SCANNER_NORMAL);
            } else {
                $data = self::parse($path, false);
                $ini->clear();
                foreach ($data as $k => $v) {
                    $ini->set($k, $v);
                }
            }
            
            $ini->remove($key);
            return $ini->save();
        } catch (Exception $e) {
            error_log("Ini::delete помилка: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статичний метод: Читання INI файлу
     * 
     * @param string $path Шлях до INI файлу
     * @param bool $processSections Обробляти чи секції
     * @param int $mode Режим парсингу
     * @return array
     */
    public static function read(string $path, bool $processSections = true, int $mode = INI_SCANNER_NORMAL): array {
        return self::parse($path, $processSections, $mode);
    }
    
    /**
     * Статичний метод: Запис даних у INI файл
     * 
     * @param string $path Шлях до INI файлу
     * @param array $data Дані для запису
     * @return bool
     */
    public static function write(string $path, array $data): bool {
        try {
            $ini = new self($path);
            // Використовуємо методи для встановлення даних
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // Це секція
                    $ini->setSection($key, $value);
                } else {
                    // Це просте значення
                    $ini->set($key, $value);
                }
            }
            return $ini->save();
        } catch (Exception $e) {
            error_log("Ini::write помилка: " . $e->getMessage());
            return false;
        }
    }
}

