<?php
/**
 * Универсальные пункты меню для sidebar и мобильного меню
 * Возвращает массив пунктов меню
 */

/**
 * Проверка поддержки кастоматизации активной темой
 * Использует оптимизированный метод ThemeManager
 */
function themeSupportsCustomization() {
    return themeManager()->supportsCustomization();
}

/**
 * Проверка поддержки навигации активной темой
 * Использует оптимизированный метод ThemeManager
 */
function themeSupportsNavigation() {
    return themeManager()->supportsNavigation();
}

function getMenuItems() {
    $supportsCustomization = themeSupportsCustomization();
    $supportsNavigation = themeSupportsNavigation();
    
    // Создаем уникальный ключ кеша на основе поддержки функций темы и активных плагинов
    // Кешируем хеш плагинов, чтобы не вычислять его каждый раз
    $pluginsHash = cache_remember('active_plugins_hash', function() {
        $activePlugins = pluginManager()->getActivePlugins();
        return md5(implode(',', array_keys($activePlugins)));
    }, 3600); // Кешируем хеш плагинов на 1 час
    
    $cacheKey = 'admin_menu_items_' . ($supportsCustomization ? '1' : '0') . '_' . ($supportsNavigation ? '1' : '0') . '_' . $pluginsHash;
    
    return cache_remember($cacheKey, function() use ($supportsCustomization, $supportsNavigation) {
    
    // Формируем подменю "Дизайн"
    $designSubmenu = [
        [
            'href' => adminUrl('themes'),
            'text' => 'Теми оформлення',
            'page' => 'themes',
            'order' => 1
        ]
    ];
    
    // Добавляем пункт кастоматизации только если тема поддерживает
    if ($supportsCustomization) {
        $designSubmenu[] = [
            'href' => adminUrl('customizer'),
            'text' => 'Кастомізація',
            'page' => 'customizer',
            'order' => 2
        ];
    }
    
    // Добавляем пункт навигации только если тема поддерживает
    if ($supportsNavigation) {
        $designSubmenu[] = [
            'href' => adminUrl('menus'),
            'text' => 'Навігація',
            'page' => 'menus',
            'order' => 3
        ];
    }
    
    $menu = [
        [
            'href' => adminUrl('dashboard'),
            'icon' => 'fas fa-tachometer-alt',
            'text' => 'Панель управління',
            'page' => 'dashboard',
            'order' => 10
        ],
        [
            'href' => '#',
            'icon' => 'fas fa-paint-brush',
            'text' => 'Дизайн',
            'page' => 'themes',
            'order' => 30,
            'submenu' => $designSubmenu
        ],
        [
            'href' => adminUrl('plugins'),
            'icon' => 'fas fa-puzzle-piece',
            'text' => 'Плагіни',
            'page' => 'plugins',
            'order' => 90
        ],
        [
            'href' => '#',
            'icon' => 'fas fa-cog',
            'text' => 'Налаштування',
            'page' => 'settings',
            'order' => 100,
            'submenu' => [
                [
                    'href' => adminUrl('settings'),
                    'text' => 'Загальні налаштування',
                    'page' => 'settings',
                    'order' => 1
                ],
                [
                    'href' => adminUrl('system'),
                    'text' => 'Системна інформація',
                    'page' => 'system',
                    'order' => 3
                ]
            ]
        ]
    ];
    
    // Применяем хук для добавления пунктов меню от плагинов
    // Модули загрузятся только один раз при первом вызове
    $menu = doHook('admin_menu', $menu);
    
    // Сортируем по order
    usort($menu, function($a, $b) {
        $orderA = $a['order'] ?? 50;
        $orderB = $b['order'] ?? 50;
        return $orderA - $orderB;
    });
    
        // Возвращаем меню
        return $menu;
    }, 3600); // Кешируем на 1 час (меню меняется редко, только при изменении плагинов или темы)
}

/**
 * Рендерит пункт меню
 */
