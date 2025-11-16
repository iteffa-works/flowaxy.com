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

<!-- Навігація -->
<div class="doc-nav mb-4">
    <ul class="nav nav-tabs border-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-structure" data-bs-toggle="tab" data-bs-target="#content-structure" type="button" role="tab">
                <i class="fas fa-folder-open me-2"></i>Структура
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-classes" data-bs-toggle="tab" data-bs-target="#content-classes" type="button" role="tab">
                <i class="fas fa-code me-2"></i>Класи
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-modules" data-bs-toggle="tab" data-bs-target="#content-modules" type="button" role="tab">
                <i class="fas fa-cube me-2"></i>Модулі
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-hooks" data-bs-toggle="tab" data-bs-target="#content-hooks" type="button" role="tab">
                <i class="fas fa-link me-2"></i>Хуки
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-api" data-bs-toggle="tab" data-bs-target="#content-api" type="button" role="tab">
                <i class="fas fa-book me-2"></i>API
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-plugins" data-bs-toggle="tab" data-bs-target="#content-plugins" type="button" role="tab">
                <i class="fas fa-puzzle-piece me-2"></i>Плагіни
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-themes" data-bs-toggle="tab" data-bs-target="#content-themes" type="button" role="tab">
                <i class="fas fa-palette me-2"></i>Теми
            </button>
        </li>
        <?php if (!empty($modulesApi)): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-modules-api" data-bs-toggle="tab" data-bs-target="#content-modules-api" type="button" role="tab">
                <i class="fas fa-code me-2"></i>API модулів
            </button>
        </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Контент -->
