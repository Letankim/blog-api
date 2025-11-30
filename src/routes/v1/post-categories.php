<?php
use App\Controllers\PostCategoryController;
use App\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1/post-categories', function (RouteCollectorProxy $group) {
    $group->get('', PostCategoryController::class . ':getAll');
    $group->get('/{id}', PostCategoryController::class . ':getById');
    $group->post('', PostCategoryController::class . ':create')->add(new AuthMiddleware('admin'));
    $group->put('/{id}', PostCategoryController::class . ':update')->add(new AuthMiddleware('admin'));
    $group->delete('/{id}', PostCategoryController::class . ':delete')->add(new AuthMiddleware('admin'));
});