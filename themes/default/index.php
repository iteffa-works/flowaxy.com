<?php
/**
 * Главный шаблон темы Default
 * Bootstrap 5 тестовая тема с использованием всего функционала CMS
 */

// Получаем менеджеры
$themeManager = themeManager();
$menuManager = menuManager();

// Получаем настройки темы
$primaryColor = $themeManager->getSetting('primary_color', '#0d6efd');
$primaryColorHover = $themeManager->getSetting('primary_color_hover', '#0b5ed7');
$secondaryColor = $themeManager->getSetting('secondary_color', '#6c757d');
$backgroundColor = $themeManager->getSetting('background_color', '#ffffff');
$textColor = $themeManager->getSetting('text_color', '#212529');
$textColorSecondary = $themeManager->getSetting('text_color_secondary', '#6c757d');
$linkColor = $themeManager->getSetting('link_color', '#0d6efd');
$linkColorHover = $themeManager->getSetting('link_color_hover', '#0b5ed7');
$headerBackground = $themeManager->getSetting('header_background', '#ffffff');
$footerBackground = $themeManager->getSetting('footer_background', '#212529');
$footerTextColor = $themeManager->getSetting('footer_text_color', '#ffffff');
$logoUrl = $themeManager->getSetting('logo', '');
$logoWidth = $themeManager->getSetting('logo_width', '150');
$fontFamily = $themeManager->getSetting('font_family', 'system');
$fontFamilyHeading = $themeManager->getSetting('font_family_heading', 'system');
$fontSizeBase = $themeManager->getSetting('font_size_base', '16');
$fontSizeH1 = $themeManager->getSetting('font_size_h1', '40');
$fontSizeH2 = $themeManager->getSetting('font_size_h2', '32');
$fontSizeH3 = $themeManager->getSetting('font_size_h3', '24');
$fontWeightBase = $themeManager->getSetting('font_weight_base', '400');
$fontWeightHeading = $themeManager->getSetting('font_weight_heading', '700');
$lineHeightBase = $themeManager->getSetting('line_height_base', '1.6');
$headerSticky = $themeManager->getSetting('header_sticky', '1');
$headerHeight = $themeManager->getSetting('header_height', '80');
$headerPadding = $themeManager->getSetting('header_padding', '20');
$headerTransparent = $themeManager->getSetting('header_transparent', '0');
$menuStyle = $themeManager->getSetting('menu_style', 'default');
$menuFontSize = $themeManager->getSetting('menu_font_size', '16');
$menuFontWeight = $themeManager->getSetting('menu_font_weight', '600');
$menuSpacing = $themeManager->getSetting('menu_spacing', '20');
$footerColumns = $themeManager->getSetting('footer_columns', '3');
$footerPadding = $themeManager->getSetting('footer_padding', '60');
$footerCopyright = $themeManager->getSetting('footer_copyright', '© 2025 Landing CMS - Усі права захищені');
$customCss = $themeManager->getSetting('custom_css', '');
$siteTitle = getSetting('site_title', 'Landing CMS');
$siteDescription = getSetting('site_description', 'Сучасна CMS система');

// Получаем главное меню
$mainMenu = $menuManager->getMenu('primary');
$menuItems = $mainMenu ? $menuManager->getMenuItems($mainMenu['id']) : [];

