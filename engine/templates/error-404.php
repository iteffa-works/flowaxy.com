<!DOCTYPE html>
<html lang="uk" id="error-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">404 - Сторінку не знайдено</title>
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
        
        .error-container {
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #e74c3c;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 32px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .error-message {
            font-size: 16px;
            color: #5a6c7d;
            margin-bottom: 30px;
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
        }
        
        .btn:hover {
            background: #34495e;
        }
        
        @media (max-width: 640px) {
            .error-code {
                font-size: 80px;
            }
            
            .error-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title" data-i18n="title">Сторінку не знайдено</h1>
        <p class="error-message" data-i18n="message">Запитана сторінка не існує або була переміщена.</p>
        <a href="/" class="btn" data-i18n="button.home">На головну</a>
    </div>
    
    <script>
        const translations = {
            uk: {
                'title': 'Сторінку не знайдено',
                'message': 'Запитана сторінка не існує або була переміщена.',
                'button.home': 'На головну'
            },
            ru: {
                'title': 'Страница не найдена',
                'message': 'Запрошенная страница не существует или была перемещена.',
                'button.home': 'На главную'
            },
            en: {
                'title': 'Page Not Found',
                'message': 'The requested page does not exist or has been moved.',
                'button.home': 'Go Home'
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

