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
$secondaryColor = $themeManager->getSetting('secondary_color', '#6c757d');
$siteTitle = getSetting('site_title', 'Landing CMS');
$siteDescription = getSetting('site_description', 'Сучасна CMS система');
$logoUrl = getSetting('site_logo', '');

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
    <!-- Custom Theme Styles -->
    <link rel="stylesheet" href="<?= $themeManager->getThemeUrl() ?>style.css">
    
    <!-- Dynamic Theme Colors -->
    <style>
        :root {
            --bs-primary: <?= safe_html($primaryColor) ?>;
            --bs-secondary: <?= safe_html($secondaryColor) ?>;
        }
        .bg-primary { background-color: <?= safe_html($primaryColor) ?> !important; }
        .text-primary { color: <?= safe_html($primaryColor) ?> !important; }
        .btn-primary { 
            background-color: <?= safe_html($primaryColor) ?>;
            border-color: <?= safe_html($primaryColor) ?>;
        }
        .btn-primary:hover {
            background-color: <?= safe_html($themeManager->getSetting('primary_color_hover', $primaryColor)) ?>;
            border-color: <?= safe_html($themeManager->getSetting('primary_color_hover', $primaryColor)) ?>;
        }
    </style>
    
    <?php doHook('theme_head'); ?>
</head>
<body>
    <?php doHook('theme_body_start'); ?>
    
    <!-- Header -->
    <header class="bg-white shadow-sm sticky-top">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?= SITE_URL ?>">
                    <?php if ($logoUrl): ?>
                        <img src="<?= safe_html($logoUrl) ?>" alt="<?= safe_html($siteTitle) ?>" height="40" class="me-2">
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
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><?= safe_html($siteTitle) ?></h5>
                    <p class="text-muted"><?= safe_html($siteDescription) ?></p>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Швидкі посилання</h5>
                    <ul class="list-unstyled">
                        <?php if (!empty($menuItems)): ?>
                            <?php foreach (array_slice($menuItems, 0, 5) as $item): ?>
                                <li class="mb-2">
                                    <a href="<?= safe_html($item['url']) ?>" class="text-muted text-decoration-none">
                                        <?= safe_html($item['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Контакти</h5>
                    <ul class="list-unstyled text-muted">
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
            </div>
            
            <hr class="bg-secondary">
            
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?= date('Y') ?> <?= safe_html($siteTitle) ?>. Всі права захищені.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        Powered by <a href="#" class="text-white text-decoration-none">Landing CMS</a>
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