function renderMenuItem($item, $currentPage, $isMobile = false) {
    $isActive = isset($item['page']) && $currentPage === $item['page'];
    $hasSubmenu = isset($item['submenu']) && !empty($item['submenu']);
    $target = isset($item['target']) ? ' target="' . $item['target'] . '"' : '';
    
    if ($hasSubmenu) {
        // Меню с субменю (одинаково для десктопа и мобильных)
        $activeClass = '';
        if (isset($item['submenu'])) {
            foreach ($item['submenu'] as $subItem) {
                if (isset($subItem['page']) && $currentPage === $subItem['page']) {
                    $activeClass = 'active';
                    break;
                }
            }
        }
        if ($isActive) $activeClass = 'active';
        
        if ($isMobile) {
            // Мобильная версия с субменю
            echo '<div class="nav-item has-submenu">';
            echo '<a class="nav-link submenu-toggle ' . $activeClass . '" href="#" onclick="toggleSubmenu(this, event)">';
            echo '<i class="' . ($item['icon'] ?? '') . '"></i>';
            echo '<span class="menu-text">' . htmlspecialchars($item['text'] ?? $item['title'] ?? '') . '</span>';
            echo '<i class="fas fa-chevron-down submenu-arrow"></i>';
            echo '</a>';
            echo '<div class="submenu">';
            foreach ($item['submenu'] as $subItem) {
                $subActive = isset($subItem['page']) && $currentPage === $subItem['page'] ? 'active' : '';
                $subIcon = isset($subItem['icon']) ? '<i class="' . $subItem['icon'] . '"></i>' : '';
                echo '<a href="' . $subItem['href'] . '" class="submenu-link ' . $subActive . '">' . $subIcon . '<span class="menu-text">' . htmlspecialchars($subItem['text'] ?? $subItem['title'] ?? '') . '</span></a>';
            }
            echo '</div>';
            echo '</div>';
        } else {
            // Десктопная версия с субменю
            echo '<li class="nav-item has-submenu">';
            echo '<a class="nav-link submenu-toggle ' . $activeClass . '" href="#" onclick="toggleSubmenu(this, event)">';
            echo '<i class="' . ($item['icon'] ?? '') . '"></i>';
            echo '<span class="menu-text">' . htmlspecialchars($item['text'] ?? $item['title'] ?? '') . '</span>';
            echo '<i class="fas fa-chevron-down submenu-arrow"></i>';
            echo '</a>';
            echo '<ul class="submenu">';
            foreach ($item['submenu'] as $subItem) {
                $subActive = isset($subItem['page']) && $currentPage === $subItem['page'] ? 'active' : '';
                $subIcon = isset($subItem['icon']) ? '<i class="' . $subItem['icon'] . '"></i>' : '';
                echo '<li><a href="' . $subItem['href'] . '" class="' . $subActive . '">' . $subIcon . '<span class="menu-text">' . htmlspecialchars($subItem['text'] ?? $subItem['title'] ?? '') . '</span></a></li>';
            }
            echo '</ul>';
            echo '</li>';
        }
    } else {
        // Обычный пункт меню (без субменю)
        $activeClass = $isActive ? 'active' : '';
        if ($isMobile) {
            echo '<a class="nav-link ' . $activeClass . '" href="' . $item['href'] . '"' . $target . '>';
            echo '<i class="' . ($item['icon'] ?? '') . '"></i>';
            echo '<span class="menu-text">' . htmlspecialchars($item['text'] ?? $item['title'] ?? '') . '</span>';
            echo '</a>';
        } else {
            echo '<li class="nav-item">';
            echo '<a class="nav-link ' . $activeClass . '" href="' . $item['href'] . '"' . $target . '>';
            echo '<i class="' . ($item['icon'] ?? '') . '"></i>';
            echo '<span class="menu-text">' . htmlspecialchars($item['text'] ?? $item['title'] ?? '') . '</span>';
            echo '</a>';
            echo '</li>';
        }
    }
}
?>
