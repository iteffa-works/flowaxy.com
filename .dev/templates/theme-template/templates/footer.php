<?php
/**
 * Шаблон футера теми
 */
?>
    <footer>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName ?? 'Flowaxy CMS') ?></p>
    </footer>
    
    <script src="<?= UrlHelper::theme('assets/js/main.js') ?>"></script>
</body>
</html>