// Хук для модификации данных перед рендерингом
$themeData = doHook('theme_before_render', [
    'title' => $siteTitle,
    'description' => $siteDescription,
    'logo' => $logoUrl,
    'menu' => $menuItems
]);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= safe_html($themeData['description'] ?? $siteDescription) ?>">
    <title><?= safe_html($themeData['title'] ?? $siteTitle) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php
    // Подключение Google Fonts
    $googleFonts = [];
    $fontMap = [
        'roboto' => 'Roboto:300,400,500,600,700',
        'open-sans' => 'Open+Sans:300,400,500,600,700',
        'lato' => 'Lato:300,400,500,600,700',
        'montserrat' => 'Montserrat:300,400,500,600,700,800',
        'raleway' => 'Raleway:300,400,500,600,700,800',
        'poppins' => 'Poppins:300,400,500,600,700',
        'inter' => 'Inter:300,400,500,600,700',
        'nunito' => 'Nunito:300,400,500,600,700,800'
    ];
    
    if ($fontFamily !== 'system' && isset($fontMap[$fontFamily])) {
        $googleFonts[] = $fontMap[$fontFamily];
    }
    if ($fontFamilyHeading !== 'system' && isset($fontMap[$fontFamilyHeading]) && $fontFamilyHeading !== $fontFamily) {
        $googleFonts[] = $fontMap[$fontFamilyHeading];
    }
    
    if (!empty($googleFonts)) {
        $fontsUrl = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_unique($googleFonts)) . '&display=swap';
        echo '<link rel="stylesheet" href="' . htmlspecialchars($fontsUrl) . '">';
    }
    ?>
    
    <!-- Custom Theme Styles -->
    <link rel="stylesheet" href="<?= $themeManager->getThemeUrl() ?>style.css">
    
    <!-- Dynamic Theme Colors and Typography -->
    <style>
        <?php
        // Функция для получения CSS-имени шрифта
        function getFontFamily($font) {
            if ($font === 'system') {
                return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
            }
            $fontMap = [
                'roboto' => '"Roboto", sans-serif',
                'open-sans' => '"Open Sans", sans-serif',
                'lato' => '"Lato", sans-serif',
                'montserrat' => '"Montserrat", sans-serif',
                'raleway' => '"Raleway", sans-serif',
                'poppins' => '"Poppins", sans-serif',
                'inter' => '"Inter", sans-serif',
                'nunito' => '"Nunito", sans-serif'
            ];
            return $fontMap[$font] ?? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        }
        ?>
        :root {
            --bs-primary: <?= safe_html($primaryColor) ?>;
            --bs-secondary: <?= safe_html($secondaryColor) ?>;
            --theme-bg: <?= safe_html($backgroundColor) ?>;
            --theme-text: <?= safe_html($textColor) ?>;
            --theme-text-secondary: <?= safe_html($textColorSecondary) ?>;
            --theme-link: <?= safe_html($linkColor) ?>;
            --theme-link-hover: <?= safe_html($linkColorHover) ?>;
            --theme-header-bg: <?= safe_html($headerBackground) ?>;
            --theme-footer-bg: <?= safe_html($footerBackground) ?>;
            --theme-footer-text: <?= safe_html($footerTextColor) ?>;
        }
        
        body {
            background-color: <?= safe_html($backgroundColor) ?>;
            color: <?= safe_html($textColor) ?>;
            font-family: <?= getFontFamily($fontFamily) ?>;
            font-size: <?= (int)$fontSizeBase ?>px;
            font-weight: <?= (int)$fontWeightBase ?>;
            line-height: <?= safe_html($lineHeightBase) ?>;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: <?= getFontFamily($fontFamilyHeading) ?>;
            font-weight: <?= (int)$fontWeightHeading ?>;
            color: <?= safe_html($textColor) ?>;
        }
        
        h1 { font-size: <?= (int)$fontSizeH1 ?>px; }
        h2 { font-size: <?= (int)$fontSizeH2 ?>px; }
        h3 { font-size: <?= (int)$fontSizeH3 ?>px; }
        
        a {
            color: <?= safe_html($linkColor) ?>;
        }
        
        a:hover {
            color: <?= safe_html($linkColorHover) ?>;
        }
        
        .text-secondary {
            color: <?= safe_html($textColorSecondary) ?> !important;
        }
        
        .bg-primary { 
            background-color: <?= safe_html($primaryColor) ?> !important; 
        }
        
        .text-primary { 
            color: <?= safe_html($primaryColor) ?> !important; 
        }
        
        .btn-primary { 
            background-color: <?= safe_html($primaryColor) ?>;
            border-color: <?= safe_html($primaryColor) ?>;
        }
        
        .btn-primary:hover {
            background-color: <?= safe_html($primaryColorHover) ?>;
            border-color: <?= safe_html($primaryColorHover) ?>;
        }
        
        header, .navbar {
            background-color: <?= safe_html($headerBackground) ?> !important;
            min-height: <?= (int)$headerHeight ?>px;
        }
        
        /* Menu Styles */
        .navbar-nav {
            font-size: <?= (int)$menuFontSize ?>px;
            font-weight: <?= (int)$menuFontWeight ?>;
        }
        
        .menu-style-underline .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: <?= safe_html($primaryColor) ?>;
        }
        
        .menu-style-background .nav-link.active {
            background: <?= safe_html($primaryColor) ?>;
            color: #fff !important;
            border-radius: 6px;
            padding: 8px 16px !important;
        }
        
        .menu-style-minimal .nav-link {
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: <?= ((int)$menuFontSize - 2) ?>px;
        }
        
        footer {
            background-color: <?= safe_html($footerBackground) ?> !important;
            color: <?= safe_html($footerTextColor) ?> !important;
            padding: <?= (int)$footerPadding ?>px 0 !important;
        }
        
        footer a {
            color: <?= safe_html($footerTextColor) ?>;
        }
        
        footer a:hover {
            color: <?= safe_html($linkColorHover) ?>;
        }
        
        <?php if (!empty($customCss)): ?>
        /* Custom CSS */
        <?= safe_html($customCss) ?>
        <?php endif; ?>
    </style>
    
    <?php doHook('theme_head'); ?>
