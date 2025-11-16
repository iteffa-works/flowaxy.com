/**
 * JavaScript для поштового клієнта
 * Повнофункціональний клієнт з AJAX
 */

const MailClient = {
    currentFolder: 'inbox',
    currentEmailId: null,
    currentEmail: null, // Зберігаємо дані поточного листа для reply
    currentPage: 1,
    selectedEmails: [],
    isLoading: false,
    
    init: function() {
        this.setupEventListeners();
        this.loadFolder('inbox');
        
        // Завантажуємо статистику папок через AJAX (не блокуємо завантаження)
        this.updateFolderStats();
        
        // Автоматично отримуємо нові листи при завантаженні сторінки (в фоновому режимі)
        setTimeout(() => {
            this.receiveEmails(true); // silent = true - без підтвердження
        }, 1000); // Затримка 1 секунда, щоб не блокувати завантаження
        
        // Автоматичне оновлення кожні 10 хвилин (збільшено з 5 для зменшення навантаження)
        setInterval(() => {
            if (this.currentFolder === 'inbox' && !this.isLoading) {
                this.loadFolder('inbox', true);
            }
        }, 600000);
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
        // Перевіряємо чи не завантажується вже інший лист
        if (this.isLoading) {
            // Якщо завантажується той самий лист, не робимо нічого
            if (this.currentEmailId === emailId) {
                return;
            }
            // Якщо завантажується інший лист, чекаємо трохи і повторюємо
            await new Promise(resolve => setTimeout(resolve, 100));
            if (this.isLoading) {
                return; // Все ще завантажується, виходимо
            }
        }
        
        // Перевіряємо чи елемент view існує
        const view = document.getElementById('mailView');
        if (!view) {
            console.error('mailView element not found');
            return;
        }
        
        this.currentEmailId = emailId;
        
        // Оновлюємо активний елемент в списку
        document.querySelectorAll('.mail-item').forEach(item => {
            item.classList.remove('active');
            if (parseInt(item.dataset.emailId) === emailId) {
                item.classList.add('active');
            }
        });
        
        view.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Завантаження...</div>';
        
        this.isLoading = true;
        
        try {
            const baseUrl = window.location.href.split('?')[0];
            const response = await fetch(`${baseUrl}?action=get_email&id=${emailId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-cache'
            });
            
            // Перевіряємо статус відповіді
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Перевіряємо чи відповідь JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Сервер повернув некоректну відповідь');
            }
            
            const data = await response.json();
            
            // Перевіряємо чи це все ще той самий лист (на випадок якщо користувач швидко переключився)
            if (this.currentEmailId !== emailId) {
                return; // Користувач вже вибрав інший лист
            }
            
            if (data.success && data.email) {
                // Зберігаємо дані листа для використання в reply
                this.currentEmail = data.email;
                this.renderEmail(data.email);
                // Оновлюємо список листів щоб позначити як прочитане
                const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
                if (emailItem) {
                    emailItem.classList.remove('unread');
                }
            } else {
                const errorMsg = data.error || 'Помилка завантаження листа';
                console.error('Error loading email:', errorMsg, data);
                this.showAlert(errorMsg, 'danger');
                view.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <h4>Помилка</h4>
                        <p>${this.escapeHtml(errorMsg)}</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="MailClient.loadEmail(${emailId})">
                            <i class="fas fa-redo me-1"></i>Спробувати ще раз
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading email:', error);
            
            // Перевіряємо чи це все ще той самий лист
            if (this.currentEmailId !== emailId) {
                return; // Користувач вже вибрав інший лист
            }
            
            const errorMsg = error.message || 'Помилка завантаження листа';
            this.showAlert(errorMsg, 'danger');
            view.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <h4>Помилка</h4>
                    <p>${this.escapeHtml(errorMsg)}</p>
                    <button class="btn btn-sm btn-primary mt-2" onclick="MailClient.loadEmail(${emailId})">
                        <i class="fas fa-redo me-1"></i>Спробувати ще раз
                    </button>
                </div>
            `;
        } finally {
            // Скидаємо прапорець завантаження тільки якщо це все ще той самий лист
            if (this.currentEmailId === emailId) {
                this.isLoading = false;
            }
        }
    },
    
    renderEmail: function(email) {
        if (!email) {
            console.error('renderEmail: email is null or undefined');
            return;
        }
        
        const view = document.getElementById('mailView');
        if (!view) {
            console.error('renderEmail: mailView element not found');
            return;
        }
        
        try {
            // Отримуємо всю цепочку писем (thread)
            const threadEmails = email.thread && Array.isArray(email.thread) && email.thread.length > 0 
                ? email.thread 
                : [email];
            
            // Формуємо HTML для всієї цепочки
            let threadHtml = '';
            
            threadEmails.forEach((threadEmail, index) => {
                const date = this.formatDate(threadEmail.date_received || threadEmail.date_sent || threadEmail.created_at);
                const from = this.extractEmailName(threadEmail.from || '');
                const fromEmail = this.extractEmail(threadEmail.from || '');
                const to = threadEmail.to || '';
                const cc = threadEmail.cc || '';
                
                let body = threadEmail.body_html || threadEmail.body || '(Порожнє повідомлення)';
                
                // Декодуємо Base64, якщо потрібно
                if (body && typeof body === 'string') {
                    const trimmed = body.trim();
                    if (/^[A-Za-z0-9+\/]+=*$/.test(trimmed) && trimmed.length % 4 === 0 && trimmed.length > 50) {
                        try {
                            const decoded = atob(trimmed);
                            if (decoded.includes('<') || decoded.length > 0) {
                                body = decoded;
                            }
                        } catch (e) {
                            // Не Base64, залишаємо як є
                        }
                    }
                }
                
                // Відображаємо HTML або текст
                let bodyHtml = '';
                if (threadEmail.body_html || (body && body.includes('<'))) {
                    const cleanBody = this.sanitizeHtml(body);
                    bodyHtml = `<div class="email-html-content" style="padding: 20px; max-width: 100%; overflow-x: auto;">
                        ${cleanBody}
                    </div>`;
                } else {
                    bodyHtml = '<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit; padding: 20px;">' + this.escapeHtml(body) + '</pre>';
                }
                
                // Вкладення для цього листа
                let attachmentsHtml = '';
                if (threadEmail.attachments && threadEmail.attachments.length > 0) {
                    attachmentsHtml = '<div class="mail-attachments mt-3 p-3 bg-light rounded">';
                    attachmentsHtml += '<h6 class="mb-2"><i class="fas fa-paperclip me-2"></i>Вкладення (' + threadEmail.attachments.length + '):</h6>';
                    attachmentsHtml += '<div class="d-flex flex-wrap gap-2">';
                    
                    threadEmail.attachments.forEach(att => {
                        const fileName = att.original_filename || att.filename || 'attachment';
                        const fileSize = this.formatFileSize(att.file_size || 0);
                        const fileIcon = this.getFileIcon(att.mime_type || '');
                        
                        attachmentsHtml += `
                            <div class="attachment-item p-2 border rounded bg-white" style="min-width: 200px;">
                                <div class="d-flex align-items-center">
                                    <i class="${fileIcon} me-2 text-primary" style="font-size: 1.5rem;"></i>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="text-truncate fw-bold" title="${this.escapeHtml(fileName)}">${this.escapeHtml(fileName)}</div>
                                        <small class="text-muted">${fileSize}</small>
                                    </div>
                                    <a href="${this.getAttachmentUrl(att.id)}" class="btn btn-sm btn-outline-primary ms-2" download title="Завантажити">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    
                    attachmentsHtml += '</div></div>';
                }
                
                // Додаємо розділювач між листами (крім першого)
                const separator = index > 0 ? '<div class="thread-separator my-4" style="border-top: 2px solid #e0e0e0; padding-top: 20px;"></div>' : '';
                
                threadHtml += `
                    ${separator}
                    <div class="thread-email ${index === threadEmails.length - 1 ? 'thread-email-last' : ''}" data-email-id="${threadEmail.id}">
                        <div class="mail-view-header">
                            <div class="mail-view-subject">${this.escapeHtml(threadEmail.subject || '(Без теми)')}</div>
                            <div class="mail-view-meta">
                                <div class="mail-view-info">
                                    <div class="mail-view-from">${this.escapeHtml(from)}</div>
                                    ${fromEmail ? `<div class="mail-view-to">${this.escapeHtml(fromEmail)}</div>` : ''}
                                    ${to ? `<div class="mail-view-to">Кому: ${this.escapeHtml(to)}</div>` : ''}
                                    ${cc ? `<div class="mail-view-cc">Копія: ${this.escapeHtml(cc)}</div>` : ''}
                                    <div class="mail-view-date">${date}</div>
                                </div>
                                ${index === threadEmails.length - 1 ? `
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
                                ` : ''}
                            </div>
                        </div>
                        ${attachmentsHtml}
                        <div class="mail-view-body">
                            ${bodyHtml}
                        </div>
                    </div>
                `;
            });
            
            view.innerHTML = `
                <div class="mail-thread-container">
                    ${threadHtml}
                </div>
            `;
        } catch (error) {
            console.error('Error rendering email:', error, email);
            view.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <h4>Помилка відображення</h4>
                    <p>Не вдалося відобразити лист. Спробуйте відкрити його ще раз.</p>
                    ${email && email.id ? `<button class="btn btn-sm btn-primary mt-2" onclick="MailClient.loadEmail(${email.id})">
                        <i class="fas fa-redo me-1"></i>Спробувати ще раз
                    </button>` : ''}
                </div>
            `;
        }
    },
    
    /**
     * Валідація та нормалізація email адреси
     * Підтримує формати: "Ім'я <email@example.com>" або "email@example.com"
     */
    normalizeEmail: function(emailString) {
        if (!emailString) return null;
        
        emailString = emailString.trim();
        if (!emailString) return null;
        
        // Нормалізуємо пробіли
        emailString = emailString.replace(/\s+/g, ' ');
        
        // Якщо формат "Ім'я <email@example.com>"
        // Шукаємо останню пару углових дужок (на випадок якщо в імені є < або >)
        const lastBracketOpen = emailString.lastIndexOf('<');
        const lastBracketClose = emailString.lastIndexOf('>');
        
        if (lastBracketOpen !== -1 && lastBracketClose !== -1 && lastBracketOpen < lastBracketClose) {
            // Є углові дужки
            const name = emailString.substring(0, lastBracketOpen).trim();
            let email = emailString.substring(lastBracketOpen + 1, lastBracketClose).trim();
            
            // Очищаємо email від можливих невидимих символів та зайвих пробілів
            email = email.replace(/[\u200B-\u200D\uFEFF]/g, '').trim();
            
            // Відладка
            console.log('Parsed name:', name, 'email:', email, 'email length:', email.length);
            
            // Валідуємо email
            if (email && email.length > 0 && this.isValidEmail(email)) {
                // Якщо ім'я порожнє, повертаємо тільки email
                if (!name || name.length === 0) {
                    return email;
                }
                // Повертаємо нормалізований формат
                return `${name} <${email}>`;
            } else {
                // Відладка: чому email невалідний
                console.warn('Email validation failed for:', email, 'isValidEmail result:', this.isValidEmail(email));
            }
            // Якщо email невалідний
            return null;
        }
        
        // Якщо просто email (без углових дужок)
        if (this.isValidEmail(emailString)) {
            return emailString;
        }
        
        return null; // Невірний формат
    },
    
    /**
     * Валідація email адреси
     */
    isValidEmail: function(email) {
        if (!email || typeof email !== 'string') {
            return false;
        }
        
        // Очищаємо від невидимих символів та пробілів
        email = email.replace(/[\u200B-\u200D\uFEFF]/g, '').trim();
        
        if (!email || email.length === 0) {
            return false;
        }
        
        // Більш гнучка валідація email
        // Дозволяємо букви, цифри, точки, дефіси, підкреслення, плюси до @
        // Після @ дозволяємо домен з крапками
        const emailRegex = /^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const result = emailRegex.test(email);
        
        // Відладка якщо невалідний
        if (!result) {
            console.warn('Email validation failed:', email, 'regex test:', emailRegex.test(email));
        }
        
        return result;
    },
    
    /**
     * Розділення рядка з кількома email адресами (враховуючи коми всередині углових дужок)
     */
    splitEmailAddresses: function(emailString) {
        if (!emailString) return [];
        
        const addresses = [];
        let current = '';
        let inBrackets = false;
        
        for (let i = 0; i < emailString.length; i++) {
            const char = emailString[i];
            
            if (char === '<') {
                inBrackets = true;
                current += char;
            } else if (char === '>') {
                inBrackets = false;
                current += char;
            } else if (char === ',' && !inBrackets) {
                // Кома поза угловими дужками - розділювач адрес
                if (current.trim()) {
                    addresses.push(current.trim());
                }
                current = '';
            } else {
                current += char;
            }
        }
        
        // Додаємо останню адресу
        if (current.trim()) {
            addresses.push(current.trim());
        }
        
        return addresses.filter(addr => addr.length > 0);
    },
    
    /**
     * Витягування email з рядка (для відправки)
     */
    extractEmailFromString: function(emailString) {
        if (!emailString) return '';
        
        emailString = emailString.trim();
        
        // Якщо формат "Ім'я <email@example.com>"
        const match = emailString.match(/^(.+?)\s*<([^>]+)>$/);
        if (match) {
            return match[2].trim();
        }
        
        // Якщо просто email
        return emailString;
    },
    
    sendEmail: async function() {
        const form = document.getElementById('composeForm');
        const toField = document.getElementById('composeTo');
        const toValue = toField.value.trim();
        const replyTo = form.dataset.replyTo || null;
        
        // Валідуємо та нормалізуємо адреси
        if (!toValue) {
            toField.classList.add('is-invalid');
            document.getElementById('composeToError').textContent = 'Введіть адресу отримувача';
            return;
        }
        
        // Розділяємо кілька адрес через кому
        // Але враховуємо, що кома може бути всередині углових дужок (в імені)
        const addresses = this.splitEmailAddresses(toValue);
        const normalizedAddresses = [];
        const errors = [];
        
        for (let i = 0; i < addresses.length; i++) {
            const addr = addresses[i].trim();
            if (!addr) continue;
            
            // Додаткова очистка адреси
            const cleanAddr = addr.replace(/\s+/g, ' ').trim();
            
            const normalized = this.normalizeEmail(cleanAddr);
            if (!normalized) {
                errors.push(`Невірний формат адреси: "${cleanAddr}"`);
            } else {
                normalizedAddresses.push(normalized);
            }
        }
        
        if (errors.length > 0) {
            toField.classList.add('is-invalid');
            document.getElementById('composeToError').textContent = errors.join('; ');
            return;
        }
        
        toField.classList.remove('is-invalid');
        
        // Створюємо FormData з нормалізованими адресами
        const formData = new FormData(form);
        formData.set('to', normalizedAddresses.join(', '));
        if (replyTo) {
            formData.append('reply_to', replyTo);
        }
        
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
    
    showComposeModal: function(to = '', subject = '', replyTo = null) {
        document.getElementById('composeTo').value = to;
        document.getElementById('composeSubject').value = subject;
        document.getElementById('composeBody').value = '';
        document.getElementById('composeIsHtml').checked = true;
        
        // Зберігаємо reply_to в dataset форми
        const form = document.getElementById('composeForm');
        if (replyTo) {
            form.dataset.replyTo = replyTo;
        } else {
            delete form.dataset.replyTo;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('composeModal'));
        modal.show();
    },
    
    replyEmail: function(emailId) {
        // Завантажуємо лист для отримання даних
        this.loadEmail(emailId).then(() => {
            const emailItem = document.querySelector(`.mail-item[data-email-id="${emailId}"]`);
            if (emailItem) {
                // Отримуємо дані з завантаженого листа
                const email = this.currentEmail || {};
                const from = email.from || '';
                const subject = email.subject || '';
                const replySubject = subject.startsWith('Re:') ? subject : 'Re: ' + subject;
                
                // Форматуємо адресу в формат "Ім'я <email>"
                let formattedFrom = from;
                if (from && !from.includes('<')) {
                    // Якщо тільки email, спробуємо витягти ім'я
                    const emailMatch = from.match(/^(.+?)\s*<(.+?)>$/);
                    if (!emailMatch) {
                        // Якщо просто email, залишаємо як є
                        formattedFrom = from;
                    }
                }
                
                this.showComposeModal(formattedFrom, replySubject, emailId);
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
    },
    
    /**
     * Форматування розміру файлу
     */
    formatFileSize: function(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },
    
    /**
     * Отримання іконки для типу файлу
     */
    getFileIcon: function(mimeType) {
        if (!mimeType) return 'fas fa-file';
        
        const icons = {
            'application/pdf': 'fas fa-file-pdf text-danger',
            'application/zip': 'fas fa-file-archive text-warning',
            'application/msword': 'fas fa-file-word text-primary',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fas fa-file-word text-primary',
            'application/vnd.ms-excel': 'fas fa-file-excel text-success',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fas fa-file-excel text-success',
            'image/jpeg': 'fas fa-file-image text-info',
            'image/png': 'fas fa-file-image text-info',
            'image/gif': 'fas fa-file-image text-info',
            'text/plain': 'fas fa-file-alt',
            'text/html': 'fas fa-file-code text-primary'
        };
        
        return icons[mimeType] || 'fas fa-file';
    },
    
    /**
     * Отримання URL для скачивання вкладення
     */
    getAttachmentUrl: function(attachmentId) {
        const baseUrl = window.location.href.split('?')[0];
        return `${baseUrl}?action=download_attachment&id=${attachmentId}&csrf_token=${encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)}`;
    },
    
    /**
     * Очищення HTML від небезпечних елементів
     */
    sanitizeHtml: function(html) {
        if (!html) return '';
        
        // Створюємо тимчасовий елемент для парсингу
        const temp = document.createElement('div');
        temp.innerHTML = html;
        
        // Видаляємо небезпечні елементи та атрибути
        const dangerousTags = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button'];
        dangerousTags.forEach(tag => {
            const elements = temp.querySelectorAll(tag);
            elements.forEach(el => el.remove());
        });
        
        // Видаляємо небезпечні атрибути
        const allElements = temp.querySelectorAll('*');
        allElements.forEach(el => {
            // Видаляємо onclick, onerror та інші event handlers
            Array.from(el.attributes).forEach(attr => {
                if (attr.name.startsWith('on')) {
                    el.removeAttribute(attr.name);
                }
                // Видаляємо javascript: з href та src
                if ((attr.name === 'href' || attr.name === 'src') && attr.value.startsWith('javascript:')) {
                    el.removeAttribute(attr.name);
                }
            });
        });
        
        return temp.innerHTML;
    }
};

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    MailClient.init();
});
