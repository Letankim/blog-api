<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\PostController;

$app->group('/api/v1/posts', function (RouteCollectorProxy $group) {
    $group->get('', PostController::class . ':getAll');
    $group->get('/{id}', PostController::class . ':getById')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/active/{id}', PostController::class . ':getByIdActive');
    $group->post('', PostController::class . ':create')->add(new App\Middleware\AuthMiddleware());
    $group->put('/{id}', PostController::class . ':update')->add(new App\Middleware\AuthMiddleware());
    $group->delete('/{id}', PostController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});
