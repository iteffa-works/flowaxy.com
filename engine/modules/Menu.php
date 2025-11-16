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
        
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        $menuLocations = $themeConfig['menu_locations'] ?? [];
        
        // Якщо тема не підтримує навігацію, повертаємо порожній масив
        if (!($themeConfig['supports_navigation'] ?? false)) {
            return [];
        }
        
        return $menuLocations;
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
     * Рендеринг меню в футері
     * 
     * @param string $footerContent Поточний контент футера
     * @return string Оновлений контент футера
     */
    public function renderFooterMenus(string $footerContent): string {
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

