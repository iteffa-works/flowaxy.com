<?php
/**
 * Компонент Sidebar
 */
?>
<nav id="sidebarMenu" class="sidebar d-md-block collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column wp-menu">
            <?php
            require_once __DIR__ . '/../../includes/menu-items.php';
            if (!class_exists('Router')) {
                require_once __DIR__ . '/../../includes/Router.php';
            }
            $currentPage = Router::getCurrentPage();
            $menuItems = getMenuItems();
            foreach ($menuItems as $item) {
                renderMenuItem($item, $currentPage);
            }
            ?>
        </ul>
    </div>
</nav>
