<?php
/**
 * Функции темы Test Theme
 * Демонстрирует использование хуков в теме
 * 
 * @package Themes
 * @version 1.0.0
 */

declare(strict_types=1);

/**
 * Регистрация маршрутов темы
 */
if (function_exists('Router')) {
    // Можно зарегистрировать кастомные маршруты через хук
    addAction('register_routes', function($router) {
        // Пример регистрации маршрута
        // $router->get('/test', function() {
        //     return 'Test route';
        // });
    });
}

/**
 * Добавление меню через хук
 */
addAction('theme_menu', function() {
    echo '<ul class="navbar-nav ms-auto">';
    echo '<li class="nav-item"><a class="nav-link" href="/">Главная</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="/about">О нас</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="/contact">Контакты</a></li>';
    echo '</ul>';
});

/**
 * Модификация заголовка через фильтр
 */
addFilter('theme_title', function($title) {
    return $title . ' | Flowaxy CMS';
}, 10);

/**
 * Дополнительный контент через фильтр
 */
addFilter('theme_content', function($content) {
    $additional = '<div class="alert alert-success mt-3">';
    $additional .= '<i class="fas fa-check-circle"></i> ';
    $additional .= 'Этот блок добавлен через фильтр <code>theme_content</code> в functions.php темы.';
    $additional .= '</div>';
    return $content . $additional;
}, 15); // Приоритет 15 - выполнится после плагина (если он использует приоритет 10)

