<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\SiteSettingController;

$app->group('/api/v1/site_settings', function (RouteCollectorProxy $group) {
    $group->get('', SiteSettingController::class . ':getAll');
    $group->get('/active', SiteSettingController::class . ':getActive'); 
    $group->put('/use/{id}', SiteSettingController::class . ':setUse')
        ->add(new App\Middleware\AuthMiddleware('admin'));

    $group->get('/{id}', SiteSettingController::class . ':getById');

    $group->post('', SiteSettingController::class . ':create')
        ->add(new App\Middleware\AuthMiddleware('admin'));
    $group->put('/{id}', SiteSettingController::class . ':update')
        ->add(new App\Middleware\AuthMiddleware('admin'));
    $group->delete('/{id}', SiteSettingController::class . ':delete')
        ->add(new App\Middleware\AuthMiddleware('admin'));
});
