<?php
/**
 * Тестовый плагин для демонстрации системы хуков
 * 
 * @package Plugins
 * @version 1.0.0
 */

declare(strict_types=1);

class TestHooksPluginPlugin extends BasePlugin {
    
    /**
     * Инициализация плагина
     */
    public function init(): void {
        // Регистрируем хуки
        
        // 1. Добавляем пункт меню в админку (фильтр)
        addFilter('admin_menu', [$this, 'addAdminMenuItem'], 10);
        
        // 2. Добавляем виджет на главную панель (фильтр)
        addFilter('dashboard_widgets', [$this, 'addDashboardWidget'], 15);
        
        // 3. Добавляем стили в head темы (событие)
        addAction('theme_head', [$this, 'addThemeStyles'], 10);
        
        // 4. Добавляем скрипты в footer темы (событие)
        addAction('theme_footer', [$this, 'addThemeScripts'], 10);
        
        // 5. Фильтр с условием - модификация контента только для определенных страниц
        addFilter('theme_content', [$this, 'modifyContent'], 10, function($content) {
            // Выполнять только если контент не пустой
            return !empty($content);
        });
        
        // 6. Логирование событий плагинов
        addAction('plugin_activated', [$this, 'logPluginActivated'], 5);
        addAction('plugin_deactivated', [$this, 'logPluginDeactivated'], 5);
        
        // 7. Демонстрация приоритетов - добавляем текст в начало (высокий приоритет)
        addFilter('test_priority', [$this, 'addPrefix'], 5);
        
        // 8. Демонстрация приоритетов - добавляем текст в конец (низкий приоритет)
        addFilter('test_priority', [$this, 'addSuffix'], 20);
        
        // 9. Регистрация маршрута админки (используем addHook для совместимости)
        // Примечание: маршрут также регистрируется автоматически системой
        // если файл admin/TestHooksPluginAdminPage.php существует
        addHook('admin_register_routes', [$this, 'registerAdminRoute'], 10);
    }
    
    /**
     * Добавление пункта меню в админку
     */
    public function addAdminMenuItem(array $menu): array {
        $menu[] = [
            'text' => 'Test Hooks',
            'title' => 'Test Hooks',
            'href' => '/admin/test-hooks-plugin',
            'icon' => 'fas fa-code',
            'order' => 100,
            'page' => 'test-hooks-plugin'
        ];
        
        return $menu;
    }
    
    /**
     * Добавление виджета на главную панель
     */
    public function addDashboardWidget(array $widgets): array {
        $widgets[] = [
            'title' => 'Test Hooks Widget',
            'content' => $this->renderWidgetContent(),
            'order' => 10
        ];
        
        return $widgets;
    }
    
    /**
     * Рендеринг содержимого виджета
     */
    private function renderWidgetContent(): string {
        $hookStats = hookManager()->getHookStats();
        $totalHooks = $hookStats['total_hooks'] ?? 0;
        $hookCalls = $hookStats['hook_calls'] ?? [];
        
        $html = '<div class="test-hooks-widget">';
        $html .= '<p><strong>Статистика хуков:</strong></p>';
        $html .= '<ul>';
        $html .= '<li>Всего хуков: <strong>' . $totalHooks . '</strong></li>';
        
        if (!empty($hookCalls)) {
            $html .= '<li>Вызовы хуков:</li>';
            $html .= '<ul>';
            foreach ($hookCalls as $hookName => $count) {
                $html .= '<li>' . htmlspecialchars($hookName) . ': ' . $count . '</li>';
            }
            $html .= '</ul>';
        }
        
        $html .= '</ul>';
        $html .= '<p class="text-muted">Этот виджет добавлен через фильтр <code>dashboard_widgets</code></p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Добавление стилей в head темы
     */
    public function addThemeStyles(): void {
        echo '<style>
            .test-hooks-widget {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #007bff;
            }
            .test-hooks-widget ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .test-hooks-widget code {
                background: #e9ecef;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 0.9em;
            }
            .test-hooks-badge {
                display: inline-block;
                background: #28a745;
                color: white;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 0.85em;
                margin-left: 10px;
            }
        </style>';
    }
    
    /**
     * Добавление скриптов в footer темы
     */
    public function addThemeScripts(): void {
        echo '<script>
            console.log("Test Hooks Plugin: Scripts loaded via theme_footer hook");
            // Можно добавить любую JavaScript логику
        </script>';
    }
    
    /**
     * Модификация контента темы
     */
    public function modifyContent(string $content): string {
        // Добавляем бейдж в начало контента
        $badge = '<span class="test-hooks-badge">Test Hooks Active</span>';
        return $badge . $content;
    }
    
    /**
     * Логирование активации плагина
     */
    public function logPluginActivated(string $pluginSlug): void {
        if (function_exists('logger')) {
            logger()->logInfo("Test Hooks Plugin: Plugin activated - {$pluginSlug}");
        }
    }
    
    /**
     * Логирование деактивации плагина
     */
    public function logPluginDeactivated(string $pluginSlug): void {
        if (function_exists('logger')) {
            logger()->logInfo("Test Hooks Plugin: Plugin deactivated - {$pluginSlug}");
        }
    }
    
    /**
     * Добавление префикса (высокий приоритет - выполнится первым)
     */
    public function addPrefix(string $text): string {
        return '[START] ' . $text;
    }
    
    /**
     * Добавление суффикса (низкий приоритет - выполнится последним)
     */
    public function addSuffix(string $text): string {
        return $text . ' [END]';
    }
    
    /**
     * Регистрация маршрута админки
     * Примечание: маршрут также регистрируется автоматически системой
     * если файл admin/TestHooksPluginAdminPage.php существует
     */
    public function registerAdminRoute($router): void {
        // Загружаем класс страницы админки (на случай если автозагрузка не сработала)
        $pluginDir = dirname(__FILE__);
        $adminPageFile = $pluginDir . '/admin/TestHooksPluginAdminPage.php';
        
        if (file_exists($adminPageFile) && !class_exists('TestHooksPluginAdminPage')) {
            require_once $adminPageFile;
        }
        
        // Регистрируем маршрут с кастомным именем (опционально)
        // Система автоматически зарегистрирует маршрут test-hooks-plugin
        // Но мы можем зарегистрировать более короткий маршрут
        if (class_exists('TestHooksPluginAdminPage')) {
            // Можно зарегистрировать альтернативный маршрут
            // $router->add(['GET', 'POST'], 'test-hooks', 'TestHooksPluginAdminPage');
        }
    }
    
    /**
     * Активация плагина
     */
    public function activate(): void {
        // Можно создать таблицы, настройки и т.д.
        if ($this->db) {
            try {
                // Пример: сохранение настройки
                $this->setSetting('activated_at', date('Y-m-d H:i:s'));
            } catch (Exception $e) {
                error_log("Test Hooks Plugin activation error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate(): void {
        // Очистка при деактивации (хуки удалятся автоматически)
    }
    
    /**
     * Удаление плагина
     */
    public function uninstall(): void {
        // Удаление всех данных плагина
        if ($this->db) {
            try {
                $slug = $this->getSlug();
                $stmt = $this->db->prepare("DELETE FROM plugin_settings WHERE plugin_slug = ?");
                $stmt->execute([$slug]);
            } catch (Exception $e) {
                error_log("Test Hooks Plugin uninstall error: " . $e->getMessage());
            }
        }
    }
}

