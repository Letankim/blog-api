<?php
use App\Controllers\PostLikeController;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1/likes', function (RouteCollectorProxy $group) {
    $group->post('', PostLikeController::class . ':like')->add(new App\Middleware\AuthMiddleware());
    $group->delete('/{post_id}', PostLikeController::class . ':unlike')->add(new App\Middleware\AuthMiddleware());
    $group->get('/{post_id}', PostLikeController::class . ':getLikesByPost');
});
