<?php
/**
 * Менеджер хуков и событий системы
 * 
 * Управляет фильтрами (filters) и событиями (actions)
 * Фильтры - модифицируют данные и возвращают результат
 * События - выполняют действия без возврата данных
 * 
 * @package Engine\System
 * @version 1.0.0
 */

declare(strict_types=1);

class HookManager {
    private static ?HookManager $instance = null;
    
    /**
     * Хранилище хуков
     * Структура: ['hook_name' => [['callback' => callable, 'priority' => int, 'condition' => callable|null, 'type' => 'filter'|'action']]]
     */
    private array $hooks = [];
    
    /**
     * Счетчик вызовов хуков (для отладки)
     */
    private array $hookCalls = [];
    
    /**
     * Приватный конструктор (Singleton)
     */
    private function __construct() {
    }
    
    /**
     * Получить экземпляр HookManager
     */
    public static function getInstance(): HookManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Добавить фильтр (filter)
     * Фильтры модифицируют данные и возвращают результат
     * 
     * @param string $hookName Имя хука
     * @param callable $callback Функция обратного вызова
     * @param int $priority Приоритет (меньше = раньше выполняется, по умолчанию 10)
     * @param callable|null $condition Условие выполнения (опционально)
     * @return void
     */
    public function addFilter(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
        $this->addHook($hookName, $callback, $priority, $condition, 'filter');
    }
    
    /**
     * Добавить событие (action)
     * События выполняют действия без возврата данных
     * 
     * @param string $hookName Имя хука
     * @param callable $callback Функция обратного вызова
     * @param int $priority Приоритет (меньше = раньше выполняется, по умолчанию 10)
     * @param callable|null $condition Условие выполнения (опционально)
     * @return void
     */
    public function addAction(string $hookName, callable $callback, int $priority = 10, ?callable $condition = null): void {
        $this->addHook($hookName, $callback, $priority, $condition, 'action');
    }
    
    /**
     * Универсальный метод добавления хука
     * 
     * @param string $hookName Имя хука
     * @param callable $callback Функция обратного вызова
     * @param int $priority Приоритет
     * @param callable|null $condition Условие выполнения
     * @param string $type Тип хука ('filter' или 'action')
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
        
        // Сортируем по приоритету
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }
    
    /**
     * Применить фильтр (filter)
     * Проходит через все зарегистрированные фильтры и модифицирует данные
     * 
     * @param string $hookName Имя хука
     * @param mixed $data Данные для фильтрации
     * @param mixed ...$args Дополнительные аргументы
     * @return mixed Отфильтрованные данные
     */
    public function applyFilter(string $hookName, $data = null, ...$args) {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return $data;
        }
        
        $this->incrementHookCall($hookName);
        
        foreach ($this->hooks[$hookName] as $hook) {
            // Проверяем условие выполнения
            if ($hook['condition'] !== null && !$this->checkCondition($hook['condition'], $data, ...$args)) {
                continue;
            }
            
            // Фильтры должны возвращать данные
            if ($hook['type'] === 'action') {
                // Если это событие, просто вызываем без возврата данных
                try {
                    call_user_func($hook['callback'], $data, ...$args);
                } catch (Exception $e) {
                    $this->logHookError($hookName, $e);
                }
                continue;
            }
            
            // Выполняем фильтр
            if (!is_callable($hook['callback'])) {
                continue;
            }
            
            try {
                $result = call_user_func($hook['callback'], $data, ...$args);
                
                // Если результат не null, используем его как новые данные
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
     * Выполнить событие (action)
     * Вызывает все зарегистрированные обработчики события
     * 
     * @param string $hookName Имя хука
     * @param mixed ...$args Аргументы для передачи в обработчики
     * @return void
     */
    public function doAction(string $hookName, ...$args): void {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return;
        }
        
        $this->incrementHookCall($hookName);
        
        foreach ($this->hooks[$hookName] as $hook) {
            // Проверяем условие выполнения
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
     * Универсальный метод выполнения хука (для обратной совместимости)
     * Автоматически определяет тип хука
     * 
     * @param string $hookName Имя хука
     * @param mixed $data Данные (для фильтров) или аргументы (для событий)
     * @return mixed Результат для фильтров, null для событий
     */
    public function doHook(string $hookName, $data = null) {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return $data;
        }
        
        // Определяем тип хука по первому зарегистрированному
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
     * Проверить существование хука
     * 
     * @param string $hookName Имя хука
     * @return bool
     */
    public function hasHook(string $hookName): bool {
        return !empty($hookName) && isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }
    
    /**
     * Удалить хук
     * 
     * @param string $hookName Имя хука
     * @param callable|null $callback Конкретный callback для удаления (если null - удаляет все)
     * @return bool Успешно ли удалено
     */
    public function removeHook(string $hookName, ?callable $callback = null): bool {
        if (empty($hookName) || !isset($this->hooks[$hookName])) {
            return false;
        }
        
        if ($callback === null) {
            // Удаляем все хуки с этим именем
            unset($this->hooks[$hookName]);
            return true;
        }
        
        // Удаляем конкретный callback
        $removed = false;
        foreach ($this->hooks[$hookName] as $key => $hook) {
            if ($this->callbacksEqual($hook['callback'], $callback)) {
                unset($this->hooks[$hookName][$key]);
                $removed = true;
            }
        }
        
        if ($removed) {
            // Переиндексируем массив
            $this->hooks[$hookName] = array_values($this->hooks[$hookName]);
            
            // Если хуков не осталось, удаляем ключ
            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
        
        return $removed;
    }
    
    /**
     * Получить все зарегистрированные хуки
     * 
     * @return array
     */
    public function getAllHooks(): array {
        return $this->hooks;
    }
    
    /**
     * Получить хуки по имени
     * 
     * @param string $hookName Имя хука
     * @return array
     */
    public function getHooks(string $hookName): array {
        return $this->hooks[$hookName] ?? [];
    }
    
    /**
     * Очистить все хуки
     * 
     * @return void
     */
    public function clearHooks(): void {
        $this->hooks = [];
    }
    
    /**
     * Очистить хуки по имени
     * 
     * @param string $hookName Имя хука
     * @return void
     */
    public function clearHook(string $hookName): void {
        if (isset($this->hooks[$hookName])) {
            unset($this->hooks[$hookName]);
        }
    }
    
    /**
     * Получить статистику вызовов хуков (для отладки)
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
     * Проверить условие выполнения хука
     * 
     * @param callable $condition Условие
     * @param mixed ...$args Аргументы
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
     * Сравнить два callback на равенство
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
        
        // Для объектов сравниваем по ссылке
        if (is_object($callback1) && is_object($callback2)) {
            return $callback1 === $callback2;
        }
        
        return false;
    }
    
    /**
     * Увеличить счетчик вызовов хука
     * 
     * @param string $hookName
     * @return void
     */
    private function incrementHookCall(string $hookName): void {
        if (!isset($this->hookCalls[$hookName])) {
            $this->hookCalls[$hookName] = 0;
        }
        $this->hookCalls[$hookName]++;
    }
    
    /**
     * Логировать ошибку хука
     * 
     * @param string $hookName
     * @param Throwable $e
     * @return void
     */
    private function logHookError(string $hookName, Throwable $e): void {
        $message = "Hook execution error for '{$hookName}': " . $e->getMessage();
        
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

