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
        filesToUpload: [],
        filtersInitialized: false,
        sortInitialized: false,
        toolbarInitialized: false,
        viewToggleInitialized: false,
        searchTimeout: null,
        currentPage: 1,
        totalPages: 1,
        isLoading: false,
        hasMore: true
    };
    
    /**
     * Показ повідомлення про кінець списку
     */
    function showEndMessage(mediaGrid) {
        if (!mediaGrid) return;
        
        // Перевіряємо, чи вже є повідомлення
        let endMessage = document.getElementById('loadMoreEndMessage');
        if (endMessage) {
            return;
        }
        
        // Створюємо повідомлення
        endMessage = document.createElement('div');
        endMessage.id = 'loadMoreEndMessage';
        endMessage.className = 'load-more-end-message';
        endMessage.innerHTML = '<div class="load-more-end-content"><i class="fas fa-check-circle"></i><span>Всі файли завантажено</span></div>';
        mediaGrid.parentNode.appendChild(endMessage);
    }
    
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
        initSortSelect();
        initMediaCheckboxes();
        initFilters();
        
        // Ініціалізуємо стан пагінації при завантаженні
        const mediaGrid = document.getElementById('mediaGrid');
        if (mediaGrid) {
            const totalPages = parseInt(mediaGrid.dataset.totalPages || '1');
            const currentPage = parseInt(mediaGrid.dataset.currentPage || '1');
            state.currentPage = currentPage;
            state.totalPages = totalPages;
            state.hasMore = currentPage < totalPages;
            console.log('Initial pagination state:', { currentPage, totalPages, hasMore: state.hasMore });
            
            // Якщо файлів більше немає, показуємо повідомлення
            if (!state.hasMore && totalPages > 0) {
                showEndMessage(mediaGrid);
            }
        }
        
        // Невелика затримка для ініціалізації lazy loading після повного завантаження DOM
        setTimeout(() => {
            initLazyLoading();
        }, 100);
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
            if (file.type.startsWith('video/')) {
                icon.className = 'fas fa-video';
            } else if (file.type.startsWith('audio/')) {
                icon.className = 'fas fa-music';
            } else if (file.type.includes('pdf')) {
                icon.className = 'fas fa-file-pdf';
            } else if (file.type.includes('word') || file.name.endsWith('.doc') || file.name.endsWith('.docx')) {
                icon.className = 'fas fa-file-word';
            } else {
                icon.className = 'fas fa-file';
            }
            item.appendChild(icon);
        }
        
        // Назва файлу
        const fileName = document.createElement('div');
        fileName.className = 'file-name';
        fileName.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;
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
        if (uploadProgress) {
            uploadProgress.classList.add('d-none');
        }
        
        if (uploaded > 0) {
            const uploadModal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
            if (uploadModal) {
                uploadModal.hide();
            }
            resetUploadForm();
            
            if (typeof showNotification !== 'undefined') {
                if (uploaded === total && !hasError) {
                    showNotification(`Успішно завантажено ${uploaded} файл(ів)`, 'success');
                } else {
                    showNotification(`Завантажено ${uploaded} з ${total} файлів${hasError ? '. Деякі файли не вдалося завантажити.' : ''}`, hasError ? 'warning' : 'info');
                }
            }
            
            // Оновлюємо список через AJAX
            loadMediaFiles();
        } else if (hasError) {
            if (typeof showNotification !== 'undefined') {
                showNotification('Не вдалося завантажити файли', 'danger');
            } else {
                alert('Не вдалося завантажити файли');
            }
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
        let html = '<div class="media-view-container">';
        
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
        
        // Ліва частина - медіа контент
        html += '<div class="media-view-preview">';
        if (file.media_type === 'image') {
            html += `<div class="media-preview-wrapper"><img src="${escapeHtml(fileUrl)}" class="media-preview-content" alt="${escapeHtml(file.alt_text || file.title || '')}"></div>`;
        } else if (file.media_type === 'video') {
            html += `<div class="media-preview-wrapper"><video src="${escapeHtml(fileUrl)}" class="media-preview-content" controls></video></div>`;
        } else if (file.media_type === 'audio') {
            html += `<div class="media-preview-wrapper audio-wrapper">`;
            html += `<div class="audio-preview-container">`;
            html += `<div class="audio-icon-large"><i class="fas fa-music"></i></div>`;
            html += `<audio src="${escapeHtml(fileUrl)}" class="media-preview-content audio-player" controls controlsList="nodownload"></audio>`;
            html += `<div class="audio-info">`;
            html += `<div class="audio-title">${escapeHtml(file.title || file.original_name)}</div>`;
            html += `<div class="audio-meta">${formatFileSize(file.file_size)} • ${escapeHtml(file.mime_type)}</div>`;
            html += `</div>`;
            html += `</div>`;
            html += `</div>`;
        } else {
            // Определяем тип документа и иконку
            const extension = (file.original_name || '').split('.').pop().toLowerCase();
            let docIcon = 'fa-file';
            let docColor = '#6c757d';
            
            if (extension === 'pdf') {
                docIcon = 'fa-file-pdf';
                docColor = '#dc2626';
            } else if (['doc', 'docx'].includes(extension)) {
                docIcon = 'fa-file-word';
                docColor = '#2563eb';
            } else if (['xls', 'xlsx'].includes(extension)) {
                docIcon = 'fa-file-excel';
                docColor = '#16a34a';
            } else if (['ppt', 'pptx'].includes(extension)) {
                docIcon = 'fa-file-powerpoint';
                docColor = '#ea580c';
            } else if (extension === 'txt') {
                docIcon = 'fa-file-lines';
                docColor = '#6b7280';
            }
            
            html += `<div class="media-preview-wrapper document-wrapper">`;
            html += `<div class="document-preview-container">`;
            html += `<div class="document-icon-large" style="color: ${docColor};">`;
            html += `<i class="fas ${docIcon}"></i>`;
            html += `</div>`;
            html += `<div class="document-info">`;
            html += `<div class="document-extension">${extension.toUpperCase()}</div>`;
            html += `</div>`;
            html += `</div>`;
            html += `</div>`;
        }
        html += '</div>';
        
        // Права частина - інформація про файл
        html += '<div class="media-view-details">';
        html += '<div class="media-details-header">';
        html += '<h6 class="media-details-title">Деталі файлу</h6>';
        html += '</div>';
        
        html += '<div class="media-details-content">';
        html += '<div class="media-detail-item">';
        html += '<div class="media-detail-label">Назва</div>';
        html += `<div class="media-detail-value">${escapeHtml(file.title || file.original_name)}</div>`;
        html += '</div>';
        
        if (file.description) {
            html += '<div class="media-detail-item">';
            html += '<div class="media-detail-label">Опис</div>';
            html += `<div class="media-detail-value">${escapeHtml(file.description)}</div>`;
            html += '</div>';
        }
        
        if (file.alt_text) {
            html += '<div class="media-detail-item">';
            html += '<div class="media-detail-label">Alt текст</div>';
            html += `<div class="media-detail-value">${escapeHtml(file.alt_text)}</div>`;
            html += '</div>';
        }
        
        if (file.width && file.height) {
            html += '<div class="media-detail-item">';
            html += '<div class="media-detail-label">Розміри</div>';
            html += `<div class="media-detail-value">${file.width} × ${file.height} px</div>`;
            html += '</div>';
        }
        
        html += '<div class="media-detail-item">';
        html += '<div class="media-detail-label">Розмір файлу</div>';
        html += `<div class="media-detail-value">${formatFileSize(file.file_size)}</div>`;
        html += '</div>';
        
        html += '<div class="media-detail-item">';
        html += '<div class="media-detail-label">Тип</div>';
        html += `<div class="media-detail-value">${escapeHtml(file.mime_type)}</div>`;
        html += '</div>';
        
        html += '<div class="media-detail-item">';
        html += '<div class="media-detail-label">Завантажено</div>';
        html += `<div class="media-detail-value">${new Date(file.uploaded_at).toLocaleString('uk-UA')}</div>`;
        html += '</div>';
        
        html += '<div class="media-detail-item media-detail-url">';
        html += '<div class="media-detail-label">URL</div>';
        html += '<div class="media-url-input-group">';
        html += `<input type="text" class="media-url-input" value="${escapeHtml(fileUrl)}" readonly>`;
        html += `<button class="btn btn-copy-url copy-url" data-url="${escapeHtml(fileUrl)}" title="Копіювати URL">`;
        html += '<i class="fas fa-copy"></i>';
        html += '</button>';
        html += '</div>';
        html += '</div>';
        
        html += '</div>'; // media-details-content
        
        html += '<div class="media-details-footer">';
        html += `<a href="${escapeHtml(fileUrl)}" class="btn btn-download-file" download="${escapeHtml(file.original_name)}">`;
        html += '<i class="fas fa-download me-2"></i>Завантажити файл';
        html += '</a>';
        html += '</div>';
        
        html += '</div>'; // media-view-details
        html += '</div>'; // media-view-container
        
        return html;
    }
    
    /**
     * Ініціалізація кнопки копіювання URL
     */
    function initCopyUrlButton() {
        const copyBtns = document.querySelectorAll('.copy-url');
        copyBtns.forEach(copyBtn => {
            copyBtn.addEventListener('click', async function() {
                const url = this.dataset.url;
                try {
                    await navigator.clipboard.writeText(url);
                    
                    // Показуємо успішне повідомлення
                    if (typeof showNotification !== 'undefined') {
                        showNotification('URL скопійовано в буфер обміну', 'success', 3000);
                    }
                    
                    // Оновлюємо іконку кнопки
                    const originalHtml = this.innerHTML;
                    if (this.classList.contains('btn-copy-url-full')) {
                        this.innerHTML = '<i class="fas fa-check me-2"></i>Скопійовано!';
                    } else {
                        this.innerHTML = '<i class="fas fa-check"></i>';
                    }
                    
                    // Повертаємо оригінальний вигляд через 2 секунди
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                    }, 2000);
                } catch (error) {
                    console.error('Copy error:', error);
                    
                    // Fallback для старих браузерів
                    const urlInput = document.querySelector('.media-url-input');
                    if (urlInput) {
                        urlInput.select();
                        urlInput.setSelectionRange(0, 99999);
                        try {
                            document.execCommand('copy');
                            if (typeof showNotification !== 'undefined') {
                                showNotification('URL скопійовано в буфер обміну', 'success', 3000);
                            }
                        } catch (e) {
                            if (typeof showNotification !== 'undefined') {
                                showNotification('Не вдалося скопіювати URL', 'danger', 3000);
                            } else {
                                alert('Не вдалося скопіювати URL');
                            }
                        }
                    } else {
                        if (typeof showNotification !== 'undefined') {
                            showNotification('Не вдалося скопіювати URL', 'danger', 3000);
                        } else {
                            alert('Не вдалося скопіювати URL');
                        }
                    }
                }
            });
        });
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
                const editModalEl = document.getElementById('editModal');
                if (editModalEl) {
                    const editModal = bootstrap.Modal.getInstance(editModalEl);
                    if (editModal) {
                        editModal.hide();
                    }
                }
                if (typeof showNotification !== 'undefined') {
                    showNotification('Файл успішно оновлено', 'success');
                }
                // Оновлюємо список через AJAX
                loadMediaFiles();
            } else {
                if (typeof showNotification !== 'undefined') {
                    showNotification('Помилка збереження: ' + (data.error || 'Невідома помилка'), 'danger');
                } else {
                    alert('Помилка збереження: ' + (data.error || 'Невідома помилка'));
                }
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
                if (typeof showNotification !== 'undefined') {
                    showNotification('Файл успішно видалено', 'success');
                }
                // Оновлюємо список через AJAX
                loadMediaFiles();
            } else {
                if (typeof showNotification !== 'undefined') {
                    showNotification('Помилка видалення: ' + (data.error || 'Невідома помилка'), 'danger');
                } else {
                    alert('Помилка видалення: ' + (data.error || 'Невідома помилка'));
                }
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
        if (state.viewToggleInitialized) return;
        state.viewToggleInitialized = true;
        
        const mediaGrid = document.getElementById('mediaGrid');
        if (!mediaGrid) return;
        
        // Використовуємо делегування подій
        document.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('[data-view]');
            if (!viewBtn) return;
            
            e.preventDefault();
            const view = viewBtn.dataset.view;
            const viewButtons = document.querySelectorAll('[data-view]');
            
            // Оновлюємо активну кнопку
            viewButtons.forEach(b => b.classList.remove('active'));
            viewBtn.classList.add('active');
            
            // Перемикаємо вигляд
            if (view === 'list') {
                mediaGrid.classList.add('media-list-view');
                localStorage.setItem('mediaView', 'list');
            } else {
                mediaGrid.classList.remove('media-list-view');
                localStorage.setItem('mediaView', 'grid');
            }
        });
        
        // Відновлюємо збережений вигляд при завантаженні
        const savedView = localStorage.getItem('mediaView');
        if (savedView === 'list') {
            mediaGrid.classList.add('media-list-view');
            const viewButtons = document.querySelectorAll('[data-view]');
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
     * Ініціалізація кнопок панелі інструментів
     */
    function initToolbarButtons() {
        if (state.toolbarInitialized) return;
        state.toolbarInitialized = true;
        
        // Використовуємо делегування подій для всіх кнопок панелі інструментів
        document.addEventListener('click', function(e) {
            // Кнопка оновлення
            if (e.target.closest('#refreshBtn')) {
                e.preventDefault();
                const btn = e.target.closest('#refreshBtn');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-spin');
                }
                loadMediaFiles();
                setTimeout(() => {
                    if (icon) {
                        icon.classList.remove('fa-spin');
                    }
                }, 500);
                return;
            }
            
            // Кнопка вибрати все
            if (e.target.closest('#selectAllBtn')) {
                e.preventDefault();
                const checkboxes = document.querySelectorAll('.media-checkbox:not(:checked)');
                checkboxes.forEach(cb => {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change'));
                });
                return;
            }
            
            // Кнопка скасувати вибір
            if (e.target.closest('#deselectAllBtn')) {
                e.preventDefault();
                const checkboxes = document.querySelectorAll('.media-checkbox:checked');
                checkboxes.forEach(cb => {
                    cb.checked = false;
                    cb.dispatchEvent(new Event('change'));
                });
                return;
            }
            
            // Кнопка масового видалення
            if (e.target.closest('#bulkDeleteBtn')) {
                e.preventDefault();
                const selected = getSelectedMediaIds();
                if (selected.length === 0) {
                    if (typeof showNotification !== 'undefined') {
                        showNotification('Виберіть файли для видалення', 'warning');
                    } else {
                        alert('Виберіть файли для видалення');
                    }
                    return;
                }
                
                if (!confirm(`Ви впевнені, що хочете видалити ${selected.length} файл(ів)?`)) {
                    return;
                }
                
                deleteSelectedFiles(selected);
                return;
            }
        });
    }
    
    /**
     * Ініціалізація селекту сортування
     */
    function initSortSelect() {
        if (state.sortInitialized) return;
        state.sortInitialized = true;
        
        // Використовуємо делегування подій для динамічних елементів
        document.addEventListener('change', function(e) {
            if (e.target && e.target.id === 'sortSelect') {
                const value = e.target.value;
                // Розбиваємо значення: останнє підкреслення розділяє поле та напрямок
                // Наприклад: "uploaded_at_desc" -> orderBy="uploaded_at", orderDir="DESC"
                // "file_size_asc" -> orderBy="file_size", orderDir="ASC"
                const lastUnderscore = value.lastIndexOf('_');
                if (lastUnderscore === -1) {
                    console.error('Invalid sort value:', value);
                    return;
                }
                const orderBy = value.substring(0, lastUnderscore);
                const orderDir = value.substring(lastUnderscore + 1).toUpperCase();
                
                // Перевіряємо валідність напрямку
                if (orderDir !== 'ASC' && orderDir !== 'DESC') {
                    console.error('Invalid sort direction:', orderDir);
                    return;
                }
                
                loadMediaFiles({
                    order_by: orderBy,
                    order_dir: orderDir
                });
            }
        });
    }
    
    /**
     * Ініціалізація чекбоксів медіафайлів
     */
    function initMediaCheckboxes() {
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('media-checkbox')) {
                updateBulkActionsVisibility();
                updateCardSelection(e.target);
            }
        });
        
        // Початкова перевірка
        updateBulkActionsVisibility();
    }
    
    /**
     * Оновлення видимості масових дій
     */
    function updateBulkActionsVisibility() {
        const bulkActions = document.getElementById('bulkActions');
        const selected = getSelectedMediaIds();
        
        if (bulkActions) {
            if (selected.length > 0) {
                bulkActions.style.display = 'flex';
            } else {
                bulkActions.style.display = 'none';
            }
        }
    }
    
    /**
     * Оновлення вигляду картки при виборі
     */
    function updateCardSelection(checkbox) {
        const card = checkbox.closest('.media-card');
        if (card) {
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }
    }
    
    /**
     * Отримання ID вибраних файлів
     */
    function getSelectedMediaIds() {
        const checkboxes = document.querySelectorAll('.media-checkbox:checked');
        return Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));
    }
    
    /**
     * Видалення вибраних файлів
     */
    async function deleteSelectedFiles(ids) {
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Видалення...';
        }
        
        let successCount = 0;
        let errorCount = 0;
        
        for (const id of ids) {
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('media_id', id);
                formData.append('csrf_token', getCsrfToken());
                
                const response = await fetch(config.uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    successCount++;
                    // Видаляємо картку з DOM
                    const mediaItem = document.querySelector(`.media-item[data-id="${id}"]`);
                    if (mediaItem) {
                        mediaItem.style.opacity = '0';
                        setTimeout(() => {
                            mediaItem.remove();
                            updateBulkActionsVisibility();
                        }, 200);
                    }
                } else {
                    errorCount++;
                }
            } catch (error) {
                console.error('Delete error:', error);
                errorCount++;
            }
        }
        
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-trash"></i><span class="d-none d-md-inline ms-1">Видалити</span>';
        }
        
        // Показуємо результат
        if (typeof showNotification !== 'undefined') {
            if (errorCount === 0) {
                showNotification(`Успішно видалено ${successCount} файл(ів)`, 'success');
            } else {
                showNotification(`Видалено ${successCount} файл(ів), помилок: ${errorCount}`, 'warning');
            }
        } else {
            alert(`Видалено ${successCount} файл(ів)${errorCount > 0 ? ', помилок: ' + errorCount : ''}`);
        }
        
        // Оновлюємо список через AJAX
        if (successCount > 0) {
            loadMediaFiles();
        }
    }
    
    /**
     * Завантаження медіафайлів через AJAX
     */
    function loadMediaFiles(additionalParams = {}) {
        const mediaGrid = document.getElementById('mediaGrid');
        if (!mediaGrid) return;
        
        // Отримуємо поточні параметри фільтрації
        const urlParams = new URLSearchParams(window.location.search);
        const params = {
            action: 'load_files',
            type: urlParams.get('type') || '',
            search: urlParams.get('search') || '',
            order_by: urlParams.get('order_by') || 'uploaded_at',
            order_dir: urlParams.get('order_dir') || 'DESC',
            page: urlParams.get('page') || '1',
            ...additionalParams
        };
        
        // Показуємо індикатор завантаження
        mediaGrid.style.opacity = '0.5';
        mediaGrid.style.pointerEvents = 'none';
        
        const queryString = new URLSearchParams(params).toString();
        
        return fetch(`${config.uploadUrl}?${queryString}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Оновлюємо контент
                mediaGrid.innerHTML = data.html;
                
                // Оновлюємо стан пагінації
                state.currentPage = data.page || 1;
                state.totalPages = data.pages || 1;
                state.hasMore = state.currentPage < state.totalPages;
                
                // Оновлюємо URL без перезагрузки
                const newUrl = new URL(window.location);
                Object.keys(params).forEach(key => {
                    if (key !== 'action' && params[key]) {
                        newUrl.searchParams.set(key, params[key]);
                    } else if (key !== 'action') {
                        newUrl.searchParams.delete(key);
                    }
                });
                window.history.pushState({}, '', newUrl);
                
                // Оновлюємо значення в селектах та інпутах
                const sortSelect = document.getElementById('sortSelect');
                if (sortSelect && params.order_by && params.order_dir) {
                    const sortValue = `${params.order_by}_${params.order_dir.toLowerCase()}`;
                    // Перевіряємо, чи існує така опція
                    const optionExists = Array.from(sortSelect.options).some(opt => opt.value === sortValue);
                    if (optionExists) {
                        sortSelect.value = sortValue;
                    }
                }
                
                const typeSelect = document.querySelector('.media-filter-select');
                if (typeSelect && params.type !== undefined) {
                    typeSelect.value = params.type;
                }
                
                const searchInput = document.querySelector('.media-search-input');
                if (searchInput && params.search !== undefined) {
                    searchInput.value = params.search;
                }
                
                // Реініціалізуємо обробники подій
                initViewModal();
                initEditModal();
                initDeleteButtons();
                initMediaCheckboxes();
                initLazyLoading();
                // initSortSelect(), initFilters(), initToolbarButtons() та initViewToggle() використовують делегування подій, тому не потрібна реініціалізація
                
                // Відновлюємо активний стан кнопок виду
                const savedView = localStorage.getItem('mediaView');
                if (savedView === 'list') {
                    mediaGrid.classList.add('media-list-view');
                    document.querySelectorAll('[data-view]').forEach(btn => {
                        if (btn.dataset.view === 'list') {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                } else {
                    mediaGrid.classList.remove('media-list-view');
                    document.querySelectorAll('[data-view]').forEach(btn => {
                        if (btn.dataset.view === 'grid') {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                }
            } else {
                if (typeof showNotification !== 'undefined') {
                    showNotification(data.error || 'Помилка завантаження', 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Load files error:', error);
            if (typeof showNotification !== 'undefined') {
                showNotification('Помилка завантаження файлів', 'danger');
            }
        })
        .finally(() => {
            mediaGrid.style.opacity = '1';
            mediaGrid.style.pointerEvents = 'auto';
        });
    }
    
    /**
     * Ініціалізація ленивої завантаження (infinite scroll)
     */
    function initLazyLoading() {
        // Видаляємо старий observer, якщо він існує
        if (state.intersectionObserver) {
            state.intersectionObserver.disconnect();
            state.intersectionObserver = null;
        }
        
        // Створюємо індикатор завантаження, якщо його немає
        const mediaGrid = document.getElementById('mediaGrid');
        if (!mediaGrid) return;
        
        // Перевіряємо, чи є ще сторінки для завантаження
        if (!state.hasMore) {
            const existingIndicator = document.getElementById('loadMoreIndicator');
            if (existingIndicator) {
                existingIndicator.style.display = 'none';
            }
            return;
        }
        
        // Видаляємо старе повідомлення про кінець, якщо воно є
        const existingEndMessage = document.getElementById('loadMoreEndMessage');
        if (existingEndMessage) {
            existingEndMessage.remove();
        }
        
        let loadMoreIndicator = document.getElementById('loadMoreIndicator');
        if (!loadMoreIndicator) {
            loadMoreIndicator = document.createElement('div');
            loadMoreIndicator.id = 'loadMoreIndicator';
            loadMoreIndicator.className = 'load-more-indicator';
            loadMoreIndicator.innerHTML = '<div class="load-more-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
            mediaGrid.parentNode.appendChild(loadMoreIndicator);
        }
        
        // Показуємо індикатор, якщо є ще сторінки
        if (state.hasMore) {
            loadMoreIndicator.style.display = 'block';
            console.log('Load more indicator created and visible', { currentPage: state.currentPage, totalPages: state.totalPages });
        } else {
            loadMoreIndicator.style.display = 'none';
            // Показуємо повідомлення про кінець
            showEndMessage(mediaGrid);
            return;
        }
        
        // Створюємо Intersection Observer
        state.intersectionObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                console.log('Intersection observer triggered', { isIntersecting: entry.isIntersecting, isLoading: state.isLoading, hasMore: state.hasMore });
                if (entry.isIntersecting && !state.isLoading && state.hasMore) {
                    console.log('Loading more files...', { currentPage: state.currentPage, totalPages: state.totalPages, hasMore: state.hasMore });
                    loadMoreFiles();
                }
            });
        }, {
            root: null,
            rootMargin: '300px',
            threshold: 0.01
        });
        
        // Спостерігаємо за індикатором
        try {
            state.intersectionObserver.observe(loadMoreIndicator);
            console.log('Observer attached to load more indicator');
        } catch (error) {
            console.error('Error attaching observer:', error);
        }
    }
    
    /**
     * Завантаження наступної сторінки файлів
     */
    function loadMoreFiles() {
        if (state.isLoading || !state.hasMore) return;
        
        const nextPage = state.currentPage + 1;
        if (nextPage > state.totalPages) {
            state.hasMore = false;
            return;
        }
        
        state.isLoading = true;
        const loadMoreIndicator = document.getElementById('loadMoreIndicator');
        if (loadMoreIndicator) {
            loadMoreIndicator.style.display = 'block';
        }
        
        // Отримуємо поточні параметри фільтрації
        const urlParams = new URLSearchParams(window.location.search);
        const params = {
            action: 'load_files',
            type: urlParams.get('type') || '',
            search: urlParams.get('search') || '',
            order_by: urlParams.get('order_by') || 'uploaded_at',
            order_dir: urlParams.get('order_dir') || 'DESC',
            page: nextPage.toString()
        };
        
        const queryString = new URLSearchParams(params).toString();
        
        fetch(`${config.uploadUrl}?${queryString}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                const mediaGrid = document.getElementById('mediaGrid');
                if (mediaGrid) {
                    const isListView = mediaGrid.classList.contains('media-list-view');
                    
                    if (isListView) {
                        // Режим таблицы
                        let tableBody = mediaGrid.querySelector('.media-table tbody');
                        if (!tableBody) {
                            const table = mediaGrid.querySelector('.media-table');
                            if (table) {
                                tableBody = table.querySelector('tbody');
                            }
                        }
                        
                        if (tableBody) {
                            // Створюємо тимчасовий контейнер для нового HTML
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data.html;
                            const newTableBody = tempDiv.querySelector('.media-table tbody');
                            
                            if (newTableBody) {
                                // Додаємо нові рядки до існуючих
                                newTableBody.querySelectorAll('tr.media-item').forEach(row => {
                                    tableBody.appendChild(row);
                                });
                            }
                        }
                    } else {
                        // Режим сетки
                        let rowContainer = mediaGrid.querySelector('.media-grid-row');
                        if (!rowContainer) {
                            // Якщо контейнера немає, створюємо його
                            rowContainer = document.createElement('div');
                            rowContainer.className = 'row media-grid-row';
                            mediaGrid.appendChild(rowContainer);
                        }
                        
                        // Створюємо тимчасовий контейнер для нового HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;
                        const newRowContainer = tempDiv.querySelector('.media-grid-row');
                        
                        if (newRowContainer) {
                            // Додаємо нові файли до існуючих
                            newRowContainer.querySelectorAll('.media-item').forEach(item => {
                                rowContainer.appendChild(item);
                            });
                        }
                    }
                }
                
                state.currentPage = nextPage;
                state.totalPages = data.pages || 1;
                state.hasMore = nextPage < state.totalPages;
                
                console.log('Files loaded', { currentPage: state.currentPage, totalPages: state.totalPages, hasMore: state.hasMore });
                
                // Реініціалізуємо обробники подій для нових елементів
                initViewModal();
                initEditModal();
                initDeleteButtons();
                initMediaCheckboxes();
                
                // Відновлюємо вигляд (grid/list)
                const savedView = localStorage.getItem('mediaView');
                if (savedView === 'list') {
                    mediaGrid.classList.add('media-list-view');
                }
                
                // Переініціалізуємо lazy loading для нової сторінки
                if (state.hasMore) {
                    initLazyLoading();
                } else {
                    if (loadMoreIndicator) {
                        loadMoreIndicator.style.display = 'none';
                    }
                    // Показуємо повідомлення про кінець
                    showEndMessage(mediaGrid);
                }
            } else {
                state.hasMore = false;
                if (loadMoreIndicator) {
                    loadMoreIndicator.style.display = 'none';
                }
                showEndMessage(mediaGrid);
            }
        })
        .catch(error => {
            console.error('Load more error:', error);
            state.hasMore = false;
            if (loadMoreIndicator) {
                loadMoreIndicator.style.display = 'none';
            }
            showEndMessage(mediaGrid);
        })
        .finally(() => {
            state.isLoading = false;
        });
    }
    
    /**
     * Ініціалізація фільтрів та пошуку
     */
    function initFilters() {
        if (state.filtersInitialized) return;
        state.filtersInitialized = true;
        
        // Використовуємо делегування подій
        // Пошук в реальному часі
        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('media-search-input')) {
                // Очищаємо попередній таймер
                if (state.searchTimeout) {
                    clearTimeout(state.searchTimeout);
                }
                
                const searchInput = e.target;
                const searchGroup = searchInput.closest('.media-search-group');
                const searchBtn = searchGroup ? searchGroup.querySelector('.media-search-btn') : null;
                
                // Показуємо індикатор завантаження
                if (searchBtn) {
                    const icon = searchBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-search');
                        icon.classList.add('fa-spinner', 'fa-spin');
                    }
                }
                
                // Встановлюємо новий таймер для debounce (500ms)
                state.searchTimeout = setTimeout(function() {
                    const typeSelect = document.querySelector('.media-filter-select');
                    const searchValue = searchInput ? searchInput.value.trim() : '';
                    const typeValue = typeSelect ? typeSelect.value : '';
                    
                    loadMediaFiles({
                        search: searchValue,
                        type: typeValue,
                        page: '1'
                    }).finally(function() {
                        // Повертаємо іконку пошуку після завершення
                        if (searchBtn) {
                            const icon = searchBtn.querySelector('i');
                            if (icon) {
                                icon.classList.remove('fa-spinner', 'fa-spin');
                                icon.classList.add('fa-search');
                            }
                        }
                    });
                }, 500);
            }
        });
        
        // Обробка форми пошуку (кнопка пошуку)
        document.addEventListener('submit', function(e) {
            const searchForm = e.target.closest('.media-filters');
            if (searchForm) {
                e.preventDefault();
                
                // Очищаємо таймер, якщо він активний
                if (state.searchTimeout) {
                    clearTimeout(state.searchTimeout);
                }
                
                const searchInput = searchForm.querySelector('.media-search-input');
                const searchGroup = searchInput ? searchInput.closest('.media-search-group') : null;
                const searchBtn = searchGroup ? searchGroup.querySelector('.media-search-btn') : null;
                const typeSelect = searchForm.querySelector('.media-filter-select') || document.querySelector('.media-filter-select');
                const searchValue = searchInput ? searchInput.value.trim() : '';
                const typeValue = typeSelect ? typeSelect.value : '';
                
                // Показуємо індикатор завантаження
                if (searchBtn) {
                    const icon = searchBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-search');
                        icon.classList.add('fa-spinner', 'fa-spin');
                    }
                }
                
                loadMediaFiles({
                    search: searchValue,
                    type: typeValue,
                    page: '1'
                }).finally(function() {
                    // Повертаємо іконку пошуку після завершення
                    if (searchBtn) {
                        const icon = searchBtn.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-spinner', 'fa-spin');
                            icon.classList.add('fa-search');
                        }
                    }
                });
            }
        });
        
        // Фільтр по типу
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('media-filter-select')) {
                // Очищаємо таймер пошуку, якщо він активний
                if (state.searchTimeout) {
                    clearTimeout(state.searchTimeout);
                }
                
                const searchInput = document.querySelector('.media-search-input');
                const searchValue = searchInput ? searchInput.value.trim() : '';
                loadMediaFiles({
                    type: e.target.value,
                    search: searchValue,
                    page: '1'
                });
            }
        });
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
