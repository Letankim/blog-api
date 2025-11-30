<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\VoucherController;

$app->group('/api/v1/vouchers', function (RouteCollectorProxy $group) {
    $group->get('', VoucherController::class . ':getAll');
    $group->get('/{id}', VoucherController::class . ':getById');
    $group->post('/check', VoucherController::class . ':checkVoucher')->add(new App\Middleware\AuthMiddleware());
    $group->post('', VoucherController::class . ':create')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->put('/{id}', VoucherController::class . ':update')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', VoucherController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});
