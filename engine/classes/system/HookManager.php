<?php
/**
 * Менеджер хуків та подій системи
 * 
 * Управляє фільтрами (filters) та подіями (actions)
 * Фільтри - модифікують дані та повертають результат
 * Події - виконують дії без повернення даних
 * 
 * @package Engine\System
 * @version 1.0.0
 */

declare(strict_types=1);

class HookManager {
    private static ?HookManager $instance = null;
    
    /**
     * Сховище хуків
     * Структура: ['hook_name' => [['callback' => callable, 'priority' => int, 'condition' => callable|null, 'type' => 'filter'|'action']]]
     */
    private array $hooks = [];
    
    /**
     * Лічильник викликів хуків (для відлагодження)
     */
    private array $hookCalls = [];
    
    /**
     * Приватний конструктор (Singleton)
     */
    private function __construct() {
    }
    
    /**
     * Отримати екземпляр HookManager
     * 
     * @return HookManager
     */
    public static function getInstance(): HookManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Додати фільтр (filter)
     * Фільтри модифікують дані та повертають результат
     * 
     * @param string $hookName Ім'я хука
     * @param callable $callback Функція зворотного виклику
     * @param int $priority Пріоритет (менше = раніше виконується, за замовчуванням 10)
     * @param callable|null $condition Умова виконання (опціонально)
     * @return void
     */
    public function addFilter(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
        $this->addHook($hookName, $callback, $priority, $condition, 'filter');
    }
    
    /**
     * Додати подію (action)
     * Події виконують дії без повернення даних
     * 
     * @param string $hookName Ім'я хука
     * @param callable $callback Функція зворотного виклику
     * @param int $priority Пріоритет (менше = раніше виконується, за замовчуванням 10)
     * @param callable|null $condition Умова виконання (опціонально)
     * @return void
     */
    public function addAction(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
        $this->addHook($hookName, $callback, $priority, $condition, 'action');
    }
    
    /**
     * Універсальний метод додавання хука
     * 
     * @param string $hookName Ім'я хука
     * @param callable $callback Функція зворотного виклику
     * @param int $priority Пріоритет
     * @param callable|null $condition Умова виконання
     * @param string $type Тип хука ('filter' або 'action')
     * @return void
     */
    public function addHook(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null, string $type = 'filter'): void {
        if (empty($hookName)) {
            return;
        }
        
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
            'condition' => $condition,
            'type' => $type
        ];
        
        // Сортуємо за пріоритетом (оптимізовано - сортуємо тільки якщо потрібно)
        if (count($this->hooks[$hookName]) > 1) {
            usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }
    
    /**
     * Застосувати фільтр (filter)
     * Проходить через всі зареєстровані фільтри та модифікує дані
     * 
     * @param string $hookName Ім'я хука
     * @param mixed $data Дані для фільтрації
     * @param mixed ...$args Додаткові аргументи
     * @return mixed Відфільтровані дані
     */
    public function applyFilter(string $hookName, $data = null, ...$args) {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return $data;
        }
        
        $this->incrementHookCall($hookName);
        
        foreach ($this->hooks[$hookName] as $hook) {
            // Перевіряємо умову виконання
            if ($hook['condition'] !== null && !$this->checkCondition($hook['condition'], $data, ...$args)) {
                continue;
            }
            
            // Фільтри повинні повертати дані
            if ($hook['type'] === 'action') {
                // Якщо це подія, просто викликаємо без повернення даних
                try {
                    call_user_func($hook['callback'], $data, ...$args);
                } catch (Exception $e) {
                    $this->logHookError($hookName, $e);
                }
                continue;
            }
            
            // Виконуємо фільтр
            if (!is_callable($hook['callback'])) {
                continue;
            }
            
            try {
                $result = call_user_func($hook['callback'], $data, ...$args);
                
                // Якщо результат не null, використовуємо його як нові дані
                if ($result !== null) {
                    $data = $result;
                }
            } catch (Exception $e) {
                $this->logHookError($hookName, $e);
            } catch (Error $e) {
                $this->logHookError($hookName, $e);
            }
        }
        
