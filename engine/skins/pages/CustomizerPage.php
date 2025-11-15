<?php
/**
 * Страница настройки дизайна (Customizer)
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class CustomizerPage extends AdminPage {
    
    /**
     * Загрузка конфигурации кастомайзера из темы
     */
    private function getCustomizerConfig($themePath) {
        $customizerFile = $themePath . 'customizer.php';
        
        if (file_exists($customizerFile) && is_readable($customizerFile)) {
            try {
                $config = require $customizerFile;
                return is_array($config) ? $config : [];
            } catch (Exception $e) {
                error_log("CustomizerPage: Error loading customizer config: " . $e->getMessage());
                return [];
            }
        }
        
        return [];
    }
    
    /**
     * Проверка поддержки кастоматизации
     */
    private function checkCustomizationSupport($activeTheme) {
        if (!$activeTheme) {
            return [false, null];
        }
        
        $themePath = themeManager()->getThemePath($activeTheme['slug']);
        $customizerFile = $themePath . 'customizer.php';
        
        return [file_exists($customizerFile), $themePath];
    }
    
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
        
        if ($_POST && isset($_POST['save_customizer'])) {
            $this->saveSettings();
            header('Location: ' . adminUrl('customizer'));
            exit;
        }
        
        // Получение активной темы и проверка поддержки кастоматизации
        $activeTheme = themeManager()->getActiveTheme();
        list($supportsCustomization, $themePath) = $this->checkCustomizationSupport($activeTheme);
        
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
        
        if (!$supportsCustomization) {
            $this->setMessage('Поточна тема "' . htmlspecialchars($activeTheme['name']) . '" не підтримує кастомізацію. Розділ недоступний.', 'warning');
            $this->render([
                'activeTheme' => $activeTheme,
                'themeConfig' => null,
                'settings' => [],
                'availableSettings' => []
            ]);
            return;
        }
        
        // Загрузка конфигурации темы и кастомайзера
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        $customizerConfig = $this->getCustomizerConfig($themePath);
        
        // Получение настроек темы из БД
        $savedSettings = themeManager()->getSettings();
        
        $defaultSettings = $themeConfig['default_settings'] ?? [];
        $settings = array_merge($defaultSettings, $savedSettings);
        $categories = $customizerConfig['categories'] ?? [
            'colors' => ['icon' => 'fa-palette', 'label' => 'Кольори'],
            'fonts' => ['icon' => 'fa-font', 'label' => 'Шрифти'],
            'sizes' => ['icon' => 'fa-ruler', 'label' => 'Розміри'],
            'other' => ['icon' => 'fa-cog', 'label' => 'Логотип та інше'],
        ];
        $this->render([
            'activeTheme' => $activeTheme,
            'themeConfig' => $themeConfig,
            'customizerConfig' => $customizerConfig,
            'settings' => $settings,
            'availableSettings' => $themeConfig['available_settings'] ?? [],
            'categories' => $categories
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
        if (!$result['success']) {
            $this->setMessage($result['error'], 'danger');
        }
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
        
        $settingsRaw = $_POST['settings'] ?? '';
        $settings = [];
        
        if (is_string($settingsRaw) && !empty($settingsRaw)) {
            $settings = json_decode($settingsRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
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
                    return [
                    'success' => false,
                    'error' => 'Немає валідних налаштувань для збереження'
                ];
            }
            
            $result = themeManager()->setSettings($validatedSettings);
            
            if ($result) {
                cache_forget('theme_settings');
                cache_forget('site_settings');
                
                return [
                    'success' => true,
                    'message' => 'Налаштування успішно збережено',
                    'settings' => $validatedSettings
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Помилка при збереженні налаштувань в базу даних'
                ];
            }
        } catch (Exception $e) {
            error_log("Customizer save error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Помилка: ' . $e->getMessage()
            ];
        } catch (Error $e) {
            error_log("Customizer save fatal error: " . $e->getMessage());
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


