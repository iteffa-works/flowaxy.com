<?php
/**
 * Компонент заголовка страницы
 */
?>
<?php if (!empty($pageHeaderTitle)): ?>
<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="page-title-section">
                <?php if (!empty($pageHeaderIcon)): ?>
                    <div class="page-icon">
                        <i class="<?= $pageHeaderIcon ?>"></i>
                    </div>
                <?php endif; ?>
                <div class="page-title-content">
                    <h1><?= htmlspecialchars($pageHeaderTitle) ?></h1>
                    <?php if (!empty($pageHeaderDescription)): ?>
                        <p class="page-description"><?= htmlspecialchars($pageHeaderDescription) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($pageHeaderButtons)): ?>
                <div class="page-actions">
                    <?= $pageHeaderButtons ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
