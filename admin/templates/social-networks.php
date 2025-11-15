<?php
/**
 * Шаблон страницы управления социальными сетями
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="content-section">
            <div class="content-section-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-share-alt me-2"></i>Соціальні мережі</span>
                <button type="button" class="btn btn-sm btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addNetworkModal">
                    <i class="fas fa-plus me-1"></i>Додати соціальну мережу
                </button>
            </div>
            <div class="content-section-body">
                <?php if (empty($networks)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-share-alt fa-3x mb-3" style="opacity: 0.3;"></i>
                        <p>Немає соціальних мереж</p>
                        <p class="small">Додайте соціальну мережу, щоб вона відображалася у футері</p>
                    </div>
                <?php else: ?>
                    <div id="networksList" class="list-group">
                        <?php foreach ($networks as $network): ?>
                            <div class="list-group-item" data-id="<?= $network['id'] ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="<?= htmlspecialchars($network['icon']) ?> fa-lg me-3" style="width: 30px;"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($network['type']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($network['url']) ?></small>
                                            <?php if ($network['open_new_window']): ?>
                                                <span class="badge bg-info ms-2">Відкривати в новому вікні</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editNetwork(<?= $network['id'] ?>, '<?= htmlspecialchars($network['type'], ENT_QUOTES) ?>', '<?= htmlspecialchars($network['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($network['icon'], ENT_QUOTES) ?>', <?= $network['open_new_window'] ? 'true' : 'false' ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNetwork(<?= $network['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="content-section">
            <div class="content-section-header">
                <span><i class="fas fa-info-circle me-2"></i>Інформація</span>
            </div>
            <div class="content-section-body">
                <p class="small text-muted">
                    Соціальні мережі, додані тут, будуть відображатися у футері сайту.
                    Ви можете додати будь-яку соціальну мережу, вказавши тип, URL та іконку.
                </p>
                <h6 class="mt-3 mb-2">Популярні іконки:</h6>
                <ul class="list-unstyled small">
                    <li><code>fab fa-facebook-f</code> - Facebook</li>
                    <li><code>fab fa-instagram</code> - Instagram</li>
                    <li><code>fab fa-telegram-plane</code> - Telegram</li>
                    <li><code>fab fa-twitter</code> - Twitter</li>
                    <li><code>fab fa-youtube</code> - YouTube</li>
                    <li><code>fab fa-viber</code> - Viber</li>
                    <li><code>fab fa-whatsapp</code> - WhatsApp</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления/редактирования -->
<div class="modal fade" id="addNetworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="networkModalTitle">Додати соціальну мережу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="networkForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" id="networkAction" value="add_network">
                <input type="hidden" name="id" id="networkId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="networkType" class="form-label">Тип соціальної мережі</label>
                        <input type="text" class="form-control" id="networkType" name="type" required 
                               placeholder="Наприклад: Facebook, Instagram, Telegram">
                    </div>
                    <div class="mb-3">
                        <label for="networkUrl" class="form-label">URL</label>
                        <input type="url" class="form-control" id="networkUrl" name="url" required 
                               placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label for="networkIcon" class="form-label">Іконка (Font Awesome класс)</label>
                        <input type="text" class="form-control" id="networkIcon" name="icon" 
                               value="fab fa-link" required placeholder="fab fa-facebook-f">
                        <div class="form-text">Використовуйте класи Font Awesome, наприклад: fab fa-facebook-f</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="networkOpenNewWindow" name="open_new_window" value="1" checked>
                            <label class="form-check-label" for="networkOpenNewWindow">
                                Відкривати в новому вікні
                            </label>
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

<script>
function editNetwork(id, type, url, icon, openNewWindow) {
    document.getElementById('networkModalTitle').textContent = 'Редагувати соціальну мережу';
    document.getElementById('networkAction').value = 'update_network';
    document.getElementById('networkId').value = id;
    document.getElementById('networkType').value = type;
    document.getElementById('networkUrl').value = url;
    document.getElementById('networkIcon').value = icon;
    document.getElementById('networkOpenNewWindow').checked = openNewWindow;
    
    const modal = new bootstrap.Modal(document.getElementById('addNetworkModal'));
    modal.show();
}

function deleteNetwork(id) {
    if (!confirm('Ви впевнені, що хочете видалити цю соціальну мережу?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" value="delete_network">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Сброс формы при закрытии модального окна
document.getElementById('addNetworkModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('networkModalTitle').textContent = 'Додати соціальну мережу';
    document.getElementById('networkAction').value = 'add_network';
    document.getElementById('networkId').value = '';
    document.getElementById('networkForm').reset();
    document.getElementById('networkIcon').value = 'fab fa-link';
    document.getElementById('networkOpenNewWindow').checked = true;
});
</script>

