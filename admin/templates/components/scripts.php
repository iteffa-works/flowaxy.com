<?php
/**
 * Компонент JavaScript скриптов
 */
?>
<!-- Bootstrap Bundle JS (включает Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// WordPress-подобная функциональность sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    const main = document.querySelector('main.col-md-9.ms-sm-auto.col-lg-10');
    
    sidebar.classList.toggle('collapsed');
    
    // Обновляем отступ основного контента
    if (sidebar.classList.contains('collapsed')) {
        main.style.marginLeft = '36px';
        main.style.marginTop = '40px';
    } else {
        main.style.marginLeft = '160px';
        main.style.marginTop = '40px';
    }
}

function toggleSubmenu(element, event) {
    // Предотвращаем всплытие события и переход по ссылке
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const parentItem = element.closest('.has-submenu');
    const submenu = parentItem.querySelector('.submenu');
    const arrow = element.querySelector('.submenu-arrow');
    
    // Определяем контекст (десктоп или мобильный)
    const isMobile = element.closest('.mobile-sidebar') !== null;
    const context = isMobile ? '.mobile-sidebar' : '.sidebar';
    
    // Закрываем все другие субменю в том же контексте
    document.querySelectorAll(context + ' .has-submenu.open').forEach(item => {
        if (item !== parentItem) {
            item.classList.remove('open');
        }
    });
    
    // Переключаем текущее субменю
    parentItem.classList.toggle('open');
    
    // Предотвращаем переход по ссылке
    return false;
}

// Функция для мобильного меню
function toggleMobileSidebar() {
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    const overlay = document.querySelector('.mobile-sidebar-overlay');
    
    if (mobileSidebar && overlay) {
        mobileSidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        // Блокируем прокрутку body когда меню открыто
        if (mobileSidebar.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

// Закрытие мобильного меню при клике на ссылку
document.addEventListener('DOMContentLoaded', function() {
    // Закрываем меню только при клике на обычные ссылки
    const mobileLinks = document.querySelectorAll('.mobile-sidebar .nav-link:not(.submenu-toggle), .mobile-sidebar .submenu-link');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const mobileSidebar = document.querySelector('.mobile-sidebar');
            const overlay = document.querySelector('.mobile-sidebar-overlay');
            
            if (mobileSidebar && overlay) {
                mobileSidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Предотвращаем закрытие меню при клике на submenu-toggle
    const submenuToggles = document.querySelectorAll('.mobile-sidebar .submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Закрытие меню при изменении размера экрана
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            const mobileSidebar = document.querySelector('.mobile-sidebar');
            const overlay = document.querySelector('.mobile-sidebar-overlay');
            
            if (mobileSidebar && overlay) {
                mobileSidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    });
    
    // Автоматическое открытие активного субменю
    const activeSubmenu = document.querySelector('.sidebar .has-submenu .active');
    if (activeSubmenu) {
        const parentSubmenu = activeSubmenu.closest('.has-submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('open');
        }
    }
    
    // Автоматическое открытие активного субменю на мобильных
    const activeMobileSubmenu = document.querySelector('.mobile-sidebar .has-submenu .active');
    if (activeMobileSubmenu) {
        const parentMobileSubmenu = activeMobileSubmenu.closest('.has-submenu');
        if (parentMobileSubmenu) {
            parentMobileSubmenu.classList.add('open');
        }
    }
});

// Инициализация tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});

// Подтверждение удаления
document.querySelectorAll('[data-confirm-delete]').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const message = this.dataset.confirmDelete || 'Вы уверены, что хотите удалить этот элемент?';
        
        if (confirm(message)) {
            if (this.href) {
                window.location.href = this.href;
            } else if (this.form) {
                this.form.submit();
            }
        }
    });
});
</script>
