<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\PasskeyController;

$app->group('/api/v1/passkey', function (RouteCollectorProxy $group) {
    $group->post('/register/start', [PasskeyController::class, 'startRegistration']);
    $group->post('/register/finish', [PasskeyController::class, 'finishRegistration']);

    $group->post('/login/start', [PasskeyController::class, 'startLogin']);
    $group->post('/login/finish', [PasskeyController::class, 'finishLogin']);
    $group->delete('/revoke', [PasskeyController::class, 'revoke'])
          ->add(new App\Middleware\AuthMiddleware());
});