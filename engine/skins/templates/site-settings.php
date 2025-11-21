<?php
/**
 * Шаблон страницы настроек сайта
 */
?>

<!-- Уведомления -->
<?php
if (!empty($message)) {
    include __DIR__ . '/../components/alert.php';
    $type = $messageType ?? 'info';
    $dismissible = true;
}
?>

<form method="POST" class="settings-form">
    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
    <input type="hidden" name="save_settings" value="1">
    
    <div class="row g-3">
        <!-- Основные настройки -->
        <div class="col-12">
            <div class="card border-0 mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-cog me-2 text-primary"></i>Основні налаштування
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="adminEmail" class="form-label fw-medium small">Email адміністратора</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="adminEmail" 
                                   name="settings[admin_email]" 
                                   value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>"
                                   placeholder="admin@example.com">
                            <div class="form-text small">Email адреса для системних повідомлень</div>
                        </div>
                        <div class="col-md-6">
                            <label for="siteProtocol" class="form-label fw-medium small">Протокол сайту</label>
                            <select class="form-select" id="siteProtocol" name="settings[site_protocol]">
                                <option value="auto" <?= ($settings['site_protocol'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>Автоматично (визначається автоматично)</option>
                                <option value="https" <?= ($settings['site_protocol'] ?? '') === 'https' ? 'selected' : '' ?>>HTTPS (захищений)</option>
                                <option value="http" <?= ($settings['site_protocol'] ?? '') === 'http' ? 'selected' : '' ?>>HTTP (незахищений)</option>
                            </select>
                            <div class="form-text small">Протокол для URL сайту. "Автоматично" визначає протокол на основі запиту</div>
                        </div>
                        <div class="col-md-6">
                            <label for="timezone" class="form-label fw-medium small">Часовий пояс</label>
                            <select class="form-select" id="timezone" name="settings[timezone]">
                                <option value="Europe/Kyiv" <?= ($settings['timezone'] ?? 'Europe/Kyiv') === 'Europe/Kyiv' || ($settings['timezone'] ?? '') === 'Europe/Kiev' ? 'selected' : '' ?>>Київ (UTC+2)</option>
                                <option value="Europe/Moscow" <?= ($settings['timezone'] ?? '') === 'Europe/Moscow' ? 'selected' : '' ?>>Москва (UTC+3)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="Europe/London" <?= ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Лондон (UTC+0)</option>
                                <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>Нью-Йорк (UTC-5)</option>
                                <option value="Asia/Tokyo" <?= ($settings['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : '' ?>>Токіо (UTC+9)</option>
                            </select>
                            <div class="form-text small">Часовий пояс для відображення дат та часу</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Настройки кеша -->
        <div class="col-12">
            <div class="card border-0 mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-database me-2 text-primary"></i>Налаштування кешу
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="cacheEnabled" 
                                       name="settings[cache_enabled]" 
                                       value="1"
                                       <?= ($settings['cache_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium" for="cacheEnabled">
                                    Увімкнути кешування
                                </label>
                                <div class="form-text small">Кешування покращує продуктивність сайту</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="cacheDefaultTtl" class="form-label fw-medium small">Час життя кешу (секунди)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="cacheDefaultTtl" 
                                   name="settings[cache_default_ttl]" 
                                   value="<?= htmlspecialchars($settings['cache_default_ttl'] ?? '3600') ?>"
                                   min="60"
                                   step="60"
                                   placeholder="3600">
                            <div class="form-text small">Стандартний час життя кешу (за замовчуванням: 3600 сек = 1 година)</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <label class="form-label fw-medium small d-block mb-2">Автоматична очистка</label>
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="cacheAutoCleanup" 
                                       name="settings[cache_auto_cleanup]" 
                                       value="1"
                                       <?= ($settings['cache_auto_cleanup'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium" for="cacheAutoCleanup">
                                    Автоматична очистка застарілого кешу
                                </label>
                                <div class="form-text small">Автоматично видаляти прострочений кеш</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Настройки логирования -->
        <div class="col-12">
            <div class="card border-0 mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-file-alt me-2 text-primary"></i>Налаштування логування
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="loggingEnabled" 
                                       name="settings[logging_enabled]" 
                                       value="1"
                                       <?= ($settings['logging_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium" for="loggingEnabled">
                                    Увімкнути логування
                                </label>
                                <div class="form-text small">Зберігати логи системних подій та помилок</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="loggingLevel" class="form-label fw-medium small">Рівні логування</label>
                            <select class="form-select" id="loggingLevel" name="settings[logging_levels][]" multiple size="5">
                                <?php 
                                $selectedLevels = !empty($settings['logging_levels']) 
                                    ? (is_array($settings['logging_levels']) ? $settings['logging_levels'] : explode(',', $settings['logging_levels']))
                                    : ['INFO', 'WARNING', 'ERROR', 'CRITICAL'];
                                ?>
                                <option value="DEBUG" <?= in_array('DEBUG', $selectedLevels) ? 'selected' : '' ?>>DEBUG - Всі події</option>
                                <option value="INFO" <?= in_array('INFO', $selectedLevels) ? 'selected' : '' ?>>INFO - Інформаційні події</option>
                                <option value="WARNING" <?= in_array('WARNING', $selectedLevels) ? 'selected' : '' ?>>WARNING - Попередження</option>
                                <option value="ERROR" <?= in_array('ERROR', $selectedLevels) ? 'selected' : '' ?>>ERROR - Помилки</option>
                                <option value="CRITICAL" <?= in_array('CRITICAL', $selectedLevels) ? 'selected' : '' ?>>CRITICAL - Критичні помилки</option>
                            </select>
                            <div class="form-text small">Виберіть рівні для логування (утримуйте Ctrl/Cmd для множинного вибору)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Типи логування</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logToFile" name="settings[logging_types][]" value="file" 
                                       <?= in_array('file', $settings['logging_types'] ?? ['file']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="logToFile">
                                    Логування у файл
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logToErrorLog" name="settings[logging_types][]" value="error_log"
                                       <?= in_array('error_log', $settings['logging_types'] ?? []) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="logToErrorLog">
                                    Логування в error_log PHP
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logDbQueries" name="settings[logging_types][]" value="db_queries"
                                       <?= in_array('db_queries', $settings['logging_types'] ?? []) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="logDbQueries">
                                    Логування SQL запитів
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logDbErrors" name="settings[logging_types][]" value="db_errors"
                                       <?= in_array('db_errors', $settings['logging_types'] ?? ['db_errors']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="logDbErrors">
                                    Логування помилок БД
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logSlowQueries" name="settings[logging_types][]" value="slow_queries"
                                       <?= in_array('slow_queries', $settings['logging_types'] ?? ['slow_queries']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="logSlowQueries">
                                    Логування повільних запитів
                                </label>
                            </div>
                            <div class="form-text small">Виберіть типи логування для використання</div>
                        </div>
                        <div class="col-md-6">
                            <label for="loggingMaxFileSize" class="form-label fw-medium small">Максимальний розмір файлу (байти)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="loggingMaxFileSize" 
                                   name="settings[logging_max_file_size]" 
                                   value="<?= htmlspecialchars($settings['logging_max_file_size'] ?? '10485760') ?>"
                                   min="1048576"
                                   step="1048576"
                                   placeholder="10485760">
                            <div class="form-text small">Максимальний розмір файлу логу (за замовчуванням: 10 MB)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="loggingRetentionDays" class="form-label fw-medium small">Зберігати логи (днів)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="loggingRetentionDays" 
                                   name="settings[logging_retention_days]" 
                                   value="<?= htmlspecialchars($settings['logging_retention_days'] ?? '30') ?>"
                                   min="1"
                                   max="365"
                                   placeholder="30">
                            <div class="form-text small">Кількість днів зберігання логів (за замовчуванням: 30 днів)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="loggingRotationType" class="form-label fw-medium small">Тип ротації</label>
                            <select class="form-select" id="loggingRotationType" name="settings[logging_rotation_type]">
                                <option value="size" <?= ($settings['logging_rotation_type'] ?? 'size') === 'size' ? 'selected' : '' ?>>По розміру</option>
                                <option value="time" <?= ($settings['logging_rotation_type'] ?? 'size') === 'time' ? 'selected' : '' ?>>По часу</option>
                                <option value="both" <?= ($settings['logging_rotation_type'] ?? 'size') === 'both' ? 'selected' : '' ?>>По розміру та часу</option>
                            </select>
                            <div class="form-text small">Коли виконувати ротацію логів</div>
                        </div>
                        <div class="col-md-6" id="rotationTimeGroup" style="display: <?= in_array($settings['logging_rotation_type'] ?? 'size', ['time', 'both']) ? 'block' : 'none' ?>;">
                            <label for="loggingRotationTime" class="form-label fw-medium small">Ротація по часу</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="loggingRotationTime" 
                                       name="settings[logging_rotation_time]" 
                                       value="<?= htmlspecialchars($settings['logging_rotation_time'] ?? '24') ?>"
                                       min="1"
                                       placeholder="24">
                                <select class="form-select" id="loggingRotationTimeUnit" name="settings[logging_rotation_time_unit]" style="max-width: 120px;">
                                    <option value="hours" <?= ($settings['logging_rotation_time_unit'] ?? 'hours') === 'hours' ? 'selected' : '' ?>>Годин</option>
                                    <option value="days" <?= ($settings['logging_rotation_time_unit'] ?? 'hours') === 'days' ? 'selected' : '' ?>>Днів</option>
                                </select>
                            </div>
                            <div class="form-text small">Період ротації логів по часу</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rotationType = document.getElementById('loggingRotationType');
            const rotationTimeGroup = document.getElementById('rotationTimeGroup');
            
            if (rotationType && rotationTimeGroup) {
                rotationType.addEventListener('change', function() {
                    if (this.value === 'time' || this.value === 'both') {
                        rotationTimeGroup.style.display = 'block';
                    } else {
                        rotationTimeGroup.style.display = 'none';
                    }
                });
            }
        });
        </script>
        
        <!-- Кнопки действий -->
        <div class="col-12">
            <div class="d-flex gap-2 justify-content-end pt-2">
                <a href="<?= UrlHelper::admin('settings') ?>" class="btn btn-secondary">Назад</a>
                <button type="submit" class="btn btn-primary">
                    Зберегти налаштування
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.settings-form .card {
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    background: #ffffff;
}

.settings-form .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
    border-radius: 4px 4px 0 0;
}

.settings-form .card-header h6 {
    font-size: 0.875rem;
    color: #23282d;
}

.settings-form .card-header .text-primary {
    color: #0073aa !important;
    font-size: 0.875rem;
}

.settings-form .form-control,
.settings-form .form-select {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    transition: border-color 0.15s ease-in-out;
}

.settings-form .form-control:focus,
.settings-form .form-select:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 0.15rem rgba(0, 115, 170, 0.15);
    outline: 0;
}

.settings-form .form-label {
    color: #23282d;
    margin-bottom: 0.375rem;
    font-size: 0.8125rem;
}

.settings-form .form-text {
    color: #6c757d;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.settings-form .form-check-input {
    width: 2rem;
    height: 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
}

.settings-form .form-check-input:checked {
    background-color: #0073aa;
    border-color: #0073aa;
}

.settings-form .form-check-input:focus {
    box-shadow: 0 0 0 0.15rem rgba(0, 115, 170, 0.15);
}

.settings-form .form-check-label {
    font-size: 0.875rem;
    color: #23282d;
    margin-left: 0.5rem;
    cursor: pointer;
}

.settings-form .btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 4px;
    font-weight: 500;
    border: 1px solid transparent;
    transition: all 0.15s ease-in-out;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    line-height: 1.5;
    vertical-align: middle;
    text-decoration: none;
}

.settings-form .btn-primary {
    background-color: #0073aa;
    border-color: #0073aa;
    color: #ffffff;
}

.settings-form .btn-primary:hover {
    background-color: #005a87;
    border-color: #005a87;
    color: #ffffff;
}

.settings-form .btn-secondary {
    color: #6c757d;
    border-color: #dee2e6;
    background-color: #ffffff;
    min-height: 38px;
}

.settings-form .btn-secondary:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
    color: #495057;
}
</style>

