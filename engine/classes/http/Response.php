<?php
/**
 * Клас для роботи з HTTP відповідями
 * Відправка відповідей, редіректів, JSON та інших типів відповідей
 * 
 * @package Engine\Classes\Http
 * @version 1.1.0
 */

declare(strict_types=1);

class Response {
    private int $statusCode = 200;
    private array $headers = [];
    private ?string $content = null;
    
    /**
     * Встановлення статус коду
     * 
     * @param int $code Код статусу
     * @return self
     */
    public function status(int $code): self {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * Встановлення заголовка
     * 
     * @param string $name Ім'я заголовка
     * @param string $value Значення
     * @return self
     */
    public function header(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Встановлення вмісту
     * 
     * @param string $content Вміст
     * @return self
     */
    public function content(string $content): self {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Відправка відповіді
     * 
     * @return void
     */
    public function send(): void {
        if (headers_sent()) {
            return;
        }
        
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        if ($this->content !== null) {
            echo $this->content;
        }
    }
    
    /**
     * Відправка JSON відповіді
     * 
     * @param mixed $data Дані
     * @param int $statusCode Код статусу
     * @return void
     */
    public function json($data, int $statusCode = 200): void {
        $this->status($statusCode)->header('Content-Type', 'application/json; charset=UTF-8');
        $this->content(Json::stringify($data));
        $this->send();
    }
    
    /**
     * Редірект
     * 
     * @param string $url URL для редіректу
     * @param int $statusCode Код статусу (301 або 302)
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302): void {
        if (headers_sent()) {
            // Використовуємо Security клас для екранування
            echo '<script>window.location.href="' . Security::clean($url) . '";</script>';
            return;
        }
        
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Відправка файла для завантаження
     * 
     * @param string $filePath Шлях до файла
     * @param string|null $fileName Ім'я файла (якщо null, береться зі шляху)
     * @return void
     */
    public function download(string $filePath, ?string $fileName = null): void {
        if (!file_exists($filePath)) {
            $this->status(404)->send();
            return;
        }
        
        $fileName = $fileName ?? basename($filePath);
        $mimeType = MimeType::get($filePath);
        $fileSize = filesize($filePath);
        
        $this->header('Content-Type', $mimeType)
             ->header('Content-Disposition', 'attachment; filename="' . Security::sanitizeFilename($fileName) . '"')
             ->header('Content-Length', (string)$fileSize)
             ->send();
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Статичний метод: Встановлення заголовка
     * 
     * @param string $name Ім'я заголовка
     * @param string $value Значення
     * @return void
     */
    public static function setHeader(string $name, string $value): void {
        if (!headers_sent()) {
            header("{$name}: {$value}");
        }
    }
    
    /**
     * Статичний метод: Швидка JSON відповідь
     * 
     * @param mixed $data Дані
     * @param int $statusCode Код статусу
     * @return void
     */
    public static function jsonResponse($data, int $statusCode = 200): void {
        (new self())->json($data, $statusCode);
    }
    
    /**
     * Статичний метод: Швидкий редірект
     * Використовується через функцію redirectTo() з init.php
     * 
     * @param string $url URL
     * @param int $statusCode Код статусу
     * @return void
     */
    public static function redirectStatic(string $url, int $statusCode = 302): void {
        (new self())->redirect($url, $statusCode);
    }
}
