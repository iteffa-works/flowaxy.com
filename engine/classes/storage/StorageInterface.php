<?php
/**
 * Интерфейс для всех типов хранилища
 * Единый API для работы с cookies, sessions, localStorage, sessionStorage
 * 
 * @package Engine\Classes\Storage
 * @version 1.0.0
 */

declare(strict_types=1);

interface StorageInterface {
    /**
     * Получение значения из хранилища
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null);
    
    /**
     * Установка значения в хранилище
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return bool
     */
    public function set(string $key, $value): bool;
    
    /**
     * Проверка наличия ключа в хранилище
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Удаление значения из хранилища
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function remove(string $key): bool;
    
    /**
     * Получение всех данных из хранилища
     * 
     * @return array
     */
    public function all(): array;
    
    /**
     * Очистка всех данных из хранилища
     * 
     * @return bool
     */
    public function clear(): bool;
    
    /**
     * Получение нескольких значений по ключам
     * 
     * @param array $keys Массив ключей
     * @return array
     */
    public function getMultiple(array $keys): array;
    
    /**
     * Установка нескольких значений
     * 
     * @param array $values Массив ключ => значение
     * @return bool
     */
    public function setMultiple(array $values): bool;
    
    /**
     * Удаление нескольких значений
     * 
     * @param array $keys Массив ключей
     * @return bool
     */
    public function removeMultiple(array $keys): bool;
}

