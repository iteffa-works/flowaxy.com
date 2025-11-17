<?php
/**
 * Компонент Sidebar
 */
?>
<nav id="sidebarMenu" class="sidebar d-md-block show">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column wp-menu">
            <?php
            require_once __DIR__ . '/../../includes/menu-items.php';
            // Router завантажується автоматично через автозавантажувач
            $currentPage = Router::getCurrentPage();
            $menuItems = getMenuItems();
            foreach ($menuItems as $item) {
                renderMenuItem($item, $currentPage);
            }
            ?>
        </ul>
    </div>
</nav>
