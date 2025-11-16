<?php
/**
 * Модуль управления меню навигации
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/classes/BaseModule.php';
require_once dirname(__DIR__) . '/classes/MenuManager.php';

class Menu extends BaseModule {
    
    /**
     * Инициализация модуля
     */
    protected function init(): void {
        // Модуль инициализирован
    }
    
    /**
     * Регистрация хуков модуля
     */
    public function registerHooks(): void {
        // Регистрация пункта меню в админке
        addHook('admin_menu', [$this, 'addAdminMenuItem']);
        
        // Регистрация маршрута админки
        addHook('admin_register_routes', [$this, 'registerAdminRoute']);
        
        // Регистрация обработки шорткодов для произвольных меню
        addHook('theme_content', [$this, 'processShortcodes'], 20);
        
        // Регистрация виджетов меню
        addHook('theme_widgets', [$this, 'registerMenuWidgets']);
        
        // Регистрация хука для встраивания меню в футер
        addHook('theme_footer', [$this, 'renderFooterMenus']);
    }
    
    /**
     * Добавление пункта меню в админку
     * Добавляется только если активная тема поддерживает навигацию
     * 
     * @param array $menu Текущее меню
     * @return array Обновленное меню
     */
    public function addAdminMenuItem(array $menu): array {
        // Добавляем пункт меню только если тема поддерживает навигацию
        if ($this->themeSupportsNavigation()) {
            $menu[] = [
                'href' => adminUrl('menus'),
                'icon' => 'fas fa-bars',
                'text' => 'Меню',
                'page' => 'menus',
                'order' => 15
            ];
        }
        return $menu;
    }
    
    /**
     * Регистрация маршрута админки
     * 
     * @param Router|null $router Роутер админки
     */
    public function registerAdminRoute($router): void {
        if ($router === null) {
            return;
        }
        
        // Регистрируем маршрут только если тема поддерживает навигацию
        if (!$this->themeSupportsNavigation()) {
            return;
        }
        
        require_once dirname(__DIR__) . '/skins/pages/MenusPage.php';
        $router->add('menus', 'MenusPage');
    }
    
    /**
     * Получение информации о модуле
     * 
     * @return array
     */
    public function getInfo(): array {
        return [
            'name' => 'Menu',
            'title' => 'Меню навігації',
            'description' => 'Управління меню навігації та розташуваннями',
            'version' => '1.0.0',
            'author' => 'Landing CMS'
        ];
    }
    
    /**
     * Получение доступных расположений меню из активной темы
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
        
        // Если тема не поддерживает навигацию, возвращаем пустой массив
        if (!($themeConfig['supports_navigation'] ?? false)) {
            return [];
        }
        
        return $menuLocations;
    }
    
    /**
     * Проверка поддержки навигации активной темой
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
     * Обработка шорткодов меню в контенте
     * 
     * @param string $content Контент страницы
     * @return string Обработанный контент
     */
    public function processShortcodes(string $content): string {
        // Обработка шорткода [menu slug="menu-slug"]
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
                
                // Добавляем стили если указаны
                if (!empty($style)) {
                    $html = str_replace('<ul', '<ul style="' . htmlspecialchars($style) . '"', $html);
                }
                
                return $html;
            },
            $content
        );
        
        // Обработка шорткода [menu_widget slug="menu-slug" location="footer"]
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
     * Рендеринг виджета меню
     * 
     * @param string $slug Slug меню
     * @param string $location Расположение виджета
     * @param string $cssClass CSS класс
     * @return string HTML виджета
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
     * Рендеринг меню по slug (для использования в темах)
     * 
     * @param string $slug Slug меню
     * @param string $cssClass CSS класс для меню
     * @return string HTML меню
     */
    public function renderMenuBySlug(string $slug, string $cssClass = 'menu'): string {
        $menuManager = menuManager();
        return $menuManager->renderMenu($slug, $cssClass);
    }
    
    /**
     * Получение менеджера меню
     * 
     * @return MenuManager
     */
    public function getManager(): MenuManager {
        return menuManager();
    }
    
    /**
     * Регистрация виджетов меню
     * 
     * @param array $widgets Текущие виджеты
     * @return array Обновленные виджеты
     */
    public function registerMenuWidgets(array $widgets): array {
        $menuManager = menuManager();
        $allMenus = $menuManager->getAllMenus();
        
        foreach ($allMenus as $menu) {
            // Добавляем виджеты только для произвольных меню
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
     * Рендеринг меню в футере
     * 
     * @param string $footerContent Текущий контент футера
     * @return string Обновленный контент футера
     */
    public function renderFooterMenus(string $footerContent): string {
        $menuManager = menuManager();
        $footerMenu = $menuManager->getMenu('footer');
        
        if ($footerMenu) {
            $menuItems = $menuManager->getMenuItems($footerMenu['id']);
            if (!empty($menuItems)) {
                $menuHtml = $this->renderMenuWidget($footerMenu['slug'], 'footer', 'footer-menu');
                // Вставляем меню перед закрывающим тегом footer
                $footerContent = str_replace('</footer>', $menuHtml . '</footer>', $footerContent);
            }
        }
        
        return $footerContent;
    }
    
    /**
     * Получение меню для конкретного расположения
     * 
     * @param string $location Расположение меню
     * @return array|null Меню или null
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
     * Проверка наличия меню для расположения
     * 
     * @param string $location Расположение меню
     * @return bool
     */
    public function hasMenuForLocation(string $location): bool {
        return $this->getMenuByLocation($location) !== null;
    }
}

/**
 * Глобальная функция для получения модуля меню
 */
function menuModule() {
    return Menu::getInstance();
}

