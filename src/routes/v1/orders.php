<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\OrderController;

$app->group('/api/v1/orders', function (RouteCollectorProxy $group) {
    $group->get('', OrderController::class . ':getAll')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/history', OrderController::class . ':getHistory')->add(new App\Middleware\AuthMiddleware());
    $group->post('', OrderController::class . ':create')->add(new App\Middleware\AuthMiddleware());
    $group->post('/cancel', OrderController::class . ':cancelOrder')->add(new App\Middleware\AuthMiddleware());
    $group->get('/payment/callback', [OrderController::class, 'paymentCallback'])->add(new App\Middleware\AuthMiddleware());
    $group->post('/checkOrder/ai', OrderController::class . ':getOrderByIdByCheck')->add(new App\Middleware\AuthMiddleware());
    $group->put('/{id}', OrderController::class . ':update')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', OrderController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/{id}', OrderController::class . ':getOrderById')->add(new App\Middleware\AuthMiddleware());
    $group->get('/{id}/items', OrderController::class . ':getOrderItems')->add(new App\Middleware\AuthMiddleware());
    $group->put('/{id}/status', OrderController::class . ':updateStatus')->add(new App\Middleware\AuthMiddleware('admin'));
});
