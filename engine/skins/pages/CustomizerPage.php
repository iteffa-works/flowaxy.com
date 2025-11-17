<?php
/**
 * Сторінка налаштування дизайну (Customizer)
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class CustomizerPage extends AdminPage {
    
    /**
     * Завантаження конфігурації кастомізера з теми
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
     * Перевірка підтримки кастомізації
     * Використовує оптимізований метод ThemeManager
     */
    private function checkCustomizationSupport($activeTheme) {
        if (!$activeTheme) {
            return [false, null];
        }
        
        $supports = themeManager()->supportsCustomization($activeTheme['slug']);
        $themePath = $supports ? themeManager()->getThemePath($activeTheme['slug']) : null;
        
        return [$supports, $themePath];
    }
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Налаштування дизайну - Flowaxy CMS';
        $this->templateName = 'customizer';
        
        $activeTheme = themeManager()->getActiveTheme();
        $themeName = $activeTheme ? $activeTheme['name'] : 'теми';
        
        // Формуємо кнопки для заголовка
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
        // Обробка AJAX запитів
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->handleAjax();
            return;
        }
        
        if ($_POST && isset($_POST['save_customizer'])) {
            $this->saveSettings();
            Response::redirectStatic(UrlHelper::admin('customizer'));
        }
        
        // Отримання активної теми та перевірка підтримки кастомізації
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
        
        // Завантаження конфігурації теми та кастомізера
        $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
        $customizerConfig = $this->getCustomizerConfig($themePath);
        
        // Отримання налаштувань теми з БД
        $savedSettings = themeManager()->getSettings();
        
        $defaultSettings = $themeConfig['default_settings'] ?? [];
        $settings = array_merge($defaultSettings, $savedSettings);
        
        // Дефолтные категории с иконками
        $defaultCategories = [
            'identity' => ['icon' => 'fa-id-card', 'label' => 'Ідентичність сайту'],
            'colors' => ['icon' => 'fa-palette', 'label' => 'Кольори'],
            'fonts' => ['icon' => 'fa-font', 'label' => 'Typography'],
            'menu' => ['icon' => 'fa-bars', 'label' => 'Меню'],
            'header' => ['icon' => 'fa-window-maximize', 'label' => 'Header Settings'],
            'footer' => ['icon' => 'fa-window-minimize', 'label' => 'Footer Settings'],
            'css' => ['icon' => 'fa-code', 'label' => 'Додатковий код CSS'],
        ];
        
        // Получаем доступные настройки из темы
        $availableSettings = $themeConfig['available_settings'] ?? [];
        
        // Формируем список категорий только из тех, что есть в available_settings
        $categories = [];
        if (isset($customizerConfig['categories']) && is_array($customizerConfig['categories'])) {
            // Если в customizer.php определены категории, используем их
            foreach ($customizerConfig['categories'] as $category => $categoryInfo) {
                if (isset($availableSettings[$category]) && !empty($availableSettings[$category])) {
                    $categories[$category] = $categoryInfo;
                }
            }
        } else {
            // Иначе используем дефолтные категории, но только те, что есть в available_settings
            foreach ($defaultCategories as $category => $categoryInfo) {
                if (isset($availableSettings[$category]) && !empty($availableSettings[$category])) {
                    $categories[$category] = $categoryInfo;
                }
            }
        }
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
     * Збереження налаштувань (для звичайних POST запитів)
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
     * Отримання зображень з медіа-галереї (AJAX)
     */
    private function getMediaImages() {
        $mediaModule = mediaModule();
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 24;
        $search = SecurityHelper::sanitizeInput($_GET['search'] ?? '');
        
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
     * Обробка завантаження зображення (AJAX)
     */
    private function handleUpload() {
        if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
            echo json_encode(['success' => false, 'error' => 'Файл не завантажено'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $mediaModule = mediaModule();
        $title = !empty($_POST['title']) ? SecurityHelper::sanitizeInput($_POST['title']) : null;
        $description = SecurityHelper::sanitizeInput($_POST['description'] ?? '');
        $alt = SecurityHelper::sanitizeInput($_POST['alt_text'] ?? '');
        
        $result = $mediaModule->uploadFile($_FILES['file'], $title, $description, $alt);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обробка AJAX запитів
     */
    private function handleAjax() {
        // Використовуємо Response клас для встановлення заголовків
        Response::setHeader('Content-Type', 'application/json');
        
        $action = SecurityHelper::sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
        
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
     * AJAX збереження всіх налаштувань
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
     * AJAX збереження однієї налаштування
     */
    private function ajaxSaveSingleSetting() {
        if (!$this->verifyCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Помилка безпеки'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $key = SecurityHelper::sanitizeInput($_POST['key'] ?? '');
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
        
        // Знаходимо налаштування в конфігурації
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
        
        // Валідуємо та зберігаємо одну налаштування
        $validatedSettings = [];
        $validatedSettings[$key] = $this->validateSetting($key, $value, $settingConfig);
        
        $result = $this->validateAndSaveSettings($validatedSettings);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * AJAX скидання налаштувань до значень за замовчуванням
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
        
        // Видаляємо всі налаштування теми з БД (вони будуть братися з default_settings)
        $this->db->prepare("DELETE FROM theme_settings WHERE theme_slug = ?")->execute([$activeTheme['slug']]);
        
        // Очищаємо кеш теми
        themeManager()->clearThemeCache($activeTheme['slug']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Налаштування скинуто до значень за замовчуванням',
            'settings' => $defaultSettings
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Валідація та збереження налаштувань
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
            
            // Валідація та фільтрація налаштувань
            foreach ($settings as $key => $value) {
                // Перевіряємо, що налаштування дозволене в конфігурації теми
                if (!in_array($key, $allowedKeys)) {
                    continue;
                }
                
                // Отримуємо конфігурацію налаштування
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
                // Зберігаємо значення, навіть якщо воно порожнє (для текстових полів це нормально)
                $validatedSettings[$key] = $validatedValue;
            }
            
            // Обробка чекбоксів з усіх категорій (не тільки 'other')
            foreach ($availableSettings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    if ($config['type'] === 'checkbox') {
                        // Якщо налаштування не було передано, встановлюємо '0'
                        if (!isset($settings[$key])) {
                            $validatedSettings[$key] = '0';
                        }
                    }
                }
            }
            
            // Зберігаємо налаштування
            if (empty($validatedSettings)) {
                    return [
                    'success' => false,
                    'error' => 'Немає валідних налаштувань для збереження'
                ];
            }
            
            $result = themeManager()->setSettings($validatedSettings);
            
            if ($result) {
                // Очищаємо кеш теми
                themeManager()->clearThemeCache($activeTheme['slug']);
                
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
                $value = SecurityHelper::sanitizeInput($value ?? '');
                
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
                
                // Якщо значення не валідне, але не порожнє, намагаємося виправити (додаємо # якщо відсутнє)
                if (!empty($value) && $value[0] !== '#') {
                    $value = '#' . $value;
                    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                        return $value;
                    }
                }
                
                // Якщо все ще не валідне, повертаємо значення за замовчуванням
                $activeTheme = themeManager()->getActiveTheme();
                if ($activeTheme) {
                    $themeConfig = themeManager()->getThemeConfig($activeTheme['slug']);
                    $defaultSettings = $themeConfig['default_settings'] ?? [];
                    return $defaultSettings[$key] ?? '#000000';
                }
                
                return '#000000';
                
            case 'select':
                $options = $settingConfig['options'] ?? [];
                $value = SecurityHelper::sanitizeInput($value);
                if (isset($options[$value])) {
                    return $value;
                } elseif (!empty($options)) {
                    // Якщо значення не знайдено, повертаємо перше доступне
                    return array_key_first($options);
                }
                return $value; // Повертаємо значення, навіть якщо опцій немає
                
            case 'checkbox':
                // Чекбокси завжди повертають '1' або '0'
                if ($value === true || $value === 'true' || $value === '1' || $value === 1) {
                    return '1';
                }
                return '0';
                
            case 'media':
                $value = SecurityHelper::sanitizeInput($value);
                // Для media дозволяємо порожні значення та будь-які рядки, які схожі на URL або шлях
                if (empty($value)) {
                    return '';
                }
                // Перевіряємо, що це схоже на URL або шлях
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
                return SecurityHelper::sanitizeInput($value ?? '');
        }
    }
}


