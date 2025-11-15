/**
 * JavaScript для керування медіафайлами
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    // Конфігурація
    const config = {
        uploadUrl: window.location.pathname,
        maxFileSize: 10485760 // 10 MB
    };
    
    // Глобальний стан
    const state = {
        filesToUpload: []
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
    });
    
    /**
     * Ініціалізація модального вікна завантаження
     */
    function initUploadModal() {
        const uploadModal = document.getElementById('uploadModal');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        
        if (!uploadModal || !fileInput || !uploadBtn) return;
        
        // Обробка вибору файлів
        fileInput.addEventListener('change', function(e) {
            handleFileSelect(e.target.files);
        });
        
        // Обробка завантаження
        uploadBtn.addEventListener('click', uploadFiles);
        
        // Очищення при закритті модального вікна
        uploadModal.addEventListener('hidden.bs.modal', resetUploadForm);
    }
    
    /**
     * Скидання форми завантаження
     */
    function resetUploadForm() {
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        
        if (fileInput) fileInput.value = '';
        if (filePreview) filePreview.innerHTML = '';
        
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
        const filePreview = document.getElementById('filePreview');
        if (!filePreview) return;
        
        filePreview.innerHTML = '';
        state.filesToUpload = [];
        
        Array.from(files).forEach(file => {
            // Перевірка розміру файлу
            if (file.size > config.maxFileSize) {
                alert(`Файл "${file.name}" занадто великий. Максимальний розмір: 10 MB`);
                return;
            }
            
            state.filesToUpload.push(file);
            const previewItem = createFilePreview(file);
            filePreview.appendChild(previewItem);
        });
    }
    
    /**
     * Створення прев'ю файлу
     */
    function createFilePreview(file) {
        const item = document.createElement('div');
        item.className = 'file-preview-item';
        item.dataset.fileName = file.name;
        
        // Прев'ю для зображень
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;
                item.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else {
            // Іконка для інших файлів
            const icon = document.createElement('i');
            icon.className = 'fas fa-file fa-3x text-muted';
            item.appendChild(icon);
        }
        
        // Назва файлу
        const fileName = document.createElement('div');
        fileName.className = 'file-name';
        fileName.textContent = file.name;
        item.appendChild(fileName);
        
        // Кнопка видалення
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-file';
        removeBtn.type = 'button';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.addEventListener('click', function() {
            item.remove();
            state.filesToUpload = state.filesToUpload.filter(f => f.name !== file.name);
        });
        item.appendChild(removeBtn);
        
        return item;
    }
    
    /**
     * Завантаження файлів
     */
    async function uploadFiles() {
        if (state.filesToUpload.length === 0) {
            alert('Виберіть файли для завантаження');
            return;
        }
        
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = uploadProgress?.querySelector('.progress-bar');
        
        if (!uploadBtn || !uploadProgress || !progressBar) return;
        
        uploadBtn.disabled = true;
        uploadProgress.classList.remove('d-none');
        progressBar.style.width = '0%';
        
        const title = document.getElementById('fileTitle')?.value || '';
        const description = document.getElementById('fileDescription')?.value || '';
        const alt = document.getElementById('fileAlt')?.value || '';
        const csrfToken = getCsrfToken();
        
        let uploaded = 0;
        const total = state.filesToUpload.length;
        let hasError = false;
        
        // Завантаження файлів послідовно
        for (let i = 0; i < state.filesToUpload.length; i++) {
            const file = state.filesToUpload[i];
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'upload');
                formData.append('title', i === 0 ? title : '');
                formData.append('description', i === 0 ? description : '');
                formData.append('alt_text', i === 0 ? alt : '');
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch(config.uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    uploaded++;
                    progressBar.style.width = ((uploaded / total) * 100) + '%';
                } else {
                    hasError = true;
                    alert('Помилка завантаження файлу "' + file.name + '": ' + (data.error || 'Невідома помилка'));
                }
            } catch (error) {
                console.error('Upload error:', error);
                hasError = true;
                alert('Помилка завантаження файлу "' + file.name + '"');
            }
        }
        
        uploadBtn.disabled = false;
        
        if (!hasError && uploaded === total) {
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    }
    
    /**
     * Ініціалізація модального вікна перегляду
     */
    function initViewModal() {
        // Використовуємо делегування подій для динамічно доданих елементів
        document.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.view-media');
            if (viewBtn) {
                const mediaId = viewBtn.dataset.id;
                if (mediaId) {
                    viewMedia(mediaId);
                }
            }
        });
    }
    
    /**
     * Перегляд медіафайлу
     */
    async function viewMedia(mediaId) {
        const viewModalEl = document.getElementById('viewModal');
        const viewModalBody = document.getElementById('viewModalBody');
        
        if (!viewModalEl || !viewModalBody) return;
        
        const viewModal = new bootstrap.Modal(viewModalEl);
        viewModalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Завантаження...</span></div></div>';
        viewModal.show();
        
        try {
            const response = await fetch(`${config.uploadUrl}?action=get_file&media_id=${mediaId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.file) {
                viewModalBody.innerHTML = renderMediaView(data.file);
                initCopyUrlButton();
            } else {
                viewModalBody.innerHTML = '<div class="alert alert-danger">Помилка завантаження файлу</div>';
            }
        } catch (error) {
            console.error('View error:', error);
            viewModalBody.innerHTML = '<div class="alert alert-danger">Помилка завантаження файлу</div>';
        }
    }
    
    /**
     * Рендеринг перегляду медіафайлу
     */
    function renderMediaView(file) {
        let html = '<div class="row">';
        
        // Медіа контент
        function toProtocolRelativeUrl(url) {
            if (!url) return url;
            if (url.startsWith('//')) return url;
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return '//' + url.replace(/^https?:\/\//, '');
            }
            return url;
        }
        
        const fileUrl = toProtocolRelativeUrl(file.file_url);
        
        if (file.media_type === 'image') {
            html += `<div class="col-md-8"><img src="${escapeHtml(fileUrl)}" class="media-view-image img-fluid" alt="${escapeHtml(file.alt_text || file.title || '')}"></div>`;
        } else if (file.media_type === 'video') {
            html += `<div class="col-md-8"><video src="${escapeHtml(fileUrl)}" class="media-view-image img-fluid" controls></video></div>`;
        } else if (file.media_type === 'audio') {
            html += `<div class="col-md-8"><audio src="${escapeHtml(fileUrl)}" class="w-100" controls></audio></div>`;
        } else {
            html += `<div class="col-md-8"><div class="text-center p-5"><i class="fas fa-file fa-5x text-muted"></i><p class="mt-3">${escapeHtml(file.original_name)}</p></div></div>`;
        }
        
        // Інформація про файл
        html += '<div class="col-md-4">';
        html += '<table class="table media-info-table">';
        html += `<tr><td>Назва:</td><td>${escapeHtml(file.title || file.original_name)}</td></tr>`;
        if (file.description) {
            html += `<tr><td>Опис:</td><td>${escapeHtml(file.description)}</td></tr>`;
        }
        if (file.alt_text) {
            html += `<tr><td>Alt текст:</td><td>${escapeHtml(file.alt_text)}</td></tr>`;
        }
        if (file.width && file.height) {
            html += `<tr><td>Розміри:</td><td>${file.width} × ${file.height} px</td></tr>`;
        }
        html += `<tr><td>Розмір файлу:</td><td>${formatFileSize(file.file_size)}</td></tr>`;
        html += `<tr><td>Тип:</td><td>${escapeHtml(file.mime_type)}</td></tr>`;
        html += `<tr><td>Завантажено:</td><td>${new Date(file.uploaded_at).toLocaleString('uk-UA')}</td></tr>`;
        html += `<tr><td>URL:</td><td><input type="text" class="form-control form-control-sm" value="${escapeHtml(fileUrl)}" readonly></td></tr>`;
        html += '</table>';
        html += '<div class="mt-3">';
        html += `<button class="btn btn-primary btn-sm copy-url" data-url="${escapeHtml(fileUrl)}"><i class="fas fa-copy me-2"></i>Копіювати URL</button>`;
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        return html;
    }
    
    /**
     * Ініціалізація кнопки копіювання URL
     */
    function initCopyUrlButton() {
        const copyBtn = document.querySelector('.copy-url');
        if (copyBtn) {
            copyBtn.addEventListener('click', async function() {
                const url = this.dataset.url;
                try {
                    await navigator.clipboard.writeText(url);
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check me-2"></i>Скопійовано!';
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                    }, 2000);
                } catch (error) {
                    console.error('Copy error:', error);
                }
            });
        }
    }
    
    /**
     * Ініціалізація модального вікна редагування
     */
    function initEditModal() {
        const saveEditBtn = document.getElementById('saveEditBtn');
        
        // Делегування подій для кнопок редагування
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-media');
            if (editBtn) {
                const mediaId = editBtn.dataset.id;
                if (mediaId) {
                    loadMediaForEdit(mediaId);
                }
            }
        });
        
        if (saveEditBtn) {
            saveEditBtn.addEventListener('click', saveMediaEdit);
        }
    }
    
    /**
     * Завантаження даних для редагування
     */
    async function loadMediaForEdit(mediaId) {
        const editModalEl = document.getElementById('editModal');
        if (!editModalEl) return;
        
        const editModal = new bootstrap.Modal(editModalEl);
        
        try {
            const response = await fetch(`${config.uploadUrl}?action=get_file&media_id=${mediaId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.file) {
                const file = data.file;
                document.getElementById('editMediaId').value = file.id;
                document.getElementById('editTitle').value = file.title || '';
                document.getElementById('editDescription').value = file.description || '';
                document.getElementById('editAlt').value = file.alt_text || '';
                editModal.show();
            } else {
                alert('Помилка завантаження файлу');
            }
        } catch (error) {
            console.error('Load error:', error);
            alert('Помилка завантаження файлу');
        }
    }
    
    /**
     * Збереження змін
     */
    async function saveMediaEdit() {
        const form = document.getElementById('editForm');
        const saveBtn = document.getElementById('saveEditBtn');
        
        if (!form || !saveBtn) return;
        
        const formData = new FormData(form);
        const originalHtml = saveBtn.innerHTML;
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Збереження...';
        
        try {
            const response = await fetch(config.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                location.reload();
            } else {
                alert('Помилка збереження: ' + (data.error || 'Невідома помилка'));
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHtml;
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Помилка збереження');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
        }
    }
    
    /**
     * Ініціалізація кнопок видалення
     */
    function initDeleteButtons() {
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-media');
            if (deleteBtn) {
                const mediaId = deleteBtn.dataset.id;
                if (mediaId && confirm('Ви впевнені, що хочете видалити цей файл?')) {
                    deleteMedia(mediaId);
                }
            }
        });
    }
    
    /**
     * Видалення медіафайлу
     */
    async function deleteMedia(mediaId) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('media_id', mediaId);
        formData.append('csrf_token', getCsrfToken());
        
        try {
            const response = await fetch(config.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Помилка видалення: ' + (data.error || 'Невідома помилка'));
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Помилка видалення');
        }
    }
    
    /**
     * Перемикання вигляду (сітка/список)
     */
    function initViewToggle() {
        const viewButtons = document.querySelectorAll('[data-view]');
        const mediaGrid = document.getElementById('mediaGrid');
        
        if (!mediaGrid) return;
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                if (view === 'list') {
                    mediaGrid.classList.add('media-list-view');
                    localStorage.setItem('mediaView', 'list');
                } else {
                    mediaGrid.classList.remove('media-list-view');
                    localStorage.setItem('mediaView', 'grid');
                }
            });
        });
        
        // Відновлення збереженого вигляду
        const savedView = localStorage.getItem('mediaView');
        if (savedView === 'list') {
            mediaGrid.classList.add('media-list-view');
            viewButtons.forEach(btn => {
                if (btn.dataset.view === 'list') {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }
    }
    
    /**
     * Ініціалізація drag and drop
     */
    function initDragAndDrop() {
        const uploadModal = document.getElementById('uploadModal');
        const fileInput = document.getElementById('fileInput');
        
        if (!uploadModal || !fileInput) return;
        
        const modalBody = uploadModal.querySelector('.modal-body');
        if (!modalBody) return;
        
        // Створення dropzone
        const dropzone = document.createElement('div');
        dropzone.className = 'upload-dropzone mb-3';
        dropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Перетягніть файли сюди або натисніть для вибору</p>';
        dropzone.setAttribute('role', 'button');
        dropzone.setAttribute('tabindex', '0');
        
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });
        
        dropzone.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });
        
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });
        
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
        });
        
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files);
            }
        });
        
        // Вставка dropzone перед input
        const fileInputContainer = fileInput.parentElement;
        if (fileInputContainer) {
            fileInputContainer.insertBefore(dropzone, fileInput);
        }
    }
    
    /**
     * Отримання CSRF токену
     */
    function getCsrfToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }
    
    /**
     * Екранування HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Форматування розміру файлу
     */
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return size.toFixed(2) + ' ' + units[unitIndex];
    }
})();
