<?php
/**
 * Хелпер для работы с URL
 * 
 * @package Engine\Classes\Helpers
 * @version 1.0.0
 */

declare(strict_types=1);

class UrlHelper {
    /**
     * Получение протокола из настроек системы
     * Использует detectProtocol() для получения актуального протокола
     * 
     * @return string Протокол (http:// или https://)
     */
    public static function getProtocol(): string {
        if (function_exists('detectProtocol')) {
            return detectProtocol();
        }
        
        // Fallback на автоматическое определение, если функция не доступна
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return 'https://';
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return 'https://';
        }
        
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
        
        return $isHttps ? 'https://' : 'http://';
    }
    
    /**
     * Проверка, используется ли HTTPS протокол
     * 
     * @return bool
     */
    public static function isHttps(): bool {
        return self::getProtocol() === 'https://';
    }
    
    /**
     * Получение протокол-относительного URL (для избежания Mixed Content)
     * 
     * @param string $path Путь
     * @return string
     */
    public static function protocolRelative(string $path = ''): string {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return '//' . $host . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    /**
     * Получение URL загрузок с правильным протоколом
     * 
     * @param string $filePath Путь к файлу
     * @return string
     */
    public static function uploads(string $filePath = ''): string {
        return self::protocolRelative('uploads' . ($filePath ? '/' . ltrim($filePath, '/') : ''));
    }
    
    /**
     * Конвертация абсолютного URL в протокол-относительный
     * 
     * @param string $url URL
     * @return string
     */
    public static function toProtocolRelative(string $url): string {
        if (empty($url)) {
            return $url;
        }
        
        // Если URL уже протокол-относительный, возвращаем как есть
        if (strpos($url, '//') === 0) {
            return $url;
        }
        
        // Если URL относительный, возвращаем как есть
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            return $url;
        }
        
        // Конвертируем абсолютный URL в протокол-относительный
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            $path = $parsed['path'] ?? '';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
            return '//' . $parsed['host'] . $path . $query . $fragment;
        }
        
        return $url;
    }
    
    /**
     * Генерация URL админки
     * 
     * @param string $path Путь
     * @return string
     */
    public static function admin(string $path = ''): string {
        $protocol = self::getProtocol();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . '/admin' . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    /**
     * Генерация URL сайта
     * 
     * @param string $path Путь
     * @return string
     */
    public static function site(string $path = ''): string {
        $protocol = self::getProtocol();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    /**
     * Получение текущего URL
     * 
     * @param bool $withQuery Включать query string
     * @return string
     */
    public static function current(bool $withQuery = true): string {
        $protocol = self::getProtocol();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (!$withQuery && ($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $protocol . $host . $uri;
    }
    
    /**
     * Получение базового URL сайта
     * 
     * @param string $path Путь (опционально)
     * @return string
     */
    public static function base(string $path = ''): string {
        $protocol = self::getProtocol();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $baseUrl = $protocol . $host;
        if (!empty($path)) {
            $baseUrl .= '/' . ltrim($path, '/');
        }
        
        return $baseUrl;
    }
}

