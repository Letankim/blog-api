<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ActivationKeyController;

$app->group('/api/v1/activation_keys', function (RouteCollectorProxy $group) {
    $group->get('', ActivationKeyController::class . ':getAll')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/{id}', ActivationKeyController::class . ':getById')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->post('', ActivationKeyController::class . ':create')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->put('/{id}', ActivationKeyController::class . ':update')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', ActivationKeyController::class . ':delete')->add(new App\Middleware\AuthMiddleware('admin'));
    $group->post('/{id}/generate-keys', ActivationKeyController::class . ':createKeysForOrder')
      ->add(new App\Middleware\AuthMiddleware('admin'));
    $group->get('/order/{order_id}', ActivationKeyController::class . ':getKeysByOrder')
        ->add(new App\Middleware\AuthMiddleware('admin'));
        $group->get('/order/my/{order_id}', ActivationKeyController::class . ':getKeysByOrder')
        ->add(new App\Middleware\AuthMiddleware('user'));
    $group->put('/{id}/active', ActivationKeyController::class . ':updateActiveStatus')
    ->add(new App\Middleware\AuthMiddleware('admin'));
    $group->post('/activation_keys/admin/create-multiple', [ActivationKeyController::class, 'createMultipleKeysByAdmin'])
    ->add(new App\Middleware\AuthMiddleware('admin'));
    $group->post('/user/me/{key_id}/reset', ActivationKeyController::class . ':resetKeyByUser')
        ->add(new App\Middleware\AuthMiddleware('user'));
     $group->get('/user/my', ActivationKeyController::class . ':getKeysByUser')
        ->add(new App\Middleware\AuthMiddleware('user'));
    

});
