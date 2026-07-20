<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\BagrController;

$app->group('/api/v1/bagr', function (RouteCollectorProxy $group) {
    $group->get('', BagrController::class . ':getAll')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/{id}', BagrController::class . ':getById')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->post('', BagrController::class . ':create')->add(new \App\Middleware\TurnstileMiddleware());
    $group->delete('/{id}', BagrController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});