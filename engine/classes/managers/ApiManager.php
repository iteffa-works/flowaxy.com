<?php
/**
 * Менеджер API ключей
 * Управление API ключами для внешних приложений
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

class ApiManager {
    private Database $db;
    private const TABLE_NAME = 'api_keys';
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
        // Таблица создается установщиком, создание удалено из ядра
    }
    
    /**
     * Генерация нового API ключа
     * 
     * @return string
     */
    public function generateKey(): string {
        return 'flowaxy_' . bin2hex(random_bytes(32));
    }
    
    /**
     * Создание нового API ключа
     * 
     * @param string $name Название ключа
     * @param array $permissions Разрешения
     * @param string|null $expiresAt Дата истечения (формат: Y-m-d H:i:s)
     * @return array Массив с данными ключа (включая сам ключ)
     */
    public function createKey(string $name, array $permissions = [], ?string $expiresAt = null): array {
        $key = $this->generateKey();
        $keyHash = Hash::hash($key);
        $keyPreview = substr($key, 0, 4) . '...' . substr($key, -4);
        
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO `" . self::TABLE_NAME . "` 
                (name, key_hash, key_preview, permissions, expires_at, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            
            $permissionsJson = !empty($permissions) ? Json::stringify($permissions) : null;
            
            $stmt->execute([
                $name,
                $keyHash,
                $keyPreview,
                $permissionsJson,
                $expiresAt
            ]);
            
            $id = (int)$conn->lastInsertId();
            
            return [
                'id' => $id,
                'name' => $name,
                'key' => $key, // Возвращаем только один раз!
                'key_preview' => $keyPreview,
                'permissions' => $permissions,
                'expires_at' => $expiresAt,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("ApiManager::createKey() error: " . $e->getMessage());
            throw new Exception("Не удалось создать API ключ: " . $e->getMessage());
        }
    }
    
    /**
     * Проверка API ключа
     * 
     * @param string $key API ключ
     * @return array|null Данные ключа или null, если невалидный
     */
    public function validateKey(string $key): ?array {
        try {
            $conn = $this->db->getConnection();
            
            // Получаем все активные ключи для проверки
            $stmt = $conn->prepare("
                SELECT id, name, key_hash, key_preview, permissions, expires_at, is_active
                FROM `" . self::TABLE_NAME . "`
                WHERE is_active = 1
            ");
            $stmt->execute();
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($keys as $keyData) {
                if (Hash::equals($keyData['key_hash'], Hash::hash($key))) {
                    // Проверка срока действия
                    if ($keyData['expires_at'] !== null) {
                        $expiresAt = strtotime($keyData['expires_at']);
                        if ($expiresAt !== false && time() > $expiresAt) {
                            return null; // Ключ истек
                        }
                    }
                    
                    // Обновляем время последнего использования
                    $this->updateLastUsed((int)$keyData['id']);
                    
                    // Парсим разрешения
                    $permissions = !empty($keyData['permissions']) 
                        ? Json::parse($keyData['permissions']) 
                        : [];
                    
                    return [
                        'id' => (int)$keyData['id'],
                        'name' => $keyData['name'],
                        'key_preview' => $keyData['key_preview'],
                        'permissions' => $permissions,
                        'expires_at' => $keyData['expires_at'],
                        'is_active' => (bool)$keyData['is_active']
                    ];
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log("ApiManager::validateKey() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Обновление времени последнего использования
     * 
     * @param int $id ID ключа
     */
    private function updateLastUsed(int $id): void {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE `" . self::TABLE_NAME . "`
                SET last_used_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("ApiManager::updateLastUsed() error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение всех ключей
     * 
     * @return array
     */
    public function getAllKeys(): array {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT id, name, key_preview, permissions, last_used_at, expires_at, is_active, created_at
                FROM `" . self::TABLE_NAME . "`
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($keys as &$key) {
                $key['permissions'] = !empty($key['permissions']) 
                    ? Json::parse($key['permissions']) 
                    : [];
                $key['id'] = (int)$key['id'];
                $key['is_active'] = (bool)$key['is_active'];
            }
            
            return $keys;
        } catch (Exception $e) {
            error_log("ApiManager::getAllKeys() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение ключа по ID
     * 
     * @param int $id ID ключа
     * @return array|null
     */
    public function getKey(int $id): ?array {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT id, name, key_preview, permissions, last_used_at, expires_at, is_active, created_at
                FROM `" . self::TABLE_NAME . "`
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $key = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($key) {
                $key['permissions'] = !empty($key['permissions']) 
                    ? Json::parse($key['permissions']) 
                    : [];
                $key['id'] = (int)$key['id'];
                $key['is_active'] = (bool)$key['is_active'];
                return $key;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("ApiManager::getKey() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Удаление API ключа
     * 
     * @param int $id ID ключа
     * @return bool
     */
    public function deleteKey(int $id): bool {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("DELETE FROM `" . self::TABLE_NAME . "` WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("ApiManager::deleteKey() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Активация/деактивация ключа
     * 
     * @param int $id ID ключа
     * @param bool $isActive Статус
     * @return bool
     */
    public function setActive(int $id, bool $isActive): bool {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE `" . self::TABLE_NAME . "`
                SET is_active = ?
                WHERE id = ?
            ");
            return $stmt->execute([$isActive ? 1 : 0, $id]);
        } catch (Exception $e) {
            error_log("ApiManager::setActive() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка разрешения для ключа
     * 
     * @param array $keyData Данные ключа
     * @param string $permission Разрешение
     * @return bool
     */
    public function hasPermission(array $keyData, string $permission): bool {
        // Если разрешения не указаны, доступ ко всему
        if (empty($keyData['permissions'])) {
            return true;
        }
        
        return in_array($permission, $keyData['permissions'], true) 
            || in_array('*', $keyData['permissions'], true);
    }
}

