<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\MaterialController;

$app->group('/api/v1/materials', function (RouteCollectorProxy $group) {
    $group->get('', MaterialController::class . ':getAll')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/public', MaterialController::class . ':getAllPublic');
    $group->get('/{id}', MaterialController::class . ':getById')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/public/{id}', MaterialController::class . ':getPublicById')->add(new App\Middleware\AuthMiddleware());
    $group->post('', MaterialController::class . ':create')->add(new App\Middleware\AuthMiddleware());
    $group->put('/{id}', MaterialController::class . ':update')->add(new App\Middleware\AuthMiddleware());
    $group->delete('/{id}', MaterialController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
     $group->patch('/{id}/status', MaterialController::class . ':updateStatus')->add(new App\Middleware\AuthMiddleware('admin'));
});
