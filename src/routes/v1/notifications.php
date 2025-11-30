<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\NotificationController;

$app->group('/api/v1/notifications', function (RouteCollectorProxy $group) {
    $group->get('', NotificationController::class . ':getAll')->add(new App\Middleware\AuthMiddleware());
    $group->patch('/{id}/read', NotificationController::class . ':markRead')->add(new App\Middleware\AuthMiddleware());
     $group->patch('/{id}/unread', NotificationController::class . ':markUnread')->add(new App\Middleware\AuthMiddleware());
    $group->post('', NotificationController::class . ':create')->add(new App\Middleware\AuthMiddleware('admin'));
});
