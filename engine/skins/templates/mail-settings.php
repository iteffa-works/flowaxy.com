<?php
/**
 * Шаблон страницы настроек почты
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" id="mailSettingsForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="save_mail_settings" value="1">
    
    <div class="row">
        <!-- SMTP настройки -->
        <div class="col-lg-12 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-paper-plane me-2"></i>SMTP настройки (отправка почты)</span>
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="smtpHost" class="form-label">SMTP сервер</label>
                                <input type="text" class="form-control" id="smtpHost" 
                                       name="settings[mail_smtp_host]" 
                                       value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>"
                                       placeholder="smtp.example.com">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="smtpPort" class="form-label">Порт SMTP</label>
                                <select class="form-select" id="smtpPort" name="settings[mail_smtp_port]">
                                    <option value="25" <?= ($settings['smtp_port'] ?? '587') === '25' ? 'selected' : '' ?>>25</option>
                                    <option value="465" <?= ($settings['smtp_port'] ?? '587') === '465' ? 'selected' : '' ?>>465 (SSL)</option>
                                    <option value="587" <?= ($settings['smtp_port'] ?? '587') === '587' ? 'selected' : '' ?>>587 (TLS)</option>
                                    <option value="2525" <?= ($settings['smtp_port'] ?? '587') === '2525' ? 'selected' : '' ?>>2525</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="smtpEncryption" class="form-label">Шифрування</label>
                                <select class="form-select" id="smtpEncryption" name="settings[mail_smtp_encryption]">
                                    <option value="none" <?= ($settings['smtp_encryption'] ?? 'tls') === 'none' ? 'selected' : '' ?>>Без шифрування</option>
                                    <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= ($settings['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="smtpUsername" class="form-label">Логин SMTP</label>
                                <input type="text" class="form-control" id="smtpUsername" 
                                       name="settings[mail_smtp_username]" 
                                       value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="smtpPassword" class="form-label">Пароль SMTP</label>
                                <input type="password" class="form-control" id="smtpPassword" 
                                       name="settings[mail_smtp_password]" 
                                       value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary" id="testSmtpBtn">
                            <i class="fas fa-check-circle me-1"></i>Тестувати SMTP
                        </button>
                        <button type="button" class="btn btn-outline-success" id="sendTestEmailBtn">
                            <i class="fas fa-paper-plane me-1"></i>Відправити тестовий email
                        </button>
                    </div>
                    <div id="smtpTestResult" class="alert d-none"></div>
                </div>
            </div>
        </div>
        
        <!-- POP3 настройки -->
        <div class="col-lg-6 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-inbox me-2"></i>POP3 настройки (получение почты)</span>
                </div>
                <div class="content-section-body">
                    <div class="mb-3">
                        <label for="pop3Host" class="form-label">POP3 сервер</label>
                        <input type="text" class="form-control" id="pop3Host" 
                               name="settings[mail_pop3_host]" 
                               value="<?= htmlspecialchars($settings['pop3_host'] ?? '') ?>"
                               placeholder="pop3.example.com">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pop3Port" class="form-label">Порт POP3</label>
                                <select class="form-select" id="pop3Port" name="settings[mail_pop3_port]">
                                    <option value="110" <?= ($settings['pop3_port'] ?? '995') === '110' ? 'selected' : '' ?>>110</option>
                                    <option value="995" <?= ($settings['pop3_port'] ?? '995') === '995' ? 'selected' : '' ?>>995 (SSL)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pop3Encryption" class="form-label">Шифрування</label>
                                <select class="form-select" id="pop3Encryption" name="settings[mail_pop3_encryption]">
                                    <option value="none" <?= ($settings['pop3_encryption'] ?? 'ssl') === 'none' ? 'selected' : '' ?>>Без шифрування</option>
                                    <option value="ssl" <?= ($settings['pop3_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pop3Username" class="form-label">Логин POP3</label>
                                <input type="text" class="form-control" id="pop3Username" 
                                       name="settings[mail_pop3_username]" 
                                       value="<?= htmlspecialchars($settings['pop3_username'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pop3Password" class="form-label">Пароль POP3</label>
                                <input type="password" class="form-control" id="pop3Password" 
                                       name="settings[mail_pop3_password]" 
                                       value="<?= htmlspecialchars($settings['pop3_password'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary" id="testPop3Btn">
                            <i class="fas fa-check-circle me-1"></i>Тестувати POP3
                        </button>
                        <button type="button" class="btn btn-outline-info" id="receiveEmailsBtn">
                            <i class="fas fa-download me-1"></i>Отримати пошту
                        </button>
                    </div>
                    <div id="pop3TestResult" class="alert d-none"></div>
                </div>
            </div>
        </div>
        
        <!-- IMAP настройки -->
        <div class="col-lg-6 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-mail-bulk me-2"></i>IMAP настройки (получение почты)</span>
                </div>
                <div class="content-section-body">
                    <div class="mb-3">
                        <label for="imapHost" class="form-label">IMAP сервер</label>
                        <input type="text" class="form-control" id="imapHost" 
                               name="settings[mail_imap_host]" 
                               value="<?= htmlspecialchars($settings['imap_host'] ?? '') ?>"
                               placeholder="imap.example.com">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="imapPort" class="form-label">Порт IMAP</label>
                                <select class="form-select" id="imapPort" name="settings[mail_imap_port]">
                                    <option value="143" <?= ($settings['imap_port'] ?? '993') === '143' ? 'selected' : '' ?>>143</option>
                                    <option value="993" <?= ($settings['imap_port'] ?? '993') === '993' ? 'selected' : '' ?>>993 (SSL)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="imapEncryption" class="form-label">Шифрування</label>
                                <select class="form-select" id="imapEncryption" name="settings[mail_imap_encryption]">
                                    <option value="none" <?= ($settings['imap_encryption'] ?? 'ssl') === 'none' ? 'selected' : '' ?>>Без шифрування</option>
                                    <option value="ssl" <?= ($settings['imap_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="imapUsername" class="form-label">Логин IMAP</label>
                                <input type="text" class="form-control" id="imapUsername" 
                                       name="settings[mail_imap_username]" 
                                       value="<?= htmlspecialchars($settings['imap_username'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="imapPassword" class="form-label">Пароль IMAP</label>
                                <input type="password" class="form-control" id="imapPassword" 
                                       name="settings[mail_imap_password]" 
                                       value="<?= htmlspecialchars($settings['imap_password'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary" id="testImapBtn">
                            <i class="fas fa-check-circle me-1"></i>Тестувати IMAP
                        </button>
                    </div>
                    <div id="imapTestResult" class="alert d-none"></div>
                </div>
            </div>
        </div>
        
        <!-- Додаткові налаштування -->
        <div class="col-lg-12 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-cog me-2"></i>Додаткові налаштування</span>
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fromEmail" class="form-label">Email відправника</label>
                                <input type="email" class="form-control" id="fromEmail" 
                                       name="settings[mail_from_email]" 
                                       value="<?= htmlspecialchars($settings['from_email'] ?? '') ?>"
                                       placeholder="noreply@example.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fromName" class="form-label">Ім'я відправника</label>
                                <input type="text" class="form-control" id="fromName" 
                                       name="settings[mail_from_name]" 
                                       value="<?= htmlspecialchars($settings['from_name'] ?? '') ?>"
                                       placeholder="Flowaxy CMS">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="domainMx" class="form-label">Налаштування домену (MX запис)</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" class="form-control" id="domainMx" 
                                   name="settings[mail_domain_mx]" 
                                   value="<?= htmlspecialchars($settings['domain_mx'] ?? 'mx.services') ?>"
                                   placeholder="mx.services">
                            <span class="input-group-text">MX 0</span>
                        </div>
                        <div class="form-text">Вкажіть MX сервер для вашого домену (наприклад: mx.services)</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Боковая панель -->
        <div class="col-lg-12 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-save me-2"></i>Дії</span>
                </div>
                <div class="content-section-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Зберегти налаштування
                        </button>
                        <a href="mail-settings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Скасувати зміни
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Тестування SMTP
    document.getElementById('testSmtpBtn')?.addEventListener('click', function() {
        const btn = this;
        const resultDiv = document.getElementById('smtpTestResult');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Тестування...';
        resultDiv.classList.add('d-none');
        
        fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_smtp&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger');
            resultDiv.textContent = data.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Тестувати SMTP';
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.textContent = 'Помилка: ' + error.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Тестувати SMTP';
        });
    });
    
    // Тестування POP3
    document.getElementById('testPop3Btn')?.addEventListener('click', function() {
        const btn = this;
        const resultDiv = document.getElementById('pop3TestResult');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Тестування...';
        resultDiv.classList.add('d-none');
        
        fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_pop3&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger');
            resultDiv.textContent = data.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Тестувати POP3';
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.textContent = 'Помилка: ' + error.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Тестувати POP3';
        });
    });
    
    // Тестування IMAP
    document.getElementById('testImapBtn')?.addEventListener('click', function() {
        const btn = this;
        const resultDiv = document.getElementById('imapTestResult');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Тестування...';
        resultDiv.classList.add('d-none');
        
        fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_imap&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger');
            resultDiv.textContent = data.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Тестувати IMAP';
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.textContent = 'Помилка: ' + error.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Тестувати IMAP';
        });
    });
    
    // Відправка тестового email
    document.getElementById('sendTestEmailBtn')?.addEventListener('click', function() {
        const to = prompt('Введіть email отримувача:', '');
        if (!to) return;
        
        const btn = this;
        const resultDiv = document.getElementById('smtpTestResult');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Відправка...';
        resultDiv.classList.add('d-none');
        
        const formData = new URLSearchParams();
        formData.append('action', 'send_test_email');
        formData.append('to', to);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger');
            resultDiv.textContent = data.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Відправити тестовий email';
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.textContent = 'Помилка: ' + error.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Відправити тестовий email';
        });
    });
    
    // Отримання пошти
    document.getElementById('receiveEmailsBtn')?.addEventListener('click', function() {
        const btn = this;
        const resultDiv = document.getElementById('pop3TestResult');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Отримання...';
        resultDiv.classList.add('d-none');
        
        fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=receive_emails&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger');
            if (data.success) {
                let message = 'Отримано листів: ' + (data.emails?.length || 0) + '\n\n';
                if (data.emails && data.emails.length > 0) {
                    data.emails.forEach((email, index) => {
                        message += (index + 1) + '. ' + (email.subject || 'Без теми') + ' від ' + (email.from || 'Невідомий') + '\n';
                    });
                }
                resultDiv.textContent = message;
            } else {
                resultDiv.textContent = data.message || 'Помилка отримання пошти';
            }
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download me-1"></i>Отримати пошту';
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.textContent = 'Помилка: ' + error.message;
            resultDiv.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download me-1"></i>Отримати пошту';
        });
    });
});
</script>

