<?php
/**
 * Головний шаблон теми
 * 
 * @package ThemeName
 * @version 1.0.0
 */

declare(strict_types=1);

// Перевірка, що файл викликається з CMS
if (!defined('FLOWAXY_CMS')) {
    die('Direct access denied');
}

// Отримання налаштувань теми
$themeManager = themeManager();
$themeSettings = $themeManager ? $themeManager->getSettings() : [];
$themeUrl = $themeManager ? $themeManager->getThemeUrl() : '';
$siteName = settingsManager()->get('site_name', 'Flowaxy CMS');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?></title>
    
    <!-- CSS теми -->
    <link rel="stylesheet" href="<?= htmlspecialchars("{$themeUrl}assets/css/style.css") ?>">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($siteName) ?></h1>
    </header>
    
    <main>
        <div class="container">
            <h2>Ласкаво просимо!</h2>
            <p>Це головна сторінка теми.</p>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?></p>
    </footer>
    
    <!-- JavaScript теми -->
    <script src="<?= htmlspecialchars("{$themeUrl}assets/js/main.js") ?>"></script>
</body>
</html>

