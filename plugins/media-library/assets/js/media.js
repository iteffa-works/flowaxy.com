/**
 * JavaScript для керування медіафайлами (без AJAX)
 * 
 * @version 2.0.0
 */

(function() {
    'use strict';
    
    // Глобальний стан
    const state = {
        filesToUpload: [],
        viewToggleInitialized: false,
        deleteButtonsInitialized: false,
        viewModalInitialized: false,
        editModalInitialized: false
    };
    
    /**
     * Ініціалізація при завантаженні DOM
     */
    document.addEventListener('DOMContentLoaded', function() {
        initUploadModal();
        initViewModal();
        initEditModal();
        initDeleteButtons();
        initViewToggle();
        initDragAndDrop();
        initToolbarButtons();
        initMediaCheckboxes();
    });
    
    /**
     * Ініціалізація модального вікна завантаження
     */
    function initUploadModal() {
        const uploadModal = document.getElementById('uploadModal');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        
        if (!uploadModal || !fileInput || !uploadBtn || !uploadForm) return;
        
        // Обробка вибору файлів
        fileInput.addEventListener('change', function(e) {
            handleFileSelect(e.target.files);
        });
        
        // Обробка завантаження через звичайну форму
        uploadForm.addEventListener('submit', function(e) {
            // Перевіряємо, чи є файли
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Виберіть файли для завантаження');
                return false;
            }
            
            // Дозволяємо формі відправитися нормально
            return true;
        });
        
        // Кнопка завантаження
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Перевіряємо, чи є файли
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Виберіть файли для завантаження');
                return;
            }
            
            // Відправляємо форму
            uploadForm.submit();
        });
        
        // Очищення при закритті модального вікна
        uploadModal.addEventListener('hidden.bs.modal', resetUploadForm);
    }
    
    /**
     * Скидання форми завантаження
     */
    function resetUploadForm() {
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const uploadFields = document.querySelector('.upload-fields');
        
        if (fileInput) fileInput.value = '';
        if (filePreview) filePreview.innerHTML = '';
        if (uploadFields) uploadFields.style.display = 'none';
        
        state.filesToUpload = [];
        
        const titleInput = document.getElementById('fileTitle');
        const descInput = document.getElementById('fileDescription');
        const altInput = document.getElementById('fileAlt');
        
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        if (altInput) altInput.value = '';
    }
    
    /**
     * Обробка вибору файлів
     */
    function handleFileSelect(files) {
        if (!files || files.length === 0) return;
        
        state.filesToUpload = Array.from(files);
        const filePreview = document.getElementById('filePreview');
        const uploadFields = document.querySelector('.upload-fields');
        
        if (!filePreview) return;
        
        filePreview.innerHTML = '';
        
        state.filesToUpload.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'mb-2 p-2 border rounded';
            fileItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file me-2"></i>${file.name}</span>
                    <small class="text-muted">${formatFileSize(file.size)}</small>
                </div>
            `;
            filePreview.appendChild(fileItem);
        });
        
        if (uploadFields) {
            uploadFields.style.display = 'block';
        }
    }
    
    /**
     * Форматування розміру файлу
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    /**
     * Ініціалізація модального вікна перегляду
     */
    function initViewModal() {
        if (state.viewModalInitialized) return;
        state.viewModalInitialized = true;
        
        // Використовуємо делегування подій
        document.addEventListener('click', function(e) {
            // Перевіряємо клік на кнопку або іконку всередині кнопки
            const viewBtn = e.target.closest('.view-media');
            if (!viewBtn) {
                // Якщо клік на іконку всередині кнопки
                const icon = e.target.closest('i');
                if (icon) {
                    const parentBtn = icon.closest('.view-media');
                    if (parentBtn) {
                        handleViewClick(parentBtn, e);
                    }
                }
                return;
            }
            
            handleViewClick(viewBtn, e);
        });
    }
    
    /**
     * Обробка кліку на кнопку перегляду
     */
    function handleViewClick(viewBtn, e) {
        e.preventDefault();
        e.stopPropagation();
        
        const mediaId = viewBtn.dataset.id;
        if (!mediaId) {
            console.error('Media ID not found');
            return;
        }
        
        const viewModal = document.getElementById('viewModal');
        if (!viewModal) {
            console.error('View modal not found');
            return;
        }
        
        const viewModalBody = document.getElementById('viewModalBody');
        if (viewModalBody) {
            viewModalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Завантаження...</span></div></div>';
        }
        
        // Завантажуємо дані через GET запит
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_file');
        url.searchParams.set('media_id', mediaId);
        
        // Показуємо модальне вікно
        let modal;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            modal = new bootstrap.Modal(viewModal);
        } else {
            // Fallback для старих версій Bootstrap
            modal = $(viewModal).modal ? $(viewModal) : null;
            if (modal) {
                modal.modal('show');
            } else {
                viewModal.style.display = 'block';
                viewModal.classList.add('show');
            }
        }
        
        if (modal && modal.show) {
            modal.show();
        }
        
        // Завантажуємо дані
        fetch(url.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.file) {
                    renderFileView(data.file, viewModalBody);
                } else {
                    if (viewModalBody) {
                        viewModalBody.innerHTML = '<div class="alert alert-danger">Помилка завантаження файлу: ' + (data.error || 'Невідома помилка') + '</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading file:', error);
                if (viewModalBody) {
                    viewModalBody.innerHTML = '<div class="alert alert-danger">Помилка завантаження файлу. Перевірте консоль для деталей.</div>';
                }
            });
    }
    
    /**
     * Рендеринг перегляду файлу
     */
    function renderFileView(file, container) {
        if (!container || !file) return;
        
        // Переконуємося, що file_url правильно сформований
        let fileUrl = file.file_url || '';
        if (fileUrl && !fileUrl.startsWith('http://') && !fileUrl.startsWith('https://') && !fileUrl.startsWith('//')) {
            if (fileUrl.startsWith('/')) {
                fileUrl = '//' + window.location.host + fileUrl;
            } else {
                fileUrl = '//' + window.location.host + '/' + fileUrl;
            }
        }
        
        let html = '<div class="media-view-container">';
        
        if (file.media_type === 'image') {
            html += `
                <div class="media-view-preview">
                    <div class="media-preview-wrapper">
                        <img src="${escapeHtml(fileUrl)}" alt="${escapeHtml(file.alt_text || file.title || file.original_name || '')}" class="media-preview-content">
                    </div>
                </div>
            `;
        } else {
            // Визначаємо іконку за типом файлу
            let iconClass = 'fa-file';
            if (file.media_type === 'video') {
                iconClass = 'fa-video';
            } else if (file.media_type === 'audio') {
                iconClass = 'fa-music';
            } else if (file.media_type === 'document') {
                iconClass = 'fa-file-alt';
            }
            
            html += `
                <div class="media-view-preview">
                    <div class="document-wrapper">
                        <div class="document-preview-container">
                            <div class="media-file-icon">
                                <i class="fas ${iconClass} fa-5x"></i>
                            </div>
                            <div class="text-center">
                                <h5>${escapeHtml(file.title || file.original_name || 'Без назви')}</h5>
                                <p class="text-muted">${escapeHtml(file.media_type || 'unknown')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Форматуємо дату
        let uploadedDate = 'Невідомо';
        if (file.uploaded_at) {
            try {
                uploadedDate = new Date(file.uploaded_at).toLocaleString('uk-UA');
            } catch (e) {
                uploadedDate = file.uploaded_at;
            }
        }
        
        html += `
            <div class="media-view-info">
                <h6>Інформація про файл</h6>
                <table class="table table-sm">
                    <tr><td><strong>Назва:</strong></td><td>${escapeHtml(file.title || file.original_name || 'Без назви')}</td></tr>
                    <tr><td><strong>Оригінальна назва:</strong></td><td>${escapeHtml(file.original_name || 'Невідомо')}</td></tr>
                    <tr><td><strong>Розмір:</strong></td><td>${formatFileSize(file.file_size || 0)}</td></tr>
                    <tr><td><strong>Тип:</strong></td><td>${escapeHtml(file.media_type || 'unknown')}</td></tr>
                    ${file.width && file.height ? `<tr><td><strong>Розміри:</strong></td><td>${file.width} × ${file.height} px</td></tr>` : ''}
                    <tr><td><strong>Завантажено:</strong></td><td>${escapeHtml(uploadedDate)}</td></tr>
                    ${file.description ? `<tr><td><strong>Опис:</strong></td><td>${escapeHtml(file.description)}</td></tr>` : ''}
                    ${file.alt_text ? `<tr><td><strong>Alt текст:</strong></td><td>${escapeHtml(file.alt_text)}</td></tr>` : ''}
                </table>
                <div class="mt-3">
                    <a href="${escapeHtml(fileUrl)}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Відкрити в новій вкладці
                    </a>
                </div>
            </div>
        `;
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    /**
     * Ініціалізація модального вікна редагування
     */
    function initEditModal() {
        if (state.editModalInitialized) return;
        
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editForm');
        const saveBtn = document.getElementById('saveEditBtn');
        
        if (!editModal || !editForm || !saveBtn) return;
        
        state.editModalInitialized = true;
        
        // Використовуємо делегування подій
        document.addEventListener('click', function(e) {
            // Перевіряємо клік на кнопку або іконку всередині кнопки
            const editBtn = e.target.closest('.edit-media');
            if (!editBtn) {
                // Якщо клік на іконку всередині кнопки
                const icon = e.target.closest('i');
                if (icon) {
                    const parentBtn = icon.closest('.edit-media');
                    if (parentBtn) {
                        handleEditClick(parentBtn, e);
                    }
                }
                return;
            }
            
            handleEditClick(editBtn, e);
        });
        
        // Збереження форми
        saveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            editForm.submit();
        });
    }
    
    /**
     * Обробка кліку на кнопку редагування
     */
    function handleEditClick(editBtn, e) {
        e.preventDefault();
        e.stopPropagation();
        
        const mediaId = editBtn.dataset.id;
        if (!mediaId) {
            console.error('Media ID not found');
            return;
        }
        
        const editModal = document.getElementById('editModal');
        if (!editModal) {
            console.error('Edit modal not found');
            return;
        }
        
        // Завантажуємо дані
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_file');
        url.searchParams.set('media_id', mediaId);
        
        fetch(url.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.file) {
                    const mediaIdInput = document.getElementById('editMediaId');
                    const titleInput = document.getElementById('editTitle');
                    const descInput = document.getElementById('editDescription');
                    const altInput = document.getElementById('editAlt');
                    
                    if (mediaIdInput) mediaIdInput.value = data.file.id;
                    if (titleInput) titleInput.value = data.file.title || data.file.original_name || '';
                    if (descInput) descInput.value = data.file.description || '';
                    if (altInput) altInput.value = data.file.alt_text || '';
                    
                    // Показуємо модальне вікно
                    let modal;
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        modal = new bootstrap.Modal(editModal);
                        modal.show();
                    } else {
                        // Fallback для старих версій Bootstrap
                        modal = $(editModal).modal ? $(editModal) : null;
                        if (modal) {
                            modal.modal('show');
                        } else {
                            editModal.style.display = 'block';
                            editModal.classList.add('show');
                        }
                    }
                } else {
                    alert('Помилка завантаження даних файлу: ' + (data.error || 'Невідома помилка'));
                }
            })
            .catch(error => {
                console.error('Error loading file:', error);
                alert('Помилка завантаження даних файлу. Перевірте консоль для деталей.');
            });
    }
    
    /**
     * Ініціалізація кнопок видалення
     */
    function initDeleteButtons() {
        if (state.deleteButtonsInitialized) return;
        state.deleteButtonsInitialized = true;
        
        // Використовуємо делегування подій
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-media');
            if (!deleteBtn) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            const mediaId = deleteBtn.dataset.id;
            if (!mediaId) return;
            
            if (!confirm('Ви впевнені, що хочете видалити цей файл?')) {
                return;
            }
            
            // Створюємо форму для видалення
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const csrfToken = document.querySelector('input[name="csrf_token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfToken.value;
                form.appendChild(csrfInput);
            }
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);
            
            const mediaIdInput = document.createElement('input');
            mediaIdInput.type = 'hidden';
            mediaIdInput.name = 'media_id';
            mediaIdInput.value = mediaId;
            form.appendChild(mediaIdInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    /**
     * Ініціалізація перемикання виду
     */
    function initViewToggle() {
        if (state.viewToggleInitialized) return;
        state.viewToggleInitialized = true;
        
        const viewButtons = document.querySelectorAll('[data-view]');
        const mediaGrid = document.getElementById('mediaGrid');
        const mediaTable = document.querySelector('.media-table');
        
        if (!viewButtons.length || !mediaGrid) return;
        
        // Відновлюємо збережений вигляд
        const savedView = localStorage.getItem('mediaView') || 'grid';
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                if (view === 'list') {
                    mediaGrid.classList.add('media-list-view');
                    if (mediaTable) mediaTable.style.display = 'table';
                } else {
                    mediaGrid.classList.remove('media-list-view');
                    if (mediaTable) mediaTable.style.display = 'none';
                }
                
                localStorage.setItem('mediaView', view);
            });
            
            // Встановлюємо початковий стан
            if (btn.dataset.view === savedView) {
                btn.click();
            }
        });
    }
    
    /**
     * Ініціалізація drag and drop
     */
    function initDragAndDrop() {
        const uploadModal = document.getElementById('uploadModal');
        const fileInput = document.getElementById('fileInput');
        
        if (!uploadModal || !fileInput) return;
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadModal.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadModal.addEventListener(eventName, function() {
                uploadModal.classList.add('drag-over');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadModal.addEventListener(eventName, function() {
                uploadModal.classList.remove('drag-over');
            }, false);
        });
        
        uploadModal.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files);
            }
        }, false);
    }
    
    /**
     * Ініціалізація кнопок панелі інструментів
     */
    function initToolbarButtons() {
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        // Обробка масового видалення
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const checked = document.querySelectorAll('.media-checkbox:checked');
                if (checked.length === 0) {
                    alert('Виберіть файли для видалення');
                    return;
                }
                
                if (!confirm(`Ви впевнені, що хочете видалити ${checked.length} файл(ів)?`)) {
                    return;
                }
                
                // Створюємо форму для масового видалення
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const csrfToken = document.querySelector('input[name="csrf_token"]');
                if (csrfToken) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = csrfToken.value;
                    form.appendChild(csrfInput);
                }
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'bulk_delete';
                form.appendChild(actionInput);
                
                checked.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'media_ids[]';
                    input.value = checkbox.dataset.id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            });
        }
    }
    
    /**
     * Ініціалізація чекбоксів
     */
    function initMediaCheckboxes() {
        const checkboxes = document.querySelectorAll('.media-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        
        if (!checkboxes.length) return;
        
        // Оновлення видимості панелі масових дій
        function updateBulkActions() {
            const checked = document.querySelectorAll('.media-checkbox:checked');
            if (bulkActions) {
                bulkActions.style.display = checked.length > 0 ? 'flex' : 'none';
            }
        }
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });
        
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(cb => cb.checked = true);
                updateBulkActions();
            });
        }
        
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(cb => cb.checked = false);
                updateBulkActions();
            });
        }
        
        updateBulkActions();
    }
    
    /**
     * Екранування HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
})();
