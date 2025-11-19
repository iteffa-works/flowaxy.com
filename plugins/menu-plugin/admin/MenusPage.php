<?php
/**
 * Страница управления меню
 * Версия с AJAX поддержкой
 */

require_once __DIR__ . '/../../../engine/skins/includes/AdminPage.php';

// Убеждаемся, что функция menuManager() доступна
if (!function_exists('menuManager')) {
    // Загружаем класс MenuManager из плагина
    if (!class_exists('MenuManager')) {
        $menuManagerFile = dirname(__DIR__) . '/MenuManager.php';
        if (file_exists($menuManagerFile)) {
            require_once $menuManagerFile;
        }
    }
}

class MenusPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Меню - Flowaxy CMS';
        $this->templateName = 'menus';
        
        $this->setPageHeader(
            'Меню',
            'Керування меню навігації сайту',
            'fas fa-bars'
        );
    }
    
    protected function getTemplatePath() {
        return __DIR__ . '/templates/';
    }
    
    public function handle() {
        // Обработка AJAX запросов
        if ($this->isAjaxRequest()) {
            $this->handleAjax();
            return;
        }
        
        // Обработка обычных POST запросов (для обратной совместимости)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $this->handlePost();
            $menuId = $_GET['menu_id'] ?? $_POST['menu_id'] ?? null;
            if ($menuId) {
                $this->redirect('menus');
            }
        }
        
        // Получение списка меню
        $menus = menuManager()->getAllMenus();
        
        // Получение доступных расположений из активной темы
        $menuLocations = [];
        
        // Получаем активную тему
        $activeTheme = themeManager()->getActiveTheme();
        if ($activeTheme) {
            $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
            $menuLocations = $themeConfig['menu_locations'] ?? [];
        }
        
        // Если в теме нет расположений, пробуем получить через плагин
        if (empty($menuLocations)) {
            // Ищем плагин menu-plugin
            $pluginManager = pluginManager();
            $plugin = $pluginManager->getPlugin('menu-plugin');
            if ($plugin && method_exists($plugin, 'getMenuLocations')) {
                $menuLocations = $plugin->getMenuLocations();
            }
        }
        
        // Если тема не поддерживает навигацию или нет расположений, используем стандартные расположения
        if (empty($menuLocations)) {
            $menuLocations = [
                'header' => ['label' => 'Меню в хедере (header)', 'description' => 'Основное меню навигации в шапке сайта'],
                'footer' => ['label' => 'Меню в футере (footer)', 'description' => 'Меню навигации в подвале сайта'],
                'primary' => ['label' => 'Основне меню (primary)', 'description' => 'Основне меню навігації'],
                'sidebar' => ['label' => 'Бічне меню (sidebar)', 'description' => 'Бічне меню навігації'],
                'custom' => ['label' => 'Произвольне меню (custom)', 'description' => 'Меню для використання через шорткоди']
            ];
        }
        
        // Рендерим страницу
        $this->render([
            'menus' => $menus,
            'menuLocations' => $menuLocations
        ]);
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax() {
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        try {
            $action = SecurityHelper::sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
            
            if (empty($action)) {
                echo json_encode(['success' => false, 'error' => 'Дія не вказана'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            switch ($action) {
                case 'get_menu':
                    $this->ajaxGetMenu();
                    break;
                case 'get_menu_items':
                    $this->ajaxGetMenuItems();
                    break;
                case 'create_menu':
                    $this->ajaxCreateMenu();
                    break;
                case 'update_menu':
                    $this->ajaxUpdateMenu();
                    break;
                case 'delete_menu':
                    $this->ajaxDeleteMenu();
                    break;
                case 'create_menu_item':
                    $this->ajaxCreateMenuItem();
                    break;
                case 'update_menu_item':
                    $this->ajaxUpdateMenuItem();
                    break;
                case 'delete_menu_item':
                    $this->ajaxDeleteMenuItem();
                    break;
                case 'update_menu_order':
                    $this->ajaxUpdateMenuOrder();
                    break;
                default:
                    echo json_encode(['success' => false, 'error' => 'Невідома дія: ' . $action], JSON_UNESCAPED_UNICODE);
                    exit;
            }
        } catch (Exception $e) {
            error_log("MenusPage handleAjax error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка обробки запиту: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    private function ajaxGetMenu() {
        try {
            $menuId = (int)($_GET['menu_id'] ?? $_POST['menu_id'] ?? 0);
            if (!$menuId) {
                echo json_encode(['success' => false, 'error' => 'ID меню не вказано'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $menu = menuManager()->getMenuById($menuId);
            if ($menu) {
                echo json_encode(['success' => true, 'menu' => $menu], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Меню не знайдено'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("MenusPage ajaxGetMenu error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxGetMenuItems() {
        try {
            $menuId = (int)($_GET['menu_id'] ?? $_POST['menu_id'] ?? 0);
            if (!$menuId) {
                echo json_encode(['success' => false, 'error' => 'ID меню не вказано'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $menuItems = menuManager()->getAllMenuItems($menuId);
            $menuItemsTree = $this->buildMenuTree($menuItems ?: []);
            echo json_encode([
                'success' => true,
                'items' => $menuItems ?: [],
                'tree' => $menuItemsTree ?: []
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("MenusPage ajaxGetMenuItems error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Помилка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxCreateMenu() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $name = SecurityHelper::sanitizeInput($_POST['name'] ?? '');
        $slug = SecurityHelper::sanitizeInput($_POST['slug'] ?? '');
        $description = SecurityHelper::sanitizeInput($_POST['description'] ?? '');
        $location = SecurityHelper::sanitizeInput($_POST['location'] ?? 'primary');
        if (empty($name) || empty($slug)) {
            echo json_encode(['success' => false, 'error' => 'Назва та slug обов\'язкові'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (menuManager()->createMenu($name, $slug, $description, $location)) {
            $createdMenu = menuManager()->getMenu($slug);
            echo json_encode(['success' => true, 'menu' => $createdMenu], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при створенні меню'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxUpdateMenu() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = (int)($_POST['menu_id'] ?? 0);
        $name = SecurityHelper::sanitizeInput($_POST['name'] ?? '');
        $slug = SecurityHelper::sanitizeInput($_POST['slug'] ?? '');
        $description = SecurityHelper::sanitizeInput($_POST['description'] ?? '');
        $location = SecurityHelper::sanitizeInput($_POST['location'] ?? 'primary');
        if (empty($name) || empty($slug)) {
            echo json_encode(['success' => false, 'error' => 'Назва та slug обов\'язкові'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (menuManager()->updateMenu($id, $name, $slug, $description, $location)) {
            $menu = menuManager()->getMenuById($id);
            echo json_encode(['success' => true, 'menu' => $menu], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при оновленні меню'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxDeleteMenu() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = (int)($_POST['menu_id'] ?? 0);
        if (menuManager()->deleteMenu($id)) {
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при видаленні меню'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxCreateMenuItem() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $menuId = (int)($_POST['menu_id'] ?? 0);
        $title = SecurityHelper::sanitizeInput($_POST['title'] ?? '');
        $url = SecurityHelper::sanitizeInput($_POST['url'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $target = SecurityHelper::sanitizeInput($_POST['target'] ?? '_self');
        $cssClasses = SecurityHelper::sanitizeInput($_POST['css_classes'] ?? '');
        $icon = SecurityHelper::sanitizeInput($_POST['icon'] ?? '');
        $orderNum = (int)($_POST['order_num'] ?? 0);
        if (empty($title) || empty($url)) {
            echo json_encode(['success' => false, 'error' => 'Назва та URL обов\'язкові'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (menuManager()->addMenuItem($menuId, $title, $url, $parentId, $target, $cssClasses, $icon, $orderNum)) {
            $menuItems = menuManager()->getAllMenuItems($menuId);
            $menuItemsTree = $this->buildMenuTree($menuItems);
            echo json_encode([
                'success' => true,
                'items' => $menuItems,
                'tree' => $menuItemsTree
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при додаванні пункта меню'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxUpdateMenuItem() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = (int)($_POST['item_id'] ?? 0);
        $menuId = (int)($_POST['menu_id'] ?? 0);
        $title = SecurityHelper::sanitizeInput($_POST['title'] ?? '');
        $url = SecurityHelper::sanitizeInput($_POST['url'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $target = SecurityHelper::sanitizeInput($_POST['target'] ?? '_self');
        $cssClasses = SecurityHelper::sanitizeInput($_POST['css_classes'] ?? '');
        $icon = SecurityHelper::sanitizeInput($_POST['icon'] ?? '');
        $orderNum = (int)($_POST['order_num'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if (menuManager()->updateMenuItem($id, $title, $url, $parentId, $target, $cssClasses, $icon, $orderNum, $isActive)) {
            $menuItems = menuManager()->getAllMenuItems($menuId);
            $menuItemsTree = $this->buildMenuTree($menuItems);
            echo json_encode([
                'success' => true,
                'items' => $menuItems,
                'tree' => $menuItemsTree
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при оновленні пункта меню'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxDeleteMenuItem() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = (int)($_POST['item_id'] ?? 0);
        $menuId = (int)($_POST['menu_id'] ?? 0);
        if (menuManager()->deleteMenuItem($id)) {
            $menuItems = menuManager()->getAllMenuItems($menuId);
            $menuItemsTree = $this->buildMenuTree($menuItems);
            echo json_encode([
                'success' => true,
                'items' => $menuItems,
                'tree' => $menuItemsTree
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при видаленні пункта меню'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function ajaxUpdateMenuOrder() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $items = json_decode($_POST['items'] ?? '[]', true);
        $menuId = (int)($_POST['menu_id'] ?? 0);
        if (menuManager()->updateMenuItemsOrder($items)) {
            $menuItems = menuManager()->getAllMenuItems($menuId);
            $menuItemsTree = $this->buildMenuTree($menuItems);
            echo json_encode([
                'success' => true,
                'items' => $menuItems,
                'tree' => $menuItemsTree
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Помилка при оновленні порядку'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function handlePost() {
        if (!$this->verifyCsrf()) {
            return;
        }
    }
    
    private function buildMenuTree($items) {
        if (empty($items)) {
            return [];
        }
        usort($items, function($a, $b) {
            $orderA = isset($a['order_num']) ? (int)$a['order_num'] : 0;
            $orderB = isset($b['order_num']) ? (int)$b['order_num'] : 0;
            return $orderA - $orderB;
        });
        $itemsById = [];
        foreach ($items as $item) {
            $itemsById[$item['id']] = [
                'id' => $item['id'],
                'title' => $item['title'] ?? '',
                'url' => $item['url'] ?? '',
                'target' => $item['target'] ?? '_self',
                'css_classes' => $item['css_classes'] ?? '',
                'icon' => $item['icon'] ?? '',
                'order_num' => $item['order_num'] ?? 0,
                'is_active' => $item['is_active'] ?? 1,
                'parent_id' => $item['parent_id'] ?? null,
                'children' => []
            ];
        }
        $tree = [];
        $processedIds = [];
        foreach ($itemsById as $itemId => $item) {
            $parentId = $item['parent_id'];
            if ($parentId === null || $parentId === '' || !isset($itemsById[$parentId])) {
                $tree[] = $itemId;
                $processedIds[] = $itemId;
            }
        }
        $maxDepth = 10;
        $currentLevel = $tree;
        $depth = 0;
        while ($depth < $maxDepth && !empty($currentLevel)) {
            $nextLevel = [];
            foreach ($currentLevel as $parentId) {
                if (!isset($itemsById[$parentId])) {
                    continue;
                }
                foreach ($itemsById as $itemId => $item) {
                    if (in_array($itemId, $processedIds)) {
                        continue;
                    }
                    $itemParentId = $item['parent_id'];
                    if ($itemParentId == $parentId) {
                        $itemsById[$parentId]['children'][] = $itemId;
                        $nextLevel[] = $itemId;
                        $processedIds[] = $itemId;
                    }
                }
            }
            $currentLevel = $nextLevel;
            $depth++;
        }
        $resultTree = [];
        foreach ($tree as $rootId) {
            $resultTree[] = $this->buildTreeItem($itemsById, $rootId);
        }
        return $resultTree;
    }
    
    private function buildTreeItem($itemsById, $itemId, $maxDepth = 10, $depth = 0) {
        if ($depth > $maxDepth || !isset($itemsById[$itemId])) {
            return null;
        }
        $item = $itemsById[$itemId];
        $children = [];
        if (!empty($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $childId) {
                $childItem = $this->buildTreeItem($itemsById, $childId, $maxDepth, $depth + 1);
                if ($childItem !== null) {
                    $children[] = $childItem;
                }
            }
        }
        $item['children'] = $children;
        return $item;
    }
}

