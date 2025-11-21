<?php
/**
 * Менеджер Webhooks
 * Управління webhooks для відправки сповіщень зовнішнім сервісам
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../../interfaces/DatabaseInterface.php';

class WebhookManager {
    private DatabaseInterface $db;
    private const TABLE_NAME = 'webhooks';
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
        // Таблиця створюється установщиком, створення видалено з ядра
    }
    
    /**
     * Генерація секретного ключа
     * 
     * @return string
     */
    public function generateSecret(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Створення нового webhook
     * 
     * @param string $name Назва
     * @param string $url URL для відправки
     * @param array $events Події для відстеження
     * @param string|null $secret Секретний ключ (якщо null, генерується автоматично)
     * @return array
     */
    public function create(string $name, string $url, array $events = [], ?string $secret = null): array {
        if (empty($secret)) {
            $secret = $this->generateSecret();
        }
        
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO `" . self::TABLE_NAME . "` 
                (name, url, secret, events, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            
            $eventsJson = !empty($events) ? Json::stringify($events) : null;
            
            $stmt->execute([
                $name,
                $url,
                Hash::sha256($secret),
                $eventsJson
            ]);
            
            $id = (int)$conn->lastInsertId();
            
            return [
                'id' => $id,
                'name' => $name,
                'url' => $url,
                'secret' => $secret, // Повертаємо тільки один раз!
                'events' => $events,
                'is_active' => true,
                'success_count' => 0,
                'failure_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("WebhookManager::create() error: " . $e->getMessage());
            throw new Exception("Не вдалося створити webhook: " . $e->getMessage());
        }
    }
    
    /**
     * Отримання всіх webhooks
     * 
     * @param bool $activeOnly Тільки активні
     * @return array
     */
    public function getAll(bool $activeOnly = false): array {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT id, name, url, events, is_active, last_triggered_at, 
                       success_count, failure_count, created_at
                FROM `" . self::TABLE_NAME . "`
            ";
            
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($webhooks as &$webhook) {
                $webhook['events'] = !empty($webhook['events']) 
                    ? Json::parse($webhook['events']) 
                    : [];
                $webhook['id'] = (int)$webhook['id'];
                $webhook['is_active'] = (bool)$webhook['is_active'];
                $webhook['success_count'] = (int)$webhook['success_count'];
                $webhook['failure_count'] = (int)$webhook['failure_count'];
            }
            
            return $webhooks;
        } catch (Exception $e) {
            error_log("WebhookManager::getAll() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение webhooks для конкретного события
     * 
     * @param string $event Название события
     * @return array
     */
    public function getForEvent(string $event): array {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT id, name, url, secret, events
                FROM `" . self::TABLE_NAME . "`
                WHERE is_active = 1
                AND (events IS NULL OR events = '' OR JSON_CONTAINS(events, ?))
            ");
            
            $eventJson = Json::stringify($event);
            $stmt->execute([$eventJson]);
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($webhooks as &$webhook) {
                // Не возвращаем секрет, только хеш
                unset($webhook['secret']);
                $webhook['events'] = !empty($webhook['events']) 
                    ? Json::parse($webhook['events']) 
                    : [];
                $webhook['id'] = (int)$webhook['id'];
            }
            
            return $webhooks;
        } catch (Exception $e) {
            error_log("WebhookManager::getForEvent() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение webhook по ID
     * 
     * @param int $id ID webhook
     * @return array|null
     */
    public function get(int $id): ?array {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT id, name, url, events, is_active, last_triggered_at, 
                       success_count, failure_count, created_at
                FROM `" . self::TABLE_NAME . "`
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($webhook) {
                $webhook['events'] = !empty($webhook['events']) 
                    ? Json::parse($webhook['events']) 
                    : [];
                $webhook['id'] = (int)$webhook['id'];
                $webhook['is_active'] = (bool)$webhook['is_active'];
                $webhook['success_count'] = (int)$webhook['success_count'];
                $webhook['failure_count'] = (int)$webhook['failure_count'];
                return $webhook;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("WebhookManager::get() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получение секрета для webhook (только хеш используется для проверки)
     * 
     * @param int $id ID webhook
     * @return string|null Хеш секрета
     */
    private function getSecretHash(int $id): ?string {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("SELECT secret FROM `" . self::TABLE_NAME . "` WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['secret'] ?? null;
        } catch (Exception $e) {
            error_log("WebhookManager::getSecretHash() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Проверка секрета webhook
     * 
     * @param int $id ID webhook
     * @param string $secret Секрет для проверки
     * @return bool
     */
    public function validateSecret(int $id, string $secret): bool {
        $secretHash = $this->getSecretHash($id);
        if ($secretHash === null) {
            return false;
        }
        
        return Hash::equals($secretHash, Hash::sha256($secret));
    }
    
    /**
     * Обновление webhook
     * 
     * @param int $id ID webhook
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update(int $id, array $data): bool {
        try {
            $conn = $this->db->getConnection();
            $fields = [];
            $values = [];
            
            $allowedFields = ['name', 'url', 'events', 'is_active'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = ?";
                    if ($field === 'events') {
                        $values[] = !empty($data[$field]) ? Json::stringify($data[$field]) : null;
                    } else {
                        $values[] = $data[$field];
                    }
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $id;
            $sql = "UPDATE `" . self::TABLE_NAME . "` SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("WebhookManager::update() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление webhook
     * 
     * @param int $id ID webhook
     * @return bool
     */
    public function delete(int $id): bool {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("DELETE FROM `" . self::TABLE_NAME . "` WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("WebhookManager::delete() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновление статистики после отправки
     * 
     * @param int $id ID webhook
     * @param bool $success Успешная ли отправка
     */
    public function updateStats(int $id, bool $success): void {
        try {
            $conn = $this->db->getConnection();
            $field = $success ? 'success_count' : 'failure_count';
            $stmt = $conn->prepare("
                UPDATE `" . self::TABLE_NAME . "`
                SET {$field} = {$field} + 1, last_triggered_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("WebhookManager::updateStats() error: " . $e->getMessage());
        }
    }
}

