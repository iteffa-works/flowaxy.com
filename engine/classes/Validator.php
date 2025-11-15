<?php
/**
 * Класс для валидации данных
 * Централизованная валидация входных данных
 * 
 * @package Core
 * @version 1.0.0
 */

class Validator {
    /**
     * Валидация email
     * 
     * @param string $email Email адрес
     * @return bool
     */
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Валидация URL
     * 
     * @param string $url URL
     * @return bool
     */
    public static function validateUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Валидация целого числа
     * 
     * @param mixed $value Значение
     * @param int|null $min Минимальное значение
     * @param int|null $max Максимальное значение
     * @return bool
     */
    public static function validateInt($value, ?int $min = null, ?int $max = null): bool {
        if (!is_numeric($value)) {
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация строки
     * 
     * @param mixed $value Значение
     * @param int|null $minLength Минимальная длина
     * @param int|null $maxLength Максимальная длина
     * @return bool
     */
    public static function validateString($value, ?int $minLength = null, ?int $maxLength = null): bool {
        if (!is_string($value)) {
            return false;
        }
        
        $length = mb_strlen($value, 'UTF-8');
        
        if ($minLength !== null && $length < $minLength) {
            return false;
        }
        
        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация slug (для URL, имен файлов и т.д.)
     * 
     * @param string $slug Slug
     * @return bool
     */
    public static function validateSlug(string $slug): bool {
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1;
    }
    
    /**
     * Валидация цвета (hex)
     * 
     * @param string $color Цвет в формате hex
     * @return bool
     */
    public static function validateColor(string $color): bool {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }
    
    /**
     * Валидация даты
     * 
     * @param string $date Дата
     * @param string $format Формат даты
     * @return bool
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Валидация файла
     * 
     * @param array $file Массив $_FILES
     * @param array $allowedTypes Разрешенные типы
     * @param int|null $maxSize Максимальный размер в байтах
     * @return array Результат валидации
     */
    public static function validateFile(array $file, array $allowedTypes = [], ?int $maxSize = null): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Файл не был загружен'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Ошибка загрузки файла: ' . $file['error']];
        }
        
        if ($maxSize !== null && $file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'Файл слишком большой. Максимальный размер: ' . self::formatFileSize($maxSize)];
        }
        
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes, true)) {
                return ['valid' => false, 'error' => 'Тип файла не разрешен. Разрешенные типы: ' . implode(', ', $allowedTypes)];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Санитизация строки
     * 
     * @param string $value Значение
     * @param bool $allowHtml Разрешить HTML
     * @return string
     */
    public static function sanitizeString(string $value, bool $allowHtml = false): string {
        $value = trim($value);
        
        if (!$allowHtml) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }
    
    /**
     * Санитизация массива
     * 
     * @param array $data Данные
     * @param bool $allowHtml Разрешить HTML
     * @return array
     */
    public static function sanitizeArray(array $data, bool $allowHtml = false): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = self::sanitizeString((string)$key, false);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = self::sanitizeArray($value, $allowHtml);
            } elseif (is_string($value)) {
                $sanitized[$sanitizedKey] = self::sanitizeString($value, $allowHtml);
            } else {
                $sanitized[$sanitizedKey] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Форматирование размера файла
     * 
     * @param int $bytes Размер в байтах
     * @return string
     */
    private static function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max(0, $bytes);
        
        if ($bytes === 0) {
            return '0 B';
        }
        
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Валидация CSRF токена
     * 
     * @param string|null $token Токен
     * @return bool
     */
    public static function validateCsrfToken(?string $token): bool {
        if ($token === null || !isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Валидация пароля
     * 
     * @param string $password Пароль
     * @param int $minLength Минимальная длина
     * @return array Результат валидации
     */
    public static function validatePassword(string $password, int $minLength = 8): array {
        $errors = [];
        
        if (mb_strlen($password, 'UTF-8') < $minLength) {
            $errors[] = "Пароль должен содержать не менее {$minLength} символов";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну заглавную букву';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну строчную букву';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну цифру';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

