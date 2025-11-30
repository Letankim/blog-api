<?php
use App\Controllers\ProductCategoryController;
use App\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1/product-categories', function (RouteCollectorProxy $group) {
    $group->get('', ProductCategoryController::class . ':getAll');
    $group->get('/{id}', ProductCategoryController::class . ':getById');
    $group->post('', ProductCategoryController::class . ':create')->add(new AuthMiddleware('admin'));
    $group->put('/{id}', ProductCategoryController::class . ':update')->add(new AuthMiddleware('admin'));
    $group->delete('/{id}', ProductCategoryController::class . ':delete')->add(new AuthMiddleware('admin'));
});