<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ReviewController;

$app->group('/api/v1/reviews', function (RouteCollectorProxy $group) {

    $group->get('', ReviewController::class . ':getAll')
          ->add(new App\Middleware\AuthMiddleware('admin'));

    $group->get('/public', ReviewController::class . ':getAllPublic');

    $group->post('', ReviewController::class . ':create')
          ->add(new App\Middleware\AuthMiddleware("admin"));

    $group->get('/check', ReviewController::class . ':checkUserReviewed')
          ->add(new App\Middleware\AuthMiddleware());

    $group->post('/create-or-update', ReviewController::class . ':createOrUpdate')
          ->add(new App\Middleware\AuthMiddleware());

    $group->patch('/{id}/status', ReviewController::class . ':updateStatus')
          ->add(new App\Middleware\AuthMiddleware('admin'));

    $group->delete('/{id}', ReviewController::class . ':delete')
          ->add(new App\Middleware\AuthMiddleware('admin'));
});