<div class="tab-content">
    <!-- Структура проекту -->
    <div class="tab-pane fade show active" id="content-structure" role="tabpanel">
        <div class="row g-3">
            <?php foreach ($documentation['structure']['sections'] as $section): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="doc-card">
                        <div class="doc-card-header">
                            <span class="doc-card-icon">📁</span>
                            <h6 class="doc-card-title mb-0"><?= htmlspecialchars($section['title']) ?></h6>
                        </div>
                        <div class="doc-card-body">
                            <p class="doc-card-desc"><?= htmlspecialchars($section['description']) ?></p>
                            <ul class="doc-list">
                                <?php foreach ($section['items'] as $item): ?>
                                    <li><?= htmlspecialchars($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Основні класи -->
    <div class="tab-pane fade" id="content-classes" role="tabpanel">
        <div class="doc-section-list">
            <?php foreach ($documentation['classes']['sections'] as $section): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-code text-primary me-2"></i>
                            <?= htmlspecialchars($section['title']) ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <p class="text-muted mb-3"><?= htmlspecialchars($section['description']) ?></p>
                        <?php if (!empty($section['methods'])): ?>
                            <div class="doc-methods">
                                <?php foreach ($section['methods'] as $method): ?>
                                    <div class="doc-method">
                                        <code><?= htmlspecialchars($method) ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Системні модулі -->
    <div class="tab-pane fade" id="content-modules" role="tabpanel">
        <div class="doc-section-list">
            <?php foreach ($documentation['modules']['sections'] as $section): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cube text-primary me-2"></i>
                            <?= htmlspecialchars($section['title']) ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <p class="text-muted mb-3"><?= htmlspecialchars($section['description']) ?></p>
                        <?php if (!empty($section['methods'])): ?>
                            <div class="doc-methods">
                                <?php foreach ($section['methods'] as $method): ?>
                                    <div class="doc-method">
                                        <code><?= htmlspecialchars($method) ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Система хуків -->
    <div class="tab-pane fade" id="content-hooks" role="tabpanel">
        <div class="doc-intro mb-4">
            <p class="text-muted"><?= htmlspecialchars($documentation['hooks']['description']) ?></p>
        </div>
        
        <div class="doc-section-list">
            <?php foreach ($documentation['hooks']['sections'] as $section): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-link text-primary me-2"></i>
                            <?= htmlspecialchars($section['title']) ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <p class="text-muted mb-3"><?= htmlspecialchars($section['description']) ?></p>
                        
                        <?php if (!empty($section['code'])): ?>
                            <div class="doc-code">
                                <pre><code><?= htmlspecialchars($section['code']) ?></code></pre>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['items'])): ?>
                            <div class="doc-items">
                                <?php foreach ($section['items'] as $item): ?>
                                    <span class="doc-badge"><code><?= htmlspecialchars($item) ?></code></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- API для розробників -->
    <div class="tab-pane fade" id="content-api" role="tabpanel">
        <div class="doc-section-list">
            <?php foreach ($documentation['api']['sections'] as $section): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-book text-primary me-2"></i>
                            <?= htmlspecialchars($section['title']) ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <p class="text-muted mb-3"><?= htmlspecialchars($section['description']) ?></p>
                        
                        <?php if (!empty($section['code'])): ?>
                            <div class="doc-code">
                                <pre><code><?= htmlspecialchars($section['code']) ?></code></pre>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['items'])): ?>
                            <div class="doc-items">
                                <?php foreach ($section['items'] as $item): ?>
                                    <div class="doc-item">
                                        <code><?= htmlspecialchars($item) ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Створення плагінів -->
    <div class="tab-pane fade" id="content-plugins" role="tabpanel">
        <div class="doc-intro mb-4">
            <h4 class="mb-2"><?= htmlspecialchars($documentation['plugins']['title']) ?></h4>
        </div>
        
        <div class="doc-section-list">
            <?php foreach ($documentation['plugins']['sections'] as $section): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-puzzle-piece text-primary me-2"></i>
                            <?= htmlspecialchars($section['title']) ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <p class="text-muted mb-3"><?= htmlspecialchars($section['description']) ?></p>
                        
                        <?php if (!empty($section['code'])): ?>
                            <div class="doc-code">
                                <pre><code><?= htmlspecialchars($section['code']) ?></code></pre>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['items'])): ?>
                            <div class="doc-items">
                                <?php foreach ($section['items'] as $item): ?>
                                    <div class="doc-item">
                                        <?= htmlspecialchars($item) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Створення тем -->
    <div class="tab-pane fade" id="content-themes" role="tabpanel">
        <div class="doc-intro mb-4">
            <h4 class="mb-2"><?= htmlspecialchars($documentation['themes']['title']) ?></h4>
        </div>
        
        <div class="doc-section-list">
            <?php foreach ($documentation['themes']['sections'] as $section): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-palette text-primary me-2"></i>
                            <?= htmlspecialchars($section['title']) ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <p class="text-muted mb-3"><?= htmlspecialchars($section['description']) ?></p>
                        
                        <?php if (!empty($section['code'])): ?>
                            <div class="doc-code">
                                <pre><code><?= htmlspecialchars($section['code']) ?></code></pre>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['items'])): ?>
                            <div class="doc-items">
                                <?php foreach ($section['items'] as $item): ?>
                                    <div class="doc-item">
                                        <?= htmlspecialchars($item) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- API методи модулів -->
    <?php if (!empty($modulesApi)): ?>
    <div class="tab-pane fade" id="content-modules-api" role="tabpanel">
        <div class="doc-section-list">
            <?php foreach ($modulesApi as $moduleName => $moduleData): ?>
                <div class="doc-section-item">
                    <div class="doc-section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cube text-primary me-2"></i>
                            <?= htmlspecialchars($moduleName) ?>
                            <?php if (!empty($moduleData['info']['version'])): ?>
                                <span class="badge bg-secondary ms-2">v<?= htmlspecialchars($moduleData['info']['version']) ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="doc-section-body">
                        <?php if (!empty($moduleData['info']['description'])): ?>
                            <p class="text-muted mb-3"><?= htmlspecialchars($moduleData['info']['description']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($moduleData['api_methods'])): ?>
                            <div class="mb-4">
                                <h6 class="mb-3 fw-semibold">Документовані API методи</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%;">Метод</th>
                                                <th>Опис</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($moduleData['api_methods'] as $method => $description): ?>
                                                <tr>
                                                    <td><code class="doc-inline-code"><?= htmlspecialchars($method) ?></code></td>
                                                    <td><?= htmlspecialchars($description) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($moduleData['public_methods'])): ?>
                            <div>
                                <h6 class="mb-3 fw-semibold">Публічні методи</h6>
                                <div class="accordion doc-accordion" id="methods-<?= strtolower($moduleName) ?>">
                                    <?php $methodIndex = 0; foreach ($moduleData['public_methods'] as $methodName => $methodInfo): ?>
                                        <div class="accordion-item border-top-0 border-start-0 border-end-0 rounded-0">
                                            <h2 class="accordion-header" id="heading-<?= strtolower($moduleName) ?>-<?= $methodIndex ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= strtolower($moduleName) ?>-<?= $methodIndex ?>">
                                                    <code class="doc-method-name me-2"><?= htmlspecialchars($methodName) ?></code>
                                                    <span class="badge bg-info ms-auto"><?= htmlspecialchars($methodInfo['return_type']) ?></span>
                                                </button>
                                            </h2>
                                            <div id="collapse-<?= strtolower($moduleName) ?>-<?= $methodIndex ?>" class="accordion-collapse collapse" data-bs-parent="#methods-<?= strtolower($moduleName) ?>">
                                                <div class="accordion-body pt-3">
                                                    <p class="text-muted mb-3"><?= htmlspecialchars($methodInfo['description']) ?></p>
                                                    
                                                    <?php if (!empty($methodInfo['parameters'])): ?>
                                                        <div class="doc-parameters">
                                                            <strong class="d-block mb-2">Параметри:</strong>
                                                            <ul class="list-unstyled mb-0">
                                                                <?php foreach ($methodInfo['parameters'] as $param): ?>
                                                                    <li class="mb-2">
                                                                        <code class="doc-inline-code"><?= htmlspecialchars($param['name']) ?></code>
                                                                        <span class="text-muted">(<?= htmlspecialchars($param['type']) ?>)</span>
                                                                        <?php if ($param['optional']): ?>
                                                                            <span class="badge bg-secondary ms-1">optional</span>
                                                                            <?php if ($param['default'] !== null): ?>
                                                                                <span class="text-muted ms-1">= <?= htmlspecialchars(var_export($param['default'], true)) ?></span>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php $methodIndex++; endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>


