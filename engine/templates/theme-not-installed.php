<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тема не встановлена - Flowaxy CMS</title>
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
            color: #212529;
            line-height: 1.5;
        }
        
        .container {
            background: #ffffff;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            text-align: center;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 40px;
            color: #ffffff;
        }
        
        .header-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .message {
            font-size: 16px;
            color: #495057;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            min-height: 48px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
            border-radius: 4px;
        }
        
        .info-box h3 {
            font-size: 14px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-box p {
            font-size: 14px;
            color: #495057;
            margin: 0;
        }
        
        @media (max-width: 640px) {
            .header {
                padding: 40px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">🎨</div>
            <h1>Тема не встановлена</h1>
            <p>Для відображення сайту необхідно встановити та активувати тему</p>
        </div>
        
        <div class="content">
            <p class="message">
                Ваш сайт готовий до роботи, але для відображення контенту потрібна активна тема.
                Перейдіть до адмін-панелі та встановіть тему з бібліотеки тем або завантажте власну.
            </p>
            
            <a href="/admin/themes" class="btn">Перейти до тем</a>
            
            <div class="info-box">
                <h3>Що далі?</h3>
                <p>
                    Після встановлення теми ваш сайт буде готовий до використання. 
                    Ви зможете налаштувати зовнішній вигляд, додати контент та налаштувати плагіни.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

