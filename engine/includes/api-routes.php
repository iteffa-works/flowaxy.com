<?php
/**
 * API Routes
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

$router->middleware('api_auth', function() {
    return ApiHandler::requireAuth();
});

$router->get('', function() {
    (new ApiController())->info();
});

$router->get('info', function() {
    (new ApiController())->info();
});

$router->get('status', function() {
    (new ApiController())->status();
});

$router->get('me', function() {
    (new ApiController())->me();
}, ['middleware' => ['api_auth']]);

$router->get('permissions/check', function($params) {
    (new ApiController())->checkPermission($params);
}, ['middleware' => ['api_auth']]);

$router->get('permissions', function() {
    (new ApiController())->permissions();
}, ['middleware' => ['api_auth']]);

