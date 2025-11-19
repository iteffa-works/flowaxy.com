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
    // Создаем уникальный ключ кеша на основе активных плагинов
    $pluginsHash = cache_remember('active_plugins_hash', function() {
        $activePlugins = pluginManager()->getActivePlugins();
        return md5(implode(',', array_keys($activePlugins)));
    }, 3600); // Кешируем хеш плагинов на 1 час
    
    $cacheKey = 'admin_menu_items_' . $pluginsHash;
    
    return cache_remember($cacheKey, function() {
        // Починаємо з порожнього меню - всі пункти додаються через плагіни
        $menu = [];
        
        // Ініціалізуємо плагіни перед викликом хука admin_menu
        // Це потрібно, щоб плагіни могли зареєструвати свої пункти меню
        if (function_exists('pluginManager')) {
            $pluginManager = pluginManager();
            if ($pluginManager && method_exists($pluginManager, 'initializePlugins')) {
                $pluginManager->initializePlugins();
            }
        }
        
        // Застосовуємо хук для додавання пунктів меню від плагінів
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
        
        // Видаляємо меню з порожніми підменю
        $menuKeysToRemove = [];
        foreach ($menu as $key => $item) {
            if (isset($item['submenu']) && is_array($item['submenu']) && empty($item['submenu'])) {
                // Видаляємо тільки якщо це не прямий посилання (href !== '#')
                if (($item['href'] ?? '#') === '#') {
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
    }, 3600); // Кешуємо на 1 годину (меню змінюється рідко, тільки при зміні плагінів)
}

/**
 * Рендерить пункт меню
 */
function renderMenuItem($item, $currentPage, $isMobile = false) {
    $hasSubmenu = isset($item['submenu']) && !empty($item['submenu']);
    $target = isset($item['target']) ? ' target="' . $item['target'] . '"' : '';
    
    // Получаем параметр tab из URL один раз
    $request = Request::getInstance();
    $tab = SecurityHelper::sanitizeInput($request->query('tab', ''));
    
    if ($hasSubmenu) {
        // Меню з підменю (однаково для десктопу та мобільних)
        // Основной пункт меню активен только если активна одна из его подменю
        $activeClass = '';
        $hasActiveSubmenu = false;
        
        if (isset($item['submenu'])) {
            foreach ($item['submenu'] as $subItem) {
                if (isset($subItem['page'])) {
                    $subItemPage = $subItem['page'];
                    
                    // Проверяем точное совпадение с текущей страницей
                    if ($currentPage === $subItemPage) {
                        // Если нет tab, значит это главная страница
                        if (empty($tab)) {
                            $hasActiveSubmenu = true;
                            break;
                        }
                    }
                    
                    // Если есть tab, проверяем совпадение с учетом tab
                    if (!empty($tab)) {
                        $pageWithTab = $currentPage . '-' . $tab;
                        if ($subItemPage === $pageWithTab) {
                            $hasActiveSubmenu = true;
                            break;
                        }
                    }
                }
            }
        }
        
        // Основной пункт меню активен только если активна подменю
        if ($hasActiveSubmenu) {
            $activeClass = 'active';
        }
        
        if ($isMobile) {
            // Мобільна версія з підменю
            echo '<li class="nav-item has-submenu">';
            echo '<a class="nav-link submenu-toggle ' . $activeClass . '" href="#" onclick="toggleSubmenu(this, event)">';
            echo '<i class="' . ($item['icon'] ?? '') . '"></i>';
            echo '<span class="menu-text">' . htmlspecialchars($item['text'] ?? $item['title'] ?? '') . '</span>';
            echo '<i class="fas fa-chevron-down submenu-arrow"></i>';
            echo '</a>';
            echo '<ul class="submenu">';
            foreach ($item['submenu'] as $subItem) {
                // Определяем активный пункт подменю с учетом tab
                $subActive = false;
                if (isset($subItem['page'])) {
                    $subItemPage = $subItem['page'];
                    // Проверяем точное совпадение (для главной страницы без tab)
                    if ($currentPage === $subItemPage && empty($tab)) {
                        $subActive = true;
                    }
                    // Если есть tab, проверяем совпадение с учетом tab
                    if (!empty($tab)) {
                        $pageWithTab = $currentPage . '-' . $tab;
                        if ($subItemPage === $pageWithTab) {
                            $subActive = true;
                        }
                    }
                }
                $subActiveClass = $subActive ? 'active' : '';
                $subIcon = isset($subItem['icon']) ? '<i class="' . $subItem['icon'] . '"></i>' : '';
                echo '<li><a href="' . $subItem['href'] . '" class="submenu-link ' . $subActiveClass . '">' . $subIcon . '<span class="menu-text">' . htmlspecialchars($subItem['text'] ?? $subItem['title'] ?? '') . '</span></a></li>';
            }
            echo '</ul>';
            echo '</li>';
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
                // Определяем активный пункт подменю с учетом tab
                $subActive = false;
                if (isset($subItem['page'])) {
                    $subItemPage = $subItem['page'];
                    // Проверяем точное совпадение (для главной страницы без tab)
                    if ($currentPage === $subItemPage && empty($tab)) {
                        $subActive = true;
                    }
                    // Если есть tab, проверяем совпадение с учетом tab
                    if (!empty($tab)) {
                        $pageWithTab = $currentPage . '-' . $tab;
                        if ($subItemPage === $pageWithTab) {
                            $subActive = true;
                        }
                    }
                }
                $subActiveClass = $subActive ? 'active' : '';
                $subIcon = isset($subItem['icon']) ? '<i class="' . $subItem['icon'] . '"></i>' : '';
                echo '<li><a href="' . $subItem['href'] . '" class="' . $subActiveClass . '">' . $subIcon . '<span class="menu-text">' . htmlspecialchars($subItem['text'] ?? $subItem['title'] ?? '') . '</span></a></li>';
            }
            echo '</ul>';
            echo '</li>';
        }
    } else {
        // Звичайний пункт меню (без підменю)
        $isActive = false;
        if (isset($item['page'])) {
            $itemPage = $item['page'];
            // Проверяем точное совпадение
            if ($currentPage === $itemPage) {
                // Если нет tab, значит это активная страница
                if (empty($tab)) {
                    $isActive = true;
                }
            }
            // Если есть tab, проверяем совпадение с учетом tab
            if (!empty($tab)) {
                $pageWithTab = $currentPage . '-' . $tab;
                if ($itemPage === $pageWithTab) {
                    $isActive = true;
                }
            }
        }
        
        $activeClass = $isActive ? 'active' : '';
        // Всегда используем <li> для валидности HTML (ul должен содержать только li, script или template)
        echo '<li class="nav-item">';
        echo '<a class="nav-link ' . $activeClass . '" href="' . $item['href'] . '"' . $target . '>';
        echo '<i class="' . ($item['icon'] ?? '') . '"></i>';
        echo '<span class="menu-text">' . htmlspecialchars($item['text'] ?? $item['title'] ?? '') . '</span>';
        echo '</a>';
        echo '</li>';
    }
}
?>
