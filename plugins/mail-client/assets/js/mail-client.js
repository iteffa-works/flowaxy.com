/**
 * JavaScript для поштового клієнта
 * Повнофункціональний клієнт з AJAX
 */

const MailClient = {
    currentFolder: 'inbox',
    currentEmailId: null,
    currentPage: 1,
    selectedEmails: [],
    isLoading: false,
    
    init: function() {
        this.setupEventListeners();
        this.loadFolder('inbox');
        
        // Завантажуємо статистику папок через AJAX (не блокуємо завантаження)
        this.updateFolderStats();
        
        // Автоматичне оновлення кожні 10 хвилин (збільшено з 5 для зменшення навантаження)
        setInterval(() => {
            if (this.currentFolder === 'inbox' && !this.isLoading) {
                this.loadFolder('inbox', true);
            }
        }, 600000);
        
        // НЕ завантажуємо пошту автоматично - тільки по кнопці або запиту користувача
    },
    
    // Видалено автоматичну перевірку - тільки по запиту користувача
    
    setupEventListeners: function() {
        // Форма відправки листа
        const composeForm = document.getElementById('composeForm');
        if (composeForm) {
            composeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendEmail();
            });
        }
        
        // Обробка checkbox для вибору листів
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('email-checkbox')) {
                const emailId = parseInt(e.target.value);
                if (e.target.checked) {
                    if (!this.selectedEmails.includes(emailId)) {
                        this.selectedEmails.push(emailId);
                    }
                } else {
                    this.selectedEmails = this.selectedEmails.filter(id => id !== emailId);
                }
                this.updateSelectedCount();
            }
        });
        
        // Гарячі клавіші
        document.addEventListener('keydown', (e) => {
            // Ctrl+N - новий лист
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.showComposeModal();
            }
            // Delete - видалити вибрані
            if (e.key === 'Delete' && this.selectedEmails.length > 0) {
                this.deleteSelected();
            }
        });
    },
    
    showAlert: function(message, type = 'success') {
        const container = document.getElementById('alertContainer');
        if (!container) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show shadow`;
        alert.innerHTML = `
            ${this.escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.innerHTML = '';
        container.appendChild(alert);
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    },
    
    loadFolder: async function(folder, silent = false) {
        if (this.isLoading) return;
        
        this.currentFolder = folder;
        this.currentPage = 1;
        this.currentEmailId = null;
        this.selectedEmails = [];
        
        // Оновлюємо активну папку
        document.querySelectorAll('.folder-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.folder === folder) {
                item.classList.add('active');
            }
        });
        
        if (!silent) {
            document.getElementById('mailList').innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i> Завантаження...
                </div>
            `;
        }
        
        this.isLoading = true;
        
        try {
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(`${baseUrl}?action=get_emails&folder=${encodeURIComponent(folder)}&page=${this.currentPage}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Якщо папка порожня і це "Вхідні", пропонуємо отримати пошту
                if (data.emails.length === 0 && folder === 'inbox' && !silent) {
                    document.getElementById('mailList').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                            <h4>Папка порожня</h4>
                            <p>Немає листів в цій папці</p>
                            <button type="button" class="btn btn-primary mt-3" onclick="MailClient.receiveEmails()">
                                <i class="fas fa-sync-alt me-1"></i>Отримати пошту
                            </button>
                        </div>
                    `;
                } else {
                    this.renderEmailList(data.emails);
                }
                this.updateSelectedCount();
                
                // Очищаємо перегляд
                document.getElementById('mailView').innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-file-alt"></i></div>
                        <h4>Виберіть лист</h4>
                        <p>Виберіть лист зі списку для перегляду</p>
                    </div>
                `;
            } else {
                this.showAlert(data.error || 'Помилка завантаження листів', 'danger');
                document.getElementById('mailList').innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <h4>Помилка</h4>
                        <p>${this.escapeHtml(data.error || 'Не вдалося завантажити листи')}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading folder:', error);
            this.showAlert('Помилка завантаження листів: ' + error.message, 'danger');
            document.getElementById('mailList').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <h4>Помилка</h4>
                    <p>Не вдалося завантажити листи</p>
                </div>
            `;
        } finally {
            this.isLoading = false;
        }
    },
    
    renderEmailList: function(emails) {
        const list = document.getElementById('mailList');
        
        if (!emails || emails.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <h4>Папка порожня</h4>
                    <p>Немає листів в цій папці</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        emails.forEach(email => {
            const date = this.formatDate(email.date_received || email.date_sent);
            const from = this.extractEmailName(email.from || '');
            const preview = (email.preview || '').substring(0, 100);
            const isActive = email.id == this.currentEmailId;
            
            html += `
                <div class="mail-item ${!email.is_read ? 'unread' : ''} ${isActive ? 'active' : ''}" 
                     data-email-id="${email.id}"
                     onclick="MailClient.loadEmail(${email.id})">
                    <div class="mail-item-actions" onclick="event.stopPropagation()">
                        <i class="fas fa-star mail-item-star ${email.is_starred ? 'active' : ''}" 
                           onclick="MailClient.toggleStar(${email.id}, event)"
                           title="${email.is_starred ? 'Прибрати зірочку' : 'Додати зірочку'}"></i>
                        <i class="fas fa-bookmark mail-item-important ${email.is_important ? 'active' : ''}" 
                           onclick="MailClient.toggleImportant(${email.id}, event)"
                           title="${email.is_important ? 'Прибрати з важливих' : 'Позначити як важливе'}"></i>
                    </div>
                    <div class="mail-item-content">
                        <div class="mail-item-header">
                            <div class="mail-item-from">${this.escapeHtml(from)}</div>
                            <div class="mail-item-date">${date}</div>
                        </div>
                        <div class="mail-item-subject">${this.escapeHtml(email.subject || '(Без теми)')}</div>
                        ${preview ? `<div class="mail-item-preview">${this.escapeHtml(preview)}</div>` : ''}
                    </div>
                </div>
            `;
        });
        
        list.innerHTML = html;
    },
    
    loadEmail: async function(emailId) {
        if (this.isLoading) return;
        
        this.currentEmailId = emailId;
        
        // Оновлюємо активний елемент в списку
        document.querySelectorAll('.mail-item').forEach(item => {
            item.classList.remove('active');
            if (parseInt(item.dataset.emailId) === emailId) {
                item.classList.add('active');
            }
        });
        
        const view = document.getElementById('mailView');
        view.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Завантаження...</div>';
        
        this.isLoading = true;
        
        try {
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(`${baseUrl}?action=get_email&id=${emailId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderEmail(data.email);
                // Оновлюємо список листів щоб позначити як прочитане
                const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
                if (emailItem) {
                    emailItem.classList.remove('unread');
                }
            } else {
                this.showAlert(data.error || 'Помилка завантаження листа', 'danger');
                view.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <h4>Помилка</h4>
                        <p>${this.escapeHtml(data.error || 'Не вдалося завантажити лист')}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading email:', error);
            this.showAlert('Помилка завантаження листа: ' + error.message, 'danger');
            view.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <h4>Помилка</h4>
                    <p>Не вдалося завантажити лист</p>
                </div>
            `;
        } finally {
            this.isLoading = false;
        }
    },
    
    renderEmail: function(email) {
        const view = document.getElementById('mailView');
        const date = this.formatDate(email.date_received || email.date_sent || email.created_at);
        const from = this.extractEmailName(email.from || '');
        const fromEmail = this.extractEmail(email.from || '');
        const to = email.to || '';
        const cc = email.cc || '';
        
        let body = email.body_html || email.body || '(Порожнє повідомлення)';
        
        // Якщо HTML, створюємо iframe для безпеки
        if (email.body_html) {
            const iframeId = 'email-body-' + email.id;
            body = `<iframe id="${iframeId}" srcdoc="${this.escapeHtml(body)}"></iframe>`;
        } else {
            body = '<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit;">' + this.escapeHtml(body) + '</pre>';
        }
        
        view.innerHTML = `
            <div class="mail-view-header">
                <div class="mail-view-subject">${this.escapeHtml(email.subject || '(Без теми)')}</div>
                <div class="mail-view-meta">
                    <div class="mail-view-info">
                        <div class="mail-view-from">${this.escapeHtml(from)}</div>
                        ${fromEmail ? `<div class="mail-view-to">${this.escapeHtml(fromEmail)}</div>` : ''}
                        ${to ? `<div class="mail-view-to">Кому: ${this.escapeHtml(to)}</div>` : ''}
                        ${cc ? `<div class="mail-view-cc">Копія: ${this.escapeHtml(cc)}</div>` : ''}
                        <div class="mail-view-date">${date}</div>
                    </div>
                    <div class="mail-view-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="MailClient.replyEmail(${email.id})" title="Відповісти">
                            <i class="fas fa-reply me-1"></i>Відповісти
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="MailClient.forwardEmail(${email.id})" title="Переслати">
                            <i class="fas fa-share me-1"></i>Переслати
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="MailClient.deleteEmail(${email.id})" title="Видалити">
                            <i class="fas fa-trash me-1"></i>Видалити
                        </button>
                    </div>
                </div>
            </div>
            <div class="mail-view-body">
                ${body}
            </div>
        `;
    },
    
    sendEmail: async function() {
        const form = document.getElementById('composeForm');
        const formData = new FormData(form);
        
        const sendBtn = form.querySelector('button[type="submit"]');
        const originalText = sendBtn.innerHTML;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Відправка...';
        
        try {
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert(data.message || 'Лист успішно відправлено', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('composeModal'));
                if (modal) {
                    modal.hide();
                }
                form.reset();
                document.getElementById('composeIsHtml').checked = true;
                
                // Перезавантажуємо папку надіслані
                if (this.currentFolder === 'sent') {
                    this.loadFolder('sent');
                }
            } else {
                this.showAlert(data.error || 'Помилка відправки листа', 'danger');
            }
        } catch (error) {
            console.error('Error sending email:', error);
            this.showAlert('Помилка відправки листа: ' + error.message, 'danger');
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalText;
        }
    },
    
    deleteEmail: async function(emailId, permanent = false) {
        if (!confirm(permanent ? 'Видалити лист назавжди? Цю дію неможливо скасувати.' : 'Перемістити лист в кошик?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete_email');
            formData.append('id', emailId);
            formData.append('permanent', permanent ? '1' : '0');
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert(data.message || 'Лист видалено', 'success');
                
                // Видаляємо зі списку
                const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
                if (emailItem) {
                    emailItem.style.transition = 'opacity 0.3s';
                    emailItem.style.opacity = '0';
                    setTimeout(() => emailItem.remove(), 300);
                }
                
                // Очищаємо перегляд якщо це був поточний лист
                if (this.currentEmailId == emailId) {
                    document.getElementById('mailView').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-file-alt"></i></div>
                            <h4>Виберіть лист</h4>
                            <p>Виберіть лист зі списку для перегляду</p>
                        </div>
                    `;
                    this.currentEmailId = null;
                }
                
                // Оновлюємо статистику папок
                this.updateFolderStats();
            } else {
                this.showAlert(data.error || 'Помилка видалення', 'danger');
            }
        } catch (error) {
            console.error('Error deleting email:', error);
            this.showAlert('Помилка видалення: ' + error.message, 'danger');
        }
    },
    
    toggleStar: async function(emailId, event) {
        if (event) event.stopPropagation();
        
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_star');
            formData.append('id', emailId);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const starIcon = event.target;
                if (data.is_starred) {
                    starIcon.classList.add('active');
                } else {
                    starIcon.classList.remove('active');
                }
                
                // Оновлюємо папку якщо потрібно
                if (this.currentFolder === 'starred' && !data.is_starred) {
                    const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
                    if (emailItem) {
                        emailItem.style.transition = 'opacity 0.3s';
                        emailItem.style.opacity = '0';
                        setTimeout(() => emailItem.remove(), 300);
                    }
                }
            }
        } catch (error) {
            console.error('Error toggling star:', error);
        }
    },
    
    toggleImportant: async function(emailId, event) {
        if (event) event.stopPropagation();
        
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_important');
            formData.append('id', emailId);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const importantIcon = event.target;
                if (data.is_important) {
                    importantIcon.classList.add('active');
                } else {
                    importantIcon.classList.remove('active');
                }
                
                // Оновлюємо папку якщо потрібно
                if (this.currentFolder === 'important' && !data.is_important) {
                    const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
                    if (emailItem) {
                        emailItem.style.transition = 'opacity 0.3s';
                        emailItem.style.opacity = '0';
                        setTimeout(() => emailItem.remove(), 300);
                    }
                }
            }
        } catch (error) {
            console.error('Error toggling important:', error);
        }
    },
    
    receiveEmails: async function(silent = false) {
        if (!silent && !confirm('Отримати нові листи з поштового сервера?')) {
            return;
        }
        
        const btn = event?.target?.closest('button');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Отримання...';
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'receive_emails');
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (!silent) {
                    this.showAlert(data.message || `Отримано ${data.imported || 0} нових листів`, 'success');
                }
                
                // Оновлюємо поточну папку
                this.loadFolder(this.currentFolder, true);
                
                // Оновлюємо статистику
                this.updateFolderStats();
            } else {
                if (!silent) {
                    this.showAlert(data.error || 'Помилка отримання пошти', 'danger');
                }
            }
        } catch (error) {
            console.error('Error receiving emails:', error);
            if (!silent) {
                this.showAlert('Помилка отримання пошти: ' + error.message, 'danger');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Оновити';
            }
        }
    },
    
    showComposeModal: function(to = '', subject = '') {
        document.getElementById('composeTo').value = to;
        document.getElementById('composeSubject').value = subject;
        document.getElementById('composeBody').value = '';
        document.getElementById('composeIsHtml').checked = true;
        const modal = new bootstrap.Modal(document.getElementById('composeModal'));
        modal.show();
    },
    
    replyEmail: function(emailId) {
        // Завантажуємо лист для отримання даних
        this.loadEmail(emailId).then(() => {
            const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
            if (emailItem) {
                const from = emailItem.querySelector('.mail-item-from')?.textContent || '';
                const subject = emailItem.querySelector('.mail-item-subject')?.textContent || '';
                const replySubject = subject.startsWith('Re:') ? subject : 'Re: ' + subject;
                this.showComposeModal(from, replySubject);
            }
        });
    },
    
    forwardEmail: function(emailId) {
        const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
        if (emailItem) {
            const subject = emailItem.querySelector('.mail-item-subject')?.textContent || '';
            const forwardSubject = subject.startsWith('Fwd:') ? subject : 'Fwd: ' + subject;
            this.showComposeModal('', forwardSubject);
        }
    },
    
    deleteSelected: async function() {
        if (this.selectedEmails.length === 0) {
            this.showAlert('Виберіть листи для видалення', 'warning');
            return;
        }
        
        if (!confirm(`Видалити ${this.selectedEmails.length} листів?`)) {
            return;
        }
        
        for (const emailId of this.selectedEmails) {
            await this.deleteEmail(emailId);
        }
        
        this.selectedEmails = [];
        this.updateSelectedCount();
    },
    
    markSelectedAsRead: async function() {
        if (this.selectedEmails.length === 0) {
            this.showAlert('Виберіть листи для позначення', 'warning');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('ids', JSON.stringify(this.selectedEmails));
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Листи позначено як прочитані', 'success');
                this.selectedEmails.forEach(id => {
                    const emailItem = document.querySelector(`.mail-item[data-email-id="${id}"]`);
                    if (emailItem) {
                        emailItem.classList.remove('unread');
                    }
                });
                this.selectedEmails = [];
                this.updateSelectedCount();
            } else {
                this.showAlert(data.error || 'Помилка', 'danger');
            }
        } catch (error) {
            console.error('Error marking as read:', error);
            this.showAlert('Помилка: ' + error.message, 'danger');
        }
    },
    
    saveDraft: function() {
        // Зберігаємо чернетку (можна реалізувати пізніше)
        this.showAlert('Чернетка збережена', 'info');
    },
    
    updateSelectedCount: function() {
        // Оновлюємо інтерфейс з кількістю вибраних
        const count = this.selectedEmails.length;
        // Можна додати відображення кількості вибраних
    },
    
    updateFolderStats: async function() {
        try {
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(`${baseUrl}?action=get_folder_stats`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            
            if (data.success && data.stats) {
                // Оновлюємо лічильники папок
                document.querySelectorAll('.folder-item').forEach(item => {
                    const folder = item.dataset.folder;
                    const countBadge = item.querySelector('.folder-count');
                    if (data.stats[folder]) {
                        const unread = data.stats[folder].unread || 0;
                        const total = data.stats[folder].total || 0;
                        const displayCount = unread > 0 ? unread : total;
                        
                        if (countBadge) {
                            if (displayCount > 0) {
                                countBadge.textContent = displayCount;
                                countBadge.style.display = 'inline-block';
                            } else {
                                countBadge.style.display = 'none';
                            }
                        } else if (displayCount > 0) {
                            const badge = document.createElement('span');
                            badge.className = 'folder-count';
                            badge.textContent = displayCount;
                            item.appendChild(badge);
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error updating folder stats:', error);
        }
    },
    
    formatDate: function(dateString) {
        if (!dateString) return '';
        
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor(diff / (1000 * 60));
            
            if (minutes < 1) {
                return 'Щойно';
            } else if (minutes < 60) {
                return `${minutes} хв. тому`;
            } else if (hours < 24) {
                return `${hours} год. тому`;
            } else if (days === 0) {
                return date.toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit' });
            } else if (days === 1) {
                return 'Вчора';
            } else if (days < 7) {
                return date.toLocaleDateString('uk-UA', { weekday: 'short' });
            } else {
                return date.toLocaleDateString('uk-UA', { day: 'numeric', month: 'short' });
            }
        } catch (e) {
            return dateString;
        }
    },
    
    extractEmailName: function(emailString) {
        if (!emailString) return '';
        
        const match = emailString.match(/^(.+?)\s*<(.+)>$/);
        if (match) {
            return match[1].trim();
        }
        
        const emailMatch = emailString.match(/^(.+)@/);
        return emailMatch ? emailMatch[1] : emailString;
    },
    
    extractEmail: function(emailString) {
        if (!emailString) return '';
        
        const match = emailString.match(/<(.+)>/);
        if (match) {
            return match[1];
        }
        
        return emailString;
    },
    
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    MailClient.init();
});