<style>
/* Flat дизайн */
.doc-nav {
    border-bottom: 2px solid #e9ecef;
}

.doc-nav .nav-tabs {
    border-bottom: none;
    margin-bottom: 0;
}

.doc-nav .nav-link {
    border: none;
    border-radius: 0;
    padding: 0.875rem 1.25rem;
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9375rem;
    border-bottom: 3px solid transparent;
    transition: all 0.15s ease;
    background: transparent;
}

.doc-nav .nav-link:hover {
    color: #0d6efd;
    background: #f8f9fa;
    border-bottom-color: #0d6efd;
}

.doc-nav .nav-link.active {
    color: #0d6efd;
    background: transparent;
    border-bottom-color: #0d6efd;
    font-weight: 600;
}

.doc-nav .nav-link i {
    font-size: 0.875rem;
}

/* Картки структури */
.doc-card {
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: white;
    height: 100%;
}

.doc-card-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.doc-card-icon {
    font-size: 1.25rem;
}

.doc-card-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #212529;
}

.doc-card-body {
    padding: 1rem;
}

.doc-card-desc {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-bottom: 0.75rem;
}

/* Списки */
.doc-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.doc-list li {
    padding: 0.375rem 0;
    padding-left: 1.25rem;
    position: relative;
    font-size: 0.8125rem;
    color: #495057;
    line-height: 1.5;
}

.doc-list li:before {
    content: "→";
    position: absolute;
    left: 0;
    color: #0d6efd;
    font-weight: bold;
}

/* Секції */
.doc-section-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.doc-section-item {
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: white;
}

.doc-section-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}

.doc-section-header h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
}

.doc-section-body {
    padding: 1.25rem;
}

/* Методи */
.doc-methods {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.doc-method {
    padding: 0.5rem 0.75rem;
    background: #f8f9fa;
    border-left: 3px solid #0d6efd;
    font-size: 0.875rem;
}

.doc-method code {
    font-size: 0.875rem;
    color: #0d6efd;
    background: transparent;
    padding: 0;
}

.doc-method-name {
    font-size: 0.9375rem;
    color: #0d6efd;
    font-weight: 600;
}

.doc-inline-code {
    font-size: 0.8125rem;
    color: #0d6efd;
    background: #f8f9fa;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
}

/* Блоки коду */
.doc-code {
    background: #1e1e1e;
    border-radius: 4px;
    overflow: hidden;
    margin: 1rem 0;
}

.doc-code pre {
    margin: 0;
    padding: 1.25rem;
    background: transparent;
    color: #d4d4d4;
    font-size: 0.8125rem;
    line-height: 1.6;
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
}

.doc-code code {
    color: #d4d4d4;
    background: transparent;
    padding: 0;
    font-family: 'Courier New', monospace;
}

/* Елементи */
.doc-items {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.doc-item {
    padding: 0.375rem 0.625rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    font-size: 0.8125rem;
}

.doc-item code {
    font-size: 0.8125rem;
    color: #495057;
    background: transparent;
    padding: 0;
}

.doc-badge {
    display: inline-block;
    padding: 0.375rem 0.625rem;
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 3px;
    font-size: 0.8125rem;
}

.doc-badge code {
    font-size: 0.8125rem;
    color: #0d6efd;
    background: transparent;
    padding: 0;
}

/* Аккордеон */
.doc-accordion .accordion-item {
    border: 1px solid #e9ecef;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.doc-accordion .accordion-button {
    background: #f8f9fa;
    border: none;
    padding: 0.75rem 1rem;
    font-weight: 500;
    font-size: 0.875rem;
}

.doc-accordion .accordion-button:not(.collapsed) {
    background: #e7f3ff;
    color: #0d6efd;
    box-shadow: none;
}

.doc-accordion .accordion-button:focus {
    box-shadow: none;
    border: none;
}

.doc-accordion .accordion-body {
    padding: 1rem;
    background: white;
}

.doc-parameters {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 4px;
    border-left: 3px solid #0d6efd;
}

/* Підсвітка пошуку */
mark {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 2px;
    font-weight: 500;
}

/* Адаптивність */
@media (max-width: 991.98px) {
    .doc-nav .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .doc-nav .nav-link {
        white-space: nowrap;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 767.98px) {
    .doc-card-body,
    .doc-section-body {
        padding: 0.75rem;
    }
}
</style>
