<!DOCTYPE html>
<html lang="uk" id="installer-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">Установка Flowaxy CMS</title>
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
            max-width: 900px;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .installer-header {
            background: #2c3e50;
            padding: 50px 40px;
            text-align: center;
            color: #ffffff;
        }
        
        .installer-header h1 {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }
        
        .installer-header p {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .installer-content {
            padding: 50px 40px;
        }
        
        .welcome-section {
            margin-bottom: 40px;
        }
        
        .welcome-section h2 {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .welcome-section p {
            font-size: 16px;
            color: #5a6c7d;
            margin-bottom: 15px;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        
        .features-list li {
            padding: 12px 0;
            padding-left: 30px;
            position: relative;
            font-size: 15px;
            color: #5a6c7d;
        }
        
        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
        }
        
        .flowaxy-promo {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 25px;
            margin: 30px 0;
            border-radius: 4px;
        }
        
        .flowaxy-promo h3 {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .flowaxy-promo p {
            font-size: 14px;
            color: #5a6c7d;
            margin-bottom: 15px;
        }
        
        .flowaxy-promo a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .flowaxy-promo a:hover {
            text-decoration: underline;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #2c3e50;
            color: #ffffff;
            min-height: 48px;
        }
        
        .btn:hover {
            background: #34495e;
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        .installer-actions {
            margin-top: 40px;
            text-align: center;
        }
        
        @media (max-width: 640px) {
            .installer-header {
                padding: 40px 20px;
            }
            
            .installer-header h1 {
                font-size: 28px;
            }
            
            .installer-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1 data-i18n="header.title">🚀 Вітаємо в Flowaxy CMS</h1>
            <p data-i18n="header.subtitle">Сучасна система управління контентом</p>
        </div>
        
        <div class="installer-content">
            <div class="welcome-section">
                <h2 data-i18n="welcome.title">Ласкаво просимо!</h2>
                <p data-i18n="welcome.text1">Flowaxy CMS - це потужна та гнучка система управління контентом, створена для сучасних веб-проектів.</p>
                <p data-i18n="welcome.text2">Система забезпечує швидку установку, зручне управління та високу продуктивність.</p>
                
                <ul class="features-list">
                    <li data-i18n="feature1">Модульна архітектура з підтримкою плагінів</li>
                    <li data-i18n="feature2">Система тем з кастомізацією</li>
                    <li data-i18n="feature3">Безпека та оптимізація продуктивності</li>
                    <li data-i18n="feature4">Зручна адмін-панель</li>
                    <li data-i18n="feature5">Підтримка багатьох мов</li>
                </ul>
            </div>
            
            <div class="flowaxy-promo">
                <h3 data-i18n="promo.title">Потрібна допомога з розробкою?</h3>
                <p data-i18n="promo.text">Flowaxy - це також веб-студія, яка надає послуги з розробки сайтів, інтеграції та підтримки. Зверніться до нас для професійної допомоги!</p>
                <a href="https://flowaxy.com" target="_blank" data-i18n="promo.link">Відвідати Flowaxy.com →</a>
            </div>
            
            <div class="installer-actions">
                <a href="/install?step=database" class="btn" data-i18n="button.start">Почати установку</a>
            </div>
        </div>
    </div>
    
    <script>
        // Мультиязычность
        const translations = {
            uk: {
                'title': 'Установка Flowaxy CMS',
                'header.title': '🚀 Вітаємо в Flowaxy CMS',
                'header.subtitle': 'Сучасна система управління контентом',
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
                'button.start': 'Почати установку'
            },
            ru: {
                'title': 'Установка Flowaxy CMS',
                'header.title': '🚀 Добро пожаловать в Flowaxy CMS',
                'header.subtitle': 'Современная система управления контентом',
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
                'button.start': 'Начать установку'
            },
            en: {
                'title': 'Flowaxy CMS Installation',
                'header.title': '🚀 Welcome to Flowaxy CMS',
                'header.subtitle': 'Modern content management system',
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
                'button.start': 'Start Installation'
            }
        };
        
        // Определение языка браузера
        function getBrowserLang() {
            const lang = navigator.language || navigator.userLanguage;
            const code = lang.split('-')[0].toLowerCase();
            return ['uk', 'ru', 'en'].includes(code) ? code : 'uk';
        }
        
        // Применение переводов
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
        
        // Инициализация
        const lang = getBrowserLang();
        document.documentElement.lang = lang;
        applyTranslations(lang);
    </script>
</body>
</html>

