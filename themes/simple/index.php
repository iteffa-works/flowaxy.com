<?php
/**
 * Главный шаблон темы Simple
 * Минималистичная тема без поддержки кастоматизации
 */

$themeManager = themeManager();
$menuManager = menuManager();
$siteTitle = getSetting('site_title', 'Landing CMS');
$siteDescription = getSetting('site_description', 'Сучасна CMS система');
$logoUrl = getSetting('site_logo', '');

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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $themeManager->getThemeUrl() ?>style.css">
    
    <?php doHook('theme_head'); ?>
</head>
<body>
    <?php doHook('theme_body_start'); ?>
    
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="<?= SITE_URL ?>">
                    <?php if ($logoUrl): ?>
                        <img src="<?= safe_html($logoUrl) ?>" alt="<?= safe_html($siteTitle) ?>" height="40" class="me-2">
                    <?php else: ?>
                        <i class="fas fa-home text-primary me-2"></i>
                    <?php endif; ?>
                    <span class="fw-bold"><?= safe_html($siteTitle) ?></span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <?php if (!empty($menuItems)): ?>
                        <ul class="navbar-nav ms-auto">
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
                                            <?= safe_html($item['title']) ?>
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown<?= $item['id'] ?>">
                                            <?php foreach ($children as $child): ?>
                                                <li><a class="dropdown-item" href="<?= safe_html($child['url']) ?>"><?= safe_html($child['title']) ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= safe_html($item['url']) ?>">
                                            <?= safe_html($item['title']) ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="mb-4"><?= safe_html($siteTitle) ?></h1>
                    <p class="lead"><?= safe_html($siteDescription) ?></p>
                    
                    <?php doHook('theme_content'); ?>
                </div>
                <div class="col-lg-4">
                    <?php doHook('theme_sidebar'); ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?= date('Y') ?> <?= safe_html($siteTitle) ?>. Всі права захищені.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">Тема: Simple</p>
                </div>
            </div>
        </div>
    </footer>
    
    <?php doHook('theme_before_footer'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $themeManager->getThemeUrl() ?>script.js"></script>
    
    <?php doHook('theme_footer'); ?>
</body>
</html>

