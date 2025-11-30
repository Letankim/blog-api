<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\TagController;

$app->group('/api/v1/tags', function (RouteCollectorProxy $group) {
    $group->get('', TagController::class . ':getAll');
    $group->get('/{id}', TagController::class . ':getById');
    $group->post('', TagController::class . ':create')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->put('/{id}', TagController::class . ':update')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', TagController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});
