<?php
/**
 * Плагин управления меню навигации
 * 
 * @package Plugins
 * @version 1.0.0
 */

declare(strict_types=1);

class MenuPluginPlugin extends BasePlugin {
    
    private $menuManager = null;
    
    /**
     * Инициализация плагина
     */
    public function init(): void {
        // Загружаем MenuManager
        require_once __DIR__ . '/MenuManager.php';
        
        // Регистрируем хуки
        addFilter('admin_menu', [$this, 'addAdminMenuItem'], 10);
        addAction('admin_register_routes', [$this, 'registerAdminRoute'], 10);
        addAction('theme_menu', [$this, 'renderHeaderMenu'], 10);
        addFilter('theme_footer', [$this, 'renderFooterMenus'], 10);
        addFilter('theme_content', [$this, 'processShortcodes'], 20);
        addFilter('theme_widgets', [$this, 'registerMenuWidgets'], 10);
    }
    
    /**
     * Активация плагина
     */
    public function activate(): void {
        // Создаем таблицы БД при активации
        $this->createTables();
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate(): void {
        // Очищаем кеш меню
        cache_forget('all_menus');
    }
    
    /**
     * Установка плагина
     */
    public function install(): void {
        // Создаем таблицы БД
        $this->createTables();
    }
    
    /**
     * Удаление плагина
     */
    public function uninstall(): void {
        // Удаляем таблицы БД
        $this->dropTables();
    }
    
    /**
     * Создание таблиц БД
     */
    private function createTables(): void {
        $db = DatabaseHelper::getConnection();
        if (!$db) {
            return;
        }
        
        try {
            // Таблица меню
            $db->exec("
                CREATE TABLE IF NOT EXISTS `menus` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `description` text COLLATE utf8mb4_unicode_ci,
                  `location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `slug` (`slug`),
                  KEY `idx_menu_location` (`location`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Таблица пунктов меню
            $db->exec("
                CREATE TABLE IF NOT EXISTS `menu_items` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `menu_id` int(11) NOT NULL,
                  `parent_id` int(11) DEFAULT NULL,
                  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `target` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '_self',
                  `css_classes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                  `order_num` int(11) NOT NULL DEFAULT '0',
                  `is_active` tinyint(1) NOT NULL DEFAULT '1',
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_menu_id` (`menu_id`),
                  KEY `idx_parent_id` (`parent_id`),
                  KEY `idx_order_num` (`order_num`),
                  KEY `idx_is_active` (`is_active`),
                  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("MenuPlugin createTables error: " . $e->getMessage());
        }
    }
    
    /**
     * Удаление таблиц БД
     */
    private function dropTables(): void {
        $db = DatabaseHelper::getConnection();
        if (!$db) {
            return;
        }
        
        try {
            $db->exec("DROP TABLE IF EXISTS `menu_items`");
            $db->exec("DROP TABLE IF EXISTS `menus`");
        } catch (Exception $e) {
            error_log("MenuPlugin dropTables error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение менеджера меню
     */
    private function getMenuManager() {
        if ($this->menuManager === null) {
            $this->menuManager = MenuManager::getInstance();
        }
        return $this->menuManager;
    }
    
    /**
     * Добавление пункта меню в админку
     */
    public function addAdminMenuItem(array $menu): array {
        // Проверяем, поддерживает ли тема навигацию
        if (!$this->themeSupportsNavigation()) {
            return $menu;
        }
        
        $menu[] = [
            'text' => 'Меню',
            'title' => 'Управление меню',
            'href' => '/admin/menus',
            'icon' => 'fas fa-bars',
            'order' => 30,
            'page' => 'menus'
        ];
        
        return $menu;
    }
    
    /**
     * Регистрация маршрута админки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        // Регистрируем маршрут только если тема поддерживает навигацию
        if (!$this->themeSupportsNavigation()) {
            // Если тема не поддерживает навигацию, все равно регистрируем маршрут
            // так как меню может использоваться в других темах
        }
        
        require_once __DIR__ . '/admin/MenusPage.php';
        $router->add(['GET', 'POST'], 'menus', 'MenusPage');
    }
    
    /**
     * Проверка поддержки навигации активной темой
     */
    private function themeSupportsNavigation(): bool {
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return false;
        }
        
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        return (bool)($themeConfig['supports_navigation'] ?? false);
    }
    
    /**
     * Получение доступных расположений меню из активной темы
     */
    public function getMenuLocations(): array {
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return [];
        }
        
        $themeSlug = $activeTheme['slug'];
        $themeConfig = themeManager()->getThemeConfig($themeSlug);
        
        if (!($themeConfig['supports_navigation'] ?? false)) {
            return [];
        }
        
        return $themeConfig['menu_locations'] ?? [];
    }
    
    /**
     * Получение экземпляра плагина (для использования в MenusPage)
     */
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
    
    /**
     * Рендеринг меню в хедере
     */
    public function renderHeaderMenu(): void {
        $menuManager = $this->getMenuManager();
        
        // Получаем активную тему
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return;
        }
        
        $themeSlug = $activeTheme['slug'];
        $themeConfig = themeManager()->getThemeConfig($themeSlug);
        $menuLocations = $themeConfig['menu_locations'] ?? [];
        
        // Ищем меню для расположения 'header'
        $menu = null;
        if (isset($menuLocations['header'])) {
            $menu = $menuManager->getMenuByLocation('header');
        }
        
        // Если не найдено, пробуем найти по slug из настроек темы
        if (!$menu) {
            $savedMenuSlug = themeManager()->getSetting('header_menu_slug');
            if (!empty($savedMenuSlug)) {
                $menu = $menuManager->getMenu($savedMenuSlug);
            }
        }
        
        if ($menu) {
            $allMenuItems = $menuManager->getAllMenuItems($menu['id']);
            $activeMenuItems = array_filter($allMenuItems, function($item) {
                return ($item['is_active'] == '1' || $item['is_active'] == 1);
            });
            
            if (!empty($activeMenuItems)) {
                echo $this->renderMenuItems($activeMenuItems);
            }
        }
    }
    
    /**
     * Рендеринг пунктов меню
     */
    private function renderMenuItems(array $menuItems, ?int $parentId = null): string {
        $filteredItems = array_filter($menuItems, function($item) use ($parentId) {
            $isActive = ($item['is_active'] == '1' || $item['is_active'] == 1);
            if (!$isActive) {
                return false;
            }
            
            $itemParentId = null;
            if (isset($item['parent_id'])) {
                if ($item['parent_id'] === null || $item['parent_id'] === '' || $item['parent_id'] === '0') {
                    $itemParentId = null;
                } else {
                    $itemParentId = (int)$item['parent_id'];
                }
            }
            
            return $itemParentId === $parentId;
        });
        
        if (empty($filteredItems)) {
            return '';
        }
        
        usort($filteredItems, function($a, $b) {
            $orderA = isset($a['order_num']) ? (int)$a['order_num'] : 0;
            $orderB = isset($b['order_num']) ? (int)$b['order_num'] : 0;
            return $orderA - $orderB;
        });
        
        $html = '<ul class="navbar-nav me-auto">';
        
        foreach ($filteredItems as $item) {
            $target = !empty($item['target']) ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
            $cssClasses = !empty($item['css_classes']) ? ' ' . htmlspecialchars($item['css_classes']) : '';
            $icon = !empty($item['icon']) ? '<i class="' . htmlspecialchars($item['icon']) . '"></i> ' : '';
            
            $itemId = isset($item['id']) ? (int)$item['id'] : null;
            
            $hasChildren = false;
            if ($itemId !== null) {
                foreach ($menuItems as $childItem) {
                    $childParentId = isset($childItem['parent_id']) && $childItem['parent_id'] !== null ? (int)$childItem['parent_id'] : null;
                    if ($childParentId === $itemId && ($childItem['is_active'] == '1' || $childItem['is_active'] == 1)) {
                        $hasChildren = true;
                        break;
                    }
                }
            }
            
            $html .= '<li class="nav-item' . ($hasChildren ? ' dropdown' : '') . $cssClasses . '">';
            
            if ($hasChildren) {
                $html .= '<a class="nav-link dropdown-toggle" href="' . htmlspecialchars($item['url'] ?? '#') . '"' . $target . ' role="button" data-bs-toggle="dropdown">';
                $html .= $icon . htmlspecialchars($item['title'] ?? '');
                $html .= '</a>';
                $html .= '<ul class="dropdown-menu">';
                $html .= $this->renderMenuItems($menuItems, $itemId);
                $html .= '</ul>';
            } else {
                $html .= '<a class="nav-link" href="' . htmlspecialchars($item['url'] ?? '#') . '"' . $target . '>';
                $html .= $icon . htmlspecialchars($item['title'] ?? '');
                $html .= '</a>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }
    
    /**
     * Рендеринг меню в футере
     */
    public function renderFooterMenus(?string $footerContent = null): string {
        if ($footerContent === null) {
            $footerContent = '';
        }
        
        $menuManager = $this->getMenuManager();
        
        // Получаем активную тему
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return $footerContent;
        }
        
        $themeSlug = $activeTheme['slug'];
        $themeConfig = themeManager()->getThemeConfig($themeSlug);
        $menuLocations = $themeConfig['menu_locations'] ?? [];
        
        // Ищем меню для расположения 'footer'
        $menu = null;
        if (isset($menuLocations['footer'])) {
            $menu = $menuManager->getMenuByLocation('footer');
        }
        
        // Если не найдено, пробуем найти по slug из настроек темы
        if (!$menu) {
            $savedMenuSlug = themeManager()->getSetting('footer_menu_slug');
            if (!empty($savedMenuSlug)) {
                $menu = $menuManager->getMenu($savedMenuSlug);
            }
        }
        
        if ($menu) {
            $menuItems = $menuManager->getMenuItems($menu['id']);
            if (!empty($menuItems)) {
                $menuHtml = $this->renderFooterMenuItems($menuItems);
                // Вставляем меню перед закрывающим тегом footer или в конец
                if (strpos($footerContent, '</footer>') !== false) {
                    $footerContent = str_replace('</footer>', $menuHtml . '</footer>', $footerContent);
                } else {
                    $footerContent .= $menuHtml;
                }
            }
        }
        
        return $footerContent;
    }
    
    /**
     * Рендеринг пунктов меню для футера
     */
    private function renderFooterMenuItems(array $menuItems): string {
        if (empty($menuItems)) {
            return '';
        }
        
        usort($menuItems, function($a, $b) {
            $orderA = isset($a['order_num']) ? (int)$a['order_num'] : 0;
            $orderB = isset($b['order_num']) ? (int)$b['order_num'] : 0;
            return $orderA - $orderB;
        });
        
        $html = '<nav class="footer-menu text-center mt-3">';
        $html .= '<ul class="list-inline mb-0">';
        
        foreach ($menuItems as $item) {
            $target = !empty($item['target']) && $item['target'] !== '_self' ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
            $cssClasses = !empty($item['css_classes']) ? ' class="' . htmlspecialchars($item['css_classes']) . '"' : '';
            $icon = !empty($item['icon']) ? '<i class="' . htmlspecialchars($item['icon']) . '"></i> ' : '';
            
            $html .= '<li class="list-inline-item">';
            $html .= '<a href="' . htmlspecialchars($item['url']) . '"' . $target . $cssClasses . '>';
            $html .= $icon . htmlspecialchars($item['title']);
            $html .= '</a>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Обработка шорткодов меню в контенте
     */
    public function processShortcodes(string $content): string {
        $menuManager = $this->getMenuManager();
        
        // Обработка шорткода [menu slug="menu-slug"]
        $content = preg_replace_callback(
            '/\[menu\s+slug=["\']([^"\']+)["\'](?:\s+class=["\']([^"\']+)["\'])?(?:\s+style=["\']([^"\']+)["\'])?\]/i',
            function($matches) use ($menuManager) {
                $slug = $matches[1] ?? '';
                $cssClass = $matches[2] ?? 'menu';
                $style = $matches[3] ?? '';
                
                if (empty($slug)) {
                    return '';
                }
                
                $menu = $menuManager->getMenu($slug);
                if (!$menu) {
                    return '';
                }
                
                $html = $menuManager->renderMenu($slug, $cssClass);
                
                if (!empty($style)) {
                    $html = str_replace('<ul', '<ul style="' . htmlspecialchars($style) . '"', $html);
                }
                
                return $html;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Регистрация виджетов меню
     */
    public function registerMenuWidgets(array $widgets): array {
        $menuManager = $this->getMenuManager();
        $allMenus = $menuManager->getAllMenus();
        
        foreach ($allMenus as $menu) {
            if ($menu['location'] === 'custom') {
                $widgets[] = [
                    'id' => 'menu_' . $menu['slug'],
                    'title' => $menu['name'],
                    'description' => $menu['description'] ?? 'Меню: ' . $menu['name'],
                    'callback' => function($args = []) use ($menu, $menuManager) {
                        return $menuManager->renderMenu($menu['slug'], $args['class'] ?? 'menu-widget');
                    }
                ];
            }
        }
        
        return $widgets;
    }
}

