<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ReviewReplyController;

$app->group('/api/v1/review_replies', function (RouteCollectorProxy $group) {
    $group->get('', ReviewReplyController::class . ':getAll')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->post('', ReviewReplyController::class . ':create')->add(new App\Middleware\AuthMiddleware());
    $group->patch('/{id}/status', ReviewReplyController::class . ':updateStatus')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', ReviewReplyController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});
