<?php
/**
 * Компонент Header
 */
?>
<!-- Верхняя зеленая полоска -->
<div class="header-top-bar"></div>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <!-- Левая часть навбара -->
        <div class="d-flex align-items-center">
            <!-- Кнопка мобильного меню -->
            <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Логотип -->
            <a class="navbar-brand d-none d-md-flex align-items-center header-logo" href="<?= UrlHelper::admin('dashboard') ?>">
                <div class="admin-logo-icon me-2">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="logo-text">Flowaxy CMS</span>
            </a>
        </div>

        <!-- Правая часть навбара - иконки в ряд -->
        <div class="d-flex align-items-center gap-2">
            <!-- Иконки быстрого доступа к разделам -->
            <div class="header-quick-actions d-flex align-items-center">
                <a href="<?= UrlHelper::admin('dashboard') ?>" class="header-quick-action-btn" title="Панель управління">
                    <i class="fas fa-home"></i>
                    <span class="d-none d-md-inline ms-1">Панель управління</span>
                </a>
                <a href="<?= UrlHelper::admin('media') ?>" class="header-quick-action-btn" title="Медіа-бібліотека">
                    <i class="fas fa-images"></i>
                    <span class="d-none d-md-inline ms-1">Медіафайли</span>
                </a>
                <a href="<?= UrlHelper::admin('plugins') ?>" class="header-quick-action-btn" title="Плагіни">
                    <i class="fas fa-puzzle-piece"></i>
                    <span class="d-none d-md-inline ms-1">Плагіни</span>
                </a>
                <a href="<?= UrlHelper::admin('themes') ?>" class="header-quick-action-btn" title="Теми оформлення">
                    <i class="fas fa-palette"></i>
                    <span class="d-none d-md-inline ms-1">Теми</span>
                </a>
            </div>
            
            <!-- Просмотр сайта -->
            <a href="<?= defined('SITE_URL') ? SITE_URL : '/' ?>" 
               class="header-view-site-btn" 
               target="_blank" 
               title="Сайт"
               rel="noopener noreferrer">
                <i class="fas fa-external-link-square-alt"></i>
            </a>
            
            <!-- Управление кешем -->
            <div class="dropdown">
                <button class="header-dropdown-btn" type="button" data-bs-toggle="dropdown" title="Очистити кеш">
                    <i class="fas fa-database"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button" class="dropdown-item cache-clear-btn" data-action="clear_all">
                            <i class="fas fa-trash me-2"></i>Очистити весь кеш
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item cache-clear-btn" data-action="clear_expired">
                            <i class="fas fa-clock me-2"></i>Очистити прострочений кеш
                        </button>
                    </li>
                </ul>
            </div>
            
            <!-- Dropdown пользователя -->
            <div class="dropdown">
                <button class="header-dropdown-btn" type="button" data-bs-toggle="dropdown" title="Користувач">
                    <i class="fas fa-user"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= UrlHelper::admin('profile') ?>"><i class="fas fa-user me-2"></i>Профіль</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= UrlHelper::admin('logout') ?>?token=<?= SecurityHelper::csrfToken() ?>"><i class="fas fa-sign-out-alt me-2"></i>Вийти</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Мобильная боковая панель -->
<div class="mobile-sidebar-overlay" onclick="toggleMobileSidebar()"></div>
<div class="mobile-sidebar">
    <!-- Заголовок мобильной панели -->
    <div class="mobile-sidebar-header">
        <div class="mobile-sidebar-logo">
            <div class="admin-logo-icon">
                <i class="fas fa-cog"></i>
            </div>
            <span class="mobile-sidebar-title">Flowaxy CMS</span>
        </div>
        <button class="mobile-sidebar-close" onclick="toggleMobileSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="nav flex-column wp-menu">
        <?php
        require_once __DIR__ . '/../../includes/menu-items.php';
        $currentPage = basename($_SERVER['PHP_SELF']);
        $menuItems = getMenuItems();
        foreach ($menuItems as $item) {
            renderMenuItem($item, $currentPage, true);
        }
        ?>
    </nav>
</div>
