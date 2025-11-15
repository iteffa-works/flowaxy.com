<?php
/**
 * Страница настройки дизайна (Customizer)
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class CustomizerPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування дизайну - Landing CMS';
        $this->templateName = 'customizer';
        
        $activeTheme = themeManager()->getActiveTheme();
        $themeName = $activeTheme ? $activeTheme['name'] : 'теми';
        
        // Формируем кнопки для заголовка
        $buttons = '<button type="button" class="btn btn-outline-secondary btn-sm" id="resetSettingsBtn">' .
                   '<i class="fas fa-undo me-2"></i>Скинути до значень за замовчуванням</button>' .
                   '<a href="' . SITE_URL . '" class="btn btn-outline-primary btn-sm ms-2" target="_blank">' .
                   '<i class="fas fa-external-link-alt me-2"></i>Переглянути сайт</a>';
        
        $this->setPageHeader(
            'Налаштування дизайну',
            'Налаштування кольорів, шрифтів та інших параметрів дизайну для активної теми: ' . $themeName,
            'fas fa-paint-brush',
            $buttons
        );
    }
    
    public function handle() {
        // Обработка AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        // Обработка сохранения настроек (обычный POST для обратной совместимости)
        // Но не показываем сообщение об успехе, так как используется AJAX
        if ($_POST && isset($_POST['save_customizer'])) {
            $this->saveSettings();
            // После сохранения делаем редирект, чтобы убрать POST данные из URL
            header('Location: ' . adminUrl('customizer'));
            exit;
        }
        
        // Получение активной темы
        $activeTheme = themeManager()->getActiveTheme();
        
        if (!$activeTheme) {
            $this->setMessage('Спочатку активуйте тему в розділі "Теми"', 'warning');
            $this->render([
                'activeTheme' => null,
                'themeConfig' => null,
                'settings' => [],
                'availableSettings' => []
            ]);
            return;
        }
        
        // Загрузка конфигурации темы
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        
        // Получение настроек темы из БД
        $savedSettings = themeManager()->getSettings();
        
        // Объединяем настройки: значения по умолчанию из конфигурации + сохраненные настройки
        $defaultSettings = $themeConfig['default_settings'] ?? [];
        $settings = array_merge($defaultSettings, $savedSettings);
        
        // Рендерим страницу
        $this->render([
            'activeTheme' => $activeTheme,
            'themeConfig' => $themeConfig,
            'settings' => $settings,
            'availableSettings' => $themeConfig['available_settings'] ?? []
        ]);
    }
    
    /**
     * Сохранение настроек (для обычных POST запросов)
     */
    private function saveSettings() {
        if (!$this->verifyCsrf()) {
            $this->setMessage('Помилка безпеки', 'danger');
            return;
        }
        
        $result = $this->validateAndSaveSettings($_POST['settings'] ?? []);
        
        // Показываем только ошибки, успех не показываем (используется AJAX)
        if (!$result['success']) {
            $this->setMessage($result['error'], 'danger');
        }
        // При успехе не устанавливаем сообщение, так как используется AJAX и статус показывается рядом с кнопкой
    }
    
    /**
     * Получение изображений из медиагалереи (AJAX)
     */
    private function getMediaImages() {
        $mediaModule = mediaModule();
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 24;
        $search = sanitizeInput($_GET['search'] ?? '');
        
        $filters = [
            'media_type' => 'image',
            'search' => $search,
            'order_by' => 'uploaded_at',
            'order_dir' => 'DESC'
        ];
        
        $result = $mediaModule->getFiles($filters, $page, $perPage);
        
        echo json_encode([
            'success' => true,
            'files' => $result['files'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обработка загрузки изображения (AJAX)
     */
    private function handleUpload() {
        if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
            echo json_encode(['success' => false, 'error' => 'Файл не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $mediaModule = mediaModule();
        $title = !empty($_POST['title']) ? sanitizeInput($_POST['title']) : null;
        $description = sanitizeInput($_POST['description'] ?? '');
        $alt = sanitizeInput($_POST['alt_text'] ?? '');
        
        $result = $mediaModule->uploadFile($_FILES['file'], $title, $description, $alt);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обработка AJAX запросов
     */
    private function handleAjax() {
        header('Content-Type: application/json');
        
        $action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
        switch ($action) {
            case 'get_media_images':
                $this->getMediaImages();
                break;
                
            case 'upload_image':
                $this->handleUpload();
                break;
                
            case 'save_settings':
                $this->ajaxSaveSettings();
                break;
                
            case 'save_setting':
                $this->ajaxSaveSingleSetting();
                break;
                
            case 'reset_settings':
                $this->ajaxResetSettings();
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Невідома дія'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    /**
     * AJAX сохранение всех настроек
     */
    private function ajaxSaveSettings() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки (CSRF токен не валідний)'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            echo json_encode(['success' => false, 'error' => 'Тему не активовано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Получаем настройки из POST (может быть JSON строка или массив)
        $settingsRaw = $_POST['settings'] ?? '';
        $settings = [];
        
        if (is_string($settingsRaw) && !empty($settingsRaw)) {
            $settings = json_decode($settingsRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Customizer JSON decode error: " . json_last_error_msg() . " | Raw: " . substr($settingsRaw, 0, 200));
                echo json_encode([
                    'success' => false, 
                    'error' => 'Помилка декодування JSON: ' . json_last_error_msg()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $settings = $settings ?? [];
        } elseif (is_array($settingsRaw)) {
            $settings = $settingsRaw;
        }
        
        if (empty($settings)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Налаштування не передані'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $result = $this->validateAndSaveSettings($settings);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * AJAX сохранение одной настройки
     */
    private function ajaxSaveSingleSetting() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $key = sanitizeInput($_POST['key'] ?? '');
        $value = $_POST['value'] ?? '';
        
        if (empty($key)) {
            echo json_encode(['success' => false, 'error' => 'Ключ налаштування не вказано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            echo json_encode(['success' => false, 'error' => 'Тему не активовано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        $availableSettings = $themeConfig['available_settings'] ?? [];
        
        // Находим настройку в конфигурации
        $settingConfig = null;
        foreach ($availableSettings as $category => $categorySettings) {
            if (isset($categorySettings[$key])) {
                $settingConfig = $categorySettings[$key];
                break;
            }
        }
        
        if (!$settingConfig) {
            echo json_encode(['success' => false, 'error' => 'Налаштування не знайдено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Валидируем и сохраняем одну настройку
        $validatedSettings = [];
        $validatedSettings[$key] = $this->validateSetting($key, $value, $settingConfig);
        
        $result = $this->validateAndSaveSettings($validatedSettings);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * AJAX сброс настроек к значениям по умолчанию
     */
    private function ajaxResetSettings() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $activeTheme = themeManager()->getActiveTheme();
        if (!$activeTheme) {
            echo json_encode(['success' => false, 'error' => 'Тему не активовано'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        $defaultSettings = $themeConfig['default_settings'] ?? [];
        
        // Удаляем все настройки темы из БД (они будут браться из default_settings)
        $this->db->prepare("DELETE FROM theme_settings WHERE theme_slug = ?")->execute([$activeTheme['slug']]);
        
        // Очищаем кеш
        cache_forget('theme_settings');
        cache_forget('site_settings');
        
        echo json_encode([
            'success' => true,
            'message' => 'Налаштування скинуто до значень за замовчуванням',
            'settings' => $defaultSettings
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Валидация и сохранение настроек
     */
    private function validateAndSaveSettings($settings) {
        try {
            $activeTheme = themeManager()->getActiveTheme();
            if (!$activeTheme) {
                return [
                    'success' => false,
                    'error' => 'Тему не активовано'
                ];
            }
            
            $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
            if (!$themeConfig) {
                return [
                    'success' => false,
                    'error' => 'Конфігурацію теми не знайдено'
                ];
            }
            
            $availableSettings = $themeConfig['available_settings'] ?? [];
            
            // Собираем все доступные ключи настроек из конфигурации
            $allowedKeys = [];
            foreach ($availableSettings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    $allowedKeys[] = $key;
                }
            }
            
            $validatedSettings = [];
            
            // Валидация и фильтрация настроек
            foreach ($settings as $key => $value) {
                // Проверяем, что настройка разрешена в конфигурации темы
                if (!in_array($key, $allowedKeys)) {
                    continue;
                }
                
                // Получаем конфигурацию настройки
                $settingConfig = null;
                foreach ($availableSettings as $category => $categorySettings) {
                    if (isset($categorySettings[$key])) {
                        $settingConfig = $categorySettings[$key];
                        break;
                    }
                }
                
                if (!$settingConfig) {
                    continue;
                }
                
                $validatedValue = $this->validateSetting($key, $value, $settingConfig);
                // Сохраняем значение, даже если оно пустое (для текстовых полей это нормально)
                $validatedSettings[$key] = $validatedValue;
            }
            
            // Обработка чекбоксов из всех категорий (не только 'other')
            foreach ($availableSettings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    if ($config['type'] === 'checkbox') {
                        // Если настройка не была передана, устанавливаем '0'
                        if (!isset($settings[$key])) {
                            $validatedSettings[$key] = '0';
                        }
                    }
                }
            }
            
            // Сохраняем настройки
            if (empty($validatedSettings)) {
                error_log("Customizer: No validated settings to save. Input settings: " . print_r($settings, true));
                return [
                    'success' => false,
                    'error' => 'Немає валідних налаштувань для збереження. Можливо, налаштування не пройшли валідацію.'
                ];
            }
            
            // Логируем настройки перед сохранением
            error_log("Customizer: Saving " . count($validatedSettings) . " settings: " . implode(', ', array_keys($validatedSettings)));
            error_log("Customizer: Settings values: " . print_r($validatedSettings, true));
            
            $result = themeManager()->setSettings($validatedSettings);
            
            if ($result) {
                // Очищаем кеш
                cache_forget('theme_settings');
                cache_forget('site_settings');
                
                error_log("Customizer: Settings saved successfully");
                
                return [
                    'success' => true,
                    'message' => 'Налаштування успішно збережено',
                    'settings' => $validatedSettings
                ];
            } else {
                error_log("Customizer: Failed to save settings to database");
                // Получаем последнюю ошибку из логов
                $lastError = error_get_last();
                $errorMsg = 'Помилка при збереженні налаштувань в базу даних.';
                if ($lastError && isset($lastError['message'])) {
                    $errorMsg .= ' Деталі: ' . $lastError['message'];
                }
                return [
                    'success' => false,
                    'error' => $errorMsg . ' Перевірте логи сервера для деталей.'
                ];
            }
        } catch (Exception $e) {
            error_log("Customizer save error: " . $e->getMessage());
            error_log("Customizer save error trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'Помилка: ' . $e->getMessage()
            ];
        } catch (Error $e) {
            error_log("Customizer save fatal error: " . $e->getMessage());
            error_log("Customizer save fatal error trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'Критична помилка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Валидация одной настройки
     */
    private function validateSetting($key, $value, $settingConfig) {
        $type = $settingConfig['type'] ?? 'text';
        
        // Нормализуем значение
        if ($value === null) {
            $value = '';
        }
        
        switch ($type) {
            case 'color':
                $value = sanitizeInput($value ?? '');
                
                // Если значение пустое, пытаемся получить значение по умолчанию
                if (empty($value)) {
                    $activeTheme = themeManager()->getActiveTheme();
                    if ($activeTheme) {
                        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
                        $defaultSettings = $themeConfig['default_settings'] ?? [];
                        $value = $defaultSettings[$key] ?? '#000000';
                    } else {
                        $value = '#000000';
                    }
                }
                
                // Валидация формата цвета
                if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                    return $value;
                }
                
                // Если значение не валидно, но не пустое, пытаемся исправить (добавляем # если отсутствует)
                if (!empty($value) && $value[0] !== '#') {
                    $value = '#' . $value;
                    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                        return $value;
                    }
                }
                
                // Если все еще не валидно, возвращаем значение по умолчанию
                $activeTheme = themeManager()->getActiveTheme();
                if ($activeTheme) {
                    $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
                    $defaultSettings = $themeConfig['default_settings'] ?? [];
                    return $defaultSettings[$key] ?? '#000000';
                }
                
                return '#000000';
                
            case 'select':
                $options = $settingConfig['options'] ?? [];
                $value = sanitizeInput($value);
                if (isset($options[$value])) {
                    return $value;
                } elseif (!empty($options)) {
                    // Если значение не найдено, возвращаем первое доступное
                    return array_key_first($options);
                }
                return $value; // Возвращаем значение, даже если опций нет
                
            case 'checkbox':
                // Чекбоксы всегда возвращают '1' или '0'
                if ($value === true || $value === 'true' || $value === '1' || $value === 1) {
                    return '1';
                }
                return '0';
                
            case 'media':
                $value = sanitizeInput($value);
                // Для media разрешаем пустые значения и любые строки, которые похожи на URL или путь
                if (empty($value)) {
                    return '';
                }
                // Проверяем, что это похоже на URL или путь
                if (filter_var($value, FILTER_VALIDATE_URL) || 
                    (strpos($value, '/') === 0) || 
                    (strpos($value, 'http') === 0) ||
                    preg_match('/^[a-zA-Z0-9_\-\.\/\\\:]+$/', $value)) {
                    return $value;
                }
                // Если не прошло валидацию, все равно возвращаем значение (пользователь должен видеть свою ошибку)
                return $value;
                
            case 'text':
            default:
                // Для текстовых полей разрешаем любые значения, включая пустые
                return sanitizeInput($value ?? '');
        }
    }
}


