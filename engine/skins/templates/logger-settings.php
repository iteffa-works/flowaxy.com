<?php
/**
 * Шаблон страницы настроек логирования
 */

$settings = $settings ?? [];
?>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sliders-h"></i> Основные настройки
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_file_size" class="form-label">
                                    Максимальный размер файла лога (байты)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="max_file_size" 
                                       name="max_file_size" 
                                       value="<?= htmlspecialchars($settings['max_file_size'] ?? 10485760) ?>"
                                       min="1048576" 
                                       step="1048576">
                                <small class="form-text text-muted">
                                    Минимум: 1 MB (1048576 байт). При превышении размера файл будет автоматически ротирован.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="retention_days" class="form-label">
                                    Дни хранения логов
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="retention_days" 
                                       name="retention_days" 
                                       value="<?= htmlspecialchars($settings['retention_days'] ?? 30) ?>"
                                       min="1" 
                                       max="365">
                                <small class="form-text text-muted">
                                    Логи старше указанного количества дней будут автоматически удалены.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-toggle-on"></i> Типы логирования
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_errors" 
                                       name="log_errors" 
                                       <?= ($settings['log_errors'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_errors">
                                    <strong>Логировать ошибки</strong>
                                    <br>
                                    <small class="text-muted">Логирование всех ошибок системы и PHP</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_warnings" 
                                       name="log_warnings" 
                                       <?= ($settings['log_warnings'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_warnings">
                                    <strong>Логировать предупреждения</strong>
                                    <br>
                                    <small class="text-muted">Логирование предупреждений системы</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_info" 
                                       name="log_info" 
                                       <?= ($settings['log_info'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_info">
                                    <strong>Логировать информацию</strong>
                                    <br>
                                    <small class="text-muted">Логирование информационных сообщений</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_success" 
                                       name="log_success" 
                                       <?= ($settings['log_success'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_success">
                                    <strong>Логировать успешные события</strong>
                                    <br>
                                    <small class="text-muted">Логирование успешных операций</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_debug" 
                                       name="log_debug" 
                                       <?= ($settings['log_debug'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_debug">
                                    <strong>Логировать отладочную информацию</strong>
                                    <br>
                                    <small class="text-muted">Детальное логирование для отладки (может создавать большие файлы)</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_db_queries" 
                                       name="log_db_queries" 
                                       <?= ($settings['log_db_queries'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_db_queries">
                                    <strong>Логировать запросы к БД</strong>
                                    <br>
                                    <small class="text-muted">Логирование всех SQL запросов (требует включенной отладки)</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_file_operations" 
                                       name="log_file_operations" 
                                       <?= ($settings['log_file_operations'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_file_operations">
                                    <strong>Логировать операции с файлами</strong>
                                    <br>
                                    <small class="text-muted">Логирование операций чтения/записи файлов</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_plugin_events" 
                                       name="log_plugin_events" 
                                       <?= ($settings['log_plugin_events'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_plugin_events">
                                    <strong>Логировать события плагинов</strong>
                                    <br>
                                    <small class="text-muted">Логирование активации, деактивации, установки плагинов</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="log_module_events" 
                                       name="log_module_events" 
                                       <?= ($settings['log_module_events'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="log_module_events">
                                    <strong>Логировать события модулей</strong>
                                    <br>
                                    <small class="text-muted">Логирование загрузки модулей и их ошибок</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить настройки
                    </button>
                    <a href="<?= adminUrl('logs') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к логам
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

