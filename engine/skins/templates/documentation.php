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
        
        <!-- API методи модулів -->
        <?php if (!empty($modulesApi)): ?>
        <div class="content-section mb-4">
            <div class="content-section-header">
                <span><i class="fas fa-code me-2"></i>API методи модулів</span>
            </div>
            <div class="content-section-body">
                <?php foreach ($modulesApi as $moduleName => $moduleData): ?>
                    <div class="module-api mb-4 pb-4 border-bottom">
                        <h5 class="mb-3">
                            <i class="fas fa-cube me-2"></i>
                            <?= htmlspecialchars($moduleName) ?>
                            <?php if (!empty($moduleData['info']['version'])): ?>
                                <span class="badge bg-secondary ms-2">v<?= htmlspecialchars($moduleData['info']['version']) ?></span>
                            <?php endif; ?>
                        </h5>
                        
                        <?php if (!empty($moduleData['info']['description'])): ?>
                            <p class="text-muted mb-3"><?= htmlspecialchars($moduleData['info']['description']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($moduleData['api_methods'])): ?>
                            <h6 class="mb-2"><i class="fas fa-list me-2"></i>Документовані API методи:</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%;">Метод</th>
                                            <th>Опис</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($moduleData['api_methods'] as $method => $description): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars($method) ?></code></td>
                                                <td><?= htmlspecialchars($description) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($moduleData['public_methods'])): ?>
                            <h6 class="mb-2"><i class="fas fa-code me-2"></i>Публічні методи:</h6>
                            <?php foreach ($moduleData['public_methods'] as $methodName => $methodInfo): ?>
                                <div class="method-info mb-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-start mb-2">
                                        <code class="text-primary me-2"><?= htmlspecialchars($methodName) ?></code>
                                        <span class="badge bg-info"><?= htmlspecialchars($methodInfo['return_type']) ?></span>
                                    </div>
                                    <p class="mb-2 text-muted small"><?= htmlspecialchars($methodInfo['description']) ?></p>
                                    
                                    <?php if (!empty($methodInfo['parameters'])): ?>
                                        <div class="parameters mt-2">
                                            <strong class="small">Параметри:</strong>
                                            <ul class="mb-0 mt-1 small">
                                                <?php foreach ($methodInfo['parameters'] as $param): ?>
                                                    <li>
                                                        <code><?= htmlspecialchars($param['name']) ?></code>
                                                        <span class="text-muted">(<?= htmlspecialchars($param['type']) ?>)</span>
                                                        <?php if ($param['optional']): ?>
                                                            <span class="badge bg-secondary">optional</span>
                                                            <?php if ($param['default'] !== null): ?>
                                                                <span class="text-muted">= <?= htmlspecialchars(var_export($param['default'], true)) ?></span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

