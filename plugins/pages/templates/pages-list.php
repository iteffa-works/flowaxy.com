<?php
/**
 * Шаблон сторінки списку сторінок
 */
?>

<!-- Повідомлення -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
    </div>
<?php endif; ?>

<!-- Фільтри та пошук -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Пошук</label>
                <input type="text" name="search" class="form-control" placeholder="Назва або slug..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Всі статуси</option>
                    <option value="publish" <?= ($filters['status'] ?? '') === 'publish' ? 'selected' : '' ?>>Опубліковано</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Чернетка</option>
                    <option value="trash" <?= ($filters['status'] ?? '') === 'trash' ? 'selected' : '' ?>>Кошик</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Категорія</label>
                <select name="category_id" class="form-select">
                    <option value="">Всі категорії</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= ($filters['category_id'] ?? null) == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Пошук
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця сторінок -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="" id="pagesForm">
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" title="Вибрати все">
                            </th>
                            <th>Назва</th>
                            <th>Slug</th>
                            <th>Категорія</th>
                            <th>Статус</th>
                            <th>Автор</th>
                            <th>Дата створення</th>
                            <th width="120">Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Сторінки не знайдено
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="page_ids[]" value="<?= $page['id'] ?>" class="page-checkbox">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($page['title']) ?></strong>
                                        <?php if (!empty($page['excerpt'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(mb_substr($page['excerpt'], 0, 60)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($page['slug']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($page['category_name'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($page['category_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'publish' => 'success',
                                            'draft' => 'warning',
                                            'trash' => 'danger'
                                        ];
                                        $statusText = [
                                            'publish' => 'Опубліковано',
                                            'draft' => 'Чернетка',
                                            'trash' => 'Кошик'
                                        ];
                                        $status = $page['status'] ?? 'draft';
                                        ?>
                                        <span class="badge bg-<?= $statusClass[$status] ?? 'secondary' ?>">
                                            <?= $statusText[$status] ?? $status ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($page['author_name'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <small><?= date('d.m.Y H:i', strtotime($page['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= UrlHelper::admin('pages-edit?id=' . $page['id']) ?>" class="btn btn-outline-primary" title="Редагувати">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-page-btn" data-page-id="<?= $page['id'] ?>" title="Видалити">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Масові дії -->
            <div class="mt-3" id="bulkActions" style="display: none;">
                <button type="submit" name="action" value="bulk_delete" class="btn btn-danger" onclick="return confirm('Ви впевнені, що хочете видалити вибрані сторінки?')">
                    <i class="fas fa-trash me-1"></i>Видалити вибрані
                </button>
            </div>
        </form>
        
        <!-- Пагінація -->
        <?php if ($pages > 1): ?>
            <nav aria-label="Навігація по сторінках" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $currentPage = $page;
                    $totalPages = $pages;
                    $queryParams = $_GET;
                    
                    // Попередня сторінка
                    if ($currentPage > 1):
                        $queryParams['page'] = $currentPage - 1;
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    // Показуємо до 5 сторінок навколо поточної
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                        $queryParams['page'] = $i;
                    ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php
                    // Наступна сторінка
                    if ($currentPage < $totalPages):
                        $queryParams['page'] = $currentPage + 1;
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Вибрати все
    const selectAll = document.getElementById('selectAll');
    const pageCheckboxes = document.querySelectorAll('.page-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            pageCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }
    
    // Оновлення видимості масових дій
    function updateBulkActions() {
        const checked = document.querySelectorAll('.page-checkbox:checked').length;
        bulkActions.style.display = checked > 0 ? 'block' : 'none';
    }
    
    pageCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
    
    // Видалення сторінки
    document.querySelectorAll('.delete-page-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Ви впевнені, що хочете видалити цю сторінку?')) {
                const form = document.getElementById('pagesForm');
                const pageId = this.getAttribute('data-page-id');
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'delete';
                form.appendChild(input);
                
                const pageIdInput = document.createElement('input');
                pageIdInput.type = 'hidden';
                pageIdInput.name = 'page_id';
                pageIdInput.value = pageId;
                form.appendChild(pageIdInput);
                
                form.submit();
            }
        });
    });
});
</script>

