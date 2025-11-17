<?php
/**
 * Універсальні пункти меню для sidebar та мобільного меню
 * Повертає масив пунктів меню
 */

/**
 * Перевірка підтримки кастомізації активною темою
 * Використовує оптимізований метод ThemeManager
 */
function themeSupportsCustomization() {
    return themeManager()->supportsCustomization();
}

/**
 * Перевірка підтримки навігації активною темою
 * Використовує оптимізований метод ThemeManager
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
    
    // Формуємо підменю "Дизайн" (тільки для кастомізації та навігації)
    $designSubmenu = [];
    
    // Додаємо пункт кастомізації тільки якщо тема підтримує
    if ($supportsCustomization) {
        $designSubmenu[] = [
            'href' => UrlHelper::admin('customizer'),
            'text' => 'Кастомізація',
            'page' => 'customizer',
            'order' => 1
        ];
    }
    
    // Додаємо пункт навігації тільки якщо тема підтримує
    if ($supportsNavigation) {
        $designSubmenu[] = [
            'href' => UrlHelper::admin('menus'),
            'text' => 'Навігація',
            'page' => 'menus',
            'order' => 2
        ];
    }
    
    $menu = [
        [
            'href' => UrlHelper::admin('settings'),
            'icon' => 'fas fa-cog',
            'text' => 'Загальні налаштування',
            'page' => 'settings',
            'order' => 50
        ],
        [
            'href' => '#',
            'icon' => 'fas fa-sliders-h',
            'text' => 'Налаштування плагінів',
            'page' => 'plugin-settings',
            'order' => 55,
            'submenu' => []
        ],
        [
            'href' => '#',
            'icon' => 'fas fa-palette',
            'text' => 'Налаштування тем',
            'page' => 'theme-settings',
            'order' => 56,
            'submenu' => []
        ],
        [
            'href' => '#',
            'icon' => 'fas fa-code',
            'text' => 'Для розробника',
            'page' => 'developer',
            'order' => 60,
            'submenu' => []
        ]
    ];
    
    // Додаємо меню "Налаштування дизайну" тільки якщо є підменю (кастомізація або навігація)
    // Розміщуємо після "Налаштування плагінів" (order 55)
    if (!empty($designSubmenu)) {
        $menu[] = [
            'href' => '#',
            'icon' => 'fas fa-paint-brush',
            'text' => 'Налаштування дизайну',
            'page' => 'design',
            'order' => 57,
            'submenu' => $designSubmenu
        ];
    }
    
    // Ініціалізуємо плагіни перед викликом хука admin_menu
    // Це потрібно, щоб плагіни могли зареєструвати свої пункти меню
    if (function_exists('pluginManager')) {
        $pluginManager = pluginManager();
        if ($pluginManager && method_exists($pluginManager, 'initializePlugins')) {
            $pluginManager->initializePlugins();
        }
    }
    
    // Застосовуємо хук для додавання пунктів меню від плагінів
    // Модулі завантажаться тільки один раз при першому виклику
    $menu = doHook('admin_menu', $menu);
    
    // Сортуємо по order
    usort($menu, function($a, $b) {
        $orderA = $a['order'] ?? 50;
        $orderB = $b['order'] ?? 50;
        return $orderA - $orderB;
    });
    
    // Сортуємо подменю для пунктів с подменю
    foreach ($menu as $key => $item) {
        if (isset($item['submenu']) && is_array($item['submenu'])) {
            usort($menu[$key]['submenu'], function($a, $b) {
                $orderA = $a['order'] ?? 50;
                $orderB = $b['order'] ?? 50;
                return $orderA - $orderB;
            });
        }
    }
    
    // Видаляємо меню "Налаштування плагінів" та "Налаштування тем" якщо підменю порожнє
    $menuKeysToRemove = [];
    foreach ($menu as $key => $item) {
        if (isset($item['page']) && ($item['page'] === 'plugin-settings' || $item['page'] === 'theme-settings')) {
            $submenuCount = 0;
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $submenuCount = count($item['submenu']);
            }
            
            // Удаляем меню, если нет пунктов в подменю
            if ($submenuCount === 0) {
                $menuKeysToRemove[] = $key;
            }
        }
    }
    
    // Удаляем найденные меню
    foreach ($menuKeysToRemove as $key) {
        unset($menu[$key]);
    }
    
    // Переиндексируем массив после удаления
    if (!empty($menuKeysToRemove)) {
        $menu = array_values($menu);
    }
    
        // Повертаємо меню
        return $menu;
    }, 3600); // Кешуємо на 1 годину (меню змінюється рідко, тільки при зміні плагінів або теми)
}

/**
 * Рендерить пункт меню
 */
function renderMenuItem($item, $currentPage, $isMobile = false) {
    $isActive = isset($item['page']) && $currentPage === $item['page'];
    $hasSubmenu = isset($item['submenu']) && !empty($item['submenu']);
    $target = isset($item['target']) ? ' target="' . $item['target'] . '"' : '';
    
    if ($hasSubmenu) {
        // Меню з підменю (однаково для десктопу та мобільних)
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
            // Мобільна версія з підменю
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
            // Десктопна версія з підменю
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
        // Звичайний пункт меню (без підменю)
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
