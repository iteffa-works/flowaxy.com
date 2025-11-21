<!DOCTYPE html>
<html lang="uk" id="error-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">Помилка підключення до бази даних - Flowaxy CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #212529;
            line-height: 1.5;
        }
        
        .error-container {
            background: #ffffff;
            border: 1px solid #dee2e6;
            max-width: 700px;
            width: 100%;
            padding: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
        }
        
        .error-header {
            background: #dc3545;
            padding: 24px 32px;
            border-bottom: 1px solid #dc3545;
        }
        
        .error-header-inner {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .error-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ffffff;
            flex-shrink: 0;
        }
        
        .error-title {
            flex: 1;
        }
        
        .error-title h1 {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.02em;
        }
        
        .error-title p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            margin: 4px 0 0 0;
        }
        
        .error-content {
            padding: 32px;
        }
        
        .error-message {
            font-size: 15px;
            color: #495057;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .error-section {
            margin-bottom: 24px;
        }
        
        .error-section:last-child {
            margin-bottom: 0;
        }
        
        .error-section-title {
            font-size: 13px;
            font-weight: 600;
            color: #212529;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .error-section-title::before {
            content: '';
            width: 3px;
            height: 13px;
            background: #dc3545;
            display: block;
        }
        
        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .error-list li {
            padding: 10px 0;
            font-size: 14px;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .error-list li:last-child {
            border-bottom: none;
        }
        
        .error-list li::before {
            content: '•';
            color: #868e96;
            font-weight: bold;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .error-list li strong {
            color: #212529;
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
        }
        
        .error-list li span {
            color: #868e96;
            font-size: 13px;
        }
        
        .error-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            flex: 1;
            min-height: 40px;
        }
        
        .btn-primary {
            background: #007bff;
            color: #ffffff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
        
        .btn-primary:active {
            background: #004085;
            border-color: #004085;
        }
        
        .btn-secondary {
            background: #ffffff;
            color: #495057;
            border-color: #ced4da;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .btn-secondary:active {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .debug-info {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
        }
        
        .debug-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .debug-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .debug-table td:first-child {
            font-weight: 600;
            color: #495057;
            width: 140px;
        }
        
        .debug-table td:last-child {
            color: #868e96;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .debug-table tr:last-child td {
            border-bottom: none;
        }
        
        @media (max-width: 640px) {
            .error-header {
                padding: 20px 24px;
            }
            
            .error-content {
                padding: 24px;
            }
            
            .error-header-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .error-title h1 {
                font-size: 18px;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background: #1a1a1a;
                color: #e9ecef;
            }
            
            .error-container {
                background: #2d2d2d;
                border-color: #404040;
            }
            
            .error-content {
                color: #e9ecef;
            }
            
            .error-message {
                color: #adb5bd;
            }
            
            .error-list li {
                color: #adb5bd;
                border-color: #404040;
            }
            
            .error-list li strong {
                color: #e9ecef;
            }
            
            .error-section-title {
                color: #e9ecef;
            }
            
            .btn-secondary {
                background: #3d3d3d;
                color: #e9ecef;
                border-color: #555;
            }
            
            .btn-secondary:hover {
                background: #4d4d4d;
            }
            
            .debug-table td {
                border-color: #404040;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-header-inner">
                <div class="error-icon">⚠</div>
                <div class="error-title">
                    <h1>Помилка підключення до бази даних</h1>
                    <p>Сервер бази даних недоступний</p>
                </div>
            </div>
        </div>
        
        <div class="error-content">
            <div class="error-message">
                Не вдалося встановити з'єднання з сервером бази даних. Перевірте конфігурацію підключення та доступність сервера.
            </div>
            
            <div class="error-section">
                <div class="error-section-title">Можливі причини</div>
                <ul class="error-list">
                    <li>
                        <strong>Сервер MySQL/MariaDB не запущений</strong>
                        <span>Перевірте статус сервісу бази даних на сервері</span>
                    </li>
                    <li>
                        <strong>Невірні параметри підключення</strong>
                        <span>Перевірте файл engine/data/database.ini: хост, порт, ім'я бази даних</span>
                    </li>
                    <li>
                        <strong>База даних не існує</strong>
                        <span>Створіть базу даних <?= htmlspecialchars(DB_NAME ?? '') ?> або змініть назву в конфігурації</span>
                    </li>
                    <li>
                        <strong>Проблеми з мережею</strong>
                        <span>Перевірте доступність сервера БД та налаштування firewall</span>
                    </li>
                    <li>
                        <strong>Перевищено ліміт підключень</strong>
                        <span>Перезапустіть сервер БД або збільште max_connections</span>
                    </li>
                    <li>
                        <strong>Неправильні облікові дані</strong>
                        <span>Перевірте логін та пароль користувача бази даних</span>
                    </li>
                </ul>
            </div>
            
            <?php if (defined('DEBUG_MODE') && DEBUG_MODE && isset($errorDetails)): ?>
            <div class="error-section debug-info">
                <div class="error-section-title">Технічна інформація</div>
                <table class="debug-table">
                    <?php if (isset($errorDetails['host'])): ?>
                    <tr>
                        <td>Хост</td>
                        <td><?= htmlspecialchars($errorDetails['host']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($errorDetails['port'])): ?>
                    <tr>
                        <td>Порт</td>
                        <td><?= htmlspecialchars($errorDetails['port']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($errorDetails['database'])): ?>
                    <tr>
                        <td>База даних</td>
                        <td><?= htmlspecialchars($errorDetails['database']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($errorDetails['error'])): ?>
                    <tr>
                        <td>Помилка</td>
                        <td><?= htmlspecialchars($errorDetails['error']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($errorDetails['code'])): ?>
                    <tr>
                        <td>Код помилки</td>
                        <td><?= htmlspecialchars($errorDetails['code']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="error-actions">
                <button class="btn btn-primary" onclick="location.reload()" data-i18n="button.reload">Оновити сторінку</button>
                <a href="<?= class_exists('UrlHelper') ? UrlHelper::admin() : '/admin' ?>" class="btn btn-secondary" data-i18n="button.admin">Адмін-панель</a>
            </div>
        </div>
    </div>
    
    <script>
        const translations = {
            uk: {
                'title': 'Помилка підключення до бази даних - Flowaxy CMS',
                'button.reload': 'Оновити сторінку',
                'button.admin': 'Адмін-панель'
            },
            ru: {
                'title': 'Ошибка подключения к базе данных - Flowaxy CMS',
                'button.reload': 'Обновить страницу',
                'button.admin': 'Админ-панель'
            },
            en: {
                'title': 'Database Connection Error - Flowaxy CMS',
                'button.reload': 'Reload Page',
                'button.admin': 'Admin Panel'
            }
        };
        
        function getBrowserLang() {
            const lang = navigator.language || navigator.userLanguage;
            const code = lang.split('-')[0].toLowerCase();
            return ['uk', 'ru', 'en'].includes(code) ? code : 'uk';
        }
        
        function applyTranslations(lang) {
            const trans = translations[lang] || translations.uk;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (trans[key]) {
                    el.textContent = trans[key];
                }
            });
            document.title = trans['title'] || document.title;
        }
        
        const lang = getBrowserLang();
        document.documentElement.lang = lang;
        applyTranslations(lang);
    </script>
</body>
</html>

