<?php
/**
 * Базовый шаблон для плагинов
 * Аналогичен base.php, но использует кастомный шаблон из переменной $customTemplateFile
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Landing CMS - Админ-панель' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="<?= adminUrl('styles/font-awesome/css/all.min.css') ?>" rel="stylesheet">
    
    <!-- Основные стили админки -->
    <link href="<?= adminUrl('styles/flowaxy.css') ?>?v=<?= time() ?>" rel="stylesheet">
    
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
                    
                    <!-- Подключаем кастомный шаблон плагина -->
                    <?php if (isset($customTemplateFile) && file_exists($customTemplateFile)): ?>
                        <?php include $customTemplateFile; ?>
                    <?php else: ?>
                        <div class="alert alert-danger">Template not found</div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>
    <?php include __DIR__ . '/../components/scripts.php'; ?>
    
    <?php if (!empty($additionalJS)): ?>
        <!-- Дополнительные JS файлы -->
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
