/**
 * Flowaxy CMS - Storage Manager (JavaScript)
 * Управление клиентским хранилищем (LocalStorage/SessionStorage)
 * 
 * @package Engine\Assets\Js
 * @version 1.0.0
 */

(function(window) {
    'use strict';
    
    /**
     * Менеджер хранилища
     */
    class StorageManager {
        constructor(type = 'localStorage', prefix = '') {
            this.type = type; // 'localStorage' или 'sessionStorage'
            this.prefix = prefix;
            this.storage = window[type];
            
            if (!this.storage) {
                console.warn(`Storage type "${type}" is not supported in this browser`);
                // Fallback на объект в памяти
                this.storage = {
                    _data: {},
                    getItem: (key) => this.storage._data[key] || null,
                    setItem: (key, value) => { this.storage._data[key] = value; },
                    removeItem: (key) => { delete this.storage._data[key]; },
                    clear: () => { this.storage._data = {}; },
                    key: (index) => Object.keys(this.storage._data)[index] || null,
                    get length() { return Object.keys(this.storage._data).length; }
                };
            }
        }
        
        /**
         * Формирование полного ключа с префиксом
         */
        getFullKey(key) {
            return this.prefix ? `${this.prefix}.${key}` : key;
        }
        
        /**
         * Получение значения из хранилища
         */
        get(key, defaultValue = null) {
            const fullKey = this.getFullKey(key);
            const value = this.storage.getItem(fullKey);
            
            if (value === null) {
                return defaultValue;
            }
            
            // Попытка декодировать JSON
            try {
                return JSON.parse(value);
            } catch (e) {
                return value;
            }
        }
        
        /**
         * Установка значения в хранилище
         */
        set(key, value) {
            const fullKey = this.getFullKey(key);
            
            // Кодируем значение в JSON, если это не строка
            const jsonValue = typeof value === 'string' ? value : JSON.stringify(value);
            
            try {
                this.storage.setItem(fullKey, jsonValue);
                return true;
            } catch (e) {
                console.error('Error setting storage value:', e);
                return false;
            }
        }
        
        /**
         * Проверка наличия ключа в хранилище
         */
        has(key) {
            const fullKey = this.getFullKey(key);
            return this.storage.getItem(fullKey) !== null;
        }
        
        /**
         * Удаление значения из хранилища
         */
        remove(key) {
            const fullKey = this.getFullKey(key);
            try {
                this.storage.removeItem(fullKey);
                return true;
            } catch (e) {
                console.error('Error removing storage value:', e);
                return false;
            }
        }
        
        /**
         * Получение всех данных из хранилища
         */
        all() {
            const result = {};
            const prefixLen = this.prefix ? this.prefix.length + 1 : 0;
            
            for (let i = 0; i < this.storage.length; i++) {
                const key = this.storage.key(i);
                
                if (!this.prefix || key.startsWith(this.prefix + '.')) {
                    const resultKey = prefixLen > 0 ? key.substring(prefixLen) : key;
                    result[resultKey] = this.get(resultKey);
                }
            }
            
            return result;
        }
        
        /**
         * Очистка всех данных из хранилища
         */
        clear(onlyPrefix = true) {
            if (!onlyPrefix || !this.prefix) {
                try {
                    this.storage.clear();
                    return true;
                } catch (e) {
                    console.error('Error clearing storage:', e);
                    return false;
                }
            }
            
            // Очищаем только ключи с префиксом
            const keysToRemove = [];
            for (let i = 0; i < this.storage.length; i++) {
                const key = this.storage.key(i);
                if (key.startsWith(this.prefix + '.')) {
                    keysToRemove.push(key);
                }
            }
            
            keysToRemove.forEach(key => this.storage.removeItem(key));
            return true;
        }
        
        /**
         * Получение нескольких значений по ключам
         */
        getMultiple(keys) {
            const result = {};
            keys.forEach(key => {
                result[key] = this.get(key);
            });
            return result;
        }
        
        /**
         * Установка нескольких значений
         */
        setMultiple(values) {
            let result = true;
            Object.keys(values).forEach(key => {
                if (!this.set(key, values[key])) {
                    result = false;
                }
            });
            return result;
        }
        
        /**
         * Удаление нескольких значений
         */
        removeMultiple(keys) {
            let result = true;
            keys.forEach(key => {
                if (!this.remove(key)) {
                    result = false;
                }
            });
            return result;
        }
        
        /**
         * Получение значения как JSON
         */
        getJson(key, defaultValue = null) {
            return this.get(key, defaultValue);
        }
        
        /**
         * Установка значения как JSON
         */
        setJson(key, value) {
            return this.set(key, value);
        }
        
        /**
         * Увеличение числового значения
         */
        increment(key, increment = 1) {
            const current = parseInt(this.get(key, 0)) || 0;
            const newValue = current + increment;
            this.set(key, newValue);
            return newValue;
        }
        
        /**
         * Уменьшение числового значения
         */
        decrement(key, decrement = 1) {
            return this.increment(key, -decrement);
        }
    }
    
    /**
     * Фабрика для создания менеджеров хранилища
     */
    class StorageFactory {
        static localStorage(prefix = '') {
            return new StorageManager('localStorage', prefix);
        }
        
        static sessionStorage(prefix = '') {
            return new StorageManager('sessionStorage', prefix);
        }
        
        static get(type, prefix = '') {
            if (type === 'localStorage') {
                return this.localStorage(prefix);
            } else if (type === 'sessionStorage') {
                return this.sessionStorage(prefix);
            }
            return null;
        }
    }
    
    // Экспорт в глобальную область видимости
    window.FlowaxyStorage = {
        StorageManager: StorageManager,
        StorageFactory: StorageFactory,
        localStorage: (prefix) => StorageFactory.localStorage(prefix),
        sessionStorage: (prefix) => StorageFactory.sessionStorage(prefix)
    };
    
    // Создаем удобные глобальные экземпляры
    window.Storage = StorageFactory.localStorage('flowaxy');
    window.SessionStorage = StorageFactory.sessionStorage('flowaxy');
    
})(window);

