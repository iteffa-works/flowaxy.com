<?php
/**
 * Фабрика для создания менеджеров хранилища
 * Унифицированный доступ к различным типам хранилища
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

class StorageFactory {
    public const TYPE_COOKIE = 'cookie';
    public const TYPE_SESSION = 'session';
    public const TYPE_LOCAL_STORAGE = 'localStorage';
    public const TYPE_SESSION_STORAGE = 'sessionStorage';
    
    /**
     * Получение менеджера хранилища по типу
     * 
     * @param string $type Тип хранилища (cookie, session, localStorage, sessionStorage)
     * @param string $prefix Префикс для ключей (опционально)
     * @return StorageInterface|null
     */
    public static function get(string $type, string $prefix = ''): ?StorageInterface {
        switch ($type) {
            case self::TYPE_COOKIE:
                $manager = CookieManager::getInstance();
                if ($prefix) {
                    $manager->setDefaultOptions(['path' => $prefix]);
                }
                return $manager;
                
            case self::TYPE_SESSION:
                $manager = SessionManager::getInstance();
                if ($prefix) {
                    $manager->setPrefix($prefix);
                }
                return $manager;
                
            case self::TYPE_LOCAL_STORAGE:
                $manager = StorageManager::getInstance();
                $manager->setType('localStorage');
                if ($prefix) {
                    $manager->setPrefix($prefix);
                }
                return $manager;
                
            case self::TYPE_SESSION_STORAGE:
                $manager = StorageManager::getInstance();
                $manager->setType('sessionStorage');
                if ($prefix) {
                    $manager->setPrefix($prefix);
                }
                return $manager;
                
            default:
                return null;
        }
    }
    
    /**
     * Получение менеджера cookies
     * 
     * @param string $prefix Префикс для ключей (опционально)
     * @return CookieManager
     */
    public static function cookie(string $prefix = ''): CookieManager {
        return self::get(self::TYPE_COOKIE, $prefix) ?? CookieManager::getInstance();
    }
    
    /**
     * Получение менеджера сессий
     * 
     * @param string $prefix Префикс для ключей (опционально)
     * @return SessionManager
     */
    public static function session(string $prefix = ''): SessionManager {
        $manager = SessionManager::getInstance();
        if ($prefix) {
            $manager->setPrefix($prefix);
        }
        return $manager;
    }
    
    /**
     * Получение менеджера localStorage
     * 
     * @param string $prefix Префикс для ключей (опционально)
     * @return StorageManager
     */
    public static function localStorage(string $prefix = ''): StorageManager {
        $manager = StorageManager::getInstance();
        $manager->setType('localStorage');
        if ($prefix) {
            $manager->setPrefix($prefix);
        }
        return $manager;
    }
    
    /**
     * Получение менеджера sessionStorage
     * 
     * @param string $prefix Префикс для ключей (опционально)
     * @return StorageManager
     */
    public static function sessionStorage(string $prefix = ''): StorageManager {
        $manager = StorageManager::getInstance();
        $manager->setType('sessionStorage');
        if ($prefix) {
            $manager->setPrefix($prefix);
        }
        return $manager;
    }
}

