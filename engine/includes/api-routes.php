<?php
/**
 * API Routes
 * Маршруты для API внешних приложений
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

// $router уже создан в router-handler.php

// Регистрируем middleware для аутентификации
$router->middleware('api_auth', function() {
    return ApiHandler::requireAuth();
});

// Публичные endpoints (не требуют аутентификации)
$router->get('', function() {
    (new ApiController())->info();
});

$router->get('info', function() {
    (new ApiController())->info();
});

$router->get('status', function() {
    (new ApiController())->status();
});

// Защищенные endpoints (требуют аутентификации)
$router->get('me', function() {
    (new ApiController())->me();
}, ['middleware' => ['api_auth']]);

$router->get('permissions/check', function($params) {
    (new ApiController())->checkPermission($params);
}, ['middleware' => ['api_auth']]);

$router->get('permissions', function() {
    (new ApiController())->permissions();
}, ['middleware' => ['api_auth']]);