        return $data;
    }
    
    /**
     * Виконати подію (action)
     * Викликає всі зареєстровані обробники події
     * 
     * @param string $hookName Ім'я хука
     * @param mixed ...$args Аргументи для передачі в обробники
     * @return void
     */
    public function doAction(string $hookName, ...$args): void {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return;
        }
        
        $this->incrementHookCall($hookName);
        
        foreach ($this->hooks[$hookName] as $hook) {
            // Перевіряємо умову виконання
            if ($hook['condition'] !== null && !$this->checkCondition($hook['condition'], ...$args)) {
                continue;
            }
            
            if (!is_callable($hook['callback'])) {
                continue;
            }
            
            try {
                call_user_func($hook['callback'], ...$args);
            } catch (Exception $e) {
                $this->logHookError($hookName, $e);
            } catch (Error $e) {
                $this->logHookError($hookName, $e);
            }
        }
    }
    
    /**
     * Універсальний метод виконання хука (для зворотної сумісності)
     * Автоматично визначає тип хука
     * 
     * @param string $hookName Ім'я хука
     * @param mixed $data Дані (для фільтрів) або аргументи (для подій)
     * @return mixed Результат для фільтрів, null для подій
     */
    public function doHook(string $hookName, $data = null) {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return $data;
        }
        
        // Визначаємо тип хука за першим зареєстрованим
        $firstHook = $this->hooks[$hookName][0] ?? null;
        $isAction = $firstHook && $firstHook['type'] === 'action';
        
        if ($isAction) {
            $this->doAction($hookName, $data);
            return null;
        } else {
            return $this->applyFilter($hookName, $data);
        }
    }
    
    /**
     * Перевірити існування хука
     * 
     * @param string $hookName Ім'я хука
     * @return bool
     */
    public function hasHook(string $hookName): bool {
        return !empty($hookName) && isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }
    
    /**
     * Видалити хук
     * 
     * @param string $hookName Ім'я хука
     * @param callable|null $callback Конкретний callback для видалення (якщо null - видаляє всі)
     * @return bool Чи успішно видалено
     */
    public function removeHook(string $hookName, ?callable $callback = null): bool {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return false;
        }
        
        if ($callback === null) {
            // Видаляємо всі хуки з цим ім'ям
            unset($this->hooks[$hookName]);
            return true;
        }
        
        // Видаляємо конкретний callback
        $removed = false;
        foreach ($this->hooks[$hookName] as $key => $hook) {
            if ($this->callbacksEqual($hook['callback'], $callback)) {
                unset($this->hooks[$hookName][$key]);
                $removed = true;
            }
        }
        
        if ($removed) {
            // Переіндексуємо масив
            $this->hooks[$hookName] = array_values($this->hooks[$hookName]);
            
            // Якщо хуків не залишилося, видаляємо ключ
            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
        
        return $removed;
    }
    
    /**
     * Отримати всі зареєстровані хуки
     * 
     * @return array
     */
    public function getAllHooks(): array {
        return $this->hooks;
    }
    
    /**
     * Отримати хуки за ім'ям
     * 
     * @param string $hookName Ім'я хука
     * @return array
     */
    public function getHooks(string $hookName): array {
        return $this->hooks[$hookName] ?? [];
    }
    
    /**
     * Очистити всі хуки
     * 
     * @return void
     */
    public function clearHooks(): void {
        $this->hooks = [];
        $this->hookCalls = [];
    }
    
    /**
     * Очистити хуки за ім'ям
     * 
     * @param string $hookName Ім'я хука
     * @return void
     */
    public function clearHook(string $hookName): void {
        if (isset($this->hooks[$hookName])) {
            unset($this->hooks[$hookName]);
            unset($this->hookCalls[$hookName]);
        }
    }
    
    /**
     * Отримати статистику викликів хуків (для відлагодження)
     * 
     * @return array
     */
    public function getHookStats(): array {
        return [
            'total_hooks' => count($this->hooks),
            'hook_calls' => $this->hookCalls,
            'hooks_list' => array_keys($this->hooks)
        ];
    }
    
    /**
     * Перевірити умову виконання хука
     * 
     * @param callable $condition Умова
     * @param mixed ...$args Аргументи
     * @return bool
     */
    private function checkCondition(callable $condition, ...$args): bool {
        try {
            return (bool)call_user_func($condition, ...$args);
        } catch (Exception $e) {
            $this->logHookError('condition_check', $e);
            return false;
        }
    }
    
    /**
     * Порівняти два callback на рівність
     * 
     * @param callable $callback1
     * @param callable $callback2
     * @return bool
     */
    private function callbacksEqual(callable $callback1, callable $callback2): bool {
        if (is_array($callback1) && is_array($callback2)) {
            return $callback1 === $callback2;
        }
        
        if (is_string($callback1) && is_string($callback2)) {
            return $callback1 === $callback2;
        }
        
        // Для об'єктів порівнюємо за посиланням
        if (is_object($callback1) && is_object($callback2)) {
            return $callback1 === $callback2;
        }
        
        return false;
    }
    
    /**
     * Збільшити лічильник викликів хука
     * 
     * @param string $hookName
     * @return void
     */
    private function incrementHookCall(string $hookName): void {
        $this->hookCalls[$hookName] = ($this->hookCalls[$hookName] ?? 0) + 1;
    }
    
    /**
     * Логувати помилку хука
     * 
     * @param string $hookName
     * @param Throwable $e
     * @return void
     */
    private function logHookError(string $hookName, Throwable $e): void {
        $message = "Помилка виконання хука '{$hookName}': " . $e->getMessage();
        
        if (function_exists('logger')) {
            try {
                logger()->logError($message, [
                    'hook' => $hookName,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (Exception $logException) {
                error_log($message);
            }
        } else {
            error_log($message);
        }
    }
}

