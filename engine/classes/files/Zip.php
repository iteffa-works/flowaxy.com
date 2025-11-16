<?php
/**
 * Класс для работы с ZIP архивами
 * Создание, извлечение и манипуляции с ZIP файлами
 * 
 * @package Engine\Classes
 * @version 1.0.0
 */

declare(strict_types=1);

class Zip {
    private ?ZipArchive $zip = null;
    private string $filePath;
    private bool $isOpen = false;
    
    /**
     * Конструктор
     * 
     * @param string|null $filePath Путь к ZIP файлу
     * @param int $flags Флаги для открытия архива
     */
    public function __construct(?string $filePath = null, int $flags = ZipArchive::CREATE) {
        if (!extension_loaded('zip')) {
            throw new Exception('Расширение ZIP не установлено');
        }
        
        if ($filePath !== null) {
            $this->open($filePath, $flags);
        }
    }
    
    /**
     * Деструктор - закрытие архива при уничтожении объекта
     */
    public function __destruct() {
        $this->close();
    }
    
    /**
     * Открытие ZIP архива
     * 
     * @param string $filePath Путь к ZIP файлу
     * @param int $flags Флаги для открытия (ZipArchive::CREATE, ZipArchive::OVERWRITE и т.д.)
     * @return self
     * @throws Exception Если не удалось открыть архив
     */
    public function open(string $filePath, int $flags = ZipArchive::CREATE): self {
        $this->close();
        
        $this->zip = new ZipArchive();
        $this->filePath = $filePath;
        
        // Создаем директорию, если её нет
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$dir}");
            }
        }
        
        $result = $this->zip->open($filePath, $flags);
        
        if ($result !== true) {
            $error = $this->getZipError($result);
            throw new Exception("Не удалось открыть ZIP архив '{$filePath}': {$error}");
        }
        
        $this->isOpen = true;
        
        return $this;
    }
    
    /**
     * Закрытие ZIP архива
     * 
     * @return bool
     */
    public function close(): bool {
        if ($this->isOpen && $this->zip !== null) {
            $result = $this->zip->close();
            $this->isOpen = false;
            
            if ($result && file_exists($this->filePath)) {
                @chmod($this->filePath, 0644);
            }
            
            return $result;
        }
        
        return true;
    }
    
    /**
     * Добавление файла в архив
     * 
     * @param string $filePath Путь к файлу для добавления
     * @param string|null $localName Имя файла в архиве (если null, используется имя файла)
     * @return self
     * @throws Exception Если файл не существует или не удалось добавить
     */
    public function addFile(string $filePath, ?string $localName = null): self {
        $this->ensureOpen();
        
        if (!file_exists($filePath)) {
            throw new Exception("Файл не существует: {$filePath}");
        }
        
        if (!is_readable($filePath)) {
            throw new Exception("Файл недоступен для чтения: {$filePath}");
        }
        
        $name = $localName ?? basename($filePath);
        
        if (!$this->zip->addFile($filePath, $name)) {
            throw new Exception("Не удалось добавить файл '{$filePath}' в архив");
        }
        
        return $this;
    }
    
    /**
     * Добавление содержимого строки в архив
     * 
     * @param string $localName Имя файла в архиве
     * @param string $contents Содержимое файла
     * @return self
     * @throws Exception Если не удалось добавить содержимое
     */
    public function addFromString(string $localName, string $contents): self {
        $this->ensureOpen();
        
        if (!$this->zip->addFromString($localName, $contents)) {
            throw new Exception("Не удалось добавить содержимое в архив с именем '{$localName}'");
        }
        
        return $this;
    }
    
    /**
     * Добавление директории в архив
     * 
     * @param string $dirPath Путь к директории
     * @param string $localPath Путь в архиве
     * @param array $exclude Паттерны файлов для исключения
     * @return self
     * @throws Exception Если директория не существует
     */
    public function addDirectory(string $dirPath, string $localPath = '', array $exclude = []): self {
        $this->ensureOpen();
        
        if (!is_dir($dirPath)) {
            throw new Exception("Директория не существует: {$dirPath}");
        }
        
        $dirPath = rtrim($dirPath, '/\\') . DIRECTORY_SEPARATOR;
        $localPath = trim($localPath, '/\\');
        
        if (!empty($localPath)) {
            $localPath .= '/';
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            
            $filePath = $file->getRealPath();
            $relativePath = str_replace($dirPath, '', $filePath);
            $relativePath = str_replace('\\', '/', $relativePath);
            
            // Проверка на исключения
            $shouldExclude = false;
            foreach ($exclude as $pattern) {
                if (fnmatch($pattern, $relativePath) || fnmatch($pattern, basename($relativePath))) {
                    $shouldExclude = true;
                    break;
                }
            }
            
            if ($shouldExclude) {
                continue;
            }
            
            $archivePath = $localPath . $relativePath;
            
            if (!$this->zip->addFile($filePath, $archivePath)) {
                throw new Exception("Не удалось добавить файл '{$filePath}' в архив");
            }
        }
        
        return $this;
    }
    
    /**
     * Извлечение всех файлов из архива
     * 
     * @param string $destinationPath Путь назначения
     * @param array|null $entries Список файлов для извлечения (null = все)
     * @return bool
     * @throws Exception Если не удалось извлечь
     */
    public function extractTo(string $destinationPath, ?array $entries = null): bool {
        $this->ensureOpen();
        
        if (!is_dir($destinationPath)) {
            if (!@mkdir($destinationPath, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$destinationPath}");
            }
        }
        
        $result = $this->zip->extractTo($destinationPath, $entries);
        
        if (!$result) {
            throw new Exception("Не удалось извлечь файлы из архива в '{$destinationPath}'");
        }
        
        return true;
    }
    
    /**
     * Извлечение конкретного файла из архива
     * 
     * @param string $entryName Имя файла в архиве
     * @param string $destinationPath Путь назначения
     * @return bool
     * @throws Exception Если файл не найден или не удалось извлечь
     */
    public function extractFile(string $entryName, string $destinationPath): bool {
        $this->ensureOpen();
        
        if (!$this->hasEntry($entryName)) {
            throw new Exception("Файл '{$entryName}' не найден в архиве");
        }
        
        // Создаем директорию, если её нет
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Не удалось создать директорию: {$dir}");
            }
        }
        
        $contents = $this->getEntryContents($entryName);
        
        if ($contents === false) {
            throw new Exception("Не удалось прочитать содержимое файла '{$entryName}' из архива");
        }
        
        $result = @file_put_contents($destinationPath, $contents, LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Не удалось записать файл: {$destinationPath}");
        }
        
        @chmod($destinationPath, 0644);
        
        return true;
    }
    
    /**
     * Получение содержимого файла из архива
     * 
     * @param string $entryName Имя файла в архиве
     * @return string|false
     */
    public function getEntryContents(string $entryName) {
        $this->ensureOpen();
        
        return $this->zip->getFromName($entryName);
    }
    
    /**
     * Удаление файла из архива
     * 
     * @param string $entryName Имя файла в архиве
     * @return self
     * @throws Exception Если не удалось удалить
     */
    public function deleteEntry(string $entryName): self {
        $this->ensureOpen();
        
        if (!$this->zip->deleteName($entryName)) {
            throw new Exception("Не удалось удалить файл '{$entryName}' из архива");
        }
        
        return $this;
    }
    
    /**
     * Переименование файла в архиве
     * 
     * @param string $oldName Старое имя
     * @param string $newName Новое имя
     * @return self
     * @throws Exception Если не удалось переименовать
     */
    public function renameEntry(string $oldName, string $newName): self {
        $this->ensureOpen();
        
        if (!$this->zip->renameName($oldName, $newName)) {
            throw new Exception("Не удалось переименовать файл '{$oldName}' в '{$newName}'");
        }
        
        return $this;
    }
    
    /**
     * Получение списка всех файлов в архиве
     * 
     * @return array
     */
    public function getEntries(): array {
        $this->ensureOpen();
        
        $entries = [];
        
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $entryName = $this->zip->getNameIndex($i);
            if ($entryName !== false) {
                $entries[] = $entryName;
            }
        }
        
        return $entries;
    }
    
    /**
     * Получение информации о файле в архиве
     * 
     * @param string $entryName Имя файла в архиве
     * @return array|false
     */
    public function getEntryInfo(string $entryName) {
        $this->ensureOpen();
        
        $stat = $this->zip->statName($entryName);
        
        if ($stat === false) {
            return false;
        }
        
        return [
            'name' => $stat['name'],
            'index' => $stat['index'],
            'crc' => $stat['crc'],
            'size' => $stat['size'],
            'mtime' => $stat['mtime'],
            'comp_size' => $stat['comp_size'],
            'comp_method' => $stat['comp_method']
        ];
    }
    
    /**
     * Проверка наличия файла в архиве
     * 
     * @param string $entryName Имя файла в архиве
     * @return bool
     */
    public function hasEntry(string $entryName): bool {
        $this->ensureOpen();
        
        return $this->zip->locateName($entryName) !== false;
    }
    
    /**
     * Получение количества файлов в архиве
     * 
     * @return int
     */
    public function getEntryCount(): int {
        $this->ensureOpen();
        
        return $this->zip->numFiles;
    }
    
    /**
     * Получение комментария архива
     * 
     * @return string|false
     */
    public function getComment() {
        $this->ensureOpen();
        
        return $this->zip->getArchiveComment();
    }
    
    /**
     * Установка комментария архива
     * 
     * @param string $comment Комментарий
     * @return self
     * @throws Exception Если не удалось установить комментарий
     */
    public function setComment(string $comment): self {
        $this->ensureOpen();
        
        if (!$this->zip->setArchiveComment($comment)) {
            throw new Exception("Не удалось установить комментарий архива");
        }
        
        return $this;
    }
    
    /**
     * Проверка, открыт ли архив
     * 
     * @return bool
     */
    public function isOpen(): bool {
        return $this->isOpen;
    }
    
    /**
     * Получение пути к файлу архива
     * 
     * @return string
     */
    public function getFilePath(): string {
        return $this->filePath;
    }
    
    /**
     * Создание ZIP архива из директории
     * 
     * @param string $sourceDir Исходная директория
     * @param string $zipPath Путь к создаваемому архиву
     * @param array $exclude Паттерны файлов для исключения
     * @return self
     */
    public static function createFromDirectory(string $sourceDir, string $zipPath, array $exclude = []): self {
        $zip = new self($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addDirectory($sourceDir, '', $exclude);
        return $zip;
    }
    
    /**
     * Проверка, открыт ли архив (внутренний метод)
     * 
     * @return void
     * @throws Exception Если архив не открыт
     */
    private function ensureOpen(): void {
        if (!$this->isOpen || $this->zip === null) {
            throw new Exception("ZIP архив не открыт");
        }
    }
    
    /**
     * Получение текстового описания ошибки ZIP
     * 
     * @param int $code Код ошибки
     * @return string
     */
    private function getZipError(int $code): string {
        $errors = [
            ZipArchive::ER_OK => 'OK',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
            ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            ZipArchive::ER_CLOSE => 'Closing zip archive failed',
            ZipArchive::ER_SEEK => 'Seek error',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_CRC => 'CRC error',
            ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            ZipArchive::ER_ZLIB => 'Zlib error',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_CHANGED => 'Entry has been changed',
            ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            ZipArchive::ER_EOF => 'Premature EOF',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_INTERNAL => 'Internal error',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_REMOVE => 'Can\'t remove file',
            ZipArchive::ER_DELETED => 'Entry has been deleted',
        ];
        
        return $errors[$code] ?? "Unknown error (code: {$code})";
    }
    
    /**
     * Статический метод: Распаковка ZIP архива
     * 
     * @param string $zipPath Путь к ZIP архиву
     * @param string $destinationPath Путь для распаковки
     * @param array|null $entries Список файлов для извлечения (null = все)
     * @return bool Возвращает true при успехе, false при ошибке
     */
    public static function unpack(string $zipPath, string $destinationPath, ?array $entries = null): bool {
        if (!extension_loaded('zip')) {
            error_log("Zip::unpack error: ZIP расширение не установлено");
            return false;
        }
        
        if (!file_exists($zipPath)) {
            error_log("Zip::unpack error: ZIP файл не существует: {$zipPath}");
            return false;
        }
        
        try {
            $zip = new self($zipPath, ZipArchive::RDONLY);
            $result = $zip->extractTo($destinationPath, $entries);
            $zip->close();
            return $result;
        } catch (Exception $e) {
            error_log("Zip::unpack error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Переименование файла в ZIP архиве
     * 
     * @param string $zipPath Путь к ZIP архиву
     * @param string $oldName Старое имя файла в архиве
     * @param string $newName Новое имя файла в архиве
     * @return bool Возвращает true при успехе, false при ошибке
     */
    public static function rename_file(string $zipPath, string $oldName, string $newName): bool {
        if (!extension_loaded('zip')) {
            error_log("Zip::rename_file error: ZIP расширение не установлено");
            return false;
        }
        
        if (!file_exists($zipPath)) {
            error_log("Zip::rename_file error: ZIP файл не существует: {$zipPath}");
            return false;
        }
        
        try {
            $zip = new self($zipPath);
            $zip->renameEntry($oldName, $newName);
            $result = $zip->close();
            return $result;
        } catch (Exception $e) {
            error_log("Zip::rename_file error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Упаковка директории в ZIP архив
     * 
     * @param string $sourceDir Исходная директория
     * @param string $zipPath Путь к создаваемому архиву
     * @param array $exclude Паттерны файлов для исключения
     * @return bool Возвращает true при успехе, false при ошибке
     */
    public static function pack(string $sourceDir, string $zipPath, array $exclude = []): bool {
        if (!extension_loaded('zip')) {
            error_log("Zip::pack error: ZIP расширение не установлено");
            return false;
        }
        
        if (!is_dir($sourceDir)) {
            error_log("Zip::pack error: Директория не существует: {$sourceDir}");
            return false;
        }
        
        try {
            $zip = self::createFromDirectory($sourceDir, $zipPath, $exclude);
            $result = $zip->close();
            return $result;
        } catch (Exception $e) {
            error_log("Zip::pack error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Статический метод: Получение списка файлов в ZIP архиве
     * 
     * @param string $zipPath Путь к ZIP архиву
     * @return array|false Массив имен файлов или false при ошибке
     */
    public static function listFiles(string $zipPath) {
        if (!extension_loaded('zip')) {
            error_log("Zip::listFiles error: ZIP расширение не установлено");
            return false;
        }
        
        if (!file_exists($zipPath)) {
            error_log("Zip::listFiles error: ZIP файл не существует: {$zipPath}");
            return false;
        }
        
        try {
            $zip = new self($zipPath, ZipArchive::RDONLY);
            $files = $zip->getEntries();
            $zip->close();
            return $files;
        } catch (Exception $e) {
            error_log("Zip::listFiles error: " . $e->getMessage());
            return false;
        }
    }
}

