<?php
/**
 * Шаблон страницы управления меню
 * Версия с AJAX и единой страницей
*/
?>
<style>
.menus-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    height: calc(100vh - 200px);
}

.menus-sidebar {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    overflow-y: auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.menus-sidebar #menusList {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 12px;
}

.menus-main {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    overflow-y: auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
    .menus-container {
        grid-template-columns: 1fr;
        height: auto;
        gap: 12px;
    }
    
    .menus-sidebar {
        order: 2;
        padding: 12px;
    }
    
    .menus-main {
        order: 1;
        padding: 12px;
    }
    
    .menu-header-card {
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .menu-header-content {
        flex-direction: column;
        gap: 0;
        align-items: stretch;
    }
    
    .menu-header-content > .flex-grow-1 {
        order: 1;
        margin-bottom: 12px;
    }
    
    .menu-header-actions {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        order: 2;
        padding: 0;
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .menu-header-actions .btn {
        width: 100%;
        height: 44px;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    
    .menu-title {
        font-size: 1.1rem;
        margin-top: 0;
        margin-bottom: 4px;
    }
    
    .menu-description {
        font-size: 0.85rem;
        margin-bottom: 0;
    }
    
    .menu-items-section {
        padding: 10px;
        margin-top: 12px;
        border-radius: 6px;
    }
    
    .menu-item-card {
        padding: 10px;
        margin-bottom: 6px;
        border-radius: 4px;
    }
    
    .menu-item-header {
        flex-wrap: nowrap;
        gap: 6px;
        align-items: center;
    }
    
    .menu-item-drag-handle {
        font-size: 14px;
        flex-shrink: 0;
        padding: 2px;
    }
    
    .menu-item-content {
        flex-wrap: nowrap;
        min-width: 0;
        flex: 1;
        gap: 6px;
    }
    
    .menu-item-icon {
        font-size: 14px;
        width: 18px;
        flex-shrink: 0;
    }
    
    .menu-item-info {
        min-width: 0;
        flex: 1;
        overflow: hidden;
    }
    
    .menu-item-title {
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
        font-weight: 500;
    }
    
    .menu-item-url {
        font-size: 9px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .menu-item-badges {
        display: none;
    }
    
    .menu-item-actions {
        flex-shrink: 0;
        margin-left: 4px;
        gap: 4px;
        display: flex;
    }
    
    .menu-item-actions .btn {
        padding: 6px;
        min-width: 32px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .menus-sidebar {
        padding: 12px;
    }
    
    .menus-sidebar .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        padding: 14px;
        font-size: 0.95rem;
        border-radius: 6px;
    }
    
    .menu-list-item {
        padding: 12px;
        margin-bottom: 8px;
        border-radius: 6px;
    }
    
    .menu-list-item h6 {
        font-size: 0.95rem;
        margin-bottom: 4px;
    }
    
    .menu-list-item .small {
        font-size: 0.8rem;
    }
}

.menu-list-item {
    padding: 10px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-bottom: 6px;
    cursor: pointer;
    background: #fff;
    position: relative;
    overflow: visible;
}

.menu-list-item:hover {
    background: #f8f9fa;
    border-color: #007bff;
}

.menu-list-item.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
}

.menu-list-item.active .menu-location-badge {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

.menu-list-item.active h6 {
    color: #fff;
}

.menu-list-item.active .small {
    color: rgba(255,255,255,0.9);
}

.menu-location-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 500;
    background: #e9ecef;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.menu-header-card {
    background: transparent;
    padding: 0;
    margin-bottom: 0;
    border-radius: 0;
    box-shadow: none;
}

.menu-header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.menu-title {
    color: #212529;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.menu-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}

.menu-header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.menu-header-actions .btn {
    border: 1px solid #ced4da !important;
    width: 36px;
    height: 36px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.menu-header-actions .btn-primary {
    border-color: #0d6efd !important;
    background-color: #0d6efd !important;
}

.menu-header-actions .btn-primary i {
    color: #fff !important;
}

.menu-header-actions .btn-outline-primary {
    border-color: #198754 !important;
    background-color: transparent !important;
}

.menu-header-actions .btn-outline-primary i {
    color: #198754 !important;
}

.menu-header-actions .btn-outline-secondary {
    border-color: #6c757d !important;
    background-color: transparent !important;
}

.menu-header-actions .btn-outline-secondary i {
    color: #6c757d !important;
}

.menu-header-actions .btn-outline-danger {
    border-color: #dc3545 !important;
    background-color: transparent !important;
}

.menu-header-actions .btn-outline-danger i {
    color: #dc3545 !important;
}

.menu-items-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.section-title {
    color: #212529;
    font-size: 1.1rem;
    font-weight: 600;
}

.menu-item-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 8px;
    cursor: move;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.menu-item-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.menu-item-card.dragging {
    opacity: 0.5;
    background: #f8f9fa;
}

.menu-item-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.menu-item-drag-handle {
    color: #6c757d;
    cursor: grab;
    font-size: 18px;
}

.menu-item-drag-handle:active {
    cursor: grabbing;
}

.menu-item-content {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
}

.menu-item-icon {
    font-size: 18px;
    color: #6c757d;
    width: 24px;
    text-align: center;
}

.menu-item-info {
    flex: 1;
}

.menu-item-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 2px;
}

.menu-item-url {
    font-size: 12px;
    color: #6c757d;
}

.menu-item-actions {
    display: flex;
    gap: 6px;
}

.menu-item-actions .btn {
    padding: 4px 8px;
    font-size: 12px;
}

.menu-item-badges {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}

.menu-item-badges .badge {
    font-size: 10px;
    padding: 4px 8px;
    font-weight: 500;
}


.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.loading::after {
    content: '...';
    animation: dots 1.5s steps(4, end) infinite;
}

@keyframes dots {
    0%, 20% { content: '.'; }
    40% { content: '..'; }
    60%, 100% { content: '...'; }
}
</style>

<!-- Уведомления -->
<div id="alertContainer"></div>

<div class="menus-container">
    <!-- Левая панель: Список меню -->
    <div class="menus-sidebar">
        <div id="menusList">
            <?php if (empty($menus)): ?>
                <div class="empty-state">
                    <p class="mb-0">Меню не знайдено</p>
                </div>
            <?php else: ?>
                <?php foreach ($menus as $menuItem): ?>
                    <div class="menu-list-item" data-menu-id="<?= $menuItem['id'] ?>" onclick="loadMenu(<?= $menuItem['id'] ?>)">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($menuItem['name']) ?></h6>
                                <?php if (!empty($menuItem['description'])): ?>
                                    <p class="mb-0 small opacity-75"><?= htmlspecialchars($menuItem['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="menu-location-badge"><?= htmlspecialchars($menuItem['location']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Кнопка добавления меню внизу -->
        <div class="mt-3">
            <button type="button" class="btn btn-primary w-100" onclick="showCreateMenuModal()">
                <i class="fas fa-plus me-1"></i>Додати меню
            </button>
        </div>
    </div>
    
    <!-- Правая панель: Редактирование меню -->
    <div class="menus-main">
        <div id="menuEditor">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-bars"></i>
                </div>
                <p class="mb-2">Виберіть меню для редагування</p>
                <p class="small text-muted mb-3">або створіть нове меню</p>
                <button type="button" class="btn btn-primary" onclick="showCreateMenuModal()">
                    <i class="fas fa-plus me-1"></i>Створити меню
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания/редактирования меню -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="menuForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" id="menuAction" value="create_menu">
                <input type="hidden" name="menu_id" id="menuFormId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalTitle">Створити меню</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="menuName" class="form-label">Назва меню <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="menuName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="menuSlug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="menuSlug" name="slug" required>
                        <div class="form-text">Унікальний ідентифікатор меню</div>
                    </div>
                    <div class="mb-3">
                        <label for="menuDescription" class="form-label">Опис</label>
                        <textarea class="form-control" id="menuDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="menuLocation" class="form-label">Розташування</label>
                        <select class="form-select" id="menuLocation" name="location" required>
                            <?php if (!empty($menuLocations)): ?>
                                <?php foreach ($menuLocations as $locationKey => $locationInfo): ?>
                                    <option value="<?= htmlspecialchars($locationKey) ?>" 
                                            data-description="<?= htmlspecialchars($locationInfo['description'] ?? '') ?>">
                                        <?= htmlspecialchars($locationInfo['label'] ?? $locationKey) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="primary">Основне меню (primary)</option>
                                <option value="footer">Меню футера (footer)</option>
                                <option value="sidebar">Бічне меню (sidebar)</option>
                                <option value="custom">Произвольне меню (custom)</option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text" id="menuLocationDescription"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Зберегти</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно создания/редактирования пункта меню -->
<div class="modal fade" id="menuItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="menuItemForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" id="menuItemAction" value="create_menu_item">
                <input type="hidden" name="menu_id" id="menuItemMenuId" value="">
                <input type="hidden" name="item_id" id="menuItemId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuItemModalTitle">Додати пункт меню</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemTitle" class="form-label">Назва <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="itemTitle" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemUrl" class="form-label">URL <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="itemUrl" name="url" required placeholder="/ або #section">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemParent" class="form-label">Батьківський пункт</label>
                                <select class="form-select" id="itemParent" name="parent_id">
                                    <option value="">Немає (основний пункт)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemTarget" class="form-label">Відкривати в</label>
                                <select class="form-select" id="itemTarget" name="target">
                                    <option value="_self">Тому ж вікні</option>
                                    <option value="_blank">Новому вікні</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemIcon" class="form-label">Іконка (Font Awesome)</label>
                                <input type="text" class="form-control" id="itemIcon" name="icon" placeholder="fas fa-home">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="itemOrder" class="form-label">Порядок</label>
                                <input type="number" class="form-control" id="itemOrder" name="order_num" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="itemActiveContainer" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="itemActive" name="is_active" value="1" checked>
                            <label class="form-check-label" for="itemActive">Активний пункт меню</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Зберегти</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
const MenusApp = {
    currentMenuId: null,
    menuItems: [],
    sortable: null,
    
    init: function() {
        this.setupEventListeners();
        
        // Автогенерация slug из названия
        document.getElementById('menuName')?.addEventListener('input', function() {
            const slugInput = document.getElementById('menuSlug');
            if (slugInput && (!slugInput.value || slugInput.dataset.autoGenerated === 'true')) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9а-яё]+/g, '-')
                    .replace(/[а-яё]/g, function(match) {
                        const translit = {
                            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
                            'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
                            'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
                            'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch',
                            'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
                        };
                        return translit[match] || match;
                    })
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
                slugInput.dataset.autoGenerated = 'true';
            }
        });
        
        document.getElementById('menuSlug')?.addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });
    },
    
    setupEventListeners: function() {
        // Форма меню
        document.getElementById('menuForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveMenu();
        });
        
        // Форма пункта меню
        document.getElementById('menuItemForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveMenuItem();
        });
    },
    
    showAlert: function(message, type = 'success') {
        const container = document.getElementById('alertContainer');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.innerHTML = '';
        container.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    },
    
    loadMenu: async function(menuId) {
        this.currentMenuId = menuId;
        
        // Обновляем активный элемент в списке
        document.querySelectorAll('.menu-list-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.menuId == menuId) {
                item.classList.add('active');
            }
        });
        
        // Показываем загрузку
        document.getElementById('menuEditor').innerHTML = '<div class="loading">Завантаження</div>';
        
        try {
            const baseUrl = '<?= adminUrl('menus') ?>';
            
            // Загружаем меню и пункты
            const [menuResponse, itemsResponse] = await Promise.all([
                fetch(`${baseUrl}?action=get_menu&menu_id=${menuId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }),
                fetch(`${baseUrl}?action=get_menu_items&menu_id=${menuId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
            ]);
            
            // Проверяем статус ответов
            if (!menuResponse.ok) {
                throw new Error(`HTTP error! status: ${menuResponse.status}`);
            }
            if (!itemsResponse.ok) {
                throw new Error(`HTTP error! status: ${itemsResponse.status}`);
            }
            
            const menuData = await menuResponse.json();
            const itemsData = await itemsResponse.json();
            
            // Проверяем наличие ошибок в ответах
            if (!menuData || menuData.success === false) {
                const errorMsg = menuData?.error || 'Помилка завантаження даних меню';
                console.error('Menu load error:', errorMsg, menuData);
                this.showAlert(errorMsg, 'danger');
                return;
            }
            
            if (!itemsData || itemsData.success === false) {
                const errorMsg = itemsData?.error || 'Помилка завантаження пунктів меню';
                console.error('Menu items load error:', errorMsg, itemsData);
                this.showAlert(errorMsg, 'danger');
                return;
            }
            
            if (menuData.success && itemsData.success) {
                this.menuItems = itemsData.items || [];
                this.renderMenuEditor(menuData.menu, itemsData.tree || []);
                this.initSortable();
            } else {
                this.showAlert('Помилка завантаження меню', 'danger');
            }
        } catch (error) {
            console.error('Error loading menu:', error);
            this.showAlert('Помилка завантаження меню: ' + error.message, 'danger');
        }
    },
    
    renderMenuEditor: function(menu, itemsTree) {
        const editor = document.getElementById('menuEditor');
        const hasItems = itemsTree && itemsTree.length > 0;
        
        editor.innerHTML = `
            <div class="menu-header-card">
                <div class="menu-header-content">
                    <div class="flex-grow-1">
                        <h3 class="menu-title">${this.escapeHtml(menu.name)}</h3>
                        ${menu.description ? `<p class="menu-description">${this.escapeHtml(menu.description)}</p>` : ''}
                    </div>
                    <div class="menu-header-actions">
                        <button type="button" class="btn btn-sm btn-light" onclick="MenusApp.showCreateItemModal()" title="Додати пункт">
                            <i class="fas fa-plus"></i>
                        </button>
                        ${hasItems ? `
                            <button type="button" class="btn btn-sm btn-light" onclick="MenusApp.saveMenuOrder()" title="Зберегти порядок">
                                <i class="fas fa-save"></i>
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-sm btn-light" onclick="MenusApp.showEditMenuModal(${menu.id})" title="Налаштування">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light" onclick="MenusApp.deleteMenu(${menu.id})" title="Видалити">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="menu-items-section">
                ${hasItems ? `
                    <div id="menuItemsList" class="menu-items-container">
                        ${this.renderMenuItems(itemsTree)}
                    </div>
                ` : `
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <p class="mb-3">Пункти меню відсутні</p>
                        <button type="button" class="btn btn-primary" onclick="MenusApp.showCreateItemModal()">
                            <i class="fas fa-plus me-1"></i>Додати перший пункт
                        </button>
                    </div>
                `}
            </div>
            ${hasItems ? `
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Перетягніть пункти меню для зміни порядку
                    </small>
                </div>
            ` : ''}
        `;
    },
    
    renderMenuItems: function(itemsTree, level = 0) {
        let html = '';
        itemsTree.forEach(item => {
            const indent = level * 24;
            const hasChildren = item.children && item.children.length > 0;
            const isInactive = item.is_active == 0;
            html += `
                <div class="menu-item-card ${isInactive ? 'opacity-75' : ''}" data-item-id="${item.id}" data-level="${level}" style="margin-left: ${indent}px; ${level > 0 ? 'border-left: 3px solid #007bff;' : ''}">
                    <div class="menu-item-header">
                        <div class="menu-item-drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="menu-item-content">
                            <div class="menu-item-icon">
                                <i class="${item.icon || 'fas fa-link'}"></i>
                            </div>
                            <div class="menu-item-info">
                                <div class="menu-item-title">${this.escapeHtml(item.title)}</div>
                                <div class="menu-item-url">${this.escapeHtml(item.url)}</div>
                            </div>
                            <div class="menu-item-badges">
                                ${isInactive ? '<span class="badge bg-secondary">Неактивний</span>' : ''}
                                ${item.target == '_blank' ? '<span class="badge bg-info">Нове вікно</span>' : ''}
                                ${hasChildren ? `<span class="badge bg-primary">${item.children.length} підпунктів</span>` : ''}
                            </div>
                        </div>
                        <div class="menu-item-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="MenusApp.showEditItemModal(${item.id})" title="Редагувати">
                                <i class="fas fa-edit"></i><span class="d-none d-lg-inline ms-1">Редагувати</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="MenusApp.deleteMenuItem(${item.id})" title="Видалити">
                                <i class="fas fa-trash"></i><span class="d-none d-lg-inline ms-1">Видалити</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            if (hasChildren) {
                html += this.renderMenuItems(item.children, level + 1);
            }
        });
        return html;
    },
    
    initSortable: function() {
        const list = document.getElementById('menuItemsList');
        if (!list) return;
        
        if (this.sortable) {
            this.sortable.destroy();
        }
        
        this.sortable = new Sortable(list, {
            handle: '.menu-item-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'dragging'
        });
    },
    
    saveMenuOrder: async function() {
        const items = Array.from(document.querySelectorAll('.menu-item-card'));
        const order = [];
        
        items.forEach((item, index) => {
            const itemId = parseInt(item.dataset.itemId);
            const itemData = this.menuItems.find(i => i.id == itemId);
            order.push({
                id: itemId,
                order_num: index + 1,
                parent_id: itemData ? (itemData.parent_id || null) : null
            });
        });
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_menu_order');
            formData.append('menu_id', this.currentMenuId);
            formData.append('items', JSON.stringify(order));
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            
            const response = await fetch('<?= adminUrl('menus') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Порядок пунктів меню успішно оновлено', 'success');
                this.menuItems = data.items;
                this.renderMenuEditor(
                    await this.getMenu(this.currentMenuId),
                    data.tree
                );
                this.initSortable();
            } else {
                this.showAlert(data.error || 'Помилка оновлення порядку', 'danger');
            }
        } catch (error) {
            console.error('Error saving order:', error);
            this.showAlert('Помилка оновлення порядку', 'danger');
        }
    },
    
    getMenu: async function(menuId) {
        const response = await fetch(`<?= adminUrl('menus') ?>?action=get_menu&menu_id=${menuId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        return data.menu;
    },
    
    showCreateMenuModal: function() {
        document.getElementById('menuModalTitle').textContent = 'Створити меню';
        document.getElementById('menuAction').value = 'create_menu';
        document.getElementById('menuFormId').value = '';
        document.getElementById('menuForm').reset();
        document.getElementById('menuSlug').dataset.autoGenerated = 'true';
        new bootstrap.Modal(document.getElementById('menuModal')).show();
    },
    
    showEditMenuModal: async function(menuId) {
        const menu = await this.getMenu(menuId);
        if (!menu) return;
        
        document.getElementById('menuModalTitle').textContent = 'Редагувати меню';
        document.getElementById('menuAction').value = 'update_menu';
        document.getElementById('menuFormId').value = menu.id;
        document.getElementById('menuName').value = menu.name;
        document.getElementById('menuSlug').value = menu.slug;
        document.getElementById('menuDescription').value = menu.description || '';
        document.getElementById('menuLocation').value = menu.location;
        new bootstrap.Modal(document.getElementById('menuModal')).show();
    },
    
    saveMenu: async function() {
        const form = document.getElementById('menuForm');
        const formData = new FormData(form);
        const action = formData.get('action');
        
        try {
            const response = await fetch('<?= adminUrl('menus') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert(action === 'create_menu' ? 'Меню успішно створено' : 'Меню успішно оновлено', 'success');
                bootstrap.Modal.getInstance(document.getElementById('menuModal')).hide();
                
                // Обновляем список меню
                location.reload();
            } else {
                this.showAlert(data.error || 'Помилка збереження меню', 'danger');
            }
        } catch (error) {
            console.error('Error saving menu:', error);
            this.showAlert('Помилка збереження меню', 'danger');
        }
    },
    
    deleteMenu: async function(menuId) {
        if (!confirm('Ви впевнені, що хочете видалити це меню? Всі пункти меню також будуть видалені.')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete_menu');
            formData.append('menu_id', menuId);
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            
            const response = await fetch('<?= adminUrl('menus') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Меню успішно видалено', 'success');
                location.reload();
            } else {
                this.showAlert(data.error || 'Помилка видалення меню', 'danger');
            }
        } catch (error) {
            console.error('Error deleting menu:', error);
            this.showAlert('Помилка видалення меню', 'danger');
        }
    },
    
    showCreateItemModal: function() {
        if (!this.currentMenuId) {
            this.showAlert('Спочатку виберіть меню', 'warning');
            return;
        }
        
        document.getElementById('menuItemModalTitle').textContent = 'Додати пункт меню';
        document.getElementById('menuItemAction').value = 'create_menu_item';
        document.getElementById('menuItemId').value = '';
        document.getElementById('menuItemMenuId').value = this.currentMenuId;
        document.getElementById('menuItemForm').reset();
        document.getElementById('itemActiveContainer').style.display = 'none';
        
        // Заполняем список родительских пунктов
        this.updateParentOptions();
        
        new bootstrap.Modal(document.getElementById('menuItemModal')).show();
    },
    
    showEditItemModal: async function(itemId) {
        const item = this.menuItems.find(i => i.id == itemId);
        if (!item) return;
        
        document.getElementById('menuItemModalTitle').textContent = 'Редагувати пункт меню';
        document.getElementById('menuItemAction').value = 'update_menu_item';
        document.getElementById('menuItemId').value = item.id;
        document.getElementById('menuItemMenuId').value = this.currentMenuId;
        document.getElementById('itemTitle').value = item.title;
        document.getElementById('itemUrl').value = item.url;
        document.getElementById('itemTarget').value = item.target || '_self';
        document.getElementById('itemIcon').value = item.icon || '';
        document.getElementById('itemOrder').value = item.order_num || 0;
        document.getElementById('itemActive').checked = item.is_active == 1;
        document.getElementById('itemActiveContainer').style.display = 'block';
        
        // Заполняем список родительских пунктов
        this.updateParentOptions(itemId);
        document.getElementById('itemParent').value = item.parent_id || '';
        
        new bootstrap.Modal(document.getElementById('menuItemModal')).show();
    },
    
    updateParentOptions: function(excludeId = null) {
        const select = document.getElementById('itemParent');
        select.innerHTML = '<option value="">Немає (основний пункт)</option>';
        
        // Показываем только корневые элементы
        this.menuItems.forEach(item => {
            if (item.id != excludeId && (!item.parent_id || item.parent_id === '')) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.title;
                select.appendChild(option);
            }
        });
    },
    
    saveMenuItem: async function() {
        const form = document.getElementById('menuItemForm');
        const formData = new FormData(form);
        const action = formData.get('action');
        
        try {
            const response = await fetch('<?= adminUrl('menus') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert(
                    action === 'create_menu_item' ? 'Пункт меню успішно додано' : 'Пункт меню успішно оновлено',
                    'success'
                );
                bootstrap.Modal.getInstance(document.getElementById('menuItemModal')).hide();
                
                // Обновляем редактор меню
                this.menuItems = data.items;
                const menu = await this.getMenu(this.currentMenuId);
                this.renderMenuEditor(menu, data.tree);
                this.initSortable();
            } else {
                this.showAlert(data.error || 'Помилка збереження пункта меню', 'danger');
            }
        } catch (error) {
            console.error('Error saving menu item:', error);
            this.showAlert('Помилка збереження пункта меню', 'danger');
        }
    },
    
    deleteMenuItem: async function(itemId) {
        if (!confirm('Ви впевнені, що хочете видалити цей пункт меню?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete_menu_item');
            formData.append('item_id', itemId);
            formData.append('menu_id', this.currentMenuId);
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            
            const response = await fetch('<?= adminUrl('menus') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Пункт меню успішно видалено', 'success');
                this.menuItems = data.items;
                const menu = await this.getMenu(this.currentMenuId);
                this.renderMenuEditor(menu, data.tree);
                this.initSortable();
            } else {
                this.showAlert(data.error || 'Помилка видалення пункта меню', 'danger');
            }
        } catch (error) {
            console.error('Error deleting menu item:', error);
            this.showAlert('Помилка видалення пункта меню', 'danger');
        }
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Глобальные функции для вызова из onclick
function loadMenu(menuId) {
    MenusApp.loadMenu(menuId);
}

function showCreateMenuModal() {
    MenusApp.showCreateMenuModal();
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    MenusApp.init();
    
    // Обновление описания расположения при выборе
    const locationSelect = document.getElementById('menuLocation');
    const locationDescription = document.getElementById('menuLocationDescription');
    
    if (locationSelect && locationDescription) {
        function updateLocationDescription() {
            const selectedOption = locationSelect.options[locationSelect.selectedIndex];
            const description = selectedOption ? (selectedOption.getAttribute('data-description') || '') : '';
            if (description) {
                locationDescription.textContent = description;
                locationDescription.style.display = 'block';
            } else {
                locationDescription.style.display = 'none';
            }
        }
        
        locationSelect.addEventListener('change', updateLocationDescription);
        updateLocationDescription(); // Инициализация при загрузке
    }
    
    // Автоматически загружаем первое меню, если есть
    const firstMenu = document.querySelector('.menu-list-item[data-menu-id]');
    if (firstMenu) {
        const menuId = firstMenu.dataset.menuId;
        MenusApp.loadMenu(menuId);
    }
});
</script>
