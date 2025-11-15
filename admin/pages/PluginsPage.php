<?php
/**
 * Страница управления плагинами
 */

require_once __DIR__ . '/../includes/AdminPage.php';

class PluginsPage extends AdminPage {
    
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Керування плагінами - Landing CMS';
        $this->templateName = 'plugins';
        
        $this->setPageHeader(
            'Керування плагінами',
            'Встановлення та налаштування плагінів',
            'fas fa-puzzle-piece'
        );
    }
    
    public function handle() {
        // Обработка действий
        if ($_POST) {
            $this->handleAction();
        }
        
        // Получение списка плагинов
        $installedPlugins = $this->getInstalledPlugins();
        $stats = $this->calculateStats($installedPlugins);
        
        // Рендерим страницу
        $this->render([
            'installedPlugins' => $installedPlugins,
            'stats' => $stats
        ]);
    }
    
    /**
     * Обработка действий с плагинами
     */
    private function handleAction() {
        if (!$this->verifyCsrf()) {
            return;
        }
        
        $action = $_POST['action'] ?? '';
        $pluginSlug = $_POST['plugin_slug'] ?? '';
        
        try {
            switch ($action) {
                case 'install':
                    pluginManager()->installPlugin($pluginSlug);
                    $this->setMessage('Плагін встановлено', 'success');
                    break;
                    
                case 'activate':
                    pluginManager()->activatePlugin($pluginSlug);
                    $this->setMessage('Плагін активовано', 'success');
                    break;
                    
                case 'deactivate':
                    pluginManager()->deactivatePlugin($pluginSlug);
                    $this->setMessage('Плагін деактивовано', 'success');
                    break;
                    
                case 'uninstall':
                    pluginManager()->uninstallPlugin($pluginSlug);
                    $this->setMessage('Плагін видалено', 'success');
                    break;
            }
        } catch (Exception $e) {
            $this->setMessage('Помилка: ' . $e->getMessage(), 'danger');
            error_log("Plugin action error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение всех плагинов (из папки + БД)
     */
    private function getInstalledPlugins() {
        $allPlugins = [];
        $pluginsDir = __DIR__ . '/../../plugins/';
        
        // Получаем плагины из БД
        $dbPlugins = [];
        try {
            $stmt = $this->db->query("SELECT * FROM plugins");
            $dbPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dbPlugins = array_column($dbPlugins, null, 'slug');
        } catch (Exception $e) {
            error_log("DB plugins error: " . $e->getMessage());
        }
        
        // Сканируем папку plugins
        if (is_dir($pluginsDir)) {
            $directories = glob($pluginsDir . '*', GLOB_ONLYDIR);
            
            foreach ($directories as $dir) {
                $slug = basename($dir);
                $configFile = $dir . '/plugin.json';
                
                if (file_exists($configFile)) {
                    $config = json_decode(file_get_contents($configFile), true);
                    
                    if ($config) {
                        // Проверяем, установлен ли плагин в БД
                        $isInstalled = isset($dbPlugins[$slug]);
                        $isActive = $isInstalled && $dbPlugins[$slug]['is_active'];
                        
                        $allPlugins[] = [
                            'slug' => $slug,
                            'name' => $config['name'] ?? $slug,
                            'description' => $config['description'] ?? '',
                            'version' => $config['version'] ?? '1.0.0',
                            'author' => $config['author'] ?? '',
                            'is_installed' => $isInstalled,
                            'is_active' => $isActive,
                            'settings' => $isInstalled ? ($dbPlugins[$slug]['settings'] ?? null) : null
                        ];
                    }
                }
            }
        }
        
        return $allPlugins;
    }
    
    /**
     * Расчет статистики
     */
    private function calculateStats($plugins) {
        $installed = 0;
        $active = 0;
        $available = count($plugins);
        
        foreach ($plugins as $plugin) {
            if ($plugin['is_installed']) {
                $installed++;
            }
            if ($plugin['is_active']) {
                $active++;
            }
        }
        
        return [
            'total' => $available,
            'installed' => $installed,
            'active' => $active,
            'inactive' => $installed - $active
        ];
    }
}
