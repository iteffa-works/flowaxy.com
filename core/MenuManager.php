<?php
/**
 * Менеджер меню
 * Управление меню навигации
 */

class MenuManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Получение всех меню
     */
    public function getAllMenus() {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->query("SELECT * FROM menus ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("MenuManager getAllMenus error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение меню по slug
     */
    public function getMenu($slug) {
        if (!$this->db) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM menus WHERE slug = ?");
            $stmt->execute([$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("MenuManager getMenu error: " . $e->getMessage());
            return null;
        }
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
     * Получение пунктов меню
     */
    public function getMenuItems($menuId, $parentId = null) {
        if (!$this->db) {
            return [];
        }
        
        try {
            if ($parentId === null) {
                $stmt = $this->db->prepare("
                    SELECT * FROM menu_items 
                    WHERE menu_id = ? AND parent_id IS NULL AND is_active = 1 
                    ORDER BY order_num ASC
                ");
                $stmt->execute([$menuId]);
            } else {
                $stmt = $this->db->prepare("
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
            
            return $stmt->execute([$name, $slug, $description, $location]);
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
            $stmt = $this->db->prepare("
                UPDATE menus 
                SET name = ?, slug = ?, description = ?, location = ? 
                WHERE id = ?
            ");
            
            return $stmt->execute([$name, $slug, $description, $location, $id]);
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
            $stmt = $this->db->prepare("DELETE FROM menus WHERE id = ?");
            return $stmt->execute([$id]);
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
            
            return $stmt->execute([$menuId, $parentId, $title, $url, $target, $cssClasses, $icon, $orderNum]);
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
            $stmt = $this->db->prepare("
                UPDATE menu_items 
                SET title = ?, url = ?, parent_id = ?, target = ?, css_classes = ?, icon = ?, order_num = ?, is_active = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([$title, $url, $parentId, $target, $cssClasses, $icon, $orderNum, $isActive, $id]);
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
            // Удаляем дочерние пункты
            $stmt = $this->db->prepare("DELETE FROM menu_items WHERE parent_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем сам пункт
            $stmt = $this->db->prepare("DELETE FROM menu_items WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("MenuManager deleteMenuItem error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновление порядка пунктов меню
     */
    public function updateMenuItemsOrder($items) {
        if (!$this->db) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($items as $item) {
                $stmt = $this->db->prepare("
                    UPDATE menu_items 
                    SET order_num = ?, parent_id = ? 
                    WHERE id = ?
                ");
                $parentId = isset($item['parent_id']) && $item['parent_id'] ? $item['parent_id'] : null;
                $stmt->execute([$item['order_num'], $parentId, $item['id']]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
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
}

/**
 * Глобальная функция для получения менеджера меню
 */
function menuManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new MenuManager();
    }
    return $instance;
}

