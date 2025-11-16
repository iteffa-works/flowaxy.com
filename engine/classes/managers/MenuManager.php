<?php
/**
 * Менеджер меню
 * Управление меню навигации
 */

class MenuManager {
    private $db;
    private static $instance = null;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Получение экземпляра (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Получение всех меню с кешированием
     */
    public function getAllMenus() {
        if (!$this->db) {
            return [];
        }
        
        return cache_remember('all_menus', function() {
            $db = getDB();
            if (!$db) {
                return [];
            }
            
            try {
                $stmt = $db->query("SELECT * FROM menus ORDER BY name ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("MenuManager getAllMenus error: " . $e->getMessage());
                return [];
            }
        }, 3600); // Кешируем на 1 час
    }
    
    /**
     * Получение меню по slug с кешированием
     */
    public function getMenu($slug) {
        if (!$this->db || empty($slug)) {
            return null;
        }
        
        $cacheKey = 'menu_' . md5($slug);
        return cache_remember($cacheKey, function() use ($slug) {
            $db = getDB();
            if (!$db) {
                return null;
            }
            
            try {
                $stmt = $db->prepare("SELECT * FROM menus WHERE slug = ? LIMIT 1");
                $stmt->execute([$slug]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Exception $e) {
                error_log("MenuManager getMenu error: " . $e->getMessage());
                return null;
            }
        }, 3600); // Кешируем на 1 час
    }
    
    /**
     * Получение меню по ID
     */
    public function getMenuById($id) {
        if (!$this->db) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("MenuManager getMenuById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получение пунктов меню с кешированием
     */
    public function getMenuItems($menuId, $parentId = null) {
        if (!$this->db || empty($menuId)) {
            return [];
        }
        
        $cacheKey = 'menu_items_' . $menuId . '_' . ($parentId ?? 'root');
        return cache_remember($cacheKey, function() use ($menuId, $parentId) {
            $db = getDB();
            if (!$db) {
                return [];
            }
            
            try {
                if ($parentId === null) {
                    $stmt = $db->prepare("
                        SELECT * FROM menu_items 
                        WHERE menu_id = ? AND parent_id IS NULL AND is_active = 1 
                        ORDER BY order_num ASC
                    ");
                    $stmt->execute([$menuId]);
                } else {
                    $stmt = $db->prepare("
                        SELECT * FROM menu_items 
                        WHERE menu_id = ? AND parent_id = ? AND is_active = 1 
                        ORDER BY order_num ASC
                    ");
                    $stmt->execute([$menuId, $parentId]);
                }
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("MenuManager getMenuItems error: " . $e->getMessage());
                return [];
            }
        }, 1800); // Кешируем на 30 минут
    }
    
    /**
     * Получение всех пунктов меню (включая неактивные, для админки)
     */
    public function getAllMenuItems($menuId) {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM menu_items 
                WHERE menu_id = ? 
                ORDER BY parent_id ASC, order_num ASC
            ");
            $stmt->execute([$menuId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("MenuManager getAllMenuItems error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение пункта меню по ID
     */
    public function getMenuItem($id) {
        if (!$this->db) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("MenuManager getMenuItem error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание меню
     */
    public function createMenu($name, $slug, $description = '', $location = 'primary') {
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO menus (name, slug, description, location) 
                VALUES (?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$name, $slug, $description, $location]);
            
            if ($result) {
                // Сохраняем slug меню в настройках темы
                $this->saveMenuSlugToTheme($slug);
                
                // Очищаем кеш
                cache_forget('all_menus');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("MenuManager createMenu error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновление меню
     */
    public function updateMenu($id, $name, $slug, $description = '', $location = 'primary') {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Получаем старый slug для очистки кеша
            $oldMenu = $this->getMenuById($id);
            
            $stmt = $this->db->prepare("
                UPDATE menus 
                SET name = ?, slug = ?, description = ?, location = ? 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$name, $slug, $description, $location, $id]);
            
            if ($result) {
                // Сохраняем slug меню в настройках темы
                $this->saveMenuSlugToTheme($slug);
                
                // Очищаем кеш
                cache_forget('all_menus');
                if ($oldMenu && isset($oldMenu['slug'])) {
                    cache_forget('menu_' . md5($oldMenu['slug']));
                }
                cache_forget('menu_' . md5($slug));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("MenuManager updateMenu error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление меню
     */
    public function deleteMenu($id) {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Получаем меню для очистки кеша
            $menu = $this->getMenuById($id);
            
            $stmt = $this->db->prepare("DELETE FROM menus WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $menu) {
                // Очищаем кеш
                cache_forget('all_menus');
                if (isset($menu['slug'])) {
                    cache_forget('menu_' . md5($menu['slug']));
                }
                cache_forget('menu_items_' . $id . '_root');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("MenuManager deleteMenu error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Добавление пункта меню
     */
    public function addMenuItem($menuId, $title, $url, $parentId = null, $target = '_self', $cssClasses = '', $icon = '', $orderNum = 0) {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Если order_num не указан, ставим в конец
            if ($orderNum === 0) {
                $stmt = $this->db->prepare("SELECT MAX(order_num) as max_order FROM menu_items WHERE menu_id = ?");
                $stmt->execute([$menuId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $orderNum = ($result['max_order'] ?? 0) + 1;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO menu_items (menu_id, parent_id, title, url, target, css_classes, icon, order_num) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$menuId, $parentId, $title, $url, $target, $cssClasses, $icon, $orderNum]);
            
            if ($result) {
                // Очищаем кеш пунктов меню
                cache_forget('menu_items_' . $menuId . '_root');
                if ($parentId) {
                    cache_forget('menu_items_' . $menuId . '_' . $parentId);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("MenuManager addMenuItem error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновление пункта меню
     */
    public function updateMenuItem($id, $title, $url, $parentId = null, $target = '_self', $cssClasses = '', $icon = '', $orderNum = 0, $isActive = 1) {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Получаем старые данные для очистки кеша
            $oldItem = $this->getMenuItem($id);
            
            $stmt = $this->db->prepare("
                UPDATE menu_items 
                SET title = ?, url = ?, parent_id = ?, target = ?, css_classes = ?, icon = ?, order_num = ?, is_active = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$title, $url, $parentId, $target, $cssClasses, $icon, $orderNum, $isActive, $id]);
            
            if ($result && $oldItem) {
                // Очищаем кеш
                $menuId = $oldItem['menu_id'] ?? null;
                if ($menuId) {
                    cache_forget('menu_items_' . $menuId . '_root');
                    if ($oldItem['parent_id']) {
                        cache_forget('menu_items_' . $menuId . '_' . $oldItem['parent_id']);
                    }
                    if ($parentId) {
                        cache_forget('menu_items_' . $menuId . '_' . $parentId);
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("MenuManager updateMenuItem error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление пункта меню
     */
    public function deleteMenuItem($id) {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Получаем данные пункта для очистки кеша
            $item = $this->getMenuItem($id);
            $menuId = $item['menu_id'] ?? null;
            
            // Удаляем дочерние пункты
            $stmt = $this->db->prepare("DELETE FROM menu_items WHERE parent_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем сам пункт
            $stmt = $this->db->prepare("DELETE FROM menu_items WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $menuId) {
                // Очищаем кеш
                cache_forget('menu_items_' . $menuId . '_root');
                if ($item['parent_id']) {
                    cache_forget('menu_items_' . $menuId . '_' . $item['parent_id']);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("MenuManager deleteMenuItem error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновление порядка пунктов меню
     */
    public function updateMenuItemsOrder($items) {
        if (!$this->db || empty($items) || !is_array($items)) {
            return false;
        }
        
        try {
            $menuId = null;
            $this->db->beginTransaction();
            
            foreach ($items as $item) {
                if (!isset($item['id'])) {
                    continue;
                }
                
                // Получаем menu_id из первого элемента для очистки кеша
                if ($menuId === null) {
                    $tempStmt = $this->db->prepare("SELECT menu_id FROM menu_items WHERE id = ? LIMIT 1");
                    $tempStmt->execute([$item['id']]);
                    $tempResult = $tempStmt->fetch(PDO::FETCH_ASSOC);
                    $menuId = $tempResult['menu_id'] ?? null;
                }
                
                $stmt = $this->db->prepare("
                    UPDATE menu_items 
                    SET order_num = ?, parent_id = ? 
                    WHERE id = ?
                ");
                $parentId = isset($item['parent_id']) && $item['parent_id'] ? $item['parent_id'] : null;
                $stmt->execute([$item['order_num'] ?? 0, $parentId, $item['id']]);
            }
            
            $this->db->commit();
            
            // Очищаем кеш после успешного обновления
            if ($menuId) {
                cache_forget('menu_items_' . $menuId . '_root');
            }
            
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("MenuManager updateMenuItemsOrder error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Рендеринг меню (для фронтенда)
     */
    public function renderMenu($slug, $cssClass = 'navbar-nav') {
        $menu = $this->getMenu($slug);
        if (!$menu) {
            return '';
        }
        
        $items = $this->getMenuItems($menu['id']);
        if (empty($items)) {
            return '';
        }
        
        $html = '<ul class="' . htmlspecialchars($cssClass) . '">';
        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item, $menu['id']);
        }
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Рендеринг пункта меню
     */
    private function renderMenuItem($item, $menuId) {
        $url = htmlspecialchars($item['url']);
        $title = htmlspecialchars($item['title']);
        $target = $item['target'] ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        $cssClasses = $item['css_classes'] ? ' ' . htmlspecialchars($item['css_classes']) : '';
        $icon = $item['icon'] ? '<i class="' . htmlspecialchars($item['icon']) . '"></i> ' : '';
        
        // Получаем дочерние пункты
        $children = $this->getMenuItems($menuId, $item['id']);
        $hasChildren = !empty($children);
        
        $html = '<li class="nav-item' . ($hasChildren ? ' has-dropdown' : '') . $cssClasses . '">';
        
        if ($hasChildren) {
            $html .= '<a href="' . $url . '" class="nav-link dropdown-toggle"' . $target . '>';
            $html .= $icon . $title;
            $html .= '</a>';
            $html .= '<ul class="dropdown-menu">';
            foreach ($children as $child) {
                $html .= $this->renderMenuItem($child, $menuId);
            }
            $html .= '</ul>';
        } else {
            $html .= '<a href="' . $url . '" class="nav-link"' . $target . '>';
            $html .= $icon . $title;
            $html .= '</a>';
        }
        
        $html .= '</li>';
        
        return $html;
    }
    
    /**
     * Сохранение slug меню в настройках темы
     * 
     * @param string $menuSlug Slug меню
     * @return void
     */
    private function saveMenuSlugToTheme(string $menuSlug): void {
        if (!$this->db) {
            return;
        }
        
        try {
            // Получаем активную тему
            $activeTheme = themeManager()->getActiveTheme();
            if (!$activeTheme || !isset($activeTheme['slug'])) {
                return;
            }
            
            $themeSlug = $activeTheme['slug'];
            
            // Сохраняем slug меню в theme_settings
            $stmt = $this->db->prepare("
                INSERT INTO theme_settings (theme_slug, setting_key, setting_value) 
                VALUES (?, 'menu_slug', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            $stmt->execute([$themeSlug, $menuSlug]);
            
            // Очищаем кеш настроек темы
            cache_forget('theme_settings_' . $themeSlug);
        } catch (Exception $e) {
            error_log("MenuManager saveMenuSlugToTheme error: " . $e->getMessage());
        }
    }
}

/**
 * Глобальная функция для получения менеджера меню
 */
function menuManager() {
    return MenuManager::getInstance();
}

