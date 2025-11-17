<?php
/**
 * Главный шаблон темы Minimal CSS
 * Минималистичная тема с поддержкой CSS файлов
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

// Получаем настройки темы из кастомайзера
$themeSettings = $themeManager->getSettings();
$themeConfig = $themeManager->getThemeConfig();
$defaultSettings = $themeConfig['default_settings'] ?? [];

// Функция для получения настройки темы
$getThemeSetting = function($key, $default = '') use ($themeSettings, $defaultSettings) {
    if (isset($themeSettings[$key]) && $themeSettings[$key] !== '') {
        return $themeSettings[$key];
    }
    return $defaultSettings[$key] ?? $default;
};

// Получаем настройки
$siteLogo = $getThemeSetting('site_logo', '');
$siteFavicon = $getThemeSetting('site_favicon', '');
$customSiteTitle = $getThemeSetting('site_title', '');
$customSiteTagline = $getThemeSetting('site_tagline', '');
$primaryColor = $getThemeSetting('primary_color', '#667eea');
$secondaryColor = $getThemeSetting('secondary_color', '#764ba2');
$textColor = $getThemeSetting('text_color', '#333333');
$backgroundColor = $getThemeSetting('background_color', '#f5f5f5');
$headerBgColor = $getThemeSetting('header_bg_color', '#2c3e50');
$headerTextColor = $getThemeSetting('header_text_color', '#ffffff');
$footerBgColor = $getThemeSetting('footer_bg_color', '#2c3e50');
$footerTextColor = $getThemeSetting('footer_text_color', '#ffffff');
$fontFamily = $getThemeSetting('font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif');
$fontSize = $getThemeSetting('font_size', '16px');
$lineHeight = $getThemeSetting('line_height', '1.6');
$headingFont = $getThemeSetting('heading_font', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif');
$customCss = $getThemeSetting('custom_css', '');

// Используем кастомные значения если они заданы
if (!empty($customSiteTitle)) {
    $siteTitle = $customSiteTitle;
}
if (!empty($customSiteTagline)) {
    $siteTagline = $customSiteTagline;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= safe_html($siteDescription) ?>">
    <title><?= safe_html($siteTitle) ?></title>
    
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" type="image/x-icon" href="<?= safe_html($siteFavicon) ?>">
    <?php endif; ?>
    
    <!-- Подключение CSS файла из assets/css -->
    <link rel="stylesheet" href="<?= $themeUrl ?>assets/css/style.css">
    
    <!-- Динамические стили из настроек темы -->
    <style>
        :root {
            --primary-color: <?= safe_html($primaryColor) ?>;
            --secondary-color: <?= safe_html($secondaryColor) ?>;
            --text-color: <?= safe_html($textColor) ?>;
            --background-color: <?= safe_html($backgroundColor) ?>;
            --header-bg-color: <?= safe_html($headerBgColor) ?>;
            --header-text-color: <?= safe_html($headerTextColor) ?>;
            --footer-bg-color: <?= safe_html($footerBgColor) ?>;
            --footer-text-color: <?= safe_html($footerTextColor) ?>;
        }
        
        body {
            font-family: <?= safe_html($fontFamily) ?>;
            font-size: <?= safe_html($fontSize) ?>;
            line-height: <?= safe_html($lineHeight) ?>;
            color: <?= safe_html($textColor) ?>;
            background-color: <?= safe_html($backgroundColor) ?>;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: <?= safe_html($headingFont) ?>;
        }
        
        .header {
            background-color: <?= safe_html($headerBgColor) ?>;
            color: <?= safe_html($headerTextColor) ?>;
        }
        
        .header .logo,
        .header .logo h1,
        .header .nav a {
            color: <?= safe_html($headerTextColor) ?>;
        }
        
        .footer {
            background-color: <?= safe_html($footerBgColor) ?>;
            color: <?= safe_html($footerTextColor) ?>;
        }
        
        .hero {
            background: linear-gradient(135deg, <?= safe_html($primaryColor) ?> 0%, <?= safe_html($secondaryColor) ?> 100%);
        }
        
        .btn-primary {
            background-color: <?= safe_html($primaryColor) ?>;
            border-color: <?= safe_html($primaryColor) ?>;
        }
        
        .btn-primary:hover {
            background-color: <?= safe_html($secondaryColor) ?>;
            border-color: <?= safe_html($secondaryColor) ?>;
        }
        
        <?php if (!empty($customCss)): ?>
        /* Кастомный CSS */
        <?= $customCss ?>
        <?php endif; ?>
    </style>
    
    <?php doHook('theme_head'); ?>
</head>
<body>
    <?php doHook('theme_body_start'); ?>
    
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?= SITE_URL ?>" class="logo">
                    <?php if (!empty($siteLogo)): ?>
                        <img src="<?= safe_html($siteLogo) ?>" alt="<?= safe_html($siteTitle) ?>" style="max-height: 50px; width: auto;">
                    <?php else: ?>
                        <h1><?= safe_html($siteTitle) ?></h1>
                    <?php endif; ?>
                </a>
                       <nav class="nav">
                           <?php 
                           if (function_exists('doHook')) {
                               doHook('theme_menu');
                           }
                           ?>
                       </nav>
            </div>
        </div>
    </header>
    
    <main class="main">
        <section class="hero">
            <div class="container">
                <h2 class="hero-title">Ласкаво просимо</h2>
                <p class="hero-description">Це мінімалістична тема-приклад з підтримкою CSS файлів</p>
                <a href="<?= SITE_URL ?>" class="btn btn-primary">Дізнатися більше</a>
            </div>
        </section>
        
        <section class="content">
            <div class="container">
                <h2>Про цю тему</h2>
                <p>Ця тема демонструє базову структуру теми з підтримкою CSS файлів.</p>
                <p>CSS файли знаходяться в <code>assets/css/</code>.</p>
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
