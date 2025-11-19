<!DOCTYPE html>
<html lang="uk" id="installer-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">Створення адміністратора</title>
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
            max-width: 600px;
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
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
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
            background: #2c3e50;
            color: #ffffff;
            min-height: 44px;
            width: 100%;
        }
        
        .btn:hover {
            background: #34495e;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        @media (max-width: 640px) {
            .installer-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1 data-i18n="header.title">Створення адміністратора</h1>
        </div>
        
        <div class="installer-content">
            <?php if (isset($error) && $error): ?>
                <div class="alert error" data-i18n="error.text"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/install?step=user" id="userForm">
                <div class="form-group">
                    <label data-i18n="label.username">Ім'я користувача</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.email">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.password">Пароль</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label data-i18n="label.password_confirm">Підтвердження пароля</label>
                    <input type="password" name="password_confirm" required minlength="8">
                </div>
                
                <button type="submit" class="btn" data-i18n="button.create">Створити та завершити</button>
            </form>
        </div>
    </div>
    
    <script>
        const translations = {
            uk: {
                'title': 'Створення адміністратора',
                'header.title': 'Створення адміністратора',
                'label.username': 'Ім\'я користувача',
                'label.email': 'Email',
                'label.password': 'Пароль',
                'label.password_confirm': 'Підтвердження пароля',
                'button.create': 'Створити та завершити',
                'error.text': 'Помилка створення користувача'
            },
            ru: {
                'title': 'Создание администратора',
                'header.title': 'Создание администратора',
                'label.username': 'Имя пользователя',
                'label.email': 'Email',
                'label.password': 'Пароль',
                'label.password_confirm': 'Подтверждение пароля',
                'button.create': 'Создать и завершить',
                'error.text': 'Ошибка создания пользователя'
            },
            en: {
                'title': 'Create Administrator',
                'header.title': 'Create Administrator',
                'label.username': 'Username',
                'label.email': 'Email',
                'label.password': 'Password',
                'label.password_confirm': 'Confirm Password',
                'button.create': 'Create & Finish',
                'error.text': 'Error creating user'
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
                    if (el.tagName === 'INPUT' || el.tagName === 'BUTTON') {
                        el.textContent = trans[key];
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
        
        // Валидация паролей
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Паролі не співпадають');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Пароль повинен містити мінімум 8 символів');
                return false;
            }
        });
    </script>
</body>
</html>