</head>
<body>
    <?php doHook('theme_body_start'); ?>
    
    <!-- Header -->
    <header class="shadow-sm <?= ($headerSticky == '1') ? 'sticky-top' : '' ?>" style="<?= ($headerTransparent == '1') ? 'background: transparent !important;' : '' ?>">
        <nav class="navbar navbar-expand-lg navbar-light" style="padding: <?= (int)$headerPadding ?>px 0;">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?= SITE_URL ?>">
                    <?php if ($logoUrl): ?>
                        <img src="<?= safe_html($logoUrl) ?>" alt="<?= safe_html($siteTitle) ?>" style="width: <?= (int)$logoWidth ?>px; height: auto;" class="me-2">
                    <?php else: ?>
                        <i class="fas fa-rocket text-primary me-2"></i>
                    <?php endif; ?>
                    <span class="fw-bold"><?= safe_html($siteTitle) ?></span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <?php if (!empty($menuItems)): ?>
                        <ul class="navbar-nav ms-auto menu-style-<?= htmlspecialchars($menuStyle) ?>" style="gap: <?= (int)$menuSpacing ?>px;">
                            <?php foreach ($menuItems as $item): ?>
                                <?php
                                $children = $menuManager->getMenuItems($mainMenu['id'], $item['id']);
                                $hasChildren = !empty($children);
                                $isActive = (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === parse_url($item['url'], PHP_URL_PATH));
                                ?>
                                <li class="nav-item<?= $hasChildren ? ' dropdown' : '' ?>">
                                    <?php if ($hasChildren): ?>
                                        <a class="nav-link dropdown-toggle<?= $isActive ? ' active' : '' ?>" 
                                           href="<?= safe_html($item['url']) ?>" 
                                           id="navbarDropdown<?= $item['id'] ?>" 
                                           role="button" 
                                           data-bs-toggle="dropdown">
                                            <?php if ($item['icon']): ?>
                                                <i class="<?= safe_html($item['icon']) ?> me-1"></i>
                                            <?php endif; ?>
                                            <?= safe_html($item['title']) ?>
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown<?= $item['id'] ?>">
                                            <?php foreach ($children as $child): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?= safe_html($child['url']) ?>">
                                                        <?php if ($child['icon']): ?>
                                                            <i class="<?= safe_html($child['icon']) ?> me-1"></i>
                                                        <?php endif; ?>
                                                        <?= safe_html($child['title']) ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <a class="nav-link<?= $isActive ? ' active' : '' ?>" 
                                           href="<?= safe_html($item['url']) ?>"
                                           style="font-size: <?= (int)$menuFontSize ?>px; font-weight: <?= (int)$menuFontWeight ?>;"
                                           <?= $item['target'] !== '_self' ? 'target="' . safe_html($item['target']) . '"' : '' ?>>
                                            <?php if ($item['icon']): ?>
                                                <i class="<?= safe_html($item['icon']) ?> me-1"></i>
                                            <?php endif; ?>
                                            <?= safe_html($item['title']) ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= SITE_URL ?>">Головна</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= SITE_URL ?>/about">Про нас</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= SITE_URL ?>/contact">Контакти</a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <?php doHook('theme_after_header'); ?>
    
    <!-- Main Content -->
    <main>
        <?php doHook('theme_before_content'); ?>
        
        <!-- Hero Section -->
        <section class="hero-section text-white">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h1 class="display-4 fw-bold mb-4">
                            <?= safe_html($themeManager->getSetting('hero_title', 'Ласкаво просимо до Landing CMS')) ?>
                        </h1>
                        <p class="lead mb-5">
                            <?= safe_html($themeManager->getSetting('hero_description', 'Сучасна система управління контентом з потужним функціоналом')) ?>
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="<?= safe_html($themeManager->getSetting('hero_button_url', '#')) ?>" 
                               class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-right me-2"></i>
                                <?= safe_html($themeManager->getSetting('hero_button_text', 'Дізнатися більше')) ?>
                            </a>
                            <a href="#features" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-info-circle me-2"></i>
                                Детальніше
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center mt-5 mt-lg-0">
                        <i class="fas fa-rocket"></i>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Features Section -->
        <section id="features" class="features-section">
            <div class="container">
                <div class="row text-center mb-5">
                    <div class="col-12">
                        <h2 class="display-5 fw-bold mb-3">
                            <?= safe_html($themeManager->getSetting('features_title', 'Наші можливості')) ?>
                        </h2>
                        <p class="text-muted fs-5">
                            <?= safe_html($themeManager->getSetting('features_description', 'Всі функції, які вам потрібні для успішного веб-сайту')) ?>
                        </p>
                    </div>
                </div>
                
                <div class="row g-4">
                    <?php
                    $features = [
                        [
                            'icon' => 'fas fa-puzzle-piece',
                            'title' => 'Плагіни',
                            'description' => 'Розширюйте функціонал за допомогою плагінів'
                        ],
                        [
                            'icon' => 'fas fa-palette',
                            'title' => 'Теми',
                            'description' => 'Змінюйте дизайн одним кліком'
                        ],
                        [
                            'icon' => 'fas fa-images',
                            'title' => 'Медіа',
                            'description' => 'Управління всіма вашими файлами'
                        ],
                        [
                            'icon' => 'fas fa-bars',
                            'title' => 'Меню',
                            'description' => 'Гнучке управління навігацією'
                        ],
                        [
                            'icon' => 'fas fa-cog',
                            'title' => 'Налаштування',
                            'description' => 'Повний контроль над сайтом'
                        ],
                        [
                            'icon' => 'fas fa-shield-alt',
                            'title' => 'Безпека',
                            'description' => 'Захист вашого контенту'
                        ]
                    ];
                    
                    // Хук для модификации features
                    $features = doHook('theme_features', $features);
                    
                    foreach ($features as $feature):
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body text-center">
                                    <div class="feature-icon mb-3">
                                        <i class="<?= safe_html($feature['icon']) ?> fa-3x"></i>
                                    </div>
                                    <h4 class="card-title"><?= safe_html($feature['title']) ?></h4>
                                    <p class="card-text"><?= safe_html($feature['description']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Content Section -->
        <section class="content-section py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-5">
                                <h2 class="mb-4"><?= safe_html($themeManager->getSetting('content_title', 'Основний контент')) ?></h2>
                                <div class="content">
                                    <?= doHook('theme_content', '<p class="lead">Це тестова тема Bootstrap для демонстрації функціоналу CMS.</p>') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Sidebar -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Інформація</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    <strong>Сайт:</strong> <?= safe_html($siteTitle) ?>
                                </p>
                                <p class="mb-2">
                                    <strong>CMS:</strong> Landing CMS
                                </p>
                                <p class="mb-0">
                                    <strong>Тема:</strong> <?= safe_html($themeManager->getActiveTheme()['name'] ?? 'Default') ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php doHook('theme_sidebar'); ?>
                    </div>
                </div>
            </div>
        </section>
        
        <?php doHook('theme_after_content'); ?>
    </main>
    
    <!-- Footer -->
    <footer class="mt-5">
        <div class="container">
            <div class="row">
                <?php
                $footerCols = (int)$footerColumns;
                $colClass = $footerCols > 0 ? 'col-md-' . (12 / $footerCols) : 'col-md-4';
                ?>
                
                <?php if ($footerCols >= 1): ?>
                <div class="<?= $colClass ?> mb-4">
                    <h5><?= safe_html($siteTitle) ?></h5>
                    <p class="text-muted"><?= safe_html($siteDescription) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($footerCols >= 2): ?>
                <div class="<?= $colClass ?> mb-4">
                    <h5>Швидкі посилання</h5>
                    <ul class="list-unstyled">
                        <?php if (!empty($menuItems)): ?>
                            <?php foreach (array_slice($menuItems, 0, 5) as $item): ?>
                                <li class="mb-2">
                                    <a href="<?= safe_html($item['url']) ?>" class="text-decoration-none">
                                        <?= safe_html($item['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($footerCols >= 3): ?>
                <div class="<?= $colClass ?> mb-4">
                    <h5>Контакти</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?= safe_html(getSetting('site_email', 'info@example.com')) ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <?= safe_html(getSetting('site_phone', '+380 XX XXX XX XX')) ?>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($footerCols >= 4): ?>
                <div class="<?= $colClass ?> mb-4">
                    <h5>Інформація</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">Про нас</a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">Політика конфіденційності</a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">Умови використання</a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <hr class="bg-secondary">
            
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">
                        <?= safe_html($footerCopyright) ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <?php doHook('theme_before_footer'); ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Theme JS -->
    <script src="<?= $themeManager->getThemeUrl() ?>script.js"></script>
    
    <?php doHook('theme_footer'); ?>
</body>
</html>
