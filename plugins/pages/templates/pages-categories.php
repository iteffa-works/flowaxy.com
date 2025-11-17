<?php
/**
 * Шаблон сторінки керування категоріями
 */
?>

<!-- Повідомлення -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Список категорій</h5>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                        Категорії не знайдено
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Slug</th>
                                    <th>Сторінок</th>
                                    <th width="150">Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                            <?php if (!empty($category['description'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($category['description'], 0, 60)) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($category['slug']) ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= (int)($category['pages_count'] ?? 0) ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary edit-category-btn" 
                                                        data-category-id="<?= $category['id'] ?>" 
                                                        title="Редагувати">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="" class="d-inline" 
                                                      onsubmit="return confirm('Ви впевнені, що хочете видалити цю категорію?')">
                                                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Видалити">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Додати категорію</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="categoryForm">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="create" id="categoryAction">
                    <input type="hidden" name="category_id" value="" id="categoryId">
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Назва <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="category_slug" name="slug" 
                               placeholder="Автоматично генерується">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Опис</label>
                        <textarea class="form-control" id="category_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_parent" class="form-label">Батьківська категорія</label>
                        <select class="form-select" id="category_parent" name="parent_id">
                            <option value="">Без батьківської категорії</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="categorySubmitBtn">
                        <i class="fas fa-plus me-1"></i>Додати категорію
                    </button>
                    <button type="button" class="btn btn-secondary w-100 mt-2" id="cancelEditBtn" style="display: none;">
                        <i class="fas fa-times me-1"></i>Скасувати редагування
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно для редагування (альтернативний варіант) -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Додати категорію</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="modalCategoryForm">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="modal_category_name" class="form-label">Назва <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal_category_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_category_slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="modal_category_slug" name="slug">
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_category_description" class="form-label">Опис</label>
                        <textarea class="form-control" id="modal_category_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_category_parent" class="form-label">Батьківська категорія</label>
                        <select class="form-select" id="modal_category_parent" name="parent_id">
                            <option value="">Без батьківської категорії</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="submit" form="modalCategoryForm" class="btn btn-primary">Додати</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryForm = document.getElementById('categoryForm');
    const categoryName = document.getElementById('category_name');
    const categorySlug = document.getElementById('category_slug');
    const categoryAction = document.getElementById('categoryAction');
    const categoryId = document.getElementById('categoryId');
    const categorySubmitBtn = document.getElementById('categorySubmitBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    // Автоматична генерація slug
    if (categoryName && categorySlug) {
        let slugManuallyEdited = false;
        
        categoryName.addEventListener('input', function() {
            if (!slugManuallyEdited && !categorySlug.value) {
                categorySlug.value = generateSlug(this.value);
            }
        });
        
        categorySlug.addEventListener('input', function() {
            slugManuallyEdited = this.value.length > 0;
        });
    }
    
    // Редагування категорії
    document.querySelectorAll('.edit-category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryIdValue = this.getAttribute('data-category-id');
            
            // Завантажуємо дані категорії через AJAX
            fetch('?action=get_category&id=' + categoryIdValue)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.category) {
                        const cat = data.category;
                        categoryName.value = cat.name || '';
                        categorySlug.value = cat.slug || '';
                        document.getElementById('category_description').value = cat.description || '';
                        document.getElementById('category_parent').value = cat.parent_id || '';
                        
                        categoryAction.value = 'update';
                        categoryId.value = cat.id;
                        categorySubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Зберегти зміни';
                        cancelEditBtn.style.display = 'block';
                        
                        // Прокручуємо до форми
                        categoryForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(error => {
                    console.error('Error loading category:', error);
                    alert('Помилка завантаження категорії');
                });
        });
    });
    
    // Скасувати редагування
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            categoryForm.reset();
            categoryAction.value = 'create';
            categoryId.value = '';
            categorySubmitBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Додати категорію';
            this.style.display = 'none';
        });
    }
    
    function generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});
</script>

