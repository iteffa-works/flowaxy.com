<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка Flowaxy CMS</title>
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
        
        .installer-container {
            background: #ffffff;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        
        .installer-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }
        
        .installer-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .installer-content {
            padding: 40px;
        }
        
        .step {
            margin-bottom: 30px;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            background: #667eea;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .step-content {
            margin-left: 42px;
        }
        
        .status-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .status-item {
            padding: 12px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .status-icon.success {
            background: #28a745;
            color: #ffffff;
        }
        
        .status-icon.error {
            background: #dc3545;
            color: #ffffff;
        }
        
        .status-icon.warning {
            background: #ffc107;
            color: #212529;
        }
        
        .status-text {
            flex: 1;
            font-size: 14px;
            color: #495057;
        }
        
        .status-text strong {
            color: #212529;
            font-weight: 600;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 48px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .installer-actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 12px;
        }
        
        .alert {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 640px) {
            .installer-header {
                padding: 30px 20px;
            }
            
            .installer-header h1 {
                font-size: 24px;
            }
            
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
            <h1>🚀 Установка Flowaxy CMS</h1>
            <p>Вітаємо! Почнемо установку вашої системи</p>
        </div>
        
        <div class="installer-content">
            <?php if (isset($installResult) && $installResult['success']): ?>
                <div class="alert alert-success">
                    <strong>Успіх!</strong> <?= htmlspecialchars($installResult['message']) ?>
                </div>
                <div class="installer-actions">
                    <a href="/admin" class="btn btn-primary">Перейти до адмін-панелі</a>
                </div>
            <?php elseif (isset($installResult) && !$installResult['success']): ?>
                <div class="alert alert-error">
                    <strong>Помилка!</strong> <?= htmlspecialchars($installResult['message']) ?>
                    <?php if (!empty($installResult['errors'])): ?>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <?php foreach ($installResult['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="installer-actions">
                    <button onclick="location.reload()" class="btn btn-primary">Спробувати знову</button>
                </div>
            <?php else: ?>
                <div class="step">
                    <div class="step-title">
                        <div class="step-number">1</div>
                        <span>Перевірка системи</span>
                    </div>
                    <div class="step-content">
                        <ul class="status-list">
                            <li class="status-item">
                                <div class="status-icon <?= $dbAvailable ? 'success' : 'error' ?>">
                                    <?= $dbAvailable ? '✓' : '✗' ?>
                                </div>
                                <div class="status-text">
                                    <strong>Підключення до бази даних</strong>
                                    <br>
                                    <span><?= $dbAvailable ? 'Підключення успішне' : 'Не вдалося підключитися' ?></span>
                                </div>
                            </li>
                            <?php if ($dbAvailable && isset($tablesStatus)): ?>
                                <li class="status-item">
                                    <div class="status-icon <?= empty($tablesStatus['missing']) ? 'success' : 'warning' ?>">
                                        <?= empty($tablesStatus['missing']) ? '✓' : '!' ?>
                                    </div>
                                    <div class="status-text">
                                        <strong>Таблиці бази даних</strong>
                                        <br>
                                        <span>
                                            <?php if (empty($tablesStatus['missing'])): ?>
                                                Всі таблиці існують (<?= count($tablesStatus['exists'] ?? []) ?>)
                                            <?php else: ?>
                                                Відсутні таблиці: <?= implode(', ', $tablesStatus['missing']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <?php if ($dbAvailable && !empty($tablesStatus['missing'])): ?>
                    <div class="step">
                        <div class="step-title">
                            <div class="step-number">2</div>
                            <span>Установка</span>
                        </div>
                        <div class="step-content">
                            <p style="margin-bottom: 20px; color: #495057;">
                                Система готова до установки. Натисніть кнопку нижче, щоб створити необхідні таблиці в базі даних.
                            </p>
                            <form method="POST" id="installForm">
                                <div class="installer-actions">
                                    <button type="submit" class="btn btn-primary" id="installBtn">
                                        <span id="installBtnText">Встановити систему</span>
                                        <span id="installBtnLoading" class="loading" style="display: none; margin-left: 10px;"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($dbAvailable && empty($tablesStatus['missing'])): ?>
                    <div class="alert alert-info">
                        <strong>Система вже встановлена!</strong> Всі необхідні таблиці існують в базі даних.
                    </div>
                    <div class="installer-actions">
                        <a href="/admin" class="btn btn-primary">Перейти до адмін-панелі</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Помилка підключення!</strong> Спочатку налаштуйте підключення до бази даних в файлі <code>engine/data/database.php</code>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('installForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('installBtn');
            const btnText = document.getElementById('installBtnText');
            const btnLoading = document.getElementById('installBtnLoading');
            
            if (btn && btnText && btnLoading) {
                btn.disabled = true;
                btnText.textContent = 'Встановлення...';
                btnLoading.style.display = 'inline-block';
            }
        });
    </script>
</body>
</html>

