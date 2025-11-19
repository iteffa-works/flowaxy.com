<?php
$step = $_GET['step'] ?? 'welcome';
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="en" id="installer-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flowaxy CMS Installation</title>
    <link rel="icon" type="image/png" href="/engine/skins/assets/images/brand/favicon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1a202c;
            line-height: 1.5;
        }
        
        .installer-container {
            background: #ffffff;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .installer-header {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            padding: 32px 40px;
            text-align: center;
            color: #ffffff;
            position: relative;
        }
        
        .logo-container {
            margin-bottom: 16px;
        }
        
        .logo-container img {
            height: 48px;
            width: auto;
        }
        
        .installer-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        
        .installer-header p {
            font-size: 14px;
            opacity: 0.85;
            font-weight: 400;
        }
        
        .installer-content {
            padding: 32px 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 32px;
            padding: 0;
        }
        
        .step-item {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
            background: #e2e8f0;
            color: #718096;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .step-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transform: scale(1.1);
        }
        
        .step-item.completed {
            background: #48bb78;
            color: #ffffff;
        }
        
        .step-item::after {
            content: '';
            position: absolute;
            right: -12px;
            width: 16px;
            height: 2px;
            background: #e2e8f0;
            transition: background 0.3s ease;
        }
        
        .step-item:last-child::after {
            display: none;
        }
        
        .step-item.completed::after {
            background: #48bb78;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 40px;
            letter-spacing: 0.3px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-full {
            width: 100%;
        }
        
        .installer-actions {
            margin-top: 24px;
            display: flex;
            gap: 10px;
        }
        
        .installer-actions .btn {
            flex: 1;
        }
        
        .test-result {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            display: none;
            font-weight: 500;
        }
        
        .test-result.success {
            background: #c6f6d5;
            border: 1px solid #9ae6b4;
            color: #22543d;
            display: block;
        }
        
        .test-result.error {
            background: #fed7d7;
            border: 1px solid #fc8181;
            color: #742a2a;
            display: block;
        }
        
        .test-result.testing {
            background: #bee3f8;
            border: 1px solid #90cdf4;
            color: #2c5282;
            display: block;
        }
        
        .progress-container {
            margin: 24px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
            width: 0%;
            transition: width 0.4s ease;
            border-radius: 4px;
        }
        
        .progress-text {
            margin-top: 12px;
            text-align: center;
            font-size: 13px;
            color: #718096;
            font-weight: 500;
        }
        
        .tables-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .tables-list li {
            padding: 10px 14px;
            margin-bottom: 6px;
            background: #f7fafc;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        
        .tables-list li.creating {
            background: #ebf8ff;
            border-left: 3px solid #4299e1;
        }
        
        .tables-list li.success {
            background: #f0fff4;
            border-left: 3px solid #48bb78;
        }
        
        .tables-list li.error {
            background: #fff5f5;
            border-left: 3px solid #f56565;
        }
        
        .table-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }
        
        .table-icon.creating::after {
            content: '⟳';
            animation: spin 0.8s linear infinite;
        }
        
        .table-icon.success::after {
            content: '✓';
            color: #48bb78;
            font-weight: bold;
        }
        
        .table-icon.error::after {
            content: '✗';
            color: #f56565;
            font-weight: bold;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .alert.error {
            background: #fed7d7;
            border: 1px solid #fc8181;
            color: #742a2a;
        }
        
        .welcome-section {
            margin-bottom: 24px;
        }
        
        .welcome-section h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }
        
        .welcome-section p {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .features-list li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
            font-size: 14px;
            color: #4a5568;
        }
        
        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #48bb78;
            font-weight: bold;
            font-size: 16px;
        }
        
        .flowaxy-promo {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6f0ff 100%);
            border-left: 4px solid #667eea;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .flowaxy-promo h3 {
            font-size: 15px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 6px;
        }
        
        .flowaxy-promo p {
            font-size: 13px;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .flowaxy-promo a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        
        .flowaxy-promo a:hover {
            text-decoration: underline;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
        }
        
        @media (max-width: 640px) {
            body {
                padding: 10px;
            }
            
            .installer-header {
                padding: 24px 20px;
            }
            
            .installer-content {
                padding: 24px 20px;
            }
            
            .installer-actions {
                flex-direction: column;
            }
            
            .step-indicator {
                gap: 6px;
            }
            
            .step-item {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            
            .step-item::after {
                width: 12px;
                right: -10px;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <div class="logo-container">
                <img src="/engine/skins/assets/images/brand/favicon.png" alt="Flowaxy CMS" data-i18n="logo.alt">
            </div>
            <h1 data-i18n="header.title">Flowaxy CMS</h1>
            <p data-i18n="header.subtitle">Installation Wizard</p>
        </div>
        
        <div class="installer-content">
            <div class="step-indicator">
                <div class="step-item <?= $step === 'welcome' ? 'active' : 'completed' ?>" data-step="welcome">1</div>
                <div class="step-item <?= $step === 'database' ? 'active' : ($step === 'tables' || $step === 'user' ? 'completed' : '') ?>" data-step="database">2</div>
                <div class="step-item <?= $step === 'tables' ? 'active' : ($step === 'user' ? 'completed' : '') ?>" data-step="tables">3</div>
                <div class="step-item <?= $step === 'user' ? 'active' : '' ?>" data-step="user">4</div>
            </div>
            
            <!-- Step 1: Welcome -->
            <div class="step-content <?= $step === 'welcome' ? 'active' : '' ?>" id="step-welcome">
                <div class="welcome-section">
                    <h2 data-i18n="welcome.title">Welcome!</h2>
                    <p data-i18n="welcome.text1">Flowaxy CMS is a powerful and flexible content management system designed for modern web projects.</p>
                    <p data-i18n="welcome.text2">The system provides quick installation, convenient management and high performance.</p>
                    
                    <ul class="features-list">
                        <li data-i18n="feature1">Modular architecture with plugin support</li>
                        <li data-i18n="feature2">Theme system with customization</li>
                        <li data-i18n="feature3">Security and performance optimization</li>
                        <li data-i18n="feature4">Convenient admin panel</li>
                        <li data-i18n="feature5">Multi-language support</li>
                    </ul>
                </div>
                
                <div class="flowaxy-promo">
                    <h3 data-i18n="promo.title">Need development help?</h3>
                    <p data-i18n="promo.text">Flowaxy is also a web studio that provides website development, integration and support services. Contact us for professional help!</p>
                    <a href="https://flowaxy.com" target="_blank" data-i18n="promo.link">Visit Flowaxy.com →</a>
                </div>
                
                <div class="installer-actions">
                    <a href="/install?step=database" class="btn btn-primary btn-full" data-i18n="button.start">Start Installation</a>
                </div>
            </div>
            
            <!-- Step 2: Database -->
            <div class="step-content <?= $step === 'database' ? 'active' : '' ?>" id="step-database">
                <form id="databaseForm" method="POST" action="/install?step=database">
                    <div class="form-group">
                        <label data-i18n="label.host">Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '127.0.0.1') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.port">Port</label>
                        <input type="number" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.database">Database</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.username">Username</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.password">Password</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                    </div>
                    
                    <div class="test-result" id="testResult"></div>
                    
                    <div class="installer-actions">
                        <button type="button" class="btn btn-secondary" id="testBtn" data-i18n="button.test">Test Connection</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn" data-i18n="button.save" disabled>Save & Continue</button>
                    </div>
                </form>
            </div>
            
            <!-- Step 3: Tables -->
            <div class="step-content <?= $step === 'tables' ? 'active' : '' ?>" id="step-tables">
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText" data-i18n="progress.text">Preparing...</div>
                </div>
                
                <ul class="tables-list" id="tablesList"></ul>
                
                <button class="btn btn-primary btn-full" id="continueBtn" style="display: none;" data-i18n="button.continue" onclick="window.location.href='/install?step=user'">Continue</button>
            </div>
            
            <!-- Step 4: User -->
            <div class="step-content <?= $step === 'user' ? 'active' : '' ?>" id="step-user">
                <?php if ($error): ?>
                    <div class="alert error" data-i18n="error.text"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="/install?step=user" id="userForm">
                    <div class="form-group">
                        <label data-i18n="label.username">Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.email">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.password">Password</label>
                        <input type="password" name="password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label data-i18n="label.password_confirm">Confirm Password</label>
                        <input type="password" name="password_confirm" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full" data-i18n="button.create">Create & Finish</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const translations = {
            uk: {
                'logo.alt': 'Flowaxy CMS',
                'header.title': 'Flowaxy CMS',
                'header.subtitle': 'Майстер установки',
                'welcome.title': 'Ласкаво просимо!',
                'welcome.text1': 'Flowaxy CMS - це потужна та гнучка система управління контентом, створена для сучасних веб-проектів.',
                'welcome.text2': 'Система забезпечує швидку установку, зручне управління та високу продуктивність.',
                'feature1': 'Модульна архітектура з підтримкою плагінів',
                'feature2': 'Система тем з кастомізацією',
                'feature3': 'Безпека та оптимізація продуктивності',
                'feature4': 'Зручна адмін-панель',
                'feature5': 'Підтримка багатьох мов',
                'promo.title': 'Потрібна допомога з розробкою?',
                'promo.text': 'Flowaxy - це також веб-студія, яка надає послуги з розробки сайтів, інтеграції та підтримки. Зверніться до нас для професійної допомоги!',
                'promo.link': 'Відвідати Flowaxy.com →',
                'button.start': 'Почати установку',
                'label.host': 'Хост',
                'label.port': 'Порт',
                'label.database': 'База даних',
                'label.username': 'Користувач',
                'label.password': 'Пароль',
                'label.password_confirm': 'Підтвердження пароля',
                'label.email': 'Email',
                'button.test': 'Тестувати підключення',
                'button.save': 'Зберегти та продовжити',
                'button.continue': 'Продовжити',
                'button.create': 'Створити та завершити',
                'test.success': 'Підключення успішне!',
                'test.error': 'Помилка підключення: ',
                'test.testing': 'Перевірка підключення...',
                'progress.text': 'Підготовка...',
                'table.creating': 'Створення',
                'table.success': 'Створено',
                'table.error': 'Помилка',
                'error.text': 'Помилка створення користувача'
            },
            ru: {
                'logo.alt': 'Flowaxy CMS',
                'header.title': 'Flowaxy CMS',
                'header.subtitle': 'Мастер установки',
                'welcome.title': 'Добро пожаловать!',
                'welcome.text1': 'Flowaxy CMS - это мощная и гибкая система управления контентом, созданная для современных веб-проектов.',
                'welcome.text2': 'Система обеспечивает быструю установку, удобное управление и высокую производительность.',
                'feature1': 'Модульная архитектура с поддержкой плагинов',
                'feature2': 'Система тем с кастомизацией',
                'feature3': 'Безопасность и оптимизация производительности',
                'feature4': 'Удобная админ-панель',
                'feature5': 'Поддержка многих языков',
                'promo.title': 'Нужна помощь с разработкой?',
                'promo.text': 'Flowaxy - это также веб-студия, которая предоставляет услуги по разработке сайтов, интеграции и поддержке. Обратитесь к нам за профессиональной помощью!',
                'promo.link': 'Посетить Flowaxy.com →',
                'button.start': 'Начать установку',
                'label.host': 'Хост',
                'label.port': 'Порт',
                'label.database': 'База данных',
                'label.username': 'Пользователь',
                'label.password': 'Пароль',
                'label.password_confirm': 'Подтверждение пароля',
                'label.email': 'Email',
                'button.test': 'Тестировать подключение',
                'button.save': 'Сохранить и продолжить',
                'button.continue': 'Продолжить',
                'button.create': 'Создать и завершить',
                'test.success': 'Подключение успешно!',
                'test.error': 'Ошибка подключения: ',
                'test.testing': 'Проверка подключения...',
                'progress.text': 'Подготовка...',
                'table.creating': 'Создание',
                'table.success': 'Создано',
                'table.error': 'Ошибка',
                'error.text': 'Ошибка создания пользователя'
            },
            en: {
                'logo.alt': 'Flowaxy CMS',
                'header.title': 'Flowaxy CMS',
                'header.subtitle': 'Installation Wizard',
                'welcome.title': 'Welcome!',
                'welcome.text1': 'Flowaxy CMS is a powerful and flexible content management system designed for modern web projects.',
                'welcome.text2': 'The system provides quick installation, convenient management and high performance.',
                'feature1': 'Modular architecture with plugin support',
                'feature2': 'Theme system with customization',
                'feature3': 'Security and performance optimization',
                'feature4': 'Convenient admin panel',
                'feature5': 'Multi-language support',
                'promo.title': 'Need development help?',
                'promo.text': 'Flowaxy is also a web studio that provides website development, integration and support services. Contact us for professional help!',
                'promo.link': 'Visit Flowaxy.com →',
                'button.start': 'Start Installation',
                'label.host': 'Host',
                'label.port': 'Port',
                'label.database': 'Database',
                'label.username': 'Username',
                'label.password': 'Password',
                'label.password_confirm': 'Confirm Password',
                'label.email': 'Email',
                'button.test': 'Test Connection',
                'button.save': 'Save & Continue',
                'button.continue': 'Continue',
                'button.create': 'Create & Finish',
                'test.success': 'Connection successful!',
                'test.error': 'Connection error: ',
                'test.testing': 'Testing connection...',
                'progress.text': 'Preparing...',
                'table.creating': 'Creating',
                'table.success': 'Created',
                'table.error': 'Error',
                'error.text': 'Error creating user'
            }
        };
        
        // Определение языка браузера (по умолчанию английский)
        function getBrowserLang() {
            const lang = navigator.language || navigator.userLanguage;
            const code = lang.split('-')[0].toLowerCase();
            return ['uk', 'ru', 'en'].includes(code) ? code : 'en';
        }
        
        // Применение переводов
        function applyTranslations(lang) {
            const trans = translations[lang] || translations.en;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (trans[key]) {
                    if (el.tagName === 'INPUT' && el.type === 'submit') {
                        el.value = trans[key];
                    } else if (el.tagName === 'BUTTON' || el.tagName === 'A') {
                        el.textContent = trans[key];
                    } else if (el.tagName === 'IMG' && el.hasAttribute('alt')) {
                        el.alt = trans[key];
                    } else {
                        el.textContent = trans[key];
                    }
                }
            });
            document.title = 'Flowaxy CMS Installation';
        }
        
        // Инициализация
        const lang = getBrowserLang();
        document.documentElement.lang = lang;
        applyTranslations(lang);
        
        // Тестирование подключения (шаг database)
        const testBtn = document.getElementById('testBtn');
        if (testBtn) {
            testBtn.addEventListener('click', async function() {
                const form = document.getElementById('databaseForm');
                const formData = new FormData(form);
                const resultDiv = document.getElementById('testResult');
                const saveBtn = document.getElementById('saveBtn');
                
                resultDiv.className = 'test-result testing';
                resultDiv.textContent = translations[lang]['test.testing'];
                testBtn.disabled = true;
                saveBtn.disabled = true;
                
                try {
                    const response = await fetch('/install?action=test_db', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        resultDiv.className = 'test-result success';
                        resultDiv.textContent = translations[lang]['test.success'];
                        saveBtn.disabled = false;
                    } else {
                        resultDiv.className = 'test-result error';
                        resultDiv.textContent = translations[lang]['test.error'] + (data.message || 'Unknown error');
                    }
                } catch (error) {
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = translations[lang]['test.error'] + error.message;
                } finally {
                    testBtn.disabled = false;
                }
            });
        }
        
        // Создание таблиц (шаг tables)
        const tablesList = document.getElementById('tablesList');
        if (tablesList) {
            const tables = ['users', 'site_settings', 'plugins', 'plugin_settings', 'theme_settings'];
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const continueBtn = document.getElementById('continueBtn');
            
            tables.forEach(table => {
                const li = document.createElement('li');
                li.id = `table-${table}`;
                li.innerHTML = `
                    <div class="table-icon creating"></div>
                    <span>${table}</span>
                `;
                tablesList.appendChild(li);
            });
            
            async function createTables() {
                for (let i = 0; i < tables.length; i++) {
                    const table = tables[i];
                    const li = document.getElementById(`table-${table}`);
                    const icon = li.querySelector('.table-icon');
                    
                    await new Promise(resolve => setTimeout(resolve, 300));
                    
                    try {
                        const response = await fetch('/install?action=create_table', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ table: table })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            icon.className = 'table-icon success';
                            li.classList.remove('creating');
                            li.classList.add('success');
                        } else {
                            icon.className = 'table-icon error';
                            li.classList.remove('creating');
                            li.classList.add('error');
                        }
                    } catch (error) {
                        icon.className = 'table-icon error';
                        li.classList.remove('creating');
                        li.classList.add('error');
                    }
                    
                    const progress = Math.round(((i + 1) / tables.length) * 100);
                    progressFill.style.width = progress + '%';
                    progressText.textContent = translations[lang]['progress.text'].replace('...', `: ${i + 1}/${tables.length}`);
                }
                
                continueBtn.style.display = 'block';
            }
            
            createTables();
        }
        
        // Валидация паролей (шаг user)
        const userForm = document.getElementById('userForm');
        if (userForm) {
            userForm.addEventListener('submit', function(e) {
                const password = document.querySelector('input[name="password"]').value;
                const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
                
                if (password !== passwordConfirm) {
                    e.preventDefault();
                    alert(translations[lang]['error.text'] || 'Passwords do not match');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert(translations[lang]['error.text'] || 'Password must be at least 8 characters');
                    return false;
                }
            });
        }
    </script>
</body>
</html>
