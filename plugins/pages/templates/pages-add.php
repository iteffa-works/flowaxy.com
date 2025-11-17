<?php
/**
 * Шаблон сторінки додавання сторінки
 */
?>

<!-- Повідомлення -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Назва -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Назва сторінки <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?= htmlspecialchars($page['title'] ?? '') ?>" 
                               placeholder="Введіть назву сторінки">
                    </div>
                    
                    <!-- Slug -->
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug (URL)</label>
                        <input type="text" class="form-control" id="slug" name="slug" 
                               value="<?= htmlspecialchars($page['slug'] ?? '') ?>" 
                               placeholder="Автоматично генерується з назви">
                        <small class="form-text text-muted">Використовується в URL сторінки</small>
                    </div>
                    
                    <!-- Контент -->
                    <div class="mb-3">
                        <label for="content" class="form-label">Контент</label>
                        <textarea class="form-control" id="content" name="content" rows="15" 
                                  placeholder="Введіть контент сторінки"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Короткий опис -->
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Короткий опис</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="3" 
                                  placeholder="Короткий опис сторінки (опціонально)"><?= htmlspecialchars($page['excerpt'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-header">
                            <strong>Параметри публікації</strong>
                        </div>
                        <div class="card-body">
                            <!-- Статус -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Статус</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Чернетка</option>
                                    <option value="publish" <?= ($page['status'] ?? 'draft') === 'publish' ? 'selected' : '' ?>>Опубліковано</option>
                                    <option value="trash" <?= ($page['status'] ?? 'draft') === 'trash' ? 'selected' : '' ?>>Кошик</option>
                                </select>
                            </div>
                            
                            <!-- Категорія -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Категорія</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Без категорії</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($page['category_id'] ?? null) == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Кнопки -->
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-1"></i>Зберегти сторінку
                        </button>
                        <a href="<?= UrlHelper::admin('pages') ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-1"></i>Скасувати
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    // Автоматична генерація slug з назви
    if (titleInput && slugInput) {
        let slugManuallyEdited = false;
        
        titleInput.addEventListener('input', function() {
            if (!slugManuallyEdited && !slugInput.value) {
                slugInput.value = generateSlug(this.value);
            }
        });
        
        slugInput.addEventListener('input', function() {
            slugManuallyEdited = this.value.length > 0;
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

