<?php
/**
 * Шаблон страницы настроек
 */
?>

<!-- Уведомления -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="save_settings" value="1">
    
    <div class="row">
        <!-- Основные настройки -->
        <div class="col-lg-8 mb-4">
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-cog me-2"></i>Основные настройки</span>
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="siteName" class="form-label">Название сайта</label>
                                <input type="text" class="form-control" id="siteName" 
                                       name="settings[site_name]" 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? 'Landing CMS') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="siteTagline" class="form-label">Слоган сайта</label>
                                <input type="text" class="form-control" id="siteTagline" 
                                       name="settings[site_tagline]" 
                                       value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="siteDescription" class="form-label">Описание сайта</label>
                        <textarea class="form-control" id="siteDescription" name="settings[site_description]" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="copyright" class="form-label">Копірайт (текст у футері)</label>
                        <input type="text" class="form-control" id="copyright" 
                               name="settings[copyright]" 
                               value="<?= htmlspecialchars($settings['copyright'] ?? '© 2025 Spokinoki - Усі права захищені') ?>"
                               placeholder="© 2025 Spokinoki - Усі права захищені">
                        <div class="form-text">Цей текст відображатиметься у футері сайту</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="adminEmail" class="form-label">Email администратора</label>
                                <input type="email" class="form-control" id="adminEmail" 
                                       name="settings[admin_email]" 
                                       value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sitePhone" class="form-label">Номер телефону (для хедера)</label>
                                <input type="text" class="form-control" id="sitePhone" 
                                       name="settings[site_phone]" 
                                       value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>"
                                       placeholder="+38 (096) 123-45-67">
                                <div class="form-text">Номер телефону відображатиметься в хедері сайту</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Часовой пояс</label>
                                <select class="form-select" id="timezone" name="settings[timezone]">
                                    <option value="Europe/Kiev" <?= ($settings['timezone'] ?? '') === 'Europe/Kiev' ? 'selected' : '' ?>>Киев (UTC+2)</option>
                                    <option value="Europe/Moscow" <?= ($settings['timezone'] ?? '') === 'Europe/Moscow' ? 'selected' : '' ?>>Москва (UTC+3)</option>
                                    <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SEO настройки -->
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-search me-2"></i>SEO настройки</span>
                </div>
                <div class="content-section-body">
                    <div class="mb-3">
                        <label for="metaKeywords" class="form-label">Ключевые слова</label>
                        <input type="text" class="form-control" id="metaKeywords" 
                               name="settings[meta_keywords]" 
                               value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>"
                               placeholder="ключевое слово, еще одно, третье">
                        <div class="form-text">Разделяйте ключевые слова запятыми</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="googleAnalytics" class="form-label">Google Analytics ID</label>
                        <input type="text" class="form-control" id="googleAnalytics" 
                               name="settings[google_analytics]" 
                               value="<?= htmlspecialchars($settings['google_analytics'] ?? '') ?>"
                               placeholder="G-XXXXXXXXXX">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Боковая панель -->
        <div class="col-lg-4 mb-4">
            <!-- Действия -->
            <div class="content-section mb-4">
                <div class="content-section-header">
                    <span><i class="fas fa-save me-2"></i>Действия</span>
                </div>
                <div class="content-section-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Сохранить настройки
                        </button>
                        <a href="settings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Отменить изменения
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Системная информация -->
            <div class="content-section mb-4">
                <div class="content-section-header">
                    <span><i class="fas fa-info-circle me-2"></i>Системная информация</span>
                </div>
                <div class="content-section-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Версия CMS:</span>
                            <span class="text-muted">1.0.0</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>PHP версия:</span>
                            <span class="text-muted"><?= PHP_VERSION ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Сервер:</span>
                            <span class="text-muted"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span>Время сервера:</span>
                            <span class="text-muted"><?= date('d.m.Y H:i') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Быстрые ссылки -->
            <div class="content-section">
                <div class="content-section-header">
                    <span><i class="fas fa-external-link-alt me-2"></i>Быстрые ссылки</span>
                </div>
                <div class="content-section-body">
                    <div class="d-grid gap-2">
                        <a href="../index.php" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-eye me-1"></i>Посмотреть сайт
                        </a>
                        <a href="system-status.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-heartbeat me-1"></i>Статус системы
                        </a>
                        <a href="analytics.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-chart-line me-1"></i>Аналитика
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Дополнительная информация -->
            <div class="content-section">
                <div class="content-section-body">
                    <div class="alert alert-info mb-0">
                        <h6><i class="fas fa-lightbulb me-2"></i>Совет</h6>
                        <p class="mb-0 small">
                            <strong>Логотип:</strong> Рекомендуемый размер 200x60px<br>
                            <strong>Favicon:</strong> Размер 32x32px или 16x16px
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
