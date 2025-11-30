<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ProductController;

$app->group('/api/v1/products', function (RouteCollectorProxy $group) {
    $group->get('', ProductController::class . ':getAll');
    $group->get('/{id}', ProductController::class . ':getById');
    $group->post('', ProductController::class . ':create')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->put('/{id}', ProductController::class . ':update')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', ProductController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});
