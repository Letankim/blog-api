<?php
use App\Controllers\UploadController;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1', function (RouteCollectorProxy $group) {
    $group->post('/upload', [UploadController::class, 'upload']);
    $group->get('/images/{filename}', [UploadController::class, 'serveImage']);
});