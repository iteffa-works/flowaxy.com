<?php
/**
 * Шаблон сторінки документації движка
 */
?>

<!-- Уведомлення -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <!-- Структура проекту -->
        <div class="content-section mb-4">
            <div class="content-section-header">
                <span><i class="fas fa-folder-open me-2"></i><?= htmlspecialchars($documentation['structure']['title']) ?></span>
            </div>
            <div class="content-section-body">
                <?php foreach ($documentation['structure']['sections'] as $section): ?>
                    <div class="mb-4">
                        <h5 class="mb-2"><?= htmlspecialchars($section['title']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($section['description']) ?></p>
                        <ul class="mb-0">
                            <?php foreach ($section['items'] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Основні класи -->
        <div class="content-section mb-4">
            <div class="content-section-header">
                <span><i class="fas fa-code me-2"></i><?= htmlspecialchars($documentation['classes']['title']) ?></span>
            </div>
            <div class="content-section-body">
                <?php foreach ($documentation['classes']['sections'] as $section): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-2"><?= htmlspecialchars($section['title']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($section['description']) ?></p>
                        <?php if (!empty($section['methods'])): ?>
                            <ul class="mb-0">
                                <?php foreach ($section['methods'] as $method): ?>
                                    <li><code><?= htmlspecialchars($method) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Системні модулі -->
        <div class="content-section mb-4">
            <div class="content-section-header">
                <span><i class="fas fa-cube me-2"></i><?= htmlspecialchars($documentation['modules']['title']) ?></span>
            </div>
            <div class="content-section-body">
                <?php foreach ($documentation['modules']['sections'] as $section): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-2"><?= htmlspecialchars($section['title']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($section['description']) ?></p>
                        <?php if (!empty($section['methods'])): ?>
                            <ul class="mb-0">
                                <?php foreach ($section['methods'] as $method): ?>
                                    <li><code><?= htmlspecialchars($method) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Система хуків -->
        <div class="content-section mb-4">
            <div class="content-section-header">
                <span><i class="fas fa-link me-2"></i><?= htmlspecialchars($documentation['hooks']['title']) ?></span>
            </div>
            <div class="content-section-body">
                <p class="text-muted mb-3"><?= htmlspecialchars($documentation['hooks']['description']) ?></p>
                
                <?php foreach ($documentation['hooks']['sections'] as $section): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-2"><?= htmlspecialchars($section['title']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($section['description']) ?></p>
                        
                        <?php if (!empty($section['code'])): ?>
                            <pre class="bg-dark text-light p-3 rounded"><code><?= htmlspecialchars($section['code']) ?></code></pre>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['items'])): ?>
                            <ul class="mb-0">
                                <?php foreach ($section['items'] as $item): ?>
                                    <li><code><?= htmlspecialchars($item) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- API для розробників -->
        <div class="content-section mb-4">
            <div class="content-section-header">
                <span><i class="fas fa-book me-2"></i><?= htmlspecialchars($documentation['api']['title']) ?></span>
            </div>
            <div class="content-section-body">
                <?php foreach ($documentation['api']['sections'] as $section): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-2"><?= htmlspecialchars($section['title']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($section['description']) ?></p>
                        
                        <?php if (!empty($section['code'])): ?>
                            <pre class="bg-dark text-light p-3 rounded"><code><?= htmlspecialchars($section['code']) ?></code></pre>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['items'])): ?>
                            <ul class="mb-0">
                                <?php foreach ($section['items'] as $item): ?>
                                    <li><code><?= htmlspecialchars($item) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

