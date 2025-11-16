<?php
/**
 * Страница управления меню
 * Версия с AJAX поддержкой
 */

require_once __DIR__ . '/../includes/AdminPage.php';

// Убеждаемся, что функция menuManager() доступна
if (!function_exists('menuManager')) {
    // Загружаем класс MenuManager, который определит функцию menuManager()
    if (!class_exists('MenuManager')) {
        $menuManagerFile = dirname(__DIR__, 2) . '/classes/managers/MenuManager.php';
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
    
    public function handle() {
        // Обработка AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обработка обычных POST запросов (для обратной совместимости)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $this->handlePost();
            // Після POST запиту робимо редірект для уникнення повторної відправки (використовуємо Response клас)
            $menuId = $_GET['menu_id'] ?? $_POST['menu_id'] ?? null;
            if ($menuId) {
                Response::redirectStatic(adminUrl('menus'));
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
        
        // Если в теме нет расположений, пробуем получить через модуль Menu
        if (empty($menuLocations) && class_exists('ModuleLoader')) {
            // Загружаем модуль Menu если не загружен
            if (!ModuleLoader::isModuleLoaded('Menu')) {
                ModuleLoader::loadModule('Menu');
            }
            
            if (ModuleLoader::isModuleLoaded('Menu') && function_exists('menuModule')) {
                $menuModule = menuModule();
                $menuLocations = $menuModule->getMenuLocations();
            }
        }
        
        // Если тема не поддерживает навигацию или нет расположений, используем стандартные расположения
        if (empty($menuLocations)) {
            $menuLocations = [
                'primary' => ['label' => 'Основне меню (primary)', 'description' => 'Основне меню навігації'],
                'footer' => ['label' => 'Меню футера (footer)', 'description' => 'Меню в підвалі сайту'],
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
        // Використовуємо Response клас для встановлення заголовків
        Response::setHeader('Content-Type', 'application/json; charset=utf-8');
        
        try {
            $action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
            
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
    
    /**
     * Получение меню по ID
     */
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
    
    /**
     * Получение пунктов меню
     */
    private function ajaxGetMenuItems() {
        try {
            $menuId = (int)($_GET['menu_id'] ?? $_POST['menu_id'] ?? 0);
            
            if (!$menuId) {
                echo json_encode(['success' => false, 'error' => 'ID меню не вказано'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Получаем все пункты меню (включая неактивные для редактирования)
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
    
    /**
     * Создание меню
     */
    private function ajaxCreateMenu() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? 'primary');
        
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
    
    /**
     * Обновление меню
     */
    private function ajaxUpdateMenu() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['menu_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? 'primary');
        
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
    
    /**
     * Удаление меню
     */
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
    
    /**
     * Создание пункта меню
     */
    private function ajaxCreateMenuItem() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $menuId = (int)($_POST['menu_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $url = sanitizeInput($_POST['url'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $target = sanitizeInput($_POST['target'] ?? '_self');
        $cssClasses = sanitizeInput($_POST['css_classes'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? '');
        $orderNum = (int)($_POST['order_num'] ?? 0);
        
        if (empty($title) || empty($url)) {
            echo json_encode(['success' => false, 'error' => 'Назва та URL обов\'язкові'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (menuManager()->addMenuItem($menuId, $title, $url, $parentId, $target, $cssClasses, $icon, $orderNum)) {
            // Получаем обновленный список пунктов
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
    
    /**
     * Обновление пункта меню
     */
    private function ajaxUpdateMenuItem() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)($_POST['item_id'] ?? 0);
        $menuId = (int)($_POST['menu_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $url = sanitizeInput($_POST['url'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $target = sanitizeInput($_POST['target'] ?? '_self');
        $cssClasses = sanitizeInput($_POST['css_classes'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? '');
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
    
    /**
     * Удаление пункта меню
     */
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
    
    /**
     * Обновление порядка пунктов меню
     */
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
    
    /**
     * Обработка обычных POST запросов (для обратной совместимости)
     */
    private function handlePost() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        // Эта функция оставлена для обратной совместимости, но все действия теперь через AJAX
    }
    
    /**
     * Построение дерева меню (оптимизированная версия)
     */
    private function buildMenuTree($items) {
        if (empty($items)) {
            return [];
        }
        
        // Сортируем элементы по order_num
        usort($items, function($a, $b) {
            $orderA = isset($a['order_num']) ? (int)$a['order_num'] : 0;
            $orderB = isset($b['order_num']) ? (int)$b['order_num'] : 0;
            return $orderA - $orderB;
        });
        
        // Создаем массив для хранения элементов по ID
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
        
        // Строим дерево
        $tree = [];
        $processedIds = [];
        
        // Добавляем корневые элементы
        foreach ($itemsById as $itemId => $item) {
            $parentId = $item['parent_id'];
            if ($parentId === null || $parentId === '' || !isset($itemsById[$parentId])) {
                $tree[] = $itemId;
                $processedIds[] = $itemId;
            }
        }
        
        // Добавляем дочерние элементы (максимум 10 уровней)
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
        
        // Преобразуем ID обратно в элементы
        $resultTree = [];
        foreach ($tree as $rootId) {
            $resultTree[] = $this->buildTreeItem($itemsById, $rootId);
        }
        
        return $resultTree;
    }
    
    /**
     * Построение элемента дерева с детьми
     */
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
