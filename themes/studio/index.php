<?php
/**
 * Главный шаблон темы Studio
 * SCSS-тема с современным дизайном
 */

$themeManager = themeManager();
$menuManager = menuManager();
$siteTitle = getSetting('site_title', 'Web Studio');
$siteDescription = getSetting('site_description', 'Сучасна веб-студія');

// Получаем настройки темы
$logoUrl = $themeManager->getSetting('logo', '');
$logoWidth = $themeManager->getSetting('logo_width', '150');
$primaryColor = $themeManager->getSetting('primary_color', '#000000');
$secondaryColor = $themeManager->getSetting('secondary_color', '#666666');
$backgroundColor = $themeManager->getSetting('background_color', '#ffffff');
$textColor = $themeManager->getSetting('text_color', '#000000');

$mainMenu = $menuManager->getMenu('primary');
$menuItems = $mainMenu ? $menuManager->getMenuItems($mainMenu['id']) : [];

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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme Styles (SCSS compiled or regular CSS) -->
    <link rel="stylesheet" href="<?= $themeManager->getStylesheetUrl() ?>">
    
    <?php doHook('theme_head'); ?>
</head>
<body>
    <?php doHook('theme_body_start'); ?>
    
    <!-- Header -->
    <header class="site-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="<?= SITE_URL ?>">
                    <?php if ($logoUrl): ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($siteTitle) ?>" style="max-width: <?= (int)$logoWidth ?>px;">
                    <?php else: ?>
                        <span><?= htmlspecialchars($siteTitle) ?></span>
                    <?php endif; ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="mainNav">
                    <?php if (!empty($menuItems)): ?>
                        <ul class="navbar-nav ms-auto">
                            <?php foreach ($menuItems as $item): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= htmlspecialchars($item['url'] ?? '#') ?>">
                                        <?= htmlspecialchars($item['title'] ?? '') ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="site-main">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1><?= htmlspecialchars($siteTitle) ?></h1>
                    <p class="lead"><?= htmlspecialchars($siteDescription) ?></p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>. Всі права захищені.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php doHook('theme_body_end'); ?>
</body>
</html>
