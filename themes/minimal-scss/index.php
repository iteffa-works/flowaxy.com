<?php
/**
 * Главный шаблон темы Minimal SCSS
 * Минималистичная тема с поддержкой SCSS файлов
 */

$themeManager = themeManager();
$themeUrl = $themeManager->getThemeUrl();

// Получаем настройки сайта из базы данных
$siteTitle = getSetting('site_name', 'Flowaxy CMS');
$siteTagline = getSetting('site_tagline', 'Сучасна CMS система');
$siteDescription = getSetting('site_description', 'Сучасна CMS система для створення сайтів');
$sitePhone = getSetting('site_phone', '');
$adminEmail = getSetting('admin_email', '');
$copyrightText = getSetting('copyright', '');
$copyright = !empty($copyrightText) ? $copyrightText : ('© ' . date('Y') . ' ' . $siteTitle . '. Всі права захищені.');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= safe_html($siteDescription) ?>">
    <title><?= safe_html($siteTitle) ?></title>
    
    <!-- Подключение CSS файла (скомпилированного из SCSS) -->
    <link rel="stylesheet" href="<?= $themeManager->getStylesheetUrl() ?>">
    
    <?php doHook('theme_head'); ?>
</head>
<body>
    <?php doHook('theme_body_start'); ?>
    
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?= SITE_URL ?>" class="logo">
                    <h1><?= safe_html($siteTitle) ?></h1>
                </a>
                <nav class="nav">
                    <?php doHook('theme_menu'); ?>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main">
        <section class="hero">
            <div class="container">
                <h2 class="hero-title">Ласкаво просимо</h2>
                <p class="hero-description">Це мінімалістична тема-приклад з підтримкою SCSS</p>
                <a href="<?= SITE_URL ?>" class="btn btn-primary">Дізнатися більше</a>
            </div>
        </section>
        
        <section class="content">
            <div class="container">
                <h2>Про цю тему</h2>
                <p>Ця тема демонструє базову структуру теми з підтримкою SCSS файлів.</p>
                <p>SCSS файли знаходяться в <code>assets/scss/</code> і автоматично компілюються в CSS.</p>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p><?= safe_html($copyright) ?></p>
        </div>
    </footer>
    
    <?php doHook('theme_footer'); ?>
</body>
</html>
