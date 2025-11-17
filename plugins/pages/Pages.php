<?php
/**
 * Модуль для роботи зі сторінками
 * 
 * @package Plugins\Pages
 * @version 1.0.0
 */

declare(strict_types=1);

class Pages {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseHelper::getConnection();
    }
    
    /**
     * Отримання всіх сторінок з фільтрацією
     */
    public function getPages(array $filters = [], int $page = 1, int $perPage = 20): array {
        if (!$this->db) {
            return ['pages' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
        
        try {
            $where = ['1=1'];
            $params = [];
            
            // Фільтр по статусу
            if (!empty($filters['status'])) {
                $where[] = 'p.status = ?';
                $params[] = $filters['status'];
            }
            
            // Фільтр по категорії
            if (!empty($filters['category_id'])) {
                $where[] = 'p.category_id = ?';
                $params[] = (int)$filters['category_id'];
            }
            
            // Пошук
            if (!empty($filters['search'])) {
                $where[] = '(p.title LIKE ? OR p.content LIKE ? OR p.slug LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Підрахунок загальної кількості
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM pages p 
                WHERE {$whereClause}
            ");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            
            // Отримання сторінок
            $orderBy = $filters['order_by'] ?? 'p.created_at';
            $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
            $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'DESC';
            
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    pc.name as category_name,
                    pc.slug as category_slug,
                    u.username as author_name
                FROM pages p
                LEFT JOIN page_categories pc ON p.category_id = pc.id
                LEFT JOIN users u ON p.author_id = u.id
                WHERE {$whereClause}
                ORDER BY {$orderBy} {$orderDir}
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $perPage;
            $params[] = $offset;
            $stmt->execute($params);
            
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPages = (int)ceil($total / $perPage);
            
            return [
                'pages' => $pages,
                'total' => $total,
                'page' => $page,
                'pages' => $totalPages
            ];
        } catch (Exception $e) {
            error_log("Pages::getPages error: " . $e->getMessage());
            return ['pages' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
    }
    
    /**
     * Отримання сторінки за ID
     */
    public function getPage(int $id): ?array {
        if (!$this->db || $id <= 0) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    pc.name as category_name,
                    pc.slug as category_slug,
                    u.username as author_name
                FROM pages p
                LEFT JOIN page_categories pc ON p.category_id = pc.id
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            return $page ?: null;
        } catch (Exception $e) {
            error_log("Pages::getPage error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Отримання сторінки за slug
     */
    public function getPageBySlug(string $slug): ?array {
        if (!$this->db || empty($slug)) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    pc.name as category_name,
                    pc.slug as category_slug,
                    u.username as author_name
                FROM pages p
                LEFT JOIN page_categories pc ON p.category_id = pc.id
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.slug = ? AND p.status = 'publish'
            ");
            $stmt->execute([$slug]);
            
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            return $page ?: null;
        } catch (Exception $e) {
            error_log("Pages::getPageBySlug error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Створення сторінки
     */
    public function createPage(array $data): array {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Помилка підключення до БД'];
        }
        
        try {
            // Валідація
            if (empty($data['title'])) {
                return ['success' => false, 'error' => 'Назва сторінки обов\'язкова'];
            }
            
            // Генерація slug якщо не вказано
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            } else {
                $data['slug'] = $this->sanitizeSlug($data['slug']);
            }
            
            // Перевірка унікальності slug
            if ($this->slugExists($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['slug']);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO pages (
                    title, slug, content, excerpt, status, category_id, author_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['slug'],
                $data['content'] ?? '',
                $data['excerpt'] ?? '',
                $data['status'] ?? 'draft',
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                $data['author_id'] ?? $this->getCurrentUserId()
            ]);
            
            if ($result) {
                $pageId = (int)$this->db->lastInsertId();
                return ['success' => true, 'id' => $pageId];
            }
            
            return ['success' => false, 'error' => 'Помилка створення сторінки'];
        } catch (Exception $e) {
            error_log("Pages::createPage error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Оновлення сторінки
     */
    public function updatePage(int $id, array $data): array {
        if (!$this->db || $id <= 0) {
            return ['success' => false, 'error' => 'Невірний ID сторінки'];
        }
        
        try {
            // Валідація
            if (empty($data['title'])) {
                return ['success' => false, 'error' => 'Назва сторінки обов\'язкова'];
            }
            
            // Генерація slug якщо не вказано
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            } else {
                $data['slug'] = $this->sanitizeSlug($data['slug']);
            }
            
            // Перевірка унікальності slug (крім поточної сторінки)
            if ($this->slugExists($data['slug'], $id)) {
                $data['slug'] = $this->generateUniqueSlug($data['slug']);
            }
            
            $stmt = $this->db->prepare("
                UPDATE pages SET
                    title = ?,
                    slug = ?,
                    content = ?,
                    excerpt = ?,
                    status = ?,
                    category_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['slug'],
                $data['content'] ?? '',
                $data['excerpt'] ?? '',
                $data['status'] ?? 'draft',
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                $id
            ]);
            
            if ($result) {
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Помилка оновлення сторінки'];
        } catch (Exception $e) {
            error_log("Pages::updatePage error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Видалення сторінки
     */
    public function deletePage(int $id): array {
        if (!$this->db || $id <= 0) {
            return ['success' => false, 'error' => 'Невірний ID сторінки'];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM pages WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Помилка видалення сторінки'];
        } catch (Exception $e) {
            error_log("Pages::deletePage error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отримання всіх категорій
     */
    public function getCategories(): array {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    pc.*,
                    COUNT(p.id) as pages_count
                FROM page_categories pc
                LEFT JOIN pages p ON pc.id = p.category_id
                GROUP BY pc.id
                ORDER BY pc.name ASC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Pages::getCategories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Отримання категорії за ID
     */
    public function getCategory(int $id): ?array {
        if (!$this->db || $id <= 0) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM page_categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            return $category ?: null;
        } catch (Exception $e) {
            error_log("Pages::getCategory error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Створення категорії
     */
    public function createCategory(array $data): array {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Помилка підключення до БД'];
        }
        
        try {
            if (empty($data['name'])) {
                return ['success' => false, 'error' => 'Назва категорії обов\'язкова'];
            }
            
            // Генерація slug
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            } else {
                $data['slug'] = $this->sanitizeSlug($data['slug']);
            }
            
            // Перевірка унікальності slug
            if ($this->categorySlugExists($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], 'page_categories');
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO page_categories (name, slug, description, parent_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['slug'],
                $data['description'] ?? '',
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null
            ]);
            
            if ($result) {
                $categoryId = (int)$this->db->lastInsertId();
                return ['success' => true, 'id' => $categoryId];
            }
            
            return ['success' => false, 'error' => 'Помилка створення категорії'];
        } catch (Exception $e) {
            error_log("Pages::createCategory error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Оновлення категорії
     */
    public function updateCategory(int $id, array $data): array {
        if (!$this->db || $id <= 0) {
            return ['success' => false, 'error' => 'Невірний ID категорії'];
        }
        
        try {
            if (empty($data['name'])) {
                return ['success' => false, 'error' => 'Назва категорії обов\'язкова'];
            }
            
            // Генерація slug
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            } else {
                $data['slug'] = $this->sanitizeSlug($data['slug']);
            }
            
            // Перевірка унікальності slug
            if ($this->categorySlugExists($data['slug'], $id)) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], 'page_categories');
            }
            
            $stmt = $this->db->prepare("
                UPDATE page_categories SET
                    name = ?,
                    slug = ?,
                    description = ?,
                    parent_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['slug'],
                $data['description'] ?? '',
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                $id
            ]);
            
            if ($result) {
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Помилка оновлення категорії'];
        } catch (Exception $e) {
            error_log("Pages::updateCategory error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Видалення категорії
     */
    public function deleteCategory(int $id): array {
        if (!$this->db || $id <= 0) {
            return ['success' => false, 'error' => 'Невірний ID категорії'];
        }
        
        try {
            // Перевірка на наявність сторінок у категорії
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM pages WHERE category_id = ?");
            $stmt->execute([$id]);
            $pagesCount = (int)$stmt->fetchColumn();
            
            if ($pagesCount > 0) {
                return ['success' => false, 'error' => 'Неможливо видалити категорію, в якій є сторінки'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM page_categories WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Помилка видалення категорії'];
        } catch (Exception $e) {
            error_log("Pages::deleteCategory error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Помилка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Генерація slug з тексту
     */
    private function generateSlug(string $text): string {
        // Транслітерація та очищення
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Очищення slug
     */
    private function sanitizeSlug(string $slug): string {
        $slug = mb_strtolower($slug, 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}-]/u', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Перевірка існування slug
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool {
        try {
            $sql = "SELECT COUNT(*) FROM pages WHERE slug = ?";
            $params = [$slug];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Перевірка існування slug категорії
     */
    private function categorySlugExists(string $slug, ?int $excludeId = null): bool {
        try {
            $sql = "SELECT COUNT(*) FROM page_categories WHERE slug = ?";
            $params = [$slug];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Генерація унікального slug
     */
    private function generateUniqueSlug(string $slug, string $table = 'pages'): string {
        $baseSlug = $slug;
        $counter = 1;
        
        while (true) {
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ?");
                $stmt->execute([$slug]);
                
                if ((int)$stmt->fetchColumn() === 0) {
                    return $slug;
                }
                
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            } catch (Exception $e) {
                return $baseSlug . '-' . time();
            }
        }
    }
    
    /**
     * Отримання ID поточного користувача
     */
    private function getCurrentUserId(): int {
        // Отримуємо ID користувача з сесії
        $userId = Session::get('admin_user_id');
        return $userId ? (int)$userId : 1; // Fallback на 1, якщо не знайдено
    }
}

