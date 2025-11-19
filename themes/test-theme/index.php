<?php
/**
 * Основной файл темы Test Theme
 * Демонстрирует использование хуков в теме
 * 
 * @package Themes
 * @version 1.0.0
 */

declare(strict_types=1);

// Получаем данные темы
$themeUrl = '/themes/test-theme';
$themePath = __DIR__;

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo applyFilter('theme_title', 'Test Theme - Flowaxy CMS'); ?></title>
    
    <!-- Базовые стили -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $themeUrl; ?>/style.css">
    
    <!-- Хук для добавления стилей в head -->
    <?php doAction('theme_head'); ?>
</head>
<body>
    <header class="theme-header">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <i class="fas fa-rocket"></i> Flowaxy CMS
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Хук для вывода меню -->
                    <?php doAction('theme_menu'); ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="theme-main">
        <div class="container mt-4">
            <div class="row">
                <div class="col-md-8">
                    <article class="content-area">
                        <h1>Добро пожаловать в Test Theme!</h1>
                        
                        <div class="alert alert-info">
                            <h4><i class="fas fa-info-circle"></i> Информация о теме</h4>
                            <p>Эта тема демонстрирует работу системы хуков Flowaxy CMS.</p>
                        </div>
                        
                        <?php
                        // Получаем контент и применяем фильтры
                        $content = '<p>Это пример контента, который будет модифицирован через фильтры.</p>';
                        $content .= '<p>Если установлен плагин Test Hooks Plugin, вы увидите бейдж в начале контента.</p>';
                        $content .= '<p>Также контент может быть модифицирован другими плагинами через фильтр <code>theme_content</code>.</p>';
                        
                        // Применяем фильтр для модификации контента
                        $content = applyFilter('theme_content', $content);
                        
                        echo $content;
                        ?>
                        
                        <hr>
                        
                        <h3>Демонстрация приоритетов хуков</h3>
                        <?php
                        // Демонстрация работы приоритетов
                        $testText = 'Тестовый текст';
                        $testText = applyFilter('test_priority', $testText);
                        echo '<p class="alert alert-warning"><strong>Результат:</strong> ' . htmlspecialchars($testText) . '</p>';
                        echo '<p class="text-muted">Текст был обработан фильтрами с разными приоритетами (5 и 20).</p>';
                        ?>
                        
                        <hr>
                        
                        <h3>Список зарегистрированных хуков</h3>
                        <?php
                        $hookManager = hookManager();
                        $allHooks = $hookManager->getAllHooks();
                        
                        if (!empty($allHooks)) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-striped">';
                            echo '<thead><tr><th>Имя хука</th><th>Количество обработчиков</th><th>Тип</th></tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($allHooks as $hookName => $hooks) {
                                $hookType = 'filter';
                                if (!empty($hooks)) {
                                    $firstHook = $hooks[0];
                                    $hookType = $firstHook['type'] ?? 'filter';
                                }
                                
                                echo '<tr>';
                                echo '<td><code>' . htmlspecialchars($hookName) . '</code></td>';
                                echo '<td><span class="badge bg-primary">' . count($hooks) . '</span></td>';
                                echo '<td><span class="badge bg-' . ($hookType === 'action' ? 'success' : 'info') . '">' . $hookType . '</span></td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            echo '</div>';
                        } else {
                            echo '<p class="text-muted">Хуки не зарегистрированы.</p>';
                        }
                        ?>
                    </article>
                </div>
                
                <div class="col-md-4">
                    <aside class="sidebar">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> О системе хуков</h5>
                            </div>
                            <div class="card-body">
                                <p>Flowaxy CMS использует мощную систему хуков для расширения функциональности.</p>
                                <ul>
                                    <li><strong>Фильтры</strong> - модифицируют данные</li>
                                    <li><strong>События</strong> - выполняют действия</li>
                                    <li><strong>Приоритеты</strong> - контролируют порядок выполнения</li>
                                    <li><strong>Условия</strong> - условное выполнение хуков</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plug"></i> Активные хуки</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $stats = $hookManager->getHookStats();
                                echo '<p><strong>Всего хуков:</strong> ' . ($stats['total_hooks'] ?? 0) . '</p>';
                                
                                if (!empty($stats['hook_calls'])) {
                                    echo '<p><strong>Вызовы:</strong></p>';
                                    echo '<ul class="list-unstyled">';
                                    foreach ($stats['hook_calls'] as $hookName => $count) {
                                        echo '<li><code>' . htmlspecialchars($hookName) . '</code>: ' . $count . '</li>';
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <footer class="theme-footer bg-dark text-white mt-5 py-4">
        <div class="container">
            <!-- Меню футера (будет добавлено через хук theme_footer) -->
            <?php
            // Применяем фильтр для добавления меню в футер
            $footerContent = '';
            $footerContent = applyFilter('theme_footer', $footerContent);
            echo $footerContent;
            ?>
            
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-1">&copy; <?php echo date('Y'); ?> Flowaxy CMS. Все права защищены.</p>
                    <p class="mb-0 small">Test Theme v1.0.0</p>
                </div>
            </div>
        </div>
        
        <!-- Хук для добавления скриптов в footer -->
        <?php doAction('theme_footer'); ?>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

