<?php
/**
 * Поштовий клієнт плагін
 * 
 * @package Plugins
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/engine/classes/base/BasePlugin.php';

class MailClientPlugin extends BasePlugin {
    
    /**
     * Ініціалізація плагіна
     */
    public function init() {
        // Реєстрація хуків
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
    }
    
    /**
     * Активація плагіна
     */
    public function activate() {
        $this->createTables();
        $this->updateTables(); // Оновлюємо структуру якщо потрібно
    }
    
    /**
     * Деактивація плагіна
     */
    public function deactivate() {
        // Не видаляємо дані при деактивації
    }
    
    /**
     * Встановлення плагіна
     */
    public function install() {
        $this->createTables();
    }
    
    /**
     * Видалення плагіна
     */
    public function uninstall() {
        if (!$this->db) {
            return;
        }
        
        try {
            // Видаляємо таблиці (в правильном порядке из-за внешних ключей)
            $this->db->exec("DROP TABLE IF EXISTS mail_client_attachments");
            $this->db->exec("DROP TABLE IF EXISTS mail_client_emails");
            $this->db->exec("DROP TABLE IF EXISTS mail_client_folders");
        } catch (Exception $e) {
            error_log("MailClientPlugin uninstall error: " . $e->getMessage());
        }
    }
    
    /**
     * Створення таблиць
     */
    private function createTables() {
        if (!$this->db) {
            return;
        }
        
        try {
            // Таблиця для збереження листів
            $this->db->exec("CREATE TABLE IF NOT EXISTS mail_client_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id VARCHAR(255) UNIQUE,
                folder VARCHAR(50) DEFAULT 'inbox',
                `from` VARCHAR(255),
                `to` VARCHAR(255),
                cc VARCHAR(255),
                bcc VARCHAR(255),
                subject TEXT,
                body TEXT,
                body_html TEXT,
                date_received DATETIME,
                date_sent DATETIME,
                is_read TINYINT(1) DEFAULT 0,
                is_starred TINYINT(1) DEFAULT 0,
                is_important TINYINT(1) DEFAULT 0,
                is_deleted TINYINT(1) DEFAULT 0,
                attachments TEXT,
                headers TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_folder (folder),
                INDEX idx_is_read (is_read),
                INDEX idx_is_starred (is_starred),
                INDEX idx_is_deleted (is_deleted),
                INDEX idx_date_received (date_received)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Таблиця для папок (якщо потрібно розширення)
            $this->db->exec("CREATE TABLE IF NOT EXISTS mail_client_folders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100),
                slug VARCHAR(100) UNIQUE,
                icon VARCHAR(50),
                order_num INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order (order_num)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Таблиця для вложений
            $this->db->exec("CREATE TABLE IF NOT EXISTS mail_client_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255),
                file_path VARCHAR(500),
                mime_type VARCHAR(100),
                file_size INT DEFAULT 0,
                content_id VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_id (email_id),
                FOREIGN KEY (email_id) REFERENCES mail_client_emails(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Оновлюємо структуру таблиць якщо потрібно
            $this->updateTables();
            
            // Додаємо стандартні папки якщо їх немає
            $folders = [
                ['inbox', 'Вхідні', 'fas fa-inbox'],
                ['starred', 'Із зірочкою', 'fas fa-star'],
                ['snoozed', 'Відкладені', 'fas fa-clock'],
                ['sent', 'Надіслані', 'fas fa-paper-plane'],
                ['drafts', 'Чернетки', 'fas fa-file-alt'],
                ['purchases', 'Покупки', 'fas fa-shopping-bag'],
                ['important', 'Важливі', 'fas fa-bookmark'],
                ['scheduled', 'Заплановано', 'fas fa-calendar-alt'],
                ['all', 'Уся пошта', 'fas fa-envelope'],
                ['spam', 'Спам', 'fas fa-exclamation-circle'],
                ['trash', 'Кошик', 'fas fa-trash']
            ];
            
            foreach ($folders as $folder) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO mail_client_folders (slug, name, icon) VALUES (?, ?, ?)");
                $stmt->execute([$folder[0], $folder[1], $folder[2]]);
            }
            
        } catch (Exception $e) {
            error_log("MailClientPlugin createTables error: " . $e->getMessage());
        }
    }
    
    /**
     * Додавання пункту меню в адмінку
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            'href' => adminUrl('mail-client'),
            'icon' => 'fas fa-envelope',
            'text' => 'Поштовий клієнт',
            'page' => 'mail-client',
            'order' => 15
        ];
        return $menu;
    }
    
    /**
     * Реєстрація маршруту адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        require_once __DIR__ . '/admin/MailClientAdminPage.php';
        $router->add(['GET', 'POST'], 'mail-client', 'MailClientAdminPage');
    }
    
    /**
     * Оновлення структури таблиць (додавання нових полів/таблиць)
     */
    private function updateTables() {
        if (!$this->db) {
            return;
        }
        
        try {
            // Перевіряємо чи існує таблиця вкладень
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'mail_client_attachments'");
            if (!$tableCheck || $tableCheck->rowCount() === 0) {
                // Створюємо таблицю вкладень якщо її немає
                $this->db->exec("CREATE TABLE IF NOT EXISTS mail_client_attachments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email_id INT NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    original_filename VARCHAR(255),
                    file_path VARCHAR(500),
                    mime_type VARCHAR(100),
                    file_size INT DEFAULT 0,
                    content_id VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email_id (email_id),
                    FOREIGN KEY (email_id) REFERENCES mail_client_emails(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
            
            // Перевіряємо чи є поле headers в таблиці листів
            $columnsCheck = $this->db->query("SHOW COLUMNS FROM mail_client_emails LIKE 'headers'");
            if (!$columnsCheck || $columnsCheck->rowCount() === 0) {
                // Додаємо поле headers якщо його немає
                $this->db->exec("ALTER TABLE mail_client_emails ADD COLUMN headers TEXT AFTER attachments");
            }
            
            // Перевіряємо чи є поля cc та bcc
            $ccCheck = $this->db->query("SHOW COLUMNS FROM mail_client_emails LIKE 'cc'");
            if (!$ccCheck || $ccCheck->rowCount() === 0) {
                $this->db->exec("ALTER TABLE mail_client_emails ADD COLUMN cc VARCHAR(255) AFTER `to`");
            }
            
            $bccCheck = $this->db->query("SHOW COLUMNS FROM mail_client_emails LIKE 'bcc'");
            if (!$bccCheck || $bccCheck->rowCount() === 0) {
                $this->db->exec("ALTER TABLE mail_client_emails ADD COLUMN bcc VARCHAR(255) AFTER cc");
            }
        } catch (Exception $e) {
            error_log("MailClientPlugin updateTables error: " . $e->getMessage());
        }
    }
}

