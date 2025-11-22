<?php
/**
 * Шаблон хедера теми
 */
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName ?? 'Flowaxy CMS') ?></title>
    <link rel="stylesheet" href="<?= UrlHelper::theme('assets/css/style.css') ?>">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($siteName ?? 'Flowaxy CMS') ?></h1>
    </header>

