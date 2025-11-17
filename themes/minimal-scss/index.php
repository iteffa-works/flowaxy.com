<?php
/**
 * Главный шаблон темы Minimal SCSS
 * Минималистичная тема с поддержкой SCSS файлов
 */

$themeManager = themeManager();
$themeUrl = $themeManager->getThemeUrl();

// Получаем настройки сайта из базы данных
$settings = settingsManager();
$siteTitle = $settings->get('site_name', 'Flowaxy CMS');
$siteTagline = $settings->get('site_tagline', 'Сучасна CMS система');
$siteDescription = $settings->get('site_description', 'Сучасна CMS система для створення сайтів');
$sitePhone = $settings->get('site_phone', '');
$adminEmail = $settings->get('admin_email', '');
$copyrightText = $settings->get('copyright', '');
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
    <meta name="description" content="<?= SecurityHelper::safeHtml($siteDescription) ?>">
    <title><?= SecurityHelper::safeHtml($siteTitle) ?></title>
    
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" type="image/x-icon" href="<?= SecurityHelper::safeHtml($siteFavicon) ?>">
    <?php endif; ?>
    
    <!-- Подключение CSS файла (скомпилированного из SCSS) -->
    <link rel="stylesheet" href="<?= $themeManager->getStylesheetUrl() ?>">
    
    <!-- Динамические стили из настроек темы -->
    <style>
        :root {
            --primary-color: <?= SecurityHelper::safeHtml($primaryColor) ?>;
            --secondary-color: <?= SecurityHelper::safeHtml($secondaryColor) ?>;
            --text-color: <?= SecurityHelper::safeHtml($textColor) ?>;
            --background-color: <?= SecurityHelper::safeHtml($backgroundColor) ?>;
            --header-bg-color: <?= SecurityHelper::safeHtml($headerBgColor) ?>;
            --header-text-color: <?= SecurityHelper::safeHtml($headerTextColor) ?>;
            --footer-bg-color: <?= SecurityHelper::safeHtml($footerBgColor) ?>;
            --footer-text-color: <?= SecurityHelper::safeHtml($footerTextColor) ?>;
        }
        
        body {
            font-family: <?= SecurityHelper::safeHtml($fontFamily) ?>;
            font-size: <?= SecurityHelper::safeHtml($fontSize) ?>;
            line-height: <?= SecurityHelper::safeHtml($lineHeight) ?>;
            color: <?= SecurityHelper::safeHtml($textColor) ?>;
            background-color: <?= SecurityHelper::safeHtml($backgroundColor) ?>;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: <?= SecurityHelper::safeHtml($headingFont) ?>;
        }
        
        .header {
            background-color: <?= SecurityHelper::safeHtml($headerBgColor) ?>;
            color: <?= SecurityHelper::safeHtml($headerTextColor) ?>;
        }
        
        .header .logo,
        .header .logo h1,
        .header .nav a {
            color: <?= SecurityHelper::safeHtml($headerTextColor) ?>;
        }
        
        .footer {
            background-color: <?= SecurityHelper::safeHtml($footerBgColor) ?>;
            color: <?= SecurityHelper::safeHtml($footerTextColor) ?>;
        }
        
        .hero {
            background: linear-gradient(135deg, <?= SecurityHelper::safeHtml($primaryColor) ?> 0%, <?= SecurityHelper::safeHtml($secondaryColor) ?> 100%);
        }
        
        .btn-primary {
            background-color: <?= SecurityHelper::safeHtml($primaryColor) ?>;
            border-color: <?= SecurityHelper::safeHtml($primaryColor) ?>;
        }
        
        .btn-primary:hover {
            background-color: <?= SecurityHelper::safeHtml($secondaryColor) ?>;
            border-color: <?= SecurityHelper::safeHtml($secondaryColor) ?>;
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
                        <img src="<?= SecurityHelper::safeHtml($siteLogo) ?>" alt="<?= SecurityHelper::safeHtml($siteTitle) ?>" style="max-height: 50px; width: auto;">
                    <?php else: ?>
                        <h1><?= SecurityHelper::safeHtml($siteTitle) ?></h1>
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
            <p><?= SecurityHelper::safeHtml($copyright) ?></p>
        </div>
    </footer>
    
    <?php doHook('theme_footer'); ?>
</body>
</html>
