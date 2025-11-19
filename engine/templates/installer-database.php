<!DOCTYPE html>
<html lang="uk" id="installer-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">Підключення до бази даних</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #2c3e50;
            line-height: 1.6;
        }
        
        .installer-container {
            background: #ffffff;
            border-radius: 8px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .installer-header {
            background: #2c3e50;
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        
        .installer-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .installer-content {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .test-result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            font-size: 14px;
            display: none;
        }
        
        .test-result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            display: block;
        }
        
        .test-result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            display: block;
        }
        
        .test-result.testing {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            display: block;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 44px;
        }
        
        .btn-primary {
            background: #2c3e50;
            color: #ffffff;
        }
        
        .btn-primary:hover {
            background: #34495e;
        }
        
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-secondary:hover {
            background: #bdc3c7;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .installer-actions {
            margin-top: 30px;
            display: flex;
            gap: 12px;
        }
        
        .installer-actions .btn {
            flex: 1;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 640px) {
            .installer-content {
                padding: 30px 20px;
            }
            
            .installer-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1 data-i18n="header.title">Підключення до бази даних</h1>
        </div>
        
        <div class="installer-content">
            <form id="databaseForm" method="POST" action="/install?step=database">
                <div class="form-group">
                    <label data-i18n="label.host">Хост</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '127.0.0.1') ?>" required>
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.port">Порт</label>
                    <input type="number" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.database">База даних</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.username">Користувач</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.password">Пароль</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                </div>
                
                <div class="test-result" id="testResult"></div>
                
                <div class="installer-actions">
                    <button type="button" class="btn btn-secondary" id="testBtn" data-i18n="button.test">Тестувати підключення</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn" data-i18n="button.save" disabled>Зберегти та продовжити</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const translations = {
            uk: {
                'title': 'Підключення до бази даних',
                'header.title': 'Підключення до бази даних',
                'label.host': 'Хост',
                'label.port': 'Порт',
                'label.database': 'База даних',
                'label.username': 'Користувач',
                'label.password': 'Пароль',
                'button.test': 'Тестувати підключення',
                'button.save': 'Зберегти та продовжити',
                'test.success': 'Підключення успішне!',
                'test.error': 'Помилка підключення: ',
                'test.testing': 'Перевірка підключення...'
            },
            ru: {
                'title': 'Подключение к базе данных',
                'header.title': 'Подключение к базе данных',
                'label.host': 'Хост',
                'label.port': 'Порт',
                'label.database': 'База данных',
                'label.username': 'Пользователь',
                'label.password': 'Пароль',
                'button.test': 'Тестировать подключение',
                'button.save': 'Сохранить и продолжить',
                'test.success': 'Подключение успешно!',
                'test.error': 'Ошибка подключения: ',
                'test.testing': 'Проверка подключения...'
            },
            en: {
                'title': 'Database Connection',
                'header.title': 'Database Connection',
                'label.host': 'Host',
                'label.port': 'Port',
                'label.database': 'Database',
                'label.username': 'Username',
                'label.password': 'Password',
                'button.test': 'Test Connection',
                'button.save': 'Save & Continue',
                'test.success': 'Connection successful!',
                'test.error': 'Connection error: ',
                'test.testing': 'Testing connection...'
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
                    if (el.tagName === 'INPUT' && el.type === 'submit') {
                        el.value = trans[key];
                    } else {
                        el.textContent = trans[key];
                    }
                }
            });
            document.title = trans['title'] || document.title;
        }
        
        const lang = getBrowserLang();
        document.documentElement.lang = lang;
        applyTranslations(lang);
        
        // Тестирование подключения
        document.getElementById('testBtn').addEventListener('click', async function() {
            const form = document.getElementById('databaseForm');
            const formData = new FormData(form);
            const resultDiv = document.getElementById('testResult');
            const saveBtn = document.getElementById('saveBtn');
            const testBtn = this;
            
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
    </script>
</body>
</html>

