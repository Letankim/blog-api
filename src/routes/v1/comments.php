<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\CommentController;

$app->group('/api/v1/comments', function (RouteCollectorProxy $group) {
    $group->get('', CommentController::class . ':getAll')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/by-post/active', CommentController::class . ':getAllCommentActiveByPost');
    $group->post('', CommentController::class . ':create')->add(new App\Middleware\AuthMiddleware());
    $group->put('/{id}', CommentController::class . ':updateByUser')->add(new App\Middleware\AuthMiddleware());
    $group->patch('/{id}/status', CommentController::class . ':updateStatus')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', CommentController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
});
