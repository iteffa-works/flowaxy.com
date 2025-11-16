<?php
/**
 * Модуль керування меню навігації
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

// BaseModule та MenuManager тепер завантажуються через автозавантажувач
// BaseModule: base/BaseModule.php
// MenuManager: managers/MenuManager.php

class Menu extends BaseModule {
    
    /**
     * Ініціалізація модуля
     */
    protected function init(): void {
        // Модуль ініціалізовано
    }
    
    /**
     * Реєстрація хуків модуля
     */
    public function registerHooks(): void {
        // Реєстрація маршруту адмінки
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
        
        // Реєстрація обробки шорткодів для довільних меню
        addHook('theme_content', [$this, 'processShortcodes'], 20);
        
        // Реєстрація віджетів меню
        addHook('theme_widgets', [$this, 'registerMenuWidgets']);
        
        // Реєстрація хука для виведення меню в хедері
        addHook('theme_menu', [$this, 'renderHeaderMenu']);
        
        // Реєстрація хука для вбудовування меню в футер
        addHook('theme_footer', [$this, 'renderFooterMenus']);
    }
    
    /**
     * Реєстрація маршруту адмінки
     * 
     * @param Router|null $router Роутер адмінки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        // Реєструємо маршрут тільки якщо тема підтримує навігацію
        if (!$this->themeSupportsNavigation()) {
            return;
        }
        
        require_once dirname(__DIR__) . '/skins/pages/MenusPage.php';
        $router->add('menus', 'MenusPage');
    }
    
    /**
     * Отримання інформації про модуль
     * 
     * @return array
     */
    public function getInfo(): array {
        return [
            'name' => 'Menu',
            'title' => 'Меню навігації',
            'description' => 'Управління меню навігації та розташуваннями',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Отримання доступних розташувань меню з активної теми
     * 
     * @return array
     */
    public function getMenuLocations(): array {
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return [];
        }
        
        $themeSlug = $activeTheme['slug'];
        $themeConfig = themeManager()->getThemeConfig($themeSlug);
        
        // Якщо тема не підтримує навігацію, повертаємо порожній масив
        if (!($themeConfig['supports_navigation'] ?? false)) {
            return [];
        }
        
        // Получаем расположения из theme.json
        // Возвращаем все доступные расположения для выбора в админке
        return $themeConfig['menu_locations'] ?? [];
    }
    
    /**
     * Перевірка підтримки навігації активною темою
     * 
     * @return bool
     */
    public function themeSupportsNavigation(): bool {
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return false;
        }
        
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        return (bool)($themeConfig['supports_navigation'] ?? false);
    }
    
    /**
     * Обробка шорткодів меню в контенті
     * 
     * @param string $content Контент сторінки
     * @return string Оброблений контент
     */
    public function processShortcodes(string $content): string {
        // Обробка шорткоду [menu slug="menu-slug"]
        $content = preg_replace_callback(
            '/\[menu\s+slug=["\']([^"\']+)["\'](?:\s+class=["\']([^"\']+)["\'])?(?:\s+style=["\']([^"\']+)["\'])?\]/i',
            function($matches) {
                $slug = $matches[1] ?? '';
                $cssClass = $matches[2] ?? 'menu';
                $style = $matches[3] ?? '';
                
                if (empty($slug)) {
                    return '';
                }
                
                $menuManager = menuManager();
                $menu = $menuManager->getMenu($slug);
                
                if (!$menu) {
                    return '';
                }
                
                $html = $menuManager->renderMenu($slug, $cssClass);
                
                // Додаємо стилі якщо вказані
                if (!empty($style)) {
                    $html = str_replace('<ul', '<ul style="' . htmlspecialchars($style) . '"', $html);
                }
                
                return $html;
            },
            $content
        );
        
        // Обробка шорткоду [menu_widget slug="menu-slug" location="footer"]
        $content = preg_replace_callback(
            '/\[menu_widget\s+slug=["\']([^"\']+)["\'](?:\s+location=["\']([^"\']+)["\'])?(?:\s+class=["\']([^"\']+)["\'])?\]/i',
            function($matches) {
                $slug = $matches[1] ?? '';
                $location = $matches[2] ?? 'widget';
                $cssClass = $matches[3] ?? 'menu-widget';
                
                if (empty($slug)) {
                    return '';
                }
                
                return $this->renderMenuWidget($slug, $location, $cssClass);
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Рендеринг віджета меню
     * 
     * @param string $slug Slug меню
     * @param string $location Розташування віджета
     * @param string $cssClass CSS клас
     * @return string HTML віджета
     */
    public function renderMenuWidget(string $slug, string $location = 'widget', string $cssClass = 'menu-widget'): string {
        $menuManager = menuManager();
        $menu = $menuManager->getMenu($slug);
        
        if (!$menu) {
            return '';
        }
        
        $menuItems = $menuManager->getMenuItems($menu['id']);
        
        if (empty($menuItems)) {
            return '';
        }
        
        $html = '<div class="menu-widget menu-widget-' . htmlspecialchars($location) . ' ' . htmlspecialchars($cssClass) . '">';
        $html .= '<ul class="menu-widget-list">';
        
        foreach ($menuItems as $item) {
            $html .= '<li class="menu-widget-item">';
            $html .= '<a href="' . htmlspecialchars($item['url']) . '"';
            
            if ($item['target'] !== '_self') {
                $html .= ' target="' . htmlspecialchars($item['target']) . '"';
            }
            
            if (!empty($item['css_classes'])) {
                $html .= ' class="' . htmlspecialchars($item['css_classes']) . '"';
            }
            
            $html .= '>';
            
            if (!empty($item['icon'])) {
                $html .= '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
            }
            
            $html .= htmlspecialchars($item['title']);
            $html .= '</a>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Рендеринг меню за slug (для використання в темах)
     * 
     * @param string $slug Slug меню
     * @param string $cssClass CSS клас для меню
     * @return string HTML меню
     */
    public function renderMenuBySlug(string $slug, string $cssClass = 'menu'): string {
        $menuManager = menuManager();
        return $menuManager->renderMenu($slug, $cssClass);
    }
    
    /**
     * Отримання менеджера меню
     * 
     * @return MenuManager
     */
    public function getManager(): MenuManager {
        return menuManager();
    }
    
    /**
     * Реєстрація віджетів меню
     * 
     * @param array $widgets Поточні віджети
     * @return array Оновлені віджети
     */
    public function registerMenuWidgets(array $widgets): array {
        $menuManager = menuManager();
        $allMenus = $menuManager->getAllMenus();
        
        foreach ($allMenus as $menu) {
            // Додаємо віджети тільки для довільних меню
            if ($menu['location'] === 'custom') {
                $widgets[] = [
                    'id' => 'menu_' . $menu['slug'],
                    'title' => $menu['name'],
                    'description' => $menu['description'] ?? 'Меню: ' . $menu['name'],
                    'callback' => function($args = []) use ($menu) {
                        return $this->renderMenuWidget(
                            $menu['slug'],
                            $args['location'] ?? 'widget',
                            $args['class'] ?? 'menu-widget'
                        );
                    }
                ];
            }
        }
        
        return $widgets;
    }
    
    /**
     * Рендеринг меню в хедері
     * 
     * @return void
     */
    public function renderHeaderMenu(): void {
        // Убеждаемся, что MenuManager загружен
        if (!class_exists('MenuManager')) {
            $menuManagerFile = dirname(__DIR__) . '/classes/managers/MenuManager.php';
            if (file_exists($menuManagerFile)) {
                require_once $menuManagerFile;
            }
        }
        
        if (!function_exists('menuManager')) {
            return;
        }
        
        $menuManager = menuManager();
        if (!$menuManager) {
            return;
        }
        
        // Получаем активную тему
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            return;
        }
        
        $themeSlug = $activeTheme['slug'];
        
        // Получаем сохраненный slug меню из настроек текущей темы
        $savedMenuSlug = themeManager()->getSetting('menu_slug');
        
        // Если не получили через getSetting, пробуем напрямую из БД
        if (empty($savedMenuSlug)) {
            $db = getDB();
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT setting_value FROM theme_settings WHERE theme_slug = ? AND setting_key = 'menu_slug' LIMIT 1");
                    $stmt->execute([$themeSlug]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result && !empty($result['setting_value'])) {
                        $savedMenuSlug = $result['setting_value'];
                    }
                } catch (Exception $e) {
                    // Игнорируем ошибки
                }
            }
        }
        
        $menu = null;
        
        // Если есть сохраненный slug меню, используем его
        if (!empty($savedMenuSlug)) {
            $menu = $menuManager->getMenu($savedMenuSlug);
        }
        
        // Если меню не найдено по slug, пробуем найти по расположению из theme.json
        if (!$menu) {
            // Получаем расположения меню из theme.json
            $themeConfig = themeManager()->getThemeConfig($themeSlug);
            $menuLocations = $themeConfig['menu_locations'] ?? [];
            
            // Берем первое доступное расположение (обычно 'header')
            $location = 'header';
            if (!empty($menuLocations)) {
                $location = array_key_first($menuLocations);
            }
            
            $menu = $this->getMenuByLocation($location);
        }
        
        if ($menu) {
            // Получаем все пункты меню (включая вложенные)
            $allMenuItems = $menuManager->getAllMenuItems($menu['id']);
            
            if (empty($allMenuItems)) {
                return;
            }
            
            // Фильтруем только активные пункты
            $activeMenuItems = array_filter($allMenuItems, function($item) {
                return ($item['is_active'] == '1' || $item['is_active'] == 1);
            });
            
            if (!empty($activeMenuItems)) {
                echo $this->renderMenuItems($activeMenuItems);
            }
        }
    }
    
    /**
     * Рендеринг пунктів меню
     * 
     * @param array $menuItems Масив пунктів меню
     * @param int|null $parentId ID батьківського пункту (для рекурсивного виведення)
     * @return string HTML меню
     */
    private function renderMenuItems(array $menuItems, ?int $parentId = null): string {
        // Фільтруємо пункти за parent_id
        $filteredItems = array_filter($menuItems, function($item) use ($parentId) {
            // Проверяем is_active отдельно, так как мы уже отфильтровали активные пункты выше
            // Но оставляем проверку для безопасности
            $isActive = ($item['is_active'] == '1' || $item['is_active'] == 1);
            if (!$isActive) {
                return false;
            }
            
            // Обрабатываем parent_id: null, 0, или число
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
        
        // Сортуємо по order_num
        usort($filteredItems, function($a, $b) {
            $orderA = isset($a['order_num']) ? (int)$a['order_num'] : 0;
            $orderB = isset($b['order_num']) ? (int)$b['order_num'] : 0;
            return $orderA - $orderB;
        });
        
        $html = '<ul class="menu menu-header">';
        
        foreach ($filteredItems as $item) {
            $target = !empty($item['target']) ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
            $cssClasses = !empty($item['css_classes']) ? ' class="' . htmlspecialchars($item['css_classes']) . '"' : '';
            $icon = !empty($item['icon']) ? '<i class="' . htmlspecialchars($item['icon']) . '"></i> ' : '';
            
            $itemId = isset($item['id']) ? (int)$item['id'] : null;
            
            // Проверяем, есть ли дочерние элементы
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
            
            $html .= '<li class="menu-item' . ($hasChildren ? ' has-submenu' : '') . '">';
            $html .= '<a href="' . htmlspecialchars($item['url'] ?? '#') . '"' . $target . $cssClasses . '>';
            $html .= $icon . htmlspecialchars($item['title'] ?? '');
            $html .= '</a>';
            
            // Рекурсивно выводим дочерние элементы
            if ($hasChildren && $itemId !== null) {
                $html .= $this->renderMenuItems($menuItems, $itemId);
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }
    
    /**
     * Рендеринг меню в футері
     * 
     * @param string $footerContent Поточний контент футера
     * @return string Оновлений контент футера
     */
    public function renderFooterMenus(?string $footerContent = null): string {
        if ($footerContent === null) {
            $footerContent = '';
        }
        $menuManager = menuManager();
        $footerMenu = $menuManager->getMenu('footer');
        
        if ($footerMenu) {
            $menuItems = $menuManager->getMenuItems($footerMenu['id']);
            if (!empty($menuItems)) {
                $menuHtml = $this->renderMenuWidget($footerMenu['slug'], 'footer', 'footer-menu');
                // Вставляємо меню перед закриваючим тегом footer
                $footerContent = str_replace('</footer>', $menuHtml . '</footer>', $footerContent);
            }
        }
        
        return $footerContent;
    }
    
    /**
     * Отримання меню для конкретного розташування
     * 
     * @param string $location Розташування меню
     * @return array|null Меню або null
     */
    public function getMenuByLocation(string $location): ?array {
        $menuManager = menuManager();
        $menus = $menuManager->getAllMenus();
        
        foreach ($menus as $menu) {
            if ($menu['location'] === $location) {
                return $menu;
            }
        }
        
        return null;
    }
    
    /**
     * Перевірка наявності меню для розташування
     * 
     * @param string $location Розташування меню
     * @return bool
     */
    public function hasMenuForLocation(string $location): bool {
        return $this->getMenuByLocation($location) !== null;
    }
}

/**
 * Глобальна функція для отримання модуля меню
 */
function menuModule() {
    return Menu::getInstance();
}

