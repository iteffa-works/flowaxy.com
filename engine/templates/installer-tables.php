<!DOCTYPE html>
<html lang="uk" id="installer-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">Створення таблиць</title>
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
        
        .progress-container {
            margin: 30px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: #27ae60;
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 12px;
            font-weight: 500;
        }
        
        .progress-text {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
            color: #5a6c7d;
        }
        
        .tables-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .tables-list li {
            padding: 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .tables-list li.creating {
            background: #d1ecf1;
        }
        
        .tables-list li.success {
            background: #d4edda;
        }
        
        .tables-list li.error {
            background: #f8d7da;
        }
        
        .table-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .table-icon.creating::after {
            content: '⟳';
            animation: spin 1s linear infinite;
        }
        
        .table-icon.success::after {
            content: '✓';
            color: #27ae60;
        }
        
        .table-icon.error::after {
            content: '✗';
            color: #e74c3c;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
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
            margin-top: 20px;
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
            <h1 data-i18n="header.title">Створення таблиць</h1>
        </div>
        
        <div class="installer-content">
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">0%</div>
                </div>
                <div class="progress-text" id="progressText" data-i18n="progress.text">Підготовка...</div>
            </div>
            
            <ul class="tables-list" id="tablesList"></ul>
            
            <button class="btn" id="continueBtn" style="display: none;" data-i18n="button.continue" onclick="window.location.href='/install?step=user'">Продовжити</button>
        </div>
    </div>
    
    <script>
        const translations = {
            uk: {
                'title': 'Створення таблиць',
                'header.title': 'Створення таблиць',
                'progress.text': 'Підготовка...',
                'button.continue': 'Продовжити',
                'table.creating': 'Створення',
                'table.success': 'Створено',
                'table.error': 'Помилка'
            },
            ru: {
                'title': 'Создание таблиц',
                'header.title': 'Создание таблиц',
                'progress.text': 'Подготовка...',
                'button.continue': 'Продолжить',
                'table.creating': 'Создание',
                'table.success': 'Создано',
                'table.error': 'Ошибка'
            },
            en: {
                'title': 'Creating Tables',
                'header.title': 'Creating Tables',
                'progress.text': 'Preparing...',
                'button.continue': 'Continue',
                'table.creating': 'Creating',
                'table.success': 'Created',
                'table.error': 'Error'
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
        
        // Создание таблиц
        const tables = ['users', 'site_settings', 'plugins', 'plugin_settings', 'theme_settings'];
        const tablesList = document.getElementById('tablesList');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const continueBtn = document.getElementById('continueBtn');
        
        // Создаем элементы списка
        tables.forEach(table => {
            const li = document.createElement('li');
            li.id = `table-${table}`;
            li.innerHTML = `
                <div class="table-icon creating"></div>
                <span>${table}</span>
            `;
            tablesList.appendChild(li);
        });
        
        // Функция создания таблиц
        async function createTables() {
            for (let i = 0; i < tables.length; i++) {
                const table = tables[i];
                const li = document.getElementById(`table-${table}`);
                const icon = li.querySelector('.table-icon');
                
                // Небольшая задержка для визуального эффекта
                await new Promise(resolve => setTimeout(resolve, 300));
                
                try {
                    const response = await fetch('/install?action=create_table', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
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
                progressFill.textContent = progress + '%';
                const progressTextKey = translations[lang]['progress.text'] || 'Підготовка...';
                progressText.textContent = progressTextKey.replace('...', `: ${i + 1}/${tables.length}`);
            }
            
            continueBtn.style.display = 'block';
        }
        
        // Запускаем создание таблиц
        createTables();
    </script>
</body>
</html>

