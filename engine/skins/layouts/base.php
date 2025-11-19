<?php
/**
 * Базовый шаблон админки
 * Содержит общую структуру HTML
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= SecurityHelper::csrfToken() ?>">
    <title><?= $pageTitle ?? 'Flowaxy CMS - Админ-панель' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= UrlHelper::admin('assets/images/brand/favicon.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= UrlHelper::admin('assets/images/brand/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= UrlHelper::admin('assets/images/brand/favicon.png') ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Compatibility Fixes (после Bootstrap для переопределения) -->
    <link href="<?= UrlHelper::admin('assets/styles/css-fixes.css') ?>?v=<?= time() ?>" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="<?= UrlHelper::admin('assets/styles/font-awesome/css/all.min.css') ?>" rel="stylesheet">
    
    <!-- Основные стили админки -->
    <link href="<?= UrlHelper::admin('assets/styles/flowaxy.css') ?>?v=<?= time() ?>" rel="stylesheet">
    
    <?php if (!empty($additionalCSS)): ?>
        <!-- Дополнительные CSS файлы -->
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <div class="admin-content-container">
                    <?php include __DIR__ . '/../components/page-header.php'; ?>
                    
                    <!-- Основной контент страницы -->
    <?php 
    $templateFile = __DIR__ . '/../templates/' . ($templateName ?? 'dashboard') . '.php';
    if (file_exists($templateFile)) {
        include $templateFile;
    }
    ?>
                </div>
            </main>
        </div>
    </div>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>
    <?php include __DIR__ . '/../components/notifications.php'; ?>
    <?php include __DIR__ . '/../components/scripts.php'; ?>
    
    <?php if (!empty($additionalJS)): ?>
        <!-- Дополнительные JS файлы -->
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

